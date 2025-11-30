<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Project;
use App\Models\ProjectCard;
use App\Models\ProjectCardChecklist;
use App\Models\ProjectCardTag;
use App\Models\ProjectCardTimeTracking;
use App\Models\SistemaLog;

/**
 * Controller de Kanban de Projetos
 */
class ProjectKanbanController extends Controller
{
    /**
     * Exibe o Kanban do projeto
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $project = Project::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$project) {
            session()->flash('error', 'Projeto não encontrado.');
            $this->redirect('/projects');
        }
        
        // Busca cards organizados por coluna
        $cards = ProjectCard::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->orderBy('ordem', 'ASC')
            ->get();
        
        $cardsPorColuna = [
            'backlog' => [],
            'a_fazer' => [],
            'fazendo' => [],
            'testes' => [],
            'publicado' => []
        ];
        
        foreach ($cards as $card) {
            $coluna = $card->coluna ?? 'backlog';
            if (isset($cardsPorColuna[$coluna])) {
                $cardsPorColuna[$coluna][] = $card;
            }
        }
        
        // Busca usuários para atribuir responsáveis
        $users = \App\Models\User::where('status', 'active')->get();
        
        return $this->view('projects/kanban', [
            'title' => 'Kanban - ' . $project->titulo,
            'project' => $project,
            'cards' => $cardsPorColuna,
            'users' => $users
        ]);
    }
    
    /**
     * Cria novo card
     */
    public function storeCard(): void
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

        // Tenta ler JSON primeiro, depois FormData
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input) || !is_array($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'project_id' => 'required|numeric',
            'titulo' => 'required',
            'coluna' => 'required|in:backlog,a_fazer,fazendo,testes,publicado',
            'prioridade' => 'nullable|in:baixa,media,alta,urgente',
            'descricao' => 'nullable',
            'responsible_user_id' => 'nullable|numeric',
            'data_prazo' => 'nullable|date'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            
            // Busca última ordem na coluna usando Database diretamente
            $db = \Core\Database::getInstance();
            $maxResult = $db->queryOne(
                "SELECT COALESCE(MAX(ordem), 0) as max_ordem FROM project_cards WHERE project_id = ? AND coluna = ? AND user_id = ?",
                [(int)$input['project_id'], $input['coluna'], $userId]
            );
            $ultimaOrdem = !empty($maxResult) && isset($maxResult['max_ordem']) ? (int)$maxResult['max_ordem'] : 0;
            
            // Prepara dados para criação
            $cardData = [
                'project_id' => (int)$input['project_id'],
                'user_id' => $userId,
                'titulo' => trim($input['titulo']),
                'coluna' => $input['coluna'],
                'prioridade' => $input['prioridade'] ?? 'media',
                'ordem' => $ultimaOrdem + 1
            ];
            
            // Campos opcionais
            if (!empty($input['descricao'])) {
                $cardData['descricao'] = trim($input['descricao']);
            }
            
            if (!empty($input['responsible_user_id'])) {
                $cardData['responsible_user_id'] = (int)$input['responsible_user_id'];
            }
            
            if (!empty($input['data_prazo'])) {
                $cardData['data_prazo'] = $input['data_prazo'];
            }
            
            // Log dos dados antes de criar
            error_log("Dados do card antes de criar: " . print_r($cardData, true));
            
            // Cria o card
            $card = new ProjectCard($cardData);
            $saved = $card->save();
            
            if (!$saved) {
                throw new \Exception("Falha ao salvar o card no banco de dados");
            }
            
            // Registra log
            SistemaLog::registrar(
                'project_cards',
                'CREATE',
                $card->id,
                "Card criado: {$card->titulo}",
                null,
                $card->toArray()
            );
            
            json_response([
                'success' => true,
                'message' => 'Card criado com sucesso!',
                'card' => $card->toArray()
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao criar card: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            error_log("Dados recebidos: " . print_r($input, true));
            json_response([
                'success' => false,
                'message' => 'Erro ao criar card: ' . $e->getMessage(),
                'debug' => [
                    'input' => $input,
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }
    
    /**
     * Atualiza coluna do card (drag-and-drop)
     */
    public function updateCardColumn(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'card_id' => 'required|numeric',
            'coluna' => 'required|in:backlog,a_fazer,fazendo,testes,publicado',
            'ordem' => 'nullable|numeric'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $input['card_id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            $oldColuna = $card->coluna;
            $card->update([
                'coluna' => $input['coluna'],
                'ordem' => $input['ordem'] ?? $card->ordem
            ]);
            
            // Registra log
            SistemaLog::registrar(
                'project_cards',
                'UPDATE_COLUMN',
                $card->id,
                "Card '{$card->titulo}' movido de '{$oldColuna}' para '{$input['coluna']}'",
                ['coluna' => $oldColuna],
                ['coluna' => $input['coluna']]
            );
            
            json_response([
                'success' => true,
                'message' => 'Card atualizado com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar card: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualiza card
     */
    public function updateCard(array $params): void
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

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $userId = auth()->getDataUserId();
        $card = ProjectCard::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$card) {
            json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
            return;
        }

        $validator = new \Core\Validator($input, [
            'titulo' => 'required',
            'descricao' => 'nullable',
            'prioridade' => 'nullable|in:baixa,media,alta,urgente',
            'responsible_user_id' => 'nullable|numeric',
            'data_prazo' => 'nullable|date'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $dadosAnteriores = $card->toArray();
            
            $card->update([
                'titulo' => $input['titulo'],
                'descricao' => $input['descricao'] ?? null,
                'prioridade' => $input['prioridade'] ?? $card->prioridade,
                'responsible_user_id' => !empty($input['responsible_user_id']) ? (int)$input['responsible_user_id'] : null,
                'data_prazo' => !empty($input['data_prazo']) ? $input['data_prazo'] : null
            ]);
            
            // Registra log
            SistemaLog::registrar(
                'project_cards',
                'UPDATE',
                $card->id,
                "Card atualizado: {$card->titulo}",
                $dadosAnteriores,
                $card->toArray()
            );
            
            json_response([
                'success' => true,
                'message' => 'Card atualizado com sucesso!',
                'card' => $card->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar card: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exclui card
     */
    public function deleteCard(array $params): void
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
        $card = ProjectCard::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$card) {
            json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
            return;
        }

        try {
            $titulo = $card->titulo;
            $dadosAnteriores = $card->toArray();
            
            $card->delete();
            
            // Registra log
            SistemaLog::registrar(
                'project_cards',
                'DELETE',
                $params['id'],
                "Card excluído: {$titulo}",
                $dadosAnteriores,
                null
            );
            
            json_response([
                'success' => true,
                'message' => 'Card excluído com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao excluir card: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Adiciona item ao checklist
     */
    public function addChecklistItem(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'card_id' => 'required|numeric',
            'item' => 'required'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $input['card_id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            // Busca última ordem usando Database diretamente
            $db = \Core\Database::getInstance();
            $maxResult = $db->queryOne(
                "SELECT COALESCE(MAX(ordem), 0) as max_ordem FROM project_card_checklists WHERE card_id = ?",
                [$card->id]
            );
            $ultimaOrdem = !empty($maxResult) && isset($maxResult['max_ordem']) ? (int)$maxResult['max_ordem'] : 0;
            
            $checklistItem = ProjectCardChecklist::create([
                'card_id' => $card->id,
                'item' => $input['item'],
                'concluido' => false,
                'ordem' => $ultimaOrdem + 1
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Item adicionado ao checklist!',
                'item' => $checklistItem->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao adicionar item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualiza item do checklist
     */
    public function updateChecklistItem(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $item = ProjectCardChecklist::find($params['id']);
        
        if (!$item) {
            json_response(['success' => false, 'message' => 'Item não encontrado'], 404);
            return;
        }
        
        // Verifica se o card pertence ao usuário
        $card = ProjectCard::find($item->card_id);
        if (!$card || $card->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Acesso negado'], 403);
            return;
        }

        try {
            $updateData = [];
            if (isset($input['item'])) {
                $updateData['item'] = $input['item'];
            }
            if (isset($input['concluido'])) {
                $updateData['concluido'] = (bool)$input['concluido'];
            }
            
            if (!empty($updateData)) {
                $item->update($updateData);
            }
            
            json_response([
                'success' => true,
                'message' => 'Item atualizado!',
                'item' => $item->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove item do checklist
     */
    public function deleteChecklistItem(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $item = ProjectCardChecklist::find($params['id']);
        
        if (!$item) {
            json_response(['success' => false, 'message' => 'Item não encontrado'], 404);
            return;
        }
        
        // Verifica se o card pertence ao usuário
        $card = ProjectCard::find($item->card_id);
        if (!$card || $card->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Acesso negado'], 403);
            return;
        }

        try {
            $item->delete();
            
            json_response([
                'success' => true,
                'message' => 'Item removido!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Adiciona tag ao card
     */
    public function addTag(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'card_id' => 'required|numeric',
            'nome' => 'required',
            'cor' => 'nullable'
        ]);

        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $input['card_id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            $tag = ProjectCardTag::create([
                'card_id' => $card->id,
                'nome' => $input['nome'],
                'cor' => $input['cor'] ?? '#0dcaf0'
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Tag adicionada!',
                'tag' => $tag->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao adicionar tag: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove tag do card
     */
    public function deleteTag(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        $tag = ProjectCardTag::find($params['id']);
        
        if (!$tag) {
            json_response(['success' => false, 'message' => 'Tag não encontrada'], 404);
            return;
        }
        
        // Verifica se o card pertence ao usuário
        $card = ProjectCard::find($tag->card_id);
        if (!$card || $card->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Acesso negado'], 403);
            return;
        }

        try {
            $tag->delete();
            
            json_response([
                'success' => true,
                'message' => 'Tag removida!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao remover tag: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Retorna modal de edição do card
     */
    public function editCardModal(array $params): string
    {
        if (!auth()->check()) {
            return '';
        }

        $userId = auth()->getDataUserId();
        $card = ProjectCard::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$card) {
            return '<div class="alert alert-danger">Card não encontrado</div>';
        }
        
        $users = \App\Models\User::where('status', 'active')->get();
        $checklists = $card->checklists();
        $tags = $card->tags();
        
        ob_start();
        include base_path('views/projects/_card_modal.php');
        $html = ob_get_clean();
        
        // Envolve em div com data attribute para passar o nome
        return '<div data-card-nome="' . e($card->titulo) . '">' . $html . '</div>';
    }
    
    /**
     * Inicia timer para um card
     */
    public function startTimer(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }
        
        $validator = new \Core\Validator($input, [
            'card_id' => 'required|numeric'
        ]);
        
        if (!$validator->passes()) {
            json_response(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $input['card_id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            // Verifica se já existe um timer ativo para este card e usuário
            $timerAtivo = ProjectCardTimeTracking::where('card_id', $card->id)
                ->where('user_id', $userId)
                ->whereNull('fim')
                ->first();
            
            if ($timerAtivo) {
                json_response(['success' => false, 'message' => 'Já existe um timer ativo para este card'], 400);
                return;
            }
            
            // Cria novo timer
            $timer = ProjectCardTimeTracking::create([
                'card_id' => $card->id,
                'user_id' => $userId,
                'inicio' => date('Y-m-d H:i:s'),
                'fim' => null,
                'tempo_segundos' => 0
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Timer iniciado!',
                'timer' => $timer->toArray()
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao iniciar timer: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao iniciar timer: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Para timer de um card
     */
    public function stopTimer(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }
        
        $validator = new \Core\Validator($input, [
            'card_id' => 'required|numeric'
        ]);
        
        if (!$validator->passes()) {
            json_response(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $input['card_id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            // Busca timer ativo
            $timer = ProjectCardTimeTracking::where('card_id', $card->id)
                ->where('user_id', $userId)
                ->whereNull('fim')
                ->first();
            
            if (!$timer) {
                json_response(['success' => false, 'message' => 'Nenhum timer ativo encontrado'], 404);
                return;
            }
            
            // Calcula tempo decorrido
            $inicio = new \DateTime($timer->inicio);
            $fim = new \DateTime();
            $diferenca = $fim->getTimestamp() - $inicio->getTimestamp();
            
            // Atualiza timer
            $timer->update([
                'fim' => $fim->format('Y-m-d H:i:s'),
                'tempo_segundos' => $diferenca
            ]);
            
            // Calcula tempo total do card
            $tempoTotal = ProjectCardTimeTracking::tempoTotalCard($card->id);
            
            json_response([
                'success' => true,
                'message' => 'Timer parado!',
                'tempo_segundos' => $diferenca,
                'tempo_formatado' => ProjectCardTimeTracking::formatarSegundos($diferenca),
                'tempo_total' => $tempoTotal,
                'tempo_total_formatado' => ProjectCardTimeTracking::formatarSegundos($tempoTotal)
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao parar timer: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao parar timer: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtém status do timer e tempo total do card
     */
    public function getTimerStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $cardId = $this->request->query('card_id');
        if (!$cardId) {
            json_response(['success' => false, 'message' => 'card_id é obrigatório'], 400);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $card = ProjectCard::where('id', $cardId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$card) {
                json_response(['success' => false, 'message' => 'Card não encontrado'], 404);
                return;
            }
            
            // Busca timer ativo
            $timerAtivo = ProjectCardTimeTracking::where('card_id', $card->id)
                ->where('user_id', $userId)
                ->whereNull('fim')
                ->first();
            
            // Calcula tempo total
            $tempoTotal = ProjectCardTimeTracking::tempoTotalCard($card->id);
            
            // Se há timer ativo, adiciona tempo decorrido
            $tempoDecorrido = 0;
            if ($timerAtivo) {
                $inicio = new \DateTime($timerAtivo->inicio);
                $agora = new \DateTime();
                $tempoDecorrido = $agora->getTimestamp() - $inicio->getTimestamp();
            }
            
            json_response([
                'success' => true,
                'timer_ativo' => $timerAtivo !== null,
                'timer_inicio' => $timerAtivo ? $timerAtivo->inicio : null,
                'tempo_total' => $tempoTotal,
                'tempo_total_formatado' => ProjectCardTimeTracking::formatarSegundos($tempoTotal),
                'tempo_decorrido' => $tempoDecorrido,
                'tempo_decorrido_formatado' => ProjectCardTimeTracking::formatarSegundos($tempoDecorrido)
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao obter status do timer: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao obter status: ' . $e->getMessage()], 500);
        }
    }
}

