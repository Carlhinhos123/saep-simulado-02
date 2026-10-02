<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = $alertaEstoque = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $id_insumo = (int)($_POST['id_insumo'] ?? 0);
    $tipo = $_POST['tipo'] ?? "";
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data'] ?? date('Y-m-d H:i:s');
    // Captura o id do usuário logado na sessão
    $id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? 1;

    if (!$id_insumo || !in_array($tipo, ['ENTRADA', 'SAIDA']) || !$data || $qtd <= 0) {
        $mensagem = "Preencha todos os campos corretamente! A quantidade deve ser maior que zero.";
        $tipoMensagem = 'erro';
    } else {
        // Busca o insumo selecionado
        $stmt = $conn->prepare("SELECT * FROM insumos WHERE id_insumo = ?");
        $stmt->execute([$id_insumo]);
        $insumo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$insumo) {
            $mensagem = "Insumo não encontrado!";
            $tipoMensagem = 'erro';
        } else {
            $qtd_anterior = (int)$insumo['qtd_atual'];
            $nova_qtd = $tipo === 'ENTRADA' ? $qtd_anterior + $qtd : $qtd_anterior - $qtd;

            if ($nova_qtd < 0) {
                $mensagem = "Estoque insuficiente para realizar esta saída! Saldo atual: {$qtd_anterior}";
                $tipoMensagem = 'erro';
            } else {
                try {
                    $conn->beginTransaction();

                    // 1. Registra a movimentação de estoque
                    $sqlMov = "INSERT INTO movimentacoes (id_insumo, id_usuario, tipo, quantidade, data_movimentacao) 
                               VALUES (?, ?, ?, ?, ?)";
                    $conn->prepare($sqlMov)->execute([$id_insumo, $id_usuario, $tipo, $qtd, $data]);

                    // 2. Atualiza o saldo atual do insumo
                    $sqlUpd = "UPDATE insumos SET qtd_atual = ? WHERE id_insumo = ?";
                    $conn->prepare($sqlUpd)->execute([$nova_qtd, $id_insumo]);

                    $conn->commit();

                    $mensagem = "Movimentação de {$tipo} registrada com sucesso!";
                    $tipoMensagem = 'sucesso';

                    // Verification de Estoque Mínimo (Alerta automático em saídas ou estoque crítico)
                    if ($tipo === 'SAIDA' && $nova_qtd <= $insumo['qtd_minima']) {
                        $alertaEstoque = "⚠️ ALERTA DE ESTOQUE MÍNIMO ATINGIDO! O insumo '{$insumo['nome']}' atingiu o saldo de {$nova_qtd} {$insumo['unidade_medida']} (Estoque mínimo: {$insumo['qtd_minima']}). Providencie a compra!";
                    }

                } catch (PDOException $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $mensagem = 'Erro ao processar a movimentação: ' . $e->getMessage();
                    $tipoMensagem = 'erro';
                }
            }
        }
    }
}

// Busca insumos ordenados alfabeticamente por nome (Requisito obrigatório)
$insumos = $conn->query("SELECT * FROM insumos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Busca histórico de movimentações com os relacionamentos de insumo e usuário
$sqlHistorico = "SELECT m.*, 
                        i.nome AS insumo_nome, 
                        i.unidade_medida,
                        u.nome AS usuario_nome 
                 FROM movimentacoes m 
                 JOIN insumos i ON i.id_insumo = m.id_insumo 
                 JOIN usuarios u ON u.id_usuario = m.id_usuario 
                 ORDER BY m.data_movimentacao DESC";
$movimentacoes = $conn->query($sqlHistorico)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque - Controle Industrial</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .sucesso { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; }
        .erro { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; }
        .alerta-caixa { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 12px; font-weight: bold; border-radius: 4px; }
        .alerta-linha { background-color: #ffe6e6; color: #cc0000; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .badge-entrada { color: green; font-weight: bold; }
        .badge-saida { color: red; font-weight: bold; }
    </style>
</head>

<body>
    <h1>Gestão de Estoque e Movimentações</h1>
    <p><a href="index.php">← Voltar para a Tela Principal</a></p>
    <hr>

    <!-- Exibição de Mensagens de Operação -->
    <?php if ($mensagem): ?>
        <div class="<?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>
        <br>
    <?php endif; ?>

    <!-- Alerta Visual para Estoque Mínimo -->
    <?php if ($alertaEstoque): ?>
        <div class="alerta-caixa">
            <?= htmlspecialchars($alertaEstoque) ?>
        </div>
        <br>
    <?php endif; ?>

    <h2>Registrar Nova Movimentação</h2>
    <form method="POST" action="gestao_estoque.php">
        <label>Insumo: *<br>
            <select name="id_insumo" required>
                <option value="">-- Selecione o Insumo --</option>
                <?php foreach ($insumos as $i): ?>
                    <option value="<?= $i['id_insumo'] ?>">
                        <?= htmlspecialchars($i['nome']) ?> (Saldo Atual: <?= $i['qtd_atual'] ?> <?= $i['unidade_medida'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <br><br>

        <label>Tipo de Movimentação: *<br>
            <input type="radio" name="tipo" value="ENTRADA" id="tipo_entrada" required> 
            <label for="tipo_entrada">Entrada (Recebimento/Compra)</label> &nbsp;
            <input type="radio" name="tipo" value="SAIDA" id="tipo_saida" required> 
            <label for="tipo_saida">Saída (Envio para Produção)</label>
        </label>
        <br><br>

        <label>Quantidade: *<br>
            <input type="number" name="quantidade" min="1" required placeholder="Ex: 10">
        </label>
        <br><br>

        <label>Data e Hora da Movimentação: *<br>
            <input type="datetime-local" name="data" value="<?= date('Y-m-d\TH:i') ?>" required>
        </label>
        <br><br>

        <button type="submit">Confirmar Movimentação</button>
    </form>

    <hr>

    <h2>Saldos Atuais de Insumos (Ordem Alfabética)</h2>
    <?php if ($insumos): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Insumo</th>
                    <th>Categoria</th>
                    <th>Preço Custo</th>
                    <th>Qtd. Atual</th>
                    <th>Qtd. Mínima</th>
                    <th>Status do Estoque</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($insumos as $i): 
                    $estoqueCritico = $i['qtd_atual'] <= $i['qtd_minima'];
                ?>
                    <tr class="<?= $estoqueCritico ? 'alerta-linha' : '' ?>">
                        <td><?= htmlspecialchars($i['id_insumo']) ?></td>
                        <td><?= htmlspecialchars($i['nome']) ?></td>
                        <td><?= htmlspecialchars($i['categoria']) ?></td>
                        <td>R$ <?= number_format($i['preco_custo'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($i['qtd_atual']) ?> <?= htmlspecialchars($i['unidade_medida']) ?></td>
                        <td><?= htmlspecialchars($i['qtd_minima']) ?> <?= htmlspecialchars($i['unidade_medida']) ?></td>
                        <td>
                            <?= $estoqueCritico 
                                ? '⚠️ **ESTOQUE CRÍTICO / MÍNIMO**' 
                                : 'Normal'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?> 
            </tbody>
        </table>  
    <?php else: ?>
        <p>Nenhum insumo encontrado no banco de dados.</p> 
    <?php endif; ?>

    <hr>

    <h2>Histórico de Movimentações (Rastreabilidade)</h2>
    <?php if ($movimentacoes): ?>
        <table>
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Insumo</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Usuário Responsável</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?></td>
                        <td><?= htmlspecialchars($m['insumo_nome']) ?></td>
                        <td class="<?= $m['tipo'] === 'ENTRADA' ? 'badge-entrada' : 'badge-saida' ?>">
                            <?= htmlspecialchars($m['tipo']) ?>
                        </td>
                        <td><?= htmlspecialchars($m['quantidade']) ?> <?= htmlspecialchars($m['unidade_medida']) ?></td>
                        <td><?= htmlspecialchars($m['usuario_nome']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma movimentação foi registrada até o momento.</p> 
    <?php endif; ?>
</body>

</html>