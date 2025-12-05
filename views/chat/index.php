<?php
ob_start();
$title = $title ?? 'Chat';
$currentUserId = auth()->getDataUserId();
$userAvatar = 'tema/assets/images/profile/user-1.jpg';
if ($user->avatar) {
    if (str_starts_with($user->avatar, 'http://') || str_starts_with($user->avatar, 'https://')) {
        $userAvatar = $user->avatar;
    } else if (str_starts_with($user->avatar, '/uploads/')) {
        $userAvatar = $user->avatar;
    } else {
        $userAvatar = 'uploads/' . $user->avatar;
    }
}
$userAvatar = asset($userAvatar);
?>
<style>
    /* Garante que o container de mensagens seja visível */
    #messages-container {
        height: calc(100vh - 350px) !important;
        min-height: 400px !important;
        max-height: 600px !important;
        overflow-y: auto !important;
        position: relative !important;
    }
    
    /* Garante que o conteúdo do SimpleBar seja visível */
    #messages-container .simplebar-content {
        padding: 20px !important;
        min-height: 100px !important;
    }
    
    /* Garante que as mensagens sejam visíveis */
    #messages-list {
        min-height: 100px;
        padding: 0;
        display: block !important;
    }
    
    /* Força visibilidade das mensagens */
    #messages-list > div {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
    }
    
    /* Ajusta o chat container */
    .chat-container {
        height: calc(100vh - 200px);
        min-height: 500px;
    }
    
    .chatting-box {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    .parent-chat-box {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .chat-box {
        height: 100%;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .chat-box-inner {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    /* Animação de spin para loader */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .spin {
        animation: spin 1s linear infinite;
    }
</style>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Chat</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Chat</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                        <i class="ti ti-plus me-2"></i>Nova Sala
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card overflow-hidden chat-application">
    <div class="d-flex align-items-center justify-content-between gap-6 m-3 d-lg-none">
        <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas" data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
            <i class="ti ti-menu-2 fs-5"></i>
        </button>
        <form class="position-relative w-100">
            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh" placeholder="Buscar Sala" />
            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
        </form>
    </div>
    <div class="d-flex">
        <!-- Sidebar: Lista de Salas -->
        <div class="w-30 d-none d-lg-block border-end user-chat-box">
            <div class="px-4 pt-9 pb-6">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="position-relative">
                            <img src="<?php echo $userAvatar; ?>" alt="user1" width="54" height="54" class="rounded-circle" />
                            <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                <span class="visually-hidden">Online</span>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-semibold mb-2"><?php echo e($user->name); ?></h6>
                            <p class="mb-0 fs-2"><?php echo e($user->email); ?></p>
                        </div>
                    </div>
                </div>
                <form class="position-relative mb-4">
                    <input type="text" class="form-control search-chat py-2 ps-5" id="search-rooms" placeholder="Buscar Sala" />
                    <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                </form>
                <div class="dropdown mb-3">
                    <a class="text-muted fw-semibold d-flex align-items-center" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Salas <i class="ti ti-chevron-down ms-1 fs-5"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="sortRooms('time')">Ordenar por tempo</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="sortRooms('name')">Ordenar por nome</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="app-chat">
                <ul class="chat-users mb-0 mh-n100" data-simplebar id="rooms-list">
                    <li class="px-4 py-3 text-center text-muted" id="rooms-loading">
                        <p class="mb-0"><i class="ti ti-loader-2 fs-4 spin"></i> Carregando salas...</p>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Área de Chat -->
        <div class="w-70 w-xs-100 chat-container">
            <div class="chat-box-inner-part h-100">
                <!-- Estado: Nenhuma sala selecionada -->
                <div class="chat-not-selected h-100 d-block" id="no-room-selected">
                    <div class="d-flex align-items-center justify-content-center h-100 p-5">
                        <div class="text-center">
                            <span class="text-primary">
                                <i class="ti ti-message-dots fs-10"></i>
                            </span>
                            <h6 class="mt-2">Selecione uma sala para começar a conversar</h6>
                        </div>
                    </div>
                </div>
                
                <!-- Estado: Sala selecionada -->
                <div class="chatting-box d-none" id="room-chat">
                    <div class="p-9 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                        <div class="hstack gap-3 current-chat-room-name">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="ti ti-hash fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 name fw-semibold" id="room-name">-</h6>
                                <p class="mb-0" id="room-description">-</p>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex align-items-center">
                            <li>
                                <button class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 btn btn-link border-0" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addMemberModal"
                                        title="Adicionar Membro">
                                    <i class="ti ti-user-plus"></i>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex parent-chat-box">
                        <div class="chat-box w-xs-100">
                            <div class="chat-box-inner p-9" data-simplebar id="messages-container">
                                <div class="chat-list chat" id="messages-list">
                                    <!-- Mensagens serão carregadas aqui via JavaScript -->
                                </div>
                                <div id="loading-older-messages" class="text-center py-3 d-none">
                                    <i class="ti ti-loader-2 fs-4 spin"></i> Carregando mensagens antigas...
                                </div>
                            </div>
                            <div class="px-9 py-6 border-top chat-send-message-footer">
                                <form id="send-message-form" onsubmit="sendMessage(event)">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 w-85">
                                            <input type="text" 
                                                   class="form-control message-type-box text-muted border-0 p-0 ms-2" 
                                                   id="message-input" 
                                                   placeholder="Digite uma mensagem..." 
                                                   required />
                                        </div>
                                        <ul class="list-unstyled mb-0 d-flex align-items-center">
                                            <li>
                                                <button type="submit" class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 btn btn-link border-0">
                                                    <i class="ti ti-send"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <input type="hidden" id="current-room-id" value="">
                                    <?php echo csrf_field(); ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Criar Nova Sala -->
<div class="modal fade" id="createRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Criar Nova Sala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="create-room-form" onsubmit="createRoom(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="room-name" class="form-label">Nome da Sala <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room-name" name="name" required placeholder="Ex: Geral, Desenvolvimento, Suporte...">
                    </div>
                    <div class="mb-3">
                        <label for="room-description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="room-description" name="description" rows="3" placeholder="Descrição da sala (opcional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="room-type" class="form-label">Tipo</label>
                        <select class="form-select" id="room-type" name="type">
                            <option value="public">Pública</option>
                            <option value="private">Privada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Sala</button>
                </div>
                <?php echo csrf_field(); ?>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Membro -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Membro à Sala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="search-user" class="form-label">Buscar Usuário</label>
                    <input type="text" 
                           class="form-control" 
                           id="search-user" 
                           placeholder="Digite o nome ou email do usuário..."
                           onkeyup="searchUsers(this.value)">
                </div>
                <div id="users-list" style="max-height: 300px; overflow-y: auto;">
                    <!-- Usuários serão carregados aqui -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentRoomId = null;
let messagePollingInterval = null;
let lastMessageId = 0;
let firstMessageId = 0; // ID da primeira mensagem carregada (para scroll infinito)
let isLoadingMessages = false;
let hasMoreMessages = true;

// Carrega uma sala
function loadRoom(roomId) {
    // Para polling anterior se houver
    stopMessagePolling();
    
    currentRoomId = roomId;
    document.getElementById('current-room-id').value = roomId;
    
    // Atualiza UI
    document.getElementById('no-room-selected').classList.add('d-none');
    document.getElementById('room-chat').classList.remove('d-none');
    
    // Atualiza sala ativa na lista
    document.querySelectorAll('.chat-room-item').forEach(item => {
        item.classList.remove('bg-light-subtle');
        if (item.dataset.roomId == roomId) {
            item.classList.add('bg-light-subtle');
        }
    });
    
    // Reseta lastMessageId
    lastMessageId = 0;
    
    // Carrega informações da sala e mensagens
    loadRoomInfo(roomId);
    loadMessages(roomId, false);
    
    // Inicia polling de mensagens após um pequeno delay para garantir que as mensagens iniciais foram carregadas
    setTimeout(() => {
        // Força recálculo do SimpleBar após carregar mensagens
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer && typeof SimpleBar !== 'undefined') {
            try {
                const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                if (simpleBarInstance) {
                    simpleBarInstance.recalculate();
                }
            } catch (e) {
                console.warn('Erro ao recalcular SimpleBar:', e);
            }
        }
        startMessagePolling(roomId);
    }, 500);
}

// Carrega informações da sala
function loadRoomInfo(roomId) {
    fetch(`<?php echo url('/api/chat/rooms'); ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const room = data.rooms.find(r => r.id == roomId);
                if (room) {
                    document.getElementById('room-name').textContent = room.name;
                    document.getElementById('room-description').textContent = room.description || '-';
                }
            }
        })
        .catch(error => console.error('Erro ao carregar informações da sala:', error));
}

// Carrega mensagens da sala
function loadMessages(roomId, append = false) {
    if (isLoadingMessages) {
        return; // Evita múltiplas requisições simultâneas
    }
    
    isLoadingMessages = true;
    
    const url = append && lastMessageId > 0 
        ? `<?php echo url('/api/chat/rooms'); ?>/${roomId}/messages?limit=50&since=${lastMessageId}`
        : `<?php echo url('/api/chat/rooms'); ?>/${roomId}/messages?limit=50&offset=0`;
    
    console.log('Carregando mensagens:', { roomId, append, lastMessageId, url });
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('Resposta de mensagens:', data);
            if (data.success) {
                const container = document.getElementById('messages-list');
                if (!append) {
                    container.innerHTML = '';
                    lastMessageId = 0;
                    firstMessageId = 0;
                    hasMoreMessages = true;
                }
                
                if (data.messages && data.messages.length > 0) {
                    console.log(`Adicionando ${data.messages.length} mensagens`);
                    
                    // Se não for append, define firstMessageId como o ID da primeira mensagem
                    if (!append && data.messages.length > 0) {
                        firstMessageId = data.messages[0].id;
                    }
                    
                    // Verifica se há mais mensagens (se retornou menos que o limite, não há mais)
                    if (data.messages.length < 50) {
                        hasMoreMessages = false;
                    }
                    
                    data.messages.forEach(msg => {
                        if (msg.id > lastMessageId) {
                            lastMessageId = msg.id;
                        }
                        if (firstMessageId === 0 || msg.id < firstMessageId) {
                            firstMessageId = msg.id;
                        }
                        appendMessage(msg, container, append);
                    });
                    
                    // Scroll para o final apenas se não for append ou se estiver no final
                    setTimeout(() => {
                        const messagesContainer = document.getElementById('messages-container');
                        // Força recálculo do SimpleBar
                        if (typeof SimpleBar !== 'undefined') {
                            const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                            if (simpleBarInstance) {
                                simpleBarInstance.recalculate();
                            }
                        }
                        
                        if (!append) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        } else {
                            // Se for append (scroll infinito), mantém a posição de scroll
                            const scrollHeightBefore = messagesContainer.scrollHeight;
                            const scrollTopBefore = messagesContainer.scrollTop;
                            const scrollHeightAfter = messagesContainer.scrollHeight;
                            messagesContainer.scrollTop = scrollTopBefore + (scrollHeightAfter - scrollHeightBefore);
                        }
                    }, 200);
                } else {
                    console.log('Nenhuma mensagem retornada');
                    if (!append) {
                        hasMoreMessages = false;
                    }
                }
            } else {
                console.error('Erro ao carregar mensagens:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar mensagens:', error);
        })
        .finally(() => {
            isLoadingMessages = false;
        });
}

// Adiciona mensagem ao DOM
function appendMessage(msg, container, prepend = false) {
    // Verifica se a mensagem já existe (evita duplicatas)
    const existingMsg = container.querySelector(`[data-message-id="${msg.id}"]`);
    if (existingMsg) {
        console.log('Mensagem já existe, ignorando:', msg.id);
        return;
    }
    
    const isCurrentUser = msg.user.id == <?php echo $currentUserId; ?>;
    let avatarUrl = '<?php echo asset('tema/assets/images/profile/user-1.jpg'); ?>';
    if (msg.user.avatar) {
        if (msg.user.avatar.startsWith('http://') || msg.user.avatar.startsWith('https://')) {
            avatarUrl = msg.user.avatar;
        } else if (msg.user.avatar.startsWith('/uploads/')) {
            avatarUrl = '<?php echo asset(''); ?>' + msg.user.avatar;
        } else {
            avatarUrl = '<?php echo asset('uploads/'); ?>' + msg.user.avatar;
        }
    }
    const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    
    const messageDiv = document.createElement('div');
    messageDiv.setAttribute('data-message-id', msg.id);
    messageDiv.className = `hstack gap-3 align-items-start mb-7 ${isCurrentUser ? 'justify-content-end' : 'justify-content-start'}`;
    
    if (!isCurrentUser) {
        messageDiv.innerHTML = `
            <img src="${avatarUrl}" alt="${escapeHtml(msg.user.name)}" width="40" height="40" class="rounded-circle" />
            <div>
                <h6 class="fs-2 text-muted">${escapeHtml(msg.user.name)}, ${time}</h6>
                <div class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                    ${escapeHtml(msg.message)}
                </div>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="text-end">
                <h6 class="fs-2 text-muted">${time}</h6>
                <div class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                    ${escapeHtml(msg.message)}
                </div>
            </div>
        `;
    }
    
    if (prepend && container.firstChild) {
        container.insertBefore(messageDiv, container.firstChild);
    } else {
        container.appendChild(messageDiv);
    }
    
    // Força recálculo do SimpleBar após adicionar mensagem
    setTimeout(() => {
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            // Tenta recálculo do SimpleBar se disponível
            if (typeof SimpleBar !== 'undefined') {
                try {
                    const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                    if (simpleBarInstance) {
                        simpleBarInstance.recalculate();
                    }
                } catch (e) {
                    console.warn('Erro ao recalcular SimpleBar:', e);
                }
            }
            
            // Scroll para o final apenas se não for prepend (mensagens antigas)
            if (!prepend) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
    }, 100);
}

// Envia mensagem
function sendMessage(event) {
    event.preventDefault();
    
    const roomId = document.getElementById('current-room-id').value;
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    
    if (!message || !roomId) {
        console.warn('Não é possível enviar mensagem:', { message, roomId });
        return;
    }
    
    console.log('Enviando mensagem:', { roomId, message });
    
    const formData = new FormData();
    formData.append('room_id', roomId);
    formData.append('message', message);
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    
    fetch('<?php echo url('/api/chat/messages/send'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta do envio:', data);
        if (data.success) {
            messageInput.value = '';
            
            // Adiciona a mensagem diretamente ao DOM
            if (data.message && data.message.id) {
                const container = document.getElementById('messages-list');
                appendMessage(data.message, container);
                
                // Atualiza lastMessageId
                if (data.message.id > lastMessageId) {
                    lastMessageId = data.message.id;
                    console.log('lastMessageId atualizado para:', lastMessageId);
                }
                
                // Scroll para o final
                setTimeout(() => {
                    const messagesContainer = document.getElementById('messages-container');
                    // Força recálculo do SimpleBar
                    if (typeof SimpleBar !== 'undefined') {
                        const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                        if (simpleBarInstance) {
                            simpleBarInstance.recalculate();
                        }
                    }
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 200);
            } else {
                console.warn('Mensagem não retornada na resposta:', data);
            }
        } else {
            alert('Erro ao enviar mensagem: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao enviar mensagem:', error);
        alert('Erro ao enviar mensagem');
    });
}

// Cria nova sala
function createRoom(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    
    fetch('<?php echo url('/api/chat/rooms/create'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fecha modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createRoomModal'));
            modal.hide();
            
            // Recarrega lista de salas
            location.reload();
        } else {
            alert('Erro ao criar sala: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao criar sala:', error);
        alert('Erro ao criar sala');
    });
}

// Busca usuários para adicionar à sala
function searchUsers(search) {
    if (!currentRoomId) return;
    
    const formData = new FormData();
    formData.append('room_id', currentRoomId);
    formData.append('search', search);
    
    fetch('<?php echo url('/api/chat/users/search'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('users-list');
        container.innerHTML = '';
        
        if (data.success && data.users.length > 0) {
            data.users.forEach(user => {
                let avatarUrl = '<?php echo asset('tema/assets/images/profile/user-1.jpg'); ?>';
                if (user.avatar) {
                    if (user.avatar.startsWith('http://') || user.avatar.startsWith('https://')) {
                        avatarUrl = user.avatar;
                    } else if (user.avatar.startsWith('/uploads/')) {
                        avatarUrl = '<?php echo asset(''); ?>' + user.avatar;
                    } else {
                        avatarUrl = '<?php echo asset('uploads/'); ?>' + user.avatar;
                    }
                }
                const userDiv = document.createElement('div');
                userDiv.className = 'd-flex align-items-center justify-content-between p-3 border-bottom';
                userDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="${avatarUrl}" alt="${user.name}" width="40" height="40" class="rounded-circle me-3" />
                        <div>
                            <h6 class="mb-0">${escapeHtml(user.name)}</h6>
                            <small class="text-muted">${escapeHtml(user.email)}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="addMemberToRoom(${user.id})">
                        <i class="ti ti-plus me-1"></i>Adicionar
                    </button>
                `;
                container.appendChild(userDiv);
            });
        } else {
            container.innerHTML = '<p class="text-muted text-center p-3">Nenhum usuário encontrado</p>';
        }
    })
    .catch(error => {
        console.error('Erro ao buscar usuários:', error);
    });
}

// Adiciona membro à sala
function addMemberToRoom(userId) {
    if (!currentRoomId) return;
    
    const formData = new FormData();
    formData.append('room_id', currentRoomId);
    formData.append('user_id', userId);
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    
    fetch('<?php echo url('/api/chat/rooms/add-member'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Membro adicionado com sucesso!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
            modal.hide();
        } else {
            alert('Erro ao adicionar membro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao adicionar membro:', error);
        alert('Erro ao adicionar membro');
    });
}

// Inicia polling de mensagens
function startMessagePolling(roomId) {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    messagePollingInterval = setInterval(() => {
        if (currentRoomId == roomId && lastMessageId > 0) {
            fetch(`<?php echo url('/api/chat/rooms'); ?>/${roomId}/messages?limit=50&since=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        const container = document.getElementById('messages-list');
                        data.messages.forEach(msg => {
                            // Verifica se a mensagem já não existe (evita duplicatas)
                            const existingMsg = container.querySelector(`[data-message-id="${msg.id}"]`);
                            if (!existingMsg && msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                                appendMessage(msg, container);
                            }
                        });
                        
                        // Scroll para o final se estiver próximo
                        setTimeout(() => {
                            const messagesContainer = document.getElementById('messages-container');
                            // Força recálculo do SimpleBar
                            if (typeof SimpleBar !== 'undefined') {
                                const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                                if (simpleBarInstance) {
                                    simpleBarInstance.recalculate();
                                }
                            }
                            const isNearBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop < messagesContainer.clientHeight + 100;
                            if (isNearBottom) {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        }, 200);
                    }
                })
                .catch(error => console.error('Erro no polling:', error));
        }
    }, 2000); // Polling a cada 2 segundos
}

// Para polling quando sair da sala
function stopMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }
}

// Função auxiliar para escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Ordena salas
function sortRooms(type) {
    const list = document.getElementById('rooms-list');
    const items = Array.from(list.querySelectorAll('li'));
    
    items.sort((a, b) => {
        if (type === 'name') {
            const nameA = a.querySelector('h6').textContent.trim();
            const nameB = b.querySelector('h6').textContent.trim();
            return nameA.localeCompare(nameB);
        }
        // Por tempo (já está ordenado no servidor)
        return 0;
    });
    
    items.forEach(item => list.appendChild(item));
}

// Busca salas
document.getElementById('search-rooms')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.chat-room-item').forEach(item => {
        const name = item.querySelector('h6').textContent.toLowerCase();
        item.closest('li').style.display = name.includes(search) ? '' : 'none';
    });
});

// Carrega salas via AJAX ao carregar a página
function loadRooms() {
    const roomsList = document.getElementById('rooms-list');
    const loadingEl = document.getElementById('rooms-loading');
    
    fetch('<?php echo url('/api/chat/rooms'); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roomsList.innerHTML = '';
                
                if (data.rooms && data.rooms.length > 0) {
                    data.rooms.forEach(room => {
                        const li = document.createElement('li');
                        const lastMessageText = room.last_message 
                            ? (room.last_message.length > 50 ? room.last_message.substring(0, 50) + '...' : room.last_message)
                            : 'Nenhuma mensagem ainda';
                        const lastMessageTime = room.last_message_at 
                            ? new Date(room.last_message_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
                            : '';
                        
                        li.innerHTML = `
                            <a href="javascript:void(0)" 
                               class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-room-item" 
                               data-room-id="${room.id}"
                               onclick="loadRoom(${room.id})">
                                <div class="d-flex align-items-center w-100">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                        <i class="ti ti-hash fs-5"></i>
                                    </div>
                                    <div class="d-inline-block w-75">
                                        <h6 class="mb-1 fw-semibold">${escapeHtml(room.name)}</h6>
                                        <span class="fs-3 text-truncate ${room.last_message ? 'text-body-color' : 'text-muted'} d-block">${escapeHtml(lastMessageText)}</span>
                                    </div>
                                </div>
                                ${lastMessageTime ? `<p class="fs-2 mb-0 text-muted">${lastMessageTime}</p>` : ''}
                            </a>
                        `;
                        roomsList.appendChild(li);
                    });
                } else {
                    roomsList.innerHTML = '<li class="px-4 py-3 text-center text-muted"><p class="mb-0">Nenhuma sala ainda. Crie uma nova sala!</p></li>';
                }
            } else {
                roomsList.innerHTML = '<li class="px-4 py-3 text-center text-danger"><p class="mb-0">Erro ao carregar salas</p></li>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar salas:', error);
            roomsList.innerHTML = '<li class="px-4 py-3 text-center text-danger"><p class="mb-0">Erro ao carregar salas</p></li>';
        });
}

// Scroll infinito para carregar mensagens antigas
function setupInfiniteScroll() {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) return;
    
    messagesContainer.addEventListener('scroll', function() {
        // Se estiver no topo e houver mais mensagens, carrega mais
        if (messagesContainer.scrollTop < 100 && hasMoreMessages && !isLoadingMessages && currentRoomId) {
            loadOlderMessages(currentRoomId);
        }
    });
}

// Carrega mensagens mais antigas (scroll infinito)
function loadOlderMessages(roomId) {
    if (isLoadingMessages || !hasMoreMessages || firstMessageId === 0) {
        return;
    }
    
    isLoadingMessages = true;
    const loadingEl = document.getElementById('loading-older-messages');
    if (loadingEl) {
        loadingEl.classList.remove('d-none');
    }
    
    const messagesContainer = document.getElementById('messages-container');
    const scrollHeightBefore = messagesContainer.scrollHeight;
    const scrollTopBefore = messagesContainer.scrollTop;
    
    // Busca mensagens anteriores à primeira mensagem atual
    fetch(`<?php echo url('/api/chat/rooms'); ?>/${roomId}/messages?limit=50&before=${firstMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                const container = document.getElementById('messages-list');
                
                // Adiciona mensagens no início (prepend)
                data.messages.forEach(msg => {
                    if (msg.id < firstMessageId) {
                        firstMessageId = msg.id;
                    }
                    appendMessage(msg, container, true); // prepend = true
                });
                
                // Verifica se há mais mensagens
                if (data.messages.length < 50) {
                    hasMoreMessages = false;
                }
                
                // Mantém posição de scroll
                setTimeout(() => {
                    const scrollHeightAfter = messagesContainer.scrollHeight;
                    messagesContainer.scrollTop = scrollTopBefore + (scrollHeightAfter - scrollHeightBefore);
                    
                    if (typeof SimpleBar !== 'undefined') {
                        try {
                            const simpleBarInstance = SimpleBar.instances.get(messagesContainer);
                            if (simpleBarInstance) {
                                simpleBarInstance.recalculate();
                            }
                        } catch (e) {
                            console.warn('Erro ao recalcular SimpleBar:', e);
                        }
                    }
                }, 100);
            } else {
                hasMoreMessages = false;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar mensagens antigas:', error);
        })
        .finally(() => {
            isLoadingMessages = false;
            if (loadingEl) {
                loadingEl.classList.add('d-none');
            }
        });
}

// Inicializa SimpleBar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Carrega salas via AJAX
    loadRooms();
    
    // Configura scroll infinito
    setupInfiniteScroll();
    
    // Aguarda um pouco para garantir que o SimpleBar foi inicializado pelo tema
    setTimeout(() => {
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            // Se o SimpleBar não foi inicializado, inicializa manualmente
            if (typeof SimpleBar !== 'undefined' && !SimpleBar.instances.get(messagesContainer)) {
                new SimpleBar(messagesContainer, {
                    autoHide: false,
                    forceVisible: true
                });
            }
        }
    }, 1000);
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

