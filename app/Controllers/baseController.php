<?php
/**
 * BaseController - Contrôleur de base avec authentification et autorisations
 */

class BaseController {
    protected $db;
    protected $user;
    protected $auth;
    protected $permission;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Initialiser le middleware d'authentification
        $this->auth = new AuthMiddleware();
        
        // Initialiser le middleware de permissions
        $this->permission = new PermissionMiddleware($this->auth);
        
        // Charger l'utilisateur
        $this->loadUser();
    }
    
    /**
     * Charge l'utilisateur depuis l'authentification
     */
    private function loadUser() {
        if (!$this->auth->isAuthenticated()) {
            // Vérifier si la requête est autorisée sans authentification
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isAllowedWithoutAuth = strpos($uri, '/login') !== false
                                 || strpos($uri, '/api/login') !== false
                                 || strpos($uri, '/css/') !== false
                                 || strpos($uri, '/js/') !== false
                                 || strpos($uri, '/img/') !== false
                                 || strpos($uri, '/fonts/') !== false;
            
            if (!$isAllowedWithoutAuth) {
                if ($this->isApiRequest()) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Non authentifié',
                        'redirect' => BASE_URL . 'login'
                    ]);
                    exit;
                }
                header('Location: ' . BASE_URL . 'login');
                exit;
            }
            return;
        }
        
        // Récupérer l'utilisateur depuis le middleware d'auth
        $this->user = $this->auth->getUser();
        
        // Valider l'intégrité de la session
        if (!$this->auth->validateSessionIntegrity()) {
            $this->sendError('Session invalide', 401);
        }
    }
    
    /**
     * Vérifie si c'est une requête API
     */
    protected function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($uri, '/api/') !== false
            || strpos($accept, 'application/json') !== false;
    }
    
    /**
     * Récupère l'utilisateur actuel
     */
    protected function getUser() {
        return $this->user;
    }
    
    /**
     * Vérifie si l'utilisateur a une permission
     */
    protected function hasPermission($permission) {
        return $this->permission->hasPermission($permission);
    }
    
    /**
     * Exige une permission (abort si non autorisé)
     */
    protected function requirePermission($permission) {
        $this->permission->require($permission);
    }
    
    /**
     * Exige plusieurs permissions (AND logique)
     */
    protected function requireAllPermissions($permissions) {
        if (!$this->permission->hasAllPermissions($permissions)) {
            $this->sendError('Accès non autorisé', 403);
        }
    }
    
    /**
     * Exige au moins une permission (OR logique)
     */
    protected function requireAnyPermission($permissions) {
        if (!$this->permission->hasAnyPermission($permissions)) {
            $this->sendError('Accès non autorisé', 403);
        }
    }
    
    /**
     * Exige un rôle spécifique
     */
    protected function requireRole($role) {
        $this->permission->requireRole($role);
    }
    
    /**
     * Exige l'authentification
     */
    protected function requireAuth() {
        $this->permission->requireAuth();
    }
    
    /**
     * Récupère les permissions de l'utilisateur
     */
    protected function getUserPermissions() {
        return $this->permission->getUserPermissions();
    }
    
    /**
     * Vérifie si l'utilisateur est ADMIN
     */
    protected function isAdmin() {
        return $this->auth->hasRole('ADMIN');
    }
    
    /**
     * Vérifie si l'utilisateur est EMPLOYE
     */
    protected function isEmploye() {
        return $this->auth->hasRole('EMPLOYE');
    }
    
    /**
     * Envoie une réponse JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Envoie une réponse de succès
     */
    protected function success($message = 'Succès', $data = null, $statusCode = 200) {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Envoie une réponse d'erreur
     */
    protected function sendError($message = 'Erreur', $statusCode = 400) {
        $this->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
    
    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonData() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (!$data) $data = $_POST;
        return $data ?? [];
    }
    
    /**
     * Récupère un paramètre GET
     */
    protected function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Génère un token CSRF
     */
    protected function generateCsrfToken() {
        return $this->auth->generateCsrfToken();
    }
    
    /**
     * Vérifie un token CSRF
     */
    protected function verifyCsrfToken($token) {
        return $this->auth->verifyCsrfToken($token);
    }
    
    /**
     * Récupère le token CSRF actuel (pour les formulaires)
     */
    protected function getCsrfToken() {
        return $this->auth->generateCsrfToken();
    }
}

?>
