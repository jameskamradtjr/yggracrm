<?php
ob_start();
$title = $title ?? 'Clientes';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Clientes</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Clientes</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/clients/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card overflow-hidden chat-application">
    <div class="d-flex align-items-center justify-content-between gap-6 m-3 d-lg-none">
        <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas" data-bs-target="#client-sidebar" aria-controls="client-sidebar">
            <i class="ti ti-menu-2 fs-5"></i>
        </button>
        <form class="position-relative w-100" id="searchForm">
            <input type="text" class="form-control search-chat py-2 ps-5" id="searchInput" placeholder="Buscar Cliente">
            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
        </form>
    </div>
    <div class="d-flex w-100">
        <!-- Sidebar Esquerda -->
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            <div class="px-9 pt-4 pb-3">
                <a href="<?php echo url('/clients/create'); ?>" class="btn btn-primary fw-semibold py-8 w-100">Novo Cliente</a>
            </div>
            <ul class="list-group mh-n100" data-simplebar>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 active" href="javascript:void(0)" data-filter="all">
                        <i class="ti ti-inbox fs-5"></i>Todos os Clientes
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)" data-filter="fisica">
                        <i class="ti ti-user"></i>Pessoa Física
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)" data-filter="juridica">
                        <i class="ti ti-building"></i>Pessoa Jurídica
                    </a>
                </li>
            </ul>
        </div>

        <!-- Lista de Clientes -->
        <div class="d-flex w-100">
            <div class="min-width-340">
                <div class="border-end user-chat-box h-100">
                    <div class="px-4 pt-9 pb-6 d-none d-lg-block">
                        <form class="position-relative" id="searchFormDesktop">
                            <input type="text" class="form-control search-chat py-2 ps-5" id="searchInputDesktop" placeholder="Buscar" />
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="app-chat">
                        <ul class="chat-users mh-n100" data-simplebar id="clientsList">
                            <?php if (empty($clients)): ?>
                                <li class="px-4 py-3 text-center text-muted">
                                    <p class="mb-0">Nenhum cliente cadastrado</p>
                                    <div class="alert alert-warning mb-3">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        <strong>Leads não aparecem aqui automaticamente!</strong>
                                        <br>
                                        O quiz cria <strong>LEADS</strong> que aparecem em 
                                        <a href="<?php echo url('/leads'); ?>" class="alert-link">/leads</a> (CRM de Leads). 
                                        <br>
                                        Para aparecer aqui em <strong>/clients</strong>, você precisa 
                                        <strong>converter o lead em cliente</strong> primeiro.
                                        <br>
                                        <small class="d-block mt-2">
                                            <i class="ti ti-arrow-right me-1"></i>
                                            Acesse o lead em <a href="<?php echo url('/leads'); ?>">/leads</a>, 
                                            clique em "Ver Detalhes" e depois em "Converter em Cliente".
                                        </small>
                                    </div>
                                    <a href="<?php echo url('/clients/create'); ?>" class="btn btn-sm btn-primary mt-2">Cadastrar Cliente</a>
                                    <a href="<?php echo url('/leads'); ?>" class="btn btn-sm btn-outline-primary mt-2 d-block">Ver Leads</a>
                                </li>
                            <?php else: ?>
                                <?php foreach ($clients as $client): ?>
                                    <?php 
                                    $clientLeads = $client->leads();
                                    $hasLeads = !empty($clientLeads);
                                    ?>
                                    <li>
                                        <a href="javascript:void(0)" class="px-4 py-3 bg-hover-light-black d-flex align-items-center chat-user" data-client-id="<?php echo $client->id; ?>" data-tipo="<?php echo $client->tipo; ?>">
                                            <span class="position-relative">
                                                <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="ti ti-<?php echo $client->tipo === 'juridica' ? 'building' : 'user'; ?> text-primary"></i>
                                                </div>
                                                <?php if ($hasLeads): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info" style="font-size: 0.6rem;" title="Criado a partir de lead">
                                                        <i class="ti ti-arrow-right"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <div class="ms-6 d-inline-block w-75">
                                                <h6 class="mb-1 fw-semibold chat-title">
                                                    <?php echo e($client->nome_razao_social); ?>
                                                    <?php if ($hasLeads): ?>
                                                        <span class="badge bg-info-subtle text-info ms-1" style="font-size: 0.7rem;" title="Convertido de lead">
                                                            Lead
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="fs-2 text-body-color d-block">
                                                    <?php echo e($client->email ?? 'Sem email'); ?>
                                                </span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Detalhes do Cliente -->
            <div class="w-100">
                <div class="chat-container h-100 w-100">
                    <div class="chat-box-inner-part h-100">
                        <div class="chatting-box app-email-chatting-box">
                            <div class="p-9 py-3 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                                <h5 class="text-dark mb-0 fs-5">Detalhes do Cliente</h5>
                                <ul class="list-unstyled mb-0 d-flex align-items-center" id="clientActions" style="display: none;">
                                    <li class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Editar">
                                        <a class="d-block text-dark px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)" id="editClientBtn">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    </li>
                                    <li class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Excluir">
                                        <a class="text-dark px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)" id="deleteClientBtn">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="position-relative overflow-hidden">
                                <div class="position-relative">
                                    <div class="chat-box email-box mh-n100 p-9" data-simplebar="init" id="clientDetails">
                                        <div class="text-center text-muted py-5">
                                            <i class="ti ti-user-off fs-1 d-block mb-3"></i>
                                            <p class="mb-0">Selecione um cliente para ver os detalhes</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offcanvas para Mobile -->
        <div class="offcanvas offcanvas-start user-chat-box" tabindex="-1" id="client-sidebar" aria-labelledby="clientSidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="clientSidebarLabel">Clientes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="px-9 pt-4 pb-3">
                <a href="<?php echo url('/clients/create'); ?>" class="btn btn-primary fw-semibold py-8 w-100">Novo Cliente</a>
            </div>
            <ul class="list-group h-n150" data-simplebar>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 active" href="javascript:void(0)" data-filter="all">
                        <i class="ti ti-inbox fs-5"></i>Todos os Clientes
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)" data-filter="fisica">
                        <i class="ti ti-user"></i>Pessoa Física
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)" data-filter="juridica">
                        <i class="ti ti-building"></i>Pessoa Jurídica
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
let currentClientId = null;

// Filtro por tipo
document.querySelectorAll('[data-filter]').forEach(link => {
    link.addEventListener('click', function() {
        const filter = this.getAttribute('data-filter');
        document.querySelectorAll('[data-filter]').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        if (filter === 'all') {
            document.querySelectorAll('[data-tipo]').forEach(item => {
                item.closest('li').style.display = '';
            });
        } else {
            document.querySelectorAll('[data-tipo]').forEach(item => {
                if (item.getAttribute('data-tipo') === filter) {
                    item.closest('li').style.display = '';
                } else {
                    item.closest('li').style.display = 'none';
                }
            });
        }
    });
});

// Busca
function handleSearch(query) {
    const searchTerm = query.toLowerCase();
    document.querySelectorAll('.chat-user').forEach(item => {
        const name = item.querySelector('.chat-title').textContent.toLowerCase();
        const email = item.querySelector('.fs-2').textContent.toLowerCase();
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            item.closest('li').style.display = '';
        } else {
            item.closest('li').style.display = 'none';
        }
    });
}

document.getElementById('searchInput')?.addEventListener('input', function() {
    handleSearch(this.value);
});

document.getElementById('searchInputDesktop')?.addEventListener('input', function() {
    handleSearch(this.value);
});

// Seleção de cliente
document.querySelectorAll('[data-client-id]').forEach(link => {
    link.addEventListener('click', function() {
        const clientId = this.getAttribute('data-client-id');
        currentClientId = clientId;
        
        // Remove active de todos
        document.querySelectorAll('.chat-user').forEach(item => {
            item.classList.remove('bg-light-subtle');
        });
        // Adiciona active no selecionado
        this.classList.add('bg-light-subtle');
        
        // Carrega detalhes
        loadClientDetails(clientId);
    });
});

function loadClientDetails(clientId) {
    fetch(`<?php echo url('/clients/'); ?>${clientId}/details`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('clientDetails').innerHTML = html;
            document.getElementById('clientActions').style.display = 'flex';
            document.getElementById('editClientBtn').href = `<?php echo url('/clients/'); ?>${clientId}/edit`;
            document.getElementById('deleteClientBtn').onclick = () => deleteClient(clientId);
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('clientDetails').innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes do cliente</div>';
        });
}

function deleteClient(clientId) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?php echo url('/clients/'); ?>${clientId}/delete`;
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();

// Scripts
ob_start();
?>
<script src="<?php echo asset('tema/assets/libs/simplebar/dist/simplebar.min.js'); ?>"></script>
<?php
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>

