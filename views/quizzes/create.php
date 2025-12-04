<?php
$title = $title ?? 'Criar Quiz';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-clipboard-list me-2"></i>
                    Criar Novo Quiz
                </h4>

                <form method="POST" action="<?php echo url('/quizzes'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nome do Quiz *</label>
                            <input type="text" name="name" class="form-control" required placeholder="Ex: Quiz de Captação de Leads">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Descreva o objetivo deste quiz"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tag Padrão</label>
                            <input type="text" name="default_tag_name" class="form-control" placeholder="Digite o nome da tag...">
                            <small class="text-muted">Tag que será aplicada automaticamente aos leads deste quiz (será criada se não existir)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="hidden" name="active" value="1">
                                <input type="checkbox" name="active" class="form-check-input" id="active" value="1" checked>
                                <label class="form-check-label" for="active">
                                    Quiz Ativo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Personalização de Cores</h5>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor Principal</label>
                            <input type="color" name="primary_color" class="form-control form-control-color" value="#007bff">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor Secundária</label>
                            <input type="color" name="secondary_color" class="form-control form-control-color" value="#6c757d">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor do Texto</label>
                            <input type="color" name="text_color" class="form-control form-control-color" value="#212529">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor de Fundo</label>
                            <input type="color" name="background_color" class="form-control form-control-color" value="#ffffff">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor do Botão</label>
                            <input type="color" name="button_color" class="form-control form-control-color" value="#007bff">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor do Texto do Botão</label>
                            <input type="color" name="button_text_color" class="form-control form-control-color" value="#ffffff">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cor do Hover do Botão</label>
                            <input type="color" name="button_hover_color" class="form-control form-control-color" value="#0056b3">
                            <small class="text-muted">Cor quando o mouse passa sobre o botão</small>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Mensagens</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mensagem de Boas-vindas</label>
                            <textarea name="welcome_message" class="form-control" rows="3" placeholder="Mensagem exibida no início do quiz"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mensagem de Conclusão</label>
                            <textarea name="completion_message" class="form-control" rows="3" placeholder="Mensagem exibida após completar o quiz"></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">URL do Logo (opcional)</label>
                            <input type="url" name="logo_url" class="form-control" placeholder="https://exemplo.com/logo.png">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Criar Quiz
                        </button>
                        <a href="<?php echo url('/quizzes'); ?>" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

