# 🔐 Système d'Authentification et Permissions - ERP Kola

## 📋 Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Utilisation](#utilisation)
4. [Permissions disponibles](#permissions-disponibles)
5. [Exemples pratiques](#exemples-pratiques)
6. [Sécurité](#sécurité)

---

## 🎯 Vue d'ensemble

Le système d'authentification comporte 3 composants principaux :

### 1. **AuthMiddleware** (`app/Middleware/AuthMiddleware.php`)
- Gère les sessions utilisateur
- Authentification (login/logout)
- Hachage et vérification des mots de passe
- Génération et vérification des tokens CSRF
- Logs d'authentification
- Détection du timeout de session

### 2. **PermissionMiddleware** (`app/Middleware/PermissionMiddleware.php`)
- Gestion des rôles (ADMIN, EMPLOYE)
- Vérification des permissions
- Chargement dynamique des permissions depuis la BD

### 3. **BaseController** (modifié)
- Intègre les deux middlewares
- Fournit des méthodes helper pour les contrôleurs
- Vérifie automatiquement l'authentification

---

## 📦 Installation

### 1. Importer les tables SQL
```sql
-- Importer le fichier: database/auth_tables.sql
-- Contient:
--   - TABLE auth_logs (logs d'authentification)
--   - TABLE user_permissions (permissions par rôle)
```

### 2. Vérifier la table utilisateurs
```sql
-- La table utilisateurs doit avoir cette structure:
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS Derniere_Connexion DATETIME DEFAULT NULL;
```

### 3. Hasher les mots de passe existants
```php
// Pour chaque utilisateur en BD:
$hashedPassword = password_hash('motdepasse', PASSWORD_BCRYPT);
// UPDATE utilisateurs SET Mot_De_Passe = '$2y$10$...' WHERE ID_User = 1;
```

---

## 🚀 Utilisation

### A. Dans les Contrôleurs

#### Vérifier l'authentification (automatique)
```php
class ClientController extends BaseController {
    public function index() {
        // L'authentification est vérifiée automatiquement par BaseController
        // Si non connecté → redirection vers login
        
        $user = $this->getUser();
        echo "Connecté : " . $user['Nom_Complet'];
    }
}
```

#### Vérifier une permission
```php
class FactureController extends BaseController {
    public function store() {
        // Exiger une permission spécifique
        $this->requirePermission('factures.create');
        
        // Code du contrôleur...
    }
}
```

#### Vérifier un rôle
```php
class ParametreController extends BaseController {
    public function update() {
        // Exiger le rôle ADMIN
        $this->requireRole('ADMIN');
        
        // Code du contrôleur...
    }
}
```

#### Vérifier plusieurs permissions
```php
// ET logique (toutes les permissions requises)
$this->requireAllPermissions(['factures.view', 'factures.edit']);

// OU logique (au moins une permission)
$this->requireAnyPermission(['clients.view', 'clients.edit']);
```

#### Méthodes disponibles dans BaseController
```php
$this->getUser()                    // Récupère l'utilisateur
$this->hasPermission($perm)         // Vérifie une permission (bool)
$this->requirePermission($perm)     // Exige une permission (abort si non)
$this->requireRole($role)           // Exige un rôle (abort si non)
$this->isAdmin()                    // Vérifie si ADMIN (bool)
$this->isEmploye()                  // Vérifie si EMPLOYE (bool)
$this->getUserPermissions()         // Récupère toutes les permissions
$this->getCsrfToken()               // Génère un token CSRF
$this->verifyCsrfToken($token)      // Vérifie un token CSRF
```

### B. Dans les Vues

#### Affichage conditionnel
```php
<!-- Bouton si permission -->
<?php if ($permission->hasPermission('factures.create')): ?>
    <button onclick="createFacture()">Créer facture</button>
<?php endif; ?>

<!-- Section si ADMIN -->
<?php if ($auth->hasRole('ADMIN')): ?>
    <div class="admin-panel">
        <!-- Options admin -->
    </div>
<?php endif; ?>
```

#### Inclure le token CSRF dans les formulaires
```html
<form method="POST" action="<?= BASE_URL ?>api/client">
    <input type="hidden" name="csrf_token" 
           value="<?= htmlspecialchars($permission->getCsrfToken()) ?>">
    
    <input type="text" name="nom" required>
    <button type="submit">Envoyer</button>
</form>
```

---

## 🔑 Permissions disponibles

### Permissions ADMIN
```
✓ dashboard.view
✓ clients.view, clients.create, clients.edit, clients.delete
✓ factures.view, factures.create, factures.edit, factures.delete, factures.pdf
✓ proformas.view, proformas.create, proformas.edit, proformas.delete
✓ paiements.view, paiements.create, paiements.delete
✓ tresorerie.view, tresorerie.create
✓ utilisateurs.view, utilisateurs.create, utilisateurs.edit, utilisateurs.delete
✓ parametres.view, parametres.edit
```

### Permissions EMPLOYE
```
✓ dashboard.view
✓ clients.view, clients.create
✓ factures.view, factures.create, factures.pdf
✓ proformas.view, proformas.create
✓ paiements.view, paiements.create
✓ tresorerie.view (lecture seule)
```

### Ajouter dynamiquement une permission
```php
$this->permission->addPermission('ADMIN', 'rapports.export', 'Exporter les rapports');
```

---

## 💡 Exemples pratiques

### Exemple 1: Créer un contrôleur sécurisé

```php
<?php
class FactureController extends BaseController {
    
    /**
     * Lister les factures (toute personne connectée avec permission)
     */
    public function index() {
        $this->requirePermission('factures.view');
        
        $stmt = $this->db->prepare(
            "SELECT f.*, c.Nom_Client FROM FACTURES f
             LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
             ORDER BY f.Date_Emission DESC LIMIT 20"
        );
        $stmt->execute();
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->success('Factures', $factures);
    }
    
    /**
     * Créer une facture
     */
    public function store() {
        $this->requirePermission('factures.create');
        
        $data = $this->getJsonData();
        
        // Valider le token CSRF
        if (!$this->verifyCsrfToken($data['csrf_token'] ?? '')) {
            $this->sendError('Token CSRF invalide', 403);
        }
        
        // Créer la facture...
        $stmt = $this->db->prepare(
            "INSERT INTO FACTURES (ID_Client, Reference, Montant, Statut)
             VALUES (?, ?, ?, 'BROUILLON')"
        );
        $stmt->execute([
            $data['ID_Client'],
            'FAC-' . date('YmdHis'),
            $data['Montant']
        ]);
        
        $this->success('Facture créée', [
            'ID_Facture' => $this->db->lastInsertId()
        ]);
    }
    
    /**
     * Modifier une facture (ADMIN seulement)
     */
    public function update() {
        $this->requireRole('ADMIN');
        
        $id = $this->getQuery('id');
        $data = $this->getJsonData();
        
        $stmt = $this->db->prepare(
            "UPDATE FACTURES SET Montant = ? WHERE ID_Facture = ?"
        );
        $stmt->execute([$data['Montant'], $id]);
        
        $this->success('Facture mise à jour');
    }
    
    /**
     * Supprimer une facture (ADMIN seulement)
     */
    public function delete() {
        $this->requirePermission('factures.delete');
        
        $id = $this->getQuery('id');
        
        $stmt = $this->db->prepare("DELETE FROM FACTURES WHERE ID_Facture = ?");
        $stmt->execute([$id]);
        
        $this->success('Facture supprimée');
    }
}
?>
```

### Exemple 2: Formulaire de login
```php
<!-- public/login.php -->
<?php
// Si déjà connecté
if ($auth->isAuthenticated()) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
}
?>

<form method="POST" action="<?= BASE_URL ?>api/login">
    <h2>Connexion ERP</h2>
    
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
    </div>
    
    <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
    </div>
    
    <button type="submit">Se connecter</button>
</form>

<?php if (isset($_GET['expired'])): ?>
    <div class="alert alert-warning">
        Votre session a expiré. Reconnectez-vous.
    </div>
<?php endif; ?>
```

---

## 🔒 Sécurité

### Fonctionnalités de sécurité incluses

| Fonctionnalité | Description |
|---|---|
| **Hash BCRYPT** | Les mots de passe sont hashés avec PASSWORD_BCRYPT |
| **Token CSRF** | Protection contre les attaques cross-site request forgery |
| **Session timeout** | Les sessions expirent après 1 heure d'inactivité |
| **Session validation** | Vérification de l'IP et du User-Agent |
| **Logs d'authentification** | Enregistrement de toutes les tentatives (réussies et échouées) |
| **Regenerate session ID** | L'ID de session est régénéré à chaque login |
| **HttpOnly cookies** | Les cookies de session ne sont pas accessibles en JavaScript |
| **SQL prepared statements** | Protection contre les injections SQL |

### Checklist de sécurité

```php
// ✓ Toujours exiger l'authentification dans les contrôleurs sensibles
$this->requireAuth();

// ✓ Toujours vérifier les permissions
$this->requirePermission('action.type');

// ✓ Toujours vérifier le token CSRF dans les formulaires
if (!$this->verifyCsrfToken($token)) { ... }

// ✓ Toujours utiliser prepared statements (fait dans les Models)
$stmt = $this->db->prepare("SELECT * FROM table WHERE id = ?");

// ✓ Toujours valider et nettoyer les inputs
$email = trim($_POST['email'] ?? '');

// ✓ Toujours encoder les outputs pour éviter XSS
echo htmlspecialchars($user['Nom_Complet']);
```

---

## 🐛 Troubleshooting

### "Non authentifié"
- Vérifier que vous êtes connecté
- Vérifier que la session n'a pas expiré (1 heure)
- Vérifier les cookies du navigateur

### "Accès non autorisé"
- Vérifier que l'utilisateur a la permission requise
- Vérifier le rôle de l'utilisateur dans la BD
- Vérifier les permissions dans la table `user_permissions`

### "Token CSRF invalide"
- Vérifier que le token est inclus dans le formulaire
- Vérifier que le token n'est pas corrompu lors du transport
- Régénérer le token si nécessaire

---

## 📞 Support

Pour toute question sur le système d'authentification:
- Consultez le fichier `AUTHENTICATION_GUIDE.php`
- Vérifiez les logs dans `auth_logs`
- Contactez l'administrateur système
