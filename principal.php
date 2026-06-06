<?php
session_start();
require_once("conexao.php");

if (!isset($_SESSION['cod_usuario'])) {
    header("Location: login.php");
    exit;
}

$cod_usuario  = $_SESSION['cod_usuario'];
$nomeUsuario  = "";
$emailUsuario = "";
$sql = "SELECT * FROM usuario WHERE cod_usuario = '$cod_usuario'";
$result = mysqli_query($conexao_bd, $sql);
if ($consulta = mysqli_fetch_assoc($result)) {
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
}

$operadorNome  = $nomeUsuario;
$operadorEmail = $emailUsuario;

/* ============================================================
   DADOS DO MÊS ATUAL
============================================================ */
$mesAtual        = isset($_GET['mes']) ? max(1, min(12, (int)$_GET['mes'])) : (int)date('n');
$anoAtual        = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$nomesMeses      = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                   'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$nomeMes         = $nomesMeses[$mesAtual];
$primeiroDia     = mktime(0, 0, 0, $mesAtual, 1, $anoAtual);
$diaSemanaInicio = (int)date('w', $primeiroDia);
$totalDias       = (int)date('t', $primeiroDia);
$diaHoje         = (int)date('j');
$mesHoje         = (int)date('n');
$anoHoje         = (int)date('Y');

$mesAnterior = $mesAtual - 1; $anoAnterior = $anoAtual;
if ($mesAnterior < 1)  { $mesAnterior = 12; $anoAnterior--; }
$proximoMes  = $mesAtual + 1; $proximoAno  = $anoAtual;
if ($proximoMes > 12) { $proximoMes  = 1;  $proximoAno++;  }

/* ============================================================
   BUSCA AGENDAMENTOS DO MÊS NO BANCO
============================================================ */
$agendamentosFicticios = [];
$sql = "SELECT *, DAY(data) diaAgenda FROM vw_agendamentos
        WHERE MONTH(data) = $mesAtual AND YEAR(data) = $anoAtual
        ORDER BY horario ASC";
$result = mysqli_query($conexao_bd, $sql);
while ($row = $result->fetch_assoc()) {
    $agendamentosFicticios[$row['diaAgenda']][] = [
        'id'            => $row['id'],
        'horario'       => date('H:i', strtotime($row['horario'])),
        'paciente'      => $row['paciente'],
        'medico'        => $row['medico'],
        'medico_id'     => $row['medico_id'],       // necessário para o modal de edição
        'especialidade' => $row['especialidade'],
        'status'        => $row['status'],
        'data_iso'      => $row['data'],             // formato YYYY-MM-DD para o input date
    ];
}

/* ============================================================
   BUSCA MÉDICOS PARA O SELECT DO MODAL DE EDIÇÃO
============================================================ */
$medicosSelect = [];
$resMed = mysqli_query($conexao_bd, "SELECT m.id, m.nome, e.nome AS esp_nome
                                      FROM medicos m
                                      JOIN especialidades e ON m.especialidade_id = e.id
                                      WHERE m.status = 'Ativo'
                                      ORDER BY m.nome");
while ($row = mysqli_fetch_assoc($resMed)) {
    $medicosSelect[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Painel Principal</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
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

        /* NAVBAR */
        .navbar-topo { background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%); height: 60px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
        .navbar-topo .navbar-brand { color: #fff; font-weight: 600; font-size: 1.25rem; }
        .navbar-topo .navbar-brand i { margin-right: 8px; }
        .btn-sanduiche { background: transparent; border: none; color: #fff; font-size: 1.3rem; padding: 6px 12px; border-radius: 6px; transition: background 0.2s; }
        .btn-sanduiche:hover { background: rgba(255,255,255,0.15); }
        .operador-toggle { background: transparent; border: none; color: #fff; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 30px; transition: background 0.2s; }
        .operador-toggle:hover, .operador-toggle:focus { background: rgba(255,255,255,0.15); color: #fff; }
        .operador-toggle i.fa-circle-user { font-size: 1.6rem; }
        .dropdown-menu-operador { min-width: 220px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); border: none; }
        .dropdown-menu-operador .dropdown-item i { width: 22px; color: var(--azul-primario); }

        /* SIDEBAR */
        .sidebar { position: fixed; top: 60px; left: 0; width: var(--sidebar-larg); height: calc(100vh - 60px); background: #fff; border-right: 1px solid var(--cinza-borda); padding: 20px 0; transition: transform 0.3s ease; z-index: 1020; overflow-y: auto; }
        .sidebar.oculta { transform: translateX(calc(var(--sidebar-larg) * -1)); }
        .sidebar .nav-link { color: var(--texto-escuro); padding: 12px 20px; border-left: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 12px; }
        .sidebar .nav-link i { width: 22px; color: var(--azul-primario); font-size: 1.05rem; }
        .sidebar .nav-link:hover { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); }
        .sidebar .nav-link.ativo { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); font-weight: 600; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1010; }
        .sidebar-overlay.ativo { display: block; }

        /* CONTEÚDO */
        .conteudo-principal { margin-top: 60px; margin-left: var(--sidebar-larg); padding: 25px; transition: margin-left 0.3s ease; min-height: calc(100vh - 60px); }
        .conteudo-principal.expandido { margin-left: 0; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(calc(var(--sidebar-larg) * -1)); }
            .sidebar.aberta { transform: translateX(0); box-shadow: 2px 0 12px rgba(0,0,0,0.15); }
            .conteudo-principal { margin-left: 0; }
        }

        /* CALENDÁRIO */
        .card-calendario { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); overflow: hidden; }
        .calendario-cabecalho { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid var(--cinza-borda); flex-wrap: wrap; gap: 10px; }
        .calendario-cabecalho h4 { margin: 0; color: var(--azul-escuro); font-weight: 600; text-transform: capitalize; }
        .calendario-cabecalho .btn-nav { border: 1px solid var(--cinza-borda); background: #fff; color: var(--texto-escuro); padding: 6px 12px; border-radius: 6px; transition: all 0.2s; text-decoration: none; }
        .calendario-cabecalho .btn-nav:hover { background: var(--azul-claro); color: var(--azul-primario); border-color: var(--azul-primario); }
        .calendario-grade { display: grid; grid-template-columns: repeat(7, 1fr); background: var(--cinza-borda); gap: 1px; }
        .calendario-grade .dia-semana { background: #fafbfc; text-align: center; padding: 10px 4px; font-weight: 600; font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .calendario-grade .dia { background: #fff; min-height: 120px; padding: 8px; position: relative; transition: background 0.15s; display: flex; flex-direction: column; }
        .calendario-grade .dia:hover { background: #fafbfc; }
        .calendario-grade .dia.vazio { background: #f8f9fa; }
        .calendario-grade .dia .numero { font-weight: 600; font-size: 0.95rem; color: var(--texto-escuro); margin-bottom: 4px; }
        .calendario-grade .dia.hoje .numero { background: var(--azul-primario); color: #fff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
        .card-agendamento { background: var(--azul-claro); border-left: 3px solid var(--azul-primario); border-radius: 4px; padding: 4px 6px; margin-bottom: 3px; font-size: 0.75rem; cursor: pointer; transition: all 0.15s; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .card-agendamento:hover { background: var(--azul-primario); color: #fff; transform: translateX(2px); }
        .card-agendamento .horario { font-weight: 600; }
        .card-agendamento .paciente { display: block; overflow: hidden; text-overflow: ellipsis; }
        .link-mais { font-size: 0.72rem; color: var(--azul-primario); cursor: pointer; font-weight: 600; margin-top: 2px; display: inline-block; }
        .link-mais:hover { text-decoration: underline; }

        /* MODAL DETALHES */
        .modal-detalhe .modal-header { background: var(--azul-primario); color: #fff; }
        .modal-detalhe .modal-header .btn-close { filter: invert(1); }
        .modal-detalhe .info-item { padding: 10px 0; border-bottom: 1px solid var(--cinza-borda); display: flex; align-items: center; gap: 12px; }
        .modal-detalhe .info-item:last-child { border-bottom: none; }
        .modal-detalhe .info-item i { color: var(--azul-primario); width: 22px; font-size: 1.05rem; }
        .modal-detalhe .info-item strong { color: #6c757d; font-weight: 500; margin-right: 8px; }

        /* MODAL EDIÇÃO */
        .modal-form .modal-header { background: var(--azul-primario); color: #fff; }
        .modal-form .modal-header .btn-close { filter: invert(1); }
        .modal-form label { font-weight: 500; font-size: 0.88rem; margin-bottom: 4px; }

        /* MODAL LISTA DO DIA */
        .modal-lista .modal-header { background: var(--azul-escuro); color: #fff; }
        .modal-lista .modal-header .btn-close { filter: invert(1); }
        .item-lista-dia { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--cinza-borda); gap: 10px; }
        .item-lista-dia:last-child { border-bottom: none; }
        .item-lista-dia .info { flex: 1; }
        .item-lista-dia .info .paciente-nome { font-weight: 600; font-size: 0.9rem; }
        .item-lista-dia .info .detalhes { font-size: 0.8rem; color: #6c757d; }
        .badge-status { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-confirmado { background: #d1e7dd; color: #0a3622; }
        .badge-pendente   { background: #fff3cd; color: #664d03; }
        .badge-cancelado  { background: #f8d7da; color: #58151c; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn-sanduiche" id="btnSanduiche" title="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <a class="navbar-brand mb-0 d-flex align-items-center" href="#">
            <i class="fa-solid fa-stethoscope"></i>
            <span>MediAgenda</span>
        </a>
    </div>
    <div class="dropdown">
        <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-circle-user"></i>
            <span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome); ?></span>
            <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear"></i>Configurações</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
        </ul>
    </div>
</nav>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link ativo" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a></li>
    </ul>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- CONTEÚDO PRINCIPAL -->
<main class="conteudo-principal" id="conteudoPrincipal">
    <div class="card-calendario">

        <!-- Cabeçalho com navegação de mês -->
        <div class="calendario-cabecalho">
            <h4><?php echo $nomeMes ?> <?php echo $anoAtual ?></h4>
            <div class="d-flex gap-2">
                <a class="btn-nav" href="?mes=<?php echo $mesAnterior ?>&ano=<?php echo $anoAnterior ?>" title="Mês anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a class="btn-nav" href="?" title="Hoje">Hoje</a>
                <a class="btn-nav" href="?mes=<?php echo $proximoMes ?>&ano=<?php echo $proximoAno ?>" title="Próximo mês">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Grade -->
        <div class="calendario-grade">
            <div class="dia-semana">Dom</div>
            <div class="dia-semana">Seg</div>
            <div class="dia-semana">Ter</div>
            <div class="dia-semana">Qua</div>
            <div class="dia-semana">Qui</div>
            <div class="dia-semana">Sex</div>
            <div class="dia-semana">Sáb</div>

            <?php for ($i = 0; $i < $diaSemanaInicio; $i++): ?>
                <div class="dia vazio"></div>
            <?php endfor; ?>

            <?php for ($dia = 1; $dia <= $totalDias; $dia++):
                $classeHoje        = ($dia === $diaHoje && $mesAtual === $mesHoje && $anoAtual === $anoHoje) ? 'hoje' : '';
                $agendamentosDoDia = isset($agendamentosFicticios[$dia]) ? $agendamentosFicticios[$dia] : [];
                $maxExibir         = 3;
                $totalAgend        = count($agendamentosDoDia);
                $exibir            = array_slice($agendamentosDoDia, 0, $maxExibir);
                // Serializa todos os agendamentos do dia para o botão "+ N mais"
                $todosDoDia = htmlspecialchars(json_encode($agendamentosDoDia), ENT_QUOTES, 'UTF-8');
                $dataFormatada = sprintf('%02d/%02d/%d', $dia, $mesAtual, $anoAtual);
            ?>
            <div class="dia <?php echo $classeHoje ?>">
                <span class="numero"><?php echo $dia ?></span>

                <?php foreach ($exibir as $agend): ?>
                    <div class="card-agendamento"
                         data-id="<?php echo $agend['id'] ?>"
                         data-horario="<?php echo htmlspecialchars($agend['horario']) ?>"
                         data-paciente="<?php echo htmlspecialchars($agend['paciente']) ?>"
                         data-medico="<?php echo htmlspecialchars($agend['medico']) ?>"
                         data-medico-id="<?php echo $agend['medico_id'] ?>"
                         data-especialidade="<?php echo htmlspecialchars($agend['especialidade']) ?>"
                         data-status="<?php echo htmlspecialchars($agend['status']) ?>"
                         data-data="<?php echo $dataFormatada ?>"
                         data-data-iso="<?php echo $agend['data_iso'] ?>">
                        <span class="horario"><?php echo htmlspecialchars($agend['horario']) ?></span>
                        <span class="paciente"><?php echo htmlspecialchars($agend['paciente']) ?></span>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalAgend > $maxExibir): ?>
                    <span class="link-mais"
                          data-dia="<?php echo $dataFormatada ?>"
                          data-agendamentos="<?php echo $todosDoDia ?>">
                        + <?php echo $totalAgend - $maxExibir ?> mais
                    </span>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</main>

<!-- ══════════════════════════════════════════════════════════
     MODAL 1 — DETALHES DO AGENDAMENTO (clique no card)
══════════════════════════════════════════════════════════ -->
<div class="modal fade modal-detalhe" id="modalAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-check me-2"></i>Detalhes do Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="info-item"><i class="fa-solid fa-user"></i><div><strong>Paciente:</strong> <span id="modalPaciente"></span></div></div>
                <div class="info-item"><i class="fa-solid fa-user-doctor"></i><div><strong>Médico:</strong> <span id="modalMedico"></span></div></div>
                <div class="info-item"><i class="fa-solid fa-stethoscope"></i><div><strong>Especialidade:</strong> <span id="modalEspecialidade"></span></div></div>
                <div class="info-item"><i class="fa-solid fa-calendar"></i><div><strong>Data:</strong> <span id="modalData"></span></div></div>
                <div class="info-item"><i class="fa-solid fa-clock"></i><div><strong>Horário:</strong> <span id="modalHorario"></span></div></div>
                <div class="info-item"><i class="fa-solid fa-circle-info"></i><div><strong>Status:</strong> <span id="modalStatus"></span></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btnCancelarAgendamento">
                    <i class="fa-solid fa-ban me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnEditarAgendamento">
                    <i class="fa-solid fa-pen me-1"></i> Editar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL 2 — LISTA DO DIA (clique em "+ N mais")
══════════════════════════════════════════════════════════ -->
<div class="modal fade modal-lista" id="modalListaDia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalListaTitulo">
                    <i class="fa-solid fa-calendar-days me-2"></i>Agendamentos do dia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalListaCorpo">
                <!-- preenchido via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL 3 — FORMULÁRIO DE EDIÇÃO (abre ao clicar "Editar")
══════════════════════════════════════════════════════════ -->
<div class="modal fade modal-form" id="modalEditarAgenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Editar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditar" action="cadastro_agendas.php" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id"   id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="editPaciente">Paciente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editPaciente" name="paciente" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editMedico">Médico <span class="text-danger">*</span></label>
                            <select class="form-select" id="editMedico" name="medico_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($medicosSelect as $m): ?>
                                    <option value="<?php echo $m['id'] ?>"
                                            data-esp="<?php echo htmlspecialchars($m['esp_nome']) ?>">
                                        <?php echo htmlspecialchars($m['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editEspecialidadeNome">Especialidade</label>
                            <input type="text" class="form-control" id="editEspecialidadeNome" placeholder="Auto-preenchido" readonly>
                            <input type="hidden" id="editEspecialidadeId" name="especialidade" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editData">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editData" name="data" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editHorario">Horário <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="editHorario" name="horario" required>
                        </div>
                        <div class="col-12">
                            <label for="editStatus">Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="Pendente">Pendente</option>
                                <option value="Confirmado">Confirmado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ── SIDEBAR ─────────────────────────────────────────────────────────────────
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
    sidebarOverlay.addEventListener('click', () => { sidebar.classList.remove('aberta'); sidebarOverlay.classList.remove('ativo'); });
    window.addEventListener('resize', () => { if (window.innerWidth > 991.98) { sidebar.classList.remove('aberta'); sidebarOverlay.classList.remove('ativo'); } });

    // ── INSTÂNCIAS DOS MODAIS ────────────────────────────────────────────────────
    const modalAgendamento = new bootstrap.Modal(document.getElementById('modalAgendamento'));
    const modalListaDia    = new bootstrap.Modal(document.getElementById('modalListaDia'));
    const modalEditarEl    = document.getElementById('modalEditarAgenda');
    const modalEditar      = new bootstrap.Modal(modalEditarEl);

    // Agendamento selecionado no momento (usado para cancelar e editar)
    let agendamentoAtual = {};

    // ── HELPER: abre o modal de detalhes com os dados de um agendamento ──────────
    function abrirModalDetalhe(dados) {
        agendamentoAtual = dados;
        document.getElementById('modalPaciente').textContent      = dados.paciente;
        document.getElementById('modalMedico').textContent        = dados.medico;
        document.getElementById('modalEspecialidade').textContent = dados.especialidade;
        document.getElementById('modalData').textContent          = dados.data;
        document.getElementById('modalHorario').textContent       = dados.horario;
        document.getElementById('modalStatus').textContent        = dados.status;
        modalAgendamento.show();
    }

    // ── CLIQUE NOS CARDS DO CALENDÁRIO ──────────────────────────────────────────
    document.querySelectorAll('.card-agendamento').forEach(card => {
        card.addEventListener('click', () => {
            abrirModalDetalhe({
                id:            card.dataset.id,
                paciente:      card.dataset.paciente,
                medico:        card.dataset.medico,
                medicoId:      card.dataset.medicoId,
                especialidade: card.dataset.especialidade,
                status:        card.dataset.status,
                data:          card.dataset.data,
                dataIso:       card.dataset.dataIso,
                horario:       card.dataset.horario,
            });
        });
    });

    // ── BOTÃO EDITAR no modal de detalhes ────────────────────────────────────────
    document.getElementById('btnEditarAgendamento').addEventListener('click', () => {
        // Fecha o modal de detalhes e abre o de edição com os dados preenchidos
        modalAgendamento.hide();

        document.getElementById('editId').value       = agendamentoAtual.id;
        document.getElementById('editPaciente').value = agendamentoAtual.paciente;
        document.getElementById('editData').value     = agendamentoAtual.dataIso;   // YYYY-MM-DD
        document.getElementById('editHorario').value  = agendamentoAtual.horario;
        document.getElementById('editStatus').value   = agendamentoAtual.status;

        // Seleciona o médico correto e preenche a especialidade
        const sel = document.getElementById('editMedico');
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].text === agendamentoAtual.medico) {
                sel.selectedIndex = i;
                break;
            }
        }
        const espNome = sel.options[sel.selectedIndex]?.getAttribute('data-esp') || agendamentoAtual.especialidade;
        document.getElementById('editEspecialidadeNome').value = espNome;
        document.getElementById('editEspecialidadeId').value   = espNome;

        // Aguarda o modal de detalhes fechar antes de abrir o de edição
        document.getElementById('modalAgendamento').addEventListener('hidden.bs.modal', function handler() {
            modalEditar.show();
            this.removeEventListener('hidden.bs.modal', handler);
        });
    });

    // Auto-preenche especialidade ao trocar médico no form de edição
    document.getElementById('editMedico').addEventListener('change', function () {
        const esp = this.options[this.selectedIndex]?.getAttribute('data-esp') || '';
        document.getElementById('editEspecialidadeNome').value = esp;
        document.getElementById('editEspecialidadeId').value   = esp;
    });

    // ── BOTÃO CANCELAR no modal de detalhes ──────────────────────────────────────
    document.getElementById('btnCancelarAgendamento').addEventListener('click', () => {
        Swal.fire({
            title: 'Cancelar agendamento?',
            html:  'Deseja cancelar o agendamento de <strong>' + agendamentoAtual.paciente + '</strong>' +
                   '<br>Data: ' + agendamentoAtual.data + ' às ' + agendamentoAtual.horario + '?',
            icon: 'warning',
            showCancelButton:   true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Sim, cancelar',
            cancelButtonText:   'Voltar'
        }).then(result => {
            if (!result.isConfirmed) return;

            fetch('cancelar_agendamento.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'id=' + agendamentoAtual.id
            })
            .then(r => r.json())
            .then(dados => {
                if (!dados.sucesso) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: dados.mensagem || 'Não foi possível cancelar.', confirmButtonColor: '#0d6efd' });
                    return;
                }
                // Remove o card do calendário sem recarregar
                const card = document.querySelector('.card-agendamento[data-id="' + agendamentoAtual.id + '"]');
                if (card) card.remove();

                modalAgendamento.hide();
                Swal.fire({
                    icon: 'success', title: 'Cancelado!',
                    text: 'O agendamento foi cancelado com sucesso.',
                    confirmButtonColor: '#0d6efd',
                    timer: 1800, showConfirmButton: false
                }).then(() => window.location.reload());
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Erro de comunicação', text: 'Não foi possível conectar ao servidor.', confirmButtonColor: '#0d6efd' });
            });
        });
    });

    // ── CLIQUE EM "+ N MAIS" → MODAL LISTA DO DIA ────────────────────────────────
    document.querySelectorAll('.link-mais').forEach(link => {
        link.addEventListener('click', () => {
            const dia          = link.dataset.dia;
            const agendamentos = JSON.parse(link.dataset.agendamentos);

            document.getElementById('modalListaTitulo').innerHTML =
                '<i class="fa-solid fa-calendar-days me-2"></i>Agendamentos de ' + dia;

            const corpo = document.getElementById('modalListaCorpo');
            corpo.innerHTML = '';

            agendamentos.forEach(ag => {
                // Classe do badge
                let badgeClass = 'badge-cancelado';
                if (ag.status === 'Confirmado') badgeClass = 'badge-confirmado';
                if (ag.status === 'Pendente')   badgeClass = 'badge-pendente';

                const item = document.createElement('div');
                item.className = 'item-lista-dia';
                item.innerHTML = `
                    <div class="info">
                        <div class="paciente-nome">${ag.paciente}</div>
                        <div class="detalhes">
                            <i class="fa-solid fa-clock me-1"></i>${ag.horario} &nbsp;|&nbsp;
                            <i class="fa-solid fa-user-doctor me-1"></i>${ag.medico} &nbsp;|&nbsp;
                            <i class="fa-solid fa-stethoscope me-1"></i>${ag.especialidade}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-status ${badgeClass}">${ag.status}</span>
                        <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                title="Ver detalhes"
                                onclick="verDetalhesDaLista(${JSON.stringify(ag).replace(/"/g, '&quot;')}, '${dia}')">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                `;
                corpo.appendChild(item);
            });

            modalListaDia.show();
        });
    });

    // Abre o modal de detalhes a partir da lista do dia
    function verDetalhesDaLista(ag, diaFormatado) {
        modalListaDia.hide();
        document.getElementById('modalListaDia').addEventListener('hidden.bs.modal', function handler() {
            abrirModalDetalhe({
                id:            ag.id,
                paciente:      ag.paciente,
                medico:        ag.medico,
                medicoId:      ag.medico_id,
                especialidade: ag.especialidade,
                status:        ag.status,
                data:          diaFormatado,
                dataIso:       ag.data_iso,
                horario:       ag.horario,
            });
            this.removeEventListener('hidden.bs.modal', handler);
        });
    }
</script>
</body>
</html>