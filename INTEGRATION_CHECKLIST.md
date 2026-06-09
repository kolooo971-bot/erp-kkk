# ✅ CHECKLIST D'INTÉGRATION - AUTHENTIFICATION

## 🎯 Objectif
Intégrer le nouveau système d'authentification dans les contrôleurs existants.

---

## 📋 Étapes d'intégration

### Phase 1: Préparation (à faire une fois)

- [ ] **1.1** Importer `database/auth_tables.sql` dans la BD
- [ ] **1.2** Vérifier que la colonne `Derniere_Connexion` existe dans `utilisateurs`
- [ ] **1.3** Hasher les mots de passe existants en BCRYPT
  ```php
  // Script temporaire
  $users = $this->db->query("SELECT * FROM utilisateurs WHERE Mot_De_Passe NOT LIKE '$2y$%'");
  foreach ($users as $user) {
      $hashed = password_hash($user['Mot_De_Passe'], PASSWORD_BCRYPT);
      // UPDATE utilisateurs SET Mot_De_Passe = ? WHERE ID_User = ?
  }
  ```
- [ ] **1.4** Vérifier que les permissions sont bien insérées dans `user_permissions`
  ```sql
  SELECT COUNT(*) FROM user_permissions;
  -- Doit retourner > 25 permissions
  ```

---

### Phase 2: Mettre à jour les contrôleurs

Pour chaque contrôleur dans `app/Controllers/`, faire les modifications suivantes :

#### Template de modification

```php
// AVANT
class MonController {
    protected $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function action() {
        // Pas de vérification d'authentification !
    }
}

// APRÈS
class MonController extends BaseController {
    // Hérite de BaseController qui gère l'auth
    
    public function action() {
        $this->requirePermission('permission.action');
        // L'authentification est vérifiée automatiquement
    }
}
```

---

### Contrôleurs à mettre à jour

#### ✅ ClientController
```php
class ClientController extends BaseController {
    
    public function index() {
        $this->requirePermission('clients.view');
        // ...
    }
    
    public function store() {
        $this->requirePermission('clients.create');
        // ...
    }
    
    public function update() {
        $this->requirePermission('clients.edit');
        // ...
    }
    
    public function delete() {
        $this->requirePermission('clients.delete');
        // ...
    }
}
```

#### ✅ FactureController
```php
class FactureController extends BaseController {
    
    public function index() {
        $this->requirePermission('factures.view');
        // ...
    }
    
    public function store() {
        $this->requirePermission('factures.create');
        // ...
    }
    
    public function show() {
        $this->requirePermission('factures.view');
        // ...
    }
    
    public function update() {
        $this->requirePermission('factures.edit');
        // ...
    }
    
    public function delete() {
        $this->requirePermission('factures.delete');
        // ...
    }
    
    public function generatePDF() {
        $this->requirePermission('factures.pdf');
        // ...
    }
}
```

#### ✅ ProformaController
```php
class ProformaController extends BaseController {
    
    public function index() {
        $this->requirePermission('proformas.view');
    }
    
    public function store() {
        $this->requirePermission('proformas.create');
    }
    
    public function update() {
        $this->requirePermission('proformas.edit');
    }
    
    public function delete() {
        $this->requirePermission('proformas.delete');
    }
}
```

#### ✅ PaiementController
```php
class PaiementController extends BaseController {
    
    public function index() {
        $this->requirePermission('paiements.view');
    }
    
    public function store() {
        $this->requirePermission('paiements.create');
    }
    
    public function delete() {
        $this->requirePermission('paiements.delete');
    }
}
```

#### ✅ TresorieController (Trésorerie)
```php
class TresorieController extends BaseController {
    
    public function index() {
        $this->requirePermission('tresorerie.view');
    }
    
    public function depensesPage() {
        $this->requirePermission('tresorerie.view');
    }
    
    public function storeDepense() {
        $this->requirePermission('tresorerie.create');
    }
}
```

#### ✅ UtilisateurController
```php
class UtilisateurController extends BaseController {
    
    public function index() {
        $this->requireRole('ADMIN');
    }
    
    public function store() {
        $this->requireRole('ADMIN');
        // Créer un utilisateur
    }
    
    public function update() {
        $this->requireRole('ADMIN');
    }
    
    public function delete() {
        $this->requireRole('ADMIN');
    }
}
```

#### ✅ ParametreController
```php
class ParametreController extends BaseController {
    
    public function index() {
        $this->requirePermission('parametres.view');
    }
    
    public function update() {
        $this->requirePermission('parametres.edit');
        // Vérifier le token CSRF
        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->sendError('Token CSRF invalide', 403);
        }
    }
}
```

#### ✅ DashboardController
```php
class DashboardController extends BaseController {
    
    public function index() {
        $this->requirePermission('dashboard.view');
        // L'authentification est vérifiée automatiquement
        
        // Afficher des données différentes selon le rôle
        if ($this->isAdmin()) {
            // Tableau de bord admin (tous les chiffres)
        } else if ($this->isEmploye()) {
            // Tableau de bord employé (données limitées)
        }
    }
}
```

---

### Phase 3: Mettre à jour les vues

#### Ajouter des éléments conditionnels

```php
<!-- Dans les vues, utiliser les permissions -->

<?php if ($permission->hasPermission('clients.create')): ?>
    <button onclick="createClient()" class="btn btn-primary">
        Ajouter un client
    </button>
<?php endif; ?>

<?php if ($auth->hasRole('ADMIN')): ?>
    <button onclick="editClient()" class="btn btn-warning">
        Modifier
    </button>
<?php endif; ?>

<?php if ($auth->hasRole('ADMIN')): ?>
    <button onclick="deleteClient()" class="btn btn-danger">
        Supprimer
    </button>
<?php endif; ?>
```

#### Inclure le token CSRF dans les formulaires

```html
<form method="POST" action="<?= BASE_URL ?>api/resource">
    <input type="hidden" name="csrf_token" 
           value="<?= htmlspecialchars($permission->getCsrfToken()) ?>">
    
    <!-- Autres champs du formulaire -->
</form>
```

---

### Phase 4: Tester

#### Tests d'authentification
- [ ] **4.1** Se connecter avec admin@kola.com
- [ ] **4.2** Accéder aux pages admin
- [ ] **4.3** Se connecter avec kola@admin.com
- [ ] **4.4** Vérifier que les pages admin sont bloquées
- [ ] **4.5** Vérifier que seules les actions permises sont disponibles

#### Tests de permissions
- [ ] **4.6** Créer un client (EMPLOYE)
- [ ] **4.7** Modifier un client (doit être bloqué)
- [ ] **4.8** Supprimer un client (doit être bloqué)
- [ ] **4.9** Créer une facture (EMPLOYE)
- [ ] **4.10** Accéder aux paramètres (doit être bloqué)

#### Tests de sécurité
- [ ] **4.11** Vérifier les logs d'authentification
  ```sql
  SELECT * FROM auth_logs ORDER BY Date_Tentative DESC LIMIT 10;
  ```
- [ ] **4.12** Tester le timeout de session (1 heure)
- [ ] **4.13** Vérifier que les tokens CSRF sont vérifiés
- [ ] **4.14** Vérifier que les mots de passe sont bien hashés

---

## 🔧 Problèmes courants et solutions

### Problème: "Class 'AuthMiddleware' not found"
**Solution:** Vérifier que les fichiers middleware sont bien en place
```bash
ls -la app/Middleware/
# Doit contenir: AuthMiddleware.php, PermissionMiddleware.php
```

### Problème: "Accès non autorisé" pour tout le monde
**Solution:** Vérifier que les permissions sont bien insérées
```sql
SELECT * FROM user_permissions LIMIT 10;
```

### Problème: "Token CSRF invalide"
**Solution:** Vérifier que le token est bien inclus dans le formulaire
```php
// Dans le contrôleur
$token = $_POST['csrf_token'] ?? '';
if (!$this->verifyCsrfToken($token)) {
    error_log("Token invalide: " . $token);
}
```

### Problème: Session expire trop vite
**Solution:** Augmenter le timeout dans `app/Middleware/AuthMiddleware.php`
```php
const SESSION_TIMEOUT = 7200; // 2 heures au lieu de 1
```

---

## 📊 Ordre d'intégration recommandé

1. **Étape 1:** AuthController (déjà mis à jour)
2. **Étape 2:** DashboardController
3. **Étape 3:** ClientController
4. **Étape 4:** FactureController
5. **Étape 5:** ProformaController
6. **Étape 6:** PaiementController
7. **Étape 7:** TresorieController
8. **Étape 8:** UtilisateurController
9. **Étape 9:** ParametreController

---

## ✨ Checklist finale

- [ ] Tous les contrôleurs étendent BaseController
- [ ] Chaque action a une vérification de permission/rôle
- [ ] Les formulaires incluent le token CSRF
- [ ] Les vues affichent les boutons de façon conditionnelle
- [ ] Les logs d'authentification sont consultables
- [ ] Les tests sont passés
- [ ] La documentation est à jour

---

**Status:** 🚀 Prêt pour la mise en production

