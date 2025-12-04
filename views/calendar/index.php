<?php
ob_start();
$title = $title ?? 'Agenda';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Agenda</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Agenda</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body calender-sidebar app-calendar">
        <div id="calendar"></div>
    </div>
</div>

<style>
/* Fix: Garante que o modal tenha altura máxima e scroll funcione */
#eventModal .modal-dialog {
    max-height: 90vh;
}

#eventModal .modal-content {
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

#eventModal .modal-body {
    overflow-y: auto;
    max-height: calc(90vh - 140px); /* Subtrai header + footer */
}

#eventModal .modal-footer {
    flex-shrink: 0; /* Garante que o footer sempre fique visível */
}
</style>

<!-- Modal para Adicionar/Editar Evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Adicionar / Editar Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="eventForm">
                <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id="event_id" name="event_id" value="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Título do Evento <span class="text-danger">*</span></label>
                            <input id="event-title" name="titulo" type="text" class="form-control" required />
                        </div>
                        <div class="col-md-12 mb-3">
                            <div>
                                <label class="form-label">Cor do Evento</label>
                            </div>
                            <div class="d-flex">
                                <div class="n-chk">
                                    <div class="form-check form-check-primary form-check-inline">
                                        <input class="form-check-input" type="radio" name="cor" value="primary" id="modalPrimary" checked />
                                        <label class="form-check-label" for="modalPrimary">Primary</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-success form-check-inline">
                                        <input class="form-check-input" type="radio" name="cor" value="success" id="modalSuccess" />
                                        <label class="form-check-label" for="modalSuccess">Success</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-warning form-check-inline">
                                        <input class="form-check-input" type="radio" name="cor" value="warning" id="modalWarning" />
                                        <label class="form-check-label" for="modalWarning">Warning</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-danger form-check-inline">
                                        <input class="form-check-input" type="radio" name="cor" value="danger" id="modalDanger" />
                                        <label class="form-check-label" for="modalDanger">Danger</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data/Hora de Início <span class="text-danger">*</span></label>
                            <input id="event-start-date" name="data_inicio" type="datetime-local" class="form-control" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data/Hora de Término</label>
                            <input id="event-end-date" name="data_fim" type="datetime-local" class="form-control" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dia_inteiro" name="dia_inteiro" value="1" />
                                <label class="form-check-label" for="dia_inteiro">Dia inteiro</label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea id="event-descricao" name="descricao" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Localização</label>
                            <input id="event-localizacao" name="localizacao" type="text" class="form-control" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cliente</label>
                            <?php 
                            $id = 'event-client';
                            $name = 'client_id';
                            $placeholder = 'Digite para buscar cliente...';
                            include base_path('views/components/tom-select-client.php'); 
                            ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Lead</label>
                            <select id="event-lead" name="lead_id" class="form-select">
                                <option value="">Nenhum</option>
                                <?php foreach ($leads as $lead): ?>
                                    <option value="<?php echo $lead->id; ?>"><?php echo e($lead->nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Projeto</label>
                            <select id="event-project" name="project_id" class="form-select">
                                <option value="">Nenhum</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project->id; ?>"><?php echo e($project->titulo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Usuário Responsável</label>
                            <select id="event-responsible" name="responsible_user_id" class="form-select">
                                <option value="">Nenhum</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->id; ?>"><?php echo e($user->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea id="event-observacoes" name="observacoes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-danger" id="btn-delete-event" style="display: none;">Excluir</button>
                    <button type="submit" class="btn btn-success" id="btn-update-event" style="display: none;">Atualizar</button>
                    <button type="submit" class="btn btn-primary" id="btn-add-event">Adicionar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo asset('tema/assets/libs/fullcalendar/index.global.min.js'); ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.querySelector("#calendar");
    var eventModal = new bootstrap.Modal(document.getElementById("eventModal"));
    var eventForm = document.getElementById("eventForm");
    var eventIdInput = document.getElementById("event_id");
    var btnAddEvent = document.getElementById("btn-add-event");
    var btnUpdateEvent = document.getElementById("btn-update-event");
    var btnDeleteEvent = document.getElementById("btn-delete-event");
    
    // Verifica se os botões foram encontrados
    if (!btnAddEvent || !btnUpdateEvent || !btnDeleteEvent) {
        console.error('Erro: Botões do modal não foram encontrados!');
        console.log('btnAddEvent:', btnAddEvent);
        console.log('btnUpdateEvent:', btnUpdateEvent);
        console.log('btnDeleteEvent:', btnDeleteEvent);
    }
    
    var calendarsEvents = {
        Danger: "danger",
        Success: "success",
        Primary: "primary",
        Warning: "warning",
    };

    var checkWindowWidth = function () {
        return window.innerWidth <= 1199;
    };

    // Inicializa calendário
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: {
            code: 'pt-br',
            week: {
                dow: 1, // Segunda-feira é o primeiro dia da semana
                doy: 4  // A semana que contém 4 de janeiro é a primeira semana do ano
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                day: 'Dia',
                list: 'Lista'
            },
            weekText: 'Sm',
            allDayText: 'Dia inteiro',
            moreLinkText: 'mais',
            noEventsText: 'Nenhum evento para exibir',
            monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                         'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
                              'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            dayNames: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 
                       'Quinta-feira', 'Sexta-feira', 'Sábado'],
            dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']
        },
        selectable: true,
        editable: true, // Permite arrastar e redimensionar eventos
        eventStartEditable: true, // Permite arrastar o início do evento
        eventDurationEditable: true, // Permite redimensionar a duração do evento
        timeZone: 'local', // Usa timezone local
        height: checkWindowWidth() ? 900 : 1052,
        initialView: checkWindowWidth() ? "listWeek" : "dayGridMonth",
        headerToolbar: {
            left: "prev next addEventButton",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek"
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('<?php echo url('/calendar/events'); ?>?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr)
                .then(response => response.json())
                .then(data => {
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Erro ao carregar eventos:', error);
                    failureCallback(error);
                });
        },
        select: function(info) {
            openEventModal(null, info.startStr, info.endStr);
        },
        eventClick: function(info) {
            openEventModal(info.event.id, info.event.startStr, info.event.endStr, info.event);
        },
        eventDrop: function(info) {
            // Quando o evento é arrastado para outro dia/hora
            updateEventDatesOnDrop(info);
        },
        eventResize: function(info) {
            // Quando o evento é redimensionado (muda duração)
            updateEventDatesOnResize(info);
        },
        customButtons: {
            addEventButton: {
                text: "Adicionar Evento",
                click: function() {
                    openEventModal();
                }
            }
        },
        eventClassNames: function ({ event: calendarEvent }) {
            // Se for um timer, mantém a classe timer-ativo
            if (calendarEvent._def.extendedProps.tipo === 'timer') {
                return ['timer-ativo'];
            }
            const getColorValue = calendarsEvents[calendarEvent._def.extendedProps.calendar] || 'primary';
            return ["event-fc-color fc-bg-" + getColorValue];
        },
        windowResize: function (arg) {
            if (checkWindowWidth()) {
                calendar.changeView("listWeek");
                calendar.setOption("height", 900);
            } else {
                calendar.changeView("dayGridMonth");
                calendar.setOption("height", 1052);
            }
        }
    });

    calendar.render();
    
    // Atualiza eventos de timer a cada 30 segundos para mostrar timers ativos
    setInterval(function() {
        calendar.refetchEvents();
    }, 30000);
    
    // Atualiza mais frequentemente quando há timers ativos (a cada 10 segundos)
    let timerUpdateInterval = setInterval(function() {
        // Verifica se há timers ativos e atualiza o calendário
        fetch('<?php echo url('/projects/kanban/timer/active'); ?>', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.total_timers > 0) {
                // Se há timers ativos, atualiza o calendário para mostrar o tempo atualizado
                calendar.refetchEvents();
            }
        })
        .catch(error => {
            console.error('Erro ao verificar timers:', error);
        });
    }, 10000);
    
    // Atualiza imediatamente ao carregar a página para mostrar timers ativos
    setTimeout(function() {
        calendar.refetchEvents();
    }, 1000);

    // Abre modal de evento
    function openEventModal(eventId, startDate, endDate, eventObj) {
        eventForm.reset();
        eventIdInput.value = eventId || '';
        
        // Garante que os botões existem antes de manipular
        if (!btnAddEvent || !btnUpdateEvent || !btnDeleteEvent) {
            console.error('Botões do modal não encontrados!');
            console.log('btnAddEvent:', btnAddEvent);
            console.log('btnUpdateEvent:', btnUpdateEvent);
            console.log('btnDeleteEvent:', btnDeleteEvent);
            return;
        }
        
        if (eventId) {
            // Modo edição
            btnAddEvent.style.display = 'none';
            btnUpdateEvent.style.display = 'inline-block';
            btnDeleteEvent.style.display = 'inline-block';
            
            if (eventObj) {
                document.getElementById('event-title').value = eventObj.title || '';
                
                // Define cor do evento
                var corValue = (eventObj._def.extendedProps.calendar || 'Primary').toLowerCase();
                var corRadio = document.querySelector(`input[name="cor"][value="${corValue}"]`);
                if (corRadio) {
                    corRadio.checked = true;
                }
                
                // Formata data para datetime-local
                // Usa a data original do banco se disponível, senão usa a do evento
                var startDateStr = eventObj._def.extendedProps.data_inicio_original || eventObj.start;
                // Se está no formato MySQL (Y-m-d H:i:s), converte para datetime-local
                if (startDateStr.indexOf(' ') !== -1) {
                    startDateStr = startDateStr.replace(' ', 'T');
                }
                // Remove segundos se houver
                if (startDateStr.length > 16) {
                    startDateStr = startDateStr.substring(0, 16);
                }
                document.getElementById('event-start-date').value = startDateStr;
                
                if (eventObj.end) {
                    var endDateStr = eventObj._def.extendedProps.data_fim_original || eventObj.end;
                    // Se está no formato MySQL (Y-m-d H:i:s), converte para datetime-local
                    if (endDateStr.indexOf(' ') !== -1) {
                        endDateStr = endDateStr.replace(' ', 'T');
                    }
                    // Remove segundos se houver
                    if (endDateStr.length > 16) {
                        endDateStr = endDateStr.substring(0, 16);
                    }
                    document.getElementById('event-end-date').value = endDateStr;
                } else {
                    document.getElementById('event-end-date').value = '';
                }
                
                if (eventObj._def.extendedProps.descricao) {
                    document.getElementById('event-descricao').value = eventObj._def.extendedProps.descricao;
                } else {
                    document.getElementById('event-descricao').value = '';
                }
                if (eventObj._def.extendedProps.localizacao) {
                    document.getElementById('event-localizacao').value = eventObj._def.extendedProps.localizacao;
                } else {
                    document.getElementById('event-localizacao').value = '';
                }
                if (eventObj._def.extendedProps.observacoes) {
                    document.getElementById('event-observacoes').value = eventObj._def.extendedProps.observacoes;
                } else {
                    document.getElementById('event-observacoes').value = '';
                }
                if (eventObj._def.extendedProps.client_id) {
                    document.getElementById('event-client').value = eventObj._def.extendedProps.client_id;
                } else {
                    document.getElementById('event-client').value = '';
                }
                if (eventObj._def.extendedProps.lead_id) {
                    document.getElementById('event-lead').value = eventObj._def.extendedProps.lead_id;
                } else {
                    document.getElementById('event-lead').value = '';
                }
                if (eventObj._def.extendedProps.project_id) {
                    document.getElementById('event-project').value = eventObj._def.extendedProps.project_id;
                } else {
                    document.getElementById('event-project').value = '';
                }
                if (eventObj._def.extendedProps.responsible_user_id) {
                    document.getElementById('event-responsible').value = eventObj._def.extendedProps.responsible_user_id;
                } else {
                    document.getElementById('event-responsible').value = '';
                }
                document.getElementById('dia_inteiro').checked = eventObj.allDay || false;
            }
        } else {
            // Modo criação
            btnAddEvent.style.display = 'inline-block';
            btnUpdateEvent.style.display = 'none';
            btnDeleteEvent.style.display = 'none';
            
            if (startDate) {
                var start = new Date(startDate);
                var startStr = start.toISOString().slice(0, 16);
                document.getElementById('event-start-date').value = startStr;
            }
            if (endDate) {
                var end = new Date(endDate);
                var endStr = end.toISOString().slice(0, 16);
                document.getElementById('event-end-date').value = endStr;
            }
        }
        
        eventModal.show();
    }

    // Salva evento (criar ou atualizar)
    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(eventForm);
        var eventId = eventIdInput.value;
        var url = eventId 
            ? '<?php echo url('/calendar'); ?>/' + eventId + '/update'
            : '<?php echo url('/calendar'); ?>/store';
        var method = 'POST';
        
        var submitBtn = eventId ? btnUpdateEvent : btnAddEvent;
        var originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';

        fetch(url, {
            method: method,
            body: formData
        })
        .then(async response => {
            // Verifica se a resposta é JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Se não é JSON, tenta ler como texto para ver o erro
                const text = await response.text();
                console.error('Resposta não é JSON:', text);
                throw new Error('Resposta do servidor não é JSON. Verifique o console para mais detalhes.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                eventModal.hide();
                calendar.refetchEvents();
                alert('Evento salvo com sucesso!');
            } else {
                var errorMsg = 'Erro: ' + data.message;
                if (data.errors) {
                    var errorsList = Object.values(data.errors).flat().join('\n');
                    errorMsg += '\n\nDetalhes:\n' + errorsList;
                }
                alert(errorMsg);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Erro ao salvar evento:', error);
            alert('Erro ao salvar evento: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Atualiza evento
    btnUpdateEvent.addEventListener('click', function() {
        eventForm.dispatchEvent(new Event('submit'));
    });

    // Deleta evento
    btnDeleteEvent.addEventListener('click', function() {
        if (!confirm('Tem certeza que deseja excluir este evento?')) {
            return;
        }
        
        var eventId = eventIdInput.value;
        var formData = new FormData();
        formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
        
        btnDeleteEvent.disabled = true;
        btnDeleteEvent.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Excluindo...';

        fetch('<?php echo url('/calendar'); ?>/' + eventId + '/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                eventModal.hide();
                calendar.refetchEvents();
                alert('Evento excluído com sucesso!');
            } else {
                alert('Erro: ' + data.message);
                btnDeleteEvent.disabled = false;
                btnDeleteEvent.innerHTML = 'Excluir';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir evento.');
            btnDeleteEvent.disabled = false;
            btnDeleteEvent.innerHTML = 'Excluir';
        });
    });

    // Limpa formulário ao fechar modal
    document.getElementById('eventModal').addEventListener('hidden.bs.modal', function() {
        eventForm.reset();
        eventIdInput.value = '';
        btnAddEvent.style.display = 'inline-block';
        btnUpdateEvent.style.display = 'none';
        btnDeleteEvent.style.display = 'none';
    });

    // Atualiza apenas as datas do evento quando arrastado
    function updateEventDatesOnDrop(info) {
        var event = info.event;
        var formData = new FormData();
        formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
        
        // Formata data para datetime-local (preserva hora local sem conversão de timezone)
        // O FullCalendar já retorna a data no formato correto, apenas precisamos formatar
        var startStr = '';
        if (event.start) {
            var startDate = new Date(event.start);
            // Usa métodos locais para preservar hora local
            startStr = startDate.getFullYear() + '-' + 
                String(startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                String(startDate.getDate()).padStart(2, '0') + 'T' + 
                String(startDate.getHours()).padStart(2, '0') + ':' + 
                String(startDate.getMinutes()).padStart(2, '0');
        }
        formData.append('data_inicio', startStr);
        
        if (event.end) {
            var endDate = new Date(event.end);
            var endStr = endDate.getFullYear() + '-' + 
                String(endDate.getMonth() + 1).padStart(2, '0') + '-' + 
                String(endDate.getDate()).padStart(2, '0') + 'T' + 
                String(endDate.getHours()).padStart(2, '0') + ':' + 
                String(endDate.getMinutes()).padStart(2, '0');
            formData.append('data_fim', endStr);
        } else {
            formData.append('data_fim', startStr);
        }
        
        // Mantém os outros campos do evento
        formData.append('titulo', event.title);
        var cor = (event._def.extendedProps.calendar || 'Primary').toLowerCase();
        formData.append('cor', cor);
        if (event._def.extendedProps.descricao) {
            formData.append('descricao', event._def.extendedProps.descricao);
        }
        if (event._def.extendedProps.localizacao) {
            formData.append('localizacao', event._def.extendedProps.localizacao);
        }
        if (event._def.extendedProps.observacoes) {
            formData.append('observacoes', event._def.extendedProps.observacoes);
        }
        if (event._def.extendedProps.client_id) {
            formData.append('client_id', event._def.extendedProps.client_id);
        }
        if (event._def.extendedProps.lead_id) {
            formData.append('lead_id', event._def.extendedProps.lead_id);
        }
        if (event._def.extendedProps.project_id) {
            formData.append('project_id', event._def.extendedProps.project_id);
        }
        if (event._def.extendedProps.responsible_user_id) {
            formData.append('responsible_user_id', event._def.extendedProps.responsible_user_id);
        }
        formData.append('dia_inteiro', event.allDay ? '1' : '0');

        fetch('<?php echo url('/calendar'); ?>/' + event.id + '/update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Reverte a mudança se houver erro
                info.revert();
                alert('Erro ao atualizar evento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            // Reverte a mudança se houver erro
            info.revert();
            alert('Erro ao atualizar evento.');
        });
    }

    // Atualiza apenas as datas do evento quando redimensionado
    function updateEventDatesOnResize(info) {
        var event = info.event;
        var formData = new FormData();
        formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
        
        // Formata data para datetime-local (preserva hora local sem conversão de timezone)
        // O FullCalendar já retorna a data no formato correto, apenas precisamos formatar
        var startStr = '';
        if (event.start) {
            var startDate = new Date(event.start);
            // Usa métodos locais para preservar hora local
            startStr = startDate.getFullYear() + '-' + 
                String(startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                String(startDate.getDate()).padStart(2, '0') + 'T' + 
                String(startDate.getHours()).padStart(2, '0') + ':' + 
                String(startDate.getMinutes()).padStart(2, '0');
        }
        formData.append('data_inicio', startStr);
        
        if (event.end) {
            var endDate = new Date(event.end);
            var endStr = endDate.getFullYear() + '-' + 
                String(endDate.getMonth() + 1).padStart(2, '0') + '-' + 
                String(endDate.getDate()).padStart(2, '0') + 'T' + 
                String(endDate.getHours()).padStart(2, '0') + ':' + 
                String(endDate.getMinutes()).padStart(2, '0');
            formData.append('data_fim', endStr);
        } else {
            formData.append('data_fim', startStr);
        }
        
        // Mantém os outros campos do evento
        formData.append('titulo', event.title);
        var cor = (event._def.extendedProps.calendar || 'Primary').toLowerCase();
        formData.append('cor', cor);
        if (event._def.extendedProps.descricao) {
            formData.append('descricao', event._def.extendedProps.descricao);
        }
        if (event._def.extendedProps.localizacao) {
            formData.append('localizacao', event._def.extendedProps.localizacao);
        }
        if (event._def.extendedProps.observacoes) {
            formData.append('observacoes', event._def.extendedProps.observacoes);
        }
        if (event._def.extendedProps.client_id) {
            formData.append('client_id', event._def.extendedProps.client_id);
        }
        if (event._def.extendedProps.lead_id) {
            formData.append('lead_id', event._def.extendedProps.lead_id);
        }
        if (event._def.extendedProps.project_id) {
            formData.append('project_id', event._def.extendedProps.project_id);
        }
        if (event._def.extendedProps.responsible_user_id) {
            formData.append('responsible_user_id', event._def.extendedProps.responsible_user_id);
        }
        formData.append('dia_inteiro', event.allDay ? '1' : '0');

        fetch('<?php echo url('/calendar'); ?>/' + event.id + '/update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Reverte a mudança se houver erro
                info.revert();
                alert('Erro ao atualizar evento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            // Reverte a mudança se houver erro
            info.revert();
            alert('Erro ao atualizar evento.');
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Tom Select Scripts
$scripts = '';
if (isset($GLOBALS['tom_select_inits'])) {
    ob_start();
    include base_path('views/components/tom-select-scripts.php');
    $scripts = ob_get_clean();
}

include base_path('views/layouts/app.php');
?>

