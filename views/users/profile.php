<?php
$title = 'Meu Perfil';

// Inicia captura do conteúdo
ob_start();
?>

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Meu Perfil</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Perfil</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="<?php echo asset('tema/assets/images/breadcrumb/ChatBc.png'); ?>" alt="" class="img-fluid mb-n4">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagens de feedback -->
    <?php if (session()->get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session()->getFlash('success')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->get('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session()->getFlash('error')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->get('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erros de validação:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach (session()->getFlash('errors') as $field => $errors): ?>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Informações do Usuário -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <?php if (!empty($user->avatar)): ?>
                            <img src="<?php echo asset($user->avatar); ?>" alt="Avatar" class="rounded-circle" width="120" height="120" style="object-fit: cover;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                <span class="fs-7 fw-bold text-primary"><?php echo strtoupper(substr($user->name, 0, 2)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="card-title mb-1"><?php echo e($user->name); ?></h5>
                    <p class="text-muted mb-3"><?php echo e($user->email); ?></p>
                    
                    <?php if (!empty($user->phone)): ?>
                        <p class="mb-2">
                            <i class="ti ti-phone me-2"></i>
                            <?php echo e($user->phone); ?>
                        </p>
                    <?php endif; ?>
                    
                    <p class="mb-2">
                        <i class="ti ti-calendar me-2"></i>
                        Membro desde <?php echo date('d/m/Y', strtotime($user->created_at)); ?>
                    </p>
                    
                    <?php if (!empty($user->last_login_at)): ?>
                        <p class="text-muted small mb-0">
                            Último login: <?php echo date('d/m/Y H:i', strtotime($user->last_login_at)); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulário de Edição -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Editar Perfil</h5>
                    
                    <form action="<?php echo url('/profile'); ?>" method="POST" id="formProfile">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto do Perfil</label>
                            <div class="border rounded p-3 text-center">
                                <video id="video" autoplay playsinline style="width: 100%; max-width: 400px; display: none;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                <div id="fotoPreview" class="mb-2">
                                    <?php if (!empty($user->avatar)): ?>
                                        <img src="<?php echo asset($user->avatar); ?>" class="img-thumbnail" style="max-width: 300px;" id="avatar-preview" onerror="this.onerror=null; this.style.display='none';">
                                    <?php endif; ?>
                                </div>
                                <div id="controlesCamera">
                                    <button type="button" class="btn btn-primary" onclick="iniciarCamera()">
                                        <i class="ti ti-camera me-2"></i> <?php echo !empty($user->avatar) ? 'Alterar Foto' : 'Iniciar Câmera'; ?>
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
                            <input type="hidden" id="avatar_base64" name="avatar_base64" value="">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name', $user->name)); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e(old('phone', $user->phone)); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo e(old('company_name', $profile->company_name ?? '')); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo e(old('cpf', $profile->cpf ?? '')); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo e(old('cnpj', $profile->cnpj ?? '')); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo e(old('address', $profile->address ?? '')); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo e(old('city', $profile->city ?? '')); ?>">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="state" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo e(old('state', $profile->state ?? '')); ?>" maxlength="2">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="zipcode" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="zipcode" name="zipcode" value="<?php echo e(old('zipcode', $profile->zipcode ?? '')); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biografia</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo e(old('bio', $profile->bio ?? '')); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo e(old('website', $profile->website ?? '')); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Alterar Senha (opcional)</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo url('/dashboard'); ?>" class="btn btn-light">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
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
    
    const preview = document.getElementById('fotoPreview');
    preview.innerHTML = `
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

// Para a câmera ao fechar o modal ou sair da página
window.addEventListener('beforeunload', pararCamera);
</script>
<?php
$scripts = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>

