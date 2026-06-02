<?php
session_start();
require_once("conexao.php");

if(!isset($_SESSION['cod_usuario'])){
    header("Location: login.php");
    exit;
}
$cod_usuario = $_SESSION['cod_usuario'];
$nomeUsuario = "";
$emailUsuario = "";
$sql = "SELECT * FROM usuario WHERE cod_usuario = '$cod_usuario'";
$result = mysqli_query($conexao_bd,$sql);

if($consulta = mysqli_fetch_assoc($result)){
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
}

$operadorNome  = $nomeUsuario;
$operadorEmail = $emailUsuario;
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

        body {
            background-color: var(--cinza-fundo);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--texto-escuro);
            overflow-x: hidden;
        }

        /* NAVBAR SUPERIOR */
        .navbar-topo {
            background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%);
            height: 60px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
        }
        .navbar-topo .navbar-brand { color: #fff; font-weight: 600; font-size: 1.25rem; }
        .navbar-topo .navbar-brand i { margin-right: 8px; }
        .btn-sanduiche { background: transparent; border: none; color: #fff; font-size: 1.3rem; padding: 6px 12px; border-radius: 6px; }
        .operador-toggle { background: transparent; border: none; color: #fff; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 30px; }
        .operador-toggle i.fa-circle-user { font-size: 1.6rem; }
        .dropdown-menu-operador { min-width: 220px; border-radius: 10px; border: none; }
        .dropdown-menu-operador .dropdown-item i { width: 22px; color: var(--azul-primario); }

        /* SIDEBAR LATERAL */
        .sidebar {
            position: fixed; top: 60px; left: 0; width: var(--sidebar-larg); height: calc(100vh - 60px);
            background: #fff; border-right: 1px solid var(--cinza-borda); padding: 20px 0; z-index: 1020; overflow-y: auto; transition: transform 0.3s ease;
        }
        .sidebar.oculta { transform: translateX(calc(var(--sidebar-larg) * -1)); }
        .sidebar .nav-link { color: var(--texto-escuro); padding: 12px 20px; border-left: 3px solid transparent; display: flex; align-items: center; gap: 12px; }
        .sidebar .nav-link i { width: 22px; color: var(--azul-primario); font-size: 1.05rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.ativo { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); }
        .sidebar .nav-link.ativo { font-weight: 600; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1010; }
        .sidebar-overlay.ativo { display: block; }

        /* CONTEÚDO PRINCIPAL */
        .conteudo-principal { margin-top: 60px; margin-left: var(--sidebar-larg); padding: 25px; transition: margin-left 0.3s ease; min-height: calc(100vh - 60px); }
        .conteudo-principal.expandido { margin-left: 0; }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(calc(var(--sidebar-larg) * -1)); }
            .sidebar.aberta { transform: translateX(0); box-shadow: 2px 0 12px rgba(0,0,0,0.15); }
            .conteudo-principal { margin-left: 0; }
        }

        /* ESTILOS ESPECÍFICOS DO CRUD (Padrão visual de tabelas e cards) */
        .card-padrao {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); padding: 20px;
        }
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
                <i class="fa-solid fa-circle-user"></i> <span class="d-none d-md-inline"><?php echo($operadorNome); ?></span> <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
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

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card-padrao">
                    <h5 class="mb-3">Nova Especialidade</h5>
                    <p class="text-muted small">Área reservada para o formulário de cadastro.</p>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card-padrao">
                    <h5 class="mb-3">Especialidades Cadastradas</h5>
                    <p class="text-muted small">Área reservada para a listagem (CRUD).</p>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // TOGGLE DA SIDEBAR (Mantendo a responsividade do professor)
        const btnSanduiche      = document.getElementById('btnSanduiche');
        const sidebar           = document.getElementById('sidebar');
        const conteudoPrincipal = document.getElementById('conteudoPrincipal');
        const sidebarOverlay    = document.getElementById('sidebarOverlay');

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