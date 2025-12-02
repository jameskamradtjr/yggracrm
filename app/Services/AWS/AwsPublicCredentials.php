<?php

namespace App\Services\AWS;

/**
 * Credenciais para bucket público S3
 * Usado para arquivos públicos como fotos de usuários, imagens públicas, etc.
 */
class AwsPublicCredentials extends AwsCredentials
{
    public function __construct()
    {
        parent::__construct();
        
        $this->bucketName = config('aws.bucket_public');
        $this->acl = 'public-read'; // Arquivos públicos
    }
}

