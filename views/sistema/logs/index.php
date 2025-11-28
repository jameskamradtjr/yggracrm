<?php
$title = 'Logs do Sistema';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="card-title fw-semibold mb-2">Logs do Sistema</h4>
                <p class="card-subtitle mb-0">Histórico completo de ações realizadas no sistema</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Filtros</h5>
                <form id="formFiltros" class="row g-3">
                    <div class="col-md-2">
                        <label for="filtro_tabela" class="form-label">Tabela</label>
                        <select class="form-select form-select-sm" id="filtro_tabela" name="filtro_tabela">
                            <option value="">Todas</option>
                            <?php foreach ($tabelas as $tabela): ?>
                                <option value="<?php echo e($tabela['tabela']); ?>"><?php echo e($tabela['tabela']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_acao" class="form-label">Ação</label>
                        <select class="form-select form-select-sm" id="filtro_acao" name="filtro_acao">
                            <option value="">Todas</option>
                            <?php foreach ($acoes as $acao): ?>
                                <option value="<?php echo e($acao['acao']); ?>"><?php echo e($acao['acao']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_usuario" class="form-label">Usuário</label>
                        <input type="text" class="form-control form-control-sm" id="filtro_usuario" name="filtro_usuario" placeholder="Nome do usuário...">
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control form-control-sm" id="filtro_data_inicio" name="filtro_data_inicio">
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control form-control-sm" id="filtro_data_fim" name="filtro_data_fim">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm w-100" onclick="aplicarFiltros()">
                                <i class="ti ti-filter me-2"></i> Aplicar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()">
                            <i class="ti ti-x me-2"></i> Limpar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Logs -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelaLogs" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tabela</th>
                                <th>Registro ID</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>Usuário</th>
                                <th>IP</th>
                                <th>Data/Hora</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dados carregados via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesLabel">Detalhes do Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesConteudo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Scripts para serem adicionados ao final
ob_start();
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
// Aguarda todos os scripts do layout carregarem
window.addEventListener('load', function() {
    // Aguarda jQuery estar disponível
    function waitForJQuery(callback) {
        if (window.jQuery && typeof window.jQuery.fn.DataTable !== 'undefined') {
            callback(window.jQuery);
        } else if (window.jQuery) {
            // jQuery está disponível, mas DataTable não, carrega DataTable
            var script1 = document.createElement('script');
            script1.src = 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js';
            script1.onload = function() {
                var script2 = document.createElement('script');
                script2.src = 'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js';
                script2.onload = function() {
                    callback(window.jQuery);
                };
                document.head.appendChild(script2);
            };
            document.head.appendChild(script1);
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 100);
        }
    }
    
    waitForJQuery(function($) {
        let table = $('#tabelaLogs').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?php echo url('/sistema/logs/datatable'); ?>',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: function(d) {
                    // Adiciona filtros customizados
                    d.filtro_tabela = $('#filtro_tabela').val();
                    d.filtro_acao = $('#filtro_acao').val();
                    d.filtro_usuario = $('#filtro_usuario').val();
                    d.filtro_data_inicio = $('#filtro_data_inicio').val();
                    d.filtro_data_fim = $('#filtro_data_fim').val();
                    d._csrf_token = '<?php echo csrf_token(); ?>';
                },
                error: function(xhr, error, thrown) {
                    console.error('Erro no DataTable:', error);
                    console.error('Response:', xhr.responseText);
                    $('#tabelaLogs tbody').html(
                        '<tr><td colspan="9" class="text-center text-danger">Erro ao carregar dados. Verifique o console.</td></tr>'
                    );
                }
            },
            columns: [
                { data: 'id' },
                { data: 'tabela' },
                { data: 'registro_id' },
                { data: 'acao' },
                { 
                    data: 'descricao',
                    render: function(data, type, row) {
                        if (type === 'display' && row.descricao_completo && row.descricao_completo.length > 80) {
                            return '<span title="' + (row.descricao_completo.replace(/"/g, '&quot;') || '') + '">' + data + '</span>';
                        }
                        return data || '-';
                    }
                },
                { data: 'usuario_nome' },
                { data: 'ip_address' },
                { data: 'created_at' },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return '<button class="btn btn-sm btn-info" onclick="verDetalhes(' + row.id + ', \'' + 
                               (row.full_data && row.full_data.dados_anteriores ? encodeURIComponent(JSON.stringify(row.full_data.dados_anteriores)) : '') + '\', \'' + 
                               (row.full_data && row.full_data.dados_novos ? encodeURIComponent(JSON.stringify(row.full_data.dados_novos)) : '') + '\', \'' + 
                               (row.full_data && row.full_data.descricao ? encodeURIComponent(row.full_data.descricao) : '') + '\')">' +
                               '<i class="ti ti-eye me-1"></i> Detalhes</button>';
                    }
                }
            ],
            order: [[7, 'desc']], // Ordena por data/hora descendente
            pageLength: 25,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json',
                processing: 'Processando...',
                emptyTable: 'Nenhum registro encontrado',
                zeroRecords: 'Nenhum registro encontrado',
                loadingRecords: 'Carregando...',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros totais)',
                lengthMenu: 'Mostrar _MENU_ registros por página',
                search: 'Buscar:',
                paginate: {
                    first: 'Primeiro',
                    last: 'Último',
                    next: 'Próximo',
                    previous: 'Anterior'
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
        
        // Funções globais para os botões de filtro
        window.aplicarFiltros = function() {
            table.ajax.reload();
        };
        
        window.limparFiltros = function() {
            $('#formFiltros')[0].reset();
            table.ajax.reload();
        };
        
        window.verDetalhes = function(id, dadosAnteriores, dadosNovos, descricao) {
            let html = '<div class="mb-3">';
            html += '<strong>Descrição:</strong><br>';
            html += '<p class="text-muted">' + (descricao ? decodeURIComponent(descricao) : 'N/A') + '</p>';
            html += '</div>';
            
            if (dadosAnteriores) {
                try {
                    const dadosAnt = JSON.parse(decodeURIComponent(dadosAnteriores));
                    html += '<div class="mb-3">';
                    html += '<strong>Dados Anteriores:</strong>';
                    html += '<pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">' + 
                           JSON.stringify(dadosAnt, null, 2) + '</pre>';
                    html += '</div>';
                } catch(e) {
                    html += '<div class="mb-3"><strong>Dados Anteriores:</strong> <span class="text-muted">Erro ao processar</span></div>';
                }
            }
            
            if (dadosNovos) {
                try {
                    const dadosNov = JSON.parse(decodeURIComponent(dadosNovos));
                    html += '<div class="mb-3">';
                    html += '<strong>Dados Novos:</strong>';
                    html += '<pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">' + 
                           JSON.stringify(dadosNov, null, 2) + '</pre>';
                    html += '</div>';
                } catch(e) {
                    html += '<div class="mb-3"><strong>Dados Novos:</strong> <span class="text-muted">Erro ao processar</span></div>';
                }
            }
            
            $('#detalhesConteudo').html(html);
            $('#modalDetalhes').modal('show');
        };
        
        // Enter nos campos de filtro
        $('#formFiltros input, #formFiltros select').on('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.aplicarFiltros();
            }
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>


