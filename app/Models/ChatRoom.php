<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ChatRoom extends Model
{
    protected string $table = 'chat_rooms';
    protected bool $multiTenant = false; // Salas são compartilhadas entre usuários
    
    protected array $fillable = [
        'name',
        'description',
        'type',
        'is_private',
        'participant1_id',
        'participant2_id',
        'created_by'
    ];
    
    /**
     * Relacionamento com usuário criador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Relacionamento com membros da sala
     */
    public function members()
    {
        $db = \Core\Database::getInstance();
        $members = $db->query(
            "SELECT u.*, crm.created_at as joined_at 
             FROM chat_room_members crm 
             INNER JOIN users u ON u.id = crm.user_id 
             WHERE crm.chat_room_id = ? 
             ORDER BY crm.created_at ASC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return User::newInstance($row, true);
        }, $members);
    }
    
    /**
     * Relacionamento com mensagens
     */
    public function messages($limit = 50, $offset = 0)
    {
        $db = \Core\Database::getInstance();
        $messages = $db->query(
            "SELECT cm.*, u.name, u.email, u.avatar 
             FROM chat_messages cm 
             INNER JOIN users u ON u.id = cm.user_id 
             WHERE cm.chat_room_id = ? 
             ORDER BY cm.created_at DESC 
             LIMIT ? OFFSET ?",
            [$this->id, $limit, $offset]
        );
        
        return array_map(function($row) {
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
        }, $messages);
    }
    
    /**
     * Verifica se usuário é membro da sala
     */
    public function hasMember(int $userId): bool
    {
        $db = \Core\Database::getInstance();
        $member = $db->queryOne(
            "SELECT id FROM chat_room_members WHERE chat_room_id = ? AND user_id = ?",
            [$this->id, $userId]
        );
        
        return !empty($member);
    }
    
    /**
     * Adiciona membro à sala
     */
    public function addMember(int $userId): bool
    {
        if ($this->hasMember($userId)) {
            return true; // Já é membro
        }
        
        try {
            $db = \Core\Database::getInstance();
            $db->execute(
                "INSERT INTO chat_room_members (chat_room_id, user_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$this->id, $userId]
            );
            // Verifica se a inserção foi bem-sucedida verificando se o membro existe agora
            return $this->hasMember($userId);
        } catch (\Throwable $e) {
            error_log("Erro ao adicionar membro à sala: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Remove membro da sala
     */
    public function removeMember(int $userId): bool
    {
        $db = \Core\Database::getInstance();
        return $db->execute(
            "DELETE FROM chat_room_members WHERE chat_room_id = ? AND user_id = ?",
            [$this->id, $userId]
        );
    }
    
    /**
     * Conta total de mensagens
     */
    public function messagesCount(): int
    {
        $db = \Core\Database::getInstance();
        $result = $db->queryOne(
            "SELECT COUNT(*) as total FROM chat_messages WHERE chat_room_id = ?",
            [$this->id]
        );
        
        return (int)($result['total'] ?? 0);
    }
}

