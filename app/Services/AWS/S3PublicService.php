<?php

namespace App\Services\AWS;

/**
 * Serviço para upload de arquivos públicos no S3
 * Arquivos ficam acessíveis via URL pública
 */
class S3PublicService extends S3Service
{
    public function __construct()
    {
        parent::__construct(new AwsPublicCredentials());
    }

    /**
     * Faz upload e retorna a URL pública do arquivo
     * 
     * @param string $localFilePath Caminho local do arquivo
     * @param string $s3Key Caminho/chave no bucket S3
     * @param array $metadata Metadados adicionais
     * @return string|false URL pública ou false em caso de erro
     */
    public function uploadAndGetUrl(string $localFilePath, string $s3Key, array $metadata = []): string|false
    {
        if ($this->upload($localFilePath, $s3Key, $metadata)) {
            return $this->getPublicUrl($s3Key);
        }
        return false;
    }

    /**
     * Retorna a URL pública de um arquivo
     */
    public function getPublicUrl(string $s3Key): string
    {
        return $this->credentials->getBucketUrl() . '/' . $s3Key;
    }

    /**
     * Gera um nome único para o arquivo baseado no usuário
     * 
     * @param int $userId ID do usuário
     * @param string $originalFileName Nome original do arquivo
     * @param string $subfolder Subpasta opcional (ex: 'avatars', 'logos')
     * @return string
     */
    public function generateUniqueKey(int $userId, string $originalFileName, string $subfolder = ''): string
    {
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $timestamp = date('YmdHis');
        $random = rand(1, 99);
        
        $key = $userId . '/';
        
        if (!empty($subfolder)) {
            $key .= $subfolder . '/';
        }
        
        $key .= $timestamp . $random . '.' . $extension;
        
        return $key;
    }

    /**
     * Valida tipo de arquivo para upload público
     * 
     * @param string $filePath Caminho do arquivo
     * @param array $allowedExtensions Extensões permitidas
     * @return bool
     */
    public function validateFile(string $filePath, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']): bool
    {
        if (!file_exists($filePath)) {
            $this->errors[] = 'Arquivo não encontrado';
            return false;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $this->errors[] = 'Extensão de arquivo não permitida. Permitidas: ' . implode(', ', $allowedExtensions);
            return false;
        }

        // Limite de 50MB
        $maxSize = 50 * 1024 * 1024; // 50MB em bytes
        if (filesize($filePath) > $maxSize) {
            $this->errors[] = 'Arquivo muito grande. Tamanho máximo: 50MB';
            return false;
        }

        return true;
    }
}

