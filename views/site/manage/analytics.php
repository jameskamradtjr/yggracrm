<?php
$title = $title ?? 'Analytics do Site';
ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Analytics do Site</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/site/manage'); ?>">Meu Site</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Analytics</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/site/manage'); ?>" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="ti ti-chart-line me-2"></i>
                        Métricas de Tráfego Orgânico
                    </h4>
                    <div class="d-flex gap-2">
                        <select id="daysFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Últimos 7 dias</option>
                            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Últimos 30 dias</option>
                            <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Últimos 90 dias</option>
                        </select>
                        <a href="<?php echo url('/site/manage'); ?>" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Cards de Métricas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                                <i class="ti ti-cursor-click text-primary" style="font-size: 24px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-muted mb-1">Cliques</h6>
                                            <h3 class="mb-0"><?php echo number_format($metrics['clicks']); ?></h3>
                                            <?php if ($metrics['clicks_diff'] != 0): ?>
                                                <small class="<?php echo $metrics['clicks_diff'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <i class="ti ti-arrow-<?php echo $metrics['clicks_diff'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo number_format(abs($metrics['clicks_diff'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="bg-info bg-opacity-10 rounded p-3">
                                                <i class="ti ti-eye text-info" style="font-size: 24px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-muted mb-1">Impressões</h6>
                                            <h3 class="mb-0"><?php echo number_format($metrics['impressions']); ?></h3>
                                            <?php if ($metrics['impressions_diff'] != 0): ?>
                                                <small class="<?php echo $metrics['impressions_diff'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <i class="ti ti-arrow-<?php echo $metrics['impressions_diff'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo number_format(abs($metrics['impressions_diff'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                                <i class="ti ti-clock text-warning" style="font-size: 24px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-muted mb-1">CTR Médio</h6>
                                            <h3 class="mb-0"><?php echo number_format($metrics['ctr'], 2); ?>%</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="bg-success bg-opacity-10 rounded p-3">
                                                <i class="ti ti-chart-bar text-success" style="font-size: 24px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-muted mb-1">Visualizações</h6>
                                            <h3 class="mb-0"><?php echo number_format($metrics['pageviews']); ?></h3>
                                            <?php if ($metrics['pageviews_diff'] != 0): ?>
                                                <small class="<?php echo $metrics['pageviews_diff'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <i class="ti ti-arrow-<?php echo $metrics['pageviews_diff'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo number_format(abs($metrics['pageviews_diff'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Visualizações -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Visualizações ao Longo do Tempo</h5>
                                </div>
                                <div class="card-body">
                                    <div id="viewsChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabelas de Dados -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Top Posts</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($topPosts)): ?>
                                        <p class="text-muted mb-0">Nenhum dado disponível</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Post</th>
                                                        <th class="text-end">Visualizações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topPosts as $post): ?>
                                                        <tr>
                                                            <td><?php echo e($post['title']); ?></td>
                                                            <td class="text-end"><?php echo number_format($post['views']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Origem do Tráfego</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($trafficSources)): ?>
                                        <p class="text-muted mb-0">Nenhum dado disponível</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Origem</th>
                                                        <th class="text-end">Visitas</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($trafficSources as $source): ?>
                                                        <tr>
                                                            <td><?php echo e($source['source']); ?></td>
                                                            <td class="text-end"><?php echo number_format($source['count']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Adiciona scripts necessários para charts usando ApexCharts
$GLOBALS['analytics_scripts'] = ($GLOBALS['analytics_scripts'] ?? '') . '
<script src="' . asset('tema/assets/libs/apexcharts/dist/apexcharts.min.js') . '"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Filtro de dias
    const daysFilter = document.getElementById("daysFilter");
    if (daysFilter) {
        daysFilter.addEventListener("change", function() {
            const days = this.value;
            window.location.href = "' . url('/site/manage/analytics') . '?days=" + days;
        });
    }
    
    // Gráfico de visualizações usando ApexCharts
    const viewsData = ' . json_encode($viewsByDay) . ';
    const labels = viewsData.map(function(item) {
        const date = new Date(item.date);
        return date.toLocaleDateString("pt-BR", { day: "2-digit", month: "2-digit" });
    });
    const counts = viewsData.map(function(item) {
        return parseInt(item.count);
    });
    
    const chartElement = document.getElementById("viewsChart");
    if (chartElement && typeof ApexCharts !== "undefined") {
        const chart = new ApexCharts(chartElement, {
            series: [{
                name: "Visualizações",
                data: counts
            }],
            chart: {
                type: "line",
                height: 350,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: "smooth",
                width: 3
            },
            colors: ["#5D87FF"],
            fill: {
                type: "gradient",
                gradient: {
                    shade: "light",
                    type: "vertical",
                    shadeIntensity: 0.3,
                    gradientToColors: ["#5D87FF"],
                    inverseColors: false,
                    opacityFrom: 0.7,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: labels
            },
            yaxis: {
                min: 0
            },
            grid: {
                borderColor: "#e0e6ed",
                strokeDashArray: 5,
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },
            tooltip: {
                theme: "light"
            }
        });
        
        chart.render();
    }
});
</script>
';

// Retorna o conteúdo para ser usado no layout
echo $content;
