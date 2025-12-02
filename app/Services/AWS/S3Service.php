<?php

namespace App\Services\AWS;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Exception;

/**
 * Serviço base para operações com S3
 */
abstract class S3Service
{
    protected AwsCredentials $credentials;
    protected ?S3Client $s3Client = null;
    protected array $errors = [];
    protected ?string $uploadedFilePath = null;

    public function __construct(AwsCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Conecta ao S3
     */
    protected function connect(): bool
    {
        try {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->credentials->getRegion(),
                'credentials' => [
                    'key' => $this->credentials->getIamKey(),
                    'secret' => $this->credentials->getIamSecret(),
                ],
            ]);
            return true;
        } catch (Exception $e) {
            $this->errors[] = 'Erro ao conectar ao S3: ' . $e->getMessage();
            error_log('AWS S3 Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Faz upload de arquivo para o S3
     * 
     * @param string $localFilePath Caminho local do arquivo
     * @param string $s3Key Caminho/chave no bucket S3
     * @param array $metadata Metadados adicionais
     * @return bool
     */
    public function upload(string $localFilePath, string $s3Key, array $metadata = []): bool
    {
        if (!file_exists($localFilePath)) {
            $this->errors[] = 'Arquivo não encontrado: ' . $localFilePath;
            return false;
        }

        if (!$this->connect()) {
            return false;
        }

        try {
            $params = [
                'Bucket' => $this->credentials->getBucketName(),
                'Key' => $s3Key,
                'SourceFile' => $localFilePath,
                'ACL' => $this->credentials->getAcl(),
            ];

            if (!empty($metadata)) {
                $params['Metadata'] = $metadata;
            }

            $result = $this->s3Client->putObject($params);

            if ($result) {
                $this->uploadedFilePath = $s3Key;
                return true;
            }

            return false;
        } catch (S3Exception $e) {
            $this->errors[] = 'Erro S3: ' . $e->getMessage();
            error_log('AWS S3 Upload Error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->errors[] = 'Erro ao fazer upload: ' . $e->getMessage();
            error_log('AWS Upload Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deleta arquivo do S3
     */
    public function delete(string $s3Key): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->credentials->getBucketName(),
                'Key' => $s3Key,
            ]);
            return true;
        } catch (S3Exception $e) {
            $this->errors[] = 'Erro ao deletar arquivo: ' . $e->getMessage();
            error_log('AWS S3 Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se arquivo existe no S3
     */
    public function exists(string $s3Key): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return $this->s3Client->doesObjectExist(
                $this->credentials->getBucketName(),
                $s3Key
            );
        } catch (Exception $e) {
            error_log('AWS S3 Exists Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna erros ocorridos
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna o último erro como string
     */
    public function getLastError(): ?string
    {
        return !empty($this->errors) ? end($this->errors) : null;
    }

    /**
     * Retorna o caminho do último arquivo enviado
     */
    public function getUploadedFilePath(): ?string
    {
        return $this->uploadedFilePath;
    }

    /**
     * Limpa erros
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }
}

