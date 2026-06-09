<?php
/**
 * PaiementController - Gestion des paiements
 */

class PaiementController extends BaseController {

    /**
     * Liste tous les paiements
     */
    public function index() {
        $query = "SELECT 
                    p.*,
                    f.Reference,
                    c.Nom_Client
                  FROM PAIEMENTS p
                  LEFT JOIN FACTURES f ON p.ID_Facture = f.ID_Facture
                  LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                  ORDER BY p.Date_Paiement DESC
                  LIMIT 20";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'paiements' => $paiements,
            'user'      => $this->user
        ];

        // NOTE : la view paiements/index.php doit exister
        require_once ROOT_PATH . 'app/Views/paiements/index.php';
    }

    /**
     * Enregistre un paiement (JSON)
     * CORRECTION : lastInsertId() était appelé AVANT l'INSERT
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Méthode non autorisée', 405);
        }

        $data = $this->getJsonData();

        if (empty($data['ID_Facture']) || empty($data['Montant'])) {
            $this->sendError('Facture et montant requis', 400);
        }

        $facture = new Facture();
        $factureData = $facture->findById($data['ID_Facture']);

        if (!$factureData) {
            $this->sendError('Facture non trouvée', 404);
        }

        $soldeRestant = $factureData['Montant_TTC'] - $factureData['Montant_Paye'];
        if ($data['Montant'] > $soldeRestant) {
            $this->sendError('Le montant dépasse le solde restant (' . $soldeRestant . ' FCFA)', 400);
        }

        $data['Date_Paiement'] = $data['Date_Paiement'] ?? date('Y-m-d H:i:s');
        $data['Date_Saisie']   = date('Y-m-d H:i:s');

        $insertQuery = "INSERT INTO PAIEMENTS 
                       (Reference, Date_Paiement, ID_Facture, Montant, Mode_Paiement, Observation, ID_User, Date_Saisie)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($insertQuery);
        $result = $stmt->execute([
            $data['Reference']     ?? null,
            $data['Date_Paiement'],
            $data['ID_Facture'],
            $data['Montant'],
            $data['Mode_Paiement'] ?? 'Especes',
            $data['Observation']   ?? null,
            $data['ID_User'],
            $data['Date_Saisie']
        ]);

        if ($result) {
            // CORRECTION : lastInsertId() appelé APRÈS l'INSERT
            $paiementId = $this->db->lastInsertId();

            $this->updateInvoicePaymentStatus($data['ID_Facture']);

            $this->log("Paiement enregistré : Facture {$data['ID_Facture']}", 'INFO');
            $this->success('Paiement enregistré avec succès', ['paiement_id' => $paiementId], 201);
        } else {
            $this->sendError("Erreur lors de l'enregistrement du paiement", 500);
        }
    }

    /**
     * Supprime un paiement (JSON)
     */
    public function delete($id) {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Méthode non autorisée', 405);
        }

        $query = "SELECT ID_Facture FROM PAIEMENTS WHERE ID_Paiement = ?";
        $stmt  = $this->db->prepare($query);
        $stmt->execute([$id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paiement) {
            $this->sendError('Paiement non trouvé', 404);
        }

        $deleteStmt = $this->db->prepare("DELETE FROM PAIEMENTS WHERE ID_Paiement = ?");

        if ($deleteStmt->execute([$id])) {
            $this->updateInvoicePaymentStatus($paiement['ID_Facture']);

            $this->log("Paiement supprimé : ID {$id}", 'INFO');
            $this->success('Paiement supprimé avec succès');
        } else {
            $this->sendError('Erreur lors de la suppression', 500);
        }
    }

    /**
     * Met à jour le statut de paiement d'une facture
     */
    private function updateInvoicePaymentStatus($idFacture) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(Montant), 0) as montant_paye FROM PAIEMENTS WHERE ID_Facture = ?");
        $stmt->execute([$idFacture]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $factureStmt = $this->db->prepare("SELECT Montant_TTC FROM FACTURES WHERE ID_Facture = ?");
        $factureStmt->execute([$idFacture]);
        $facture = $factureStmt->fetch(PDO::FETCH_ASSOC);

        $montantPaye = $result['montant_paye'] ?? 0;
        $montantTTC  = $facture['Montant_TTC'] ?? 0;

        $statut = 'EN_ATTENTE';
        if ($montantPaye >= $montantTTC && $montantTTC > 0) {
            $statut = 'SOLDEE';
        } elseif ($montantPaye > 0) {
            $statut = 'PARTIELLE';
        }

        $updateStmt = $this->db->prepare("UPDATE FACTURES SET Montant_Paye = ?, Statut = ? WHERE ID_Facture = ?");
        $updateStmt->execute([$montantPaye, $statut, $idFacture]);
    }
}

?>
