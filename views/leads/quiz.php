<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz de Qualificação - <?php echo config('app.name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .question-enter {
            animation: slideIn 0.3s ease-out;
        }
        .progress-bar {
            transition: width 0.5s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Barra de Progresso -->
        <div class="mb-8">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Pergunta <span id="current-question">1</span> de <span id="total-questions">8</span></span>
                <span id="progress-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div id="progress-bar" class="progress-bar bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <!-- Container das Perguntas -->
        <div id="quiz-container" class="bg-white rounded-2xl shadow-xl p-8 md:p-12 min-h-[400px] flex items-center">
            <form id="quiz-form" class="w-full">
                <!-- Pergunta 1: Faturamento -->
                <div class="question" data-step="1">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual é o faturamento atual da sua empresa?</h2>
                    <div class="space-y-4">
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faturamento" value="0-10k" class="mr-3" required>
                            <span class="text-lg">Até R$ 10.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faturamento" value="10-50k" class="mr-3" required>
                            <span class="text-lg">R$ 10.000 - R$ 50.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faturamento" value="50-200k" class="mr-3" required>
                            <span class="text-lg">R$ 50.000 - R$ 200.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faturamento" value="200k+" class="mr-3" required>
                            <span class="text-lg">Acima de R$ 200.000/mês</span>
                        </label>
                    </div>
                </div>

                <!-- Pergunta 2: Investimento -->
                <div class="question hidden" data-step="2">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Quanto pretende investir em tráfego pago?</h2>
                    <div class="space-y-4">
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="investimento" value="1k" class="mr-3" required>
                            <span class="text-lg">Até R$ 1.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="investimento" value="3k" class="mr-3" required>
                            <span class="text-lg">R$ 1.000 - R$ 3.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="investimento" value="5k" class="mr-3" required>
                            <span class="text-lg">R$ 3.000 - R$ 5.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="investimento" value="10k" class="mr-3" required>
                            <span class="text-lg">R$ 5.000 - R$ 10.000/mês</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="investimento" value="10k+" class="mr-3" required>
                            <span class="text-lg">Acima de R$ 10.000/mês</span>
                        </label>
                    </div>
                </div>

                <!-- Pergunta 3: Instagram -->
                <div class="question hidden" data-step="3">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual o Instagram da sua empresa?</h2>
                    <input type="text" name="instagram" placeholder="@seuinstagram" class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required>
                </div>

                <!-- Pergunta 4: Ramo -->
                <div class="question hidden" data-step="4">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual é o ramo da sua empresa?</h2>
                    <input type="text" name="ramo" placeholder="Ex: E-commerce, Serviços, SaaS..." class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required>
                </div>

                <!-- Pergunta 5: Faz Tráfego -->
                <div class="question hidden" data-step="5">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Você já faz tráfego pago atualmente?</h2>
                    <div class="space-y-4">
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faz_trafego" value="sim" class="mr-3" required>
                            <span class="text-lg">Sim</span>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="faz_trafego" value="não" class="mr-3" required>
                            <span class="text-lg">Não</span>
                        </label>
                    </div>
                </div>

                <!-- Pergunta 6: Objetivo -->
                <div class="question hidden" data-step="6">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual seu objetivo principal com tráfego pago?</h2>
                    <textarea name="objetivo" rows="4" placeholder="Descreva seu objetivo..." class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required></textarea>
                </div>

                <!-- Pergunta 7: Nome -->
                <div class="question hidden" data-step="7">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual é o seu nome?</h2>
                    <input type="text" name="nome" placeholder="Seu nome completo" class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required>
                </div>

                <!-- Pergunta 8: Email -->
                <div class="question hidden" data-step="8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual é o seu email?</h2>
                    <input type="email" name="email" placeholder="seu@email.com" class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required>
                </div>

                <!-- Pergunta 9: Telefone -->
                <div class="question hidden" data-step="9">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Qual é o seu telefone?</h2>
                    <input type="tel" name="telefone" placeholder="(00) 00000-0000" class="w-full p-4 border-2 border-gray-200 rounded-lg text-lg focus:border-blue-500 focus:outline-none" required>
                </div>

                <!-- Botão Próximo/Enviar -->
                <div class="mt-8 flex justify-end">
                    <button type="button" id="btn-next" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:from-blue-600 hover:to-indigo-700 transition shadow-lg">
                        Próximo →
                    </button>
                    <button type="submit" id="btn-submit" class="hidden bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition shadow-lg">
                        Enviar ✓
                    </button>
                </div>
            </form>
        </div>

        <!-- Mensagem de Sucesso -->
        <div id="success-message" class="hidden bg-white rounded-2xl shadow-xl p-12 text-center">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Obrigado!</h2>
            <p class="text-lg text-gray-600">Seu formulário foi enviado com sucesso. Entraremos em contato em breve!</p>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 9;

        function updateProgress() {
            const percent = (currentStep / totalSteps) * 100;
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('progress-percent').textContent = Math.round(percent) + '%';
            document.getElementById('current-question').textContent = currentStep;
        }

        function showStep(step) {
            document.querySelectorAll('.question').forEach((q, index) => {
                if (index + 1 === step) {
                    q.classList.remove('hidden');
                    q.classList.add('question-enter');
                } else {
                    q.classList.add('hidden');
                    q.classList.remove('question-enter');
                }
            });

            // Mostra/esconde botões
            if (step === totalSteps) {
                document.getElementById('btn-next').classList.add('hidden');
                document.getElementById('btn-submit').classList.remove('hidden');
            } else {
                document.getElementById('btn-next').classList.remove('hidden');
                document.getElementById('btn-submit').classList.add('hidden');
            }

            updateProgress();
        }

        document.getElementById('btn-next').addEventListener('click', function() {
            const currentQuestion = document.querySelector(`.question[data-step="${currentStep}"]`);
            const requiredInput = currentQuestion.querySelector('[required]');
            
            if (requiredInput) {
                if (requiredInput.type === 'radio') {
                    const checked = currentQuestion.querySelector('input[type="radio"]:checked');
                    if (!checked) {
                        alert('Por favor, selecione uma opção.');
                        return;
                    }
                } else if (!requiredInput.value.trim()) {
                    alert('Por favor, preencha o campo.');
                    requiredInput.focus();
                    return;
                }
            }

            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        });

        document.getElementById('quiz-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            // Adiciona token da URL se existir
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            if (token) {
                data.token = token;
            }

            // Mostra loading
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            fetch('<?php echo url('/api/leads/new'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('quiz-container').classList.add('hidden');
                    document.getElementById('success-message').classList.remove('hidden');
                } else {
                    alert('Erro: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Enviar ✓';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar formulário. Tente novamente.');
                btn.disabled = false;
                btn.textContent = 'Enviar ✓';
            });
        });

        // Inicializa
        showStep(1);
    </script>
</body>
</html>

