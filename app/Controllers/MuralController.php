<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Mural;
use App\Models\User;

class MuralController extends \Core\Controller
{
    /**
     * Lista todos os itens do mural
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        
        // Busca todos os itens do mural do usuário, ordenados por ordem e data de criação
        $itens = $db->query(
            "SELECT m.*, u.name as user_name 
             FROM mural m 
             INNER JOIN users u ON u.id = m.user_id 
             WHERE m.user_id = ? 
             ORDER BY m.ordem ASC, m.created_at DESC",
            [$userId]
        );
        
        $itensData = array_map(function($row) {
            return [
                'id' => (int)$row['id'],
                'titulo' => $row['titulo'],
                'descricao' => $row['descricao'],
                'imagem_url' => $row['imagem_url'],
                'link_url' => $row['link_url'],
                'link_texto' => $row['link_texto'],
                'data_inicio' => $row['data_inicio'],
                'data_fim' => $row['data_fim'],
                'is_ativo' => (bool)$row['is_ativo'],
                'ordem' => (int)$row['ordem'],
                'created_at' => $row['created_at'],
                'user_name' => $row['user_name']
            ];
        }, $itens);
        
        return $this->view('mural/index', [
            'title' => 'Mural',
            'itens' => $itensData
        ]);
    }
    
    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        return $this->view('mural/create', [
            'title' => 'Novo Item do Mural'
        ]);
    }
    
    /**
     * Salva novo item do mural
     */
    public function store(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $titulo = trim($this->request->input('titulo', ''));
            $descricao = trim($this->request->input('descricao', ''));
            $linkUrl = trim($this->request->input('link_url', ''));
            $linkTexto = trim($this->request->input('link_texto', ''));
            $dataInicio = $this->request->input('data_inicio') ?: null;
            $dataFim = $this->request->input('data_fim') ?: null;
            $isAtivo = (bool)$this->request->input('is_ativo', false);
            $ordem = (int)$this->request->input('ordem', 0);
            
            if (empty($titulo)) {
                json_response(['success' => false, 'message' => 'Título é obrigatório'], 400);
                return;
            }
            
            // Processa upload de imagem se houver
            $imagemUrl = null;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['imagem'];
                $tmpFile = $file['tmp_name'];
                $originalName = $file['name'];
                
                // Upload para S3 público
                $url = s3_upload_public($tmpFile, $userId, 'mural', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
                
                if ($url) {
                    $imagemUrl = $url;
                } else {
                    $s3 = s3_public();
                    $errorMsg = $s3->getLastError() ?: 'Erro ao fazer upload da imagem';
                    json_response(['success' => false, 'message' => $errorMsg], 500);
                    return;
                }
            }
            
            $mural = Mural::create([
                'user_id' => $userId,
                'titulo' => $titulo,
                'descricao' => $descricao ?: null,
                'imagem_url' => $imagemUrl,
                'link_url' => $linkUrl ?: null,
                'link_texto' => $linkTexto ?: null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'is_ativo' => $isAtivo,
                'ordem' => $ordem
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Item do mural criado com sucesso!',
                'redirect' => url('/mural')
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao criar item do mural: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao criar item do mural'], 500);
        }
    }
    
    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $mural = Mural::find($params['id']);
        
        if (!$mural || $mural->user_id != $userId) {
            $this->redirect('/mural');
        }
        
        return $this->view('mural/edit', [
            'title' => 'Editar Item do Mural',
            'mural' => $mural
        ]);
    }
    
    /**
     * Atualiza item do mural
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $mural = Mural::find($params['id']);
            
            if (!$mural || $mural->user_id != $userId) {
                json_response(['success' => false, 'message' => 'Item não encontrado'], 404);
                return;
            }
            
            $titulo = trim($this->request->input('titulo', ''));
            $descricao = trim($this->request->input('descricao', ''));
            $linkUrl = trim($this->request->input('link_url', ''));
            $linkTexto = trim($this->request->input('link_texto', ''));
            $dataInicio = $this->request->input('data_inicio') ?: null;
            $dataFim = $this->request->input('data_fim') ?: null;
            $isAtivo = (bool)$this->request->input('is_ativo', false);
            $ordem = (int)$this->request->input('ordem', 0);
            
            if (empty($titulo)) {
                json_response(['success' => false, 'message' => 'Título é obrigatório'], 400);
                return;
            }
            
            // Processa upload de nova imagem se houver
            $imagemUrl = $mural->imagem_url;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['imagem'];
                $tmpFile = $file['tmp_name'];
                $originalName = $file['name'];
                
                // Upload para S3 público
                $url = s3_upload_public($tmpFile, $userId, 'mural', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
                
                if ($url) {
                    // Se havia imagem antiga, pode deletar do S3 (opcional)
                    $imagemUrl = $url;
                } else {
                    $s3 = s3_public();
                    $errorMsg = $s3->getLastError() ?: 'Erro ao fazer upload da imagem';
                    json_response(['success' => false, 'message' => $errorMsg], 500);
                    return;
                }
            }
            
            $mural->update([
                'titulo' => $titulo,
                'descricao' => $descricao ?: null,
                'imagem_url' => $imagemUrl,
                'link_url' => $linkUrl ?: null,
                'link_texto' => $linkTexto ?: null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'is_ativo' => $isAtivo,
                'ordem' => $ordem
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Item do mural atualizado com sucesso!',
                'redirect' => url('/mural')
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao atualizar item do mural: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao atualizar item do mural'], 500);
        }
    }
    
    /**
     * Exclui item do mural
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $mural = Mural::find($params['id']);
            
            if (!$mural || $mural->user_id != $userId) {
                json_response(['success' => false, 'message' => 'Item não encontrado'], 404);
                return;
            }
            
            // Se houver imagem, pode deletar do S3 (opcional)
            if ($mural->imagem_url) {
                // Opcional: deletar imagem do S3
                // s3_delete_public($mural->imagem_url);
            }
            
            $mural->delete();
            
            json_response([
                'success' => true,
                'message' => 'Item do mural excluído com sucesso!'
            ]);
        } catch (\Throwable $e) {
            error_log("Erro ao excluir item do mural: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao excluir item do mural'], 500);
        }
    }
}

