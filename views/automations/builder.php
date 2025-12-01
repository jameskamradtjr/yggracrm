<?php
$title = isset($automation) ? 'Editar Automação' : 'Criar Automação';
ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8"><?php echo $title; ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/automations'); ?>">Automações</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo isset($automation) ? 'Editar' : 'Criar'; ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end">
                <a href="<?php echo url('/automations'); ?>" class="btn btn-light">
                    <i class="ti ti-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form id="automationForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nome da Automação <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo e(isset($automation) ? $automation->name : ''); ?>" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Status</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       <?php echo (!isset($automation) || (isset($automation) && $automation->is_active)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Automação ativa
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="2"><?php echo e(isset($automation) ? ($automation->description ?? '') : ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Builder Visual -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Componentes</h5>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="components-list">
                                        <!-- Será preenchido via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Workflow</h5>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-success" onclick="saveAutomation()">
                                            <i class="ti ti-device-floppy me-1"></i>
                                            Salvar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="workflow-canvas" style="min-height: 600px; border: 2px dashed #ddd; border-radius: 8px; position: relative; background: #f8f9fa;">
                                        <!-- Canvas para arrastar e conectar nós -->
                                        <div id="nodes-container" style="position: relative; width: 100%; height: 100%; min-height: 600px;">
                                            <!-- Nós serão adicionados aqui -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="workflow_data" name="workflow_data" value='<?php 
                        $workflowData = ['nodes' => [], 'connections' => []];
                        if (isset($automation) && $automation) {
                            $workflowData = $automation->getWorkflowData();
                        }
                        echo json_encode($workflowData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
                    ?>'>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para configurar nó -->
<div class="modal fade" id="nodeConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nodeConfigModalTitle">Configurar Nó</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="nodeConfigModalBody">
                <!-- Configuração será preenchida via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNodeConfig()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<style>
.node {
    position: absolute;
    min-width: 200px;
    padding: 15px;
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: move;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 10;
}

.node.trigger {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.node.condition {
    border-color: #ffc107;
    background: #fff3cd;
}

.node.action {
    border-color: #198754;
    background: #d1e7dd;
}

.node-header {
    font-weight: bold;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.node-body {
    font-size: 0.9rem;
    color: #666;
}

.node-connector {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #333;
    position: absolute;
    cursor: pointer;
    z-index: 20;
    transition: all 0.2s;
}

.node-connector:hover {
    background: #0d6efd;
    border-color: #0d6efd;
    transform: scale(1.2);
}

.node-connector.input {
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
}

.node-connector.output {
    right: -10px;
    top: 50%;
    transform: translateY(-50%);
}

.connection-line {
    position: absolute;
    pointer-events: none;
    z-index: 1;
}

.component-item {
    padding: 10px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: grab;
    transition: all 0.2s;
}

.component-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.component-item:active {
    cursor: grabbing;
}
</style>

<script>
let components = <?php 
    // Garante que components existe e tem a estrutura correta
    if (!isset($components) || !is_array($components)) {
        $components = ['triggers' => [], 'conditions' => [], 'actions' => []];
    }
    // Garante que cada chave é um array
    if (!isset($components['triggers']) || !is_array($components['triggers'])) {
        $components['triggers'] = [];
    }
    if (!isset($components['conditions']) || !is_array($components['conditions'])) {
        $components['conditions'] = [];
    }
    if (!isset($components['actions']) || !is_array($components['actions'])) {
        $components['actions'] = [];
    }
    echo json_encode($components, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
?>;
let nodes = [];
let connections = [];
let currentNodeId = null;
let nodeCounter = 0;
let isDragging = false;
let dragOffset = { x: 0, y: 0 };
let selectedNode = null;

// Debug: verifica se componentes foram carregados
console.log('Componentes carregados:', components);
console.log('Tipo de components:', typeof components);
console.log('É array?', Array.isArray(components));
console.log('Tem triggers?', components?.triggers);
console.log('Tem conditions?', components?.conditions);
console.log('Tem actions?', components?.actions);

// Inicializa o builder
document.addEventListener('DOMContentLoaded', function() {
    loadComponents();
    loadWorkflow();
    initializeCanvas();
});

function loadComponents() {
    const container = document.getElementById('components-list');
    if (!container) {
        console.error('Container de componentes não encontrado!');
        return;
    }
    
    container.innerHTML = '';
    
    // Verifica se components existe e tem dados
    if (!components || typeof components !== 'object') {
        container.innerHTML = '<p class="text-muted">Carregando componentes...</p>';
        // Tenta carregar via AJAX
        fetch('<?php echo url('/api/automations/components'); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.components) {
                    components = data.components;
                    renderComponents();
                } else {
                    container.innerHTML = '<p class="text-danger">Erro ao carregar componentes</p>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar componentes:', error);
                container.innerHTML = '<p class="text-danger">Erro ao carregar componentes</p>';
            });
        return;
    }
    
    renderComponents();
}

function renderComponents() {
    const container = document.getElementById('components-list');
    if (!container) {
        console.error('Container de componentes não encontrado!');
        return;
    }
    
    container.innerHTML = '';
    
    // Verifica estrutura de components
    console.log('Estrutura de components:', components);
    console.log('Tipo:', typeof components);
    console.log('Triggers:', components?.triggers);
    console.log('Conditions:', components?.conditions);
    console.log('Actions:', components?.actions);
    
    // Triggers
    if (components && components.triggers && Array.isArray(components.triggers) && components.triggers.length > 0) {
        const triggerSection = document.createElement('div');
        triggerSection.innerHTML = '<h6 class="text-primary mb-2">Gatilhos</h6>';
        components.triggers.forEach(trigger => {
            const item = createComponentItem(trigger, 'trigger');
            triggerSection.appendChild(item);
        });
        container.appendChild(triggerSection);
    } else {
        console.warn('Nenhum trigger encontrado. Components:', components);
    }
    
    // Conditions
    if (components && components.conditions && Array.isArray(components.conditions) && components.conditions.length > 0) {
        const conditionSection = document.createElement('div');
        conditionSection.innerHTML = '<h6 class="text-warning mb-2 mt-3">Condições</h6>';
        components.conditions.forEach(condition => {
            const item = createComponentItem(condition, 'condition');
            conditionSection.appendChild(item);
        });
        container.appendChild(conditionSection);
    } else {
        console.warn('Nenhuma condição encontrada');
    }
    
    // Actions
    if (components && components.actions && Array.isArray(components.actions) && components.actions.length > 0) {
        const actionSection = document.createElement('div');
        actionSection.innerHTML = '<h6 class="text-success mb-2 mt-3">Ações</h6>';
        components.actions.forEach(action => {
            const item = createComponentItem(action, 'action');
            actionSection.appendChild(item);
        });
        container.appendChild(actionSection);
    } else {
        console.warn('Nenhuma ação encontrada');
    }
    
    if (container.innerHTML === '') {
        container.innerHTML = '<p class="text-muted">Nenhum componente disponível. <br><small>Verifique o console (F12) para mais detalhes.</small></p>';
    }
}

function createComponentItem(component, type) {
    const item = document.createElement('div');
    item.className = 'component-item';
    item.draggable = true;
    item.innerHTML = `
        <strong>${component.name}</strong>
        <br><small class="text-muted">${component.description || ''}</small>
    `;
    
    item.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('component', JSON.stringify({...component, nodeType: type}));
    });
    
    return item;
}

function initializeCanvas() {
    const canvas = document.getElementById('workflow-canvas');
    
    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
    });
    
    canvas.addEventListener('drop', (e) => {
        e.preventDefault();
        const data = JSON.parse(e.dataTransfer.getData('component'));
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        addNode(data, x, y);
    });
}

function addNode(component, x, y) {
    nodeCounter++;
    const nodeId = `node_${nodeCounter}`;
    const nodeType = component.nodeType;
    const componentId = component.id;
    
    const node = {
        id: nodeId,
        type: `${nodeType}_${componentId}`,
        component: componentId,
        x: x,
        y: y,
        config: {}
    };
    
    nodes.push(node);
    renderNode(node);
    updateWorkflowData();
}

function renderNode(node) {
    const container = document.getElementById('nodes-container');
    const nodeEl = document.createElement('div');
    nodeEl.id = node.id;
    nodeEl.className = `node ${node.component.split('_')[0]}`;
    nodeEl.style.left = node.x + 'px';
    nodeEl.style.top = node.y + 'px';
    
    const component = findComponent(node.type);
    const typeLabel = node.type.startsWith('trigger_') ? 'Gatilho' : 
                      node.type.startsWith('condition_') ? 'Condição' : 'Ação';
    
    // Cria estrutura do nó
    const header = document.createElement('div');
    header.className = 'node-header';
    header.innerHTML = `
        <span>${component?.name || typeLabel}</span>
        <div>
            <button type="button" class="btn btn-sm btn-link p-0" onclick="configureNode('${node.id}')" title="Configurar">
                <i class="ti ti-settings"></i>
            </button>
            <button type="button" class="btn btn-sm btn-link p-0 text-danger" onclick="removeNode('${node.id}')" title="Remover">
                <i class="ti ti-x"></i>
            </button>
        </div>
    `;
    
    const body = document.createElement('div');
    body.className = 'node-body';
    body.textContent = component?.description || '';
    
    nodeEl.appendChild(header);
    nodeEl.appendChild(body);
    
    // Adiciona conectores
    if (!node.type.startsWith('trigger_')) {
        const inputConnector = document.createElement('div');
        inputConnector.className = 'node-connector input';
        inputConnector.setAttribute('data-node-id', node.id);
        inputConnector.setAttribute('title', 'Conectar aqui');
        inputConnector.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            console.log('Input connector clicked for node:', node.id);
            endConnection(node.id);
        });
        nodeEl.appendChild(inputConnector);
    }
    
    if (!node.type.startsWith('action_')) {
        const outputConnector = document.createElement('div');
        outputConnector.className = 'node-connector output';
        outputConnector.setAttribute('data-node-id', node.id);
        outputConnector.setAttribute('title', 'Iniciar conexão');
        outputConnector.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            console.log('Output connector clicked for node:', node.id);
            startConnection(node.id);
        });
        nodeEl.appendChild(outputConnector);
    }
    
    // Torna o nó arrastável
    makeNodeDraggable(nodeEl, node);
    
    container.appendChild(nodeEl);
}

function makeNodeDraggable(nodeEl, node) {
    let isDragging = false;
    let offset = { x: 0, y: 0 };
    let updateTimeout = null;
    
    nodeEl.addEventListener('mousedown', (e) => {
        if (e.target.closest('button') || e.target.closest('.node-connector')) return;
        isDragging = true;
        const rect = nodeEl.getBoundingClientRect();
        const canvas = document.getElementById('workflow-canvas');
        const canvasRect = canvas.getBoundingClientRect();
        offset.x = e.clientX - canvasRect.left - parseFloat(nodeEl.style.left);
        offset.y = e.clientY - canvasRect.top - parseFloat(nodeEl.style.top);
        nodeEl.style.cursor = 'grabbing';
    });
    
    let renderTimeout = null;
    
    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const canvas = document.getElementById('workflow-canvas');
        const rect = canvas.getBoundingClientRect();
        node.x = e.clientX - rect.left - offset.x;
        node.y = e.clientY - rect.top - offset.y;
        nodeEl.style.left = node.x + 'px';
        nodeEl.style.top = node.y + 'px';
        
        // Debounce para renderizar conexões (evita múltiplas renderizações)
        if (renderTimeout) {
            clearTimeout(renderTimeout);
        }
        renderTimeout = setTimeout(() => {
            renderConnections();
        }, 16); // ~60fps
        
        // Debounce para updateWorkflowData (não precisa salvar a cada pixel)
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }
        updateTimeout = setTimeout(() => {
            updateWorkflowData();
        }, 100);
    });
    
    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            nodeEl.style.cursor = 'move';
            
            // Limpa timeouts
            if (renderTimeout) {
                clearTimeout(renderTimeout);
                renderTimeout = null;
            }
            if (updateTimeout) {
                clearTimeout(updateTimeout);
                updateTimeout = null;
            }
            
            // Atualiza conexões uma última vez (garantir que está correto)
            setTimeout(() => {
                renderConnections();
                updateWorkflowData();
            }, 10);
        }
    });
}

function findComponent(nodeType) {
    const parts = nodeType.split('_');
    const type = parts[0];
    const id = parts.slice(1).join('_');
    
    if (type === 'trigger') {
        return components.triggers?.find(t => t.id === id);
    } else if (type === 'condition') {
        return components.conditions?.find(c => c.id === id);
    } else if (type === 'action') {
        return components.actions?.find(a => a.id === id);
    }
    return null;
}

function configureNode(nodeId) {
    const node = nodes.find(n => n.id === nodeId);
    if (!node) return;
    
    currentNodeId = nodeId;
    const component = findComponent(node.type);
    
    document.getElementById('nodeConfigModalTitle').textContent = `Configurar: ${component?.name || 'Nó'}`;
    
    const body = document.getElementById('nodeConfigModalBody');
    body.innerHTML = generateConfigForm(component, node.config);
    
    const modal = new bootstrap.Modal(document.getElementById('nodeConfigModal'));
    modal.show();
}

function generateConfigForm(component, currentConfig) {
    if (!component) {
        return '<p class="text-muted">Componente não encontrado.</p>';
    }
    
    const schema = component.schema || [];
    if (!Array.isArray(schema) || schema.length === 0) {
        return '<p class="text-muted">Este componente não requer configuração.</p>';
    }
    
    let html = '<form id="nodeConfigForm">';
    schema.forEach(field => {
        const value = (currentConfig && currentConfig[field.name]) || '';
        html += `<div class="mb-3">`;
        html += `<label class="form-label">${field.label || field.name}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;
        
        if (field.type === 'select') {
            html += `<select class="form-select" name="${field.name}" ${field.required ? 'required' : ''} data-field-type="select" data-field-name="${field.name}">`;
            html += `<option value="">Selecione...</option>`;
            
            // Se tem options, usa elas
            if (field.options && Array.isArray(field.options) && field.options.length > 0) {
                field.options.forEach(option => {
                    const optValue = typeof option === 'object' ? option.value : option;
                    const optLabel = typeof option === 'object' ? option.label : option;
                    html += `<option value="${optValue}" ${value == optValue ? 'selected' : ''}>${optLabel}</option>`;
                });
            } else if (field.loadOptions) {
                // Campo que precisa carregar opções dinamicamente
                html += `<option value="">Carregando...</option>`;
            }
            
            html += `</select>`;
            
            // Se precisa carregar opções dinamicamente
            if (field.loadOptions === 'tags') {
                loadTagsForSelect(field.name);
            } else if (field.loadOptions === 'users') {
                loadUsersForSelect(field.name);
            } else if (field.loadOptions === 'origins') {
                loadOriginsForSelect(field.name);
            }
        } else if (field.type === 'textarea') {
            html += `<textarea class="form-control" name="${field.name}" rows="4" ${field.required ? 'required' : ''}>${value}</textarea>`;
        } else {
            html += `<input type="${field.type || 'text'}" class="form-control" name="${field.name}" value="${value}" ${field.required ? 'required' : ''} placeholder="${field.placeholder || ''}">`;
        }
        
        html += `</div>`;
    });
    html += '</form>';
    
    return html;
}

function loadTagsForSelect(selectName) {
    fetch('<?php echo url('/api/tags'); ?>')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector(`select[name="${selectName}"]`);
            if (!select) return;
            
            // Limpa opções de carregamento
            select.innerHTML = '<option value="">Selecione uma tag...</option>';
            
            if (data.success && data.tags && Array.isArray(data.tags)) {
                data.tags.forEach(tag => {
                    const option = document.createElement('option');
                    option.value = tag.id;
                    option.textContent = tag.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar tags:', error);
        });
}

function loadUsersForSelect(selectName) {
    fetch('<?php echo url('/api/users'); ?>')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector(`select[name="${selectName}"]`);
            if (!select) return;
            
            select.innerHTML = '<option value="">Selecione um usuário...</option>';
            
            if (data.success && data.users && Array.isArray(data.users)) {
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name || user.email;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar usuários:', error);
        });
}

function loadOriginsForSelect(selectName) {
    fetch('<?php echo url('/api/lead-origins'); ?>')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector(`select[name="${selectName}"]`);
            if (!select) return;
            
            select.innerHTML = '<option value="">Selecione uma origem...</option>';
            
            if (data.success && data.origins && Array.isArray(data.origins)) {
                data.origins.forEach(origin => {
                    const option = document.createElement('option');
                    option.value = origin.id;
                    option.textContent = origin.nome;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar origens:', error);
        });
}

function saveNodeConfig() {
    const form = document.getElementById('nodeConfigForm');
    if (!form || !currentNodeId) return;
    
    const formData = new FormData(form);
    const config = {};
    for (const [key, value] of formData.entries()) {
        config[key] = value;
    }
    
    const node = nodes.find(n => n.id === currentNodeId);
    if (node) {
        node.config = config;
        updateWorkflowData();
    }
    
    bootstrap.Modal.getInstance(document.getElementById('nodeConfigModal')).hide();
}

function removeNode(nodeId) {
    if (!confirm('Tem certeza que deseja remover este nó?')) return;
    
    nodes = nodes.filter(n => n.id !== nodeId);
    connections = connections.filter(c => c.source !== nodeId && c.target !== nodeId);
    
    const nodeEl = document.getElementById(nodeId);
    if (nodeEl) {
        nodeEl.remove();
    }
    
    updateWorkflowData();
}

function loadWorkflow() {
    const workflowData = JSON.parse(document.getElementById('workflow_data').value || '{"nodes": [], "connections": []}');
    nodes = workflowData.nodes || [];
    connections = workflowData.connections || [];
    
    nodes.forEach(node => {
        renderNode(node);
    });
    
    // Renderiza conexões
    renderConnections();
}

function updateWorkflowData() {
    document.getElementById('workflow_data').value = JSON.stringify({
        nodes: nodes,
        connections: connections
    });
    // Pequeno delay para garantir que os nós foram renderizados
    setTimeout(() => {
        renderConnections();
    }, 10);
}

function renderConnections() {
    const canvas = document.getElementById('nodes-container');
    if (!canvas) {
        return;
    }
    
    // Remove TODAS as conexões antigas de forma mais agressiva
    // Busca especificamente por SVGs de conexão
    const oldConnections = canvas.querySelectorAll('svg.connection-line, svg[data-connection]');
    oldConnections.forEach(el => {
        el.remove();
    });
    
    // Também remove do canvas pai se houver
    const workflowCanvas = document.getElementById('workflow-canvas');
    if (workflowCanvas) {
        const oldConnectionsCanvas = workflowCanvas.querySelectorAll('svg.connection-line, svg[data-connection]');
        oldConnectionsCanvas.forEach(el => {
            el.remove();
        });
    }
    
    // Garante que o marker da seta existe
    ensureArrowMarker();
    
    // Se não há conexões, para aqui
    if (!connections || connections.length === 0) {
        return;
    }
    
    connections.forEach((conn) => {
        const sourceNode = document.getElementById(conn.source);
        const targetNode = document.getElementById(conn.target);
        
        if (!sourceNode || !targetNode) {
            return; // Nó não encontrado, pula esta conexão
        }
        
        // Calcula posições relativas ao canvas
        const sourceRect = sourceNode.getBoundingClientRect();
        const targetRect = targetNode.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        
        // Ponto de saída do nó fonte (lado direito)
        const sourceX = sourceRect.right - canvasRect.left;
        const sourceY = sourceRect.top - canvasRect.top + sourceRect.height / 2;
        
        // Ponto de entrada do nó destino (lado esquerdo)
        const targetX = targetRect.left - canvasRect.left;
        const targetY = targetRect.top - canvasRect.top + targetRect.height / 2;
        
        // Verifica se já existe uma conexão para este par (evita duplicatas)
        const existingConnection = canvas.querySelector(`svg[data-connection="${conn.source}-${conn.target}"]`);
        if (existingConnection) {
            existingConnection.remove();
        }
        
        // Cria SVG para a linha
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.className = 'connection-line';
        svg.style.position = 'absolute';
        svg.style.left = '0';
        svg.style.top = '0';
        svg.style.width = '100%';
        svg.style.height = '100%';
        svg.style.pointerEvents = 'none';
        svg.style.zIndex = '1';
        svg.setAttribute('data-connection', `${conn.source}-${conn.target}`);
        
        // Calcula pontos de controle para curva suave
        const midX = (sourceX + targetX) / 2;
        const controlOffset = Math.abs(targetX - sourceX) * 0.5;
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M ${sourceX} ${sourceY} C ${sourceX + controlOffset} ${sourceY}, ${targetX - controlOffset} ${targetY}, ${targetX} ${targetY}`);
        path.setAttribute('stroke', '#0d6efd');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.setAttribute('marker-end', 'url(#arrowhead)');
        
        svg.appendChild(path);
        canvas.appendChild(svg);
    });
}

function ensureArrowMarker() {
    if (document.getElementById('arrowhead-def')) {
        return; // Já existe
    }
    
    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    defs.id = 'arrowhead-def';
    
    const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
    marker.id = 'arrowhead';
    marker.setAttribute('markerWidth', '10');
    marker.setAttribute('markerHeight', '10');
    marker.setAttribute('refX', '9');
    marker.setAttribute('refY', '3');
    marker.setAttribute('orient', 'auto');
    marker.setAttribute('markerUnits', 'strokeWidth');
    
    const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    polygon.setAttribute('points', '0 0, 10 3, 0 6');
    polygon.setAttribute('fill', '#0d6efd');
    
    marker.appendChild(polygon);
    defs.appendChild(marker);
    
    // Adiciona ao body ou ao primeiro SVG encontrado
    const existingSvg = document.querySelector('svg');
    if (existingSvg) {
        existingSvg.appendChild(defs);
    } else {
        // Cria SVG oculto para armazenar as definições
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.style.position = 'absolute';
        svg.style.width = '0';
        svg.style.height = '0';
        svg.style.overflow = 'hidden';
        svg.appendChild(defs);
        document.body.appendChild(svg);
    }
}

// Adiciona funcionalidade de conectar nós
let connectingFrom = null;
let tempLine = null;

function startConnection(nodeId) {
    console.log('startConnection called with nodeId:', nodeId);
    
    if (connectingFrom) {
        // Já está conectando, cancela
        console.log('Canceling previous connection');
        connectingFrom = null;
        if (tempLine) {
            tempLine.remove();
            tempLine = null;
        }
        const canvas = document.getElementById('workflow-canvas');
        if (canvas) canvas.style.cursor = 'default';
        return;
    }
    
    const node = nodes.find(n => n.id === nodeId);
    if (!node) {
        console.warn('Node not found:', nodeId);
        return;
    }
    
    if (node.type.startsWith('action_')) {
        console.warn('Actions do not have output connectors');
        return; // Ações não têm saída
    }
    
    connectingFrom = nodeId;
    console.log('Starting connection from node:', nodeId);
    
    const canvas = document.getElementById('workflow-canvas');
    if (canvas) {
        canvas.style.cursor = 'crosshair';
        // Adiciona listener para desenhar linha temporária
        canvas.addEventListener('mousemove', drawTempLine);
    }
}

function drawTempLine(e) {
    if (!connectingFrom) return;
    
    const canvas = document.getElementById('workflow-canvas');
    const rect = canvas.getBoundingClientRect();
    const sourceNode = document.getElementById(connectingFrom);
    if (!sourceNode) return;
    
    const sourceRect = sourceNode.getBoundingClientRect();
    const sourceX = sourceRect.right - rect.left;
    const sourceY = sourceRect.top - rect.top + sourceRect.height / 2;
    const targetX = e.clientX - rect.left;
    const targetY = e.clientY - rect.top;
    
    if (tempLine) {
        tempLine.remove();
    }
    
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.className = 'connection-line';
    svg.style.position = 'absolute';
    svg.style.left = '0';
    svg.style.top = '0';
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.pointerEvents = 'none';
    svg.style.zIndex = '5';
    
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    const midX = (sourceX + targetX) / 2;
    path.setAttribute('d', `M ${sourceX} ${sourceY} C ${midX} ${sourceY}, ${midX} ${targetY}, ${targetX} ${targetY}`);
    path.setAttribute('stroke', '#0d6efd');
    path.setAttribute('stroke-width', '2');
    path.setAttribute('stroke-dasharray', '5,5');
    path.setAttribute('fill', 'none');
    
    svg.appendChild(path);
    document.getElementById('nodes-container').appendChild(svg);
    tempLine = svg;
}

function endConnection(nodeId) {
    console.log('endConnection called with nodeId:', nodeId, 'connectingFrom:', connectingFrom);
    
    const canvas = document.getElementById('workflow-canvas');
    if (canvas) {
        canvas.removeEventListener('mousemove', drawTempLine);
    }
    
    if (tempLine) {
        tempLine.remove();
        tempLine = null;
    }
    
    if (connectingFrom && connectingFrom !== nodeId) {
        const targetNode = nodes.find(n => n.id === nodeId);
        if (!targetNode) {
            console.warn('Target node not found:', nodeId);
            connectingFrom = null;
            if (canvas) canvas.style.cursor = 'default';
            return;
        }
        
        if (targetNode.type.startsWith('trigger_')) {
            // Triggers não podem receber conexões
            console.warn('Cannot connect to trigger node');
            connectingFrom = null;
            if (canvas) canvas.style.cursor = 'default';
            return;
        }
        
        // Verifica se já existe conexão
        const exists = connections.some(c => 
            c.source === connectingFrom && c.target === nodeId
        );
        
        if (!exists) {
            connections.push({
                source: connectingFrom,
                target: nodeId
            });
            console.log('Connection created:', { source: connectingFrom, target: nodeId });
            console.log('All connections:', connections);
            // Força re-renderização imediata
            setTimeout(() => {
                renderConnections();
            }, 50);
            updateWorkflowData();
        } else {
            console.log('Connection already exists');
        }
    } else if (!connectingFrom) {
        console.log('No active connection to end');
    }
    
    connectingFrom = null;
    if (canvas) canvas.style.cursor = 'default';
}

// Cancela conexão ao clicar fora
document.addEventListener('click', function(e) {
    if (connectingFrom && !e.target.closest('.node-connector') && !e.target.closest('.node')) {
        connectingFrom = null;
        if (tempLine) {
            tempLine.remove();
            tempLine = null;
        }
        document.getElementById('workflow-canvas').style.cursor = 'default';
        const canvas = document.getElementById('workflow-canvas');
        canvas.removeEventListener('mousemove', drawTempLine);
    }
});

function saveAutomation() {
    const form = document.getElementById('automationForm');
    const formData = new FormData(form);
    
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        is_active: formData.get('is_active') === 'on',
        workflow_data: JSON.parse(formData.get('workflow_data'))
    };
    
    const url = <?php echo isset($automation) ? "'" . url('/automations/' . $automation->id) . "'" : "'" . url('/automations') . "'"; ?>;
    const method = <?php echo isset($automation) ? "'POST'" : "'POST'"; ?>;
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Automação salva com sucesso!');
            window.location.href = '<?php echo url('/automations'); ?>';
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar automação');
    });
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

