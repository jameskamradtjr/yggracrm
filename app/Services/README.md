# Serviços de Integração

Este diretório contém serviços para integração com APIs externas.

## APIzap (WhatsApp)

### Configuração
1. Acesse **Configurações > Integrações**
2. Preencha os campos:
   - **Instance Key**: Chave da instância do WhatsApp
   - **Token de Segurança**: Token de autenticação da APIzap

### Uso

```php
use App\Services\ApiZapService;

// Instancia o serviço
$apizap = new ApiZapService();

// Verifica se está configurado
if (!$apizap->isConfigured()) {
    echo "APIzap não configurada!";
    return;
}

// Envia mensagem simples
$result = $apizap->sendMessage('47989235660', 'Olá! Esta é uma mensagem de teste.');

if ($result['success']) {
    echo "Mensagem enviada com sucesso!";
} else {
    echo "Erro: " . $result['observacoes'];
}

// Envia mensagem com template e variáveis
$template = "Olá, {nome}!\n\nSeu pagamento foi aprovado no {site}.";
$variables = [
    'nome' => 'João Silva',
    'site' => 'gestaoink.com'
];

$result = $apizap->sendTemplate('47989235660', $template, $variables);
```

### Retorno

O método `sendMessage()` retorna um array com:
- `success` (bool): Se a mensagem foi enviada com sucesso
- `status` (string): 'enviado' ou 'nao_enviado'
- `response` (mixed): Resposta da API
- `observacoes` (string|null): Mensagem de erro, se houver

## Resend (Email)

### Configuração
1. Acesse **Configurações > Integrações**
2. Preencha os campos:
   - **API Key**: Chave da API do Resend
   - **Email Remetente**: Email padrão para envio (opcional)

### Uso

```php
use App\Services\ResendService;

// Instancia o serviço
$resend = new ResendService();

// Verifica se está configurado
if (!$resend->isConfigured()) {
    echo "Resend não configurado!";
    return;
}

// Envia email simples
$html = '<h1>Olá!</h1><p>Este é um email de teste.</p>';
$result = $resend->sendEmail(
    'cliente@example.com',
    'Assunto do Email',
    $html
);

if ($result['success']) {
    echo "Email enviado com sucesso!";
} else {
    echo "Erro: " . json_encode($result['response']);
}

// Envia email com template e variáveis
$template = '
<html>
  <head>
    <title>Compra Aprovada</title>
  </head>
  <body>
    <h2>Parabéns, {nome}!</h2>
    <p>Sua compra do {produto} foi aprovada.</p>
    <p>Acesse: <a href="{link}">{link}</a></p>
  </body>
</html>
';

$variables = [
    'nome' => 'João Silva',
    'produto' => 'GestãoInk',
    'link' => 'https://gestaoink.com'
];

$result = $resend->sendTemplate(
    'cliente@example.com',
    'Compra Aprovada GestãoInk',
    $template,
    $variables
);
```

### Retorno

O método `sendEmail()` retorna um array com:
- `success` (bool): Se o email foi enviado com sucesso
- `response` (array): Resposta da API do Resend
- `httpCode` (int): Código HTTP da resposta

## Exemplo Completo

```php
use App\Services\ApiZapService;
use App\Services\ResendService;

// Dados do cliente
$clienteNome = 'João Silva';
$clienteEmail = 'joao@example.com';
$clienteTelefone = '47989235660';

// Envia WhatsApp
$apizap = new ApiZapService();
$mensagemWhatsApp = "Olá, {$clienteNome}!\n\nSeu pagamento foi aprovado no gestaoink.com.";
$resultWhatsApp = $apizap->sendMessage($clienteTelefone, $mensagemWhatsApp);

// Envia Email
$resend = new ResendService();
$htmlEmail = "
<html>
  <body>
    <h2>Parabéns, {$clienteNome}!</h2>
    <p>Sua compra foi aprovada com sucesso.</p>
    <p>Acesse: <a href='https://gestaoink.com'>https://gestaoink.com</a></p>
  </body>
</html>
";
$resultEmail = $resend->sendEmail($clienteEmail, 'Compra Aprovada', $htmlEmail);

// Verifica resultados
if ($resultWhatsApp['success']) {
    echo "WhatsApp enviado!\n";
}
if ($resultEmail['success']) {
    echo "Email enviado!\n";
}
```

