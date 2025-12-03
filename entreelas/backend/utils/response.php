<?php
/**
 * Funções auxiliares para padronização de respostas JSON
 */

/**
 * Envia resposta JSON de sucesso
 */
function sendSuccess($data = [], $message = 'Operação realizada com sucesso', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envia resposta JSON de erro
 */
function sendError($message = 'Erro ao processar requisição', $statusCode = 400, $errors = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Valida se todos os campos obrigatórios estão presentes
 */
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missingFields[] = $field;
        }
    }
    
    return $missingFields;
}

/**
 * Sanitiza string
 */
function sanitizeString($string) {
    return htmlspecialchars(strip_tags(trim($string)));
}

/**
 * Valida email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>