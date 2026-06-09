<?php
/**
 * GUIDE D'UTILISATION DU SYSTÈME D'AUTHENTIFICATION ET PERMISSIONS
 * ============================================================
 * 
 * Ce guide explique comment utiliser le nouveau système d'authentification
 * sécurisé et complet dans votre ERP.
 */

// ============================================================
// 1. AUTHENTIFICATION DE BASE
// ============================================================

/**
 * VÉRIFIER SI L'UTILISATEUR EST CONNECTÉ
 */
class ExempleAuthentication extends BaseController {
    
    public function index() {
        // Vérifier l'authentification automatiquement (fait dans BaseController)
        
        // Récupérer l'utilisateur
        $user = $this->getUser();
        echo "Connecté en tant que: " . $user['Nom_Complet'];
    }
}

// ============================================================
// 2. PERMISSIONS ET RÔLES
// ============================================================

/**
 * VÉRIFIER LES PERMISSIONS
 */
class ExemplePermissions extends BaseController {
    
    public function creerFacture() {
        // Exiger une permission spécifique
        $this->requirePermission('factures.create');
        
        // Si on arrive ici, l'utilisateur a la permission
        // ...créer la facture...
    }
    
    public function supprimerFacture() {
        // Exiger plusieurs permissions (AND logique)
        $this->requireAllPermissions(['factures.view', 'factures.delete']);
    }
    
    public function afficherFactures() {
        // Vérifier si l'utilisateur a au moins une permission (OR logique)
        if (!$this->permission->hasAnyPermission(['factures.view', 'factures.edit'])) {
            $this->sendError('Accès non autorisé', 403);
        }
    }
}

/**
 * VÉRIFIER LES RÔLES
 */
class ExempleRoles extends BaseController {
    
    public function tableauBordAdmin() {
        // Exiger le rôle ADMIN
        $this->requireRole('ADMIN');
        
        // Accès garanti pour les ADMINs seulement
    }
    
    public function paramètres() {
        // Vérifier si c'est un admin
        if ($this->isAdmin()) {
            // Afficher tous les paramètres
        } else if ($this->isEmploye()) {
            // Afficher les paramètres limitées
        }
    }
}

// ============================================================
// 3. DANS LES CONTRÔLEURS (EXEMPLES COMPLETS)
// ============================================================

/**
 * EXEMPLE: ClientController
 */
class ClientControllerExample extends BaseController {
    
    /**
     * Lister les clients (toute personne connectée)
     */
    public function index() {
        // Authentification vérifiée automatiquement par BaseController
        $this->requirePermission('clients.view');
        
        $stmt = $this->db->prepare("SELECT * FROM CLIENTS ORDER BY Nom_Client");
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->success('Clients', $clients);
    }
    
    /**
     * Créer un client (permissions spécifiques)
     */
    public function store() {
        $this->requirePermission('clients.create');
        
        $data = $this->getJsonData();
        
        // Valider les données
        if (empty($data['Nom_Client'])) {
            $this->sendError('Le nom du client est requis');
        }
        
        // Créer le client
        $stmt = $this->db->prepare(
            "INSERT INTO CLIENTS (Nom_Client, Email, Telephone) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['Nom_Client'],
            $data['Email'] ?? '',
            $data['Telephone'] ?? ''
        ]);
        
        $this->success('Client créé', [
            'ID_Client' => $this->db->lastInsertId()
        ]);
    }
    
    /**
     * Modifier un client (ADMIN seulement)
     */
    public function update() {
        $this->requireRole('ADMIN');
        
        $id = $this->getQuery('id');
        $data = $this->getJsonData();
        
        $stmt = $this->db->prepare(
            "UPDATE CLIENTS SET Nom_Client = ? WHERE ID_Client = ?"
        );
        $stmt->execute([$data['Nom_Client'], $id]);
        
        $this->success('Client mis à jour');
    }
    
    /**
     * Supprimer un client (ADMIN seulement)
     */
    public function delete() {
        $this->requirePermission('clients.delete');
        
        $id = $this->getQuery('id');
        
        $stmt = $this->db->prepare("DELETE FROM CLIENTS WHERE ID_Client = ?");
        $stmt->execute([$id]);
        
        $this->success('Client supprimé');
    }
}

// ============================================================
// 4. DANS LES VUES (AFFICHAGE CONDITIONNEL)
// ============================================================

/**
 * EXEMPLE EN VUE PHP
 */
?>

<!-- Bouton créer client (si permission) -->
<?php if ($permission->hasPermission('clients.create')): ?>
    <button onclick="createClient()" class="btn btn-primary">
        Ajouter un client
    </button>
<?php endif; ?>

<!-- Modifier/Supprimer (si ADMIN) -->
<?php if ($auth->hasRole('ADMIN')): ?>
    <button onclick="editClient()" class="btn btn-warning">Modifier</button>
    <button onclick="deleteClient()" class="btn btn-danger">Supprimer</button>
<?php endif; ?>

<!-- Affichage conditionnel par rôle -->
<?php if ($auth->hasRole('ADMIN')): ?>
    <div class="admin-section">
        <!-- Options avancées pour les admins -->
    </div>
<?php else: ?>
    <div class="employee-section">
        <!-- Options limitées pour les employés -->
    </div>
<?php endif; ?>

<?php

// ============================================================
// 5. GESTION DES SESSIONS
// ============================================================

/**
 * La session est automatiquement gérée par AuthMiddleware
 */
class SessionExample extends BaseController {
    
    public function index() {
        // Vérifier le timeout (automatique dans BaseController)
        
        // Récupérer les données de session
        $user = $this->getUser();
        $userId = $user['ID_User'];
        $userRole = $user['Role'];
        
        // La session expire après 1 heure d'inactivité
        // L'utilisateur sera déconnecté automatiquement
    }
}

// ============================================================
// 6. SÉCURITÉ CSRF
// ============================================================

/**
 * Utiliser les tokens CSRF dans les formulaires
 */

/**
 * Dans le contrôleur (générer le token)
 */
class FormController extends BaseController {
    
    public function showForm() {
        $csrfToken = $this->getCsrfToken();
        
        $data = ['csrf_token' => $csrfToken];
        require_once ROOT_PATH . 'app/Views/form.php';
    }
    
    public function handleForm() {
        // Vérifier le token CSRF
        $token = $_POST['csrf_token'] ?? '';
        
        if (!$this->verifyCsrfToken($token)) {
            $this->sendError('Token CSRF invalide', 403);
        }
        
        // Traiter le formulaire en sécurité
    }
}

/**
 * Dans la vue (inclure le token)
 */
?>

<form method="POST" action="<?= BASE_URL ?>api/form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <input type="text" name="nom" required>
    <button type="submit">Envoyer</button>
</form>

<?php

// ============================================================
// 7. CHANGER LE MOT DE PASSE
// ============================================================

/**
 * Exemple de changement de mot de passe
 */
class ChangePasswordExample extends BaseController {
    
    public function changePassword() {
        $this->requireAuth();
        
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $result = $this->auth->changePassword(
            $this->user['ID_User'],
            $oldPassword,
            $newPassword
        );
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->sendError($result['message']);
        }
    }
}

// ============================================================
// 8. AJOUTER DYNAMIQUEMENT DES PERMISSIONS
// ============================================================

/**
 * Pour les administrateurs qui gèrent les permissions
 */
class AdminController extends BaseController {
    
    public function addPermission() {
        // Vérifier que c'est un admin
        $this->requireRole('ADMIN');
        
        $role = $_POST['role'] ?? '';
        $permission = $_POST['permission'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Ajouter la permission
        $this->permission->addPermission($role, $permission, $description);
        
        $this->success('Permission ajoutée');
    }
    
    public function removePermission() {
        $this->requireRole('ADMIN');
        
        $role = $_POST['role'] ?? '';
        $permission = $_POST['permission'] ?? '';
        
        // Supprimer la permission
        $this->permission->removePermission($role, $permission);
        
        $this->success('Permission supprimée');
    }
}

// ============================================================
// 9. LOGS D'AUTHENTIFICATION
// ============================================================

/**
 * Les logs d'authentification sont enregistrés automatiquement
 * Table: auth_logs
 * Contient:
 *  - Email
 *  - Succès (1/0)
 *  - IP_Address
 *  - User_Agent
 *  - Message
 *  - Date_Tentative
 */

// ============================================================
// 10. TABLE DE PERMISSIONS PAR DÉFAUT
// ============================================================

/**
 * Les permissions prédéfinies:
 * 
 * ADMIN:
 * - dashboard.view
 * - clients.view, clients.create, clients.edit, clients.delete
 * - factures.view, factures.create, factures.edit, factures.delete, factures.pdf
 * - proformas.view, proformas.create, proformas.edit, proformas.delete
 * - paiements.view, paiements.create, paiements.delete
 * - tresorerie.view, tresorerie.create
 * - utilisateurs.view, utilisateurs.create, utilisateurs.edit, utilisateurs.delete
 * - parametres.view, parametres.edit
 * 
 * EMPLOYE:
 * - dashboard.view
 * - clients.view, clients.create
 * - factures.view, factures.create, factures.pdf
 * - proformas.view, proformas.create
 * - paiements.view, paiements.create
 * - tresorerie.view (lecture seule)
 */

?>
