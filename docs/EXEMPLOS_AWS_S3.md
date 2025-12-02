# Exemplos de Uso - AWS S3

Este documento contém exemplos práticos de como usar a integração AWS S3 no sistema.

## Configuração Inicial

1. Instale as dependências:
```bash
composer install
```

2. Configure as variáveis de ambiente no arquivo `.env`:
```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET_PUBLIC=bravtopub-fort
AWS_BUCKET_PRIVATE=bravtopriv-fort
AWS_S3_BASE_URL=https://s3.us-east-1.amazonaws.com
```

## Upload de Arquivos Públicos

### Exemplo 1: Upload de Avatar de Usuário

```php
<?php
// Em um controller
public function uploadAvatar(): void
{
    $userId = auth()->getDataUserId();
    $tmpFile = $_FILES['avatar']['tmp_name'];
    
    // Upload simples - retorna URL pública
    $url = s3_upload_public($tmpFile, $userId, 'avatars', ['jpg', 'jpeg', 'png']);
    
    if ($url) {
        // Salva URL no banco
        User::find($userId)->update(['avatar_url' => $url]);
        
        json_response(['success' => true, 'url' => $url]);
    } else {
        json_response(['success' => false, 'message' => 'Erro ao fazer upload'], 500);
    }
}
```

### Exemplo 2: Upload de Logo da Empresa

```php
<?php
public function uploadLogo(): void
{
    $userId = auth()->getDataUserId();
    $tmpFile = $_FILES['logo']['tmp_name'];
    
    // Upload de logo
    $url = s3_upload_public($tmpFile, $userId, 'logos', ['jpg', 'jpeg', 'png', 'svg']);
    
    if ($url) {
        SystemSetting::updateOrCreate(
            ['user_id' => $userId, 'key' => 'logo_url'],
            ['value' => $url]
        );
        
        json_response(['success' => true, 'url' => $url]);
    }
}
```

### Exemplo 3: Upload com Controle Manual

```php
<?php
use App\Services\AWS\S3PublicService;

public function uploadCustom(): void
{
    $s3 = new S3PublicService();
    $userId = auth()->getDataUserId();
    $tmpFile = $_FILES['file']['tmp_name'];
    
    // Validação customizada
    if (!$s3->validateFile($tmpFile, ['jpg', 'png', 'pdf'])) {
        json_response(['success' => false, 'errors' => $s3->getErrors()], 400);
    }
    
    // Gera chave customizada
    $s3Key = $s3->generateUniqueKey($userId, $_FILES['file']['name'], 'custom_folder');
    
    // Upload
    if ($s3->upload($tmpFile, $s3Key)) {
        $url = $s3->getPublicUrl($s3Key);
        json_response(['success' => true, 'url' => $url]);
    } else {
        json_response(['success' => false, 'error' => $s3->getLastError()], 500);
    }
}
```

## Upload de Arquivos Privados

### Exemplo 4: Upload de Documento Confidencial

```php
<?php
public function uploadContract(): void
{
    $userId = auth()->getDataUserId();
    $tmpFile = $_FILES['contract']['tmp_name'];
    
    // Upload privado - retorna chave S3 (não URL pública)
    $s3Key = s3_upload_private($tmpFile, $userId, 'contracts');
    
    if ($s3Key) {
        // Salva chave S3 no banco
        Contract::create([
            'user_id' => $userId,
            's3_key' => $s3Key,
            'filename' => $_FILES['contract']['name'],
            'size' => $_FILES['contract']['size']
        ]);
        
        json_response(['success' => true, 's3_key' => $s3Key]);
    }
}
```

### Exemplo 5: Upload de Anexo de Proposta

```php
<?php
public function uploadProposalAttachment(): void
{
    $userId = auth()->getDataUserId();
    $proposalId = $this->request->post('proposal_id');
    $tmpFile = $_FILES['attachment']['tmp_name'];
    
    // Valida proposta
    $proposal = Proposal::where('id', $proposalId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$proposal) {
        json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
    }
    
    // Upload privado
    $s3Key = s3_upload_private($tmpFile, $userId, "proposals/{$proposalId}");
    
    if ($s3Key) {
        ProposalAttachment::create([
            'proposal_id' => $proposalId,
            's3_key' => $s3Key,
            'filename' => $_FILES['attachment']['name']
        ]);
        
        json_response(['success' => true]);
    }
}
```

## Download de Arquivos Privados

### Exemplo 6: Download Direto (Redirecionamento)

```php
<?php
public function downloadContract(array $params): void
{
    $userId = auth()->getDataUserId();
    $contractId = $params['id'];
    
    // Busca contrato e valida permissão
    $contract = Contract::where('id', $contractId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$contract) {
        json_response(['success' => false, 'message' => 'Contrato não encontrado'], 404);
    }
    
    // Download direto (redireciona para URL assinada)
    s3_private()->downloadFile($contract->s3_key, $contract->filename);
}
```

### Exemplo 7: Gerar URL Assinada (Para Iframe/Preview)

```php
<?php
public function previewDocument(array $params): void
{
    $userId = auth()->getDataUserId();
    $documentId = $params['id'];
    
    $document = Document::where('id', $documentId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$document) {
        json_response(['success' => false, 'message' => 'Documento não encontrado'], 404);
    }
    
    // Gera URL válida por 30 minutos
    $signedUrl = s3_get_signed_url($document->s3_key, 30);
    
    if ($signedUrl) {
        json_response(['success' => true, 'url' => $signedUrl]);
    } else {
        json_response(['success' => false, 'message' => 'Erro ao gerar URL'], 500);
    }
}
```

### Exemplo 8: URL Assinada com Nome Customizado

```php
<?php
use App\Services\AWS\S3PrivateService;

public function downloadWithCustomName(array $params): void
{
    $userId = auth()->getDataUserId();
    $documentId = $params['id'];
    
    $document = Document::where('id', $documentId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$document) {
        json_response(['success' => false], 404);
    }
    
    $s3 = new S3PrivateService();
    
    // Gera URL com nome de arquivo customizado
    $customName = "documento_{$document->id}.pdf";
    $signedUrl = $s3->getSignedDownloadUrlWithFilename(
        $document->s3_key,
        $customName,
        15 // 15 minutos
    );
    
    if ($signedUrl) {
        header("Location: {$signedUrl}");
        exit;
    }
}
```

## Exclusão de Arquivos

### Exemplo 9: Excluir Avatar

```php
<?php
public function deleteAvatar(): void
{
    $userId = auth()->getDataUserId();
    $user = User::find($userId);
    
    if ($user->avatar_s3_key) {
        // Deleta do S3
        if (s3_delete_public($user->avatar_s3_key)) {
            // Atualiza banco
            $user->update(['avatar_s3_key' => null, 'avatar_url' => null]);
            json_response(['success' => true]);
        }
    }
}
```

### Exemplo 10: Excluir Documento Privado

```php
<?php
public function deleteDocument(array $params): void
{
    $userId = auth()->getDataUserId();
    $documentId = $params['id'];
    
    $document = Document::where('id', $documentId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$document) {
        json_response(['success' => false], 404);
    }
    
    // Deleta do S3
    if (s3_delete_private($document->s3_key)) {
        // Deleta do banco
        $document->delete();
        json_response(['success' => true]);
    } else {
        json_response(['success' => false, 'message' => 'Erro ao excluir'], 500);
    }
}
```

## Uso em Views

### Exemplo 11: Exibir Avatar Público

```php
<!-- Em uma view -->
<?php if ($user->avatar_url): ?>
    <img src="<?= htmlspecialchars($user->avatar_url) ?>" alt="Avatar" class="avatar">
<?php else: ?>
    <img src="/tema/assets/images/default-avatar.png" alt="Avatar" class="avatar">
<?php endif; ?>
```

### Exemplo 12: Preview de Documento Privado

```html
<!-- View com iframe para preview -->
<div id="document-preview">
    <button onclick="loadPreview(<?= $document->id ?>)">Visualizar Documento</button>
    <iframe id="preview-frame" style="display:none; width:100%; height:600px;"></iframe>
</div>

<script>
async function loadPreview(documentId) {
    const response = await fetch(`/documents/${documentId}/preview`);
    const data = await response.json();
    
    if (data.success) {
        const iframe = document.getElementById('preview-frame');
        iframe.src = data.url;
        iframe.style.display = 'block';
    }
}
</script>
```

## Helpers Disponíveis

### Funções Helper

```php
// Upload público (retorna URL)
$url = s3_upload_public($tmpFile, $userId, 'subfolder', ['jpg', 'png']);

// Upload privado (retorna chave S3)
$s3Key = s3_upload_private($tmpFile, $userId, 'subfolder', 50); // 50MB max

// Gerar URL assinada
$signedUrl = s3_get_signed_url($s3Key, 15); // 15 minutos

// Deletar público
s3_delete_public($s3Key);

// Deletar privado
s3_delete_private($s3Key);

// Obter URL pública
$url = s3_public_url($s3Key);

// Instâncias dos serviços
$publicService = s3_public();
$privateService = s3_private();
```

## Boas Práticas

1. **Sempre valide permissões** antes de permitir download de arquivos privados
2. **Salve metadados no banco**: nome original, tamanho, tipo, data de upload
3. **Use subpastas organizadas**: `avatars/`, `contracts/`, `proposals/{id}/`
4. **Defina tempo de expiração adequado** para URLs assinadas
5. **Implemente soft delete**: marque como deletado no banco antes de remover do S3
6. **Log de operações**: registre uploads e downloads importantes
7. **Validação de tipo de arquivo**: sempre valide extensões permitidas
8. **Limite de tamanho**: defina limites apropriados por tipo de arquivo

## Estrutura de Pastas no S3

```
Bucket Público (bravtopub-fort):
├── {user_id}/
│   ├── avatars/
│   │   └── 20251202143025_99.jpg
│   ├── logos/
│   │   └── 20251202143530_12.png
│   └── public_images/
│       └── 20251202144000_45.jpg

Bucket Privado (bravtopriv-fort):
├── {user_id}/
│   ├── contracts/
│   │   └── 20251202143025_99.pdf
│   ├── documents/
│   │   └── 20251202143530_12.docx
│   └── proposals/
│       ├── {proposal_id}/
│       │   └── 20251202144000_45.pdf
```

## Rotas de Exemplo

Adicione ao `routes/web.php`:

```php
// Uploads
$router->post('/s3/upload/avatar', [S3Controller::class, 'uploadAvatar']);
$router->post('/s3/upload/document', [S3Controller::class, 'uploadDocument']);

// Downloads
$router->get('/s3/download/{id}', [S3Controller::class, 'downloadDocument']);
$router->get('/s3/preview/{id}', [S3Controller::class, 'getSignedUrl']);

// Exclusões
$router->delete('/s3/avatar', [S3Controller::class, 'deleteAvatar']);
```

