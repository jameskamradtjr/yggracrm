<?php
$title = 'Criar Usuário';

// Inicia captura do conteúdo
ob_start();

// Define módulos e recursos do sistema
$modulos = [
    'gerenciamento' => [
        'nome' => 'Gerenciamento',
        'recursos' => [
            'usuarios' => ['nome' => 'Usuários']
        ]
    ],
    'sistema' => [
        'nome' => 'Sistema',
        'recursos' => [
            'logs' => ['nome' => 'Logs do Sistema']
        ]
    ]
];

$acoes = [
    'view' => 'Visualizar',
    'create' => 'Criar',
    'edit' => 'Editar',
    'delete' => 'Excluir',
    'all' => 'Todos'
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Criar Novo Usuário</h4>
                        <p class="card-subtitle mb-0">Adicione um novo usuário à sua conta</p>
                    </div>
                    <a href="<?php echo url('/users'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/users'); ?>" method="POST" id="formCreateUser">
                    <?php echo csrf_field(); ?>
                    
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" data-bs-toggle="tab" href="#dados" aria-selected="true" role="tab">Dados Pessoais</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#foto" aria-selected="false" tabindex="-1" role="tab">Foto</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#permissoes" aria-selected="false" tabindex="-1" role="tab">Permissões</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Tab Dados Pessoais -->
                        <div class="tab-pane fade show active" id="dados" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name')); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email')); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e(old('phone')); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo old('status') === 'active' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inactive" <?php echo old('status') === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                        <option value="suspended" <?php echo old('status') === 'suspended' ? 'selected' : ''; ?>>Suspenso</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Roles <span class="text-muted">(opcional)</span></label>
                                <div class="row">
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $role): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role->id; ?>" id="role_<?php echo $role->id; ?>">
                                                    <label class="form-check-label" for="role_<?php echo $role->id; ?>">
                                                        <?php echo e($role->name); ?>
                                                        <?php if ($role->description): ?>
                                                            <small class="text-muted d-block"><?php echo e($role->description); ?></small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-muted">Nenhuma role disponível. <a href="<?php echo url('/roles/create'); ?>">Criar role</a></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Foto -->
                        <div class="tab-pane fade" id="foto" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Foto do Usuário</label>
                                <div class="border rounded p-3 text-center">
                                    <video id="video" autoplay playsinline style="width: 100%; max-width: 400px; display: none;"></video>
                                    <canvas id="canvas" style="display: none;"></canvas>
                                    <div id="fotoPreview" class="mb-2"></div>
                                    <div id="controlesCamera">
                                        <button type="button" class="btn btn-primary" onclick="iniciarCamera()">
                                            <i class="ti ti-camera me-2"></i> Iniciar Câmera
                                        </button>
                                    </div>
                                    <div id="controlesFoto" style="display: none;">
                                        <button type="button" class="btn btn-success" onclick="tirarFoto()">
                                            <i class="ti ti-camera me-2"></i> Tirar Foto
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="reiniciarCamera()">
                                            <i class="ti ti-refresh me-2"></i> Reiniciar
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="avatar_base64" name="avatar_base64">
                            </div>
                        </div>
                        
                        <!-- Tab Permissões -->
                        <div class="tab-pane fade" id="permissoes" role="tabpanel">
                            <div class="mb-3">
                                <p class="text-muted">Configure as permissões de acesso para cada módulo e recurso do sistema.</p>
                            </div>
                            
                            <?php foreach ($modulos as $moduloKey => $modulo): ?>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo e($modulo['nome']); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($modulo['recursos'] as $recursoKey => $recurso): ?>
                                            <div class="mb-4">
                                                <h6 class="mb-3"><?php echo e($recurso['nome']); ?></h6>
                                                <div class="row">
                                                    <?php foreach ($acoes as $acaoKey => $acaoNome): ?>
                                                        <div class="col-md-3 mb-2">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="permissions[<?php echo e($moduloKey); ?>][<?php echo e($recursoKey); ?>][<?php echo e($acaoKey); ?>]" 
                                                                       value="1" 
                                                                       id="perm_<?php echo e($moduloKey); ?>_<?php echo e($recursoKey); ?>_<?php echo e($acaoKey); ?>"
                                                                       onchange="toggleAllPermissao('<?php echo e($moduloKey); ?>', '<?php echo e($recursoKey); ?>', '<?php echo e($acaoKey); ?>')">
                                                                <label class="form-check-label" for="perm_<?php echo e($moduloKey); ?>_<?php echo e($recursoKey); ?>_<?php echo e($acaoKey); ?>">
                                                                    <?php echo e($acaoNome); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <hr>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?php echo url('/users'); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Criar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Scripts
ob_start();
?>
<script>
let stream = null;
let fotoCapturada = false;

function iniciarCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(function(mediaStream) {
            stream = mediaStream;
            const video = document.getElementById('video');
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('controlesCamera').style.display = 'none';
            document.getElementById('controlesFoto').style.display = 'block';
        })
        .catch(function(err) {
            console.error('Erro ao acessar câmera:', err);
            alert('Erro ao acessar a câmera. Verifique as permissões.');
        });
}

function tirarFoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    
    const fotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
    document.getElementById('avatar_base64').value = fotoBase64;
    
    document.getElementById('fotoPreview').innerHTML = `
        <img src="${fotoBase64}" class="img-thumbnail" style="max-width: 300px;">
        <p class="text-success mt-2"><i class="ti ti-check me-2"></i>Foto capturada!</p>
    `;
    
    pararCamera();
    fotoCapturada = true;
}

function reiniciarCamera() {
    pararCamera();
    document.getElementById('fotoPreview').innerHTML = '';
    document.getElementById('avatar_base64').value = '';
    fotoCapturada = false;
    iniciarCamera();
}

function pararCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    document.getElementById('video').style.display = 'none';
    document.getElementById('controlesCamera').style.display = 'block';
    document.getElementById('controlesFoto').style.display = 'none';
}

function toggleAllPermissao(modulo, recurso, acao) {
    if (acao === 'all') {
        const checkbox = document.getElementById(`perm_${modulo}_${recurso}_all`);
        const checked = checkbox.checked;
        
        // Marca/desmarca todas as outras ações
        ['view', 'create', 'edit', 'delete'].forEach(function(a) {
            const cb = document.getElementById(`perm_${modulo}_${recurso}_${a}`);
            if (cb) {
                cb.checked = checked;
            }
        });
    } else {
        // Se desmarcar qualquer ação, desmarca "all"
        const allCheckbox = document.getElementById(`perm_${modulo}_${recurso}_all`);
        if (allCheckbox && !document.getElementById(`perm_${modulo}_${recurso}_${acao}`).checked) {
            allCheckbox.checked = false;
        }
    }
}

// Processa permissões antes de enviar
document.getElementById('formCreateUser').addEventListener('submit', function(e) {
    const permissions = {};
    
    // Coleta todas as permissões marcadas
    document.querySelectorAll('input[name^="permissions["]').forEach(function(checkbox) {
        if (checkbox.checked) {
            const name = checkbox.name;
            const matches = name.match(/permissions\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]/);
            if (matches) {
                const [, modulo, recurso, acao] = matches;
                if (!permissions[modulo]) permissions[modulo] = {};
                if (!permissions[modulo][recurso]) permissions[modulo][recurso] = {};
                permissions[modulo][recurso][acao] = true;
            }
        }
    });
    
    // Converte para array formatado
    const permissionsArray = [];
    Object.keys(permissions).forEach(function(modulo) {
        Object.keys(permissions[modulo]).forEach(function(recurso) {
            Object.keys(permissions[modulo][recurso]).forEach(function(acao) {
                permissionsArray.push({
                    module: modulo,
                    resource: recurso,
                    action: acao,
                    granted: true
                });
            });
        });
    });
    
    // Adiciona campo hidden com permissões formatadas
    let hiddenInput = document.getElementById('permissions_json');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'permissions_json';
        hiddenInput.name = 'permissions_json';
        this.appendChild(hiddenInput);
    }
    hiddenInput.value = JSON.stringify(permissionsArray);
});

// Para a câmera ao fechar o modal ou sair da página
window.addEventListener('beforeunload', pararCamera);
</script>
<?php
$scripts = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>

