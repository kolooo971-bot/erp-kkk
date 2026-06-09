# 🚀 GUIDE DE DÉMARRAGE RAPIDE - Authentification

## ⚡ 5 minutes pour mettre en place

### Étape 1: Importer les tables SQL (1 min)

```sql
-- Exécuter dans phpMyAdmin ou MySQL
-- Importer: database/auth_tables.sql

-- Vérifie que les tables ont été créées:
SHOW TABLES LIKE 'auth%';
SHOW TABLES LIKE 'user_permissions';
```

### Étape 2: Vérifier les mots de passe (1 min)

```sql
-- Vérifier que admin a un mot de passe hashé
SELECT ID_User, Email, Mot_De_Passe FROM utilisateurs;

-- Si le mot de passe n'est pas hashé (commence pas par $2y$):
-- Le hasher avec:
UPDATE utilisateurs 
SET Mot_De_Passe = '$2y$10$CMtIsH7yKlmVVd36n3IxiOg2KlCQglkvMQRWvu.IBRtQxmEJysDmG'
WHERE ID_User = 1;
-- C'est le hash de "admin"
```

### Étape 3: Tester la connexion (1 min)

```
1. Accéder à: http://localhost/erp-kola/public/login
2. Se connecter avec:
   - Email: admin@kola.com
   - Password: admin  (ou votre mot de passe)
3. Cliquer sur "Se connecter"
4. Vérifier l'accès au dashboard
```

### Étape 4: Vérifier les logs (1 min)

```sql
-- Vérifier que la connexion a été enregistrée
SELECT * FROM auth_logs ORDER BY Date_Tentative DESC LIMIT 5;
```

### Étape 5: Tester les permissions (1 min)

```
1. Se connecter avec kola@admin.com (EMPLOYE)
2. Vérifier que:
   - Peut voir les clients ✓
   - Peut voir les factures ✓
   - CANNOT supprimer les clients ✗
   - CANNOT accéder aux paramètres ✗
```

---

## 🔑 Données de test

### Utilisateurs prédéfinis

```sql
-- Admin
Email: admin@kola.com
Password: admin
Role: ADMIN
Accès: Complet

-- Employé
Email: kola@admin.com
Password: $2y$10$CMtIsH7yKlmVVd36n3IxiOg2KlCQglkvMQRWvu.IBRtQxmEJysDmG
Role: EMPLOYE
Accès: Limité
```

---

## 📁 Fichiers créés/modifiés

### ✅ Middleware
```
app/Middleware/
  ├── AuthMiddleware.php          (authentification + sessions)
  └── PermissionMiddleware.php    (rôles + permissions)
```

### ✅ Contrôleurs
```
app/Controllers/
  ├── baseController.php          (intègre les middlewares)
  └── authController.php          (login/logout/profile)
```

### ✅ Base de données
```
database/
  └── auth_tables.sql             (tables auth_logs, user_permissions)
```

### ✅ Documentation
```
├── AUTHENTICATION.md              (guide complet)
├── AUTHENTICATION_GUIDE.php       (code examples)
├── INTEGRATION_CHECKLIST.md       (checklist d'intégration)
└── QUICK_START.md                (ce fichier)
```

---

## 🛠️ Commandes utiles

### Vérifier l'installation

```bash
# Vérifier que les fichiers existent
ls -la app/Middleware/Auth*.php
ls -la database/auth_tables.sql

# Vérifier que les tables existent (via MySQL)
mysql -u root erp_kola -e "SELECT COUNT(*) as logs FROM auth_logs; SELECT COUNT(*) as perms FROM user_permissions;"
```

### Générer un mot de passe hashé

```php
<?php
$password = "monmotdepasse";
$hashed = password_hash($password, PASSWORD_BCRYPT);
echo $hashed;
// Copier la valeur et l'insérer dans la BD
?>
```

### Réinitialiser un mot de passe

```sql
-- Remplacer 1 par l'ID de l'utilisateur
UPDATE utilisateurs 
SET Mot_De_Passe = '$2y$10$CMtIsH7yKlmVVd36n3IxiOg2KlCQglkvMQRWvu.IBRtQxmEJysDmG'
WHERE ID_User = 1;
-- Mot de passe = "admin"
```

### Consulter les logs d'authentification

```sql
-- Derniers 20 logs
SELECT * FROM auth_logs ORDER BY Date_Tentative DESC LIMIT 20;

-- Logs échoués
SELECT * FROM auth_logs WHERE Succes = 0 ORDER BY Date_Tentative DESC;

-- Logs d'un utilisateur spécifique
SELECT * FROM auth_logs WHERE Email = 'admin@kola.com' ORDER BY Date_Tentative DESC;
```

### Ajouter une permission

```sql
-- Pour l'admin
INSERT INTO user_permissions (Role, Permission, Description)
VALUES ('ADMIN', 'rapports.export', 'Exporter les rapports');

-- Pour l'employé
INSERT INTO user_permissions (Role, Permission, Description)
VALUES ('EMPLOYE', 'rapports.view', 'Voir les rapports');
```

---

## ⚠️ Problèmes courants

### "Identifiants incorrects"
```
✓ Vérifier que l'utilisateur existe
✓ Vérifier que le rôle Actif = 1
✓ Vérifier que le mot de passe est correct (voir hasher ci-dessus)
✓ Consulter les logs: SELECT * FROM auth_logs WHERE Succes = 0;
```

### "Accès non autorisé"
```
✓ Vérifier la permission requise dans le contrôleur
✓ Vérifier que l'utilisateur a la permission
✓ Consulter: SELECT * FROM user_permissions WHERE Role = 'ADMIN';
```

### "Session expirée"
```
✓ Cela est normal après 1 heure d'inactivité
✓ Pour réduire à 30 min: SESSION_TIMEOUT = 1800 dans AuthMiddleware.php
✓ Pour augmenter à 2h: SESSION_TIMEOUT = 7200
```

---

## 🔒 Sécurité

### Checklist de sécurité

- [ ] Les mots de passe sont hashés en BCRYPT
- [ ] Les sessions expirent après 1 heure
- [ ] Les tokens CSRF sont vérifiés dans les formulaires
- [ ] Les logs d'authentification sont enregistrés
- [ ] Les prepared statements sont utilisés (pas de SQL injection)
- [ ] Les outputs sont échappés (pas de XSS)
- [ ] Les IPs et User-Agents sont vérifiés
- [ ] Les mots de passe par défaut doivent être changés

---

## 📞 Support

### Questions fréquentes

**Q: Comment changer le mot de passe d'un utilisateur?**
- Répondre: Voir "Réinitialiser un mot de passe" ci-dessus

**Q: Comment ajouter une nouvelle permission?**
- Répondre: Voir "Ajouter une permission" ci-dessus

**Q: Comment créer un nouvel utilisateur?**
- Via l'admin panel (UtilisateurController)
- Ou avec: `INSERT INTO utilisateurs (...) VALUES (...)`

**Q: Où sont stockés les logs?**
- Table: `auth_logs`
- Consultables via: phpmyAdmin ou MySQL

**Q: Est-ce compatible avec mon vieux code?**
- Oui! BaseController reste compatible
- Les anciens contrôleurs doivent juste étendre BaseController

---

## 🎓 Prochaines étapes

1. **Étape 1:** Importer les tables SQL ✅
2. **Étape 2:** Tester la connexion ✅
3. **Étape 3:** Mettre à jour les contrôleurs (voir INTEGRATION_CHECKLIST.md)
4. **Étape 4:** Mettre à jour les vues
5. **Étape 5:** Tester toutes les permissions
6. **Étape 6:** Déployer en production

---

**Estimé:** 30-60 minutes pour l'intégration complète

**Support:** Consultez AUTHENTICATION.md pour plus de détails
