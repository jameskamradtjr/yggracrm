<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ChatRoomMember extends Model
{
    protected string $table = 'chat_room_members';
    protected bool $multiTenant = false; // Membros são compartilhados entre usuários
    
    protected array $fillable = [
        'chat_room_id',
        'user_id'
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

