<?php
session_start();
$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';

if (!$code) die("Código não fornecido.");

switch ($provider) {
    case 'github':
        $token_url = 'https://github.com/login/oauth/access_token';
        $user_url  = 'https://api.github.com/user';
        $fields    = [
            'client_id'     => 'Ov23limHfW3vDDuSJJvk',
            'client_secret' => '_SECRET_GITHUB',
            'code'          => $code
        ];
        break;

    case 'google':
        $token_url = 'https://oauth2.googleapis.com/token';
        $user_url  = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $fields    = [
            'client_id'     => '_ID_GOOGLE',
            'client_secret' => '_SECRET_GOOGLE',
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => 'https://cepincis.com.br/callback.php?provider=google'
        ];
        break;
}

if (empty($token_url)) {
    die("Provedor inválido ou não suportado.");
}

// 1. Fazer o POST para trocar o 'code' pelo Access Token
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
// O GitHub precisa deste cabeçalho para retornar JSON amigável; o Google ignora e retorna JSON de qualquer forma.
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

// Verifica se a rede social retornou algum erro
if (isset($response['error'])) {
    die("Erro ao obter token da rede social: " . htmlspecialchars($response['error_description'] ?? $response['error']));
}

$access_token = $response['access_token'] ?? null;

if (!$access_token) {
    die("Falha: O Access Token não foi retornado.");
}

// 2. Fazer o GET para buscar os dados do perfil do usuário usando o Token
$ch = curl_init($user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// O GitHub exige um User-Agent. Ambos aceitam o padrão 'Bearer' para o token.
$headers = [
    'Authorization: Bearer ' . $access_token,
    'User-Agent: CEPIN-CIS-App' 
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$user_data = json_decode(curl_exec($ch), true);
curl_close($ch);

// 3. Normalizar os dados (cada rede chama as coisas de um jeito diferente)
$nome = '';
$email = '';

if ($provider === 'github') {
    // No GitHub, se o cara não preencheu o nome real, usamos o username (login)
    $nome  = $user_data['name'] ?? $user_data['login'];
    // O email pode vir vazio se ele marcou como privado no GitHub
    $email = $user_data['email'] ?? 'Email privado'; 
} elseif ($provider === 'google') {
    // O Google sempre manda 'name' e 'email' clarinhos
    $nome  = $user_data['name'] ?? '';
    $email = $user_data['email'] ?? '';
}

// 4. Lógica final de Login / Redirecionamento
if (!empty($nome)) {
    // Sucesso! Aqui salva na sessão. 
    // Em um sistema real, aqui faria o INSERT ou SELECT no banco de dados.
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_email'] = $email;
    $_SESSION['provider_usado'] = $provider; // Só pra saber de onde ele veio
    
    // Manda pro painel
    header("Location: dashboard.php");
    exit();
} else {
    die("Erro: Não foi possível ler os dados da conta.");
}