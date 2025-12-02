<?php

declare(strict_types=1);

namespace Core;

/**
 * Helper para manipulação de arquivos
 */
class FileHelper
{
    /**
     * Salva uma imagem base64 como arquivo
     * 
     * @param string $base64Data Dados da imagem em base64 (com ou sem prefixo data:image)
     * @param string $directory Diretório onde salvar (relativo a public/)
     * @param string|null $filename Nome do arquivo (sem extensão). Se null, gera automaticamente
     * @param bool $useS3 Se true, salva no S3 público ao invés do sistema de arquivos local
     * @return string|null Caminho relativo do arquivo salvo ou URL do S3 em caso de sucesso, null em caso de erro
     */
    public static function saveBase64Image(string $base64Data, string $directory = 'storage/avatars', ?string $filename = null, bool $useS3 = true): ?string
    {
        // Remove o prefixo data:image se existir
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $imageType = $matches[1];
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        } else {
            // Assume JPEG se não tiver prefixo
            $imageType = 'jpeg';
        }
        
        // Decodifica base64
        $imageData = base64_decode($base64Data, true);
        
        if ($imageData === false) {
            return null;
        }
        
        // Gera nome do arquivo se não fornecido
        if ($filename === null) {
            $filename = 'user_' . (auth()->id() ?? '0') . '_' . time();
        }
        
        // Adiciona extensão baseada no tipo
        $extension = match($imageType) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            default => 'jpg'
        };
        
        // Se usar S3, faz upload para S3 público
        if ($useS3 && config('aws.access_key_id')) {
            try {
                // Cria arquivo temporário
                $tmpFile = sys_get_temp_dir() . '/' . $filename . '.' . $extension;
                if (file_put_contents($tmpFile, $imageData) === false) {
                    return null;
                }
                
                // Determina a subpasta baseada no diretório
                $subfolder = 'avatars'; // padrão
                if (str_contains($directory, 'logo')) {
                    $subfolder = 'logos';
                } elseif (str_contains($directory, 'avatar')) {
                    $subfolder = 'avatars';
                } elseif (str_contains($directory, 'image')) {
                    $subfolder = 'images';
                }
                
                // Upload para S3
                $userId = auth()->getDataUserId() ?? auth()->id() ?? 0;
                $url = s3_upload_public($tmpFile, $userId, $subfolder, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                
                // Remove arquivo temporário
                @unlink($tmpFile);
                
                if ($url) {
                    error_log("Avatar salvo no S3: {$url}");
                    return $url;
                }
                
                error_log("Erro ao salvar no S3, usando armazenamento local como fallback");
            } catch (\Exception $e) {
                error_log("Erro ao fazer upload para S3: " . $e->getMessage());
                // Continua para salvar localmente como fallback
            }
        }
        
        // Fallback: salva localmente
        $fullDirectory = BASE_PATH . '/public/' . trim($directory, '/');
        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }
        
        $fullPath = $fullDirectory . '/' . $filename . '.' . $extension;
        
        // Salva o arquivo
        if (file_put_contents($fullPath, $imageData) !== false) {
            // Retorna caminho relativo para acesso via web
            return '/' . trim($directory, '/') . '/' . $filename . '.' . $extension;
        }
        
        return null;
    }
    
    /**
     * Remove um arquivo
     * 
     * @param string $filePath Caminho relativo do arquivo (ex: /storage/avatars/avatar_123.jpg) ou URL do S3
     * @return bool True se removido com sucesso
     */
    public static function deleteFile(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        
        // Se for URL do S3, tenta deletar do S3
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            try {
                // Extrai a chave S3 da URL
                $bucketPublic = config('aws.bucket_public');
                if ($bucketPublic && str_contains($filePath, $bucketPublic)) {
                    // Extrai a chave após o nome do bucket
                    $parts = explode($bucketPublic . '/', $filePath);
                    if (isset($parts[1])) {
                        $s3Key = $parts[1];
                        return s3_delete_public($s3Key);
                    }
                }
            } catch (\Exception $e) {
                error_log("Erro ao deletar arquivo do S3: " . $e->getMessage());
                return false;
            }
        }
        
        // Remove barra inicial se existir
        $filePath = ltrim($filePath, '/');
        
        $fullPath = BASE_PATH . '/public/' . $filePath;
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Verifica se um arquivo existe
     * 
     * @param string $filePath Caminho relativo do arquivo ou URL do S3
     * @return bool True se o arquivo existe
     */
    public static function fileExists(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        
        // Se for URL do S3, assume que existe (não faz requisição ao S3)
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return true;
        }
        
        $filePath = ltrim($filePath, '/');
        $fullPath = BASE_PATH . '/public/' . $filePath;
        
        return file_exists($fullPath) && is_file($fullPath);
    }
    
    /**
     * Verifica se o caminho é uma URL do S3
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool True se for URL do S3
     */
    public static function isS3Url(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        
        $bucketPublic = config('aws.bucket_public');
        $bucketPrivate = config('aws.bucket_private');
        
        return (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))
            && ($bucketPublic && str_contains($filePath, $bucketPublic) 
                || $bucketPrivate && str_contains($filePath, $bucketPrivate));
    }
}


