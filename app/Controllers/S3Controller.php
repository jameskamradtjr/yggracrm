<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;

/**
 * Controller de exemplo para operações com S3
 */
class S3Controller extends Controller
{
    /**
     * Exemplo de upload de arquivo público (foto de perfil)
     */
    public function uploadAvatar(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $userId = auth()->getDataUserId();

        // Valida se há arquivo
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            json_response(['success' => false, 'message' => 'Nenhum arquivo enviado'], 400);
        }

        $tmpFile = $_FILES['avatar']['tmp_name'];
        
        // Faz upload para S3 público
        $url = s3_upload_public($tmpFile, $userId, 'avatars', ['jpg', 'jpeg', 'png', 'webp']);

        if ($url) {
            // Salva URL no banco de dados
            // Exemplo: User::find($userId)->update(['avatar_url' => $url]);
            
            json_response([
                'success' => true,
                'message' => 'Avatar enviado com sucesso',
                'url' => $url
            ]);
        } else {
            $s3 = s3_public();
            json_response([
                'success' => false,
                'message' => 'Erro ao fazer upload',
                'error' => $s3->getLastError()
            ], 500);
        }
    }

    /**
     * Exemplo de upload de documento privado
     */
    public function uploadDocument(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $userId = auth()->getDataUserId();

        // Valida se há arquivo
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            json_response(['success' => false, 'message' => 'Nenhum arquivo enviado'], 400);
        }

        $tmpFile = $_FILES['document']['tmp_name'];
        
        // Faz upload para S3 privado
        $s3Key = s3_upload_private($tmpFile, $userId, 'documents');

        if ($s3Key) {
            // Salva caminho S3 no banco de dados
            // Exemplo: Document::create(['user_id' => $userId, 's3_key' => $s3Key, 'name' => $_FILES['document']['name']]);
            
            json_response([
                'success' => true,
                'message' => 'Documento enviado com sucesso',
                's3_key' => $s3Key
            ]);
        } else {
            $s3 = s3_private();
            json_response([
                'success' => false,
                'message' => 'Erro ao fazer upload',
                'error' => $s3->getLastError()
            ], 500);
        }
    }

    /**
     * Exemplo de download de documento privado
     */
    public function downloadDocument(array $params = []): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $userId = auth()->getDataUserId();
        $documentId = $params['id'] ?? null;

        if (!$documentId) {
            json_response(['success' => false, 'message' => 'ID do documento não fornecido'], 400);
        }

        // Busca documento no banco e valida permissão
        // Exemplo:
        // $document = Document::where('id', $documentId)->where('user_id', $userId)->first();
        // if (!$document) {
        //     json_response(['success' => false, 'message' => 'Documento não encontrado'], 404);
        // }

        // Para este exemplo, vamos simular o s3_key
        $s3Key = $params['s3_key'] ?? null;

        if (!$s3Key) {
            json_response(['success' => false, 'message' => 'Chave S3 não encontrada'], 400);
        }

        // Gera URL assinada e redireciona
        $s3 = s3_private();
        $s3->downloadFile($s3Key, 'documento.pdf');
    }

    /**
     * Exemplo de geração de URL assinada (para exibir em iframe, por exemplo)
     */
    public function getSignedUrl(array $params = []): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $s3Key = $this->request->get('s3_key');

        if (!$s3Key) {
            json_response(['success' => false, 'message' => 'Chave S3 não fornecida'], 400);
        }

        // Valida permissão do usuário para acessar este arquivo
        // ...

        $signedUrl = s3_get_signed_url($s3Key, 30); // 30 minutos

        if ($signedUrl) {
            json_response([
                'success' => true,
                'url' => $signedUrl
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Erro ao gerar URL assinada'
            ], 500);
        }
    }

    /**
     * Exemplo de exclusão de arquivo público
     */
    public function deleteAvatar(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $userId = auth()->getDataUserId();
        $s3Key = $this->request->post('s3_key');

        if (!$s3Key) {
            json_response(['success' => false, 'message' => 'Chave S3 não fornecida'], 400);
        }

        // Valida se o arquivo pertence ao usuário
        if (!str_starts_with($s3Key, $userId . '/')) {
            json_response(['success' => false, 'message' => 'Permissão negada'], 403);
        }

        if (s3_delete_public($s3Key)) {
            // Atualiza banco de dados
            // Exemplo: User::find($userId)->update(['avatar_url' => null]);
            
            json_response([
                'success' => true,
                'message' => 'Avatar excluído com sucesso'
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Erro ao excluir arquivo'
            ], 500);
        }
    }
}

