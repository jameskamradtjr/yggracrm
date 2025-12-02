<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Models\Tag;
use App\Models\SistemaLog;

class DriveController extends Controller
{
    /**
     * Lista arquivos e pastas do Drive
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $folderId = $this->request->get('folder');
        $view = $this->request->get('view', 'grid'); // grid ou list
        $filter = $this->request->get('filter'); // favorites, shared, recent, expiring

        // Busca pasta atual
        $currentFolder = null;
        if ($folderId) {
            $currentFolder = DriveFolder::where('id', $folderId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$currentFolder) {
                abort(404, 'Pasta não encontrada.');
            }
        }

        // Busca pastas
        $foldersQuery = DriveFolder::where('user_id', $userId);
        if ($folderId) {
            $foldersQuery->where('parent_id', $folderId);
        } else {
            $foldersQuery->whereNull('parent_id');
        }
        $folders = $foldersQuery->orderBy('name', 'ASC')->get();

        // Busca arquivos
        $filesQuery = DriveFile::where('user_id', $userId);
        if ($folderId) {
            $filesQuery->where('folder_id', $folderId);
        } else {
            $filesQuery->whereNull('folder_id');
        }
        $filesQuery->whereNull('deleted_at');

        // Aplica filtros
        if ($filter === 'favorites') {
            $filesQuery->where('is_favorite', true);
        } elseif ($filter === 'shared') {
            $filesQuery->where('is_shared', true);
        } elseif ($filter === 'recent') {
            $filesQuery->orderBy('created_at', 'DESC')->limit(50);
        } elseif ($filter === 'expiring') {
            $filesQuery->whereNotNull('expiration_date')
                ->whereRaw('expiration_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)')
                ->orderBy('expiration_date', 'ASC');
        }

        $files = $filesQuery->orderBy('name', 'ASC')->get();

        // Estatísticas
        $db = \Core\Database::getInstance();
        $totalSizeResult = $db->queryOne(
            "SELECT COALESCE(SUM(size), 0) as total_size FROM drive_files WHERE user_id = ? AND deleted_at IS NULL",
            [$userId]
        );
        
        $stats = [
            'total_files' => DriveFile::where('user_id', $userId)->whereNull('deleted_at')->count(),
            'total_size' => $totalSizeResult['total_size'] ?? 0,
            'favorites' => DriveFile::where('user_id', $userId)->where('is_favorite', true)->whereNull('deleted_at')->count(),
            'shared' => DriveFile::where('user_id', $userId)->where('is_shared', true)->whereNull('deleted_at')->count(),
        ];

        return $this->view('drive/index', [
            'title' => 'Drive',
            'currentFolder' => $currentFolder,
            'folders' => $folders,
            'files' => $files,
            'view' => $view,
            'filter' => $filter,
            'stats' => $stats
        ]);
    }

    /**
     * Busca clientes via AJAX para Select2
     */
    public function searchClients(): void
    {
        if (!auth()->check()) {
            json_response(['results' => []], 401);
        }

        $userId = auth()->getDataUserId();
        $search = $this->request->get('q', '');
        $page = (int) $this->request->get('page', 1);
        $perPage = 20;

        $query = Client::where('user_id', $userId);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nome_razao_social', 'LIKE', "%{$search}%")
                  ->orWhere('nome_fantasia', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();
        $clients = $query->orderBy('nome_razao_social', 'ASC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $results = array_map(function($client) {
            return [
                'id' => $client->id,
                'text' => $client->nome_razao_social . ($client->nome_fantasia ? ' (' . $client->nome_fantasia . ')' : '')
            ];
        }, $clients);

        json_response([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Busca usuários via AJAX para Select2
     */
    public function searchUsers(): void
    {
        if (!auth()->check()) {
            json_response(['results' => []], 401);
        }

        $userId = auth()->getDataUserId();
        $search = $this->request->get('q', '');
        $page = (int) $this->request->get('page', 1);
        $perPage = 20;

        $query = User::where('user_id', $userId);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();
        $users = $query->orderBy('name', 'ASC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $results = array_map(function($user) {
            return [
                'id' => $user->id,
                'text' => $user->name . ' (' . $user->email . ')'
            ];
        }, $users);

        json_response([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Busca tags via AJAX para Select2
     */
    public function searchTags(): void
    {
        if (!auth()->check()) {
            json_response(['results' => []], 401);
        }

        $userId = auth()->getDataUserId();
        $search = $this->request->get('q', '');

        $query = Tag::where('user_id', $userId);
        
        if (!empty($search)) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $tags = $query->orderBy('name', 'ASC')->get();

        $results = array_map(function($tag) {
            return [
                'id' => $tag->id,
                'text' => $tag->name
            ];
        }, $tags);

        json_response(['results' => $results]);
    }

    /**
     * Processa upload de arquivo
     */
    public function store(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        try {
            $userId = auth()->getDataUserId();
            
            error_log("Drive Upload - Iniciando para user_id: " . $userId);
            error_log("Drive Upload - FILES: " . json_encode($_FILES));
            error_log("Drive Upload - POST: " . json_encode($_POST));

            // Valida se há arquivo
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Nenhum arquivo enviado';
                if (isset($_FILES['file']['error'])) {
                    $errorMsg .= ' (Erro: ' . $_FILES['file']['error'] . ')';
                }
                error_log("Drive Upload - Erro: " . $errorMsg);
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }

            $file = $_FILES['file'];
            $tmpFile = $file['tmp_name'];
            $originalName = $file['name'];
            $size = $file['size'];
            $mimeType = $file['type'];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            error_log("Drive Upload - Arquivo: {$originalName}, Tamanho: {$size}, Tipo: {$mimeType}");

            // Validação de tamanho (50MB padrão)
            $maxSize = 50 * 1024 * 1024;
            if ($size > $maxSize) {
                error_log("Drive Upload - Arquivo muito grande: {$size} bytes");
                json_response(['success' => false, 'message' => 'Arquivo muito grande. Máximo: 50MB'], 400);
            }

            // Upload para S3 privado (passa nome original para preservar extensão)
            error_log("Drive Upload - Iniciando upload para S3...");
            $s3Key = s3_upload_private($tmpFile, $userId, 'drive', 50, $originalName);

            if (!$s3Key) {
                $s3 = s3_private();
                $errorMsg = $s3->getLastError() ?: 'Erro ao fazer upload para S3';
                error_log("Drive Upload - Erro S3: " . $errorMsg);
                json_response(['success' => false, 'message' => $errorMsg], 500);
                return;
            }
            
            error_log("Drive Upload - S3 Key: " . $s3Key);

            // Cria registro no banco
            $driveFile = DriveFile::create([
                'user_id' => $userId,
                'folder_id' => $this->request->input('folder_id') ?: null,
                'name' => $originalName,
                's3_key' => $s3Key,
                'mime_type' => $mimeType,
                'size' => $size,
                'extension' => $extension,
                'client_id' => $this->request->input('client_id') ?: null,
                'lead_id' => $this->request->input('lead_id') ?: null,
                'project_id' => $this->request->input('project_id') ?: null,
                'responsible_user_id' => $this->request->input('responsible_user_id') ?: null,
                'description' => $this->request->input('description') ?: null,
                'expiration_date' => $this->request->input('expiration_date') ?: null,
            ]);

            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            if (!empty($tagsInput)) {
                $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
                
                foreach ($tags as $tagNameOrId) {
                    if (is_numeric($tagNameOrId)) {
                        $driveFile->addTag((int) $tagNameOrId);
                    } else {
                        $tag = Tag::where('name', $tagNameOrId)
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = Tag::create([
                                'name' => $tagNameOrId,
                                'user_id' => $userId
                            ]);
                        }
                        $driveFile->addTag($tag->id);
                    }
                }
            }

            SistemaLog::registrar('drive_files', 'CREATE', $driveFile->id, "Arquivo enviado: {$originalName}");

            json_response([
                'success' => true,
                'message' => 'Arquivo enviado com sucesso!',
                'file_id' => $driveFile->id
            ]);

        } catch (\Exception $e) {
            error_log("Erro ao fazer upload: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao processar upload'], 500);
        }
    }

    /**
     * Exibe detalhes do arquivo
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $file = DriveFile::where('id', $params['id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$file) {
            abort(404, 'Arquivo não encontrado.');
        }

        return $this->view('drive/show', [
            'title' => $file->name,
            'file' => $file
        ]);
    }

    /**
     * Download de arquivo
     */
    public function download(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $userId = auth()->getDataUserId();
        $file = DriveFile::where('id', $params['id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$file) {
            json_response(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        // Gera URL assinada e redireciona
        s3_private()->downloadFile($file->s3_key, $file->name);
    }

    /**
     * Marca/desmarca arquivo como favorito
     */
    public function toggleFavorite(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }

        $userId = auth()->getDataUserId();
        $file = DriveFile::where('id', $params['id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$file) {
            json_response(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        $file->update(['is_favorite' => !$file->is_favorite]);

        json_response([
            'success' => true,
            'is_favorite' => $file->is_favorite
        ]);
    }

    /**
     * Move arquivo para lixeira (soft delete)
     */
    public function trash(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }

        $userId = auth()->getDataUserId();
        $file = DriveFile::where('id', $params['id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$file) {
            json_response(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        $file->delete(); // Soft delete

        SistemaLog::registrar('drive_files', 'DELETE', $file->id, "Arquivo movido para lixeira: {$file->name}");

        json_response(['success' => true, 'message' => 'Arquivo movido para lixeira']);
    }

    /**
     * Deleta arquivo permanentemente
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }

        $userId = auth()->getDataUserId();
        $file = DriveFile::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$file) {
            json_response(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        // Deleta do S3
        s3_delete_private($file->s3_key);

        // Deleta do banco
        $file->forceDelete();

        SistemaLog::registrar('drive_files', 'DELETE', $file->id, "Arquivo deletado permanentemente: {$file->name}");

        json_response(['success' => true, 'message' => 'Arquivo deletado permanentemente']);
    }

    /**
     * Cria nova pasta
     */
    public function createFolder(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }

        try {
            $userId = auth()->getDataUserId();

            $data = $this->validate([
                'name' => 'required',
                'parent_id' => 'nullable|integer',
                'color' => 'nullable',
                'description' => 'nullable'
            ]);

            $folder = DriveFolder::create([
                'user_id' => $userId,
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'color' => $data['color'] ?? null,
                'description' => $data['description'] ?? null
            ]);

            SistemaLog::registrar('drive_folders', 'CREATE', $folder->id, "Pasta criada: {$folder->name}");

            json_response([
                'success' => true,
                'message' => 'Pasta criada com sucesso!',
                'folder_id' => $folder->id
            ]);

        } catch (\Exception $e) {
            json_response(['success' => false, 'message' => 'Erro ao criar pasta'], 500);
        }
    }

    /**
     * Deleta pasta
     */
    public function deleteFolder(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false], 401);
        }

        $userId = auth()->getDataUserId();
        $folder = DriveFolder::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$folder) {
            json_response(['success' => false, 'message' => 'Pasta não encontrada'], 404);
        }

        if (!$folder->isEmpty()) {
            json_response(['success' => false, 'message' => 'Pasta não está vazia'], 400);
        }

        $folder->delete();

        SistemaLog::registrar('drive_folders', 'DELETE', $folder->id, "Pasta deletada: {$folder->name}");

        json_response(['success' => true, 'message' => 'Pasta deletada com sucesso']);
    }
}

