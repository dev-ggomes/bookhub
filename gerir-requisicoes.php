<?php
require_once 'assets/php/config.php';
require_once 'assets/php/check_login.php';
require_once 'assets/php/carrinho_header.php';

// Buscar requisições conforme o tipo de utilizador
try {
    if ((int) $_SESSION['admin'] === 1) {
        $stmt = $pdo->prepare("
            SELECT r.id, u.nome_completo AS utilizador, l.titulo, l.cod_isbn, l.autor,
                   r.data_requisicao, r.status, r.data_devolucao, r.prazo_devolucao
            FROM requisicoes r
            JOIN utilizadores u ON r.id_utilizador = u.id
            JOIN livros l ON r.cod_isbn = l.cod_isbn
            ORDER BY r.data_requisicao DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT r.id, u.nome_completo AS utilizador, l.titulo, l.cod_isbn, l.autor,
                   r.data_requisicao, r.status, r.data_devolucao, r.prazo_devolucao
            FROM requisicoes r
            JOIN utilizadores u ON r.id_utilizador = u.id
            JOIN livros l ON r.cod_isbn = l.cod_isbn
            WHERE r.id_utilizador = ?
            ORDER BY r.data_requisicao DESC
        ");
        $stmt->execute([$_SESSION['id']]);
    }

    $requisicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar requisições: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOKhub | Gerir Requisições</title>
    <link rel="stylesheet" href="./assets/css/index_style.css">
    <link rel="stylesheet" href="./assets/css/gerir-requisicoes.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="requisicoes-container">
        <h1>Gerir Requisições</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case '1':
                        echo "Requisição marcada como pronta para levantamento!";
                        break;
                    case '2':
                        echo "Notificação de devolução enviada ao utilizador!";
                        break;
                    case '3':
                        echo "Devolução registrada com sucesso!";
                        break;
                    case '5':
                        echo "Utilizador notificado com sucesso!";
                        break;
                    default:
                        echo "Operação realizada com sucesso!";
                }
                ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilizador</th>
                    <th>Autor</th>
                    <th>Livro</th>
                    <th>Data Requisição</th>
                    <th>Prazo de Devolução</th>
                    <th>Status</th>
                    <?php if ((int) $_SESSION['admin'] === 1): ?>
                        <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requisicoes as $req):
                    $late = (
                        $req['status'] === 'devolvido' &&
                        !empty($req['prazo_devolucao']) &&
                        !empty($req['data_devolucao']) &&
                        strtotime($req['data_devolucao']) > strtotime($req['prazo_devolucao'])
                    );
                ?>
                    <tr
                        data-prazo-ms="<?= !empty($req['prazo_devolucao']) ? strtotime($req['prazo_devolucao']) * 1000 : '' ?>"
                        <?= $late ? 'class="linha-atrasada"' : '' ?>
                    >
                        <td><?= htmlspecialchars($req['id']) ?></td>
                        <td><?= htmlspecialchars($req['utilizador']) ?></td>
                        <td><?= htmlspecialchars($req['autor']) ?></td>
                        <td><?= htmlspecialchars($req['titulo']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($req['data_requisicao'])) ?></td>
                        <td>
                            <?= $req['prazo_devolucao']
                                ? date('d/m/Y H:i', strtotime($req['prazo_devolucao']))
                                : '&mdash;'; ?>
                        </td>
                        <td class="status-<?= str_replace('_', '', $req['status']) ?>">
                            <?php
                            if ($req['status'] == 'com_o_aluno' && $req['data_devolucao'] == '1970-01-01 00:00:01') {
                                echo "Devolução Solicitada!";
                            } else {
                                switch ($req['status']) {
                                    case 'pendente':
                                        echo "Pendente";
                                        break;
                                    case 'pronto_para_levantar':
                                        echo "Pronto para Levantar";
                                        break;
                                    case 'com_o_aluno':
                                        echo "Com o Aluno";
                                        break;
                                    case 'devolvido':
                                        echo "Devolvido";
                                        break;
                                    default:
                                        echo htmlspecialchars($req['status']);
                                }
                            }
                            ?>
                        </td>

                        <?php if ((int) $_SESSION['admin'] === 1): ?>
                            <td>
                                <?php if ($req['status'] == 'pendente'): ?>
                                    <a href="assets/php/concluir_requisicao.php?id=<?= urlencode($req['id']) ?>" class="btn-acao">Preparar Livro</a>
                                <?php elseif ($req['status'] == 'pronto_para_levantar'): ?>
                                    <button
                                        class="btn-acao entregar-livro"
                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                        data-isbn="<?= htmlspecialchars($req['cod_isbn']) ?>"
                                    >
                                        Entregar Livro ao Aluno
                                    </button>
                                <?php elseif ($req['status'] == 'com_o_aluno'): ?>
                                    <?php if ($req['data_devolucao'] == '1970-01-01 00:00:01'): ?>
                                        <button
                                            class="btn-acao btn-confirmar-devolucao"
                                            data-id="<?= htmlspecialchars($req['id']) ?>"
                                            data-isbn="<?= htmlspecialchars($req['cod_isbn']) ?>"
                                            data-titulo="<?= htmlspecialchars($req['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-autor="<?= htmlspecialchars($req['autor'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            Confirmar Devolução
                                        </button>
                                    <?php else: ?>
                                        <a href="assets/php/notificar_devolucao.php?id=<?= urlencode($req['id']) ?>" class="btn-acao">Solicitar Devolução</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal de entrega -->
        <dialog id="entregaModal" class="modal-overlay">
            <div class="modal-content">
                <span id="closeEntregaModal" class="modal-close">&times;</span>
                <div class="modal-header">
                    <h2>Entregar Livro ao Aluno</h2>
                </div>
                <div class="modal-body">
                    <div class="book-cover">
                        <img id="capaLivro" src="" alt="Capa do Livro">
                    </div>
                    <div class="book-details">
                        <p><strong>Título:</strong> <span id="tituloLivro"></span></p>
                        <p><strong>Autor:</strong> <span id="autorLivro"></span></p>
                        <p><strong>ISBN:</strong> <span id="isbnLivro"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btnCancelarEntrega" class="modal-btn modal-btn-cancel">Cancelar</button>
                    <button id="btnConfirmarEntrega" class="modal-btn modal-btn-confirm">Confirmar Entrega</button>
                </div>
            </div>
        </dialog>

        <!-- Modal de devolução -->
        <dialog id="devolucaoModal" class="modal-overlay">
            <div class="modal-content">
                <span id="closeDevolucaoModal" class="modal-close">&times;</span>
                <div class="modal-header">
                    <h2>Confirmar Devolução</h2>
                </div>
                <div class="modal-body">
                    <div class="book-cover">
                        <img id="capaLivroDevolucao" src="" alt="Capa do Livro">
                    </div>
                    <div class="book-details">
                        <p><strong>Título:</strong> <span id="tituloLivroDevolucao"></span></p>
                        <p><strong>Autor:</strong> <span id="autorLivroDevolucao"></span></p>
                        <p><strong>ISBN:</strong> <span id="isbnLivroDevolucao"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btnCancelarDevolucao" class="modal-btn modal-btn-cancel">Cancelar</button>
                    <button id="btnConfirmarDevolucao" class="modal-btn modal-btn-confirm">Confirmar Devolução</button>
                </div>
            </div>
        </dialog>
    </main>

    <?php include 'footer.php'; ?>
    <script src="./assets/js/gerir-requisicoes.js"></script>
</body>
</html>