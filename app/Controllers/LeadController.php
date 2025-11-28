<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Lead;
use App\Models\SystemSetting;

/**
 * Controller de Leads
 */
class LeadController extends Controller
{
    /**
     * Exibe página de quiz (pública)
     * Aceita apenas parâmetro 'token' para identificar o dono do lead
     */
    public function quiz(): void
    {
        // Obtém token da URL
        $token = $this->request->query('token');
        $userId = null;
        
        // Se tem token, valida e busca user_id
        if ($token) {
            $userId = $this->getUserIdFromToken($token);
            
            if (!$userId) {
                // Token inválido - mostra erro
                die('
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Link Inválido</title>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                            .error { color: #dc3545; }
                        </style>
                    </head>
                    <body>
                        <h1 class="error">Link Inválido</h1>
                        <p>O link do formulário que você está tentando acessar é inválido ou expirou.</p>
                        <p>Por favor, solicite um novo link.</p>
                    </body>
                    </html>
                ');
            }
        } else {
            // Sem token - mostra erro
            die('
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Link Inválido</title>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                        .error { color: #dc3545; }
                    </style>
                </head>
                <body>
                    <h1 class="error">Link Inválido</h1>
                    <p>Este formulário requer um link válido para ser acessado.</p>
                    <p>Por favor, solicite um novo link.</p>
                </body>
                </html>
            ');
        }
        
        // O quiz é uma página standalone, não usa layout
        $viewFile = base_path('views/leads/quiz.php');
        if (file_exists($viewFile)) {
            // Armazena temporariamente na sessão para usar na API
            session()->set('quiz_owner_id', $userId);
            include $viewFile;
        } else {
            abort(404, 'Página não encontrada');
        }
    }
    
    /**
     * Valida se um user_id é válido e retorna ele
     */
    private function validateUserId($userId): ?int
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return null;
        }
        
        $user = \App\Models\User::find($userId);
        if ($user && $user->status === 'active') {
            return $userId;
        }
        
        return null;
    }
    
    /**
     * Gera token único para compartilhar link do quiz
     */
    public function generateQuizLink(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = auth()->getDataUserId();
        
        // Gera token único baseado no user_id + timestamp + hash
        $token = $this->generateUserToken($userId);
        
        // Salva token na tabela system_settings para validação
        \App\Models\SystemSetting::set("quiz_token_{$userId}", $token, 'text', 'integrations', 'Token para link do quiz');
        
        $quizUrl = url('/quiz?token=' . $token);
        
        json_response([
            'success' => true,
            'token' => $token,
            'url' => $quizUrl,
            'message' => 'Link gerado com sucesso!'
        ]);
    }
    
    /**
     * Gera token único para um usuário
     */
    private function generateUserToken(int $userId): string
    {
        $data = $userId . '|' . time() . '|' . bin2hex(random_bytes(16));
        return base64_encode(hash('sha256', $data));
    }
    
    /**
     * Valida token e retorna user_id
     */
    private function getUserIdFromToken(string $token): ?int
    {
        // Busca token salvo no system_settings (sem filtro de multi-tenancy porque é busca pública)
        $db = \Core\Database::getInstance();
        $result = $db->query(
            "SELECT `key`, value, user_id FROM system_settings WHERE `key` LIKE 'quiz_token_%' AND value = ?",
            [$token]
        );
        
        if (empty($result)) {
            return null;
        }
        
        // Pega o user_id diretamente do resultado ou extrai da key
        $row = $result[0];
        $userId = $row['user_id'] ?? null;
        
        // Se não tem user_id na coluna, tenta extrair da key (quiz_token_123)
        if (!$userId) {
            $key = $row['key'];
            $userId = (int)str_replace('quiz_token_', '', $key);
        }
        
        return $this->validateUserId($userId);
    }

    /**
     * API para receber novo lead e processar com Gemini
     */
    public function newLead(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Obtém dados JSON do body
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input)) {
                $input = $this->request->all();
            }

            // Valida dados recebidos manualmente (para API)
            $validator = new \Core\Validator($input, [
                'nome' => 'required',
                'email' => 'required|email',
                'telefone' => 'required',
                'faturamento' => 'required',
                'investimento' => 'required',
                'instagram' => 'nullable',
                'ramo' => 'nullable',
                'objetivo' => 'nullable',
                'faz_trafego' => 'nullable'
            ]);

            if (!$validator->passes()) {
                json_response([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $validator->validated();

            // Obtém user_id para associar o lead ANTES de processar
            // Prioridade: 1) Token no body, 2) Session (quiz_owner_id), 3) Usuário autenticado, 4) Primeiro admin
            $userId = null;
            
            // 1. Verifica token no body da requisição (enviado pelo formulário)
            // Usa $input (dados brutos) porque token não está nas regras de validação
            $token = $input['token'] ?? null;
            
            if ($token) {
                $userId = $this->getUserIdFromToken($token);
            }
            
            // 2. Se não encontrou no body, verifica na sessão (definido quando acessa /quiz?token=)
            if (!$userId) {
                $userId = session()->get('quiz_owner_id');
            }

            // Prepara dados para Gemini
            $promptData = [
                'faturamento' => $data['faturamento'],
                'investimento' => $data['investimento'],
                'instagram' => $data['instagram'] ?? '',
                'ramo' => $data['ramo'] ?? '',
                'objetivo' => $data['objetivo'] ?? '',
                'faz_trafego' => $data['faz_trafego'] ?? 'não'
            ];
            
            // 3. Se ainda não tem, usa usuário autenticado
            if (!$userId && auth()->check()) {
                $userId = auth()->getDataUserId();
            }
            
            // 4. Se ainda não tem, busca o primeiro admin (fallback)
            if (!$userId) {
                $adminUser = $this->getFirstAdminUser();
                $userId = $adminUser ? $adminUser->id : null;
            }
            
            // 5. Último fallback: primeiro usuário ativo
            if (!$userId) {
                $firstUser = \App\Models\User::where('status', 'active')->first();
                $userId = $firstUser ? $firstUser->id : null;
            }
            
            // Limpa a sessão após usar
            session()->forget('quiz_owner_id');

            // Chama Gemini para classificação (usa API key do usuário dono do lead)
            $aiAnalysis = $this->analyzeWithGemini($promptData, $userId);

            // Salva lead no banco
            $tagsAi = $aiAnalysis['tags_ai'] ?? [];
            // Garante que tags_ai seja sempre uma string JSON válida
            if (is_array($tagsAi)) {
                $tagsAi = json_encode($tagsAi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (!is_string($tagsAi) || json_decode($tagsAi) === null) {
                $tagsAi = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
            $leadData = [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'],
                'instagram' => $data['instagram'] ?? null,
                'ramo' => $data['ramo'] ?? null,
                'faturamento_raw' => $data['faturamento'],
                'faturamento_categoria' => $aiAnalysis['faturamento_categoria'] ?? null,
                'invest_raw' => $data['investimento'],
                'invest_categoria' => $aiAnalysis['invest_categoria'] ?? null,
                'objetivo' => $data['objetivo'] ?? null,
                'faz_trafego' => isset($data['faz_trafego']) && $data['faz_trafego'] === 'sim',
                'tags_ai' => $tagsAi,
                'score_potencial' => $aiAnalysis['score_potencial'] ?? 0,
                'urgencia' => $aiAnalysis['urgencia'] ?? 'baixa',
                'resumo' => $aiAnalysis['resumo'] ?? null,
                'status_kanban' => $this->getStatusByFaturamento($aiAnalysis['faturamento_categoria'] ?? '0-10k')
            ];

            // Adiciona user_id se encontrado
            if ($userId) {
                $leadData['user_id'] = $userId;
            }

            $lead = Lead::create($leadData);

            json_response([
                'success' => true,
                'message' => 'Lead cadastrado com sucesso!',
                'lead_id' => $lead->id
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao processar lead: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtém um modelo disponível do Gemini que suporta generateContent
     * @param string $apiKey Chave da API
     * @return array ['model' => string, 'apiVersion' => string] ou null se nenhum disponível
     */
    private function getAvailableGeminiModel(string $apiKey): ?array
    {
        // Tenta listar modelos disponíveis na v1beta primeiro
        $apiVersions = ['v1beta', 'v1'];
        
        foreach ($apiVersions as $apiVersion) {
            $listUrl = "https://generativelanguage.googleapis.com/{$apiVersion}/models?key=" . $apiKey;
            
            $ch = curl_init($listUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['models']) && is_array($data['models'])) {
                    // Lista de modelos preferidos (em ordem de prioridade, excluindo experimentais)
                    $preferredModels = [
                        'gemini-1.5-flash-latest',
                        'gemini-1.5-pro-latest',
                        'gemini-1.5-flash',
                        'gemini-1.5-pro',
                        'gemini-pro-latest',
                        'gemini-pro'
                    ];
                    
                    $availableModels = [];
                    
                    // Primeiro, coleta todos os modelos disponíveis que suportam generateContent
                    foreach ($data['models'] as $model) {
                        if (isset($model['name']) && isset($model['supportedGenerationMethods'])) {
                            $methods = $model['supportedGenerationMethods'];
                            if (in_array('generateContent', $methods)) {
                                // Extrai o nome do modelo (remove o prefixo "models/")
                                $modelName = str_replace('models/', '', $model['name']);
                                
                                // Ignora modelos experimentais (contêm "exp" no nome)
                                if (stripos($modelName, 'exp') === false && stripos($modelName, 'experimental') === false) {
                                    $availableModels[] = $modelName;
                                }
                            }
                        }
                    }
                    
                    // Tenta encontrar um modelo preferido primeiro
                    foreach ($preferredModels as $preferred) {
                        if (in_array($preferred, $availableModels)) {
                            return [
                                'model' => $preferred,
                                'apiVersion' => $apiVersion
                            ];
                        }
                    }
                    
                    // Se não encontrou um preferido, usa o primeiro disponível
                    if (!empty($availableModels)) {
                        return [
                            'model' => $availableModels[0],
                            'apiVersion' => $apiVersion
                        ];
                    }
                }
            }
        }
        
        // Se não conseguiu listar, retorna o modelo mais comum com v1beta
        return [
            'model' => 'gemini-1.5-flash-latest',
            'apiVersion' => 'v1beta'
        ];
    }

    /**
     * Analisa lead com Gemini AI
     * @param array $data Dados do lead
     * @param int|null $userId ID do usuário dono do lead (para buscar sua API key)
     */
    private function analyzeWithGemini(array $data, ?int $userId = null): array
    {
        // Busca API key do usuário específico
        $apiKey = $this->getGeminiApiKey($userId);
        
        if (empty($apiKey)) {
            // Retorna valores padrão se não houver API key
            return [
                'faturamento_categoria' => $this->categorizeFaturamento($data['faturamento']),
                'invest_categoria' => $this->categorizeInvestimento($data['investimento']),
                'tags_ai' => [],
                'score_potencial' => 50,
                'urgencia' => 'media',
                'resumo' => 'Análise não disponível - configure a API do Gemini'
            ];
        }

        // Obtém um modelo disponível
        $modelInfo = $this->getAvailableGeminiModel($apiKey);
        
        if (!$modelInfo) {
            throw new \Exception('Nenhum modelo do Gemini disponível. Verifique sua API key e permissões.');
        }

        $model = $modelInfo['model'];
        $apiVersion = $modelInfo['apiVersion'];

        $prompt = "Você é um assistente de qualificação de leads para uma agência de tráfego pago. Receba as respostas abaixo e devolva um JSON contendo:

faturamento_categoria (0-10k, 10-50k, 50-200k, 200k+)
invest_categoria (1k, 3k, 5k, 10k, 10k+)
tags_ai = lista com insights do lead
score_potencial (0-100)
urgencia (baixa, média, alta)
resumo = descrição curta do potencial do lead

Responda apenas com JSON puro.

Dados do lead:
- Faturamento: {$data['faturamento']}
- Investimento pretendido: {$data['investimento']}
- Instagram: {$data['instagram']}
- Ramo: {$data['ramo']}
- Objetivo: {$data['objetivo']}
- Já faz tráfego pago: {$data['faz_trafego']}";

        $url = "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$model}:generateContent?key=" . $apiKey;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMsg = $response ?? 'Sem resposta';
            
            // Tratamento especial para erro 429 (quota excedida)
            if ($httpCode === 429) {
                $errorData = json_decode($errorMsg, true);
                $retryAfter = null;
                
                if (isset($errorData['error']['details'])) {
                    foreach ($errorData['error']['details'] as $detail) {
                        if (isset($detail['@type']) && $detail['@type'] === 'type.googleapis.com/google.rpc.RetryInfo') {
                            if (isset($detail['retryDelay'])) {
                                $retryAfter = $detail['retryDelay'];
                            }
                        }
                    }
                }
                
                $quotaMessage = 'Quota da API do Gemini excedida. ';
                if ($retryAfter) {
                    $quotaMessage .= "Tente novamente em {$retryAfter}. ";
                }
                $quotaMessage .= 'Verifique seu plano e limites em: https://ai.google.dev/gemini-api/docs/rate-limits';
                
                throw new \Exception($quotaMessage);
            }
            
            if ($curlError) {
                $errorMsg .= ' | cURL Error: ' . $curlError;
            }
            throw new \Exception('Erro ao chamar API do Gemini (HTTP ' . $httpCode . '): ' . $errorMsg);
        }

        $result = json_decode($response, true);
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Resposta inválida da API do Gemini');
        }

        $text = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Remove markdown code blocks se houver
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $analysis = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Erro ao decodificar JSON da resposta do Gemini');
        }

        return $analysis;
    }

    /**
     * Categoriza faturamento manualmente
     */
    private function categorizeFaturamento(string $faturamento): string
    {
        $faturamento = strtolower($faturamento);
        
        if (preg_match('/200|milhão|million/i', $faturamento)) {
            return '200k+';
        } elseif (preg_match('/50|100|150/i', $faturamento)) {
            return '50-200k';
        } elseif (preg_match('/10|20|30|40/i', $faturamento)) {
            return '10-50k';
        }
        
        return '0-10k';
    }

    /**
     * Categoriza investimento manualmente
     */
    private function categorizeInvestimento(string $investimento): string
    {
        $investimento = strtolower($investimento);
        
        if (preg_match('/10|mais|above/i', $investimento)) {
            return '10k+';
        } elseif (preg_match('/5|cinco/i', $investimento)) {
            return '5k';
        } elseif (preg_match('/3|três|three/i', $investimento)) {
            return '3k';
        }
        
        return '1k';
    }

    /**
     * Retorna status kanban baseado no faturamento
     */
    private function getStatusByFaturamento(string $categoria): string
    {
        return match($categoria) {
            '0-10k' => 'cold',
            '10-50k' => 'morno',
            '50-200k' => 'quente',
            '200k+' => 'ultra_quente',
            default => 'cold'
        };
    }

    /**
     * Busca API key do Gemini para um usuário específico
     */
    private function getGeminiApiKey(?int $userId = null): ?string
    {
        if ($userId === null) {
            $userId = auth()->check() ? auth()->getDataUserId() : null;
        }
        
        if ($userId === null) {
            return null;
        }
        
        // Busca diretamente no banco para o usuário específico
        $db = \Core\Database::getInstance();
        $result = $db->queryOne(
            "SELECT value FROM system_settings WHERE `key` = 'gemini_api_key' AND user_id = ?",
            [$userId]
        );
        
        return $result['value'] ?? null;
    }

    /**
     * Retorna o primeiro usuário admin do sistema
     */
    private function getFirstAdminUser(): ?\App\Models\User
    {
        // Busca usuários com role admin ou super-admin
        $sql = "
            SELECT DISTINCT u.* 
            FROM users u
            INNER JOIN user_role ur ON ur.user_id = u.id
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE (r.slug = 'admin' OR r.slug = 'super-admin')
            AND u.status = 'active'
            ORDER BY u.id ASC
            LIMIT 1
        ";

        $result = \Core\Database::getInstance()->queryOne($sql);
        
        if ($result) {
            return \App\Models\User::newInstance($result, true);
        }

        // Se não encontrar admin, retorna o primeiro usuário ativo
        return \App\Models\User::where('status', 'active')->first();
    }

    /**
     * Exibe CRM com Kanban
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $leads = [
            'cold' => Lead::byStatus('cold'),
            'morno' => Lead::byStatus('morno'),
            'quente' => Lead::byStatus('quente'),
            'ultra_quente' => Lead::byStatus('ultra_quente')
        ];

        return $this->view('leads/index', [
            'title' => 'CRM de Leads',
            'leads' => $leads
        ]);
    }

    /**
     * Exibe formulário de cadastro manual de lead
     */
    public function create(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        return $this->view('leads/create', [
            'title' => 'Cadastrar Lead'
        ]);
    }

    /**
     * Salva lead cadastrado manualmente
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/leads/create');
        }

        $data = $this->validate([
            'nome' => 'required',
            'email' => 'required|email',
            'telefone' => 'required',
            'faturamento' => 'required',
            'investimento' => 'required',
            'instagram' => 'nullable',
            'ramo' => 'nullable',
            'objetivo' => 'nullable',
            'faz_trafego' => 'nullable'
        ]);

        try {
            $userId = auth()->getDataUserId();

            // Prepara dados para Gemini
            $promptData = [
                'faturamento' => $data['faturamento'],
                'investimento' => $data['investimento'],
                'instagram' => $data['instagram'] ?? '',
                'ramo' => $data['ramo'] ?? '',
                'objetivo' => $data['objetivo'] ?? '',
                'faz_trafego' => isset($data['faz_trafego']) && $data['faz_trafego'] === 'sim' ? 'sim' : 'não'
            ];

            // Chama Gemini para classificação (usa API key do usuário logado)
            $aiAnalysis = $this->analyzeWithGemini($promptData, $userId);

            // Salva lead no banco
            $tagsAi = $aiAnalysis['tags_ai'] ?? [];
            // Garante que tags_ai seja sempre uma string JSON válida
            if (is_array($tagsAi)) {
                $tagsAi = json_encode($tagsAi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (!is_string($tagsAi) || json_decode($tagsAi) === null) {
                $tagsAi = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
            $leadData = [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'],
                'instagram' => $data['instagram'] ?? null,
                'ramo' => $data['ramo'] ?? null,
                'faturamento_raw' => $data['faturamento'],
                'faturamento_categoria' => $aiAnalysis['faturamento_categoria'] ?? null,
                'invest_raw' => $data['investimento'],
                'invest_categoria' => $aiAnalysis['invest_categoria'] ?? null,
                'objetivo' => $data['objetivo'] ?? null,
                'faz_trafego' => isset($data['faz_trafego']) && $data['faz_trafego'] === 'sim',
                'tags_ai' => $tagsAi,
                'score_potencial' => $aiAnalysis['score_potencial'] ?? 0,
                'urgencia' => $aiAnalysis['urgencia'] ?? 'baixa',
                'resumo' => $aiAnalysis['resumo'] ?? null,
                'status_kanban' => $this->getStatusByFaturamento($aiAnalysis['faturamento_categoria'] ?? '0-10k'),
                'user_id' => $userId
            ];

            $lead = Lead::create($leadData);

            session()->flash('success', 'Lead cadastrado com sucesso!');
            $this->redirect('/leads');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar lead: ' . $e->getMessage());
            session()->flash('old', $this->request->all());
            $this->redirect('/leads/create');
        }
    }

    /**
     * Exibe detalhes do lead
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $lead = Lead::find($params['id']);

        if (!$lead) {
            abort(404, 'Lead não encontrado.');
        }

        return $this->view('leads/show', [
            'title' => 'Detalhes do Lead',
            'lead' => $lead
        ]);
    }

    /**
     * Atualiza status kanban via AJAX
     */
    public function updateStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'lead_id' => 'required|numeric',
            'status' => 'required'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $status = $input['status'];
        if (!in_array($status, ['cold', 'morno', 'quente', 'ultra_quente'])) {
            json_response(['success' => false, 'message' => 'Status inválido'], 400);
        }

        $data = $validator->validated();
        $data['status'] = $status;

        $lead = Lead::find($data['lead_id']);

        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
        }

        $lead->updateStatus($data['status']);

        json_response([
            'success' => true,
            'message' => 'Status atualizado com sucesso'
        ]);
    }

    /**
     * Reanalisa lead com Gemini
     */
    public function reanalyze(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $lead = Lead::find($params['id']);

        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
        }

        try {
            $promptData = [
                'faturamento' => $lead->faturamento_raw ?? '',
                'investimento' => $lead->invest_raw ?? '',
                'instagram' => $lead->instagram ?? '',
                'ramo' => $lead->ramo ?? '',
                'objetivo' => $lead->objetivo ?? '',
                'faz_trafego' => $lead->faz_trafego ? 'sim' : 'não'
            ];

            // Usa a API key do usuário dono do lead
            $userId = $lead->user_id ?? auth()->getDataUserId();
            $aiAnalysis = $this->analyzeWithGemini($promptData, $userId);

            // Garante que tags_ai seja sempre uma string JSON válida
            $tagsAi = $aiAnalysis['tags_ai'] ?? [];
            if (is_array($tagsAi)) {
                $tagsAi = json_encode($tagsAi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (!is_string($tagsAi) || json_decode($tagsAi) === null) {
                $tagsAi = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $lead->update([
                'faturamento_categoria' => $aiAnalysis['faturamento_categoria'] ?? $lead->faturamento_categoria,
                'invest_categoria' => $aiAnalysis['invest_categoria'] ?? $lead->invest_categoria,
                'tags_ai' => $tagsAi,
                'score_potencial' => $aiAnalysis['score_potencial'] ?? $lead->score_potencial,
                'urgencia' => $aiAnalysis['urgencia'] ?? $lead->urgencia,
                'resumo' => $aiAnalysis['resumo'] ?? $lead->resumo,
                'status_kanban' => $this->getStatusByFaturamento($aiAnalysis['faturamento_categoria'] ?? $lead->faturamento_categoria)
            ]);

            json_response([
                'success' => true,
                'message' => 'Lead reanalisado com sucesso',
                'lead' => $lead->toArray()
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao reanalisar: ' . $e->getMessage()
            ], 500);
        }
    }
}

