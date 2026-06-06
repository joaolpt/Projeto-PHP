<?php
/* ============================================================
   cancelar_agendamento.php
   Endpoint chamado via fetch() pelo principal.php para
   cancelar (status = 'Cancelado') um agendamento pelo id.

   Método esperado : POST
   Parâmetro       : id (int) — id do agendamento
   Retorno         : JSON  { "sucesso": true }
                        ou { "sucesso": false, "mensagem": "..." }
============================================================ */

header('Content-Type: application/json; charset=utf-8');

// Validação de sessão
session_start();
if (!isset($_SESSION['cod_usuario'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autorizado.']);
    exit;
}

// Validação do método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

// Validação do ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

// Conexão com o banco
require_once 'conexao.php';

// Cancelamento via exclusão lógica (atualiza status para 'Cancelado')
$stmt = $conexao_bd->prepare("UPDATE agendamentos SET status = 'Cancelado' WHERE id = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao preparar a query: ' . $conexao_bd->error]);
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Agendamento não encontrado ou já cancelado.']);
    $stmt->close();
    exit;
}

$stmt->close();

echo json_encode(['sucesso' => true]);