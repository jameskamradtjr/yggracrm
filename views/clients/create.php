<?php
ob_start();
$title = $title ?? 'Novo Cliente';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Cliente</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/clients'); ?>">Clientes</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Cliente</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/clients'); ?>">
            <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <h5 class="mb-3">Tipo de Cliente</h5>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_fisica" value="fisica" checked onchange="toggleTipoCliente()">
                            <label class="form-check-label" for="tipo_fisica">
                                Pessoa Física
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_juridica" value="juridica" onchange="toggleTipoCliente()">
                            <label class="form-check-label" for="tipo_juridica">
                                Pessoa Jurídica
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="nome_razao_social" class="form-label">
                        <span id="label_nome">Nome Completo</span> <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="nome_razao_social" name="nome_razao_social" required>
                </div>
                
                <div class="col-md-6 mb-3" id="nome_fantasia_container">
                    <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                    <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cpf_cnpj" class="form-label">
                        <span id="label_cpf_cnpj">CPF</span>
                    </label>
                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" placeholder="000.000.000-00">
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(00) 0000-0000">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="text" class="form-control" id="celular" name="celular" placeholder="(00) 00000-0000">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="instagram" class="form-label">Instagram</label>
                    <input type="text" class="form-control" id="instagram" name="instagram" placeholder="@usuario">
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Endereço</h5>
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="endereco" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="endereco" name="endereco" placeholder="Rua, Avenida, etc.">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="numero">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" placeholder="Apto, Bloco, etc.">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="bairro">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" class="form-control" id="estado" name="estado" maxlength="2" placeholder="SP">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000">
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="score" class="form-label">Score (0-100)</label>
                    <input type="number" class="form-control" id="score" name="score" min="0" max="100" value="50">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="4"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?php echo url('/clients'); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Salvar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTipoCliente() {
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    const nomeLabel = document.getElementById('label_nome');
    const cpfCnpjLabel = document.getElementById('label_cpf_cnpj');
    const nomeFantasiaContainer = document.getElementById('nome_fantasia_container');
    const cpfCnpjInput = document.getElementById('cpf_cnpj');
    
    if (tipo === 'juridica') {
        nomeLabel.textContent = 'Razão Social';
        cpfCnpjLabel.textContent = 'CNPJ';
        cpfCnpjInput.placeholder = '00.000.000/0000-00';
        nomeFantasiaContainer.style.display = 'block';
    } else {
        nomeLabel.textContent = 'Nome Completo';
        cpfCnpjLabel.textContent = 'CPF';
        cpfCnpjInput.placeholder = '000.000.000-00';
        nomeFantasiaContainer.style.display = 'none';
    }
}

// Máscaras
document.getElementById('cpf_cnpj')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    
    if (tipo === 'juridica') {
        if (value.length <= 14) {
            value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
        }
    } else {
        if (value.length <= 11) {
            value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
        }
    }
    e.target.value = value;
});

document.getElementById('telefone')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 10) {
        value = value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
    }
    e.target.value = value;
});

document.getElementById('celular')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    }
    e.target.value = value;
});

document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 8) {
        value = value.replace(/^(\d{5})(\d{3}).*/, '$1-$2');
    }
    e.target.value = value;
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

