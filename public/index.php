<?php
$host = apache_getenv("DB_HOST");
$dbname = apache_getenv("DB_NAME");
$user = apache_getenv("DB_USER");
$password = apache_getenv("DB_PASS");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

// Seleciona as listas com a nova funcionalidade de ordenação
$sql = "SELECT * FROM listas ORDER BY posicao";
$listas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBoard - Kanban</title>
    <!-- Combinei o arquivo de estilos correto com o script necessário -->
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
</head>
<body>
    <div class="board-header">
        <div class="board-title">
            <img src="logo.png" alt="Logo GreenBoard" class="logo">
            <h1>GreenBoard</h1>
        </div>
        <div class="users">
            <img src="olivia.jpeg" alt="Usuário 1" class="user-icon">
            <img src="taylor.jpg" alt="Usuário 2" class="user-icon">
            <img src="lalisa.jpg" alt="Usuário 3" class="user-icon">
            <span class="extra-users">+1</span>
        </div>
    </div>

    <div class="scroll-container">
        <div class="kanban-board">
            <?php foreach ($listas as $lista): ?>
                <div class="column" id="lista_<?php echo $lista['id']; ?>">
                    <div class="column-header">
                        <div class="title-container">
                            <h2><?php echo $lista['titulo']; ?></h2>
                        </div>
                        <div class="column-options">
                            <span class="options-icon" onclick="toggleOptions(<?php echo $lista['id']; ?>)" style="color: black;">&#9998;</span>
                            <div class="options-menu" id="options_menu_<?php echo $lista['id']; ?>">
                                <button class="edit-list-btn" onclick="editItem('lista', <?php echo $lista['id']; ?>, '<?php echo $lista['titulo']; ?>')">Editar</button>
                                <button class="delete-list-btn" onclick="deleteColumn(<?php echo $lista['id']; ?>)">Excluir</button>
                            </div>
                        </div>
                    </div>
                    <div class="cards-container">
                        <?php
                        $sqlCards = "SELECT * FROM cartoes WHERE lista_id = :lista_id ORDER BY posicao";
                        $stmt = $pdo->prepare($sqlCards);
                        $stmt->bindParam(':lista_id', $lista['id']);
                        $stmt->execute();
                        $cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($cartoes as $cartao): ?>
                            <div class="card" id="card_<?php echo $cartao['id']; ?>">
                                <div class="card-header">
                                    <p><?php echo $cartao['corpo']; ?></p>
                                    <div class="card-options">
                                        <span class="options-icon" onclick="toggleOptions(<?php echo $lista['id']; ?>)" style="color: black;">&#9998;</span>
                                        <div class="card-options-menu" id="card_options_menu_<?php echo $cartao['id']; ?>">
                                            <button class="edit-card-btn" onclick="editItem('cartao', <?php echo $cartao['id']; ?>, '<?php echo $cartao['corpo']; ?>')">Editar</button>
                                            <button class="delete-card-btn" onclick="deleteCard(<?php echo $cartao['id']; ?>)">Excluir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="add-card-container">
                            <button id="addCardButton_<?php echo $lista['id']; ?>" class="add-card-btn" onclick="showAddCardForm(<?php echo $lista['id']; ?>)">Adicionar Cartão
                                <img src="/resources/plus.png" alt="Adicionar" class="icon" style="width: 20px; height: 20px; margin-left: 5px;">
                            </button>
                            <form id="addCardForm_<?php echo $lista['id']; ?>" class="add-card-form" style="display:none;" onsubmit="addCard(event, <?php echo $lista['id']; ?>)">
                                <input type="text" name="corpo_cartao" placeholder="Insira um nome para o cartão..." required>
                                <button type="submit" style="font-size: 15px;">Adicionar Cartão</button>
                                <button type="button" style="background-color: transparent;" onclick="hideAddCardForm(<?php echo $lista['id']; ?>)">
                                    <img src="/resources/close_icon.png" alt="Fechar" style="width: 20px; height: 20px;">
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Adicionar nova lista -->
            <div class="add-list-container">
                <button id="addListButton" class="add-card-btn" style="width: 250px; border-radius: 15px; height: 55px; background-color: #91d991; margin-right: 10px; margin-top: -10px;" onclick="showAddListForm()">Adicionar Lista
                    <img src="/resources/plus.png" alt="Adicionar" class="icon" style="width: 20px; height: 20px; margin-left: 45px;">
                </button>

                <form id="addListForm" class="add-list-form" style="display:none;" onsubmit="addList(event)">
                    <input type="text" name="titulo_lista" placeholder="Insira um título para a lista..." required>
                    <button type="submit" style="font-size: 15px;">Adicionar Lista</button>
                    <button type="button" style="background-color: transparent;" onclick="hideAddListForm()">
                        <img src="/resources/close_icon.png" alt="Fechar" style="width: 20px; height: 20px;">
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Funções para exibir/ocultar formulários de adicionar cartão e lista
        function showAddCardForm(lista_id) {
            const form = document.getElementById(`addCardForm_${lista_id}`);
            const button = document.querySelector(`#addCardButton_${lista_id}`);
            if (form && button) {
                button.style.display = 'none';
                form.style.display = 'block'; 
            }
        }

        function hideAddCardForm(lista_id) {
            const form = document.getElementById(`addCardForm_${lista_id}`);
            const button = document.querySelector(`#addCardButton_${lista_id}`);
            if (form && button) {
                form.style.display = 'none'; 
                button.style.display = 'block'; 
            }
        }

        function addCard(event, lista_id) {
            event.preventDefault();
            const form = document.getElementById(`addCardForm_${lista_id}`);
            const formData = new FormData(form);
            formData.append('lista_id', lista_id);

            fetch('adicionar_cartao.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload(); 
            })
            .catch(error => console.error('Erro:', error));
        }

        function addList(event) {
            event.preventDefault();
            const form = document.getElementById('addListForm');
            const formData = new FormData(form);
            formData.append('quadro_id', 1);
            fetch('adicionar_lista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();  
            })
            .catch(error => console.error('Erro:', error));
        }

        function showAddListForm() {
            const form = document.getElementById('addListForm');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
            document.getElementById('addListButton').style.display = 'none'; 
        }

        function hideAddListForm() {
            const form = document.getElementById('addListForm');
            form.style.display = 'none';
            document.getElementById('addListButton').style.display = 'block'; 
        }

        // Funções de excluir lista e cartão

        function editItem(tipo, item_id, texto) {
            let novo_texto = prompt(tipo == 'lista' ? "Entre o novo título da lista" : "Entre o novo corpo do cartão", texto);
            if (novo_texto) {
                const formData = new FormData();
                formData.append('editar_item_id', item_id);
                formData.append('editar_item_tipo', tipo);
                formData.append('editar_item_texto', novo_texto);
                
                fetch('editar_item.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    alert(result);
                    window.location.reload();
                })
                .catch(error => console.error('Erro:', error));
            }
        }

        // Tornar listas e cartões arrastáveis com Sortable.js (Funções combinadas de ambas as branches)
        document.addEventListener('DOMContentLoaded', function () {
            new Sortable(document.querySelector('.kanban-board'), {
                group: 'listas',
                animation: 150,
                handle: '.column-header',
                onEnd: function (evt) {
                    let listas = document.querySelectorAll('.column');
                    let order = [];
                    listas.forEach((lista, index) => {
                        order.push({
                            id: lista.id.replace('lista_', ''),
                            position: index + 1
                        });
                    });
                    atualizarOrdemListas(order);
                }
            });
		});

        function deleteColumn(lista_id) {
            if (confirm("Tem certeza que deseja excluir esta lista?")) {
                const formData = new FormData();
                formData.append('excluir_lista_id', lista_id);
                fetch('excluir_lista.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    alert(result);
                    document.getElementById(`lista_${lista_id}`).remove();
                })
                .catch(error => console.error('Erro:', error));
            }
        }

        function deleteCard(cartao_id) {
            if (confirm("Tem certeza que deseja excluir este cartão?")) {
                const formData = new FormData();
                formData.append('excluir_cartao_id', cartao_id);
                fetch('excluir_cartao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    alert(result);
                    document.getElementById(`card_${cartao_id}`).remove();
                })
                .catch(error => console.error('Erro:', error));
            }
        }
        
        document.querySelectorAll('.cards-container').forEach(function (container) {
            new Sortable(container, {
                group: 'cartoes',
                animation: 150,
                handle: '.card',
                onEnd: function (evt) {
                    let cartoes = evt.to.querySelectorAll('.card');
                    let order = [];
                    cartoes.forEach((cartao, index) => {
                        order.push({
                            id: cartao.id.replace('card_', ''),
                            lista_id: evt.to.closest('.column').id.replace('lista_', ''),
                            position: index + 1
                        });
                    });
                    atualizarOrdemCartoes(order);
                }
            });
        });

        // Funções para atualizar a ordem das listas e cartões no banco de dados
        function atualizarOrdemListas(order) {
            fetch('atualizar_ordem_listas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(order)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('Ordem das listas atualizada com sucesso');
                } else {
                    console.error('Erro ao atualizar a ordem das listas');
                }
            })
            .catch(error => console.error('Erro:', error));
        }

        function atualizarOrdemCartoes(order) {
            fetch('atualizar_ordem_cartoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(order)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('Ordem dos cartões atualizada com sucesso');
                } else {
                    console.error('Erro ao atualizar a ordem dos cartões');
                }
            })
            .catch(error => console.error('Erro:', error));
        }
    </script>
</body>
</html>
