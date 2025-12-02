<?php
$title = $title ?? 'Quizzes';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-clipboard-list me-2"></i>
                        Quizzes
                    </h4>
                    <a href="<?php echo url('/quizzes/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Novo Quiz
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Link Público</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quizzes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="ti ti-clipboard-list fs-1 d-block mb-2"></i>
                                        Nenhum quiz encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td><?php echo e($quiz->name); ?></td>
                                        <td>
                                            <code><?php echo e($quiz->slug); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($quiz->active): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $quiz->getPublicUrl(); ?>" target="_blank" class="btn btn-sm btn-link">
                                                <i class="ti ti-external-link me-1"></i>
                                                Ver Quiz
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($quiz->created_at)); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo url('/quizzes/' . $quiz->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-success" onclick="copyLink('<?php echo $quiz->getPublicUrl(); ?>')" title="Copiar Link">
                                                    <i class="ti ti-copy"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteQuiz(<?php echo $quiz->id; ?>)" title="Excluir">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        alert('Link copiado para a área de transferência!');
    }).catch(function() {
        // Fallback para navegadores antigos
        const textarea = document.createElement('textarea');
        textarea.value = url;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Link copiado para a área de transferência!');
    });
}

function deleteQuiz(id) {
    if (confirm('Deseja realmente excluir este quiz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo url('/quizzes'); ?>/' + id + '/delete';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_csrf_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

