<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Serviço para geração de PDFs
 * 
 * Nota: Para produção, instale uma biblioteca de PDF como DomPDF ou TCPDF
 * Exemplo: composer require dompdf/dompdf
 */
class PdfService
{
    /**
     * Gera PDF a partir de HTML
     * 
     * @param string $html Conteúdo HTML
     * @param string $filename Nome do arquivo
     * @param array $options Opções adicionais
     * @return string Caminho do arquivo gerado ou conteúdo binário
     */
    public static function generateFromHtml(string $html, string $filename = 'documento.pdf', array $options = []): string
    {
        // Verifica se DomPDF está disponível
        if (class_exists('\Dompdf\Dompdf')) {
            return self::generateWithDomPdf($html, $filename, $options);
        }
        
        // Fallback: retorna HTML (pode ser salvo como PDF pelo navegador)
        // Em produção, sempre use uma biblioteca de PDF
        $outputPath = base_path('storage/pdfs/' . $filename);
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Salva HTML temporário (pode ser convertido para PDF depois)
        file_put_contents($outputPath . '.html', $html);
        
        return $outputPath . '.html';
    }
    
    /**
     * Gera PDF usando DomPDF
     */
    private static function generateWithDomPdf(string $html, string $filename, array $options): string
    {
        $dompdf = new \Dompdf\Dompdf();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper($options['paper'] ?? 'A4', $options['orientation'] ?? 'portrait');
        $dompdf->render();
        
        $outputPath = base_path('storage/pdfs/' . $filename);
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($outputPath, $dompdf->output());
        
        return $outputPath;
    }
    
    /**
     * Força download do PDF
     */
    public static function download(string $filePath, string $filename): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Arquivo PDF não encontrado: {$filePath}");
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    
    /**
     * Exibe PDF no navegador
     */
    public static function display(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Arquivo PDF não encontrado: {$filePath}");
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

