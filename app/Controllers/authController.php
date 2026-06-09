<?php
/**
 * AuthController - Gestion de l'authentification avec middleware
 */

class AuthController extends BaseController {
    
    public function loginForm() {
        // Si déjà connecté → rediriger au dashboard
        if ($this->auth->isAuthenticated()) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
        
        require_once ROOT_PATH . 'app/Views/auth/login.php';
    }
    
    /**
     * Traite la connexion via l'API
     */
    public function login() {
        // Si déjà connecté → rediriger
        if ($this->auth->isAuthenticated()) {
            $this->success('Déjà connecté', [
                'redirect' => BASE_URL . 'dashboard'
            ]);
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // Appeler le middleware d'authentification
        $result = $this->auth->login($email, $password);
        
        if (!$result['success']) {
            $this->sendError($result['message'], 401);
        }
        
        // Succès
        $this->success('Connecté avec succès', [
            'redirect' => BASE_URL . 'dashboard'
        ]);
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        $this->auth->logout();
        
        if ($this->isApiRequest()) {
            $this->success('Déconnecté');
        }
        
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
    
    /**
     * Affiche le formulaire de changement de mot de passe
     */
    public function changePasswordForm() {
        $this->requireAuth();
        
        $data = [
            'user' => $this->user,
            'pageTitle' => 'Changer le mot de passe'
        ];
        
        require_once ROOT_PATH . 'app/Views/auth/change-password.php';
    }
    
    /**
     * Traite le changement de mot de passe
     */
    public function changePassword() {
        $this->requireAuth();
        
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Valider les inputs
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->sendError('Tous les champs sont requis');
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->sendError('Les mots de passe ne correspondent pas');
        }
        
        if (strlen($newPassword) < 8) {
            $this->sendError('Le mot de passe doit faire au moins 8 caractères');
        }
        
        // Appeler le middleware pour changer le mot de passe
        $result = $this->auth->changePassword(
            $this->user['ID_User'],
            $oldPassword,
            $newPassword
        );
        
        if (!$result['success']) {
            $this->sendError($result['message']);
        }
        
        $this->success($result['message']);
    }
    
    /**
     * Affiche le profil utilisateur
     */
    public function profile() {
        $this->requireAuth();
        
        $data = [
            'user' => $this->user,
            'pageTitle' => 'Mon profil'
        ];
        
        require_once ROOT_PATH . 'app/Views/auth/profile.php';
    }
    
    /**
     * Met à jour le profil utilisateur
     */
    public function updateProfile() {
        $this->requireAuth();
        
        $data = $this->getJsonData();
        
        $nomComplet = trim($data['Nom_Complet'] ?? '');
        
        if (empty($nomComplet)) {
            $this->sendError('Le nom complet est requis');
        }
        
        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateurs 
                 SET Nom_Complet = ? 
                 WHERE ID_User = ?"
            );
            $stmt->execute([$nomComplet, $this->user['ID_User']]);
            
            // Mettre à jour la session
            $_SESSION['user']['Nom_Complet'] = $nomComplet;
            $this->user['Nom_Complet'] = $nomComplet;
            
            $this->success('Profil mis à jour avec succès');
        } catch (Exception $e) {
            error_log('Erreur mise à jour profil: ' . $e->getMessage());
            $this->sendError('Erreur lors de la mise à jour', 500);
        }
    }
    
    /**
     * Récupère les logs d'authentification de l'utilisateur
     */
    public function getAuthLogs() {
        $this->requireAuth();
        
        $limit = (int)($this->getQuery('limit') ?? 20);
        $limit = min($limit, 100); // Max 100
        
        try {
            $stmt = $this->db->prepare(
                "SELECT ID_Log, Succes, IP_Address, Message, Date_Tentative
                 FROM auth_logs
                 WHERE Email = ?
                 ORDER BY Date_Tentative DESC
                 LIMIT ?"
            );
            $stmt->execute([$this->user['Email'], $limit]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->success('Logs d\'authentification', $logs);
        } catch (Exception $e) {
            error_log('Erreur récupération logs: ' . $e->getMessage());
            $this->sendError('Erreur lors de la récupération des logs', 500);
        }
    }
}

?>
