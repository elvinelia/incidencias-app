<?php // utils/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Requiere que el usuario haya iniciado sesión.
 * Si no lo está, redirige a login.php (web) o devuelve JSON (API).
 */
function require_login() {
    if (!isset($_SESSION['usuario'])) {
        if (is_api_request()) {
            http_response_code(401);
            echo json_encode(["error" => "No autenticado"]);
            exit;
        }
        header("Location: /login.php");
        exit;
    }
}

/**
 * Requiere que el usuario tenga uno de los roles especificados.
 * Ejemplo: require_role(['validador', 'admin']);
 */
function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['usuario']['rol'], $roles)) {
        if (is_api_request()) {
            http_response_code(403);
            echo json_encode(["error" => "Acceso denegado"]);
            exit;
        }
        http_response_code(403);
        die("Acceso denegado: rol insuficiente.");
    }
}

/**
 * Detecta si la petición es API (acepta JSON).
 */
function is_api_request() {
    return strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}
