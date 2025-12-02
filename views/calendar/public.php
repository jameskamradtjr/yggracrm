<!DOCTYPE html>
<html lang="pt-BR" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    
    <style>
        body {
            background-color: #f6f9fc;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .calendar-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .calendar-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            overflow: hidden;
        }
        .calendar-header-section {
            background: linear-gradient(135deg, #5d87ff 0%, #5d87ff 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .calendar-header-section h1 {
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .calendar-header-section p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }
        .calendar-body {
            padding: 40px;
        }
        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            border: 1px solid #e5eaef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            color: #5d87ff;
            font-weight: 500;
        }
        .time-slot:hover {
            border-color: #5d87ff;
            background: #ecf2ff;
            color: #5d87ff;
        }
        .time-slot.selected {
            border-color: #5d87ff;
            background: #5d87ff;
            color: white;
        }
        .time-slot.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5eaef;
            color: #8a9099;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: 600;
        }
        .step.active {
            background: #5d87ff;
            color: white;
        }
        .step.completed {
            background: #13deb9;
            color: white;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5eaef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            font-weight: 500;
            color: #5a6a85;
        }
        .calendar-day:hover:not(.disabled):not(.selected) {
            border-color: #5d87ff;
            background: #ecf2ff;
            color: #5d87ff;
        }
        .calendar-day.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background: #f6f9fc;
        }
        .calendar-day.selected {
            border-color: #5d87ff;
            background: #5d87ff;
            color: white;
        }
        .calendar-day.available {
            border-color: #13deb9;
        }
        .calendar-header-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }
        .calendar-header-day {
            text-align: center;
            font-weight: 600;
            color: #5a6a85;
            font-size: 0.875rem;
            padding: 8px;
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-nav button {
            background: #5d87ff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .calendar-nav button:hover {
            background: #4c73e6;
        }
        .calendar-nav button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .calendar-nav h5 {
            color: #5a6a85;
            font-weight: 600;
            margin: 0;
        }
        .btn-primary {
            background-color: #5d87ff;
            border-color: #5d87ff;
        }
        .btn-primary:hover {
            background-color: #4c73e6;
            border-color: #4c73e6;
        }
        .btn-secondary {
            background-color: #8a9099;
            border-color: #8a9099;
        }
        .form-label {
            color: #5a6a85;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            border-color: #e5eaef;
        }
        .form-control:focus {
            border-color: #5d87ff;
            box-shadow: 0 0 0 0.2rem rgba(93, 135, 255, 0.25);
        }
        .alert-info {
            background-color: #ecf2ff;
            border-color: #5d87ff;
            color: #5d87ff;
        }
    </style>
</head>
<body>
    <div class="calendar-container">
        <div class="calendar-card">
            <div class="calendar-header-section">
                <h1 class="mb-2"><?php echo e($settings->calendar_title ?: 'Agendar Reunião'); ?></h1>
                <?php if ($settings->calendar_description): ?>
                    <p class="mb-0"><?php echo nl2br(e($settings->calendar_description)); ?></p>
                <?php endif; ?>
                <p class="mt-3 mb-0">
                    <i class="ti ti-user-circle me-2"></i>
                    <?php echo e($user->name); ?>
                </p>
            </div>
            
            <div class="calendar-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">1</div>
                    <div class="step" id="step2-indicator">2</div>
                    <div class="step" id="step3-indicator">3</div>
                </div>
                
                <!-- Step 1: Selecionar Data -->
                <div class="form-section active" id="step1">
                    <h4 class="mb-4" style="color: #5a6a85; font-weight: 600;">Selecione uma data</h4>
                    <div id="calendar-container" class="mb-3"></div>
                    <input type="hidden" id="appointment_date" value="">
                    <div class="mt-3">
                        <button class="btn btn-primary btn-lg w-100" onclick="loadAvailableTimes()" id="btn-next-date" disabled>
                            Continuar
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Selecionar Horário -->
                <div class="form-section" id="step2">
                    <h4 class="mb-4" style="color: #5a6a85; font-weight: 600;">Selecione um horário</h4>
                    <div id="available-times-container">
                        <p class="text-muted">Carregando horários disponíveis...</p>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-secondary btn-lg w-100" onclick="goToStep(1)">
                            Voltar
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Informações do Cliente -->
                <div class="form-section" id="step3">
                    <h4 class="mb-4" style="color: #5a6a85; font-weight: 600;">Suas informações</h4>
                    <form id="booking-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="calendar_slug" value="<?php echo e($settings->calendar_slug); ?>">
                        <input type="hidden" name="appointment_date" id="form_appointment_date">
                        <input type="hidden" name="appointment_time" id="form_appointment_time">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome completo *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="phone" placeholder="(00) 00000-0000">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Alguma informação adicional sobre o agendamento..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Agendamento:</strong><br>
                            <span id="appointment-summary"></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                                Voltar
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                Confirmar Agendamento
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Success Message -->
                <div class="form-section" id="success-message" style="display: none;">
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="ti ti-check-circle" style="font-size: 64px; color: #13deb9;"></i>
                        </div>
                        <h3 style="color: #13deb9; font-weight: 600;">Agendamento Confirmado!</h3>
                        <p style="color: #8a9099;">Você receberá um email de confirmação em breve.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo asset('tema/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js'); ?>"></script>
    <script>
        let currentStep = 1;
        let selectedDate = '';
        let selectedTime = '';
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let availableDates = [];
        
        const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        
        // Carrega datas disponíveis ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadAvailableDates();
        });
        
        function loadAvailableDates() {
            fetch(`<?php echo url('/calendar/' . $settings->calendar_slug . '/available-dates'); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        availableDates = data.dates || [];
                        renderCalendar();
                    } else {
                        availableDates = [];
                        renderCalendar();
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar datas:', error);
                    availableDates = [];
                    renderCalendar();
                });
        }
        
        function renderCalendar() {
            const container = document.getElementById('calendar-container');
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();
            
            let html = '<div class="calendar-nav">';
            html += `<button onclick="previousMonth()" ${currentMonth === new Date().getMonth() && currentYear === new Date().getFullYear() ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>`;
            html += `<h5 class="mb-0">${monthNames[currentMonth]} ${currentYear}</h5>`;
            html += `<button onclick="nextMonth()"><i class="bi bi-chevron-right"></i></button>`;
            html += '</div>';
            
            html += '<div class="calendar-header-days">';
            dayNames.forEach(day => {
                html += `<div class="calendar-header-day">${day}</div>`;
            });
            html += '</div>';
            
            html += '<div class="calendar-grid">';
            
            // Espaços vazios antes do primeiro dia
            for (let i = 0; i < startingDayOfWeek; i++) {
                html += '<div class="calendar-day disabled"></div>';
            }
            
            // Dias do mês
            const today = new Date();
            const minDate = today.toISOString().split('T')[0];
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + <?php echo $settings->advance_booking_days; ?>);
            const maxDateStr = maxDate.toISOString().split('T')[0];
            
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(currentYear, currentMonth, day);
                const dateStr = date.toISOString().split('T')[0];
                const isPast = dateStr < minDate;
                const isFuture = dateStr > maxDateStr;
                const isAvailable = availableDates.includes(dateStr);
                const isSelected = selectedDate === dateStr;
                
                let classes = 'calendar-day';
                if (isPast || isFuture) {
                    classes += ' disabled';
                } else if (isAvailable) {
                    classes += ' available';
                }
                if (isSelected) {
                    classes += ' selected';
                }
                
                html += `<div class="${classes}" onclick="${isPast || isFuture ? '' : `selectDate('${dateStr}')`}" data-date="${dateStr}">${day}</div>`;
            }
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function previousMonth() {
            if (currentMonth === 0) {
                currentMonth = 11;
                currentYear--;
            } else {
                currentMonth--;
            }
            renderCalendar();
        }
        
        function nextMonth() {
            if (currentMonth === 11) {
                currentMonth = 0;
                currentYear++;
            } else {
                currentMonth++;
            }
            renderCalendar();
        }
        
        function selectDate(date) {
            selectedDate = date;
            document.getElementById('appointment_date').value = date;
            document.getElementById('btn-next-date').disabled = false;
            
            // Atualiza visual do calendário
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
                if (day.dataset.date === date) {
                    day.classList.add('selected');
                }
            });
        }
        
        function goToStep(step) {
            // Esconde todas as seções
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Atualiza indicadores
            document.querySelectorAll('.step').forEach((s, i) => {
                s.classList.remove('active', 'completed');
                if (i + 1 < step) {
                    s.classList.add('completed');
                } else if (i + 1 === step) {
                    s.classList.add('active');
                }
            });
            
            // Mostra seção atual
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
        }
        
        function loadAvailableTimes() {
            const dateInput = document.getElementById('appointment_date');
            const date = dateInput.value;
            
            if (!date) {
                alert('Por favor, selecione uma data.');
                return;
            }
            
            selectedDate = date;
            document.getElementById('form_appointment_date').value = date;
            
            const container = document.getElementById('available-times-container');
            container.innerHTML = '<p class="text-muted">Carregando horários disponíveis...</p>';
            
            fetch(`<?php echo url('/calendar/' . $settings->calendar_slug . '/available-times'); ?>?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.times.length > 0) {
                        container.innerHTML = '';
                        data.times.forEach(time => {
                            const slot = document.createElement('div');
                            slot.className = 'time-slot';
                            slot.textContent = time;
                            slot.onclick = () => selectTime(time, slot);
                            container.appendChild(slot);
                        });
                    } else {
                        container.innerHTML = '<p class="text-muted">Nenhum horário disponível para esta data.</p>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    container.innerHTML = '<p class="text-danger">Erro ao carregar horários. Tente novamente.</p>';
                });
            
            goToStep(2);
        }
        
        function selectTime(time, element) {
            // Remove seleção anterior
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Seleciona novo horário
            element.classList.add('selected');
            selectedTime = time;
            document.getElementById('form_appointment_time').value = time;
            
            // Atualiza resumo
            const dateObj = new Date(selectedDate);
            const dateStr = dateObj.toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('appointment-summary').textContent = 
                `${dateStr} às ${time}`;
            
            goToStep(3);
        }
        
        document.getElementById('booking-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(`<?php echo url('/calendar/' . $settings->calendar_slug . '/book'); ?>`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('step3').style.display = 'none';
                    document.getElementById('success-message').style.display = 'block';
                } else {
                    alert(data.message || 'Erro ao processar agendamento.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar agendamento. Tente novamente.');
            });
        });
    </script>
</body>
</html>

