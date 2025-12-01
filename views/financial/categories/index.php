<?php
$title = $title ?? 'Categorias Financeiras';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-tags me-2"></i>
                        Categorias Financeiras
                    </h4>
                    <a href="<?php echo url('/financial/categories/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Nova Categoria
                    </a>
                </div>

                <!-- Categorias de Entrada -->
                <div class="mb-4">
                    <h5 class="text-success mb-3">
                        <i class="ti ti-arrow-down me-2"></i>
                        Categorias de Entrada
                    </h5>
                    <?php if (empty($categoriesByType['entrada'])): ?>
                        <p class="text-muted">Nenhuma categoria de entrada cadastrada.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($categoriesByType['entrada'] as $category): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div>
                                                    <h6 class="mb-0"><?php echo e($category->name); ?></h6>
                                                    <?php if ($category->color): ?>
                                                        <span class="badge" style="background-color: <?php echo e($category->color); ?>">
                                                            <i class="ti ti-palette"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-success" onclick="addSubcategory(<?php echo $category->id; ?>, '<?php echo e($category->name); ?>')" title="Adicionar Subcategoria">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <a href="<?php echo url('/financial/categories/' . $category->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar Categoria">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <form action="<?php echo url('/financial/categories/' . $category->id . '/delete'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Todas as subcategorias também serão excluídas.');">
                                                        <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir Categoria">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <?php 
                                            $subcategories = $category->subcategories ?? [];
                                            if (!empty($subcategories)): 
                                            ?>
                                                <div class="mt-2">
                                                    <?php foreach ($subcategories as $subcategory): ?>
                                                        <?php 
                                                        $subId = is_array($subcategory) ? $subcategory['id'] : ($subcategory->id ?? 0);
                                                        $subName = is_array($subcategory) ? $subcategory['name'] : ($subcategory->name ?? '');
                                                        ?>
                                                        <span class="badge bg-success-subtle text-success me-1 mb-1 d-inline-flex align-items-center">
                                                            <?php echo e($subName); ?>
                                                            <button class="btn btn-link p-0 ms-1 text-success" style="font-size: 0.7em; line-height: 1;" onclick="editSubcategory(<?php echo $subId; ?>, '<?php echo e($subName); ?>')" title="Editar">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button class="btn btn-link p-0 ms-1 text-danger" style="font-size: 0.7em; line-height: 1;" onclick="deleteSubcategory(<?php echo $subId; ?>)" title="Excluir">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Nenhuma subcategoria</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Categorias de Saída -->
                <div class="mb-4">
                    <h5 class="text-danger mb-3">
                        <i class="ti ti-arrow-up me-2"></i>
                        Categorias de Saída
                    </h5>
                    <?php if (empty($categoriesByType['saida'])): ?>
                        <p class="text-muted">Nenhuma categoria de saída cadastrada.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($categoriesByType['saida'] as $category): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-danger">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div>
                                                    <h6 class="mb-0"><?php echo e($category->name); ?></h6>
                                                    <?php if ($category->color): ?>
                                                        <span class="badge" style="background-color: <?php echo e($category->color); ?>">
                                                            <i class="ti ti-palette"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-danger" onclick="addSubcategory(<?php echo $category->id; ?>, '<?php echo e($category->name); ?>')" title="Adicionar Subcategoria">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <a href="<?php echo url('/financial/categories/' . $category->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar Categoria">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <form action="<?php echo url('/financial/categories/' . $category->id . '/delete'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Todas as subcategorias também serão excluídas.');">
                                                        <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir Categoria">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <?php 
                                            $subcategories = $category->subcategories ?? [];
                                            if (!empty($subcategories)): 
                                            ?>
                                                <div class="mt-2">
                                                    <?php foreach ($subcategories as $subcategory): ?>
                                                        <?php 
                                                        $subId = is_array($subcategory) ? $subcategory['id'] : ($subcategory->id ?? 0);
                                                        $subName = is_array($subcategory) ? $subcategory['name'] : ($subcategory->name ?? '');
                                                        ?>
                                                        <span class="badge bg-danger-subtle text-danger me-1 mb-1 d-inline-flex align-items-center">
                                                            <?php echo e($subName); ?>
                                                            <button class="btn btn-link p-0 ms-1 text-danger" style="font-size: 0.7em; line-height: 1;" onclick="editSubcategory(<?php echo $subId; ?>, '<?php echo e($subName); ?>')" title="Editar">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button class="btn btn-link p-0 ms-1 text-danger" style="font-size: 0.7em; line-height: 1;" onclick="deleteSubcategory(<?php echo $subId; ?>)" title="Excluir">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Nenhuma subcategoria</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Categorias Outras -->
                <?php if (!empty($categoriesByType['outros'])): ?>
                <div class="mb-4">
                    <h5 class="text-info mb-3">
                        <i class="ti ti-category me-2"></i>
                        Outras Categorias
                    </h5>
                    <div class="row">
                        <?php foreach ($categoriesByType['outros'] as $category): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div>
                                                <h6 class="mb-0"><?php echo e($category->name); ?></h6>
                                                <?php if ($category->color): ?>
                                                    <span class="badge" style="background-color: <?php echo e($category->color); ?>">
                                                        <i class="ti ti-palette"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info" onclick="addSubcategory(<?php echo $category->id; ?>, '<?php echo e($category->name); ?>')" title="Adicionar Subcategoria">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                                <a href="<?php echo url('/financial/categories/' . $category->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar Categoria">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <form action="<?php echo url('/financial/categories/' . $category->id . '/delete'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Todas as subcategorias também serão excluídas.');">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir Categoria">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php 
                                        $subcategories = $category->subcategories ?? [];
                                        if (!empty($subcategories)): 
                                        ?>
                                            <div class="mt-2">
                                                <?php foreach ($subcategories as $subcategory): ?>
                                                    <?php 
                                                    $subId = is_array($subcategory) ? $subcategory['id'] : ($subcategory->id ?? 0);
                                                    $subName = is_array($subcategory) ? $subcategory['name'] : ($subcategory->name ?? '');
                                                    ?>
                                                    <span class="badge bg-info-subtle text-info me-1 mb-1 d-inline-flex align-items-center">
                                                        <?php echo e($subName); ?>
                                                        <button class="btn btn-link p-0 ms-1 text-info" style="font-size: 0.7em; line-height: 1;" onclick="editSubcategory(<?php echo $subId; ?>, '<?php echo e($subName); ?>')" title="Editar">
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                        <button class="btn btn-link p-0 ms-1 text-danger" style="font-size: 0.7em; line-height: 1;" onclick="deleteSubcategory(<?php echo $subId; ?>)" title="Excluir">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Nenhuma subcategoria</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar subcategoria -->
<div class="modal fade" id="subcategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subcategoryModalTitle">Adicionar Subcategoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="subcategoryForm" method="POST">
                <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="category_id" id="modal_category_id">
                <input type="hidden" name="id" id="modal_subcategory_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" id="modal_category_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome da Subcategoria *</label>
                        <input type="text" name="name" id="modal_subcategory_name" class="form-control" required placeholder="Ex: Energia Elétrica, Água, Internet">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addSubcategory(categoryId, categoryName) {
    document.getElementById('subcategoryModalTitle').textContent = 'Adicionar Subcategoria';
    document.getElementById('modal_category_id').value = categoryId;
    document.getElementById('modal_category_name').value = categoryName;
    document.getElementById('modal_subcategory_id').value = '';
    document.getElementById('modal_subcategory_name').value = '';
    const modal = new bootstrap.Modal(document.getElementById('subcategoryModal'));
    modal.show();
}

function editSubcategory(subcategoryId, subcategoryName) {
    document.getElementById('subcategoryModalTitle').textContent = 'Editar Subcategoria';
    document.getElementById('modal_subcategory_id').value = subcategoryId;
    document.getElementById('modal_subcategory_name').value = subcategoryName;
    // Busca a categoria da subcategoria
    fetch('<?php echo url('/financial/categories/subcategories/info'); ?>?id=' + subcategoryId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modal_category_id').value = data.category_id;
                document.getElementById('modal_category_name').value = data.category_name;
                const modal = new bootstrap.Modal(document.getElementById('subcategoryModal'));
                modal.show();
            }
        });
}

function deleteSubcategory(subcategoryId) {
    if (confirm('Tem certeza que deseja excluir esta subcategoria?')) {
        const formData = new FormData();
        formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
        formData.append('id', subcategoryId);
        
        fetch('<?php echo url('/financial/categories/subcategories/delete'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
}

document.getElementById('subcategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const subcategoryId = formData.get('id');
    
    let url = '<?php echo url('/financial/categories/subcategories'); ?>';
    if (subcategoryId) {
        url = '<?php echo url('/financial/categories/subcategories/update'); ?>';
    }
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Verifica se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, tenta ler como texto para debug
            return response.text().then(text => {
                console.error('Resposta não é JSON:', text);
                throw new Error('Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao salvar subcategoria:', error);
        alert('Erro ao salvar subcategoria: ' + error.message);
    });
});
</script>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

