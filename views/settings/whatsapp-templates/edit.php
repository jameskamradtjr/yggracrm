<?php
$title = 'Editar Template de WhatsApp';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Editar Template de WhatsApp</h4>
                        <p class="card-subtitle mb-0">Edite o template de mensagem WhatsApp</p>
                    </div>
                    <a href="<?php echo url('/settings?tab=whatsapp-templates'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/settings/whatsapp-templates/' . $template->id); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome do Template <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo e(old('name', $template->name)); ?>" 
                                   required>
                            <small class="text-muted">Ex: Mensagem de Boas-vindas</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   value="<?php echo e(old('slug', $template->slug)); ?>" 
                                   required>
                            <small class="text-muted">Ex: welcome-whatsapp (sem espaços, minúsculas)</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="message" class="form-label">Mensagem <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="10" 
                                      required><?php echo e(old('message', $template->message)); ?></textarea>
                            <small class="text-muted">
                                Use {{variavel}} para variáveis dinâmicas. Ex: Olá {{nome}}, bem-vindo!
                            </small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="variables" class="form-label">Variáveis Disponíveis</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="variables" 
                                   name="variables" 
                                   value="<?php echo e(old('variables', is_array($template->getVariables()) ? implode(', ', $template->getVariables()) : '')); ?>" 
                                   placeholder="nome, telefone, email">
                            <small class="text-muted">Separe as variáveis por vírgula (sem espaços)</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1" 
                                       <?php echo old('is_active', $template->is_active) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Template ativo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?php echo url('/settings?tab=whatsapp-templates'); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Atualizar Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>

