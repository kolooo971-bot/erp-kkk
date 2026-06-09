<?php
/**
 * SCRIPT D'INSTALLATION - À exécuter une seule fois
 * 
 * Ce script prépare la base de données pour le nouveau système d'authentification
 */

// Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'erp_kola';

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connecté à la base de données\n";
    
    // ============================================================
    // 1. Créer les tables d'authentification
    // ============================================================
    
    echo "\n[1/3] Création des tables...\n";
    
    // Table auth_logs
    $pdo->exec("
        DROP TABLE IF EXISTS `auth_logs`;
        CREATE TABLE IF NOT EXISTS `auth_logs` (
          `ID_Log` int(11) NOT NULL AUTO_INCREMENT,
          `Email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
          `Succes` tinyint(1) DEFAULT '0',
          `IP_Address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `User_Agent` text COLLATE utf8mb4_unicode_ci,
          `Message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `Date_Tentative` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`ID_Log`),
          KEY `idx_email` (`Email`),
          KEY `idx_date` (`Date_Tentative`),
          KEY `idx_succes` (`Succes`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  ✓ Table auth_logs créée\n";
    
    // Table user_permissions
    $pdo->exec("
        DROP TABLE IF EXISTS `user_permissions`;
        CREATE TABLE IF NOT EXISTS `user_permissions` (
          `ID_Permission` int(11) NOT NULL AUTO_INCREMENT,
          `Role` enum('ADMIN','EMPLOYE') COLLATE utf8mb4_unicode_ci NOT NULL,
          `Permission` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
          `Description` varchar(255) COLLATE utf8mb4_unicode_ci,
          `Created_At` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`ID_Permission`),
          UNIQUE KEY `unique_role_permission` (`Role`, `Permission`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  ✓ Table user_permissions créée\n";
    
    // ============================================================
    // 2. Insérer les permissions par défaut
    // ============================================================
    
    echo "\n[2/3] Insertion des permissions...\n";
    
    $permissions = [
        // ADMIN
        ['ADMIN', 'dashboard.view', 'Voir le tableau de bord'],
        ['ADMIN', 'clients.view', 'Voir les clients'],
        ['ADMIN', 'clients.create', 'Créer un client'],
        ['ADMIN', 'clients.edit', 'Modifier un client'],
        ['ADMIN', 'clients.delete', 'Supprimer un client'],
        ['ADMIN', 'factures.view', 'Voir les factures'],
        ['ADMIN', 'factures.create', 'Créer une facture'],
        ['ADMIN', 'factures.edit', 'Modifier une facture'],
        ['ADMIN', 'factures.delete', 'Supprimer une facture'],
        ['ADMIN', 'factures.pdf', 'Générer PDF factures'],
        ['ADMIN', 'proformas.view', 'Voir les proformas'],
        ['ADMIN', 'proformas.create', 'Créer un proforma'],
        ['ADMIN', 'proformas.edit', 'Modifier un proforma'],
        ['ADMIN', 'proformas.delete', 'Supprimer un proforma'],
        ['ADMIN', 'paiements.view', 'Voir les paiements'],
        ['ADMIN', 'paiements.create', 'Créer un paiement'],
        ['ADMIN', 'paiements.delete', 'Supprimer un paiement'],
        ['ADMIN', 'tresorerie.view', 'Voir la trésorerie'],
        ['ADMIN', 'tresorerie.create', 'Créer une dépense'],
        ['ADMIN', 'utilisateurs.view', 'Voir les utilisateurs'],
        ['ADMIN', 'utilisateurs.create', 'Créer un utilisateur'],
        ['ADMIN', 'utilisateurs.edit', 'Modifier un utilisateur'],
        ['ADMIN', 'utilisateurs.delete', 'Supprimer un utilisateur'],
        ['ADMIN', 'parametres.view', 'Voir les paramètres'],
        ['ADMIN', 'parametres.edit', 'Modifier les paramètres'],
        
        // EMPLOYE
        ['EMPLOYE', 'dashboard.view', 'Voir le tableau de bord'],
        ['EMPLOYE', 'clients.view', 'Voir les clients'],
        ['EMPLOYE', 'clients.create', 'Créer un client'],
        ['EMPLOYE', 'factures.view', 'Voir les factures'],
        ['EMPLOYE', 'factures.create', 'Créer une facture'],
        ['EMPLOYE', 'factures.pdf', 'Générer PDF factures'],
        ['EMPLOYE', 'proformas.view', 'Voir les proformas'],
        ['EMPLOYE', 'proformas.create', 'Créer un proforma'],
        ['EMPLOYE', 'paiements.view', 'Voir les paiements'],
        ['EMPLOYE', 'paiements.create', 'Enregistrer un paiement'],
        ['EMPLOYE', 'tresorerie.view', 'Voir la trésorerie (lecture)'],
    ];
    
    $stmt = $pdo->prepare(
        "INSERT INTO user_permissions (Role, Permission, Description) VALUES (?, ?, ?)"
    );
    
    foreach ($permissions as $perm) {
        $stmt->execute($perm);
    }
    
    echo "  ✓ " . count($permissions) . " permissions insérées\n";
    
    // ============================================================
    // 3. Hasher les mots de passe des utilisateurs
    // ============================================================
    
    echo "\n[3/3] Sécurisation des mots de passe...\n";
    
    $users = $pdo->query("SELECT ID_User, Email, Mot_De_Passe FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
    
    $updateStmt = $pdo->prepare("UPDATE utilisateurs SET Mot_De_Passe = ? WHERE ID_User = ?");
    
    foreach ($users as $user) {
        // Vérifier si le mot de passe n'est pas déjà hashé
        if (strpos($user['Mot_De_Passe'], '$2y$') !== 0) {
            $hashed = password_hash($user['Mot_De_Passe'], PASSWORD_BCRYPT);
            $updateStmt->execute([$hashed, $user['ID_User']]);
            echo "  ✓ Mot de passe sécurisé: {$user['Email']}\n";
        } else {
            echo "  ✓ Déjà sécurisé: {$user['Email']}\n";
        }
    }
    
    echo "\n✅ INSTALLATION COMPLÈTE!\n\n";
    echo "Prochaines étapes:\n";
    echo "1. Accédez à: http://localhost/erp-kola/public/login\n";
    echo "2. Se connecter avec:\n";
    echo "   - Email: admin@kola.com\n";
    echo "   - Password: admin\n";
    echo "3. Les données sont prêtes pour la production!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
