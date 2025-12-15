<?php
// Passo 1: Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o controlador de autenticação
require_once 'controllers/AuthController.php';

$auth = new AuthController();

// Pega os dados do usuário logado da sessão
// É CRUCIAL que o AuthController armazene 'user_name' ou 'fullname' na sessão
$user_data = $auth->getCurrentUser(); 
$nome_usuario = 'Visitante'; // Valor padrão para segurança

if ($user_data && isset($user_data['fullname'])) {
    $nome_usuario = $user_data['fullname'];
} elseif ($user_data && isset($user_data['username'])) {
    // Se 'fullname' não estiver disponível, use o username
    $nome_usuario = $user_data['username'];
}

?>

<?php include_once 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | Cepin-cis</title>
    <link rel="stylesheet" href="seu-estilo-principal.css">
    <style>
        /* O CSS sugerido na seção 2 irá aqui */
    </style>
</head>

<body>

    <main class="perfil-moldura">
        <section class="perfil-painel">

            <div class="perfil-cabecalho">
                <div class="foto-orbita">
                    <img src="./img/login.png" alt="Foto de Perfil do Usuário" class="foto-avatar">
                </div>

                <h1 class="nome-identidade"><?php echo htmlspecialchars($nome_usuario, ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>


            <div class="perfil-acoes">

            <button class="acao-botao acao-secundaria" onclick="window.location.href='logout.php'">Sair da conta</button>
            </div>

        </section>
    </main>

</body>

</html>

<?php include_once 'includes/footer.php'; ?>