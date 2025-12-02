<?php

namespace App\Services\AWS;

/**
 * Classe base para credenciais AWS
 */
abstract class AwsCredentials
{
    protected string $bucketName;
    protected string $iamKey;
    protected string $iamSecret;
    protected string $region;
    protected string $acl;
    protected string $baseUrl;

    public function __construct()
    {
        $this->iamKey = config('aws.access_key_id');
        $this->iamSecret = config('aws.secret_access_key');
        $this->region = config('aws.region');
        $this->baseUrl = config('aws.s3_base_url');
    }

    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    public function getIamKey(): string
    {
        return $this->iamKey;
    }

    public function getIamSecret(): string
    {
        return $this->iamSecret;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getAcl(): string
    {
        return $this->acl;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Retorna a URL completa do bucket
     */
    public function getBucketUrl(): string
    {
        return "{$this->baseUrl}/{$this->bucketName}";
    }
}

