<?php
$title = 'Editar Lead';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-semibold mb-0">Editar Lead</h4>
                    <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/leads/' . $lead->id . '/update'); ?>" method="POST" id="formEditLead">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Informações Básicas</h5>
                            
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Cliente Existente (Opcional)</label>
                                <?php 
                                $id = 'client_id';
                                $name = 'client_id';
                                $placeholder = 'Digite para buscar cliente...';
                                $selected = $lead->client_id ?? '';
                                include base_path('views/components/tom-select-client.php'); 
                                ?>
                                <small class="text-muted">Se selecionar um cliente, o lead será vinculado a ele.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nome" 
                                           name="nome" 
                                           value="<?php echo e($lead->nome); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo e($lead->email); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefone" 
                                           name="telefone" 
                                           value="<?php echo e($lead->telefone); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">Instagram</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="instagram" 
                                           name="instagram" 
                                           value="<?php echo e($lead->instagram ?? ''); ?>" 
                                           placeholder="@seuinstagram">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">Detalhes Adicionais</h5>
                            
                            <div class="mb-3">
                                <label for="valor_oportunidade" class="form-label">Valor da Oportunidade (R$)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="valor_oportunidade" 
                                       name="valor_oportunidade" 
                                       value="<?php echo $lead->valor_oportunidade ?? ''; ?>" 
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                <small class="text-muted">Valor estimado da oportunidade de negócio</small>
                            </div>

                            <div class="mb-3">
                                <label for="objetivo" class="form-label">Objetivo Principal</label>
                                <textarea class="form-control" 
                                          id="objetivo" 
                                          name="objetivo" 
                                          rows="4" 
                                          placeholder="Descreva o objetivo principal do lead..."><?php echo e($lead->objetivo ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-light">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-2"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Inclui scripts do Tom Select
include base_path('views/components/tom-select-scripts.php');
?>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

