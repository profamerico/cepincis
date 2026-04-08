<?php
session_start();

require_once 'controllers/AuthController.php';

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';

if (!$code) {
    die('Codigo nao fornecido.');
}

switch ($provider) {
    case 'github':
        $token_url = 'https://github.com/login/oauth/access_token';
        $user_url = 'https://api.github.com/user';
        $fields = [
            'client_id' => 'Ov23limHfW3vDDuSJJvk',
            'client_secret' => '_SECRET_GITHUB',
            'code' => $code,
        ];
        break;

    case 'google':
        $token_url = 'https://oauth2.googleapis.com/token';
        $user_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $fields = [
            'client_id' => '_ID_GOOGLE',
            'client_secret' => '_SECRET_GOOGLE',
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://cepincis.com.br/callback.php?provider=google',
        ];
        break;

    default:
        die('Provedor invalido ou nao suportado.');
}

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['error'])) {
    $errorMessage = $response['error_description'] ?? $response['error'];
    die('Erro ao obter token da rede social: ' . htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'));
}

$accessToken = $response['access_token'] ?? null;
if (!$accessToken) {
    die('Falha ao obter o access token.');
}

$ch = curl_init($user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'User-Agent: CEPIN-CIS-App',
]);

$userData = json_decode(curl_exec($ch), true);
curl_close($ch);

$nome = '';
$email = '';

if ($provider === 'github') {
    $nome = $userData['name'] ?? $userData['login'] ?? '';
    $email = $userData['email'] ?? '';
} elseif ($provider === 'google') {
    $nome = $userData['name'] ?? '';
    $email = $userData['email'] ?? '';
}

if ($nome === '') {
    die('Erro: nao foi possivel ler os dados da conta.');
}

$auth = new AuthController();
$result = $auth->loginSocialUser($nome, $email, $provider);

if (!$result['success']) {
    $message = $result['errors'][0] ?? 'Falha ao autenticar o usuario social.';
    die(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}

header('Location: dashboard.php');
exit();
