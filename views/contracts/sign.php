<?php
ob_start();
$title = $title ?? 'Assinar Contrato';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .sign-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .contract-preview {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            font-weight: bold;
        }
        .signature-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="sign-container">
        <div class="text-center mb-4">
            <h2>Assinatura de Contrato</h2>
            <p class="text-muted">Contrato: <?php echo e($contract->numero_contrato); ?></p>
        </div>
        
        <div class="signature-info">
            <h5>Dados do Assinante</h5>
            <p class="mb-1"><strong>Nome:</strong> <?php echo e($signature->nome_assinante); ?></p>
            <p class="mb-1"><strong>Email:</strong> <?php echo e($signature->email); ?></p>
            <?php if ($signature->cpf_cnpj): ?>
                <p class="mb-0"><strong>CPF/CNPJ:</strong> <?php echo e($signature->cpf_cnpj); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($signature->assinado): ?>
            <div class="alert alert-success text-center">
                <i class="ti ti-check-circle fs-1 d-block mb-2"></i>
                <h4>Contrato já assinado!</h4>
                <p class="mb-0">Este contrato foi assinado em <?php echo date('d/m/Y H:i', strtotime($signature->assinado_em)); ?></p>
            </div>
        <?php else: ?>
            <div class="contract-preview">
                <h5 class="mb-3"><?php echo e($contract->titulo); ?></h5>
                <?php if (!empty($contract->conteudo_gerado)): ?>
                    <div><?php echo $contract->conteudo_gerado; ?></div>
                <?php elseif (!empty($contract->conteudo)): ?>
                    <div><?php echo $contract->conteudo; ?></div>
                <?php else: ?>
                    <p class="text-muted">Conteúdo do contrato não disponível.</p>
                <?php endif; ?>
            </div>
            
            <form id="signForm" class="mt-4">
                <div class="mb-3">
                    <label for="codigo_verificacao" class="form-label">
                        <strong>Código de Verificação</strong>
                    </label>
                    <p class="text-muted small">Digite o código de 6 dígitos enviado para seu email.</p>
                    <input type="text" 
                           class="form-control code-input" 
                           id="codigo_verificacao" 
                           name="codigo_verificacao" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           placeholder="000000"
                           required>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="aceito_termos" required>
                    <label class="form-check-label" for="aceito_termos">
                        Eu aceito os termos e condições deste contrato e confirmo que li e compreendi todo o conteúdo.
                    </label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="ti ti-signature me-2"></i>Assinar Contrato
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo asset('tema/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js'); ?>"></script>
    <script>
        document.getElementById('signForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const codigo = document.getElementById('codigo_verificacao').value;
            const aceito = document.getElementById('aceito_termos').checked;
            
            if (!aceito) {
                alert('Você deve aceitar os termos e condições para assinar o contrato.');
                return;
            }
            
            if (codigo.length !== 6) {
                alert('Por favor, digite o código de 6 dígitos.');
                return;
            }
            
            if (!confirm('Tem certeza que deseja assinar este contrato? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';
            
            fetch('<?php echo url('/contracts/sign/' . $token); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo csrf_token(); ?>'
                },
                body: JSON.stringify({
                    codigo_verificacao: codigo,
                    tipo_assinante: '<?php echo $signature->tipo_assinante; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Contrato assinado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-signature me-2"></i>Assinar Contrato';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar assinatura. Tente novamente.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-signature me-2"></i>Assinar Contrato';
            });
        });
        
        // Auto-focus no código e formatação
        document.getElementById('codigo_verificacao')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
// Para a página de assinatura, não usamos o layout padrão
echo $content;
?>

