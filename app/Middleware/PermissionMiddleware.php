<?php
/**
 * PermissionMiddleware - Gestion des permissions et des rôles
 */

class PermissionMiddleware {
    
    private $db;
    private $auth;
    private $permissions = [];
    
    public function __construct(AuthMiddleware $auth) {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = $auth;
        $this->loadPermissions();
    }
    
    /**
     * Charge toutes les permissions de la BD
     */
    private function loadPermissions() {
        try {
            $stmt = $this->db->prepare(
                "SELECT Role, Permission FROM user_permissions"
            );
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($result as $row) {
                $role = $row['Role'];
                $perm = $row['Permission'];
                
                if (!isset($this->permissions[$role])) {
                    $this->permissions[$role] = [];
                }
                
                $this->permissions[$role][] = $perm;
            }
        } catch (Exception $e) {
            error_log('Erreur chargement permissions: ' . $e->getMessage());
        }
    }
    
    /**
     * Vérifie si l'utilisateur a une permission
     */
    public function hasPermission($permission) {
        if (!$this->auth->isAuthenticated()) {
            return false;
        }
        
        $role = $this->auth->getUserRole();
        
        if (!isset($this->permissions[$role])) {
            return false;
        }
        
        return in_array($permission, $this->permissions[$role]);
    }
    
    /**
     * Vérifie plusieurs permissions (ET logique)
     */
    public function hasAllPermissions($permissions) {
        foreach ((array)$permissions as $perm) {
            if (!$this->hasPermission($perm)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Vérifie plusieurs permissions (OU logique)
     */
    public function hasAnyPermission($permissions) {
        foreach ((array)$permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Récupère toutes les permissions de l'utilisateur
     */
    public function getUserPermissions() {
        if (!$this->auth->isAuthenticated()) {
            return [];
        }
        
        $role = $this->auth->getUserRole();
        return $this->permissions[$role] ?? [];
    }
    
    /**
     * Récupère les permissions par rôle
     */
    public function getPermissionsByRole($role) {
        return $this->permissions[$role] ?? [];
    }
    
    /**
     * Exige une permission (abort si non autorisé)
     */
    public function require($permission) {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Accès non autorisé',
                'required_permission' => $permission
            ]);
            exit;
        }
    }
    
    /**
     * Exige un rôle spécifique
     */
    public function requireRole($role) {
        if (!$this->auth->hasRole($role)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Rôle insuffisant',
                'required_role' => $role
            ]);
            exit;
        }
    }
    
    /**
     * Exige l'authentification
     */
    public function requireAuth() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Authentification requise'
            ]);
            exit;
        }
    }
    
    /**
     * Ajoute une permission pour un rôle
     */
    public function addPermission($role, $permission, $description = '') {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO user_permissions (Role, Permission, Description) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE Description = VALUES(Description)"
            );
            $stmt->execute([$role, $permission, $description]);
            
            // Recharger les permissions
            $this->loadPermissions();
            
            return true;
        } catch (Exception $e) {
            error_log('Erreur ajout permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime une permission pour un rôle
     */
    public function removePermission($role, $permission) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM user_permissions WHERE Role = ? AND Permission = ?"
            );
            $stmt->execute([$role, $permission]);
            
            // Recharger les permissions
            $this->loadPermissions();
            
            return true;
        } catch (Exception $e) {
            error_log('Erreur suppression permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère toutes les permissions disponibles (pour admin)
     */
    public function getAllPermissions() {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT Permission, Description FROM user_permissions ORDER BY Permission"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur récupération permissions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Réinitialise les permissions d'un rôle à sa configuration par défaut
     */
    public function resetRolePermissions($role) {
        // Cette méthode dépend de votre logique métier
        // À implémenter selon vos besoins
    }
}

?>
