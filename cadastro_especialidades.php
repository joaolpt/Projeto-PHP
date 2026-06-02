<?php
session_start();
require_once("conexao.php");

// Verificação de segurança da sessão
if(!isset($_SESSION['cod_usuario'])){
    header("Location: login.php");
    exit;
}

// Recupera dados do operador
$cod_usuario = $_SESSION['cod_usuario'];
$nomeUsuario = "";
$emailUsuario = "";
$sql_user = "SELECT * FROM usuario WHERE cod_usuario = '$cod_usuario'";
$result_user = mysqli_query($conexao_bd, $sql_user);

if($consulta = mysqli_fetch_assoc($result_user)){
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
}
$operadorNome  = $nomeUsuario;

// ============================================================
// LÓGICA DE NEGÓCIOS (CRUD)
// ============================================================
$mensagem = "";
$modoEdicao = false;
$idEdicao = null;
$nomeEdicao = "";

// 1. INSERIR (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $nomeEspecialidade = mysqli_real_escape_string($conexao_bd, trim($_POST['nome']));
    
    if (!empty($nomeEspecialidade)) {
        // Verifica duplicidade para evitar o erro de UNIQUE KEY do script do professor
        $sqlCheck = "SELECT id FROM especialidades WHERE nome = '$nomeEspecialidade'";
        $resultCheck = mysqli_query($conexao_bd, $sqlCheck);
        
        if (mysqli_num_rows($resultCheck) > 0) {
            $mensagem = "<div class='alert alert-warning alert-dismissible fade show'>Esta especialidade já está cadastrada.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $sqlInsert = "INSERT INTO especialidades (nome) VALUES ('$nomeEspecialidade')";
            if (mysqli_query($conexao_bd, $sqlInsert)) {
                $mensagem = "<div class='alert alert-success alert-dismissible fade show'>Especialidade cadastrada com sucesso!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $mensagem = "<div class='alert alert-danger alert-dismissible fade show'>Erro ao cadastrar: " . mysqli_error($conexao_bd) . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    }
}

// 2. EXCLUIR (Delete)
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $idExcluir = (int)$_GET['id'];
    
    // O banco possui restrição (ON DELETE RESTRICT). Falhará se houver médicos na especialidade.
    $sqlDelete = "DELETE FROM especialidades WHERE id = $idExcluir";
    if (mysqli_query($conexao_bd, $sqlDelete)) {
        $mensagem = "<div class='alert alert-success alert-dismissible fade show'>Especialidade excluída com sucesso!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $mensagem = "<div class='alert alert-danger alert-dismissible fade show'>Não é possível excluir. Existem médicos vinculados a esta especialidade.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 3. PREPARAR EDIÇÃO (Read para Update)
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $modoEdicao = true;
    $idEdicao = (int)$_GET['id'];
    $sqlBusca = "SELECT nome FROM especialidades WHERE id = $idEdicao";
    $resultBusca = mysqli_query($conexao_bd, $sqlBusca);
    if ($row = mysqli_fetch_assoc($resultBusca)) {
        $nomeEdicao = $row['nome'];
    }
}

// 4. ATUALIZAR (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $idAtualizar = (int)$_POST['id_especialidade'];
    $nomeNovo = mysqli_real_escape_string($conexao_bd, trim($_POST['nome']));
    
    if (!empty($nomeNovo)) {
        $sqlUpdate = "UPDATE especialidades SET nome = '$nomeNovo' WHERE id = $idAtualizar";
        if (mysqli_query($conexao_bd, $sqlUpdate)) {
            $mensagem = "<div class='alert alert-success alert-dismissible fade show'>Especialidade atualizada com sucesso!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $modoEdicao = false; 
            $nomeEdicao = "";
        } else {
            $mensagem = "<div class='alert alert-danger alert-dismissible fade show'>Erro ao atualizar a especialidade.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Especialidades</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --azul-primario: #0d6efd;
            --azul-escuro:   #084298;
            --azul-claro:    #e7f1ff;
            --cinza-fundo:   #f5f7fa;
            --cinza-borda:   #e3e6ea;
            --texto-escuro:  #1f2d3d;
            --sidebar-larg:  250px;
        }
        body { background-color: var(--cinza-fundo); font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--texto-escuro); overflow-x: hidden; }
        .navbar-topo { background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%); height: 60px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
        .navbar-topo .navbar-brand { color: #fff; font-weight: 600; font-size: 1.25rem; }
        .navbar-topo .navbar-brand i { margin-right: 8px; }
        .btn-sanduiche { background: transparent; border: none; color: #fff; font-size: 1.3rem; padding: 6px 12px; border-radius: 6px; }
        .operador-toggle { background: transparent; border: none; color: #fff; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 30px; }
        .operador-toggle i.fa-circle-user { font-size: 1.6rem; }
        .dropdown-menu-operador { min-width: 220px; border-radius: 10px; border: none; }
        .dropdown-menu-operador .dropdown-item i { width: 22px; color: var(--azul-primario); }
        .sidebar { position: fixed; top: 60px; left: 0; width: var(--sidebar-larg); height: calc(100vh - 60px); background: #fff; border-right: 1px solid var(--cinza-borda); padding: 20px 0; z-index: 1020; overflow-y: auto; transition: transform 0.3s ease; }
        .sidebar.oculta { transform: translateX(calc(var(--sidebar-larg) * -1)); }
        .sidebar .nav-link { color: var(--texto-escuro); padding: 12px 20px; border-left: 3px solid transparent; display: flex; align-items: center; gap: 12px; }
        .sidebar .nav-link i { width: 22px; color: var(--azul-primario); font-size: 1.05rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.ativo { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); font-weight: 600; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1010; }
        .sidebar-overlay.ativo { display: block; }
        .conteudo-principal { margin-top: 60px; margin-left: var(--sidebar-larg); padding: 25px; transition: margin-left 0.3s ease; min-height: calc(100vh - 60px); }
        .conteudo-principal.expandido { margin-left: 0; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(calc(var(--sidebar-larg) * -1)); }
            .sidebar.aberta { transform: translateX(0); box-shadow: 2px 0 12px rgba(0,0,0,0.15); }
            .conteudo-principal { margin-left: 0; }
        }
        .card-padrao { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); padding: 20px; }
    </style>
</head>
<body>

    <nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-sanduiche" id="btnSanduiche"><i class="fa-solid fa-bars"></i></button>
            <a class="navbar-brand mb-0 d-flex align-items-center" href="principal.php">
                <i class="fa-solid fa-stethoscope"></i> <span>MediAgenda</span>
            </a>
        </div>
        <div class="dropdown">
            <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown">
                <i class="fa-solid fa-circle-user"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome); ?></span> <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador">
                <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ativo" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a>
            </li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0 text-gray-800">Especialidades Médicas</h2>
        </div>
        
        <?php if(!empty($mensagem)) echo $mensagem; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card-padrao">
                    <h5 class="mb-3"><?php echo $modoEdicao ? 'Editar Especialidade' : 'Nova Especialidade'; ?></h5>
                    
                    <form method="POST" action="cadastro_especialidades.php">
                        <?php if($modoEdicao): ?>
                            <input type="hidden" name="id_especialidade" value="<?php echo $idEdicao; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Especialidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nomeEdicao); ?>" required autofocus>
                        </div>
                        
                        <?php if($modoEdicao): ?>
                            <button type="submit" name="atualizar" class="btn btn-warning w-100 mb-2"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar Alterações</button>
                            <a href="cadastro_especialidades.php" class="btn btn-secondary w-100">Cancelar Edição</a>
                        <?php else: ?>
                            <button type="submit" name="cadastrar" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i>Cadastrar Especialidade</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card-padrao">
                    <h5 class="mb-3">Especialidades Cadastradas</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="10%">ID</th>
                                    <th scope="col" width="60%">Nome da Especialidade</th>
                                    <th scope="col" width="30%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 5. LISTAR (Read)
                                $sqlListar = "SELECT * FROM especialidades ORDER BY nome ASC";
                                $resultListar = mysqli_query($conexao_bd, $sqlListar);
                                
                                if (mysqli_num_rows($resultListar) > 0) {
                                    while ($row = mysqli_fetch_assoc($resultListar)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($row['nome']) . "</strong></td>";
                                        echo "<td class='text-center'>
                                                <a href='cadastro_especialidades.php?acao=editar&id=" . $row['id'] . "' class='btn btn-sm btn-outline-primary me-2' title='Editar'>
                                                    <i class='fa-solid fa-pen'></i>
                                                </a>
                                                <a href='cadastro_especialidades.php?acao=excluir&id=" . $row['id'] . "' class='btn btn-sm btn-outline-danger' title='Excluir' onclick='return confirm(\"Tem certeza que deseja excluir a especialidade " . addslashes($row['nome']) . "?\");'>
                                                    <i class='fa-solid fa-trash'></i>
                                                </a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-4 text-muted'>Nenhuma especialidade cadastrada no sistema.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const btnSanduiche = document.getElementById('btnSanduiche');
        const sidebar = document.getElementById('sidebar');
        const conteudoPrincipal = document.getElementById('conteudoPrincipal');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        btnSanduiche.addEventListener('click', () => {
            if (window.innerWidth <= 991.98) {
                sidebar.classList.toggle('aberta');
                sidebarOverlay.classList.toggle('ativo');
            } else {
                sidebar.classList.toggle('oculta');
                conteudoPrincipal.classList.toggle('expandido');
            }
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('aberta');
            sidebarOverlay.classList.remove('ativo');
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('aberta');
                sidebarOverlay.classList.remove('ativo');
            }
        });
    </script>
</body>
</html>