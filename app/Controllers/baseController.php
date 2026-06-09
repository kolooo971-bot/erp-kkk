<?php
class BaseController {
    protected $db;
    protected $user;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadUser();
    }

    private function loadUser() {
        if (!empty($_SESSION['user'])) {
            $this->user = $_SESSION['user'];
            return;
        }
        // Non connecté → rediriger
        $uri     = $_SERVER['REQUEST_URI'] ?? '';
        $isLogin = strpos($uri, '/login') !== false
                || strpos($uri, '/api/login') !== false
                || strpos($uri, '/css/')  !== false
                || strpos($uri, '/js/')   !== false
                || strpos($uri, '/img/')  !== false;

        if (!$isLogin) {
            if ($this->isApiRequest()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => false,
                    'message'  => 'Non authentifié',
                    'redirect' => BASE_URL . 'login'
                ]);
                exit;
            }
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    protected function isApiRequest() {
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($uri, '/api/') !== false
            || strpos($accept, 'application/json') !== false;
    }

    protected function requireAdmin() {
        if (($this->user['Role'] ?? '') !== 'ADMIN') {
            $this->sendError('Accès réservé aux administrateurs', 403);
        }
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function success($message = 'Succès', $data = null, $statusCode = 200) {
        $this->json(['success' => true, 'message' => $message, 'data' => $data], $statusCode);
    }

    protected function sendError($message = 'Erreur', $statusCode = 400) {
        $this->json(['success' => false, 'message' => $message], $statusCode);
    }

    protected function getJsonData() {
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);
        if (!$data) $data = $_POST;
        return $data ?? [];
    }

   protected function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    protected function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

}
?>