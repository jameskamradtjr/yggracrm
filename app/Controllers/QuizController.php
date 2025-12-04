<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Quiz;
use App\Models\QuizStep;
use App\Models\QuizOption;
use App\Models\Tag;

class QuizController extends Controller
{
    /**
     * Lista todos os quizzes do usuário
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $quizzes = Quiz::where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();

        return $this->view('quizzes/index', [
            'title' => 'Quizzes',
            'quizzes' => $quizzes
        ]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();

        return $this->view('quizzes/create', [
            'title' => 'Criar Quiz'
        ]);
    }

    /**
     * Salva novo quiz
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/quizzes');
        }

        $userId = auth()->getDataUserId();
        
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'background_color' => 'nullable|string|max:7',
            'button_color' => 'nullable|string|max:7',
            'button_text_color' => 'nullable|string|max:7',
            'logo_url' => 'nullable|string',
            'welcome_message' => 'nullable|string',
            'completion_message' => 'nullable|string',
            'default_tag_name' => 'nullable|string',
            'active' => 'nullable'
        ]);

        $slug = Quiz::generateSlug($data['name'], $userId);

        // Processa campo active - sempre ativo por padrão na criação
        // O hidden field envia "1" por padrão, e o checkbox também envia "1" se marcado
        // Como ambos têm value="1" na criação, sempre será 1
        $activeInput = $this->request->input('active');
        $active = ($activeInput === '1' || $activeInput === 1 || $activeInput === true) ? 1 : 1; // Sempre 1 na criação
        
        // Processa tag: busca ou cria pelo nome
        $defaultTagId = null;
        if (!empty($data['default_tag_name'])) {
            $tagName = trim($data['default_tag_name']);
            $tag = Tag::where('name', $tagName)
                ->where('user_id', $userId)
                ->first();
            
            if (!$tag) {
                // Cria nova tag
                $tag = Tag::create([
                    'name' => $tagName,
                    'color' => '#0dcaf0', // Cor padrão
                    'user_id' => $userId
                ]);
            }
            $defaultTagId = $tag->id;
        }
        
        $quiz = Quiz::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $slug,
            'primary_color' => $data['primary_color'] ?? '#007bff',
            'secondary_color' => $data['secondary_color'] ?? '#6c757d',
            'text_color' => $data['text_color'] ?? '#212529',
            'background_color' => $data['background_color'] ?? '#ffffff',
            'button_color' => $data['button_color'] ?? '#007bff',
            'button_text_color' => $data['button_text_color'] ?? '#ffffff',
            'button_hover_color' => $data['button_hover_color'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'welcome_message' => $data['welcome_message'] ?? null,
            'completion_message' => $data['completion_message'] ?? null,
            'default_tag_id' => $defaultTagId,
            'active' => $active,
            'user_id' => $userId
        ]);

        session()->flash('success', 'Quiz criado com sucesso!');
        $this->redirect('/quizzes/' . $quiz->id . '/edit');
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            session()->flash('error', 'Quiz não encontrado.');
            $this->redirect('/quizzes');
        }

        $steps = $quiz->steps();

        return $this->view('quizzes/edit', [
            'title' => 'Editar Quiz',
            'quiz' => $quiz,
            'steps' => $steps
        ]);
    }

    /**
     * Atualiza quiz
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/quizzes');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            session()->flash('error', 'Quiz não encontrado.');
            $this->redirect('/quizzes');
        }

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'background_color' => 'nullable|string|max:7',
            'button_color' => 'nullable|string|max:7',
            'button_text_color' => 'nullable|string|max:7',
            'button_hover_color' => 'nullable|string|max:7',
            'logo_url' => 'nullable|string',
            'welcome_message' => 'nullable|string',
            'completion_message' => 'nullable|string',
            'default_tag_name' => 'nullable|string',
            'active' => 'nullable'
        ]);

        // Gera novo slug se o nome mudou
        if ($data['name'] !== $quiz->name) {
            $slug = Quiz::generateSlug($data['name'], $userId);
            $data['slug'] = $slug;
        }

        // Processa campo active - verifica diretamente no request
        // O hidden field envia "0" por padrão, e o checkbox envia "1" se marcado
        // Se o checkbox estiver marcado, o valor será "1" (último valor enviado)
        // Se não estiver marcado, apenas o hidden será enviado com "0"
        $activeInput = $this->request->input('active');
        
        // Se for array (múltiplos valores), verifica se contém "1"
        if (is_array($activeInput)) {
            $active = in_array('1', $activeInput) || in_array(1, $activeInput) ? 1 : 0;
        } else {
            $active = ($activeInput === '1' || $activeInput === 1 || $activeInput === true) ? 1 : 0;
        }
        
        // Processa tag: busca ou cria pelo nome
        $defaultTagId = null;
        if (!empty($data['default_tag_name'])) {
            $tagName = trim($data['default_tag_name']);
            $tag = Tag::where('name', $tagName)
                ->where('user_id', $userId)
                ->first();
            
            if (!$tag) {
                // Cria nova tag
                $tag = Tag::create([
                    'name' => $tagName,
                    'color' => '#0dcaf0', // Cor padrão
                    'user_id' => $userId
                ]);
            }
            $defaultTagId = $tag->id;
        }
        
        // Log para debug
        error_log("QuizController::update() - button_hover_color recebido: " . ($data['button_hover_color'] ?? 'NULL'));
        
        $quiz->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $data['slug'] ?? $quiz->slug,
            'primary_color' => $data['primary_color'] ?? '#007bff',
            'secondary_color' => $data['secondary_color'] ?? '#6c757d',
            'text_color' => $data['text_color'] ?? '#212529',
            'background_color' => $data['background_color'] ?? '#ffffff',
            'button_color' => $data['button_color'] ?? '#007bff',
            'button_text_color' => $data['button_text_color'] ?? '#ffffff',
            'button_hover_color' => !empty($data['button_hover_color']) ? $data['button_hover_color'] : null,
            'logo_url' => $data['logo_url'] ?? null,
            'welcome_message' => $data['welcome_message'] ?? null,
            'completion_message' => $data['completion_message'] ?? null,
            'default_tag_id' => $defaultTagId,
            'active' => $active
        ]);
        
        // Log para debug após update
        $quizUpdated = Quiz::find($quiz->id);
        error_log("QuizController::update() - button_hover_color após update: " . ($quizUpdated->button_hover_color ?? 'NULL'));

        session()->flash('success', 'Quiz atualizado com sucesso!');
        $this->redirect('/quizzes/' . $quiz->id . '/edit');
    }

    /**
     * Deleta quiz
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/quizzes');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            session()->flash('error', 'Quiz não encontrado.');
            $this->redirect('/quizzes');
        }

        // Deleta steps e options
        $steps = $quiz->steps();
        foreach ($steps as $step) {
            $options = $step->options();
            foreach ($options as $option) {
                $option->delete();
            }
            $step->delete();
        }

        $quiz->delete();

        session()->flash('success', 'Quiz excluído com sucesso!');
        $this->redirect('/quizzes');
    }

    /**
     * Salva ou atualiza um step
     */
    public function saveStep(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            $this->json(['success' => false, 'message' => 'Quiz não encontrado'], 404);
            return;
        }

        $data = $this->request->all();
        $stepId = $data['step_id'] ?? null;

        if ($stepId) {
            // Atualiza step existente
            $step = QuizStep::find($stepId);
            if (!$step || $step->quiz_id != $quiz->id) {
                $this->json(['success' => false, 'message' => 'Step não encontrado'], 404);
                return;
            }

            $step->update([
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'text',
                'required' => isset($data['required']) ? 1 : 0,
                'order' => $data['order'] ?? 0,
                'points' => $data['points'] ?? 0,
                'field_name' => $data['field_name'] ?? null
            ]);
        } else {
            // Cria novo step
            $step = QuizStep::create([
                'quiz_id' => $quiz->id,
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'text',
                'required' => isset($data['required']) ? 1 : 0,
                'order' => $data['order'] ?? 0,
                'points' => $data['points'] ?? 0,
                'field_name' => $data['field_name'] ?? null
            ]);
        }

        // Salva opções se for select, radio ou checkbox
        if (in_array($step->type, ['select', 'radio', 'checkbox'])) {
            $existingOptions = $step->options();
            $existingOptionIds = array_column($existingOptions, 'id');
            $submittedOptionIds = [];

            if (isset($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $index => $optionData) {
                    $optionId = $optionData['id'] ?? null;
                    $submittedOptionIds[] = $optionId;

                    if ($optionId && in_array($optionId, $existingOptionIds)) {
                        // Atualiza opção existente
                        $option = QuizOption::find($optionId);
                        if ($option) {
                            $option->update([
                                'label' => $optionData['label'] ?? '',
                                'value' => $optionData['value'] ?? null,
                                'points' => $optionData['points'] ?? 0,
                                'order' => $index
                            ]);
                        }
                    } else {
                        // Cria nova opção
                        QuizOption::create([
                            'quiz_step_id' => $step->id,
                            'label' => $optionData['label'] ?? '',
                            'value' => $optionData['value'] ?? null,
                            'points' => $optionData['points'] ?? 0,
                            'order' => $index
                        ]);
                    }
                }
            }

            // Remove opções que não foram enviadas
            foreach ($existingOptions as $existingOption) {
                if (!in_array($existingOption->id, $submittedOptionIds)) {
                    $existingOption->delete();
                }
            }
        }

        $this->json(['success' => true, 'step' => $step->toArray()]);
    }

    /**
     * Deleta um step
     */
    public function deleteStep(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            $this->json(['success' => false, 'message' => 'Quiz não encontrado'], 404);
            return;
        }

        $stepId = $this->request->input('step_id');
        $step = QuizStep::find($stepId);

        if (!$step || $step->quiz_id != $quiz->id) {
            $this->json(['success' => false, 'message' => 'Step não encontrado'], 404);
            return;
        }

        // Deleta opções
        $options = $step->options();
        foreach ($options as $option) {
            $option->delete();
        }

        $step->delete();

        $this->json(['success' => true]);
    }

    /**
     * Busca dados de um step
     */
    public function getStep(array $params): void
    {
        if (!auth()->check()) {
            $this->json(['success' => false, 'message' => 'Não autorizado'], 401);
            return;
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            $this->json(['success' => false, 'message' => 'Quiz não encontrado'], 404);
            return;
        }

        $stepId = $this->request->query('step_id');
        $step = QuizStep::find($stepId);

        if (!$step || $step->quiz_id != $quiz->id) {
            $this->json(['success' => false, 'message' => 'Step não encontrado'], 404);
            return;
        }

        $options = $step->options();

        $this->json([
            'success' => true,
            'step' => $step->toArray(),
            'options' => array_map(function($opt) {
                return $opt->toArray();
            }, $options)
        ]);
    }

    /**
     * Reordena steps
     */
    public function reorderSteps(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $quiz = Quiz::find($params['id']);

        if (!$quiz || $quiz->user_id != $userId) {
            $this->json(['success' => false, 'message' => 'Quiz não encontrado'], 404);
            return;
        }

        $order = $this->request->input('order', []);
        
        if (is_array($order)) {
            foreach ($order as $index => $stepId) {
                $step = QuizStep::find($stepId);
                if ($step && $step->quiz_id == $quiz->id) {
                    $step->update(['order' => $index]);
                }
            }
        }

        $this->json(['success' => true]);
    }

    /**
     * Exibe quiz público
     */
    public function publicQuiz(array $params): string
    {
        $slug = $params['slug'] ?? null;
        
        error_log("QuizController::publicQuiz - Slug recebido: " . $slug);
        
        // Tenta buscar por slug primeiro
        $quiz = null;
        if ($slug) {
            $quiz = Quiz::where('slug', $slug)->where('active', true)->first();
            error_log("Busca por slug '{$slug}': " . ($quiz ? "Encontrado (ID: {$quiz->id})" : "Não encontrado"));
        }
        
        // Se não encontrou por slug, tenta buscar por ID (caso o usuário tenha passado ID)
        if (!$quiz && is_numeric($slug)) {
            $quiz = Quiz::where('id', (int)$slug)->where('active', true)->first();
            error_log("Busca por ID '{$slug}': " . ($quiz ? "Encontrado" : "Não encontrado"));
        }

        if (!$quiz) {
            error_log("Quiz não encontrado para slug/ID: {$slug}");
            http_response_code(404);
            
            // Tenta buscar todos os quizzes ativos para debug
            $allQuizzes = Quiz::where('active', true)->get();
            error_log("Total de quizzes ativos: " . count($allQuizzes));
            foreach ($allQuizzes as $q) {
                error_log("Quiz disponível - ID: {$q->id}, Slug: {$q->slug}, Nome: {$q->name}");
            }
            
            return $this->view('errors/404', [
                'title' => 'Quiz não encontrado',
                'message' => 'O quiz solicitado não foi encontrado ou está inativo.'
            ]);
        }

        try {
            $steps = $quiz->steps();
            error_log("Quiz encontrado: {$quiz->name} (ID: {$quiz->id}, Slug: {$quiz->slug}), Steps: " . count($steps));

            $html = $this->view('quizzes/public', [
                'title' => $quiz->name,
                'quiz' => $quiz,
                'steps' => $steps
            ]);
            
            error_log("View renderizada com sucesso, tamanho: " . strlen($html));
            return $html;
        } catch (\Throwable $e) {
            error_log("Erro ao renderizar view do quiz: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return '<h1>Erro ao carregar quiz</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }

    /**
     * Processa submissão do quiz
     */
    public function submitQuiz(array $params): void
    {
        $slug = $params['slug'] ?? null;
        
        // Tenta buscar por slug primeiro
        $quiz = null;
        if ($slug) {
            $quiz = Quiz::where('slug', $slug)->where('active', true)->first();
        }
        
        // Se não encontrou por slug, tenta buscar por ID (caso o usuário tenha passado ID)
        if (!$quiz && is_numeric($slug)) {
            $quiz = Quiz::where('id', (int)$slug)->where('active', true)->first();
        }

        if (!$quiz) {
            $this->json(['success' => false, 'message' => 'Quiz não encontrado'], 404);
            return;
        }

        $data = $this->request->all();
        $steps = $quiz->steps();
        
        $leadData = [];
        $totalPoints = 0;

        // Primeiro, coleta dados pessoais diretamente do request (sempre presentes)
        $leadData['nome'] = $data['nome'] ?? $data['name'] ?? '';
        $leadData['email'] = $data['email'] ?? '';
        $leadData['telefone'] = $data['telefone'] ?? $data['phone'] ?? '';
        $leadData['instagram'] = $data['instagram'] ?? '';

        // Processa respostas e calcula pontuação
        $quizResponses = []; // Armazena respostas para salvar depois
        
        error_log("QuizController::submitQuiz() - Total de steps do quiz: " . count($steps));
        error_log("QuizController::submitQuiz() - Dados recebidos: " . json_encode(array_keys($data)));
        
        foreach ($steps as $step) {
            $fieldName = $step->field_name ?: 'step_' . $step->id;
            $answer = $data[$fieldName] ?? null;
            
            error_log("QuizController::submitQuiz() - Processando step ID: {$step->id}, Field: {$fieldName}, Answer: " . ($answer !== null ? (is_array($answer) ? json_encode($answer) : $answer) : 'NULL'));

            if ($answer !== null && $answer !== '') {
                // Adiciona pontos da etapa
                $stepPoints = $step->points;
                $totalPoints += $stepPoints;

                // Se tiver opções, verifica pontos da opção selecionada
                $optionPoints = 0;
                if (in_array($step->type, ['select', 'radio', 'checkbox'])) {
                    $options = $step->options();
                    foreach ($options as $option) {
                        $optionValue = $option->value ?: $option->label;
                        if ($answer == $optionValue || (is_array($answer) && in_array($optionValue, $answer))) {
                            $optionPoints += $option->points;
                            $totalPoints += $option->points;
                        }
                    }
                }

                // Mapeia resposta para campos do lead (não sobrescreve dados pessoais)
                if ($step->field_name && !in_array($step->field_name, ['nome', 'name', 'email', 'telefone', 'phone', 'instagram'])) {
                    $leadData[$step->field_name] = is_array($answer) ? implode(', ', $answer) : trim($answer);
                }
                
                // Armazena resposta para salvar depois
                $quizResponses[] = [
                    'quiz_id' => $quiz->id,
                    'quiz_step_id' => $step->id,
                    'field_name' => $step->field_name,
                    'response' => is_array($answer) ? implode(', ', $answer) : trim($answer),
                    'points' => $stepPoints + $optionPoints
                ];
            }
        }

        // Normaliza nomes de campos (aceita variações)
        $nome = $leadData['nome'] ?? $leadData['name'] ?? '';
        $email = $leadData['email'] ?? '';
        $telefone = $leadData['telefone'] ?? $leadData['phone'] ?? '';
        $instagram = $leadData['instagram'] ?? '';
        
        // Log para debug
        error_log("QuizController::submitQuiz() - Dados pessoais coletados: nome={$nome}, email={$email}, telefone={$telefone}, instagram={$instagram}");

        // Cria lead com todos os campos disponíveis
        $lead = \App\Models\Lead::create([
            'nome' => !empty($nome) ? $nome : 'Lead do Quiz',
            'email' => $email,
            'telefone' => $telefone,
            'instagram' => !empty($instagram) ? $instagram : null,
            'ramo' => $leadData['ramo'] ?? null,
            'objetivo' => $leadData['objetivo'] ?? null,
            'valor_oportunidade' => isset($leadData['valor_oportunidade']) && !empty($leadData['valor_oportunidade']) ? (float)$leadData['valor_oportunidade'] : null,
            'score_potencial' => $totalPoints,
            'etapa_funil' => 'interessados',
            'origem' => 'quiz_' . $quiz->slug,
            'user_id' => $quiz->user_id
        ]);

        // Salva respostas do quiz
        error_log("QuizController::submitQuiz() - Total de respostas para salvar: " . count($quizResponses));
        
        if (empty($quizResponses)) {
            error_log("QuizController::submitQuiz() - AVISO: Nenhuma resposta coletada do quiz! Steps: " . count($steps));
            foreach ($steps as $step) {
                $fieldName = $step->field_name ?: 'step_' . $step->id;
                $answer = $data[$fieldName] ?? null;
                error_log("QuizController::submitQuiz() - Step ID: {$step->id}, Field: {$fieldName}, Answer: " . ($answer ?? 'NULL'));
            }
        }
        
        foreach ($quizResponses as $responseData) {
            try {
                $saved = \App\Models\LeadQuizResponse::create([
                    'lead_id' => $lead->id,
                    'quiz_id' => $responseData['quiz_id'],
                    'quiz_step_id' => $responseData['quiz_step_id'],
                    'field_name' => $responseData['field_name'],
                    'response' => $responseData['response'],
                    'points' => $responseData['points']
                ]);
                error_log("QuizController::submitQuiz() - Resposta salva: LeadQuizResponse ID {$saved->id} para Step {$responseData['quiz_step_id']}");
            } catch (\Exception $e) {
                error_log("QuizController::submitQuiz() - ERRO ao salvar resposta: " . $e->getMessage());
                error_log("QuizController::submitQuiz() - Stack trace: " . $e->getTraceAsString());
            }
        }
        
        error_log("QuizController::submitQuiz() - Lead criado com ID: {$lead->id}, Origem: quiz_{$quiz->slug}");

        // Adiciona tag padrão se configurada
        if ($quiz->default_tag_id) {
            $lead->addTag($quiz->default_tag_id);
        }

        // Retorna sucesso
        $this->json([
            'success' => true,
            'message' => $quiz->completion_message ?: 'Obrigado por responder o quiz!',
            'lead_id' => $lead->id
        ]);
    }
}

