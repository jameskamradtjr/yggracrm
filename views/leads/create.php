<?php
$title = 'Cadastrar Lead';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Cadastrar Lead Manualmente</h4>
                        <p class="card-subtitle mb-0">Preencha os dados do lead para cadastrar no sistema</p>
                    </div>
                    <a href="<?php echo url('/leads'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/leads'); ?>" method="POST" id="formCreateLead">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <!-- Informações Básicas -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Informações Básicas</h5>
                            
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Cliente Existente (Opcional)</label>
                                <select class="form-select" id="client_id" name="client_id" onchange="preencherDadosCliente()">
                                    <option value="">Selecione um cliente existente ou cadastre novo</option>
                                    <?php if (!empty($clients)): ?>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?php echo $client->id; ?>" 
                                                    data-nome="<?php echo e($client->nome_razao_social); ?>"
                                                    data-email="<?php echo e($client->email ?? ''); ?>"
                                                    data-telefone="<?php echo e($client->telefone ?? ''); ?>">
                                                <?php echo e($client->nome_razao_social); ?>
                                                <?php if ($client->email): ?>
                                                    (<?php echo e($client->email); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Se selecionar um cliente, os campos abaixo serão preenchidos automaticamente. Se não selecionar, um novo cliente será criado automaticamente.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       value="<?php echo old('nome'); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo old('email'); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefone" 
                                       name="telefone" 
                                       value="<?php echo old('telefone'); ?>" 
                                       placeholder="(00) 00000-0000"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="instagram" class="form-label">Instagram</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="instagram" 
                                       name="instagram" 
                                       value="<?php echo old('instagram'); ?>" 
                                       placeholder="@seuinstagram">
                            </div>

                            <div class="mb-3">
                                <label for="ramo" class="form-label">Ramo da Empresa</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ramo" 
                                       name="ramo" 
                                       value="<?php echo old('ramo'); ?>" 
                                       placeholder="Ex: E-commerce, Serviços, SaaS...">
                            </div>
                        </div>

                        <!-- Informações de Software -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Informações de Software</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Já possui software/sistema? <span class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tem_software" id="tem_software_sim" value="sim" <?php echo old('tem_software') === 'sim' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="tem_software_sim">Sim, já tenho</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tem_software" id="tem_software_nao" value="nao" <?php echo old('tem_software') === 'nao' || old('tem_software') === '' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="tem_software_nao">Não, ainda não tenho</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="investimento_software" class="form-label">Investimento em Software <span class="text-danger">*</span></label>
                                <select class="form-select" id="investimento_software" name="investimento_software" required>
                                    <option value="">Selecione...</option>
                                    <option value="5k" <?php echo old('investimento_software') === '5k' ? 'selected' : ''; ?>>Até R$ 5.000</option>
                                    <option value="10k" <?php echo old('investimento_software') === '10k' ? 'selected' : ''; ?>>R$ 5.000 - R$ 10.000</option>
                                    <option value="25k" <?php echo old('investimento_software') === '25k' ? 'selected' : ''; ?>>R$ 10.000 - R$ 25.000</option>
                                    <option value="50k" <?php echo old('investimento_software') === '50k' ? 'selected' : ''; ?>>R$ 25.000 - R$ 50.000</option>
                                    <option value="50k+" <?php echo old('investimento_software') === '50k+' ? 'selected' : ''; ?>>Acima de R$ 50.000</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_sistema" class="form-label">Tipo de Sistema <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo_sistema" name="tipo_sistema" required>
                                    <option value="">Selecione...</option>
                                    <option value="interno" <?php echo old('tipo_sistema') === 'interno' ? 'selected' : ''; ?>>Sistema para utilização interna da empresa</option>
                                    <option value="cliente" <?php echo old('tipo_sistema') === 'cliente' ? 'selected' : ''; ?>>Software para um cliente específico</option>
                                    <option value="saas" <?php echo old('tipo_sistema') === 'saas' ? 'selected' : ''; ?>>SaaS (Software como Serviço) para múltiplos clientes</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="plataforma_app" class="form-label">Plataforma de Aplicativo <span class="text-danger">*</span></label>
                                <select class="form-select" id="plataforma_app" name="plataforma_app" required>
                                    <option value="">Selecione...</option>
                                    <option value="ios_android" <?php echo old('plataforma_app') === 'ios_android' ? 'selected' : ''; ?>>iOS e Android</option>
                                    <option value="ios" <?php echo old('plataforma_app') === 'ios' ? 'selected' : ''; ?>>Apenas iOS</option>
                                    <option value="android" <?php echo old('plataforma_app') === 'android' ? 'selected' : ''; ?>>Apenas Android</option>
                                    <option value="nenhum" <?php echo old('plataforma_app') === 'nenhum' ? 'selected' : ''; ?>>Não preciso de aplicativo</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="origem_conheceu" class="form-label">De onde nos conheceu?</label>
                                <select class="form-select" id="origem_conheceu" name="origem_conheceu">
                                    <option value="">Selecione...</option>
                                    <?php
                                    // Busca origens do sistema
                                    $userId = auth()->getDataUserId();
                                    $origens = \App\Models\LeadOrigin::where('user_id', $userId)->get();
                                    if (empty($origens)) {
                                        // Fallback para lista padrão
                                        $origens = [
                                            (object)['nome' => 'Google'],
                                            (object)['nome' => 'Facebook/Instagram'],
                                            (object)['nome' => 'Indicação'],
                                            (object)['nome' => 'LinkedIn'],
                                            (object)['nome' => 'YouTube'],
                                            (object)['nome' => 'Outro']
                                        ];
                                    }
                                    foreach ($origens as $origem):
                                    ?>
                                        <option value="<?php echo e($origem->nome); ?>" <?php echo old('origem_conheceu') === $origem->nome ? 'selected' : ''; ?>>
                                            <?php echo e($origem->nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="valor_oportunidade" class="form-label">Valor da Oportunidade (R$)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="valor_oportunidade" 
                                       name="valor_oportunidade" 
                                       value="<?php echo old('valor_oportunidade'); ?>" 
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                <small class="text-muted">Valor estimado da oportunidade de negócio</small>
                            </div>

                            <div class="mb-3">
                                <label for="objetivo" class="form-label">Objetivo Principal</label>
                                <textarea class="form-control" 
                                          id="objetivo" 
                                          name="objetivo" 
                                          rows="4" 
                                          placeholder="Descreva seu objetivo principal com o software..."><?php echo old('objetivo'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo url('/leads'); ?>" class="btn btn-light">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-2"></i>
                                    Cadastrar Lead
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function preencherDadosCliente() {
    const select = document.getElementById('client_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        // Preenche os campos com os dados do cliente selecionado
        document.getElementById('nome').value = selectedOption.getAttribute('data-nome') || '';
        document.getElementById('email').value = selectedOption.getAttribute('data-email') || '';
        document.getElementById('telefone').value = selectedOption.getAttribute('data-telefone') || '';
        
        // Desabilita os campos (mas mantém required para validação)
        document.getElementById('nome').readOnly = true;
        document.getElementById('email').readOnly = true;
        document.getElementById('telefone').readOnly = true;
    } else {
        // Limpa e habilita os campos
        document.getElementById('nome').value = '';
        document.getElementById('email').value = '';
        document.getElementById('telefone').value = '';
        
        document.getElementById('nome').readOnly = false;
        document.getElementById('email').readOnly = false;
        document.getElementById('telefone').readOnly = false;
    }
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

