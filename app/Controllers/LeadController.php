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
                'tem_software' => 'nullable',
                'investimento_software' => 'required',
                'tipo_sistema' => 'required',
                'plataforma_app' => 'required',
                'ramo' => 'nullable',
                'objetivo' => 'nullable',
                'origem_conheceu' => 'nullable',
                'valor_oportunidade' => 'nullable|numeric|min:0'
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
                'tem_software' => $data['tem_software'] ?? false,
                'investimento_software' => $data['investimento_software'] ?? '',
                'tipo_sistema' => $data['tipo_sistema'] ?? '',
                'plataforma_app' => $data['plataforma_app'] ?? '',
                'ramo' => $data['ramo'] ?? '',
                'objetivo' => $data['objetivo'] ?? ''
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

            // VERIFICA SE JÁ EXISTE CLIENTE COM MESMO EMAIL OU TELEFONE
            // Se não existir, CRIA AUTOMATICAMENTE um novo cliente
            $existingClient = null;
            if ($userId) {
                $db = \Core\Database::getInstance();
                
                // Busca por email (se fornecido)
                if (!empty($data['email'])) {
                    $clientByEmail = $db->query(
                        "SELECT * FROM clients WHERE user_id = ? AND email = ? LIMIT 1",
                        [$userId, $data['email']]
                    );
                    if (!empty($clientByEmail)) {
                        $existingClient = \App\Models\Client::newInstance($clientByEmail[0], true);
                    }
                }
                
                // Se não encontrou por email, busca por telefone (se fornecido)
                if (!$existingClient && !empty($data['telefone'])) {
                    $clientByPhone = $db->query(
                        "SELECT * FROM clients WHERE user_id = ? AND (telefone = ? OR celular = ?) LIMIT 1",
                        [$userId, $data['telefone'], $data['telefone']]
                    );
                    if (!empty($clientByPhone)) {
                        $existingClient = \App\Models\Client::newInstance($clientByPhone[0], true);
                    }
                }
                
                // Se não encontrou cliente existente, CRIA UM NOVO automaticamente
                if (!$existingClient) {
                    $existingClient = \App\Models\Client::create([
                        'user_id' => $userId,
                        'tipo' => 'fisica', // Padrão: Pessoa Física
                        'nome_razao_social' => $data['nome'],
                        'email' => $data['email'] ?? null,
                        'telefone' => $data['telefone'] ?? null,
                        'score' => 50 // Score padrão
                    ]);
                }
            }

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
                'objetivo' => $data['objetivo'] ?? null,
                'tem_software' => isset($data['tem_software']) && ($data['tem_software'] === true || $data['tem_software'] === 'sim'),
                'investimento_software' => $data['investimento_software'] ?? null,
                'tipo_sistema' => $data['tipo_sistema'] ?? null,
                'plataforma_app' => $data['plataforma_app'] ?? null,
                'valor_oportunidade' => isset($data['valor_oportunidade']) && !empty($data['valor_oportunidade']) && (float)$data['valor_oportunidade'] > 0 ? (float)$data['valor_oportunidade'] : null,
                'tags_ai' => $tagsAi,
                'score_potencial' => $aiAnalysis['score_potencial'] ?? 0,
                'urgencia' => $aiAnalysis['urgencia'] ?? 'baixa',
                'resumo' => $aiAnalysis['resumo'] ?? null,
                'etapa_funil' => 'interessados',
                'origem' => 'quiz',
                'origem_conheceu' => $data['origem_conheceu'] ?? null
            ];

            // Adiciona user_id se encontrado
            if ($userId) {
                $leadData['user_id'] = $userId;
            }

            // Se encontrou cliente existente, vincula o lead a ele
            if ($existingClient) {
                $leadData['client_id'] = $existingClient->id;
            }

            $lead = Lead::create($leadData);

            $message = 'Lead cadastrado com sucesso!';
            if ($existingClient) {
                // Verifica se o cliente foi criado agora ou já existia
                $clientCreated = strtotime($existingClient->created_at) > (time() - 5); // Criado há menos de 5 segundos
                if ($clientCreated) {
                    $message .= ' Cliente criado automaticamente: ' . $existingClient->nome_razao_social;
                } else {
                    $message .= ' Lead vinculado ao cliente existente: ' . $existingClient->nome_razao_social;
                }
            }

            json_response([
                'success' => true,
                'message' => $message,
                'lead_id' => $lead->id,
                'client_id' => $existingClient ? $existingClient->id : null,
                'client_linked' => $existingClient ? true : false
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

        $temSoftware = isset($data['tem_software']) ? ($data['tem_software'] ? 'Sim' : 'Não') : 'Não informado';
        $investimento = $data['investimento_software'] ?? 'Não informado';
        $tipoSistema = $data['tipo_sistema'] ?? 'Não informado';
        $plataformaApp = $data['plataforma_app'] ?? 'Não informado';
        $ramo = $data['ramo'] ?? 'Não informado';
        $objetivo = $data['objetivo'] ?? 'Não informado';

        $prompt = "Você é um assistente de qualificação de leads para uma empresa de desenvolvimento de software. Receba as respostas abaixo e devolva um JSON contendo:

tags_ai = lista com insights do lead (ex: ['alto investimento', 'saas', 'mobile', 'empresa estabelecida'])
score_potencial (0-100) = pontuação baseada no potencial de fechamento
urgencia (baixa, média, alta) = urgência do lead
resumo = descrição curta do potencial do lead (máximo 200 caracteres)

Responda apenas com JSON puro, sem markdown, sem explicações.

Dados do lead:
- Já possui software: {$temSoftware}
- Investimento pretendido: {$investimento}
- Tipo de sistema: {$tipoSistema}
- Plataforma de aplicativo: {$plataformaApp}
- Ramo da empresa: {$ramo}
- Objetivo: {$objetivo}";

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
     * Exibe CRM com Kanban baseado em etapas do funil
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        
        // Busca leads por etapa do funil
        $leads = [
            'interessados' => Lead::where('etapa_funil', 'interessados')
                ->where('user_id', $userId)
                ->orderBy('score_potencial', 'DESC')
                ->get(),
            'negociacao_proposta' => Lead::where('etapa_funil', 'negociacao_proposta')
                ->where('user_id', $userId)
                ->orderBy('score_potencial', 'DESC')
                ->get(),
            'fechamento' => Lead::where('etapa_funil', 'fechamento')
                ->where('user_id', $userId)
                ->orderBy('score_potencial', 'DESC')
                ->get()
        ];
        
        // Busca usuários para atribuir responsáveis
        $users = \App\Models\User::where('status', 'active')->get();
        
        // Calcula métricas
        $totalLeads = count($leads['interessados']) + count($leads['negociacao_proposta']) + count($leads['fechamento']);
        $metrics = [
            'total' => $totalLeads,
            'interessados' => [
                'count' => count($leads['interessados']),
                'percentage' => $totalLeads > 0 ? round((count($leads['interessados']) / $totalLeads) * 100, 1) : 0
            ],
            'negociacao_proposta' => [
                'count' => count($leads['negociacao_proposta']),
                'percentage' => $totalLeads > 0 ? round((count($leads['negociacao_proposta']) / $totalLeads) * 100, 1) : 0
            ],
            'fechamento' => [
                'count' => count($leads['fechamento']),
                'percentage' => $totalLeads > 0 ? round((count($leads['fechamento']) / $totalLeads) * 100, 1) : 0
            ]
        ];
        
        // Busca origens dos leads
        $origens = \Core\Database::getInstance()->query(
            "SELECT origem, COUNT(*) as total 
             FROM leads 
             WHERE user_id = ? AND origem IS NOT NULL 
             GROUP BY origem 
             ORDER BY total DESC",
            [$userId]
        );

        return $this->view('leads/index', [
            'title' => 'CRM de Leads',
            'leads' => $leads,
            'users' => $users,
            'metrics' => $metrics,
            'origens' => $origens
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

        $userId = auth()->getDataUserId();
        $clients = \App\Models\Client::where('user_id', $userId)
            ->orderBy('nome_razao_social', 'ASC')
            ->get();

        return $this->view('leads/create', [
            'title' => 'Cadastrar Lead',
            'clients' => $clients
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
            'tem_software' => 'required|in:sim,nao',
            'investimento_software' => 'required|in:5k,10k,25k,50k,50k+',
            'tipo_sistema' => 'required|in:interno,cliente,saas',
            'plataforma_app' => 'required|in:ios_android,ios,android,nenhum',
            'valor_oportunidade' => 'nullable|numeric|min:0',
            'instagram' => 'nullable',
            'ramo' => 'nullable',
            'objetivo' => 'nullable',
            'origem_conheceu' => 'nullable'
        ]);

        try {
            $userId = auth()->getDataUserId();

            // VERIFICA OU CRIA CLIENTE
            $existingClient = null;
            $clientIdFromForm = $this->request->input('client_id');
            
            if ($clientIdFromForm) {
                // Se foi selecionado um cliente existente, busca ele
                $existingClient = \App\Models\Client::where('id', $clientIdFromForm)
                    ->where('user_id', $userId)
                    ->first();
            } else {
                // Se não foi selecionado, verifica se já existe ou cria novo
                $db = \Core\Database::getInstance();
                
                // Busca por email (se fornecido)
                if (!empty($data['email'])) {
                    $clientByEmail = $db->query(
                        "SELECT * FROM clients WHERE user_id = ? AND email = ? LIMIT 1",
                        [$userId, $data['email']]
                    );
                    if (!empty($clientByEmail)) {
                        $existingClient = \App\Models\Client::newInstance($clientByEmail[0], true);
                    }
                }
                
                // Se não encontrou por email, busca por telefone (se fornecido)
                if (!$existingClient && !empty($data['telefone'])) {
                    $clientByPhone = $db->query(
                        "SELECT * FROM clients WHERE user_id = ? AND (telefone = ? OR celular = ?) LIMIT 1",
                        [$userId, $data['telefone'], $data['telefone']]
                    );
                    if (!empty($clientByPhone)) {
                        $existingClient = \App\Models\Client::newInstance($clientByPhone[0], true);
                    }
                }
                
                // Se não encontrou cliente existente, CRIA UM NOVO automaticamente
                if (!$existingClient) {
                    $existingClient = \App\Models\Client::create([
                        'user_id' => $userId,
                        'tipo' => 'fisica', // Padrão: Pessoa Física
                        'nome_razao_social' => $data['nome'],
                        'email' => $data['email'] ?? null,
                        'telefone' => $data['telefone'] ?? null,
                        'score' => 50 // Score padrão
                    ]);
                }
            }

            // Prepara dados para Gemini (mesmo formato do quiz)
            $promptData = [
                'tem_software' => $data['tem_software'] ?? false,
                'investimento_software' => $data['investimento_software'] ?? '',
                'tipo_sistema' => $data['tipo_sistema'] ?? '',
                'plataforma_app' => $data['plataforma_app'] ?? '',
                'ramo' => $data['ramo'] ?? '',
                'objetivo' => $data['objetivo'] ?? '',
                'origem_conheceu' => $data['origem_conheceu'] ?? ''
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
                'tem_software' => isset($data['tem_software']) && ($data['tem_software'] === true || $data['tem_software'] === 'sim'),
                'investimento_software' => $data['investimento_software'] ?? null,
                'tipo_sistema' => $data['tipo_sistema'] ?? null,
                'plataforma_app' => $data['plataforma_app'] ?? null,
                'valor_oportunidade' => isset($data['valor_oportunidade']) && !empty($data['valor_oportunidade']) && (float)$data['valor_oportunidade'] > 0 ? (float)$data['valor_oportunidade'] : null,
                'objetivo' => $data['objetivo'] ?? null,
                'tags_ai' => $tagsAi,
                'score_potencial' => $aiAnalysis['score_potencial'] ?? 0,
                'urgencia' => $aiAnalysis['urgencia'] ?? 'baixa',
                'resumo' => $aiAnalysis['resumo'] ?? null,
                'etapa_funil' => 'interessados',
                'origem' => 'cadastro_manual',
                'origem_conheceu' => $data['origem_conheceu'] ?? null,
                'user_id' => $userId
            ];

            // Vincula o lead ao cliente (existente ou recém-criado)
            if ($existingClient) {
                $leadData['client_id'] = $existingClient->id;
            }

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

        $userId = auth()->getDataUserId();
        $lead = Lead::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$lead) {
            abort(404, 'Lead não encontrado.');
        }
        
        // Busca relacionamentos
        $client = $lead->client();
        $proposals = $lead->proposals();
        $contacts = $lead->contacts();
        $users = \App\Models\User::where('status', 'active')->get();

        return $this->view('leads/show', [
            'title' => 'Detalhes do Lead',
            'lead' => $lead,
            'client' => $client,
            'proposals' => $proposals,
            'contacts' => $contacts,
            'users' => $users
        ]);
    }
    
    /**
     * Retorna HTML do modal de edição (via AJAX)
     */
    public function editModal(array $params): void
    {
        if (!auth()->check()) {
            http_response_code(401);
            echo 'Não autenticado';
            exit;
        }

        $userId = auth()->getDataUserId();
        $lead = Lead::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$lead) {
            http_response_code(404);
            echo 'Lead não encontrado';
            exit;
        }
        
        $users = \App\Models\User::where('status', 'active')->get();
        
        // Renderiza apenas o conteúdo do modal
        $viewFile = base_path('views/leads/_edit_modal.php');
        if (file_exists($viewFile)) {
            // Inclui o nome do lead em um atributo data para facilitar o acesso via JavaScript
            echo '<div data-lead-nome="' . htmlspecialchars($lead->nome, ENT_QUOTES, 'UTF-8') . '">';
            include $viewFile;
            echo '</div>';
        } else {
            echo '<p>Erro ao carregar formulário de edição.</p>';
        }
        exit;
    }
    
    /**
     * Atualiza lead
     */
    public function update(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $lead = Lead::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
            return;
        }

        $data = $this->validate([
            'nome' => 'required',
            'email' => 'required|email',
            'telefone' => 'required',
            'valor_oportunidade' => 'nullable|numeric|min:0',
            'etapa_funil' => 'nullable|in:interessados,negociacao_proposta,fechamento',
            'responsible_user_id' => 'nullable|numeric',
            'origem' => 'nullable',
            'observacoes' => 'nullable'
        ]);

        try {
            // Trata valor_oportunidade: se foi enviado e é numérico, salva (pode ser 0 para limpar)
            $valorOportunidade = null;
            if (isset($data['valor_oportunidade'])) {
                $valor = trim((string)$data['valor_oportunidade']);
                if ($valor !== '' && is_numeric($valor)) {
                    $valorFloat = (float)$valor;
                    // Permite salvar 0 ou valores positivos
                    $valorOportunidade = $valorFloat >= 0 ? $valorFloat : null;
                }
            }
            
            $lead->update([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'],
                'valor_oportunidade' => $valorOportunidade,
                'etapa_funil' => $data['etapa_funil'] ?? $lead->etapa_funil,
                'responsible_user_id' => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
                'origem' => $data['origem'] ?? $lead->origem
            ]);

            json_response([
                'success' => true,
                'message' => 'Lead atualizado com sucesso!'
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar lead: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Atualiza etapa do funil via AJAX
     */
    public function updateEtapaFunil(): void
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
            'etapa_funil' => 'required'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $etapa = $input['etapa_funil'];
        if (!in_array($etapa, ['interessados', 'negociacao_proposta', 'fechamento'])) {
            json_response(['success' => false, 'message' => 'Etapa inválida'], 400);
        }

        $lead = Lead::find($input['lead_id']);

        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
        }

        $lead->updateEtapaFunil($etapa);

        json_response([
            'success' => true,
            'message' => 'Etapa atualizada com sucesso'
        ]);
    }
    
    /**
     * Atualiza responsável do lead via AJAX
     */
    public function updateResponsible(): void
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
            'responsible_user_id' => 'nullable|numeric'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $lead = Lead::find($input['lead_id']);

        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
        }

        $lead->update([
            'responsible_user_id' => !empty($input['responsible_user_id']) ? (int)$input['responsible_user_id'] : null
        ]);

        json_response([
            'success' => true,
            'message' => 'Responsável atualizado com sucesso'
        ]);
    }
    
    /**
     * Atualiza status kanban via AJAX (mantido para compatibilidade)
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

        $lead = Lead::find($input['lead_id']);

        if (!$lead) {
            json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
        }

        $lead->updateStatus($status);

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

    /**
     * API para buscar origens configuradas (pública, usa token)
     */
    public function getOrigens(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $token = $this->request->query('token');
            $userId = null;

            if ($token) {
                $userId = $this->getUserIdFromToken($token);
            }

            if (!$userId) {
                json_response([
                    'success' => false,
                    'message' => 'Token inválido',
                    'origens' => []
                ], 401);
            }

            // Busca origens ativas do usuário
            $origens = \App\Models\LeadOrigin::getActiveOrigins($userId);

            // Se não tem origens configuradas, retorna lista padrão
            if (empty($origens)) {
                $origens = [
                    (object)['nome' => 'Google'],
                    (object)['nome' => 'Facebook/Instagram'],
                    (object)['nome' => 'Indicação'],
                    (object)['nome' => 'LinkedIn'],
                    (object)['nome' => 'YouTube'],
                    (object)['nome' => 'Outro']
                ];
            }

            json_response([
                'success' => true,
                'origens' => array_map(function($origem) {
                    return [
                        'id' => $origem->id ?? null,
                        'nome' => is_string($origem) ? $origem : ($origem->nome ?? '')
                    ];
                }, $origens)
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao buscar origens: ' . $e->getMessage(),
                'origens' => []
            ], 500);
        }
    }

    /**
     * Converte lead em cliente
     */
    public function convertToClient(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        try {
            $userId = auth()->getDataUserId();
            $leadId = $params['id'] ?? null;

            if (!$leadId) {
                json_response(['success' => false, 'message' => 'ID do lead não informado'], 400);
            }

            // Busca lead
            $lead = Lead::where('id', $leadId)
                ->where('user_id', $userId)
                ->first();

            if (!$lead) {
                json_response(['success' => false, 'message' => 'Lead não encontrado'], 404);
            }

            // Valida dados do formulário
            $data = $this->validate([
                'tipo' => 'required|in:fisica,juridica',
                'nome_razao_social' => 'required',
                'nome_fantasia' => 'nullable',
                'cpf_cnpj' => 'nullable',
                'email' => 'nullable|email',
                'telefone' => 'nullable',
                'celular' => 'nullable',
                'score' => 'nullable|integer|min:0|max:100',
                'observacoes' => 'nullable'
            ]);

            // Cria cliente
            $client = \App\Models\Client::create([
                'user_id' => $userId,
                'tipo' => $data['tipo'],
                'nome_razao_social' => $data['nome_razao_social'],
                'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                'email' => $data['email'] ?? $lead->email,
                'telefone' => $data['telefone'] ?? $lead->telefone,
                'celular' => $data['celular'] ?? null,
                'score' => $data['score'] ?? $lead->score_potencial ?? 50,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            // Associa cliente ao lead
            $lead->update(['client_id' => $client->id]);

            json_response([
                'success' => true,
                'message' => 'Cliente cadastrado com sucesso!',
                'client_id' => $client->id
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao cadastrar cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}

