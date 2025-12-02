<?php
ob_start();
$title = $title ?? $file->name;
?>

<div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8"><?php echo e($file->name); ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="<?php echo url('/drive'); ?>">Drive</a></li>
                        <li class="breadcrumb-item" aria-current="page">Detalhes</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end">
                <a href="<?php echo url('/drive/' . $file->id . '/download'); ?>" class="btn btn-primary">
                    <i class="ti ti-download me-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti <?php echo $file->getIcon(); ?> fs-1 text-primary mb-3"></i>
                <h5><?php echo e($file->name); ?></h5>
                <p class="text-muted"><?php echo $file->getFormattedSize(); ?> • <?php echo strtoupper($file->extension ?? ''); ?></p>
                
                <?php if ($file->description): ?>
                    <div class="alert alert-info mt-3">
                        <?php echo nl2br(e($file->description)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($file->expiration_date): ?>
                    <div class="alert <?php echo $file->isExpired() ? 'alert-danger' : 'alert-warning'; ?> mt-3">
                        <i class="ti ti-alert-circle me-2"></i>
                        <?php if ($file->isExpired()): ?>
                            Vencido em <?php echo date('d/m/Y', strtotime($file->expiration_date)); ?>
                        <?php else: ?>
                            Vence em <?php echo date('d/m/Y', strtotime($file->expiration_date)); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($file->tags())): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Tags</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($file->tags() as $tag): ?>
                        <span class="badge bg-primary me-2"><?php echo e($tag['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Tipo:</th>
                        <td><?php echo e($file->mime_type); ?></td>
                    </tr>
                    <tr>
                        <th>Tamanho:</th>
                        <td><?php echo $file->getFormattedSize(); ?></td>
                    </tr>
                    <tr>
                        <th>Criado:</th>
                        <td><?php echo date('d/m/Y H:i', strtotime($file->created_at)); ?></td>
                    </tr>
                    <tr>
                        <th>Versão:</th>
                        <td><?php echo $file->version; ?></td>
                    </tr>
                    <?php if ($file->client()): ?>
                        <tr>
                            <th>Cliente:</th>
                            <td><?php echo e($file->client()->nome_razao_social); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($file->project()): ?>
                        <tr>
                            <th>Projeto:</th>
                            <td><?php echo e($file->project()->titulo); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($file->responsible()): ?>
                        <tr>
                            <th>Responsável:</th>
                            <td><?php echo e($file->responsible()->name); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

