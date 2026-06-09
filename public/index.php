<?php
/**
 * Point d'entrée unique — sans authentification
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/init.php';
require_once ROOT_PATH . 'app/Controllers/baseController.php';

$request_uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// ── API ─────────────────────────────────────────────────────────────
if (strpos($request_uri, '/api/') !== false) {
    require_once ROOT_PATH . 'app/Routes/api.php';
    exit;
}

// ── URI nettoyée ────────────────────────────────────────────────────
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$uri       = trim(substr($request_uri, strlen($base_path)), '/');

// Redirections login/logout inutiles → dashboard
// Page racine → login
if ($uri === '') {
    header('Location: ' . BASE_URL . 'login');
    exit;
}
if ($uri === 'login') {
    require_once ROOT_PATH . 'app/Controllers/authController.php';
    require_once ROOT_PATH . 'app/Models/utilisateur.php';
    $c = new AuthController();
    $c->loginForm();
    exit;
}
if ($uri === 'logout') {
    require_once ROOT_PATH . 'app/Controllers/authController.php';
    $c = new AuthController();
    $c->logout();
    exit;
}

// ── Résolution contrôleur ───────────────────────────────────────────
$parts  = explode('/', $uri);
$page   = $parts[0] ?? 'dashboard';
$params = array_slice($parts, 1);

$map = [
    'dashboard'    => 'Dashboard',
    'clients'      => 'Client',
    'proformas'    => 'Proforma',
    'factures'     => 'Facture',
    'paiements'    => 'Paiement',
    'tresorerie'   => 'Tresorie',
    'depenses'     => 'Tresorie',  // → depensesPage()
    'utilisateurs' => 'Utilisateur',
    'parametres'   => 'Parametre',
    'entreprise'   => 'Parametre',
    'catalogue'    => 'Service',
    'livraisons'   => 'Livraison',
];

$controllerName  = $map[$page] ?? ucfirst($page);
$controllerClass = $controllerName . 'Controller';
// Pages qui utilisent une méthode spécifique au lieu de index()
$pageMethodOverrides = [
    'depenses' => 'depensesPage',
];
$controllerFile  = ROOT_PATH . 'app/Controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    $controllerClass = 'DashboardController';
    $controllerFile  = ROOT_PATH . 'app/Controllers/DashboardController.php';
}

require_once $controllerFile;

try {
    $controller = new $controllerClass();

    // Résoudre l'action
    // Méthode par défaut (éventuel override)
    $defaultAction = $pageMethodOverrides[$page] ?? 'index';

    if (empty($params)) {
        $action       = $defaultAction;
        $actionParams = [];
    } elseif (is_numeric($params[0])) {
        $sub          = $params[1] ?? null;
        $actionParams = [$params[0]];
        $action       = match($sub) {
            'edit'    => 'edit',
            'delete'  => 'delete',
            null      => 'show',
            default   => $sub
        };
    } else {
        $action       = $params[0];
        $actionParams = array_slice($params, 1);
    }

    if (method_exists($controller, $action)) {
        call_user_func_array([$controller, $action], $actionParams);
    } else {
        $controller->index();
    }

} catch (Throwable $e) {
    http_response_code(500);
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<h2>Erreur 500</h2><pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "<h2>Une erreur est survenue.</h2>";
    }
}
?>
