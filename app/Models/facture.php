<?php
/**
 * Model Facture - Mise à jour complète
 */

class Facture extends Model {
    protected $table = 'FACTURES';
    protected $fillable = [
        'Reference',
        'Date_Emission',
        'Date_Echeance',
        'ID_Client',
        'ID_Proforma',
        'Objet',
        'Taux_TVA',
        'Montant_HT',
        'Montant_TVA',
        'Montant_TTC',
        'Montant_Paye',
        'Statut',
        'ID_User',
        'Observations',
        'Date_Creation'
    ];

    /**
     * Génère la référence automatique (upsert-safe)
     */
    public function genererReference() {
        $annee = date('y');

        // Tenter d'insérer la ligne si elle n'existe pas encore
        $queryInsert = "INSERT IGNORE INTO SEQUENCES (Type_Document, Annee, Numero_Courant)
                        VALUES ('FACTURE', ?, 0)";
        $stmtInsert = $this->db->prepare($queryInsert);
        $stmtInsert->execute([$annee]);

        // Incrémenter et récupérer le nouveau numéro de façon atomique
        $queryUpdate = "UPDATE SEQUENCES
                        SET Numero_Courant = Numero_Courant + 1
                        WHERE Type_Document = 'FACTURE' AND Annee = ?";
        $stmtUpdate = $this->db->prepare($queryUpdate);
        $stmtUpdate->execute([$annee]);

        $querySelect = "SELECT Numero_Courant FROM SEQUENCES
                        WHERE Type_Document = 'FACTURE' AND Annee = ?";
        $stmtSelect = $this->db->prepare($querySelect);
        $stmtSelect->execute([$annee]);
        $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        $numero = $result['Numero_Courant'] ?? 1;
        return "F-{$annee}-" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Récupère les factures impayées
     */
    public function impayees() {
        $query = "SELECT * FROM FACTURES 
                  WHERE Statut IN ('EN_ATTENTE', 'PARTIELLE') 
                  ORDER BY Date_Echeance ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les factures en retard
     */
    public function enRetard() {
        $query = "SELECT * FROM FACTURES 
                  WHERE Statut IN ('EN_ATTENTE', 'PARTIELLE') 
                  AND Date_Echeance < CURDATE()
                  ORDER BY Date_Echeance ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques financières
     */
    public function statistiques() {
        $query = "SELECT 
                    COALESCE(SUM(Montant_TTC), 0) as ca_total,
                    COALESCE(SUM(Montant_Paye), 0) as encaisse,
                    COALESCE(SUM(Montant_TTC - Montant_Paye), 0) as solde_restant,
                    COUNT(*) as nb_factures,
                    SUM(CASE WHEN Statut = 'SOLDEE' THEN 1 ELSE 0 END) as nb_soldees,
                    SUM(CASE WHEN Statut IN ('EN_ATTENTE', 'PARTIELLE') THEN 1 ELSE 0 END) as nb_impayees
                  FROM FACTURES
                  WHERE Statut != 'ANNULEE'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les lignes d'une facture
     */
    public function getLignes($idFacture) {
        $query = "SELECT * FROM LIGNES_FACTURE WHERE ID_Facture = ? ORDER BY Ordre ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idFacture]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche multicritère
     */
    public function rechercher($terme) {
        $query = "SELECT f.*, c.Nom_Client FROM FACTURES f
                  LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                  WHERE (f.Reference LIKE ? OR c.Nom_Client LIKE ? OR f.Objet LIKE ?)
                  AND f.Statut != 'ANNULEE'
                  ORDER BY f.Date_Emission DESC";
        
        $terme = "%{$terme}%";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$terme, $terme, $terme]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les factures avec infos client
     */
    public function all() {
        $query = "SELECT f.*, c.Nom_Client FROM FACTURES f
                  LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                  WHERE f.Statut != 'ANNULEE'
                  ORDER BY f.Date_Emission DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pagination avec infos client
     */
    public function paginate($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $total  = $this->count("Statut != 'ANNULEE'");

        $query = "SELECT f.*, c.Nom_Client FROM FACTURES f
                  LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                  WHERE f.Statut != 'ANNULEE'
                  ORDER BY f.Date_Emission DESC
                  LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => ceil($total / $perPage)
        ];
    }
}

?>