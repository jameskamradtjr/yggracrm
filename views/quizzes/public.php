<?php
// Verifica se quiz existe
if (!isset($quiz) || !$quiz) {
    http_response_code(404);
    die('<h1>Quiz não encontrado</h1>');
}

// Garante que steps seja um array
$steps = $steps ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($quiz->name ?? 'Quiz'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo e($quiz->primary_color ?? '#007bff'); ?>;
            --secondary-color: <?php echo e($quiz->secondary_color ?? '#6c757d'); ?>;
            --text-color: <?php echo e($quiz->text_color ?? '#212529'); ?>;
            --background-color: <?php echo e($quiz->background_color ?? '#ffffff'); ?>;
            --button-color: <?php echo e($quiz->button_color ?? '#007bff'); ?>;
            --button-text-color: <?php echo e($quiz->button_text_color ?? '#ffffff'); ?>;
            --button-hover-color: <?php echo e($quiz->button_hover_color ?? '#0056b3'); ?>;
        }
        
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .quiz-container {
            max-width: 600px;
            width: 100%;
        }
        
        .quiz-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 20px;
        }
        
        .quiz-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .quiz-logo img {
            max-height: 80px;
            max-width: 200px;
        }
        
        .quiz-title {
            color: var(--text-color);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .quiz-description {
            color: var(--secondary-color);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .step-container {
            display: none;
        }
        
        .step-container.active {
            display: block;
        }
        
        .step-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .step-description {
            color: var(--secondary-color);
            margin-bottom: 25px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
            outline: none;
        }
        
        .btn-primary {
            background-color: var(--button-color);
            border-color: var(--button-color);
            color: var(--button-text-color);
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--button-hover-color);
            border-color: var(--button-hover-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }
        
        .progress-bar {
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .radio-option, .checkbox-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .radio-option:hover, .checkbox-option:hover {
            border-color: var(--primary-color);
            background-color: rgba(var(--primary-color-rgb), 0.05);
        }
        
        .radio-option input[type="radio"]:checked + label,
        .checkbox-option input[type="checkbox"]:checked + label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .radio-option input[type="radio"]:checked ~ .option-content,
        .checkbox-option input[type="checkbox"]:checked ~ .option-content {
            border-color: var(--primary-color);
        }
        
        .completion-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .completion-message h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <?php if ($quiz->logo_url): ?>
            <div class="quiz-logo">
                <img src="<?php echo e($quiz->logo_url); ?>" alt="Logo">
            </div>
        <?php endif; ?>
        
        <div class="quiz-card" id="welcome-card" style="<?php echo count($steps) > 0 ? '' : 'display: none;'; ?>">
            <h1 class="quiz-title"><?php echo e($quiz->name); ?></h1>
            <?php if ($quiz->description): ?>
                <p class="quiz-description"><?php echo e($quiz->description); ?></p>
            <?php endif; ?>
            <?php if ($quiz->welcome_message): ?>
                <p class="text-center"><?php echo nl2br(e($quiz->welcome_message)); ?></p>
            <?php endif; ?>
            <?php if (count($steps) > 0): ?>
                <div class="text-center mt-4">
                    <button class="btn btn-primary btn-lg" onclick="startQuiz()">
                        Começar Quiz
                    </button>
                </div>
            <?php else: ?>
                <p class="text-center text-muted mt-4">Este quiz ainda não possui etapas configuradas.</p>
            <?php endif; ?>
        </div>
        
        <div id="quiz-steps" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
            </div>
            
            <?php if (empty($steps)): ?>
                <div class="quiz-card">
                    <p class="text-center text-muted">Este quiz ainda não possui etapas configuradas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($steps as $index => $step): ?>
                <div class="quiz-card step-container" data-step-index="<?php echo $index; ?>" data-step-id="<?php echo $step->id; ?>">
                    <h2 class="step-title"><?php echo e($step->title); ?></h2>
                    <?php if ($step->description): ?>
                        <p class="step-description"><?php echo e($step->description); ?></p>
                    <?php endif; ?>
                    
                    <form class="step-form" onsubmit="handleStepSubmit(event, <?php echo $index; ?>, <?php echo count($steps); ?>)">
                        <?php 
                        $options = $step->options();
                        $fieldName = $step->field_name ?: 'step_' . $step->id;
                        ?>
                        
                        <?php if ($step->type === 'text'): ?>
                            <input type="text" 
                                   name="<?php echo $fieldName; ?>" 
                                   class="form-control" 
                                   <?php echo $step->required ? 'required' : ''; ?>
                                   placeholder="Digite sua resposta">
                        
                        <?php elseif ($step->type === 'textarea'): ?>
                            <textarea name="<?php echo $fieldName; ?>" 
                                      class="form-control" 
                                      rows="4"
                                      <?php echo $step->required ? 'required' : ''; ?>
                                      placeholder="Digite sua resposta"></textarea>
                        
                        <?php elseif ($step->type === 'email'): ?>
                            <input type="email" 
                                   name="<?php echo $fieldName; ?>" 
                                   class="form-control" 
                                   <?php echo $step->required ? 'required' : ''; ?>
                                   placeholder="seu@email.com">
                        
                        <?php elseif ($step->type === 'phone'): ?>
                            <input type="tel" 
                                   name="<?php echo $fieldName; ?>" 
                                   class="form-control" 
                                   <?php echo $step->required ? 'required' : ''; ?>
                                   placeholder="(00) 00000-0000">
                        
                        <?php elseif ($step->type === 'number'): ?>
                            <input type="number" 
                                   name="<?php echo $fieldName; ?>" 
                                   class="form-control" 
                                   <?php echo $step->required ? 'required' : ''; ?>
                                   placeholder="Digite um número">
                        
                        <?php elseif ($step->type === 'select'): ?>
                            <select name="<?php echo $fieldName; ?>" 
                                    class="form-select" 
                                    <?php echo $step->required ? 'required' : ''; ?>>
                                <option value="">Selecione uma opção</option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?php echo e($option->value ?: $option->label); ?>">
                                        <?php echo e($option->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        
                        <?php elseif ($step->type === 'radio'): ?>
                            <?php foreach ($options as $option): ?>
                                <div class="radio-option">
                                    <input type="radio" 
                                           name="<?php echo $fieldName; ?>" 
                                           id="option_<?php echo $step->id; ?>_<?php echo $option->id; ?>"
                                           value="<?php echo e($option->value ?: $option->label); ?>"
                                           <?php echo $step->required ? 'required' : ''; ?>>
                                    <label for="option_<?php echo $step->id; ?>_<?php echo $option->id; ?>" class="ms-2">
                                        <?php echo e($option->label); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        
                        <?php elseif ($step->type === 'checkbox'): ?>
                            <?php foreach ($options as $option): ?>
                                <div class="checkbox-option">
                                    <input type="checkbox" 
                                           name="<?php echo $fieldName; ?>[]" 
                                           id="option_<?php echo $step->id; ?>_<?php echo $option->id; ?>"
                                           value="<?php echo e($option->value ?: $option->label); ?>">
                                    <label for="option_<?php echo $step->id; ?>_<?php echo $option->id; ?>" class="ms-2">
                                        <?php echo e($option->label); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <?php if ($index > 0): ?>
                                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                                    <i class="ri-arrow-left-line me-1"></i>
                                    Anterior
                                </button>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <?php echo $index === count($steps) - 1 ? 'Finalizar' : 'Próximo'; ?>
                                <i class="ri-arrow-right-line ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="quiz-card" id="completion-card" style="display: none;">
            <div class="completion-message">
                <h2><i class="ri-checkbox-circle-line me-2"></i>Quiz Concluído!</h2>
                <p id="completion-message-text">
                    <?php echo nl2br(e($quiz->completion_message ?: 'Obrigado por responder o quiz!')); ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 0;
        const totalSteps = <?php echo count($steps); ?>;
        const quizSlug = '<?php echo $quiz->slug; ?>';
        const quizId = <?php echo $quiz->id; ?>;
        const formData = {};
        
        function startQuiz() {
            document.getElementById('welcome-card').style.display = 'none';
            document.getElementById('quiz-steps').style.display = 'block';
            showStep(0);
        }
        
        function showStep(index) {
            document.querySelectorAll('.step-container').forEach((step, i) => {
                step.classList.toggle('active', i === index);
            });
            
            currentStep = index;
            updateProgress();
        }
        
        function updateProgress() {
            const progress = ((currentStep + 1) / totalSteps) * 100;
            document.getElementById('progress-fill').style.width = progress + '%';
        }
        
        function handleStepSubmit(event, stepIndex, totalSteps) {
            event.preventDefault();
            
            const form = event.target;
            const formDataObj = new FormData(form);
            
            // Coleta dados do formulário
            for (let [key, value] of formDataObj.entries()) {
                if (formData[key]) {
                    // Se já existe e é array, adiciona
                    if (Array.isArray(formData[key])) {
                        formData[key].push(value);
                    } else {
                        formData[key] = [formData[key], value];
                    }
                } else {
                    formData[key] = value;
                }
            }
            
            if (stepIndex < totalSteps - 1) {
                showStep(stepIndex + 1);
            } else {
                submitQuiz();
            }
        }
        
        function previousStep() {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        }
        
        function submitQuiz() {
            // Usa slug se disponível, senão usa ID
            const quizIdentifier = quizSlug || quizId;
            fetch(`<?php echo url('/quiz'); ?>/${quizIdentifier}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('quiz-steps').style.display = 'none';
                    document.getElementById('completion-card').style.display = 'block';
                    if (data.message) {
                        document.getElementById('completion-message-text').innerHTML = data.message;
                    }
                } else {
                    alert('Erro ao enviar quiz: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar quiz. Tente novamente.');
            });
        }
        
        // Inicialização: se há steps, mostra welcome inicialmente
        // Se não há steps, o welcome já está escondido e mostra mensagem
        <?php if (count($steps) > 0): ?>
            // Welcome já está visível, steps escondidos - tudo certo
        <?php else: ?>
            // Sem steps, welcome já está escondido
        <?php endif; ?>
    </script>
</body>
</html>

