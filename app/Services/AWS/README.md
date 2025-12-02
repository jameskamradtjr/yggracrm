# AWS S3 Integration

Integração completa com AWS S3 para gerenciamento de arquivos públicos e privados.

## Estrutura

```
app/Services/AWS/
├── AwsCredentials.php           # Classe base para credenciais
├── AwsPublicCredentials.php     # Credenciais para bucket público
├── AwsPrivateCredentials.php    # Credenciais para bucket privado
├── S3Service.php                # Serviço base para operações S3
├── S3PublicService.php          # Serviço para arquivos públicos
└── S3PrivateService.php         # Serviço para arquivos privados
```

## Configuração

### 1. Instalar SDK AWS

```bash
composer require aws/aws-sdk-php
```

### 2. Configurar Variáveis de Ambiente

Adicione ao arquivo `.env`:

```env
AWS_ACCESS_KEY_ID=sua_chave_aqui
AWS_SECRET_ACCESS_KEY=sua_chave_secreta_aqui
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET_PUBLIC=bravtopub-fort
AWS_BUCKET_PRIVATE=bravtopriv-fort
AWS_S3_BASE_URL=https://s3.us-east-1.amazonaws.com
```

### 3. Configurar Buckets na AWS

#### Bucket Público (bravtopub-fort)
- **ACL**: public-read
- **Uso**: Fotos de usuários, logos, imagens públicas
- **Acesso**: URLs públicas diretas

#### Bucket Privado (bravtopriv-fort)
- **ACL**: private
- **Uso**: Documentos, contratos, arquivos confidenciais
- **Acesso**: URLs assinadas temporárias

## Uso Básico

### Upload Público

```php
// Usando helper
$url = s3_upload_public($tmpFile, $userId, 'avatars', ['jpg', 'png']);

// Usando serviço diretamente
$s3 = new S3PublicService();
$s3Key = $s3->generateUniqueKey($userId, 'foto.jpg', 'avatars');
if ($s3->upload($tmpFile, $s3Key)) {
    $url = $s3->getPublicUrl($s3Key);
}
```

### Upload Privado

```php
// Usando helper
$s3Key = s3_upload_private($tmpFile, $userId, 'documents');

// Usando serviço diretamente
$s3 = new S3PrivateService();
$s3Key = $s3->generateUniqueKey($userId, 'contrato.pdf', 'contracts');
$s3->upload($tmpFile, $s3Key);
```

### Download Privado

```php
// Download direto (redirecionamento)
s3_private()->downloadFile($s3Key, 'nome_arquivo.pdf');

// Gerar URL assinada
$signedUrl = s3_get_signed_url($s3Key, 30); // válida por 30 minutos
```

### Exclusão

```php
// Excluir público
s3_delete_public($s3Key);

// Excluir privado
s3_delete_private($s3Key);
```

## Métodos Disponíveis

### S3PublicService

- `upload($localFilePath, $s3Key, $metadata = [])` - Upload de arquivo
- `uploadAndGetUrl($localFilePath, $s3Key, $metadata = [])` - Upload e retorna URL
- `getPublicUrl($s3Key)` - Retorna URL pública
- `generateUniqueKey($userId, $originalFileName, $subfolder = '')` - Gera chave única
- `validateFile($filePath, $allowedExtensions = [])` - Valida arquivo
- `delete($s3Key)` - Deleta arquivo
- `exists($s3Key)` - Verifica se arquivo existe

### S3PrivateService

- `upload($localFilePath, $s3Key, $metadata = [])` - Upload de arquivo
- `getSignedDownloadUrl($s3Key, $expirationMinutes = 15)` - Gera URL assinada
- `getSignedDownloadUrlWithFilename($s3Key, $downloadFileName, $expirationMinutes = 15)` - URL assinada com nome customizado
- `downloadFile($s3Key, $downloadFileName = null)` - Download direto
- `generateUniqueKey($userId, $originalFileName, $subfolder = '')` - Gera chave única
- `validateFile($filePath, $maxSizeMB = 50)` - Valida arquivo
- `delete($s3Key)` - Deleta arquivo
- `exists($s3Key)` - Verifica se arquivo existe

## Helpers Globais

```php
s3_public()                    // Instância de S3PublicService
s3_private()                   // Instância de S3PrivateService
s3_upload_public(...)          // Upload público simplificado
s3_upload_private(...)         // Upload privado simplificado
s3_get_signed_url(...)         // Gera URL assinada
s3_delete_public(...)          // Deleta arquivo público
s3_delete_private(...)         // Deleta arquivo privado
s3_public_url(...)             // Retorna URL pública
```

## Tratamento de Erros

Todos os serviços mantêm um array de erros:

```php
$s3 = s3_public();

if (!$s3->upload($file, $key)) {
    // Obter último erro
    $error = $s3->getLastError();
    
    // Obter todos os erros
    $errors = $s3->getErrors();
    
    // Limpar erros
    $s3->clearErrors();
}
```

## Segurança

1. **Validação de Permissões**: Sempre valide se o usuário tem permissão para acessar o arquivo
2. **Validação de Tipo**: Use `validateFile()` para validar extensões permitidas
3. **Limite de Tamanho**: Defina limites apropriados por tipo de arquivo
4. **URLs Assinadas**: Use tempo de expiração adequado (15-30 minutos recomendado)
5. **Organização**: Use estrutura de pastas por usuário: `{user_id}/{subfolder}/`

## Exemplo Completo

```php
<?php

namespace App\Controllers;

use Core\Controller;

class DocumentController extends Controller
{
    public function upload(): void
    {
        // Autenticação
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }
        
        $userId = auth()->getDataUserId();
        
        // Validação de arquivo
        if (!isset($_FILES['document'])) {
            json_response(['success' => false, 'message' => 'Nenhum arquivo enviado'], 400);
        }
        
        $tmpFile = $_FILES['document']['tmp_name'];
        $originalName = $_FILES['document']['name'];
        
        // Upload privado
        $s3Key = s3_upload_private($tmpFile, $userId, 'documents');
        
        if ($s3Key) {
            // Salvar no banco
            $document = Document::create([
                'user_id' => $userId,
                's3_key' => $s3Key,
                'filename' => $originalName,
                'size' => $_FILES['document']['size']
            ]);
            
            json_response(['success' => true, 'id' => $document->id]);
        } else {
            json_response(['success' => false, 'message' => 'Erro ao fazer upload'], 500);
        }
    }
    
    public function download(array $params): void
    {
        // Autenticação
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }
        
        $userId = auth()->getDataUserId();
        $documentId = $params['id'];
        
        // Buscar e validar permissão
        $document = Document::where('id', $documentId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$document) {
            json_response(['success' => false], 404);
        }
        
        // Download
        s3_private()->downloadFile($document->s3_key, $document->filename);
    }
}
```

## Logs

Todos os erros são automaticamente registrados no log do PHP:

```php
error_log('AWS S3 Connection Error: ...');
error_log('AWS S3 Upload Error: ...');
error_log('AWS S3 Delete Error: ...');
```

## Testes

Para testar a integração:

1. Configure as credenciais no `.env`
2. Crie um endpoint de teste
3. Faça upload de um arquivo
4. Verifique se o arquivo aparece no bucket S3
5. Teste download/exclusão

## Suporte

Para mais exemplos, veja o arquivo `EXEMPLOS_AWS_S3.md` na raiz do projeto.

