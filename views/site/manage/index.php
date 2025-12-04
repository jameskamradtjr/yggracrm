<?php
$title = 'Meu Site';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Meu Site</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Meu Site</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/site/' . $site->slug); ?>" target="_blank" class="btn btn-primary">
                        <i class="ti ti-external-link me-2"></i>Ver Site
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">Configurações do Site</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo url('/site/manage/update'); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">URL do Site</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   value="<?php echo url('/site/' . $site->slug); ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="copyToClipboard('<?php echo url('/site/' . $site->slug); ?>')">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <?php if ($site->logo_url): ?>
                            <div class="mb-2">
                                <img src="<?php echo e($site->logo_url); ?>" 
                                     alt="Logo" 
                                     id="logoPreview" 
                                     style="max-width: 200px; max-height: 100px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" 
                               class="form-control" 
                               id="logo_file" 
                               name="logo_file" 
                               accept="image/*"
                               onchange="previewImage(this, 'logoPreview', 'logo_url')">
                        <small class="text-muted">Ou informe a URL abaixo</small>
                        <input type="text" 
                               class="form-control mt-2" 
                               name="logo_url" 
                               id="logo_url"
                               value="<?php echo e($site->logo_url ?? ''); ?>" 
                               placeholder="https://...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Foto de Perfil</label>
                        <?php if ($site->photo_url): ?>
                            <div class="mb-2">
                                <img src="<?php echo e($site->photo_url); ?>" 
                                     alt="Foto" 
                                     id="photoPreview" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" 
                               class="form-control" 
                               id="photo_file" 
                               name="photo_file" 
                               accept="image/*"
                               onchange="previewImage(this, 'photoPreview', 'photo_url')">
                        <small class="text-muted">Ou informe a URL abaixo</small>
                        <input type="text" 
                               class="form-control mt-2" 
                               name="photo_url" 
                               id="photo_url"
                               value="<?php echo e($site->photo_url ?? ''); ?>" 
                               placeholder="https://...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" 
                                  name="bio" 
                                  rows="4" 
                                  placeholder="Sobre você..."><?php echo e($site->bio ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Twitter (URL)</label>
                        <input type="text" 
                               class="form-control" 
                               name="twitter_url" 
                               value="<?php echo e($site->twitter_url ?? ''); ?>" 
                               placeholder="https://twitter.com/...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">YouTube (URL)</label>
                        <input type="text" 
                               class="form-control" 
                               name="youtube_url" 
                               value="<?php echo e($site->youtube_url ?? ''); ?>" 
                               placeholder="https://youtube.com/...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">LinkedIn (URL)</label>
                        <input type="text" 
                               class="form-control" 
                               name="linkedin_url" 
                               value="<?php echo e($site->linkedin_url ?? ''); ?>" 
                               placeholder="https://linkedin.com/...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instagram (URL)</label>
                        <input type="text" 
                               class="form-control" 
                               name="instagram_url" 
                               value="<?php echo e($site->instagram_url ?? ''); ?>" 
                               placeholder="https://instagram.com/...">
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3">Pixels de Rastreamento</h6>
                    
                    <div class="mb-3">
                        <label for="meta_pixel_id" class="form-label">Meta Pixel ID (Facebook)</label>
                        <input type="text" 
                               class="form-control" 
                               name="meta_pixel_id" 
                               value="<?php echo e($site->meta_pixel_id ?? ''); ?>" 
                               placeholder="Ex: 123456789012345">
                        <small class="text-muted">ID do Pixel do Meta (Facebook) para rastreamento</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                        <input type="text" 
                               class="form-control" 
                               name="google_analytics_id" 
                               value="<?php echo e($site->google_analytics_id ?? ''); ?>" 
                               placeholder="Ex: G-XXXXXXXXXX ou UA-XXXXXXXXX-X">
                        <small class="text-muted">ID do Google Analytics (GA4 ou Universal Analytics)</small>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3">Newsletter</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Título da Newsletter</label>
                        <input type="text" 
                               class="form-control" 
                               name="newsletter_title" 
                               value="<?php echo e($site->newsletter_title ?? 'Newsletter'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição da Newsletter</label>
                        <textarea class="form-control" 
                                  name="newsletter_description" 
                                  rows="3" 
                                  placeholder="Descrição da sua newsletter..."><?php echo e($site->newsletter_description ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-device-floppy me-2"></i>Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Assinantes -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Assinantes da Newsletter</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong><?php echo count($subscribers); ?></strong> assinantes
                </div>
                <?php if (!empty($subscribers)): ?>
                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($subscribers as $subscriber): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo e($subscriber->email); ?></strong>
                                        <?php if ($subscriber->name): ?>
                                            <br><small class="text-muted"><?php echo e($subscriber->name); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($subscriber->created_at)); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum assinante ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Posts</h6>
                <div class="d-flex gap-2">
                    <a href="<?php echo url('/site/manage/analytics'); ?>" class="btn btn-sm btn-info">
                        <i class="ti ti-chart-line me-2"></i>Analytics
                    </a>
                    <a href="<?php echo url('/site/manage/posts/create'); ?>" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Post
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="ti ti-file-text fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum post criado</h5>
                        <p class="text-muted">Comece criando seu primeiro post!</p>
                        <a href="<?php echo url('/site/manage/posts/create'); ?>" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>Criar Post
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Visualizações</th>
                                    <th>Curtidas</th>
                                    <th>Data</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($post->title); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($post->type); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($post->published): ?>
                                                <span class="badge bg-success">Publicado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Rascunho</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $post->views_count ?? 0; ?></td>
                                        <td><?php echo $post->likes_count ?? 0; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($post->created_at)); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="<?php echo url('/site/manage/posts/' . $post->id . '/analytics'); ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Métricas">
                                                    <i class="ti ti-chart-line"></i>
                                                </a>
                                                <a href="<?php echo url('/site/' . $site->slug . '/post/' . $post->slug); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Ver">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <a href="<?php echo url('/site/manage/posts/' . $post->id . '/edit'); ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="excluirPost(<?php echo $post->id; ?>, '<?php echo e($post->title); ?>')" 
                                                        title="Excluir">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copiada para a área de transferência!');
    }, function(err) {
        console.error('Erro ao copiar:', err);
    });
}

function previewImage(input, previewId, urlInputId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            let preview = document.getElementById(previewId);
            if (!preview) {
                // Cria elemento de preview se não existir
                preview = document.createElement('img');
                preview.id = previewId;
                preview.style.maxWidth = previewId === 'logoPreview' ? '200px' : '150px';
                preview.style.maxHeight = previewId === 'logoPreview' ? '100px' : '150px';
                preview.style.objectFit = previewId === 'logoPreview' ? 'contain' : 'cover';
                preview.style.border = '1px solid #ddd';
                preview.style.padding = '5px';
                preview.style.borderRadius = previewId === 'photoPreview' ? '50%' : '4px';
                preview.style.marginBottom = '10px';
                
                const container = input.parentElement;
                container.insertBefore(preview, input);
            }
            
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Limpa o campo de URL quando uma imagem é selecionada
            const urlInput = document.getElementById(urlInputId);
            if (urlInput) {
                urlInput.value = '';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function excluirPost(id, titulo) {
    if (!confirm(`Tem certeza que deseja excluir o post "${titulo}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url('/site/manage/posts'); ?>/' + id + '/delete';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_csrf_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

