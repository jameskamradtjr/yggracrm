<?php
/**
 * Scripts do Tom Select - Incluir no final da página
 */

if (!isset($GLOBALS['tom_select_inits']) || empty($GLOBALS['tom_select_inits'])) {
    return;
}
?>

<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
/* Tom Select - Ajustes para modal */
.ts-wrapper {
    width: 100% !important;
}

.ts-dropdown {
    z-index: 10060 !important;
}

.modal .ts-dropdown {
    z-index: 10060 !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("=== Inicializando Tom Select Clientes ===");
    
    // Inicializa todos os Tom Selects registrados
    <?php foreach ($GLOBALS['tom_select_inits'] as $config): ?>
    (function() {
        var element = document.getElementById("<?php echo $config['id']; ?>");
        if (!element) {
            console.warn("Elemento #<?php echo $config['id']; ?> não encontrado");
            return;
        }
        
        var type = "<?php echo $config['type'] ?? 'client'; ?>";
        var searchUrl = type === 'lead' 
            ? "<?php echo url('/drive/search/leads'); ?>"
            : "<?php echo url('/drive/search/clients'); ?>";
        var noResultsText = type === 'lead' 
            ? "Nenhum lead encontrado"
            : "Nenhum cliente encontrado";
        
        var tomSelect = new TomSelect("#<?php echo $config['id']; ?>", {
            valueField: "id",
            labelField: "text",
            searchField: "text",
            placeholder: "<?php echo addslashes($config['placeholder']); ?>",
            loadThrottle: 300,
            preload: false,
            load: function(query, callback) {
                if (query.length < 2) {
                    return callback();
                }
                
                var url = searchUrl + "?q=" + encodeURIComponent(query) + "&page=1";
                
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.results || []);
                    })
                    .catch(error => {
                        console.error("Erro na busca:", error);
                        callback();
                    });
            },
            render: {
                option: function(data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
                item: function(data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
                no_results: function(data, escape) {
                    return "<div class='no-results'>" + noResultsText + "</div>";
                }
            },
            onItemAdd: function(value) {
                // Quando um item é adicionado, salva os dados extras no options
                var item = this.options[value];
                if (item && item.email) {
                    // Os dados já estão no item retornado pela API
                }
            },
            plugins: ["clear_button"]
        });
        
        // Salvar instância no elemento
        element.tomselect = tomSelect;
        
        console.log("✓ Tom Select inicializado:", "<?php echo $config['id']; ?>");
        
        // Dispara evento customizado após inicialização
        var event = new CustomEvent('tomselect:initialized', {
            detail: {
                element: element,
                tomSelect: tomSelect,
                id: "<?php echo $config['id']; ?>"
            }
        });
        element.dispatchEvent(event);
    })();
    <?php endforeach; ?>
    
    console.log("=== Tom Select Clientes Inicializados ===");
});
</script>

