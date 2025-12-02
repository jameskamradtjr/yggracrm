<?php
ob_start();
$title = $title ?? 'Nova Proposta';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Nova Proposta</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/proposals'); ?>">Propostas</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Nova Proposta</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/proposals'); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Cliente</label>
                    <?php 
                    $id = 'client_id';
                    $name = 'client_id';
                    $placeholder = 'Digite para buscar cliente...';
                    include base_path('views/components/tom-select-client.php'); 
                    ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="project_id" class="form-label">Projeto</label>
                    <select name="project_id" id="project_id" class="form-select">
                        <option value="">Selecione um projeto...</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project->id; ?>"><?php echo e($project->titulo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="titulo" class="form-label">Título da Proposta <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="identificacao" class="form-label">Identificação da Proposta</label>
                    <input type="text" class="form-control" id="identificacao" name="identificacao" placeholder="Exemplo: 2023-02, Desconto de 10%, Plano Gold, Versão 1, etc...">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="objetivo" class="form-label">Objetivo</label>
                    <textarea class="form-control" id="objetivo" name="objetivo" rows="3" placeholder="Qual o objetivo do cliente com este projeto?"></textarea>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="apresentacao" class="form-label">Apresentação</label>
                    <textarea class="form-control" id="apresentacao" name="apresentacao" rows="5" placeholder="Apresente o seu negócio ou você como..."></textarea>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="duracao_dias" class="form-label">Duração (dias)</label>
                    <input type="number" class="form-control" id="duracao_dias" name="duracao_dias" min="1">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="desconto_percentual" class="form-label">Desconto (%)</label>
                    <input type="number" class="form-control" id="desconto_percentual" name="desconto_percentual" value="0" min="0" max="100" step="0.01">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="data_validade" class="form-label">Data de Validade</label>
                    <input type="date" class="form-control" id="data_validade" name="data_validade">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="disponibilidade_inicio_imediato" name="disponibilidade_inicio_imediato" value="1">
                        <label class="form-check-label" for="disponibilidade_inicio_imediato">
                            Disponibilidade para início imediato
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo url('/proposals'); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar Proposta</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// Tom Select Scripts
$scripts = '';
if (isset($GLOBALS['tom_select_inits'])) {
    ob_start();
    include base_path('views/components/tom-select-scripts.php');
    $scripts = ob_get_clean();
}

include base_path('views/layouts/app.php');
?>

