<?php
/**
 * AuthMiddleware - Gestion centralisée de l'authentification et des sessions
 */

class AuthMiddleware {
    
    // Constantes
    const SESSION_KEY = 'user';
    const SESSION_TIMEOUT = 3600; // 1 heure en secondes
    const TOKEN_KEY = 'csrf_token';
    
    private $db;
    private $user = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initSession();
    }
    
    /**
     * Initialise la session de manière sécurisée
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Configuration de sécurité pour les sessions
            ini_set('session.cookie_httponly', 1);      // Empêcher l'accès JS
            ini_set('session.use_strict_mode', 1);       // Rejeter les sessions inconnues
            ini_set('session.cookie_secure', 1);         // HTTPS uniquement (en prod)
            ini_set('session.cookie_samesite', 'Strict'); // Protection CSRF
        }
        
        // Vérifier le timeout de la session
        $this->checkSessionTimeout();
        
        // Charger l'utilisateur de la session
        if ($this->isAuthenticated()) {
            $this->loadUserFromSession();
        }
    }
    
    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public function isAuthenticated() {
        return !empty($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY]);
    }
    
    /**
     * Charge l'utilisateur depuis la session
     */
    private function loadUserFromSession() {
        $userSession = $_SESSION[self::SESSION_KEY];
        
        // Vérifier que l'utilisateur existe toujours en BD
        $stmt = $this->db->prepare(
            "SELECT ID_User, Nom_Complet, Email, Role, Actif 
             FROM utilisateurs 
             WHERE ID_User = ? AND Actif = 1"
        );
        $stmt->execute([$userSession['ID_User']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $this->user = $userData;
        } else {
            // Utilisateur n'existe plus ou est inactif → déconnecter
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Récupère l'utilisateur courant
     */
    public function getUser() {
        return $this->user;
    }
    
    /**
     * Récupère l'ID de l'utilisateur
     */
    public function getUserId() {
        return $this->user['ID_User'] ?? null;
    }
    
    /**
     * Récupère le rôle de l'utilisateur
     */
    public function getUserRole() {
        return $this->user['Role'] ?? null;
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole($role) {
        if (!$this->isAuthenticated()) return false;
        
        // Support des rôles multiples: hasRole(['ADMIN', 'MANAGER'])
        if (is_array($role)) {
            return in_array($this->user['Role'], $role);
        }
        
        return $this->user['Role'] === $role;
    }
    
    /**
     * Vérifie le timeout de session
     */
    private function checkSessionTimeout() {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed > self::SESSION_TIMEOUT) {
            // Session expirée
            $this->logout();
            header('Location: ' . BASE_URL . 'login?expired=1');
            exit;
        }
        
        // Mettre à jour le timestamp
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Authentifie un utilisateur
     */
    public function login($email, $password) {
        $email = trim($email);
        
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email et mot de passe requis'
            ];
        }
        
        // Chercher l'utilisateur
        $stmt = $this->db->prepare(
            "SELECT ID_User, Nom_Complet, Email, Mot_De_Passe, Role, Actif 
             FROM utilisateurs 
             WHERE Email = ? AND Actif = 1 
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Enregistrer la tentative échouée
            $this->logAuthAttempt($email, false, 'Utilisateur non trouvé');
            return [
                'success' => false,
                'message' => 'Identifiants incorrects'
            ];
        }
        
        // Vérifier le mot de passe
        if (!password_verify($password, $user['Mot_De_Passe'])) {
            $this->logAuthAttempt($email, false, 'Mot de passe incorrect');
            return [
                'success' => false,
                'message' => 'Identifiants incorrects'
            ];
        }
        
        // Régénérer l'ID de session (sécurité CSRF)
        session_regenerate_id(true);
        
        // Créer la session utilisateur
        $_SESSION[self::SESSION_KEY] = [
            'ID_User'     => $user['ID_User'],
            'Nom_Complet' => $user['Nom_Complet'],
            'Email'       => $user['Email'],
            'Role'        => $user['Role'],
            'Actif'       => $user['Actif'],
            'login_time'  => time()
        ];
        
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Mettre à jour la dernière connexion en BD
        $this->updateLastLogin($user['ID_User']);
        
        // Enregistrer la connexion réussie
        $this->logAuthAttempt($email, true, 'Connexion réussie');
        
        // Charger l'utilisateur dans la propriété
        $this->loadUserFromSession();
        
        return [
            'success' => true,
            'message' => 'Connexion réussie'
        ];
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public function logout() {
        // Enregistrer la déconnexion
        if ($this->isAuthenticated()) {
            $this->logAuthAttempt($_SESSION[self::SESSION_KEY]['Email'], true, 'Déconnexion');
        }
        
        session_unset();
        session_destroy();
        
        // Régénérer l'ID de session
        session_start();
        session_regenerate_id(true);
        session_destroy();
    }
    
    /**
     * Met à jour la dernière connexion
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare(
            "UPDATE utilisateurs 
             SET Derniere_Connexion = NOW() 
             WHERE ID_User = ?"
        );
        $stmt->execute([$userId]);
    }
    
    /**
     * Enregistre une tentative d'authentification
     */
    private function logAuthAttempt($email, $success, $message) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO auth_logs (Email, Succes, IP_Address, User_Agent, Message, Date_Tentative) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $email,
                $success ? 1 : 0,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $message
            ]);
        } catch (Exception $e) {
            error_log('Erreur enregistrement auth_logs: ' . $e->getMessage());
        }
    }
    
    /**
     * Génère un token CSRF
     */
    public function generateCsrfToken() {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public function verifyCsrfToken($token) {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }
    
    /**
     * Change le mot de passe de l'utilisateur
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        if (empty($newPassword) || strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Le mot de passe doit faire au moins 8 caractères'
            ];
        }
        
        // Récupérer l'utilisateur
        $stmt = $this->db->prepare("SELECT Mot_De_Passe FROM utilisateurs WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Vérifier l'ancien mot de passe
        if (!password_verify($oldPassword, $user['Mot_De_Passe'])) {
            return ['success' => false, 'message' => 'Ancien mot de passe incorrect'];
        }
        
        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Mettre à jour
        $stmt = $this->db->prepare(
            "UPDATE utilisateurs SET Mot_De_Passe = ? WHERE ID_User = ?"
        );
        $stmt->execute([$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
    }
    
    /**
     * Valide l'IP et l'User-Agent (protection contre le hijacking de session)
     */
    public function validateSessionIntegrity() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Vérifier l'IP
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            error_log("Session hijacking attempt: IP mismatch for user " . $this->user['ID_User']);
            $this->logout();
            return false;
        }
        
        // Vérifier l'User-Agent (moins strict, peut changer)
        // if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        //     $this->logout();
        //     return false;
        // }
        
        return true;
    }
}

?>
