<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\ChatRoomMember;
use App\Models\User;

class ChatController extends \Core\Controller
{
    /**
     * Página principal do chat
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $user = User::find($userId);
        
        // Cria sala "Geral" se não existir
        $db = \Core\Database::getInstance();
        $generalRoom = $db->queryOne(
            "SELECT id FROM chat_rooms WHERE name = 'Geral' AND type = 'public' LIMIT 1"
        );
        
        if (!$generalRoom) {
            $db->execute(
                "INSERT INTO chat_rooms (name, description, type, created_by, created_at, updated_at) 
                 VALUES ('Geral', 'Sala geral para conversas do time', 'public', ?, NOW(), NOW())",
                [$userId]
            );
            $generalRoomId = (int)$db->lastInsertId();
            
            if ($generalRoomId) {
                // Adiciona todos os usuários à sala Geral
                $allUsers = $db->query("SELECT id FROM users");
                foreach ($allUsers as $u) {
                    $db->execute(
                        "INSERT IGNORE INTO chat_room_members (chat_room_id, user_id, created_at, updated_at) 
                         VALUES (?, ?, NOW(), NOW())",
                        [$generalRoomId, $u['id']]
                    );
                }
            }
        } else {
            // Garante que o usuário atual está na sala Geral
            $db->execute(
                "INSERT IGNORE INTO chat_room_members (chat_room_id, user_id, created_at, updated_at) 
                 VALUES (?, ?, NOW(), NOW())",
                [$generalRoom['id'], $userId]
            );
        }
        
        // Não carrega salas aqui - serão carregadas via AJAX para melhor performance
        // Apenas retorna a view com dados básicos do usuário
        return $this->view('chat/index', [
            'title' => 'Chat',
            'user' => $user,
            'rooms' => [] // Salas serão carregadas via AJAX
        ]);
    }
    
    /**
     * API: Busca salas do usuário
     */
    public function getRooms(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $db = \Core\Database::getInstance();
            
            // Query otimizada - inclui salas públicas e conversas privadas
            $rooms = $db->query(
                "SELECT cr.*,
                        COUNT(DISTINCT cm.id) as messages_count,
                        MAX(cm.created_at) as last_message_at,
                        (SELECT cm2.message 
                         FROM chat_messages cm2 
                         WHERE cm2.chat_room_id = cr.id 
                         ORDER BY cm2.created_at DESC 
                         LIMIT 1) as last_message,
                        CASE 
                            WHEN cr.is_private = 1 AND cr.participant1_id = ? THEN cr.participant2_id
                            WHEN cr.is_private = 1 AND cr.participant2_id = ? THEN cr.participant1_id
                            ELSE NULL
                        END as other_participant_id
                 FROM chat_rooms cr
                 LEFT JOIN chat_room_members crm ON crm.chat_room_id = cr.id AND crm.user_id = ?
                 LEFT JOIN chat_messages cm ON cm.chat_room_id = cr.id
                 WHERE (crm.user_id = ? OR (cr.is_private = 1 AND (cr.participant1_id = ? OR cr.participant2_id = ?)))
                 GROUP BY cr.id
                 ORDER BY COALESCE(MAX(cm.created_at), cr.created_at) DESC, cr.created_at DESC
                 LIMIT 50",
                [$userId, $userId, $userId, $userId, $userId, $userId]
            );
            
            $roomsData = array_map(function($row) use ($userId, $db) {
                $roomData = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'is_private' => (bool)($row['is_private'] ?? 0),
                    'messages_count' => (int)($row['messages_count'] ?? 0),
                    'last_message' => $row['last_message'] ?? null,
                    'last_message_at' => $row['last_message_at'] ?? null
                ];
                
                // Se for conversa privada, busca dados do outro participante
                if ($roomData['is_private'] && !empty($row['other_participant_id'])) {
                    $otherUser = $db->queryOne(
                        "SELECT id, name, email, avatar FROM users WHERE id = ?",
                        [$row['other_participant_id']]
                    );
                    if ($otherUser) {
                        $roomData['other_participant'] = [
                            'id' => (int)$otherUser['id'],
                            'name' => $otherUser['name'],
                            'email' => $otherUser['email'],
                            'avatar' => $otherUser['avatar']
                        ];
                        // Nome da sala privada é o nome do outro participante
                        $roomData['name'] = $otherUser['name'];
                    }
                }
                
                return $roomData;
            }, $rooms);
            
            json_response([
                'success' => true,
                'rooms' => $roomsData
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao buscar salas: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao buscar salas'], 500);
        }
    }
    
    /**
     * API: Busca mensagens de uma sala
     */
    public function getMessages(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $roomId = (int)($params['room_id'] ?? 0);
            $limit = (int)($this->request->input('limit', 50));
            $offset = (int)($this->request->input('offset', 0));
            $since = (int)($this->request->input('since', 0));
            $before = (int)($this->request->input('before', 0));
            
            if (!$roomId) {
                json_response(['success' => false, 'message' => 'ID da sala não informado'], 400);
                return;
            }
            
            $room = ChatRoom::find($roomId);
            if (!$room) {
                json_response(['success' => false, 'message' => 'Sala não encontrada'], 404);
                return;
            }
            
            // Verifica se usuário é membro
            if (!$room->hasMember($userId)) {
                json_response(['success' => false, 'message' => 'Você não é membro desta sala'], 403);
                return;
            }
            
            $db = \Core\Database::getInstance();
            
            // Se since > 0, busca apenas mensagens mais recentes (para polling)
            if ($since > 0) {
                $messagesRows = $db->query(
                    "SELECT cm.*, u.name, u.email, u.avatar 
                     FROM chat_messages cm 
                     INNER JOIN users u ON u.id = cm.user_id 
                     WHERE cm.chat_room_id = ? AND cm.id > ?
                     ORDER BY cm.created_at ASC 
                     LIMIT ?",
                    [$roomId, $since, $limit]
                );
                
                $messages = array_map(function($row) {
                    return [
                        'id' => (int)$row['id'],
                        'message' => $row['message'],
                        'attachment_url' => $row['attachment_url'],
                        'created_at' => $row['created_at'],
                        'user' => [
                            'id' => (int)$row['user_id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'avatar' => $row['avatar']
                        ]
                    ];
                }, $messagesRows);
            } 
            // Se before > 0, busca mensagens mais antigas (para scroll infinito)
            else if ($before > 0) {
                $messagesRows = $db->query(
                    "SELECT cm.*, u.name, u.email, u.avatar 
                     FROM chat_messages cm 
                     INNER JOIN users u ON u.id = cm.user_id 
                     WHERE cm.chat_room_id = ? AND cm.id < ?
                     ORDER BY cm.created_at DESC 
                     LIMIT ?",
                    [$roomId, $before, $limit]
                );
                
                $messages = array_map(function($row) {
                    $message = [
                        'id' => (int)$row['id'],
                        'message' => $row['message'],
                        'attachment_url' => $row['attachment_url'],
                        'created_at' => $row['created_at'],
                        'user' => [
                            'id' => (int)$row['user_id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'avatar' => $row['avatar']
                        ]
                    ];
                    
                    // Adiciona dados do anexo se existir
                    if (!empty($row['attachment_s3_key'])) {
                        $message['attachment'] = [
                            's3_key' => $row['attachment_s3_key'],
                            'name' => $row['attachment_name'] ?? null,
                            'size' => isset($row['attachment_size']) ? (int)$row['attachment_size'] : null,
                            'mime_type' => $row['attachment_mime_type'] ?? null
                        ];
                    }
                    
                    return $message;
                }, $messagesRows);
                
                // Inverte ordem para mostrar do mais antigo ao mais recente
                $messages = array_reverse($messages);
            } 
            // Carregamento inicial: carrega apenas as últimas mensagens
            else {
                $messagesRows = $db->query(
                    "SELECT cm.*, u.name, u.email, u.avatar 
                     FROM chat_messages cm 
                     INNER JOIN users u ON u.id = cm.user_id 
                     WHERE cm.chat_room_id = ?
                     ORDER BY cm.created_at DESC 
                     LIMIT ? OFFSET ?",
                    [$roomId, $limit, $offset]
                );
                
                $messages = array_map(function($row) {
                    $message = [
                        'id' => (int)$row['id'],
                        'message' => $row['message'],
                        'attachment_url' => $row['attachment_url'],
                        'created_at' => $row['created_at'],
                        'user' => [
                            'id' => (int)$row['user_id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'avatar' => $row['avatar']
                        ]
                    ];
                    
                    // Adiciona dados do anexo se existir
                    if (!empty($row['attachment_s3_key'])) {
                        $message['attachment'] = [
                            's3_key' => $row['attachment_s3_key'],
                            'name' => $row['attachment_name'] ?? null,
                            'size' => isset($row['attachment_size']) ? (int)$row['attachment_size'] : null,
                            'mime_type' => $row['attachment_mime_type'] ?? null
                        ];
                    }
                    
                    return $message;
                }, $messagesRows);
                
                // Inverte ordem para mostrar do mais antigo ao mais recente
                $messages = array_reverse($messages);
            }
            
            json_response([
                'success' => true,
                'messages' => $messages,
                'room' => [
                    'id' => (int)$room->id,
                    'name' => $room->name,
                    'description' => $room->description
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao buscar mensagens: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao buscar mensagens'], 500);
        }
    }
    
    /**
     * API: Envia mensagem
     */
    public function sendMessage(): void
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
        
        try {
            $userId = auth()->getDataUserId();
            $roomId = (int)($this->request->input('room_id', 0));
            $message = trim($this->request->input('message', ''));
            
            if (!$roomId) {
                json_response(['success' => false, 'message' => 'ID da sala não informado'], 400);
                return;
            }
            
            // Mensagem ou anexo deve estar presente
            $hasAttachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
            
            if (empty($message) && !$hasAttachment) {
                json_response(['success' => false, 'message' => 'Mensagem ou anexo é obrigatório'], 400);
                return;
            }
            
            $room = ChatRoom::find($roomId);
            if (!$room) {
                json_response(['success' => false, 'message' => 'Sala não encontrada'], 404);
                return;
            }
            
            // Verifica se usuário é membro
            if (!$room->hasMember($userId)) {
                json_response(['success' => false, 'message' => 'Você não é membro desta sala'], 403);
                return;
            }
            
            // Processa anexo se houver
            $attachmentS3Key = null;
            $attachmentName = null;
            $attachmentSize = null;
            $attachmentMimeType = null;
            
            if ($hasAttachment) {
                $file = $_FILES['attachment'];
                $tmpFile = $file['tmp_name'];
                $originalName = $file['name'];
                $size = $file['size'];
                $mimeType = $file['type'];
                
                // Validação de tamanho (50MB padrão)
                $maxSize = 50 * 1024 * 1024;
                if ($size > $maxSize) {
                    json_response(['success' => false, 'message' => 'Arquivo muito grande. Máximo: 50MB'], 400);
                    return;
                }
                
                // Upload para S3 privado
                $s3Key = s3_upload_private($tmpFile, $userId, 'chat', 50, $originalName);
                
                if (!$s3Key) {
                    $s3 = s3_private();
                    $errorMsg = $s3->getLastError() ?: 'Erro ao fazer upload para S3';
                    json_response(['success' => false, 'message' => $errorMsg], 500);
                    return;
                }
                
                $attachmentS3Key = $s3Key;
                $attachmentName = $originalName;
                $attachmentSize = $size;
                $attachmentMimeType = $mimeType;
            }
            
            $chatMessage = ChatMessage::create([
                'chat_room_id' => $roomId,
                'user_id' => $userId,
                'message' => $message ?: '',
                'attachment_url' => null,
                'attachment_s3_key' => $attachmentS3Key,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
                'attachment_mime_type' => $attachmentMimeType
            ]);
            
            // Busca dados completos da mensagem do banco (incluindo anexo)
            $db = \Core\Database::getInstance();
            $messageRow = $db->queryOne(
                "SELECT cm.*, u.name, u.email, u.avatar 
                 FROM chat_messages cm 
                 INNER JOIN users u ON u.id = cm.user_id 
                 WHERE cm.id = ?",
                [$chatMessage->id]
            );
            
            $messageData = [
                'id' => (int)$messageRow['id'],
                'message' => $messageRow['message'] ?: '',
                'created_at' => $messageRow['created_at'],
                'user' => [
                    'id' => (int)$messageRow['user_id'],
                    'name' => $messageRow['name'],
                    'email' => $messageRow['email'],
                    'avatar' => $messageRow['avatar']
                ]
            ];
            
            // Adiciona dados do anexo se existir
            if (!empty($messageRow['attachment_s3_key'])) {
                $messageData['attachment'] = [
                    's3_key' => $messageRow['attachment_s3_key'],
                    'name' => $messageRow['attachment_name'] ?? null,
                    'size' => isset($messageRow['attachment_size']) ? (int)$messageRow['attachment_size'] : null,
                    'mime_type' => $messageRow['attachment_mime_type'] ?? null
                ];
            }
            
            json_response([
                'success' => true,
                'message' => $messageData
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao enviar mensagem'], 500);
        }
    }
    
    /**
     * API: Cria nova sala
     */
    public function createRoom(): void
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
        
        try {
            $userId = auth()->getDataUserId();
            $name = trim($this->request->input('name', ''));
            $description = trim($this->request->input('description', ''));
            $type = $this->request->input('type', 'public');
            
            error_log("=== Criar Sala ===");
            error_log("User ID: {$userId}");
            error_log("Name: {$name}");
            error_log("Description: " . ($description ?: 'NULL'));
            error_log("Type: {$type}");
            
            if (empty($name)) {
                json_response(['success' => false, 'message' => 'Nome da sala é obrigatório'], 400);
                return;
            }
            
            if (!in_array($type, ['public', 'private'])) {
                $type = 'public';
            }
            
            error_log("Tentando criar ChatRoom...");
            
            // Cria sala usando SQL direto para evitar problemas com multiTenant
            $db = \Core\Database::getInstance();
            $db->execute(
                "INSERT INTO chat_rooms (name, description, type, created_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())",
                [$name, $description ?: null, $type, $userId]
            );
            $roomId = (int)$db->lastInsertId();
            
            error_log("Sala criada com ID: {$roomId}");
            
            if (!$roomId) {
                throw new \Exception("Falha ao criar sala: ID não retornado");
            }
            
            // Busca a sala criada
            $room = ChatRoom::find($roomId);
            if (!$room) {
                throw new \Exception("Sala criada mas não encontrada após criação");
            }
            
            // Adiciona criador como membro
            error_log("Adicionando criador como membro...");
            $memberAdded = $room->addMember($userId);
            error_log("Membro adicionado: " . ($memberAdded ? 'SIM' : 'NÃO'));
            
            if (!$memberAdded) {
                error_log("AVISO: Falha ao adicionar criador como membro, mas continuando...");
            }
            
            json_response([
                'success' => true,
                'room' => [
                    'id' => (int)$room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'type' => $room->type
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao criar sala: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            json_response([
                'success' => false, 
                'message' => 'Erro ao criar sala: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API: Adiciona membro à sala
     */
    public function addMember(): void
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
        
        try {
            $userId = auth()->getDataUserId();
            $roomId = (int)($this->request->input('room_id', 0));
            $memberUserId = (int)($this->request->input('user_id', 0));
            
            if (!$roomId || !$memberUserId) {
                json_response(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            $room = ChatRoom::find($roomId);
            if (!$room) {
                json_response(['success' => false, 'message' => 'Sala não encontrada'], 404);
                return;
            }
            
            // Verifica se usuário é membro (ou criador)
            if (!$room->hasMember($userId) && $room->created_by != $userId) {
                json_response(['success' => false, 'message' => 'Você não tem permissão para adicionar membros'], 403);
                return;
            }
            
            if ($room->addMember($memberUserId)) {
                json_response(['success' => true, 'message' => 'Membro adicionado com sucesso']);
            } else {
                json_response(['success' => false, 'message' => 'Erro ao adicionar membro'], 500);
            }
        } catch (\Throwable $e) {
            error_log("Erro ao adicionar membro: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao adicionar membro'], 500);
        }
    }
    
    /**
     * API: Busca usuários para adicionar à sala
     */
    public function searchUsers(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $roomId = (int)($this->request->input('room_id', 0));
            $search = trim($this->request->input('search', ''));
            $forPrivateChat = (bool)($this->request->input('for_private_chat', false));
            
            $db = \Core\Database::getInstance();
            
            // Se for para conversa privada, busca todos os usuários (exceto o próprio)
            if ($forPrivateChat) {
                $where = "u.id != ?";
                $params = [$userId];
                
                if (!empty($search)) {
                    $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $users = $db->query(
                    "SELECT u.id, u.name, u.email, u.avatar 
                     FROM users u 
                     WHERE {$where} 
                     ORDER BY u.name ASC 
                     LIMIT 20",
                    $params
                );
            } else {
                // Busca usuários que não são membros da sala
                if (!$roomId) {
                    json_response(['success' => false, 'message' => 'ID da sala não informado'], 400);
                    return;
                }
                
                $room = ChatRoom::find($roomId);
                if (!$room) {
                    json_response(['success' => false, 'message' => 'Sala não encontrada'], 404);
                    return;
                }
                
                $where = "u.id != ? AND u.id NOT IN (SELECT user_id FROM chat_room_members WHERE chat_room_id = ?)";
                $params = [$userId, $roomId];
                
                if (!empty($search)) {
                    $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $users = $db->query(
                    "SELECT u.id, u.name, u.email, u.avatar 
                     FROM users u 
                     WHERE {$where} 
                     ORDER BY u.name ASC 
                     LIMIT 20",
                    $params
                );
            }
            
            json_response([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao buscar usuários: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao buscar usuários'], 500);
        }
    }
    
    /**
     * API: Download de anexo
     */
    public function downloadAttachment(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $messageId = (int)($params['message_id'] ?? 0);
            
            if (!$messageId) {
                json_response(['success' => false, 'message' => 'ID da mensagem não informado'], 400);
                return;
            }
            
            $message = ChatMessage::find($messageId);
            if (!$message || !$message->attachment_s3_key) {
                json_response(['success' => false, 'message' => 'Anexo não encontrado'], 404);
                return;
            }
            
            // Verifica se usuário é membro da sala
            $room = ChatRoom::find($message->chat_room_id);
            if (!$room || !$room->hasMember($userId)) {
                json_response(['success' => false, 'message' => 'Acesso negado'], 403);
                return;
            }
            
            // Faz download do arquivo
            $fileName = $message->attachment_name ?: 'anexo';
            s3_private()->downloadFile($message->attachment_s3_key, $fileName);
        } catch (\Throwable $e) {
            error_log("Erro ao fazer download do anexo: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao fazer download'], 500);
        }
    }
    
    /**
     * API: Lista mídia da sala
     */
    public function getMedia(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $roomId = (int)($params['room_id'] ?? 0);
            $limit = (int)($this->request->input('limit', 50));
            $offset = (int)($this->request->input('offset', 0));
            
            if (!$roomId) {
                json_response(['success' => false, 'message' => 'ID da sala não informado'], 400);
                return;
            }
            
            $room = ChatRoom::find($roomId);
            if (!$room) {
                json_response(['success' => false, 'message' => 'Sala não encontrada'], 404);
                return;
            }
            
            // Verifica se usuário é membro
            if (!$room->hasMember($userId)) {
                json_response(['success' => false, 'message' => 'Você não é membro desta sala'], 403);
                return;
            }
            
            // Busca mensagens com anexos
            $db = \Core\Database::getInstance();
            $messagesRows = $db->query(
                "SELECT cm.id, cm.attachment_s3_key, cm.attachment_name, cm.attachment_size, 
                        cm.attachment_mime_type, cm.created_at,
                        u.id as user_id, u.name as user_name, u.avatar
                 FROM chat_messages cm 
                 INNER JOIN users u ON u.id = cm.user_id 
                 WHERE cm.chat_room_id = ? AND cm.attachment_s3_key IS NOT NULL
                 ORDER BY cm.created_at DESC 
                 LIMIT ? OFFSET ?",
                [$roomId, $limit, $offset]
            );
            
            $media = array_map(function($row) {
                return [
                    'id' => (int)$row['id'],
                    's3_key' => $row['attachment_s3_key'],
                    'name' => $row['attachment_name'],
                    'size' => (int)$row['attachment_size'],
                    'mime_type' => $row['attachment_mime_type'],
                    'created_at' => $row['created_at'],
                    'user' => [
                        'id' => (int)$row['user_id'],
                        'name' => $row['user_name'],
                        'avatar' => $row['avatar']
                    ]
                ];
            }, $messagesRows);
            
            json_response([
                'success' => true,
                'media' => $media,
                'total' => count($media)
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao buscar mídia: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao buscar mídia'], 500);
        }
    }
    
    /**
     * API: Inicia ou retorna conversa privada com um usuário
     */
    public function startPrivateChat(): void
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
        
        try {
            $userId = auth()->getDataUserId();
            $otherUserId = (int)($this->request->input('user_id', 0));
            
            if (!$otherUserId || $otherUserId == $userId) {
                json_response(['success' => false, 'message' => 'ID do usuário inválido'], 400);
                return;
            }
            
            // Verifica se o outro usuário existe
            $otherUser = User::find($otherUserId);
            if (!$otherUser) {
                json_response(['success' => false, 'message' => 'Usuário não encontrado'], 404);
                return;
            }
            
            $db = \Core\Database::getInstance();
            
            // Busca se já existe conversa privada entre os dois usuários
            $existingRoom = $db->queryOne(
                "SELECT id FROM chat_rooms 
                 WHERE is_private = 1 
                 AND ((participant1_id = ? AND participant2_id = ?) OR (participant1_id = ? AND participant2_id = ?))",
                [$userId, $otherUserId, $otherUserId, $userId]
            );
            
            if ($existingRoom) {
                // Retorna sala existente
                $room = ChatRoom::find($existingRoom['id']);
                json_response([
                    'success' => true,
                    'room' => [
                        'id' => (int)$room->id,
                        'name' => $otherUser->name,
                        'description' => null,
                        'type' => 'private',
                        'is_private' => true,
                        'other_participant' => [
                            'id' => (int)$otherUser->id,
                            'name' => $otherUser->name,
                            'email' => $otherUser->email,
                            'avatar' => $otherUser->avatar
                        ]
                    ]
                ]);
                return;
            }
            
            // Cria nova sala privada
            $db->execute(
                "INSERT INTO chat_rooms (name, description, type, is_private, participant1_id, participant2_id, created_by, created_at, updated_at) 
                 VALUES (?, ?, 'private', 1, ?, ?, ?, NOW(), NOW())",
                [$otherUser->name, null, $userId, $otherUserId, $userId]
            );
            $newRoomId = (int)$db->lastInsertId();
            
            $room = ChatRoom::find($newRoomId);
            
            // Adiciona ambos os usuários como membros
            $room->addMember($userId);
            $room->addMember($otherUserId);
            
            json_response([
                'success' => true,
                'room' => [
                    'id' => (int)$room->id,
                    'name' => $otherUser->name,
                    'description' => null,
                    'type' => 'private',
                    'is_private' => true,
                    'other_participant' => [
                        'id' => (int)$otherUser->id,
                        'name' => $otherUser->name,
                        'email' => $otherUser->email,
                        'avatar' => $otherUser->avatar
                    ]
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao iniciar conversa privada: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            json_response([
                'success' => false, 
                'message' => 'Erro ao iniciar conversa privada: ' . $e->getMessage()
            ], 500);
        }
    }
}

