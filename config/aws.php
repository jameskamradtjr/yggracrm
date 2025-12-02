<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AWS Access Key ID
    |--------------------------------------------------------------------------
    |
    | Chave de acesso IAM da AWS
    |
    */
    'access_key_id' => env('AWS_ACCESS_KEY_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | AWS Secret Access Key
    |--------------------------------------------------------------------------
    |
    | Chave secreta de acesso IAM da AWS
    |
    */
    'secret_access_key' => env('AWS_SECRET_ACCESS_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | AWS Default Region
    |--------------------------------------------------------------------------
    |
    | Região padrão da AWS onde os recursos estão localizados
    |
    */
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Bucket - Público
    |--------------------------------------------------------------------------
    |
    | Nome do bucket S3 para arquivos públicos
    | Usado para fotos de usuários, imagens públicas, etc.
    |
    */
    'bucket_public' => env('AWS_BUCKET_PUBLIC', 'bravtopub-fort'),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Bucket - Privado
    |--------------------------------------------------------------------------
    |
    | Nome do bucket S3 para arquivos privados
    | Usado para documentos, contratos, arquivos confidenciais, etc.
    |
    */
    'bucket_private' => env('AWS_BUCKET_PRIVATE', 'bravtopriv-fort'),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Base URL
    |--------------------------------------------------------------------------
    |
    | URL base do S3 para construção de URLs públicas
    |
    */
    's3_base_url' => env('AWS_S3_BASE_URL', 'https://s3.us-east-1.amazonaws.com'),

    /*
    |--------------------------------------------------------------------------
    | AWS Use Path Style Endpoint
    |--------------------------------------------------------------------------
    |
    | Define se deve usar endpoint estilo path (necessário para alguns ambientes)
    |
    */
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
];

