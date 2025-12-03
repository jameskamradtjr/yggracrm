<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractService;
use App\Models\ContractCondition;
use App\Models\ContractSignature;
use App\Models\Client;
use App\Models\SistemaLog;
use App\Services\ContractService as ContractServiceHelper;
use App\Services\PdfService;
use App\Services\ResendService;
use App\Services\SmtpService;
use App\Services\Automation\AutomationEventDispatcher;

class ContractController extends Controller
{
    /**
     * Lista contratos
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $status = $this->request->query('status', 'all');
        $search = $this->request->query('search');
        
        $query = Contract::where('user_id', $userId);
        
        if ($status !== 'all') {
            $query = $query->where('status', $status);
        }
        
        if ($search) {
            $query = $query->where('titulo', 'LIKE', "%{$search}%")
                ->orWhere('numero_contrato', 'LIKE', "%{$search}%");
        }
        
        $contracts = $query->orderBy('created_at', 'DESC')->get();

        return $this->view('contracts/index', [
            'title' => 'Contratos',
            'contracts' => $contracts,
            'status' => $status,
            'search' => $search
        ]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        
        $templates = ContractTemplate::where('user_id', $userId)
            ->where('ativo', true)
            ->orderBy('nome', 'ASC')
            ->get();
        
        $clients = Client::where('user_id', $userId)
            ->orderBy('nome_razao_social', 'ASC')
            ->get();

        return $this->view('contracts/create', [
            'title' => 'Novo Contrato',
            'templates' => $templates,
            'clients' => $clients
        ]);
    }

    /**
     * Salva novo contrato
     */
    public function store(): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts/create');
        }

        $data = $this->validate([
            'template_id' => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'titulo' => 'required|min:3|max:255',
            'data_inicio' => 'nullable|date',
            'data_termino' => 'nullable|date',
            'valor_total' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            // Gera número do contrato
            $numeroContrato = Contract::gerarNumeroContrato();
            
            // Processa template se fornecido
            $conteudoGerado = '';
            if (!empty($data['template_id'])) {
                $template = ContractTemplate::find($data['template_id']);
                if ($template && $template->user_id === $userId) {
                    $client = !empty($data['client_id']) ? Client::find($data['client_id']) : null;
                    $conteudoGerado = ContractServiceHelper::substituirVariaveis(
                        $template->conteudo,
                        new Contract(['numero_contrato' => $numeroContrato, 'data_inicio' => $data['data_inicio'] ?? null, 'data_termino' => $data['data_termino'] ?? null, 'valor_total' => $data['valor_total'] ?? null]),
                        $client
                    );
                }
            }
            
            $contract = Contract::create([
                'user_id' => $userId,
                'template_id' => !empty($data['template_id']) ? (int)$data['template_id'] : null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'numero_contrato' => $numeroContrato,
                'titulo' => $data['titulo'],
                'conteudo_gerado' => $conteudoGerado,
                'status' => 'rascunho',
                'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
                'data_termino' => !empty($data['data_termino']) ? $data['data_termino'] : null,
                'valor_total' => !empty($data['valor_total']) ? (float)$data['valor_total'] : null,
                'observacoes' => $data['observacoes'] ?? null
            ]);
            
            // Gera token público para compartilhamento
            if (!$contract->token_publico) {
                $contract->gerarTokenPublico();
                $contract->save();
            }

            // Dispara evento de automação
            AutomationEventDispatcher::onContract('created', $contract->id, $userId);
            
            SistemaLog::registrar(
                'contracts',
                'CREATE',
                $contract->id,
                "Contrato criado: {$contract->titulo} ({$contract->numero_contrato})",
                null,
                $contract->toArray()
            );

            session()->flash('success', 'Contrato criado com sucesso!');
            $this->redirect('/contracts/' . $contract->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar contrato: ' . $e->getMessage());
            $this->redirect('/contracts/create');
        }
    }

    /**
     * Exibe detalhes do contrato
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Contrato não encontrado.');
            $this->redirect('/contracts');
        }

        $client = $contract->client();
        $template = $contract->template();
        $services = $contract->services();
        $conditions = $contract->conditions();
        $signatures = $contract->signatures();

        return $this->view('contracts/show', [
            'title' => 'Detalhes do Contrato',
            'contract' => $contract,
            'client' => $client,
            'template' => $template,
            'services' => $services,
            'conditions' => $conditions,
            'signatures' => $signatures
        ]);
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Contrato não encontrado.');
            $this->redirect('/contracts');
        }

        $userId = auth()->getDataUserId();
        
        $templates = ContractTemplate::where('user_id', $userId)
            ->where('ativo', true)
            ->orderBy('nome', 'ASC')
            ->get();
        
        $clients = Client::where('user_id', $userId)
            ->orderBy('nome_razao_social', 'ASC')
            ->get();
        
        $services = $contract->services();
        $conditions = $contract->conditions();
        $signatures = $contract->signatures();

        return $this->view('contracts/edit', [
            'title' => 'Editar Contrato',
            'contract' => $contract,
            'templates' => $templates,
            'clients' => $clients,
            'services' => $services,
            'conditions' => $conditions,
            'signatures' => $signatures
        ]);
    }

    /**
     * Atualiza contrato
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Contrato não encontrado.');
            $this->redirect('/contracts');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts/' . $contract->id . '/edit');
        }

        $data = $this->validate([
            'template_id' => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'titulo' => 'required|min:3|max:255',
            'data_inicio' => 'nullable|date',
            'data_termino' => 'nullable|date',
            'valor_total' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable',
            'conteudo_gerado' => 'nullable'
        ]);

        try {
            $oldData = $contract->toArray();
            
            // Se mudou template ou cliente, regenera conteúdo
            if ((!empty($data['template_id']) && $contract->template_id != $data['template_id']) ||
                (!empty($data['client_id']) && $contract->client_id != $data['client_id'])) {
                $template = ContractTemplate::find($data['template_id']);
                if ($template) {
                    $client = !empty($data['client_id']) ? Client::find($data['client_id']) : null;
                    $data['conteudo_gerado'] = ContractServiceHelper::substituirVariaveis(
                        $template->conteudo,
                        $contract,
                        $client
                    );
                }
            }
            
            $contract->update([
                'template_id' => !empty($data['template_id']) ? (int)$data['template_id'] : null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'titulo' => $data['titulo'],
                'conteudo_gerado' => $data['conteudo_gerado'] ?? $contract->conteudo_gerado,
                'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
                'data_termino' => !empty($data['data_termino']) ? $data['data_termino'] : null,
                'valor_total' => !empty($data['valor_total']) ? (float)$data['valor_total'] : null,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            SistemaLog::registrar(
                'contracts',
                'UPDATE',
                $contract->id,
                "Contrato atualizado: {$contract->titulo}",
                $oldData,
                $contract->toArray()
            );

            session()->flash('success', 'Contrato atualizado com sucesso!');
            $this->redirect('/contracts/' . $contract->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar contrato: ' . $e->getMessage());
            $this->redirect('/contracts/' . $contract->id . '/edit');
        }
    }

    /**
     * Deleta contrato
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Contrato não encontrado.');
            $this->redirect('/contracts');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts');
        }

        try {
            $oldData = $contract->toArray();
            $titulo = $contract->titulo;
            $numero = $contract->numero_contrato;
            
            $contract->delete();

            SistemaLog::registrar(
                'contracts',
                'DELETE',
                $params['id'],
                "Contrato deletado: {$titulo} ({$numero})",
                $oldData,
                null
            );

            session()->flash('success', 'Contrato deletado com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao deletar contrato: ' . $e->getMessage());
        }

        $this->redirect('/contracts');
    }

    /**
     * Adiciona serviço ao contrato
     */
    public function addService(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Contrato não encontrado'], 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'descricao' => 'required|min:3',
            'detalhes' => 'nullable',
            'valor' => 'nullable|numeric|min:0',
            'quantidade' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Busca última ordem
            $db = \Core\Database::getInstance();
            $maxResult = $db->queryOne(
                "SELECT MAX(ordem) as max_ordem FROM contract_services WHERE contract_id = ?",
                [$contract->id]
            );
            $ultimaOrdem = !empty($maxResult) && isset($maxResult['max_ordem']) ? (int)$maxResult['max_ordem'] : 0;
            
            $service = ContractService::create([
                'contract_id' => $contract->id,
                'descricao' => $validatedData['descricao'],
                'detalhes' => $validatedData['detalhes'] ?? null,
                'valor' => !empty($validatedData['valor']) ? (float)$validatedData['valor'] : null,
                'quantidade' => !empty($validatedData['quantidade']) ? (int)$validatedData['quantidade'] : 1,
                'ordem' => $ultimaOrdem + 1
            ]);

            json_response([
                'success' => true,
                'message' => 'Serviço adicionado com sucesso!',
                'service' => $service->toArray()
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao adicionar serviço: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adiciona condição ao contrato
     */
    public function addCondition(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Contrato não encontrado'], 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'titulo' => 'required|min:3',
            'descricao' => 'required|min:3'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Busca última ordem
            $db = \Core\Database::getInstance();
            $maxResult = $db->queryOne(
                "SELECT MAX(ordem) as max_ordem FROM contract_conditions WHERE contract_id = ?",
                [$contract->id]
            );
            $ultimaOrdem = !empty($maxResult) && isset($maxResult['max_ordem']) ? (int)$maxResult['max_ordem'] : 0;
            
            $condition = ContractCondition::create([
                'contract_id' => $contract->id,
                'titulo' => $validatedData['titulo'],
                'descricao' => $validatedData['descricao'],
                'ordem' => $ultimaOrdem + 1
            ]);

            json_response([
                'success' => true,
                'message' => 'Condição adicionada com sucesso!',
                'condition' => $condition->toArray()
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao adicionar condição: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configura assinaturas do contrato
     */
    public function setupSignatures(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Contrato não encontrado'], 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'contratante_nome' => 'required|min:3',
            'contratante_email' => 'required|email',
            'contratante_cpf_cnpj' => 'nullable',
            'contratante_telefone' => 'nullable',
            'contratado_nome' => 'required|min:3',
            'contratado_email' => 'required|email',
            'contratado_cpf_cnpj' => 'nullable',
            'contratado_telefone' => 'nullable'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Remove assinaturas existentes
            $db = \Core\Database::getInstance();
            $db->execute("DELETE FROM contract_signatures WHERE contract_id = ?", [$contract->id]);
            
            // Cria assinatura do contratante
            ContractSignature::create([
                'contract_id' => $contract->id,
                'tipo_assinante' => 'contratante',
                'nome_assinante' => $validatedData['contratante_nome'],
                'email' => $validatedData['contratante_email'],
                'cpf_cnpj' => $validatedData['contratante_cpf_cnpj'] ?? null,
                'telefone' => $validatedData['contratante_telefone'] ?? null,
                'assinado' => false
            ]);
            
            // Cria assinatura do contratado
            ContractSignature::create([
                'contract_id' => $contract->id,
                'tipo_assinante' => 'contratado',
                'nome_assinante' => $validatedData['contratado_nome'],
                'email' => $validatedData['contratado_email'],
                'cpf_cnpj' => $validatedData['contratado_cpf_cnpj'] ?? null,
                'telefone' => $validatedData['contratado_telefone'] ?? null,
                'assinado' => false
            ]);

            json_response([
                'success' => true,
                'message' => 'Assinaturas configuradas com sucesso!'
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao configurar assinaturas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envia contrato para assinatura de um assinante específico
     */
    public function sendForSignature(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Contrato não encontrado'], 404);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input)) {
                $input = $this->request->all();
            }
            
            $tipoAssinante = $input['tipo_assinante'] ?? null;
            
            if (!$tipoAssinante || !in_array($tipoAssinante, ['contratante', 'contratado'])) {
                json_response([
                    'success' => false,
                    'message' => 'Tipo de assinante inválido. Deve ser "contratante" ou "contratado".'
                ], 400);
                return;
            }
            
            $signatures = $contract->signatures();
            
            if (empty($signatures)) {
                json_response([
                    'success' => false,
                    'message' => 'Configure as assinaturas antes de enviar o contrato.'
                ], 400);
                return;
            }
            
            // Busca assinatura específica
            $signature = null;
            foreach ($signatures as $sig) {
                if ($sig->tipo_assinante === $tipoAssinante) {
                    $signature = $sig;
                    break;
                }
            }
            
            if (!$signature) {
                json_response([
                    'success' => false,
                    'message' => "Assinatura do {$tipoAssinante} não encontrada."
                ], 404);
                return;
            }
            
            if ($signature->assinado) {
                json_response([
                    'success' => false,
                    'message' => 'Este assinante já assinou o contrato.'
                ], 400);
                return;
            }
            
            // Verifica qual serviço de email está disponível
            $resendService = new ResendService();
            $smtpService = new SmtpService();
            
            $emailService = null;
            if ($resendService->isConfigured()) {
                $emailService = $resendService;
            } elseif ($smtpService->isConfigured()) {
                // Testa conexão SMTP antes de tentar enviar
                $testResult = $smtpService->testConnection();
                if (!$testResult['success']) {
                    error_log("Teste de conexão SMTP falhou: " . ($testResult['error'] ?? 'Erro desconhecido'));
                    json_response([
                        'success' => false,
                        'message' => 'Não foi possível conectar ao servidor SMTP: ' . ($testResult['message'] ?? 'Erro desconhecido') . '. Verifique as configurações em Configurações > Integrações > Email.'
                    ], 400);
                    return;
                }
                $emailService = $smtpService;
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Serviço de email não configurado. Configure Resend ou SMTP em Configurações > Integrações.'
                ], 400);
                return;
            }
            
            // Gera token e link se não existir
            if (!$contract->token_assinatura) {
                $contract->gerarTokenAssinatura();
            }
            
            // Gera código de verificação
            $codigo = ContractSignature::gerarCodigoVerificacao();
            $signature->update([
                'codigo_verificacao' => $codigo,
                'codigo_enviado_em' => date('Y-m-d H:i:s')
            ]);
            
            // Gera link de assinatura específico para este assinante
            $link = url('/contracts/sign/' . $contract->token_assinatura . '?tipo=' . $signature->tipo_assinante);
            
            // Envia email
            $html = $this->gerarEmailAssinatura($contract, $signature, $codigo, $link);
            
            $result = null;
            try {
                if ($emailService instanceof ResendService) {
                    $result = $emailService->sendEmail(
                        $signature->email,
                        "Assinatura de Contrato - {$contract->numero_contrato}",
                        $html
                    );
                } else {
                    $result = $emailService->sendEmail(
                        $signature->email,
                        "Assinatura de Contrato - {$contract->numero_contrato}",
                        $html
                    );
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar email: " . $e->getMessage());
                json_response([
                    'success' => false,
                    'message' => "Erro ao enviar email: " . $e->getMessage()
                ], 500);
                return;
            }
            
            if (!$result['success']) {
                $errorMsg = $result['error'] ?? ($result['response']['error'] ?? ($result['message'] ?? 'Erro desconhecido'));
                error_log("Erro ao enviar email para {$signature->email}: {$errorMsg}");
                json_response([
                    'success' => false,
                    'message' => "Erro ao enviar email: {$errorMsg}"
                ], 500);
                return;
            }
            
            // Atualiza status do contrato
            if ($contract->status === 'rascunho') {
                $contract->update([
                    'status' => 'enviado',
                    'data_envio' => date('Y-m-d H:i:s'),
                    'link_assinatura' => url('/contracts/sign/' . $contract->token_assinatura)
                ]);
            }

            SistemaLog::registrar(
                'contracts',
                'UPDATE',
                $contract->id,
                "Email de assinatura enviado para {$tipoAssinante}: {$signature->email}",
                null,
                $contract->toArray()
            );

            json_response([
                'success' => true,
                'message' => "Email enviado com sucesso para {$signature->nome_assinante} ({$signature->email})!"
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao enviar contrato: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gera HTML do email de assinatura
     */
    private function gerarEmailAssinatura(Contract $contract, ContractSignature $signature, string $codigo, string $link): string
    {
        // Garante que o link seja uma URL absoluta
        if (strpos($link, 'http') !== 0) {
            // Se não começa com http, adiciona o domínio
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $link = $protocol . '://' . $host . $link;
        }
        
        // Log para debug
        error_log("Link de assinatura gerado: " . $link);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                .code { background: #fff; border: 2px dashed #007bff; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; border-radius: 5px; letter-spacing: 4px; }
                .button-container { text-align: center; margin: 30px 0; }
                .button { display: inline-block; background: #007bff; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px 0; font-size: 16px; font-weight: bold; }
                .button:hover { background: #0056b3; }
                .link-fallback { margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 5px; word-break: break-all; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Assinatura de Contrato</h2>
                </div>
                <div class="content">
                    <p>Olá <strong><?php echo htmlspecialchars($signature->nome_assinante, ENT_QUOTES, 'UTF-8'); ?></strong>,</p>
                    <p>Você recebeu um contrato para assinatura:</p>
                    <p><strong>Contrato:</strong> <?php echo htmlspecialchars($contract->numero_contrato, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Título:</strong> <?php echo htmlspecialchars($contract->titulo, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Para assinar o contrato, utilize o código de verificação abaixo:</p>
                    <div class="code"><?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></div>
                    <p>Ou clique no botão abaixo para acessar a página de assinatura:</p>
                    <div class="button-container">
                        <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" class="button" style="background: #007bff; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">Assinar Contrato</a>
                    </div>
                    <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
                    <div class="link-fallback"><?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?></div>
                    <p><small>Este código é válido por 24 horas.</small></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Página pública de assinatura
     */
    public function signPage(array $params): string
    {
        $token = $params['token'] ?? '';
        $contract = Contract::where('token_assinatura', $token)->first();
        
        if (!$contract) {
            return $this->view('contracts/sign-error', [
                'title' => 'Erro',
                'message' => 'Link de assinatura inválido ou expirado.'
            ]);
        }
        
        $tipo = $this->request->query('tipo', 'contratante');
        $signature = ContractSignature::where('contract_id', $contract->id)
            ->where('tipo_assinante', $tipo)
            ->first();
        
        if (!$signature) {
            return $this->view('contracts/sign-error', [
                'title' => 'Erro',
                'message' => 'Assinatura não encontrada.'
            ]);
        }
        
        // Gera o conteúdo do contrato se não estiver gerado
        if (empty($contract->conteudo_gerado)) {
            try {
                $conteudoGerado = ContractServiceHelper::substituirVariaveis(
                    $contract->conteudo ?? '',
                    $contract
                );
                $contract->update(['conteudo_gerado' => $conteudoGerado]);
                $contract->conteudo_gerado = $conteudoGerado; // Atualiza o objeto em memória
            } catch (\Exception $e) {
                error_log("Erro ao gerar conteúdo do contrato: " . $e->getMessage());
                // Continua mesmo com erro, mas sem conteúdo gerado
            }
        }
        
        return $this->view('contracts/sign', [
            'title' => 'Assinar Contrato',
            'contract' => $contract,
            'signature' => $signature,
            'token' => $token
        ]);
    }

    /**
     * Processa assinatura
     */
    public function processSignature(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = $params['token'] ?? '';
        $contract = Contract::where('token_assinatura', $token)->first();
        
        if (!$contract) {
            json_response(['success' => false, 'message' => 'Link de assinatura inválido'], 404);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'codigo_verificacao' => 'required|size:6',
            'tipo_assinante' => 'required|in:contratante,contratado'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            $signature = ContractSignature::where('contract_id', $contract->id)
                ->where('tipo_assinante', $validatedData['tipo_assinante'])
                ->first();
            
            if (!$signature) {
                json_response(['success' => false, 'message' => 'Assinatura não encontrada'], 404);
                return;
            }
            
            if ($signature->assinado) {
                json_response(['success' => false, 'message' => 'Contrato já foi assinado por este assinante'], 400);
                return;
            }
            
            // Valida código
            if ($signature->codigo_verificacao !== $validatedData['codigo_verificacao']) {
                json_response(['success' => false, 'message' => 'Código de verificação inválido'], 400);
                return;
            }
            
            // Verifica se código não expirou (24 horas)
            if ($signature->codigo_enviado_em) {
                $tempoEnvio = strtotime($signature->codigo_enviado_em);
                $tempoAtual = time();
                if (($tempoAtual - $tempoEnvio) > 86400) { // 24 horas
                    json_response(['success' => false, 'message' => 'Código de verificação expirado'], 400);
                    return;
                }
            }
            
            // Obtém informações do dispositivo e localização
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
            
            // Tenta obter geolocalização (pode usar API externa)
            $geolocalizacao = self::obterGeolocalizacao($ip);
            
            // Gera hash de assinatura
            $signature->update([
                'codigo_validado_em' => date('Y-m-d H:i:s'),
                'assinado' => true,
                'assinado_em' => date('Y-m-d H:i:s'),
                
                // Dispara evento de automação
                // (será verificado após salvar)
                'ip_assinatura' => $ip,
                'geolocalizacao' => json_encode($geolocalizacao),
                'dispositivo' => $userAgent,
                'hash_assinatura' => $signature->gerarHashAssinatura()
            ]);
            
            // Verifica se todas as assinaturas foram concluídas
            if ($contract->todasAssinaturasConcluidas()) {
                $contract->update([
                    'status' => 'assinado',
                    'data_assinatura_completa' => date('Y-m-d H:i:s')
                ]);
            } else {
                $contract->update([
                    'status' => 'aguardando_assinaturas'
                ]);
            }

            SistemaLog::registrar(
                'contract_signatures',
                'UPDATE',
                $signature->id,
                "Contrato assinado: {$contract->numero_contrato} por {$signature->nome_assinante}",
                null,
                $signature->toArray()
            );
            
            // Dispara evento de automação se todas as assinaturas foram concluídas
            if ($contract->todasAssinaturasConcluidas()) {
                AutomationEventDispatcher::onContract('signed', $contract->id, $contract->user_id);
            }

            json_response([
                'success' => true,
                'message' => 'Contrato assinado com sucesso!',
                'todas_assinadas' => $contract->todasAssinaturasConcluidas()
            ]);
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao processar assinatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém geolocalização do IP (simplificado - em produção use API profissional)
     */
    private static function obterGeolocalizacao(string $ip): array
    {
        // Em produção, use uma API de geolocalização como ipapi.co, ip-api.com, etc.
        // Por enquanto, retorna dados básicos
        return [
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ];
    }

    /**
     * Gera PDF do contrato
     */
    public function generatePdf(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $contract = Contract::find($params['id']);
        
        if (!$contract || $contract->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Contrato não encontrado.');
            $this->redirect('/contracts');
        }

        try {
            $html = ContractServiceHelper::gerarHtmlContrato($contract);
            $filename = 'contrato_' . $contract->numero_contrato . '.pdf';
            $filePath = PdfService::generateFromHtml($html, $filename);
            
            PdfService::download($filePath, $filename);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            $this->redirect('/contracts/' . $contract->id);
        }
    }
}

