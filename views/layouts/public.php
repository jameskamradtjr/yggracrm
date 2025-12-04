<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo $title ?? 'Site'; ?></title>
    
    <?php if (isset($site) && $site->google_analytics_id): ?>
        <!-- Google Analytics -->
        <?php if (str_starts_with($site->google_analytics_id, 'G-')): ?>
            <!-- Google Analytics 4 (GA4) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($site->google_analytics_id); ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '<?php echo e($site->google_analytics_id); ?>');
            </script>
        <?php else: ?>
            <!-- Universal Analytics (Legacy) -->
            <script>
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
                ga('create', '<?php echo e($site->google_analytics_id); ?>', 'auto');
                ga('send', 'pageview');
            </script>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($site) && $site->meta_pixel_id): ?>
        <!-- Meta Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '<?php echo e($site->meta_pixel_id); ?>');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id=<?php echo e($site->meta_pixel_id); ?>&ev=PageView&noscript=1"/>
        </noscript>
    <?php endif; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
        }
        
        .site-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .site-header {
            margin-bottom: 60px;
        }
        
        .site-logo {
            max-height: 50px;
            max-width: 200px;
        }
        
        .profile-section {
            display: flex;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .profile-left {
            flex: 0 0 300px;
        }
        
        .profile-photo {
            width: 111px;
            height: 111px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }
        
        .profile-bio {
            margin-bottom: 20px;
            color: #666;
            line-height: 1.8;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .social-links a {
            color: #1a1a1a;
            font-size: 24px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .social-links a:hover {
            opacity: 0.7;
        }
        
        .feed-section {
            flex: 1;
        }
        
        .post-card {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            transition: box-shadow 0.2s;
            cursor: pointer;
        }
        
        .post-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .post-date {
            color: #999;
            font-size: 0.875rem;
            margin-bottom: 10px;
        }
        
        .post-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        
        .post-excerpt {
            color: #666;
            margin-bottom: 15px;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #999;
            font-size: 0.875rem;
        }
        
        .post-likes {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination {
            margin-top: 40px;
        }
        
        .newsletter-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 40px;
            margin-top: 60px;
        }
        
        .newsletter-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .newsletter-description {
            color: #666;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .profile-section {
                flex-direction: column;
            }
            
            .profile-left {
                flex: 1;
            }
        }
    </style>
    <?php echo $styles ?? ''; ?>
</head>
<body>
    <?php echo $content ?? ''; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo $scripts ?? ''; ?>
    
    <?php if (isset($site)): ?>
    <!-- Site Analytics Tracking -->
    <script>
        (function() {
            const siteId = <?php echo $site->id; ?>;
            const currentPath = window.location.pathname;
            const currentUrl = new URL(window.location.href);
            
            // Extrai UTM parameters
            const utmParams = {
                utm_source: currentUrl.searchParams.get('utm_source'),
                utm_medium: currentUrl.searchParams.get('utm_medium'),
                utm_campaign: currentUrl.searchParams.get('utm_campaign'),
                utm_term: currentUrl.searchParams.get('utm_term'),
                utm_content: currentUrl.searchParams.get('utm_content')
            };
            
            // Função para enviar evento de tracking
            function trackEvent(eventType, postId = null, additionalData = {}) {
                const data = {
                    user_site_id: siteId,
                    post_id: postId,
                    event_type: eventType,
                    page_path: currentPath,
                    referrer: document.referrer || null,
                    user_agent: navigator.userAgent,
                    ...utmParams,
                    ...additionalData
                };
                
                // Envia de forma assíncrona (não bloqueia a página)
                fetch('<?php echo url('/api/site/track'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                }).catch(err => {
                    // Silenciosamente ignora erros de tracking
                    console.debug('Tracking error:', err);
                });
            }
            
            // Rastreia cliques em posts (no feed)
            document.addEventListener('click', function(e) {
                const postLink = e.target.closest('a[href*="/post/"]');
                if (postLink) {
                    const href = postLink.getAttribute('href');
                    const postIdMatch = href.match(/\/post\/([^\/]+)/);
                    if (postIdMatch) {
                        // Tenta encontrar o post_id do data attribute ou do elemento
                        const postCard = postLink.closest('[data-post-id]');
                        const postId = postCard ? postCard.getAttribute('data-post-id') : null;
                        if (postId) {
                            trackEvent('click', postId);
                        }
                    }
                }
                
                // Rastreia cliques em cards de posts (onclick)
                const postCard = e.target.closest('[data-post-id]');
                if (postCard && postCard.onclick) {
                    const postId = postCard.getAttribute('data-post-id');
                    if (postId) {
                        trackEvent('click', postId);
                    }
                }
            });
            
            // Rastreia impressões de posts (quando aparecem na tela)
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const postElement = entry.target;
                            const postId = postElement.getAttribute('data-post-id');
                            if (postId && !postElement.dataset.impressionTracked) {
                                postElement.dataset.impressionTracked = 'true';
                                trackEvent('impression', postId);
                            }
                        }
                    });
                }, {
                    threshold: 0.5 // 50% do elemento visível
                });
                
                // Observa todos os cards de posts
                document.querySelectorAll('[data-post-id]').forEach(function(postElement) {
                    observer.observe(postElement);
                });
            }
        })();
    </script>
    <?php endif; ?>
</body>
</html>

