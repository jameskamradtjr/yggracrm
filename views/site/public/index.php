<?php
$title = ($site->user()->name ?? 'Site') . ' - ' . config('app.name');

ob_start();
?>

<div class="site-container">
    <!-- Header com Logo -->
    <div class="site-header">
        <?php if ($site->logo_url): ?>
            <img src="<?php echo e($site->logo_url); ?>" alt="Logo" class="site-logo">
        <?php else: ?>
            <h1 style="font-size: 1.5rem; font-weight: 600;"><?php echo e($site->user()->name ?? 'Site'); ?></h1>
        <?php endif; ?>
    </div>
    
    <!-- Seção Principal: Perfil + Feed -->
    <div class="profile-section">
        <!-- Lado Esquerdo: Perfil -->
        <div class="profile-left">
            <?php if ($site->photo_url): ?>
                <img src="<?php echo e($site->photo_url); ?>" alt="Foto" class="profile-photo">
            <?php endif; ?>
            
            <?php if ($site->bio): ?>
                <div class="profile-bio">
                    <?php echo nl2br(e($site->bio)); ?>
                </div>
            <?php endif; ?>
            
            <div class="social-links">
                <?php if ($site->twitter_url): ?>
                    <a href="<?php echo e($site->twitter_url); ?>" target="_blank" rel="noopener">
                        <i class="ri-twitter-fill"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($site->youtube_url): ?>
                    <a href="<?php echo e($site->youtube_url); ?>" target="_blank" rel="noopener">
                        <i class="ri-youtube-fill"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($site->linkedin_url): ?>
                    <a href="<?php echo e($site->linkedin_url); ?>" target="_blank" rel="noopener">
                        <i class="ri-linkedin-fill"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($site->instagram_url): ?>
                    <a href="<?php echo e($site->instagram_url); ?>" target="_blank" rel="noopener">
                        <i class="ri-instagram-fill"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lado Direito: Feed -->
        <div class="feed-section">
            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">Nenhum post publicado ainda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card" 
                         data-post-id="<?php echo $post->id; ?>"
                         onclick="window.location.href='<?php echo url('/site/' . $site->slug . '/post/' . $post->slug); ?>'">
                        <div class="post-date">
                            <?php echo date('d/m/Y', strtotime($post->published_at ?? $post->created_at)); ?>
                        </div>
                        
                        <h2 class="post-title"><?php echo e($post->title); ?></h2>
                        
                        <?php if ($post->excerpt): ?>
                            <p class="post-excerpt"><?php echo e($post->excerpt); ?></p>
                        <?php endif; ?>
                        
                        <div class="post-meta">
                            <span>
                                <i class="ri-eye-line"></i> <?php echo $post->views_count ?? 0; ?> visualizações
                            </span>
                            <span class="post-likes">
                                <i class="ri-heart-line"></i> <?php echo $post->likes_count ?? 0; ?> curtidas
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('/site/' . $site->slug . '?page=' . ($currentPage - 1)); ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('/site/' . $site->slug . '?page=' . $i); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo url('/site/' . $site->slug . '?page=' . ($currentPage + 1)); ?>">Próxima</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Newsletter -->
    <div class="newsletter-section">
        <h3 class="newsletter-title"><?php echo e($site->newsletter_title ?? 'Newsletter'); ?></h3>
        <?php if ($site->newsletter_description): ?>
            <p class="newsletter-description"><?php echo e($site->newsletter_description); ?></p>
        <?php endif; ?>
        
        <form id="newsletterForm" class="d-flex gap-2">
            <input type="text" 
                   class="form-control" 
                   id="subscriberName" 
                   placeholder="Seu nome (opcional)"
                   style="max-width: 200px;">
            <input type="email" 
                   class="form-control" 
                   id="subscriberEmail" 
                   placeholder="Seu email"
                   required
                   style="flex: 1;">
            <button type="submit" class="btn btn-primary">Inscrever-se</button>
        </form>
        <div id="newsletterMessage" class="mt-2"></div>
    </div>
</div>

<script>
document.getElementById('newsletterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const name = document.getElementById('subscriberName').value;
    const email = document.getElementById('subscriberEmail').value;
    const messageDiv = document.getElementById('newsletterMessage');
    
    if (!email) {
        messageDiv.innerHTML = '<div class="alert alert-danger">Por favor, informe seu email.</div>';
        return;
    }
    
    try {
        const response = await fetch('<?php echo url('/site/' . $site->slug . '/newsletter/subscribe'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ name, email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">Inscrição realizada com sucesso!</div>';
            document.getElementById('newsletterForm').reset();
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Erro ao inscrever-se') + '</div>';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="alert alert-danger">Erro ao processar inscrição. Tente novamente.</div>';
    }
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/public.php');
?>

