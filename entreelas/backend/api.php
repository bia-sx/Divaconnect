<?php
/**
 * API EntreElas - Ponto de entrada principal
 */

// Habilita CORS - Configuração específica para desenvolvimento
$allowed_origins = [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'http://127.0.0.1:5501',
    'http://localhost:5501',
    'http://localhost',
    'http://127.0.0.1'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Responde requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Imports
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuariaController.php';
require_once __DIR__ . '/controllers/ServicoController.php';
require_once __DIR__ . '/controllers/SolicitacaoController.php';
require_once __DIR__ . '/utils/response.php';

// Pega o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Pega a ação da query string
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Pega o body da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log para debug
error_log("Método: $method | Ação: $action");

try {
    // Roteamento
    switch ($action) {
        
        // ========== AUTENTICAÇÃO ==========
        case 'cadastro':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $authController = new AuthController();
            $authController->cadastro($data);
            break;

        case 'login':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $authController = new AuthController();
            $authController->login($data);
            break;

        case 'logout':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $authController = new AuthController();
            $authController->logout();
            break;

        case 'verificar-auth':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();
            sendSuccess(['usuario_id' => $usuarioId], 'Autenticado');
            break;

        // ========== PERFIL ==========
        case 'meu-perfil':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->meuPerfil();
            break;

        case 'atualizar-perfil':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->atualizarPerfil($data);
            break;

        case 'upload-foto':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->uploadFoto();
            break;

        case 'remover-foto':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->removerFoto();
            break;

        case 'alterar-senha':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->alterarSenha($data);
            break;

        case 'excluir-conta':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $usuariaController = new UsuariaController();
            $usuariaController->excluirConta();
            break;

        // ========== SERVIÇOS ==========
        case 'listar-servicos':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $servicoController = new ServicoController();
            $servicoController->listarServicos();
            break;

        case 'meus-servicos':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $servicoController = new ServicoController();
            $servicoController->meusServicos();
            break;

        case 'criar-servico':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $servicoController = new ServicoController();
            $servicoController->criarServico($data);
            break;

        case 'atualizar-servico':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $servicoController = new ServicoController();
            $servicoController->atualizarServico($data);
            break;

        case 'deletar-servico':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $servicoController = new ServicoController();
            $servicoController->deletarServico($data);
            break;

        // ========== SOLICITAÇÕES ==========
        case 'solicitar-servico':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $solicitacaoController = new SolicitacaoController();
            $solicitacaoController->solicitarServico($data);
            break;

        case 'minhas-solicitacoes':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $solicitacaoController = new SolicitacaoController();
            $solicitacaoController->minhasSolicitacoes();
            break;

        case 'solicitacoes-recebidas':
            if ($method !== 'GET') {
                sendError('Método não permitido', 405);
            }
            $solicitacaoController = new SolicitacaoController();
            $solicitacaoController->solicitacoesRecebidas();
            break;

        case 'responder-solicitacao':
            if ($method !== 'POST') {
                sendError('Método não permitido', 405);
            }
            $solicitacaoController = new SolicitacaoController();
            $solicitacaoController->responderSolicitacao($data);
            break;

        // ========== DEFAULT ==========
        default:
            sendError('Ação não encontrada', 404);
            break;
    }

} catch (Exception $e) {
    error_log("Erro na API: " . $e->getMessage());
    sendError('Erro interno do servidor', 500);
}
?>