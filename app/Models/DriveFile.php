<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class DriveFile extends Model
{
    protected string $table = 'drive_files';
    
    protected array $fillable = [
        'user_id',
        'folder_id',
        'name',
        's3_key',
        'mime_type',
        'size',
        'extension',
        'client_id',
        'lead_id',
        'project_id',
        'responsible_user_id',
        'description',
        'expiration_date',
        'is_favorite',
        'is_shared',
        'version',
        'previous_version_id'
    ];
    
    protected bool $multiTenant = true;
    protected bool $useSoftDeletes = true;
    
    /**
     * Retorna a pasta do arquivo
     */
    public function folder(): ?DriveFolder
    {
        if (!$this->folder_id) {
            return null;
        }
        
        return DriveFolder::find($this->folder_id);
    }
    
    /**
     * Retorna o cliente relacionado
     */
    public function client(): ?Client
    {
        if (!$this->client_id) {
            return null;
        }
        
        return Client::find($this->client_id);
    }
    
    /**
     * Retorna o lead relacionado
     */
    public function lead(): ?Lead
    {
        if (!$this->lead_id) {
            return null;
        }
        
        return Lead::find($this->lead_id);
    }
    
    /**
     * Retorna o projeto relacionado
     */
    public function project(): ?Project
    {
        if (!$this->project_id) {
            return null;
        }
        
        return Project::find($this->project_id);
    }
    
    /**
     * Retorna o usuário responsável
     */
    public function responsible(): ?User
    {
        if (!$this->responsible_user_id) {
            return null;
        }
        
        return User::find($this->responsible_user_id);
    }
    
    /**
     * Retorna as tags do arquivo
     */
    public function tags(): array
    {
        $db = \Core\Database::getInstance();
        
        $tags = $db->query(
            "SELECT t.* FROM tags t
             INNER JOIN taggables tg ON t.id = tg.tag_id
             WHERE tg.taggable_type = 'DriveFile'
             AND tg.taggable_id = ?
             ORDER BY t.name ASC",
            [$this->id]
        );
        
        return $tags ?? [];
    }
    
    /**
     * Adiciona uma tag ao arquivo
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $exists = $db->queryOne(
            "SELECT id FROM taggables 
             WHERE taggable_type = 'DriveFile' 
             AND taggable_id = ? 
             AND tag_id = ?",
            [$this->id, $tagId]
        );
        
        if ($exists) {
            return true;
        }
        
        return $db->execute(
            "INSERT INTO taggables (taggable_type, taggable_id, tag_id, created_at) 
             VALUES ('DriveFile', ?, ?, NOW())",
            [$this->id, $tagId]
        );
    }
    
    /**
     * Remove uma tag do arquivo
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        return $db->execute(
            "DELETE FROM taggables 
             WHERE taggable_type = 'DriveFile' 
             AND taggable_id = ? 
             AND tag_id = ?",
            [$this->id, $tagId]
        );
    }
    
    /**
     * Remove todas as tags do arquivo
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        
        return $db->execute(
            "DELETE FROM taggables 
             WHERE taggable_type = 'DriveFile' 
             AND taggable_id = ?",
            [$this->id]
        );
    }
    
    /**
     * Retorna os compartilhamentos do arquivo
     */
    public function shares(): array
    {
        $db = \Core\Database::getInstance();
        
        return $db->query(
            "SELECT dfs.*, u.name as user_name, u.email as user_email
             FROM drive_file_shares dfs
             INNER JOIN users u ON dfs.shared_with_user_id = u.id
             WHERE dfs.file_id = ?
             ORDER BY dfs.created_at DESC",
            [$this->id]
        ) ?? [];
    }
    
    /**
     * Compartilha arquivo com um usuário
     */
    public function shareWith(int $userId, string $permission = 'view', ?string $expiresAt = null): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já está compartilhado
        $exists = $db->queryOne(
            "SELECT id FROM drive_file_shares 
             WHERE file_id = ? AND shared_with_user_id = ?",
            [$this->id, $userId]
        );
        
        if ($exists) {
            // Atualiza permissão
            return $db->execute(
                "UPDATE drive_file_shares 
                 SET permission = ?, expires_at = ?, updated_at = NOW()
                 WHERE file_id = ? AND shared_with_user_id = ?",
                [$permission, $expiresAt, $this->id, $userId]
            );
        }
        
        // Cria novo compartilhamento
        $result = $db->execute(
            "INSERT INTO drive_file_shares (file_id, shared_with_user_id, permission, expires_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$this->id, $userId, $permission, $expiresAt]
        );
        
        if ($result) {
            $this->update(['is_shared' => true]);
        }
        
        return $result;
    }
    
    /**
     * Remove compartilhamento com um usuário
     */
    public function unshareWith(int $userId): bool
    {
        $db = \Core\Database::getInstance();
        
        $result = $db->execute(
            "DELETE FROM drive_file_shares 
             WHERE file_id = ? AND shared_with_user_id = ?",
            [$this->id, $userId]
        );
        
        // Verifica se ainda há compartilhamentos
        $hasShares = !empty($this->shares());
        if (!$hasShares) {
            $this->update(['is_shared' => false]);
        }
        
        return $result;
    }
    
    /**
     * Gera URL de download (assinada)
     */
    public function getDownloadUrl(int $expirationMinutes = 15): string|false
    {
        return s3_get_signed_url($this->s3_key, $expirationMinutes);
    }
    
    /**
     * Formata o tamanho do arquivo
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size ?? 0;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Retorna ícone baseado no tipo de arquivo
     */
    public function getIcon(): string
    {
        $extension = strtolower($this->extension ?? '');
        
        $icons = [
            // Documentos
            'pdf' => 'ti-file-type-pdf',
            'doc' => 'ti-file-type-doc',
            'docx' => 'ti-file-type-docx',
            'xls' => 'ti-file-type-xls',
            'xlsx' => 'ti-file-type-xlsx',
            'ppt' => 'ti-file-type-ppt',
            'pptx' => 'ti-file-type-pptx',
            'txt' => 'ti-file-text',
            
            // Imagens
            'jpg' => 'ti-photo',
            'jpeg' => 'ti-photo',
            'png' => 'ti-photo',
            'gif' => 'ti-photo',
            'svg' => 'ti-photo',
            'webp' => 'ti-photo',
            
            // Vídeos
            'mp4' => 'ti-video',
            'avi' => 'ti-video',
            'mov' => 'ti-video',
            'wmv' => 'ti-video',
            
            // Áudio
            'mp3' => 'ti-music',
            'wav' => 'ti-music',
            'ogg' => 'ti-music',
            
            // Compactados
            'zip' => 'ti-file-zip',
            'rar' => 'ti-file-zip',
            '7z' => 'ti-file-zip',
            'tar' => 'ti-file-zip',
            'gz' => 'ti-file-zip',
        ];
        
        return $icons[$extension] ?? 'ti-file';
    }
    
    /**
     * Verifica se o arquivo está vencido
     */
    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }
        
        return strtotime($this->expiration_date) < time();
    }
    
    /**
     * Retorna a versão anterior do arquivo
     */
    public function previousVersion(): ?self
    {
        if (!$this->previous_version_id) {
            return null;
        }
        
        return self::find($this->previous_version_id);
    }
    
    /**
     * Retorna todas as versões do arquivo
     */
    public function versions(): array
    {
        $versions = [$this];
        $current = $this;
        
        while ($current->previous_version_id) {
            $previous = $current->previousVersion();
            if ($previous) {
                $versions[] = $previous;
                $current = $previous;
            } else {
                break;
            }
        }
        
        return array_reverse($versions);
    }
}

