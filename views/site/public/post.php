<?php
$title = $post->title . ' - ' . ($site->user()->name ?? 'Site');

ob_start();
?>

<div class="site-container">
    <!-- Header com Logo -->
    <div class="site-header">
        <a href="<?php echo url('/site/' . $site->slug); ?>" style="text-decoration: none; color: inherit;">
            <?php if ($site->logo_url): ?>
                <img src="<?php echo e($site->logo_url); ?>" alt="Logo" class="site-logo">
            <?php else: ?>
                <h1 style="font-size: 1.5rem; font-weight: 600;"><?php echo e($site->user()->name ?? 'Site'); ?></h1>
            <?php endif; ?>
        </a>
    </div>
    
    <!-- Post -->
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <article>
                <div class="post-date mb-3">
                    <?php echo date('d/m/Y', strtotime($post->published_at ?? $post->created_at)); ?>
                </div>
                
                <h1 class="mb-4" style="font-size: 2.5rem; font-weight: 700; line-height: 1.2;">
                    <?php echo e($post->title); ?>
                </h1>
                
                <?php if ($post->excerpt): ?>
                    <p class="lead mb-4" style="font-size: 1.25rem; color: #666;">
                        <?php echo e($post->excerpt); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Conteúdo do Post -->
                <div class="post-content mb-5" style="line-height: 1.8; font-size: 1.1rem;">
                    <?php if ($post->type === 'youtube' && $post->external_url): ?>
                        <?php $videoId = $post->getYoutubeVideoId(); ?>
                        <?php if ($videoId): ?>
                            <div class="ratio ratio-16x9 mb-4">
                                <iframe src="https://www.youtube.com/embed/<?php echo e($videoId); ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                        <?php if ($post->content): ?>
                            <div class="mt-4">
                                <?php echo $post->content; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($post->type === 'twitter' && $post->external_url): ?>
                        <?php $tweetId = $post->getTwitterTweetId(); ?>
                        <?php if ($tweetId): ?>
                            <blockquote class="twitter-tweet" data-theme="light">
                                <a href="<?php echo e($post->external_url); ?>"></a>
                            </blockquote>
                            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                        <?php endif; ?>
                        <?php if ($post->content): ?>
                            <div class="mt-4">
                                <?php echo $post->content; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo $post->content; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Meta do Post -->
                <div class="post-meta mb-5 pb-4 border-bottom">
                    <div class="d-flex align-items-center gap-4">
                        <div class="post-likes" style="cursor: pointer;" onclick="toggleLike(<?php echo $post->id; ?>)">
                            <i class="ri-heart-<?php echo $hasLiked ? 'fill' : 'line'; ?>" id="likeIcon-<?php echo $post->id; ?>" style="color: <?php echo $hasLiked ? '#dc3545' : '#999'; ?>;"></i>
                            <span id="likesCount-<?php echo $post->id; ?>"><?php echo $post->likes_count ?? 0; ?></span>
                        </div>
                        <div>
                            <i class="ri-eye-line"></i> <?php echo $post->views_count ?? 0; ?> visualizações
                        </div>
                    </div>
                </div>
                
                <!-- Voltar -->
                <div class="mt-4">
                    <a href="<?php echo url('/site/' . $site->slug); ?>" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-2"></i>Voltar ao feed
                    </a>
                </div>
            </article>
        </div>
    </div>
</div>

<script>
async function toggleLike(postId) {
    try {
        const response = await fetch('<?php echo url('/site/post'); ?>/' + postId + '/like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const icon = document.getElementById('likeIcon-' + postId);
            const count = document.getElementById('likesCount-' + postId);
            
            if (data.liked) {
                icon.className = 'ri-heart-fill';
                icon.style.color = '#dc3545';
            } else {
                icon.className = 'ri-heart-line';
                icon.style.color = '#999';
            }
            
            count.textContent = data.likes_count;
        }
    } catch (error) {
        console.error('Erro ao curtir:', error);
    }
}
</script>

<style>
.post-content h1,
.post-content h2,
.post-content h3,
.post-content h4,
.post-content h5,
.post-content h6 {
    margin-top: 2em;
    margin-bottom: 1em;
    font-weight: 600;
}

.post-content p {
    margin-bottom: 1.5em;
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5em 0;
}

.post-content blockquote {
    border-left: 4px solid #007bff;
    padding-left: 1.5em;
    margin: 1.5em 0;
    color: #666;
    font-style: italic;
}

.post-content code {
    background-color: #f4f4f4;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.post-content pre {
    background-color: #f4f4f4;
    padding: 1.5em;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1.5em 0;
}

.post-content pre code {
    background-color: transparent;
    padding: 0;
}
</style>

<?php
$content = ob_get_clean();
include base_path('views/layouts/public.php');
?>

