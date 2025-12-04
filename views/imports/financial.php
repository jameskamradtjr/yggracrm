<?php
ob_start();
$title = $title ?? 'Importar Lançamentos Financeiros';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Importar Lançamentos Financeiros via Excel</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/financial'); ?>">Financeiro</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Importar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">📥 Upload de Planilha</h5>
                
                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Como usar:</h6>
                    <ol class="mb-0">
                        <li>Baixe o template Excel clicando no botão ao lado →</li>
                        <li>Preencha a planilha com os dados dos lançamentos</li>
                        <li><strong>Importante:</strong> Use nomes EXATOS de categorias e centros de custo já cadastrados</li>
                        <li>Para tags, separe por vírgula (ex: projeto,urgente,cliente-x)</li>
                        <li>Salve o arquivo e faça o upload abaixo</li>
                    </ol>
                </div>

                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="ti ti-alert-triangle me-2"></i>Atenção!</h6>
                    <p class="mb-2">Para que a importação funcione corretamente:</p>
                    <ul class="mb-0">
                        <li><strong>Categorias e Centros de Custo</strong> devem estar cadastrados no sistema</li>
                        <li>Se o nome não for encontrado, o campo ficará vazio</li>
                        <li><strong>Tags</strong> serão criadas automaticamente se não existirem</li>
                    </ul>
                </div>

                <form id="importForm" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-4">
                        <label for="file" class="form-label">Selecione o arquivo Excel (.xlsx) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        <small class="text-muted">Apenas arquivos .xlsx ou .xls</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btnImport">
                            <i class="ti ti-upload me-2"></i>Importar Lançamentos
                        </button>
                        <a href="<?php echo url('/financial'); ?>" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </form>

                <!-- Resultado da importação -->
                <div id="resultContainer" class="mt-4" style="display: none;">
                    <div class="alert alert-success" id="successAlert" style="display: none;">
                        <h6 class="alert-heading"><i class="ti ti-check me-2"></i>Importação Concluída!</h6>
                        <p id="successMessage" class="mb-0"></p>
                    </div>

                    <div class="alert alert-warning" id="errorsAlert" style="display: none;">
                        <h6 class="alert-heading"><i class="ti ti-alert-triangle me-2"></i>Avisos</h6>
                        <ul id="errorsList" class="mb-0"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-light-primary">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="ti ti-download me-2"></i>Template Excel
                </h5>
                <p class="card-text">
                    Baixe o modelo de planilha para importação. Inclui exemplos e instruções detalhadas.
                </p>
                <a href="<?php echo url('/imports/financial/template'); ?>" class="btn btn-primary w-100" download>
                    <i class="ti ti-file-spreadsheet me-2"></i>Baixar Template
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="ti ti-info-circle me-2"></i>Campos da Planilha
                </h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><strong>tipo:</strong> receita ou despesa</li>
                    <li class="mb-2"><strong>descricao:</strong> Descrição do lançamento</li>
                    <li class="mb-2"><strong>valor:</strong> Usar ponto decimal (ex: 1500.00)</li>
                    <li class="mb-2"><strong>data_vencimento:</strong> AAAA-MM-DD</li>
                    <li class="mb-2"><strong>data_pagamento:</strong> AAAA-MM-DD (opcional)</li>
                    <li class="mb-2"><strong>categoria:</strong> Nome exato da categoria</li>
                    <li class="mb-2"><strong>centro_custo:</strong> Nome exato do centro</li>
                    <li class="mb-2"><strong>tags:</strong> Separadas por vírgula</li>
                    <li class="mb-2"><strong>recorrente:</strong> sim ou nao</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3 bg-light-warning">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="ti ti-bulb me-2"></i>Dicas
                </h6>
                <ul class="small mb-0">
                    <li class="mb-2">Verifique se categorias e centros de custo existem antes de importar</li>
                    <li class="mb-2">Tags serão criadas automaticamente</li>
                    <li class="mb-2">Status é definido automaticamente (pago se tiver data de pagamento)</li>
                    <li class="mb-2">Linhas vazias são ignoradas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnImport');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importando...';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('<?php echo url('/imports/financial/upload'); ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        // Mostra resultado
        document.getElementById('resultContainer').style.display = 'block';
        
        if (data.success) {
            const successAlert = document.getElementById('successAlert');
            const successMessage = document.getElementById('successMessage');
            
            successAlert.style.display = 'block';
            successMessage.innerHTML = `
                <strong>${data.imported}</strong> lançamento(s) importado(s) com sucesso!<br>
                ${data.skipped > 0 ? `<small>${data.skipped} linha(s) em branco ignorada(s)</small>` : ''}
            `;
            
            // Mostra erros se houver
            if (data.errors && data.errors.length > 0) {
                const errorsAlert = document.getElementById('errorsAlert');
                const errorsList = document.getElementById('errorsList');
                
                errorsAlert.style.display = 'block';
                errorsList.innerHTML = data.errors.map(err => `<li>${err}</li>`).join('');
            }
            
            // Limpa form
            this.reset();
            
            // Redireciona após 3 segundos
            setTimeout(() => {
                window.location.href = '<?php echo url('/financial'); ?>';
            }, 3000);
            
        } else {
            alert('❌ ' + data.message);
        }
        
    } catch (error) {
        console.error('Erro:', error);
        alert('❌ Erro ao importar arquivo. Verifique o formato e tente novamente.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

