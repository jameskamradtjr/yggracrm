<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

/**
 * Serviço para integração com OpenAI
 */
class OpenAIService
{
    private ?string $apiKey = null;
    
    public function __construct()
    {
        $this->apiKey = SystemSetting::get('openai_api_key');
    }
    
    /**
     * Verifica se a API key está configurada
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
    
    /**
     * Gera conteúdo de post otimizado para SEO usando OpenAI
     * 
     * @param string $keywords Palavras-chave separadas por vírgula
     * @param string $tone Tom de voz (ex: profissional, descontraído, técnico)
     * @param array $referenceLinks Links de referência
     * @return array ['title' => string, 'content' => string, 'excerpt' => string]
     */
    public function generatePostContent(string $keywords, string $tone, array $referenceLinks = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('API key da OpenAI não configurada. Configure em /settings');
        }
        
        // Constrói links de referência para o prompt
        $linksText = '';
        if (!empty($referenceLinks)) {
            $linksText = "\n\nLinks de referência para consulta:\n";
            foreach ($referenceLinks as $index => $link) {
                $linksText .= ($index + 1) . ". " . $link . "\n";
            }
        }
        
        // Prompt especializado em SEO
        $prompt = "Você é um especialista em SEO e criação de conteúdo para blogs. Sua tarefa é criar um post de blog otimizado para SEO e para ser encontrado por assistentes de IA como ChatGPT, Claude, etc.

REQUISITOS:
1. O post deve ser otimizado para SEO (Search Engine Optimization)
2. Deve ser útil e informativo para leitores humanos
3. Deve ser estruturado de forma que assistentes de IA possam extrair informações facilmente
4. Use as palavras-chave fornecidas de forma natural e estratégica
5. Mantenha o tom de voz especificado
6. O conteúdo deve ter entre 800-1200 palavras
7. Use estrutura clara com subtítulos (H2, H3)
8. Inclua listas quando apropriado
9. Seja específico e forneça valor real

PALAVRAS-CHAVE: {$keywords}
TOM DE VOZ: {$tone}{$linksText}

IMPORTANTE:
- Use as palavras-chave de forma natural, sem keyword stuffing
- Distribua as palavras-chave ao longo do texto
- Use variações das palavras-chave
- Crie um título otimizado para SEO (50-60 caracteres)
- Crie um resumo/excerpt de 150-160 caracteres
- Estruture o conteúdo com HTML semântico (use tags <h2>, <h3>, <p>, <ul>, <ol>, <strong>, <em>)
- O conteúdo deve ser informativo e útil

FORMATO DE RESPOSTA (JSON):
{
    \"title\": \"Título otimizado para SEO (50-60 caracteres)\",
    \"excerpt\": \"Resumo do post (150-160 caracteres)\",
    \"content\": \"Conteúdo completo em HTML com estrutura semântica\"
}

Gere o conteúdo agora:";
        
        $response = $this->callOpenAI($prompt);
        
        // Tenta extrair JSON da resposta
        $json = $this->extractJsonFromResponse($response);
        
        if (!$json) {
            throw new \Exception('Erro ao processar resposta da OpenAI. Resposta: ' . substr($response, 0, 200));
        }
        
        return [
            'title' => $json['title'] ?? 'Post Gerado por IA',
            'excerpt' => $json['excerpt'] ?? '',
            'content' => $json['content'] ?? $response
        ];
    }
    
    /**
     * Chama a API da OpenAI
     */
    private function callOpenAI(string $prompt): string
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-4o-mini', // Modelo mais econômico e eficiente
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um especialista em SEO e criação de conteúdo para blogs. Sempre retorne respostas em formato JSON válido quando solicitado.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 3000
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Erro na requisição para OpenAI: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Erro desconhecido da API';
            throw new \Exception('Erro da API OpenAI (' . $httpCode . '): ' . $errorMessage);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Resposta inválida da API OpenAI');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Extrai JSON da resposta da OpenAI
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        // Tenta encontrar JSON na resposta
        // Pode estar entre ```json ... ``` ou apenas como JSON puro
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json) {
                return $json;
            }
        }
        
        // Tenta encontrar JSON entre chaves
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }
        
        // Tenta decodificar a resposta inteira como JSON
        $json = json_decode($response, true);
        if ($json) {
            return $json;
        }
        
        return null;
    }
}

