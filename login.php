<?php
require_once 'config.php';
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Processo do login
$mensagemErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $mensagemErro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $sql = "SELECT * FROM usuarios WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                $mensagemErro = 'Email ou senha inválidos.';
            } elseif ($senha === $usuario['senha']) {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                header('Location: index.php');
                exit();
            } else {
                $mensagemErro = 'Email ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $mensagemErro = 'Erro ao acessar o banco de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Industria Senai-435</h1>
   
    <?php if (!empty($mensagemErro)): ?>
        <p style="color: red;"><?= htmlspecialchars($mensagemErro) ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        <br>
        <div>
            <label for="senha">Senha:</label>
            <input type="password" name="senha" id="senha" required>
        </div>
        <br>
        <button type="submit">Entrar</button>
    </form>
</body>

</html>