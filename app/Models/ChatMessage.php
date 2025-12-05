<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ChatMessage extends Model
{
    protected string $table = 'chat_messages';
    protected bool $multiTenant = false; // Mensagens são compartilhadas entre usuários
    
    protected array $fillable = [
        'chat_room_id',
        'user_id',
        'message',
        'attachment_url'
    ];
    
    /**
     * Relacionamento com sala
     */
    public function room()
    {
        return ChatRoom::find($this->chat_room_id);
    }
    
    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return User::find($this->user_id);
    }
}

