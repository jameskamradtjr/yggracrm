<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class DriveFolder extends Model
{
    protected string $table = 'drive_folders';
    
    protected array $fillable = [
        'user_id',
        'parent_id',
        'name',
        'color',
        'description'
    ];
    
    protected bool $multiTenant = true;
    
    /**
     * Retorna a pasta pai
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        
        return self::find($this->parent_id);
    }
    
    /**
     * Retorna as subpastas
     */
    public function subfolders(): array
    {
        return self::where('parent_id', $this->id)
            ->where('user_id', $this->user_id)
            ->orderBy('name', 'ASC')
            ->get();
    }
    
    /**
     * Retorna os arquivos da pasta
     */
    public function files(): array
    {
        return DriveFile::where('folder_id', $this->id)
            ->where('user_id', $this->user_id)
            ->whereNull('deleted_at')
            ->orderBy('name', 'ASC')
            ->get();
    }
    
    /**
     * Retorna o caminho completo da pasta (breadcrumb)
     */
    public function getPath(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name
            ]);
            $current = $current->parent();
        }
        
        return $path;
    }
    
    /**
     * Verifica se a pasta está vazia
     */
    public function isEmpty(): bool
    {
        $hasFiles = !empty($this->files());
        $hasSubfolders = !empty($this->subfolders());
        
        return !$hasFiles && !$hasSubfolders;
    }
    
    /**
     * Conta total de arquivos (incluindo subpastas)
     */
    public function getTotalFiles(): int
    {
        $count = count($this->files());
        
        foreach ($this->subfolders() as $subfolder) {
            $count += $subfolder->getTotalFiles();
        }
        
        return $count;
    }
    
    /**
     * Calcula tamanho total da pasta em bytes
     */
    public function getTotalSize(): int
    {
        $size = 0;
        
        foreach ($this->files() as $file) {
            $size += $file->size ?? 0;
        }
        
        foreach ($this->subfolders() as $subfolder) {
            $size += $subfolder->getTotalSize();
        }
        
        return $size;
    }
}

