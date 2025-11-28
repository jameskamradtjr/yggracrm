<?php
$title = 'Criar Template de Email';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Criar Template de Email</h4>
                        <p class="card-subtitle mb-0">Crie um novo template para envio de emails</p>
                    </div>
                    <a href="<?php echo url('/settings?tab=templates'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/settings/templates'); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome do Template <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo e(old('name')); ?>" 
                                   required>
                            <small class="text-muted">Ex: Email de Boas-vindas</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   value="<?php echo e(old('slug')); ?>" 
                                   required>
                            <small class="text-muted">Ex: welcome-email (sem espaços, minúsculas)</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="subject" class="form-label">Assunto <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="subject" 
                                   name="subject" 
                                   value="<?php echo e(old('subject')); ?>" 
                                   required>
                            <small class="text-muted">Use {{variavel}} para variáveis dinâmicas</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="body" class="form-label">Corpo do Email (HTML) <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="body" 
                                      name="body" 
                                      rows="15" 
                                      required><?php echo e(old('body')); ?></textarea>
                            <small class="text-muted">
                                Use HTML para formatar o email. Variáveis: {{variavel}}
                            </small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="variables" class="form-label">Variáveis Disponíveis</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="variables" 
                                   name="variables" 
                                   value="<?php echo e(old('variables')); ?>" 
                                   placeholder="nome, email, senha">
                            <small class="text-muted">Separe as variáveis por vírgula (sem espaços)</small>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?php echo url('/settings?tab=templates'); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Criar Template</button>
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

