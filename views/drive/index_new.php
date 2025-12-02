<?php
ob_start();
$title = $title ?? 'Drive';
?>

<div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Drive</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a></li>
                        <?php if ($currentFolder): ?>
                            <?php foreach ($currentFolder->getPath() as $folder): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo url('/drive?folder=' . $folder['id']); ?>" class="text-muted text-decoration-none">
                                        <?php echo e($folder['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="breadcrumb-item" aria-current="page">Drive</li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="ti ti-upload me-1"></i> Upload
                </button>
                <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                    <i class="ti ti-folder-plus me-1"></i> Nova Pasta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-files fs-7 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0"><?php echo $stats['total_files']; ?></h6>
                        <small class="text-muted">Total de Arquivos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-database fs-7 text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0"><?php echo number_format($stats['total_size'] / 1048576, 2); ?> MB</h6>
                        <small class="text-muted">Espaço Usado</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-star fs-7 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-0"><?php echo $stats['favorites']; ?></h6>
                        <small class="text-muted">Favoritos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-share fs-7 text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-0"><?php echo $stats['shared']; ?></h6>
                        <small class="text-muted">Compartilhados</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="<?php echo url('/drive' . ($currentFolder ? '?folder=' . $currentFolder->id : '')); ?>" class="btn btn-sm <?php echo !$filter ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-folder me-1"></i> Todos
            </a>
            <a href="<?php echo url('/drive?filter=favorites'); ?>" class="btn btn-sm <?php echo $filter === 'favorites' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-star me-1"></i> Favoritos
            </a>
            <a href="<?php echo url('/drive?filter=shared'); ?>" class="btn btn-sm <?php echo $filter === 'shared' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-share me-1"></i> Compartilhados
            </a>
            <a href="<?php echo url('/drive?filter=recent'); ?>" class="btn btn-sm <?php echo $filter === 'recent' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-clock me-1"></i> Recentes
            </a>
            <a href="<?php echo url('/drive?filter=expiring'); ?>" class="btn btn-sm <?php echo $filter === 'expiring' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-alert-circle me-1"></i> Vencendo
            </a>
        </div>
        <div class="btn-group ms-3" role="group">
            <a href="<?php echo url('/drive?view=grid' . ($currentFolder ? '&folder=' . $currentFolder->id : '') . ($filter ? '&filter=' . $filter : '')); ?>" class="btn btn-sm <?php echo $view === 'grid' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-grid-dots"></i>
            </a>
            <a href="<?php echo url('/drive?view=list' . ($currentFolder ? '&folder=' . $currentFolder->id : '') . ($filter ? '&filter=' . $filter : '')); ?>" class="btn btn-sm <?php echo $view === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="ti ti-list"></i>
            </a>
        </div>
    </div>
</div>

<!-- Pastas e Arquivos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($folders) && empty($files)): ?>
            <div class="text-center py-5">
                <i class="ti ti-folder-off fs-1 text-muted mb-3"></i>
                <p class="text-muted">Nenhum arquivo ou pasta encontrado</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="ti ti-upload me-1"></i> Fazer Upload
                </button>
            </div>
        <?php else: ?>
            <?php if ($view === 'grid'): ?>
                <!-- Visualização em Grade -->
                <div class="row g-3">
                    <?php foreach ($folders as $folder): ?>
                        <div class="col-md-2">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <a href="<?php echo url('/drive?folder=' . $folder->id); ?>" class="text-decoration-none">
                                        <i class="ti ti-folder fs-1 <?php echo $folder->color ? 'text-' . $folder->color : 'text-warning'; ?>"></i>
                                        <p class="mb-0 mt-2 small"><?php echo e($folder->name); ?></p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($files as $file): ?>
                        <div class="col-md-2">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <a href="<?php echo url('/drive/' . $file->id); ?>" class="text-decoration-none">
                                        <i class="ti <?php echo $file->getIcon(); ?> fs-1 text-primary"></i>
                                        <p class="mb-0 mt-2 small text-truncate" title="<?php echo e($file->name); ?>"><?php echo e($file->name); ?></p>
                                        <small class="text-muted"><?php echo $file->getFormattedSize(); ?></small>
                                    </a>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-link" onclick="toggleFavorite(<?php echo $file->id; ?>)">
                                            <i class="ti ti-star<?php echo $file->is_favorite ? '-filled text-warning' : ''; ?>"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Visualização em Lista -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Tamanho</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($folders as $folder): ?>
                                <tr>
                                    <td>
                                        <i class="ti ti-folder <?php echo $folder->color ? 'text-' . $folder->color : 'text-warning'; ?> me-2"></i>
                                        <a href="<?php echo url('/drive?folder=' . $folder->id); ?>"><?php echo e($folder->name); ?></a>
                                    </td>
                                    <td>Pasta</td>
                                    <td>-</td>
                                    <td><?php echo date('d/m/Y', strtotime($folder->created_at)); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deleteFolder(<?php echo $folder->id; ?>)">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td>
                                        <i class="ti <?php echo $file->getIcon(); ?> text-primary me-2"></i>
                                        <a href="<?php echo url('/drive/' . $file->id); ?>"><?php echo e($file->name); ?></a>
                                        <?php if ($file->is_favorite): ?>
                                            <i class="ti ti-star-filled text-warning ms-2"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo strtoupper($file->extension ?? '-'); ?></td>
                                    <td><?php echo $file->getFormattedSize(); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($file->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo url('/drive/' . $file->id . '/download'); ?>" class="btn btn-sm btn-primary">
                                            <i class="ti ti-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="trashFile(<?php echo $file->id); ?>)">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload de Arquivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="folder_id" value="<?php echo $currentFolder->id ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Arquivo *</label>
                        <input type="file" class="form-control" name="file" required>
                        <small class="text-muted">Tamanho máximo: 50MB</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cliente</label>
                            <select class="form-control" id="select2-client" name="client_id"></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Projeto</label>
                            <select class="form-control" name="project_id">
                                <option value="">Nenhum</option>
                                <?php foreach ($projects ?? [] as $project): ?>
                                    <option value="<?php echo $project->id; ?>"><?php echo e($project->titulo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsável</label>
                            <select class="form-control" id="select2-user" name="responsible_user_id"></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Vencimento</label>
                            <input type="date" class="form-control" name="expiration_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <div id="tags-container" class="tags-input-container">
                            <div class="tags-list" id="tags-list"></div>
                            <input type="text" id="tags-input" class="tags-input" placeholder="Digite e pressione Enter ou vírgula">
                        </div>
                        <input type="hidden" name="tags" id="tags-hidden">
                        <small class="text-muted">Digite e pressione Enter ou vírgula para adicionar tags</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitUpload()">Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Pasta -->
<div class="modal fade" id="newFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Pasta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newFolderForm">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo $currentFolder->id ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Pasta *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cor</label>
                        <select class="form-select" name="color">
                            <option value="">Padrão</option>
                            <option value="primary">Azul</option>
                            <option value="success">Verde</option>
                            <option value="danger">Vermelho</option>
                            <option value="warning">Amarelo</option>
                            <option value="info">Ciano</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitNewFolder()">Criar</button>
            </div>
        </div>
    </div>
</div>

<style>
.tags-input-container {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    min-height: 38px;
    background-color: #fff;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.tags-input {
    border: none;
    outline: none;
    padding: 0.25rem;
    flex: 1;
    min-width: 120px;
}

.tags-input:focus {
    outline: none;
}

.tags-input-container:focus-within {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Select2 z-index para modal */
.select2-dropdown {
    z-index: 10000 !important;
}
</style>

<?php
$content = ob_get_clean();

// Scripts adicionais
$scripts = '
<link rel="stylesheet" href="' . asset('/tema/assets/libs/select2/dist/css/select2.min.css') . '">
<script src="' . asset('/tema/assets/libs/select2/dist/js/select2.full.min.js') . '"></script>
<script>
// Componente de Tags
(function() {
    const tagsList = document.getElementById("tags-list");
    const tagsInput = document.getElementById("tags-input");
    const tagsHidden = document.getElementById("tags-hidden");
    let tags = [];
    
    function updateHiddenInput() {
        tagsHidden.value = tags.join(",");
    }
    
    function addTag(tagText) {
        tagText = tagText.trim();
        if (tagText && !tags.includes(tagText)) {
            tags.push(tagText);
            const tagElement = document.createElement("span");
            tagElement.className = "badge bg-primary me-1 mb-1";
            tagElement.innerHTML = tagText + " <button type=\"button\" class=\"btn-close btn-close-white ms-1\" style=\"font-size: 0.7em;\" onclick=\"removeTag(\'" + tagText.replace(/\'/g, "\\\\'") + "\')\"></button>";
            tagsList.appendChild(tagElement);
            updateHiddenInput();
        }
    }
    
    function removeTag(tagText) {
        tags = tags.filter(t => t !== tagText);
        tagsList.innerHTML = "";
        tags.forEach(tag => {
            const tagElement = document.createElement("span");
            tagElement.className = "badge bg-primary me-1 mb-1";
            tagElement.innerHTML = tag + " <button type=\"button\" class=\"btn-close btn-close-white ms-1\" style=\"font-size: 0.7em;\" onclick=\"removeTag(\'" + tag.replace(/\'/g, "\\\\'") + "\')\"></button>";
            tagsList.appendChild(tagElement);
        });
        updateHiddenInput();
    }
    
    window.removeTag = removeTag;
    
    if (tagsInput) {
        tagsInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addTag(value);
                    this.value = "";
                }
            }
        });
        
        tagsInput.addEventListener("blur", function() {
            const value = this.value.trim();
            if (value) {
                addTag(value);
                this.value = "";
            }
        });
    }
})();

// Select2 para clientes (AJAX) - Padrão do tema
$("#select2-client").select2({
    dropdownParent: $("#uploadModal"),
    placeholder: "Buscar cliente...",
    allowClear: true,
    ajax: {
        url: "' . url('/drive/search/clients') . '",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
                q: params.term,
                page: params.page || 1
            };
        },
        processResults: function (data, params) {
            params.page = params.page || 1;
            return {
                results: data.results,
                pagination: {
                    more: data.pagination && data.pagination.more
                }
            };
        },
        cache: true
    },
    escapeMarkup: function (markup) {
        return markup;
    },
    minimumInputLength: 0
});

// Select2 para usuários (AJAX) - Padrão do tema
$("#select2-user").select2({
    dropdownParent: $("#uploadModal"),
    placeholder: "Buscar responsável...",
    allowClear: true,
    ajax: {
        url: "' . url('/drive/search/users') . '",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
                q: params.term,
                page: params.page || 1
            };
        },
        processResults: function (data, params) {
            params.page = params.page || 1;
            return {
                results: data.results,
                pagination: {
                    more: data.pagination && data.pagination.more
                }
            };
        },
        cache: true
    },
    escapeMarkup: function (markup) {
        return markup;
    },
    minimumInputLength: 0
});

// Funções de ação
async function submitUpload() {
    const form = document.getElementById("uploadForm");
    const formData = new FormData(form);
    
    try {
        const response = await fetch("' . url('/drive') . '", {
            method: "POST",
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert("Arquivo enviado com sucesso!");
            location.reload();
        } else {
            alert(data.message || "Erro ao enviar arquivo");
        }
    } catch (error) {
        console.error("Erro:", error);
        alert("Erro ao enviar arquivo. Verifique o console.");
    }
}

async function submitNewFolder() {
    const form = document.getElementById("newFolderForm");
    const formData = new FormData(form);
    
    try {
        const response = await fetch("' . url('/drive/folders') . '", {
            method: "POST",
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert("Pasta criada com sucesso!");
            location.reload();
        } else {
            alert(data.message || "Erro ao criar pasta");
        }
    } catch (error) {
        alert("Erro ao criar pasta");
    }
}

async function toggleFavorite(fileId) {
    try {
        const response = await fetch("' . url('/drive') . '/" + fileId + "/favorite", {
            method: "POST"
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        }
    } catch (error) {
        alert("Erro ao atualizar favorito");
    }
}

async function trashFile(fileId) {
    if (!confirm("Mover arquivo para lixeira?")) return;
    
    try {
        const response = await fetch("' . url('/drive') . '/" + fileId + "/trash", {
            method: "POST"
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert("Arquivo movido para lixeira");
            location.reload();
        }
    } catch (error) {
        alert("Erro ao mover arquivo");
    }
}

async function deleteFolder(folderId) {
    if (!confirm("Deletar pasta?")) return;
    
    try {
        const response = await fetch("' . url('/drive/folders') . '/" + folderId, {
            method: "DELETE"
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert("Pasta deletada");
            location.reload();
        } else {
            alert(data.message || "Erro ao deletar pasta");
        }
    } catch (error) {
        alert("Erro ao deletar pasta");
    }
}
</script>
';

include base_path('views/layouts/app.php');
?>

