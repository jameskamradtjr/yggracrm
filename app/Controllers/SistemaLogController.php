<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;

/**
 * Controller de Logs Gerais do Sistema
 */
class SistemaLogController extends Controller
{
    /**
     * Lista logs com paginação e filtros
     */
    public function index(): string
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('sistema', 'logs', 'view');
        
        $db = Database::getInstance();
        
        // Busca tabelas únicas para filtro
        $tabelas = $db->query(
            "SELECT DISTINCT tabela FROM sistema_logs 
             ORDER BY tabela"
        );
        
        // Busca ações únicas para filtro
        $acoes = $db->query(
            "SELECT DISTINCT acao FROM sistema_logs 
             ORDER BY acao"
        );
        
        return $this->view('sistema/logs/index', [
            'tabelas' => $tabelas,
            'acoes' => $acoes
        ]);
    }

    /**
     * API para DataTable com paginação server-side
     */
    public function datatable(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $db = Database::getInstance();
            
            // Parâmetros do DataTable
            $draw = (int)($this->request->get('draw') ?? 1);
            $start = (int)($this->request->get('start') ?? 0);
            $length = (int)($this->request->get('length') ?? 10);
            $search = $this->request->get('search')['value'] ?? '';
            $orderColumn = (int)($this->request->get('order')[0]['column'] ?? 0);
            $orderDir = $this->request->get('order')[0]['dir'] ?? 'DESC';
            
            // Filtros customizados
            $filtroTabela = $this->request->get('filtro_tabela') ?? '';
            $filtroAcao = $this->request->get('filtro_acao') ?? '';
            $filtroUsuario = $this->request->get('filtro_usuario') ?? '';
            $filtroDataInicio = $this->request->get('filtro_data_inicio') ?? '';
            $filtroDataFim = $this->request->get('filtro_data_fim') ?? '';
            
            // Colunas para ordenação
            $columns = [
                'sl.id',
                'sl.tabela',
                'sl.registro_id',
                'sl.acao',
                'sl.descricao',
                'u.name',
                'sl.ip_address',
                'sl.created_at'
            ];
            
            $orderBy = $columns[$orderColumn] ?? 'sl.created_at';
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            
            // Construção da query base
            $whereConditions = [];
            $params = [];
            
            // Filtro de busca geral
            if (!empty($search)) {
                $whereConditions[] = "(sl.tabela LIKE ? OR sl.descricao LIKE ? OR sl.acao LIKE ? OR u.name LIKE ? OR sl.ip_address LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Filtros específicos
            if (!empty($filtroTabela)) {
                $whereConditions[] = "sl.tabela = ?";
                $params[] = $filtroTabela;
            }
            
            if (!empty($filtroAcao)) {
                $whereConditions[] = "sl.acao = ?";
                $params[] = $filtroAcao;
            }
            
            if (!empty($filtroUsuario)) {
                $whereConditions[] = "u.name LIKE ?";
                $params[] = "%{$filtroUsuario}%";
            }
            
            if (!empty($filtroDataInicio)) {
                $whereConditions[] = "DATE(sl.created_at) >= ?";
                $params[] = $filtroDataInicio;
            }
            
            if (!empty($filtroDataFim)) {
                $whereConditions[] = "DATE(sl.created_at) <= ?";
                $params[] = $filtroDataFim;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Query para contar total de registros
            $countQuery = "
                SELECT COUNT(*) as total
                FROM sistema_logs sl
                LEFT JOIN users u ON sl.usuario_id = u.id
                {$whereClause}
            ";
            
            $totalRecords = $db->queryOne($countQuery, $params)['total'] ?? 0;
            
            // Query para buscar dados paginados
            $dataQuery = "
                SELECT 
                    sl.id,
                    sl.tabela,
                    sl.registro_id,
                    sl.acao,
                    sl.descricao,
                    u.name as usuario_nome,
                    u.email as usuario_email,
                    sl.ip_address,
                    sl.created_at,
                    sl.dados_anteriores,
                    sl.dados_novos
                FROM sistema_logs sl
                LEFT JOIN users u ON sl.usuario_id = u.id
                {$whereClause}
                ORDER BY {$orderBy} {$orderDir}
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $length;
            $params[] = $start;
            
            $logs = $db->query($dataQuery, $params);
            
            // Formata dados para o DataTable
            $data = [];
            foreach ($logs as $log) {
                $acaoBadge = match($log['acao']) {
                    'CREATE' => '<span class="badge bg-success">Criar</span>',
                    'UPDATE' => '<span class="badge bg-primary">Atualizar</span>',
                    'DELETE' => '<span class="badge bg-danger">Deletar</span>',
                    'VIEW' => '<span class="badge bg-info">Visualizar</span>',
                    default => '<span class="badge bg-secondary">' . htmlspecialchars($log['acao']) . '</span>'
                };
                
                $data[] = [
                    'id' => $log['id'],
                    'tabela' => htmlspecialchars($log['tabela']),
                    'registro_id' => $log['registro_id'] ?? '-',
                    'acao' => $acaoBadge,
                    'descricao' => htmlspecialchars($log['descricao'] ?? '-'),
                    'usuario' => $log['usuario_nome'] ? htmlspecialchars($log['usuario_nome']) . '<br><small class="text-muted">' . htmlspecialchars($log['usuario_email']) . '</small>' : '-',
                    'ip_address' => htmlspecialchars($log['ip_address'] ?? '-'),
                    'created_at' => date('d/m/Y H:i:s', strtotime($log['created_at'])),
                    'dados' => $log['dados_anteriores'] || $log['dados_novos'] ? 'Sim' : 'Não'
                ];
            }
            
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => (int)$totalRecords,
                'recordsFiltered' => (int)$totalRecords,
                'data' => $data
            ]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'draw' => $draw ?? 1,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Erro ao buscar logs: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}


