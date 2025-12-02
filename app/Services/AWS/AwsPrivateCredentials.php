<?php

namespace App\Services\AWS;

/**
 * Credenciais para bucket privado S3
 * Usado para arquivos privados que requerem download com chave de segurança
 */
class AwsPrivateCredentials extends AwsCredentials
{
    public function __construct()
    {
        parent::__construct();
        
        $this->bucketName = config('aws.bucket_private');
        $this->acl = 'private'; // Arquivos privados
    }
}

