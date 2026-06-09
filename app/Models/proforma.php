<?php
/**
 * Model Proforma - Mise à jour complète
 */

class Proforma extends Model {
    protected $table = 'PROFORMAS';
    protected $fillable = [
        'Reference',
        'Date_Emission',
        'Date_Validite',
        'ID_Client',
        'Objet',
        'Taux_TVA',
        'Montant_HT',
        'Montant_TVA',
        'Montant_TTC',
        'Statut',
        'ID_Facture_Liee',
        'ID_User',
        'Observations',
        'Date_Creation'
    ];

    /**
     * Génère la référence automatique
     */
    public function genererReference() {
        $annee = date('y');

        $stmtInsert = $this->db->prepare(
            "INSERT IGNORE INTO SEQUENCES (Type_Document, Annee, Numero_Courant) VALUES ('PROFORMA', ?, 0)"
        );
        $stmtInsert->execute([$annee]);

        $stmtUpdate = $this->db->prepare(
            "UPDATE SEQUENCES SET Numero_Courant = Numero_Courant + 1 WHERE Type_Document = 'PROFORMA' AND Annee = ?"
        );
        $stmtUpdate->execute([$annee]);

        $stmtSelect = $this->db->prepare(
            "SELECT Numero_Courant FROM SEQUENCES WHERE Type_Document = 'PROFORMA' AND Annee = ?"
        );
        $stmtSelect->execute([$annee]);
        $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        $numero = $result['Numero_Courant'] ?? 1;
        return "P-{$annee}-" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Récupère les lignes d'un proforma
     */
    public function getLignes($idProforma) {
        $query = "SELECT * FROM LIGNES_PROFORMA WHERE ID_Proforma = ? ORDER BY Ordre ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idProforma]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche multicritère
     */
    public function rechercher($terme) {
        $query = "SELECT p.*, c.Nom_Client FROM PROFORMAS p
                  LEFT JOIN CLIENTS c ON p.ID_Client = c.ID_Client
                  WHERE p.Reference LIKE ? OR c.Nom_Client LIKE ? OR p.Objet LIKE ?
                  ORDER BY p.Date_Creation DESC";
        
        $terme = "%{$terme}%";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$terme, $terme, $terme]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les proformas avec infos client
     */
    public function all() {
        $query = "SELECT p.*, c.Nom_Client FROM PROFORMAS p
                  LEFT JOIN CLIENTS c ON p.ID_Client = c.ID_Client
                  ORDER BY p.Date_Creation DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pagination avec infos client
     */
    public function paginate($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $total  = $this->count();

        $query = "SELECT p.*, c.Nom_Client FROM PROFORMAS p
                  LEFT JOIN CLIENTS c ON p.ID_Client = c.ID_Client
                  ORDER BY p.Date_Creation DESC
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