<?php

namespace App\Services\AWS;

use Aws\S3\Exception\S3Exception;
use Exception;

/**
 * Serviço para upload de arquivos privados no S3
 * Arquivos requerem URL assinada para download
 */
class S3PrivateService extends S3Service
{
    public function __construct()
    {
        parent::__construct(new AwsPrivateCredentials());
    }

    /**
     * Gera URL assinada para download de arquivo privado
     * 
     * @param string $s3Key Caminho/chave no bucket S3
     * @param int $expirationMinutes Tempo de validade em minutos (padrão: 15)
     * @return string|false URL assinada ou false em caso de erro
     */
    public function getSignedDownloadUrl(string $s3Key, int $expirationMinutes = 15): string|false
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->credentials->getBucketName(),
                'Key' => $s3Key,
            ]);

            $request = $this->s3Client->createPresignedRequest(
                $cmd,
                "+{$expirationMinutes} minutes"
            );

            return (string) $request->getUri();
        } catch (S3Exception $e) {
            $this->errors[] = 'Erro ao gerar URL assinada: ' . $e->getMessage();
            error_log('AWS S3 Signed URL Error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->errors[] = 'Erro ao gerar URL: ' . $e->getMessage();
            error_log('AWS Signed URL Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera URL assinada com nome de arquivo personalizado para download
     * 
     * @param string $s3Key Caminho/chave no bucket S3
     * @param string $downloadFileName Nome do arquivo para download
     * @param int $expirationMinutes Tempo de validade em minutos
     * @return string|false
     */
    public function getSignedDownloadUrlWithFilename(
        string $s3Key,
        string $downloadFileName,
        int $expirationMinutes = 15
    ): string|false {
        if (!$this->connect()) {
            return false;
        }

        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->credentials->getBucketName(),
                'Key' => $s3Key,
                'ResponseContentDisposition' => "attachment; filename=\"{$downloadFileName}\"",
            ]);

            $request = $this->s3Client->createPresignedRequest(
                $cmd,
                "+{$expirationMinutes} minutes"
            );

            return (string) $request->getUri();
        } catch (Exception $e) {
            $this->errors[] = 'Erro ao gerar URL assinada: ' . $e->getMessage();
            error_log('AWS Signed URL with Filename Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Faz download direto do arquivo (redireciona para URL assinada)
     * 
     * @param string $s3Key Caminho/chave no bucket S3
     * @param string|null $downloadFileName Nome opcional para o arquivo
     * @return void
     */
    public function downloadFile(string $s3Key, ?string $downloadFileName = null): void
    {
        if ($downloadFileName) {
            $signedUrl = $this->getSignedDownloadUrlWithFilename($s3Key, $downloadFileName);
        } else {
            $signedUrl = $this->getSignedDownloadUrl($s3Key);
        }

        if ($signedUrl) {
            header("Location: {$signedUrl}");
            exit;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Arquivo não encontrado ou erro ao gerar link de download']);
        exit;
    }

    /**
     * Gera um nome único para o arquivo baseado no usuário
     * 
     * @param int $userId ID do usuário
     * @param string $originalFileName Nome original do arquivo
     * @param string $subfolder Subpasta opcional (ex: 'documents', 'contracts')
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
     * Valida arquivo para upload privado
     * 
     * @param string $filePath Caminho do arquivo
     * @param int $maxSizeMB Tamanho máximo em MB (padrão: 50MB)
     * @return bool
     */
    public function validateFile(string $filePath, int $maxSizeMB = 50): bool
    {
        if (!file_exists($filePath)) {
            $this->errors[] = 'Arquivo não encontrado';
            return false;
        }

        $maxSize = $maxSizeMB * 1024 * 1024;
        if (filesize($filePath) > $maxSize) {
            $this->errors[] = "Arquivo muito grande. Tamanho máximo: {$maxSizeMB}MB";
            return false;
        }

        return true;
    }
}

