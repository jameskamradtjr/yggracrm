<?php
/**
 * Componente Tom Select para busca de Clientes
 * 
 * @param string $id - ID do elemento select (padrão: 'client_id')
 * @param string $name - Nome do campo no formulário (padrão: 'client_id')
 * @param string $placeholder - Texto do placeholder (padrão: 'Digite para buscar cliente...')
 * @param mixed $selected - Valor pré-selecionado (opcional)
 * @param bool $required - Se o campo é obrigatório (padrão: false)
 * @param string $class - Classes CSS adicionais (opcional)
 */

$id = $id ?? 'client_id';
$name = $name ?? 'client_id';
$placeholder = $placeholder ?? 'Digite para buscar cliente...';
$selected = $selected ?? '';
$required = $required ?? false;
$class = $class ?? '';
?>

<select class="form-control tom-select-client <?php echo $class; ?>" 
        id="<?php echo $id; ?>" 
        name="<?php echo $name; ?>"
        <?php echo $required ? 'required' : ''; ?>>
    <option value="">Selecione um cliente...</option>
    <?php if ($selected): ?>
        <option value="<?php echo $selected; ?>" selected>Carregando...</option>
    <?php endif; ?>
</select>

<?php
// Adiciona scripts e estilos apenas uma vez
if (!isset($GLOBALS['tom_select_loaded'])): 
    $GLOBALS['tom_select_loaded'] = true;
?>

<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">

<?php endif; ?>

<?php
// Registra inicialização do Tom Select para este elemento
if (!isset($GLOBALS['tom_select_inits'])) {
    $GLOBALS['tom_select_inits'] = [];
}

$GLOBALS['tom_select_inits'][] = [
    'id' => $id,
    'placeholder' => $placeholder
];
?>

