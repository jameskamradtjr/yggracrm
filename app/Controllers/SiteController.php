<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\UserSite;
use App\Models\SitePost;
use App\Models\NewsletterSubscriber;
use App\Models\SiteAnalytics;
use App\Models\SistemaLog;
use App\Services\OpenAIService;

class SiteController extends Controller
{
    /**
     * Exibe o site público do usuário
     */
    public function show(array $params): string
    {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            abort(404, 'Site não encontrado.');
        }
        
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE slug = ? AND active = 1 LIMIT 1",
            [$slug]
        );
        
        if (!$siteRow) {
            abort(404, 'Site não encontrado.');
        }
        
        $site = UserSite::newInstance($siteRow, true);
        
        if (!$site) {
            abort(404, 'Site não encontrado.');
        }
        
        // Busca posts publicados com paginação
        $page = (int)($this->request->query('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $db = \Core\Database::getInstance();
        $totalPosts = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_posts WHERE user_site_id = ? AND published = 1",
            [$site->id]
        )['total'] ?? 0;
        
        $posts = $db->query(
            "SELECT * FROM site_posts WHERE user_site_id = ? AND published = 1 ORDER BY published_at DESC LIMIT ? OFFSET ?",
            [$site->id, $perPage, $offset]
        );
        
        $postsArray = array_map(function($row) {
            return SitePost::newInstance($row, true);
        }, $posts);
        
        $totalPages = ceil($totalPosts / $perPage);
        
        // Registra visualização do site
        $this->trackPageView($site->id, null, '/site/' . $site->slug);
        
        // Renderiza view pública
        $title = ($site->user()->name ?? 'Site') . ' - ' . config('app.name');
        ob_start();
        extract([
            'title' => $title,
            'site' => $site,
            'posts' => $postsArray,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts
        ]);
        include base_path('views/site/public/index.php');
        $content = ob_get_clean();
        
        return $this->view('layouts/public', [
            'title' => $title,
            'content' => $content,
            'site' => $site // Passa site para o layout poder usar os pixels
        ]);
    }
    
    /**
     * Exibe post individual
     */
    public function showPost(array $params): string
    {
        $slug = $params['slug'] ?? null;
        $postSlug = $params['post_slug'] ?? null;
        
        if (!$slug || !$postSlug) {
            abort(404, 'Post não encontrado.');
        }
        
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE slug = ? AND active = 1 LIMIT 1",
            [$slug]
        );
        
        if (!$siteRow) {
            abort(404, 'Site não encontrado.');
        }
        
        $site = UserSite::newInstance($siteRow, true);
        
        if (!$site) {
            abort(404, 'Site não encontrado.');
        }
        
        $db = \Core\Database::getInstance();
        $postRow = $db->queryOne(
            "SELECT * FROM site_posts WHERE slug = ? AND user_site_id = ? AND published = 1 LIMIT 1",
            [$postSlug, $site->id]
        );
        
        if (!$postRow) {
            abort(404, 'Post não encontrado.');
        }
        
        $post = SitePost::newInstance($postRow, true);
        
        if (!$post) {
            abort(404, 'Post não encontrado.');
        }
        
        // Incrementa visualizações
        $post->incrementViews();
        
        // Registra visualização do post
        $this->trackPageView($site->id, $post->id, '/site/' . $site->slug . '/post/' . $post->slug);
        
        // Verifica se já curtiu (por IP)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $hasLiked = $post->hasLiked($ipAddress);
        
        // Renderiza view pública do post
        $title = $post->title . ' - ' . ($site->user()->name ?? 'Site');
        ob_start();
        extract([
            'title' => $title,
            'site' => $site,
            'post' => $post,
            'hasLiked' => $hasLiked
        ]);
        include base_path('views/site/public/post.php');
        $content = ob_get_clean();
        
        return $this->view('layouts/public', [
            'title' => $title,
            'content' => $content,
            'site' => $site // Passa site para o layout poder usar os pixels
        ]);
    }
    
    /**
     * Assina newsletter
     */
    public function subscribeNewsletter(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            json_response(['success' => false, 'message' => 'Site não encontrado'], 404);
            return;
        }
        
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE slug = ? AND active = 1 LIMIT 1",
            [$slug]
        );
        
        if (!$siteRow) {
            abort(404, 'Site não encontrado.');
        }
        
        $site = UserSite::newInstance($siteRow, true);
        
        if (!$site) {
            json_response(['success' => false, 'message' => 'Site não encontrado'], 404);
            return;
        }
        
        $data = $this->request->all();
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }
        
        // Verifica se já está inscrito
        $db = \Core\Database::getInstance();
        $existing = $db->queryOne(
            "SELECT id FROM newsletter_subscribers WHERE user_site_id = ? AND email = ?",
            [$site->id, $email]
        );
        
        if ($existing) {
            json_response(['success' => false, 'message' => 'Este email já está inscrito'], 400);
            return;
        }
        
        try {
            NewsletterSubscriber::create([
                'user_site_id' => $site->id,
                'email' => $email,
                'name' => $name ?: null,
                'confirmed' => true, // Confirmação automática por enquanto
                'confirmed_at' => date('Y-m-d H:i:s')
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Inscrição realizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao inscrever na newsletter: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao processar inscrição'], 500);
        }
    }
    
    /**
     * Curtir/descurtir post
     */
    public function toggleLike(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $postId = $params['post_id'] ?? null;
        
        if (!$postId) {
            json_response(['success' => false, 'message' => 'Post não encontrado'], 404);
            return;
        }
        
        $db = \Core\Database::getInstance();
        $postRow = $db->queryOne(
            "SELECT * FROM site_posts WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$postRow) {
            json_response(['success' => false, 'message' => 'Post não encontrado'], 404);
            return;
        }
        
        $post = SitePost::newInstance($postRow, true);
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        if ($post->hasLiked($ipAddress)) {
            // Remove like
            $post->removeLike($ipAddress);
            $liked = false;
        } else {
            // Adiciona like
            $post->addLike($ipAddress, $userAgent);
            $liked = true;
        }
        
        json_response([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $post->likes_count
        ]);
    }
    
    /**
     * Página administrativa - Gerenciar meu site
     */
    public function manage(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        
        $site = $siteRow ? UserSite::newInstance($siteRow, true) : null;
        
        // Se não tem site, cria um automaticamente
        if (!$site) {
            $user = \App\Models\User::find($userId);
            $slug = UserSite::generateSlug($user->name ?? 'user' . $userId);
            
            $site = UserSite::create([
                'user_id' => $userId,
                'slug' => $slug,
                'newsletter_title' => 'Newsletter',
                'active' => true
            ]);
        }
        
        $posts = $site->allPosts();
        $subscribers = $site->subscribers();
        
        return $this->view('site/manage/index', [
            'title' => 'Meu Site',
            'site' => $site,
            'posts' => $posts,
            'subscribers' => $subscribers
        ]);
    }
    
    /**
     * Atualiza configurações do site
     */
    public function updateSite(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido');
            $this->redirect('/site/manage');
        }
        
        try {
            $userId = auth()->getDataUserId();
            $db = \Core\Database::getInstance();
            $siteRow = $db->queryOne(
                "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
                [$userId]
            );
            
            if (!$siteRow) {
                abort(404, 'Site não encontrado.');
            }
            
            $site = UserSite::newInstance($siteRow, true);
            
            $data = $this->validate([
                'slug' => 'required|string|max:100|regex:/^[a-z0-9-]+$/',
                'logo_url' => 'nullable|string',
                'photo_url' => 'nullable|string',
                'bio' => 'nullable|string',
                'twitter_url' => 'nullable|url',
                'youtube_url' => 'nullable|url',
                'linkedin_url' => 'nullable|url',
                'instagram_url' => 'nullable|url',
                'meta_pixel_id' => 'nullable|string|max:50',
                'google_analytics_id' => 'nullable|string|max:50',
                'newsletter_title' => 'nullable|string|max:255',
                'newsletter_description' => 'nullable|string'
            ]);
            
            // Valida se o slug é único (exceto para o próprio site)
            $slug = strtolower(trim($data['slug']));
            $existingSite = $db->queryOne(
                "SELECT id FROM user_sites WHERE slug = ? AND id != ?",
                [$slug, $site->id]
            );
            
            if ($existingSite) {
                session()->flash('error', 'Esta URL já está em uso. Escolha outra.');
                $this->redirect('/site/manage');
                return;
            }
            
            // Valida formato do slug
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                session()->flash('error', 'A URL deve conter apenas letras minúsculas, números e hífens.');
                $this->redirect('/site/manage');
                return;
            }
            
            // Valida que não começa ou termina com hífen
            if (str_starts_with($slug, '-') || str_ends_with($slug, '-')) {
                session()->flash('error', 'A URL não pode começar ou terminar com hífen.');
                $this->redirect('/site/manage');
                return;
            }
            
            $data['slug'] = $slug;
            
            // Processa upload de logo
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $tmpFile = $_FILES['logo_file']['tmp_name'];
                $originalName = $_FILES['logo_file']['name'];
                $url = s3_upload_public($tmpFile, $userId, 'logos', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], $originalName);
                if ($url) {
                    $data['logo_url'] = $url;
                }
            }
            
            // Processa upload de foto
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $tmpFile = $_FILES['photo_file']['tmp_name'];
                $originalName = $_FILES['photo_file']['name'];
                $url = s3_upload_public($tmpFile, $userId, 'avatars', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
                if ($url) {
                    $data['photo_url'] = $url;
                }
            }
            
            $site->update($data);
            
            SistemaLog::registrar(
                'user_sites',
                'UPDATE',
                $site->id,
                "Site atualizado",
                null,
                $site->toArray()
            );
            
            session()->flash('success', 'Site atualizado com sucesso!');
            $this->redirect('/site/manage');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar site: ' . $e->getMessage());
            $this->redirect('/site/manage');
        }
    }
    
    /**
     * Cria novo post
     */
    public function createPost(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        
        $site = $siteRow ? UserSite::newInstance($siteRow, true) : null;
        
        if (!$site) {
            abort(404, 'Site não encontrado. Configure seu site primeiro.');
        }
        
        return $this->view('site/manage/create-post', [
            'title' => 'Novo Post',
            'site' => $site
        ]);
    }
    
    /**
     * Gera post usando IA
     */
    public function generatePostWithAI(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
            
            $keywords = trim($data['keywords'] ?? '');
            $tone = trim($data['tone'] ?? 'profissional');
            $referenceLinks = array_filter(array_map('trim', $data['reference_links'] ?? []));
            
            if (empty($keywords)) {
                json_response([
                    'success' => false,
                    'message' => 'Palavras-chave são obrigatórias'
                ], 400);
                return;
            }
            
            $openAIService = new OpenAIService();
            
            if (!$openAIService->isConfigured()) {
                json_response([
                    'success' => false,
                    'message' => 'API key da OpenAI não configurada. Configure em /settings'
                ], 400);
                return;
            }
            
            $result = $openAIService->generatePostContent($keywords, $tone, $referenceLinks);
            
            json_response([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao gerar post com IA: " . $e->getMessage());
            json_response([
                'success' => false,
                'message' => 'Erro ao gerar post: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Salva novo post
     */
    public function storePost(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido');
            $this->redirect('/site/manage/posts/create');
        }
        
        try {
            $userId = auth()->getDataUserId();
            $db = \Core\Database::getInstance();
            $siteRow = $db->queryOne(
                "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
                [$userId]
            );
            
            if (!$siteRow) {
                abort(404, 'Site não encontrado.');
            }
            
            $site = UserSite::newInstance($siteRow, true);
            
            $data = $this->validate([
                'title' => 'required|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'type' => 'required|in:text,youtube,twitter',
                'external_url' => 'nullable|url',
                'featured_image' => 'nullable|string',
                'published' => 'nullable|boolean'
            ]);
            
            // Processa upload de imagem destacada (igual ao /site/manage - logo e foto)
            $featuredImageUrl = $data['featured_image'] ?? null;
            
            error_log("========== SiteController storePost - Upload de Imagem ==========");
            error_log("FILES data: " . json_encode(array_keys($_FILES)));
            
            if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
                error_log("SiteController: Arquivo de imagem recebido");
                error_log("SiteController: - Nome original: " . $_FILES['featured_image_file']['name']);
                error_log("SiteController: - Tamanho: " . $_FILES['featured_image_file']['size'] . " bytes");
                error_log("SiteController: - Tipo MIME: " . $_FILES['featured_image_file']['type']);
                
                $tmpFile = $_FILES['featured_image_file']['tmp_name'];
                $originalName = $_FILES['featured_image_file']['name'];
                
                error_log("SiteController: - Arquivo temporário: {$tmpFile}");
                error_log("SiteController: - Arquivo existe? " . (file_exists($tmpFile) ? 'SIM' : 'NÃO'));
                
                if (file_exists($tmpFile)) {
                    // Usa o helper s3_upload_public que agora aceita o nome original
                    // Ele automaticamente renomeia o arquivo temporário se necessário
                    $url = s3_upload_public($tmpFile, $userId, 'posts', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
                    
                    error_log("SiteController: Resultado do s3_upload_public: " . ($url ? "URL: {$url}" : 'FALSE (falhou)'));
                    
                    if ($url && $url !== false) {
                        error_log("SiteController: ✓ Imagem enviada para S3 com sucesso: {$url}");
                        $featuredImageUrl = $url;
                    } else {
                        $s3 = s3_public();
                        $errorMsg = $s3->getLastError() ?: 'Erro desconhecido';
                        error_log("SiteController: ✗ Upload falhou. Erro S3: {$errorMsg}");
                    }
                } else {
                    error_log("SiteController: ✗ Arquivo temporário não existe!");
                }
            } else {
                if (isset($_FILES['featured_image_file'])) {
                    error_log("SiteController: Erro no upload: " . $_FILES['featured_image_file']['error']);
                } else {
                    error_log("SiteController: Nenhum arquivo de imagem enviado");
                }
            }
            
            error_log("SiteController: featured_image final que será salvo: " . ($featuredImageUrl ?: 'NULL'));
            
            $slug = SitePost::generateSlug($data['title'], $site->id);
            $published = !empty($data['published']);
            
            error_log("SiteController: Criando post com dados:");
            error_log("SiteController: - featured_image: " . ($featuredImageUrl ?: 'NULL'));
            
            $post = SitePost::create([
                'user_site_id' => $site->id,
                'title' => $data['title'],
                'slug' => $slug,
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'type' => $data['type'],
                'external_url' => $data['external_url'] ?? null,
                'featured_image' => $featuredImageUrl,
                'published' => $published,
                'published_at' => $published ? date('Y-m-d H:i:s') : null,
                'likes_count' => 0,
                'views_count' => 0
            ]);
            
            error_log("SiteController: Post criado com ID: {$post->id}");
            error_log("SiteController: Post featured_image após criação: " . ($post->featured_image ?: 'NULL'));
            
            SistemaLog::registrar(
                'site_posts',
                'CREATE',
                $post->id,
                "Post criado: {$post->title}",
                null,
                $post->toArray()
            );
            
            session()->flash('success', 'Post criado com sucesso!');
            $this->redirect('/site/manage');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar post: ' . $e->getMessage());
            $this->redirect('/site/manage/posts/create');
        }
    }
    
    /**
     * Edita post
     */
    public function editPost(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        
        $site = $siteRow ? UserSite::newInstance($siteRow, true) : null;
        
        if (!$site) {
            abort(404, 'Site não encontrado.');
        }
        
        $db = \Core\Database::getInstance();
        $postRow = $db->queryOne(
            "SELECT * FROM site_posts WHERE id = ? AND user_site_id = ? LIMIT 1",
            [$params['id'], $site->id]
        );
        
        if (!$postRow) {
            abort(404, 'Post não encontrado.');
        }
        
        $post = SitePost::newInstance($postRow, true);
        
        return $this->view('site/manage/edit-post', [
            'title' => 'Editar Post',
            'site' => $site,
            'post' => $post
        ]);
    }
    
    /**
     * Atualiza post
     */
    public function updatePost(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido');
            $this->redirect('/site/manage/posts/' . $params['id'] . '/edit');
        }
        
        try {
            $userId = auth()->getDataUserId();
            $db = \Core\Database::getInstance();
            $siteRow = $db->queryOne(
                "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
                [$userId]
            );
            
            if (!$siteRow) {
                abort(404, 'Site não encontrado.');
            }
            
            $site = UserSite::newInstance($siteRow, true);
            
            $postRow = $db->queryOne(
                "SELECT * FROM site_posts WHERE id = ? AND user_site_id = ? LIMIT 1",
                [$params['id'], $site->id]
            );
            
            if (!$postRow) {
                abort(404, 'Post não encontrado.');
            }
            
            $post = SitePost::newInstance($postRow, true);
            
            if (!$post) {
                abort(404, 'Post não encontrado.');
            }
            
            $data = $this->validate([
                'title' => 'required|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'type' => 'required|in:text,youtube,twitter',
                'external_url' => 'nullable|url',
                'featured_image' => 'nullable|string',
                'published' => 'nullable|boolean'
            ]);
            
            // Processa upload de imagem destacada (igual ao /site/manage - logo e foto)
            $featuredImageUrl = $data['featured_image'] ?? $post->featured_image;
            
            error_log("========== SiteController updatePost - Upload de Imagem ==========");
            error_log("FILES data: " . json_encode(array_keys($_FILES)));
            
            if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
                error_log("SiteController: Arquivo de imagem recebido");
                error_log("SiteController: - Nome original: " . $_FILES['featured_image_file']['name']);
                error_log("SiteController: - Tamanho: " . $_FILES['featured_image_file']['size'] . " bytes");
                
                $tmpFile = $_FILES['featured_image_file']['tmp_name'];
                $originalName = $_FILES['featured_image_file']['name'];
                
                error_log("SiteController: - Arquivo temporário: {$tmpFile}");
                
                if (file_exists($tmpFile)) {
                    // Usa o helper s3_upload_public que agora aceita o nome original
                    // Ele automaticamente renomeia o arquivo temporário se necessário
                    $url = s3_upload_public($tmpFile, $userId, 'posts', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
                    
                    error_log("SiteController: Resultado do s3_upload_public: " . ($url ? "URL: {$url}" : 'FALSE (falhou)'));
                    
                    if ($url && $url !== false) {
                        error_log("SiteController: ✓ Imagem enviada para S3 com sucesso: {$url}");
                        
                        // Remove imagem antiga do S3 se existir
                        if ($post->featured_image && (strpos($post->featured_image, 's3.') !== false || strpos($post->featured_image, 'amazonaws.com') !== false)) {
                            if (preg_match('/amazonaws\.com\/(.+)$/', $post->featured_image, $matches)) {
                                $oldS3Key = urldecode($matches[1]);
                                s3_delete_public($oldS3Key);
                                error_log("SiteController: Imagem antiga removida do S3: {$oldS3Key}");
                            }
                        }
                        $featuredImageUrl = $url;
                    } else {
                        $s3 = s3_public();
                        $errorMsg = $s3->getLastError() ?: 'Erro desconhecido';
                        error_log("SiteController: ✗ Upload falhou. Erro S3: {$errorMsg}");
                    }
                } else {
                    error_log("SiteController: ✗ Arquivo temporário não existe!");
                }
            } else {
                if (isset($_FILES['featured_image_file'])) {
                    error_log("SiteController: Erro no upload: " . $_FILES['featured_image_file']['error']);
                } else {
                    error_log("SiteController: Nenhum arquivo de imagem enviado");
                }
            }
            
            error_log("SiteController: featured_image final que será salvo: " . ($featuredImageUrl ?: 'NULL'));
            
            // Gera novo slug se o título mudou
            if ($post->title !== $data['title']) {
                $slug = SitePost::generateSlug($data['title'], $site->id);
                $data['slug'] = $slug;
            }
            
            $published = !empty($data['published']);
            if ($published && !$post->published) {
                $data['published_at'] = date('Y-m-d H:i:s');
            } elseif (!$published) {
                $data['published_at'] = null;
            }
            
            unset($data['published']); // Remove do array para usar o valor processado
            $data['published'] = $published;
            $data['featured_image'] = $featuredImageUrl;
            
            error_log("SiteController: Atualizando post com dados:");
            error_log("SiteController: - featured_image: " . ($featuredImageUrl ?: 'NULL'));
            
            $post->update($data);
            
            error_log("SiteController: Post atualizado. featured_image após update: " . ($post->featured_image ?: 'NULL'));
            
            SistemaLog::registrar(
                'site_posts',
                'UPDATE',
                $post->id,
                "Post atualizado: {$post->title}",
                null,
                $post->toArray()
            );
            
            session()->flash('success', 'Post atualizado com sucesso!');
            $this->redirect('/site/manage');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar post: ' . $e->getMessage());
            $this->redirect('/site/manage/posts/' . $params['id'] . '/edit');
        }
    }
    
    /**
     * Exclui post
     */
    public function deletePost(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        try {
            $userId = auth()->getDataUserId();
            $db = \Core\Database::getInstance();
            $siteRow = $db->queryOne(
                "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
                [$userId]
            );
            
            if (!$siteRow) {
                abort(404, 'Site não encontrado.');
            }
            
            $site = UserSite::newInstance($siteRow, true);
            
            $postRow = $db->queryOne(
                "SELECT * FROM site_posts WHERE id = ? AND user_site_id = ? LIMIT 1",
                [$params['id'], $site->id]
            );
            
            if (!$postRow) {
                abort(404, 'Post não encontrado.');
            }
            
            $post = SitePost::newInstance($postRow, true);
            
            if (!$post) {
                abort(404, 'Post não encontrado.');
            }
            
            $postId = $post->id;
            $postTitle = $post->title;
            
            // Remove likes
            $db = \Core\Database::getInstance();
            $db->execute("DELETE FROM post_likes WHERE site_post_id = ?", [$postId]);
            
            // Remove post
            $post->delete();
            
            SistemaLog::registrar(
                'site_posts',
                'DELETE',
                $postId,
                "Post excluído: {$postTitle}",
                null,
                null
            );
            
            session()->flash('success', 'Post excluído com sucesso!');
            $this->redirect('/site/manage');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir post: ' . $e->getMessage());
            $this->redirect('/site/manage');
        }
    }
    
    /**
     * Registra evento de tracking (pageview, click, impression)
     */
    public function trackEvent(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
            
            $userSiteId = $data['user_site_id'] ?? null;
            $postId = $data['post_id'] ?? null;
            $eventType = $data['event_type'] ?? 'pageview';
            $pagePath = $data['page_path'] ?? null;
            $referrer = $data['referrer'] ?? null;
            $utmSource = $data['utm_source'] ?? null;
            $utmMedium = $data['utm_medium'] ?? null;
            $utmCampaign = $data['utm_campaign'] ?? null;
            $utmTerm = $data['utm_term'] ?? null;
            $utmContent = $data['utm_content'] ?? null;
            $userAgent = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            if (!$userSiteId) {
                json_response(['success' => false, 'message' => 'user_site_id é obrigatório'], 400);
                return;
            }
            
            // Detecta device, browser e OS
            $deviceType = SiteAnalytics::detectDeviceType($userAgent);
            $browser = SiteAnalytics::detectBrowser($userAgent);
            $os = SiteAnalytics::detectOS($userAgent);
            
            SiteAnalytics::create([
                'user_site_id' => $userSiteId,
                'site_post_id' => $postId ?: null,
                'event_type' => $eventType,
                'page_path' => $pagePath,
                'referrer' => $referrer,
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_term' => $utmTerm,
                'utm_content' => $utmContent,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_type' => $deviceType,
                'browser' => $browser,
                'os' => $os
            ]);
            
            json_response(['success' => true]);
        } catch (\Exception $e) {
            error_log("Erro ao registrar evento de tracking: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao registrar evento'], 500);
        }
    }
    
    /**
     * Registra pageview (método auxiliar)
     */
    private function trackPageView(int $userSiteId, ?int $postId, string $pagePath): void
    {
        try {
            $referrer = $_SERVER['HTTP_REFERER'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            // Extrai UTM parameters da URL
            $utmSource = $_GET['utm_source'] ?? null;
            $utmMedium = $_GET['utm_medium'] ?? null;
            $utmCampaign = $_GET['utm_campaign'] ?? null;
            $utmTerm = $_GET['utm_term'] ?? null;
            $utmContent = $_GET['utm_content'] ?? null;
            
            // Detecta device, browser e OS
            $deviceType = SiteAnalytics::detectDeviceType($userAgent);
            $browser = SiteAnalytics::detectBrowser($userAgent);
            $os = SiteAnalytics::detectOS($userAgent);
            
            SiteAnalytics::create([
                'user_site_id' => $userSiteId,
                'site_post_id' => $postId,
                'event_type' => 'pageview',
                'page_path' => $pagePath,
                'referrer' => $referrer,
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_term' => $utmTerm,
                'utm_content' => $utmContent,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_type' => $deviceType,
                'browser' => $browser,
                'os' => $os
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao registrar pageview: " . $e->getMessage());
            // Não interrompe o fluxo se falhar
        }
    }
    
    /**
     * Exibe analytics do site
     */
    public function analytics(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        
        $siteRow = $db->queryOne(
            "SELECT * FROM user_sites WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$siteRow) {
            abort(404, 'Site não encontrado.');
        }
        
        $site = UserSite::newInstance($siteRow, true);
        
        // Período padrão: últimos 30 dias
        $days = (int)($this->request->query('days', 30));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Total de visualizações do site
        $totalPageviews = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'pageview' AND site_post_id IS NULL 
             AND created_at >= ?",
            [$site->id, $startDate]
        )['total'] ?? 0;
        
        // Total de visualizações de posts
        $totalPostViews = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'pageview' AND site_post_id IS NOT NULL 
             AND created_at >= ?",
            [$site->id, $startDate]
        )['total'] ?? 0;
        
        // Total de cliques
        $totalClicks = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'click' 
             AND created_at >= ?",
            [$site->id, $startDate]
        )['total'] ?? 0;
        
        // Total de impressões
        $totalImpressions = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'impression' 
             AND created_at >= ?",
            [$site->id, $startDate]
        )['total'] ?? 0;
        
        // CTR (Click-Through Rate)
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        
        // Visualizações por dia (últimos 30 dias)
        $viewsByDay = $db->query(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'pageview' 
             AND created_at >= ? 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            [$site->id, $startDate]
        );
        
        // Top posts por visualizações
        $topPosts = $db->query(
            "SELECT sp.id, sp.title, sp.slug, COUNT(sa.id) as views
             FROM site_posts sp
             INNER JOIN site_analytics sa ON sa.site_post_id = sp.id
             WHERE sp.user_site_id = ? AND sa.event_type = 'pageview' 
             AND sa.created_at >= ?
             GROUP BY sp.id, sp.title, sp.slug
             ORDER BY views DESC
             LIMIT 10",
            [$site->id, $startDate]
        );
        
        // Origem do tráfego
        $trafficSources = $db->query(
            "SELECT 
                CASE 
                    WHEN utm_source IS NOT NULL AND utm_source != '' THEN CONCAT('UTM: ', utm_source)
                    WHEN referrer IS NOT NULL AND referrer != '' THEN 
                        CASE 
                            WHEN referrer LIKE '%google%' THEN 'Google'
                            WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                            WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                            WHEN referrer LIKE '%twitter%' OR referrer LIKE '%x.com%' THEN 'Twitter/X'
                            WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                            WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                            ELSE 'Outros Sites'
                        END
                    ELSE 'Direto'
                END as source,
                COUNT(*) as count
             FROM site_analytics
             WHERE user_site_id = ? AND event_type = 'pageview' 
             AND created_at >= ?
             GROUP BY source
             ORDER BY count DESC",
            [$site->id, $startDate]
        );
        
        // Comparação com período anterior
        $previousStartDate = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
        $previousEndDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $previousPageviews = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'pageview' AND site_post_id IS NULL 
             AND created_at >= ? AND created_at < ?",
            [$site->id, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $previousClicks = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'click' 
             AND created_at >= ? AND created_at < ?",
            [$site->id, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $previousImpressions = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE user_site_id = ? AND event_type = 'impression' 
             AND created_at >= ? AND created_at < ?",
            [$site->id, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $pageviewsDiff = $totalPageviews - $previousPageviews;
        $clicksDiff = $totalClicks - $previousClicks;
        $impressionsDiff = $totalImpressions - $previousImpressions;
        
        // Renderiza view usando o padrão do sistema
        ob_start();
        extract([
            'title' => 'Analytics do Site',
            'site' => $site,
            'days' => $days,
            'metrics' => [
                'pageviews' => $totalPageviews,
                'post_views' => $totalPostViews,
                'clicks' => $totalClicks,
                'impressions' => $totalImpressions,
                'ctr' => $ctr,
                'pageviews_diff' => $pageviewsDiff,
                'clicks_diff' => $clicksDiff,
                'impressions_diff' => $impressionsDiff
            ],
            'viewsByDay' => $viewsByDay,
            'topPosts' => $topPosts,
            'trafficSources' => $trafficSources
        ]);
        include base_path('views/site/manage/analytics.php');
        $content = ob_get_clean();
        
        // Extrai scripts se foram definidos na view
        $scripts = null;
        if (isset($GLOBALS['analytics_scripts'])) {
            $scripts = $GLOBALS['analytics_scripts'];
            unset($GLOBALS['analytics_scripts']);
        }
        
        // Retorna usando o layout padrão do sistema
        return $this->view('layouts/app', [
            'title' => 'Analytics do Site',
            'content' => $content,
            'scripts' => $scripts
        ]);
    }
    
    /**
     * Exibe analytics de um post específico
     */
    public function postAnalytics(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }
        
        $userId = auth()->getDataUserId();
        $postId = $params['id'] ?? null;
        
        if (!$postId) {
            abort(404, 'Post não encontrado.');
        }
        
        $db = \Core\Database::getInstance();
        
        // Busca o post
        $postRow = $db->queryOne(
            "SELECT sp.*, us.user_id 
             FROM site_posts sp
             INNER JOIN user_sites us ON us.id = sp.user_site_id
             WHERE sp.id = ? AND us.user_id = ?",
            [$postId, $userId]
        );
        
        if (!$postRow) {
            abort(404, 'Post não encontrado.');
        }
        
        $post = SitePost::newInstance($postRow, true);
        $site = UserSite::find($postRow['user_site_id']);
        
        // Período padrão: últimos 30 dias
        $days = (int)($this->request->query('days', 30));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Total de visualizações do post
        $totalPageviews = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'pageview' 
             AND created_at >= ?",
            [$postId, $startDate]
        )['total'] ?? 0;
        
        // Total de cliques
        $totalClicks = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'click' 
             AND created_at >= ?",
            [$postId, $startDate]
        )['total'] ?? 0;
        
        // Total de impressões
        $totalImpressions = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'impression' 
             AND created_at >= ?",
            [$postId, $startDate]
        )['total'] ?? 0;
        
        // CTR (Click-Through Rate)
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        
        // Visualizações por dia
        $viewsByDay = $db->query(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'pageview' 
             AND created_at >= ? 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            [$postId, $startDate]
        );
        
        // Origem do tráfego
        $trafficSources = $db->query(
            "SELECT 
                CASE 
                    WHEN utm_source IS NOT NULL AND utm_source != '' THEN CONCAT('UTM: ', utm_source)
                    WHEN referrer IS NOT NULL AND referrer != '' THEN 
                        CASE 
                            WHEN referrer LIKE '%google%' THEN 'Google'
                            WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                            WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                            WHEN referrer LIKE '%twitter%' OR referrer LIKE '%x.com%' THEN 'Twitter/X'
                            WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                            WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                            ELSE 'Outros Sites'
                        END
                    ELSE 'Direto'
                END as source,
                COUNT(*) as count
             FROM site_analytics
             WHERE site_post_id = ? AND event_type = 'pageview' 
             AND created_at >= ?
             GROUP BY source
             ORDER BY count DESC",
            [$postId, $startDate]
        );
        
        // Comparação com período anterior
        $previousStartDate = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
        $previousEndDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $previousPageviews = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'pageview' 
             AND created_at >= ? AND created_at < ?",
            [$postId, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $previousClicks = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'click' 
             AND created_at >= ? AND created_at < ?",
            [$postId, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $previousImpressions = $db->queryOne(
            "SELECT COUNT(*) as total FROM site_analytics 
             WHERE site_post_id = ? AND event_type = 'impression' 
             AND created_at >= ? AND created_at < ?",
            [$postId, $previousStartDate, $previousEndDate]
        )['total'] ?? 0;
        
        $pageviewsDiff = $totalPageviews - $previousPageviews;
        $clicksDiff = $totalClicks - $previousClicks;
        $impressionsDiff = $totalImpressions - $previousImpressions;
        
        // Renderiza view usando o padrão do sistema
        ob_start();
        extract([
            'title' => 'Analytics do Post',
            'site' => $site,
            'post' => $post,
            'days' => $days,
            'metrics' => [
                'pageviews' => $totalPageviews,
                'clicks' => $totalClicks,
                'impressions' => $totalImpressions,
                'ctr' => $ctr,
                'pageviews_diff' => $pageviewsDiff,
                'clicks_diff' => $clicksDiff,
                'impressions_diff' => $impressionsDiff
            ],
            'viewsByDay' => $viewsByDay,
            'trafficSources' => $trafficSources
        ]);
        include base_path('views/site/manage/post-analytics.php');
        $content = ob_get_clean();
        
        // Extrai scripts se foram definidos na view
        $scripts = null;
        if (isset($GLOBALS['analytics_scripts'])) {
            $scripts = $GLOBALS['analytics_scripts'];
            unset($GLOBALS['analytics_scripts']);
        }
        
        // Retorna usando o layout padrão do sistema
        return $this->view('layouts/app', [
            'title' => 'Analytics do Post: ' . $post->title,
            'content' => $content,
            'scripts' => $scripts
        ]);
    }
}

