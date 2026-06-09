-- ============================================
-- TABLE: auth_logs (Enregistrement des connexions)
-- ============================================

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

-- ============================================
-- TABLE: user_permissions (Permissions par rôle)
-- ============================================

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

-- ============================================
-- Insérer les permissions par défaut
-- ============================================

INSERT INTO `user_permissions` (`Role`, `Permission`, `Description`) VALUES
-- ADMIN - Accès complet
('ADMIN', 'dashboard.view', 'Voir le tableau de bord'),
('ADMIN', 'clients.view', 'Voir les clients'),
('ADMIN', 'clients.create', 'Créer un client'),
('ADMIN', 'clients.edit', 'Modifier un client'),
('ADMIN', 'clients.delete', 'Supprimer un client'),
('ADMIN', 'factures.view', 'Voir les factures'),
('ADMIN', 'factures.create', 'Créer une facture'),
('ADMIN', 'factures.edit', 'Modifier une facture'),
('ADMIN', 'factures.delete', 'Supprimer une facture'),
('ADMIN', 'factures.pdf', 'Générer PDF factures'),
('ADMIN', 'proformas.view', 'Voir les proformas'),
('ADMIN', 'proformas.create', 'Créer un proforma'),
('ADMIN', 'proformas.edit', 'Modifier un proforma'),
('ADMIN', 'proformas.delete', 'Supprimer un proforma'),
('ADMIN', 'paiements.view', 'Voir les paiements'),
('ADMIN', 'paiements.create', 'Créer un paiement'),
('ADMIN', 'paiements.delete', 'Supprimer un paiement'),
('ADMIN', 'tresorerie.view', 'Voir la trésorerie'),
('ADMIN', 'tresorerie.create', 'Créer une dépense'),
('ADMIN', 'utilisateurs.view', 'Voir les utilisateurs'),
('ADMIN', 'utilisateurs.create', 'Créer un utilisateur'),
('ADMIN', 'utilisateurs.edit', 'Modifier un utilisateur'),
('ADMIN', 'utilisateurs.delete', 'Supprimer un utilisateur'),
('ADMIN', 'parametres.view', 'Voir les paramètres'),
('ADMIN', 'parametres.edit', 'Modifier les paramètres'),

-- EMPLOYE - Accès limité
('EMPLOYE', 'dashboard.view', 'Voir le tableau de bord'),
('EMPLOYE', 'clients.view', 'Voir les clients'),
('EMPLOYE', 'clients.create', 'Créer un client'),
('EMPLOYE', 'factures.view', 'Voir les factures'),
('EMPLOYE', 'factures.create', 'Créer une facture'),
('EMPLOYE', 'factures.pdf', 'Générer PDF factures'),
('EMPLOYE', 'proformas.view', 'Voir les proformas'),
('EMPLOYE', 'proformas.create', 'Créer un proforma'),
('EMPLOYE', 'paiements.view', 'Voir les paiements'),
('EMPLOYE', 'paiements.create', 'Enregistrer un paiement'),
('EMPLOYE', 'tresorerie.view', 'Voir la trésorerie (lecture)');
