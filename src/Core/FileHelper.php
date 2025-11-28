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
     * @return string|null Caminho relativo do arquivo salvo ou null em caso de erro
     */
    public static function saveBase64Image(string $base64Data, string $directory = 'storage/avatars', ?string $filename = null): ?string
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
        
        // Cria o diretório se não existir
        $fullDirectory = BASE_PATH . '/public/' . trim($directory, '/');
        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
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
     * @param string $filePath Caminho relativo do arquivo (ex: /storage/avatars/avatar_123.jpg)
     * @return bool True se removido com sucesso
     */
    public static function deleteFile(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
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
     * @param string $filePath Caminho relativo do arquivo
     * @return bool True se o arquivo existe
     */
    public static function fileExists(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        
        $filePath = ltrim($filePath, '/');
        $fullPath = BASE_PATH . '/public/' . $filePath;
        
        return file_exists($fullPath) && is_file($fullPath);
    }
}


