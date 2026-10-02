<?php
require_once 'config.php';
verificarLogin();

$mensagem = '';
$tipoMensagem = '';

// 1. EXCLUSÃO DE INSUMO
if (isset($_GET['excluir'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM insumos WHERE id_insumo = ?");
        $stmt->execute([$_GET['excluir']]);
        $mensagem = "Insumo excluído com sucesso!";
        $tipoMensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir o insumo: " . $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// 2. CADASTRO / EDIÇÃO DE INSUMO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_insumo = (int)($_POST['id_insumo'] ?? 0);

    $data = [
        'nome'           => trim($_POST['nome'] ?? ''),
        'categoria'      => trim($_POST['categoria'] ?? ''),
        'unidade_medida' => trim($_POST['unidade_medida'] ?? ''),
        'preco_custo'    => !empty($_POST['preco_custo']) ? (float)$_POST['preco_custo'] : 0.00,
        'qtd_atual'      => !empty($_POST['qtd_atual']) ? (int)$_POST['qtd_atual'] : 0,
        'qtd_minima'     => !empty($_POST['qtd_minima']) ? (int)$_POST['qtd_minima'] : 0
    ];

    // Validação simples dos campos obrigatórios
    if (empty($data['nome']) || empty($data['categoria']) || empty($data['unidade_medida'])) {
        $mensagem = "Preencha todos os campos obrigatórios (Nome, Categoria e Unidade de Medida).";
        $tipoMensagem = 'erro';
    } else {
        try {
            if ($id_insumo > 0) {
                // Atualização
                $sql = "UPDATE insumos SET 
                            nome = :nome,
                            categoria = :categoria,
                            unidade_medida = :unidade_medida,
                            preco_custo = :preco_custo,
                            qtd_atual = :qtd_atual,
                            qtd_minima = :qtd_minima
                        WHERE id_insumo = :id_insumo";
                $data['id_insumo'] = $id_insumo;
                $conn->prepare($sql)->execute($data);
                $mensagem = "Insumo atualizado com sucesso!";
            } else {
                // Inserção
                $sql = "INSERT INTO insumos (nome, categoria, unidade_medida, preco_custo, qtd_atual, qtd_minima) 
                        VALUES (:nome, :categoria, :unidade_medida, :preco_custo, :qtd_atual, :qtd_minima)";
                $conn->prepare($sql)->execute($data);
                $mensagem = "Insumo cadastrado com sucesso!";
            }
            $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = "Erro ao salvar o insumo: " . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

// 3. CARREGAR DADOS PARA EDIÇÃO
$insumoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM insumos WHERE id_insumo = ?");
    $stmt->execute([$_GET['editar']]);
    $insumoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. CONSULTA E BUSCA DE INSUMOS
$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $sql = "SELECT * FROM insumos WHERE nome LIKE :b OR categoria LIKE :b ORDER BY nome ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['b' => "%$busca%"]);
} else {
    $sql = "SELECT * FROM insumos ORDER BY nome ASC";
    $stmt = $conn->query($sql);
}
$insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Insumos - Controle de Estoque Industrial</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .sucesso { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .alerta-minimo { background-color: #ffe6e6; color: #cc0000; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        form label { display: inline-block; margin-bottom: 8px; }
    </style>
</head>

<body>
    <h1>Cadastro e Gestão de Insumos</h1>
    <p><a href="index.php">← Voltar para a Tela Principal</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p class="<?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <h2><?= $insumoEditar ? 'Editar' : 'Novo' ?> Insumo</h2>
    <form method="POST" action="insumos.php">
        <input type="hidden" name="id_insumo" value="<?= htmlspecialchars($insumoEditar['id_insumo'] ?? '') ?>">
        
        <label>Nome do Insumo: *<br>
            <input type="text" name="nome" size="50" required value="<?= htmlspecialchars($insumoEditar['nome'] ?? '') ?>">
        </label><br>

        <label>Categoria: *<br>
            <input type="text" name="categoria" size="30" placeholder="Ex: Chapas, Fixadores, Pintura" required value="<?= htmlspecialchars($insumoEditar['categoria'] ?? '') ?>">
        </label><br>

        <label>Unidade de Medida: *<br>
            <input type="text" name="unidade_medida" size="10" placeholder="Un, Kg, L, m" required value="<?= htmlspecialchars($insumoEditar['unidade_medida'] ?? '') ?>">
        </label><br>

        <label>Preço / Custo Unitário (R$): *<br>
            <input type="number" step="0.01" min="0" name="preco_custo" required value="<?= htmlspecialchars($insumoEditar['preco_custo'] ?? '0.00') ?>">
        </label><br>

        <label>Estoque Atual:<br>
            <input type="number" min="0" name="qtd_atual" value="<?= htmlspecialchars($insumoEditar['qtd_atual'] ?? '0') ?>">
        </label><br>

        <label>Estoque Mínimo:<br>
            <input type="number" min="0" name="qtd_minima" value="<?= htmlspecialchars($insumoEditar['qtd_minima'] ?? '0') ?>">
        </label><br><br>

        <button type="submit">Salvar Insumo</button>
        <?php if ($insumoEditar): ?>
            <a href="insumos.php"><button type="button">Cancelar</button></a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Insumos Cadastrados</h2>
    <form method="GET" action="insumos.php">
        <label>Buscar: 
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou categoria...">
        </label>
        <button type="submit">Pesquisar</button>
        <?php if ($busca): ?> 
            <a href="insumos.php">Limpar Busca</a> 
        <?php endif; ?>
    </form>
    <br>

    <?php if (!empty($insumos)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Un. Medida</th>
                    <th>Preço de Custo</th>
                    <th>Qtd. Atual</th>
                    <th>Qtd. Mínima</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($insumos as $item): 
                    $estoqueBaixo = $item['qtd_atual'] <= $item['qtd_minima'];
                ?> 
                    <tr class="<?= $estoqueBaixo ? 'alerta-minimo' : '' ?>">
                        <td><?= htmlspecialchars($item['id_insumo']) ?></td>
                        <td><?= htmlspecialchars($item['nome']) ?></td>
                        <td><?= htmlspecialchars($item['categoria']) ?></td>
                        <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                        <td>R$ <?= number_format($item['preco_custo'], 2, ',', '.') ?></td>
                        <td>
                            <?= htmlspecialchars($item['qtd_atual']) ?>
                            <?= $estoqueBaixo ? ' ⚠️ (Abaixo/Igual ao mínimo)' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($item['qtd_minima']) ?></td>
                        <td>
                            <a href="insumos.php?editar=<?= $item['id_insumo'] ?>">Editar</a> |
                            <a href="insumos.php?excluir=<?= $item['id_insumo'] ?>" onclick="return confirm('Tem certeza que deseja excluir este insumo?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table> 
    <?php else: ?>
        <p>Nenhum insumo encontrado no sistema.</p>
    <?php endif; ?>
</body>

</html>