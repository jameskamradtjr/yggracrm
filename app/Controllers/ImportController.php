<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Client;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\CostCenter;
use App\Models\Tag;

class ImportController extends Controller
{
    /**
     * Página de importação de clientes
     */
    public function clients(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        return $this->view('imports/clients', [
            'title' => 'Importar Clientes'
        ]);
    }

    /**
     * Download do template de clientes
     */
    public function clientsTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $headers = [
            'A1' => 'tipo',
            'B1' => 'nome_razao_social',
            'C1' => 'nome_fantasia',
            'D1' => 'cpf_cnpj',
            'E1' => 'email',
            'F1' => 'telefone',
            'G1' => 'celular',
            'H1' => 'instagram',
            'I1' => 'endereco',
            'J1' => 'numero',
            'K1' => 'complemento',
            'L1' => 'bairro',
            'M1' => 'cidade',
            'N1' => 'estado',
            'O1' => 'cep',
            'P1' => 'score',
            'Q1' => 'observacoes'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Exemplo de linha
        $sheet->setCellValue('A2', 'fisica');
        $sheet->setCellValue('B2', 'João da Silva');
        $sheet->setCellValue('C2', 'João Silva');
        $sheet->setCellValue('D2', '12345678901');
        $sheet->setCellValue('E2', 'joao@email.com');
        $sheet->setCellValue('F2', '4733221100');
        $sheet->setCellValue('G2', '47999887766');
        $sheet->setCellValue('H2', '@joaosilva');
        $sheet->setCellValue('I2', 'Rua das Flores');
        $sheet->setCellValue('J2', '123');
        $sheet->setCellValue('K2', 'Apto 101');
        $sheet->setCellValue('L2', 'Centro');
        $sheet->setCellValue('M2', 'Joinville');
        $sheet->setCellValue('N2', 'SC');
        $sheet->setCellValue('O2', '89200000');
        $sheet->setCellValue('P2', '75');
        $sheet->setCellValue('Q2', 'Cliente potencial para projeto X');

        // Ajusta largura das colunas
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Gera arquivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'template_clientes_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Upload e processamento do arquivo de clientes
     */
    public function clientsUpload(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!isset($_FILES['file'])) {
            json_response(['success' => false, 'message' => 'Nenhum arquivo enviado'], 400);
            return;
        }

        $file = $_FILES['file'];
        $userId = auth()->getDataUserId();

        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $imported = 0;
            $errors = [];
            $skipped = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $tipo = trim($sheet->getCell('A' . $row)->getValue() ?? '');
                $nomeRazaoSocial = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                
                // Pula linhas vazias
                if (empty($nomeRazaoSocial)) {
                    $skipped++;
                    continue;
                }

                try {
                    $clientData = [
                        'user_id' => $userId,
                        'tipo' => $tipo ?: 'fisica',
                        'nome_razao_social' => $nomeRazaoSocial,
                        'nome_fantasia' => trim($sheet->getCell('C' . $row)->getValue() ?? ''),
                        'cpf_cnpj' => trim($sheet->getCell('D' . $row)->getValue() ?? ''),
                        'email' => trim($sheet->getCell('E' . $row)->getValue() ?? ''),
                        'telefone' => trim($sheet->getCell('F' . $row)->getValue() ?? ''),
                        'celular' => trim($sheet->getCell('G' . $row)->getValue() ?? ''),
                        'instagram' => trim($sheet->getCell('H' . $row)->getValue() ?? ''),
                        'endereco' => trim($sheet->getCell('I' . $row)->getValue() ?? ''),
                        'numero' => trim($sheet->getCell('J' . $row)->getValue() ?? ''),
                        'complemento' => trim($sheet->getCell('K' . $row)->getValue() ?? ''),
                        'bairro' => trim($sheet->getCell('L' . $row)->getValue() ?? ''),
                        'cidade' => trim($sheet->getCell('M' . $row)->getValue() ?? ''),
                        'estado' => trim($sheet->getCell('N' . $row)->getValue() ?? ''),
                        'cep' => trim($sheet->getCell('O' . $row)->getValue() ?? ''),
                        'score' => (int)($sheet->getCell('P' . $row)->getValue() ?? 50),
                        'observacoes' => trim($sheet->getCell('Q' . $row)->getValue() ?? '')
                    ];

                    // Remove campos vazios e converte para null
                    foreach ($clientData as $key => $value) {
                        if ($value === '') {
                            $clientData[$key] = null;
                        }
                    }

                    Client::create($clientData);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Linha {$row}: " . $e->getMessage();
                }
            }

            json_response([
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'skipped' => $skipped,
                'message' => "{$imported} cliente(s) importado(s) com sucesso!"
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Página de importação de lançamentos financeiros
     */
    public function financial(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        return $this->view('imports/financial', [
            'title' => 'Importar Lançamentos Financeiros'
        ]);
    }

    /**
     * Download do template de lançamentos financeiros
     */
    public function financialTemplate(): void
    {
        $userId = auth()->getDataUserId();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $headers = [
            'A1' => 'tipo',
            'B1' => 'descricao',
            'C1' => 'valor',
            'D1' => 'data_vencimento',
            'E1' => 'data_pagamento',
            'F1' => 'categoria',
            'G1' => 'centro_custo',
            'H1' => 'tags',
            'I1' => 'observacoes',
            'J1' => 'recorrente'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Exemplos
        $sheet->setCellValue('A2', 'receita');
        $sheet->setCellValue('B2', 'Pagamento Cliente X - Projeto Y');
        $sheet->setCellValue('C2', '5000.00');
        $sheet->setCellValue('D2', '2025-12-15');
        $sheet->setCellValue('E2', '2025-12-15');
        $sheet->setCellValue('F2', 'Serviços Prestados');
        $sheet->setCellValue('G2', 'Projetos');
        $sheet->setCellValue('H2', 'projeto-y,cliente-x,desenvolvimento');
        $sheet->setCellValue('I2', 'Primeira parcela do projeto');
        $sheet->setCellValue('J2', 'nao');

        $sheet->setCellValue('A3', 'despesa');
        $sheet->setCellValue('B3', 'Conta de Luz - Escritório');
        $sheet->setCellValue('C3', '350.50');
        $sheet->setCellValue('D3', '2025-12-20');
        $sheet->setCellValue('E3', '');
        $sheet->setCellValue('F3', 'Utilidades');
        $sheet->setCellValue('G3', 'Administrativo');
        $sheet->setCellValue('H3', 'contas,escritorio');
        $sheet->setCellValue('I3', 'Vencimento todo dia 20');
        $sheet->setCellValue('J3', 'sim');

        // Ajusta largura
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Adiciona sheet de ajuda
        $helpSheet = $spreadsheet->createSheet(1);
        $helpSheet->setTitle('Instruções');
        $helpSheet->setCellValue('A1', 'INSTRUÇÕES DE PREENCHIMENTO');
        $helpSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $helpSheet->setCellValue('A3', 'tipo: receita ou despesa');
        $helpSheet->setCellValue('A4', 'descricao: Descrição do lançamento');
        $helpSheet->setCellValue('A5', 'valor: Valor sem R$, use ponto para decimal (ex: 1500.00)');
        $helpSheet->setCellValue('A6', 'data_vencimento: Data no formato AAAA-MM-DD (ex: 2025-12-15)');
        $helpSheet->setCellValue('A7', 'data_pagamento: Data de pagamento (opcional)');
        $helpSheet->setCellValue('A8', 'categoria: Nome EXATO da categoria cadastrada no sistema');
        $helpSheet->setCellValue('A9', 'centro_custo: Nome EXATO do centro de custo cadastrado');
        $helpSheet->setCellValue('A10', 'tags: Separadas por vírgula (ex: projeto,urgente,cliente-x)');
        $helpSheet->setCellValue('A11', 'observacoes: Observações adicionais (opcional)');
        $helpSheet->setCellValue('A12', 'recorrente: sim ou nao');

        $helpSheet->getColumnDimension('A')->setWidth(80);

        // Gera arquivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'template_lancamentos_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Upload e processamento do arquivo de lançamentos financeiros
     */
    public function financialUpload(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!isset($_FILES['file'])) {
            json_response(['success' => false, 'message' => 'Nenhum arquivo enviado'], 400);
            return;
        }

        $file = $_FILES['file'];
        $userId = auth()->getDataUserId();

        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $imported = 0;
            $errors = [];
            $skipped = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $tipo = trim($sheet->getCell('A' . $row)->getValue() ?? '');
                $descricao = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                
                // Pula linhas vazias
                if (empty($descricao) || empty($tipo)) {
                    $skipped++;
                    continue;
                }

                try {
                    // Busca categoria pelo nome
                    $categoriaNome = trim($sheet->getCell('F' . $row)->getValue() ?? '');
                    $categoryId = null;
                    if ($categoriaNome) {
                        $category = FinancialCategory::where('user_id', $userId)
                            ->where('nome', $categoriaNome)
                            ->first();
                        $categoryId = $category->id ?? null;
                    }

                    // Busca centro de custo pelo nome
                    $centroCustoNome = trim($sheet->getCell('G' . $row)->getValue() ?? '');
                    $costCenterId = null;
                    if ($centroCustoNome) {
                        $costCenter = CostCenter::where('user_id', $userId)
                            ->where('nome', $centroCustoNome)
                            ->first();
                        $costCenterId = $costCenter->id ?? null;
                    }

                    $valor = (float)($sheet->getCell('C' . $row)->getValue() ?? 0);
                    $dataVencimento = trim($sheet->getCell('D' . $row)->getValue() ?? '');
                    $dataPagamento = trim($sheet->getCell('E' . $row)->getValue() ?? '');
                    $recorrente = strtolower(trim($sheet->getCell('J' . $row)->getValue() ?? 'nao')) === 'sim' ? 1 : 0;

                    // Define status baseado no pagamento
                    $status = !empty($dataPagamento) ? 'pago' : 'pendente';

                    $transactionData = [
                        'user_id' => $userId,
                        'tipo' => $tipo,
                        'descricao' => $descricao,
                        'valor' => $valor,
                        'data_vencimento' => !empty($dataVencimento) ? $dataVencimento : null,
                        'data_pagamento' => !empty($dataPagamento) ? $dataPagamento : null,
                        'category_id' => $categoryId,
                        'cost_center_id' => $costCenterId,
                        'status' => $status,
                        'recorrente' => $recorrente,
                        'observacoes' => trim($sheet->getCell('I' . $row)->getValue() ?? '')
                    ];

                    $transaction = FinancialTransaction::create($transactionData);

                    // Processa tags
                    $tagsStr = trim($sheet->getCell('H' . $row)->getValue() ?? '');
                    if ($tagsStr) {
                        $tagNames = array_map('trim', explode(',', $tagsStr));
                        foreach ($tagNames as $tagName) {
                            if (empty($tagName)) continue;

                            // Busca ou cria tag
                            $tag = Tag::where('user_id', $userId)
                                ->where('nome', $tagName)
                                ->first();

                            if (!$tag) {
                                $tag = Tag::create([
                                    'user_id' => $userId,
                                    'nome' => $tagName
                                ]);
                            }

                            // Relaciona tag com transação
                            if ($tag) {
                                $db = \Core\Database::getInstance();
                                $db->execute(
                                    "INSERT IGNORE INTO taggables (tag_id, taggable_id, taggable_type) VALUES (?, ?, ?)",
                                    [$tag->id, $transaction->id, 'App\\Models\\FinancialTransaction']
                                );
                            }
                        }
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Linha {$row}: " . $e->getMessage();
                }
            }

            json_response([
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'skipped' => $skipped,
                'message' => "{$imported} lançamento(s) importado(s) com sucesso!"
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ], 500);
        }
    }
}

