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

                        <!-- Informações de Negócio -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Informações de Negócio</h5>
                            
                            <div class="mb-3">
                                <label for="faturamento" class="form-label">Faturamento Atual <span class="text-danger">*</span></label>
                                <select class="form-select" id="faturamento" name="faturamento" required>
                                    <option value="">Selecione...</option>
                                    <option value="0-10k" <?php echo old('faturamento') === '0-10k' ? 'selected' : ''; ?>>Até R$ 10.000/mês</option>
                                    <option value="10-50k" <?php echo old('faturamento') === '10-50k' ? 'selected' : ''; ?>>R$ 10.000 - R$ 50.000/mês</option>
                                    <option value="50-200k" <?php echo old('faturamento') === '50-200k' ? 'selected' : ''; ?>>R$ 50.000 - R$ 200.000/mês</option>
                                    <option value="200k+" <?php echo old('faturamento') === '200k+' ? 'selected' : ''; ?>>Acima de R$ 200.000/mês</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="investimento" class="form-label">Investimento Pretendido <span class="text-danger">*</span></label>
                                <select class="form-select" id="investimento" name="investimento" required>
                                    <option value="">Selecione...</option>
                                    <option value="1k" <?php echo old('investimento') === '1k' ? 'selected' : ''; ?>>Até R$ 1.000/mês</option>
                                    <option value="3k" <?php echo old('investimento') === '3k' ? 'selected' : ''; ?>>R$ 1.000 - R$ 3.000/mês</option>
                                    <option value="5k" <?php echo old('investimento') === '5k' ? 'selected' : ''; ?>>R$ 3.000 - R$ 5.000/mês</option>
                                    <option value="10k" <?php echo old('investimento') === '10k' ? 'selected' : ''; ?>>R$ 5.000 - R$ 10.000/mês</option>
                                    <option value="10k+" <?php echo old('investimento') === '10k+' ? 'selected' : ''; ?>>Acima de R$ 10.000/mês</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Já faz tráfego pago?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="faz_trafego" id="faz_trafego_sim" value="sim" <?php echo old('faz_trafego') === 'sim' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="faz_trafego_sim">Sim</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="faz_trafego" id="faz_trafego_nao" value="não" <?php echo old('faz_trafego') === 'não' || old('faz_trafego') === '' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="faz_trafego_nao">Não</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="objetivo" class="form-label">Objetivo Principal</label>
                                <textarea class="form-control" 
                                          id="objetivo" 
                                          name="objetivo" 
                                          rows="4" 
                                          placeholder="Descreva o objetivo principal com tráfego pago..."><?php echo old('objetivo'); ?></textarea>
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

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

