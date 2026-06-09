<?php
/**
 * ProformaController - Gestion des proformas
 */
class ProformaController extends BaseController {

    public function index() {
        $proforma = new Proforma();
        $page     = intval($this->getQuery('page', 1));
        $search   = $this->getQuery('search', '');

        if (!empty($search)) {
            $proformas = $proforma->rechercher($search);
        } else {
            $pagination = $proforma->paginate($page, 20);
            $proformas  = $pagination['data'];
        }

        if ($this->isApiRequest()) {
            $this->success('Liste des proformas', $proformas);
        }

        $data = ['proformas' => $proformas, 'search' => $search, 'pagination' => $pagination ?? null, 'user' => $this->user];
        require_once ROOT_PATH . 'app/Views/proformas/index.php';
    }

    public function store() {
        // Accepte JSON ou POST classique
        $raw = $this->getJsonData();

        if (empty($raw['ID_Client']) || empty($raw['Objet'])) {
            $this->sendError('Client et objet requis', 400);
        }

        // Construire les lignes depuis les champs du formulaire
        $lignes = [];
        $lineNum = 1;
        while (isset($raw["qty_{$lineNum}"]) || isset($raw["price_{$lineNum}"])) {
            $designation = $raw["designation_{$lineNum}"] ?? $raw["service_{$lineNum}"] ?? '';
            $qty         = floatval($raw["qty_{$lineNum}"] ?? 1);
            $prix        = floatval($raw["price_{$lineNum}"] ?? 0);
            if (!empty($designation) || $prix > 0) {
                $lignes[] = [
                    'Designation'  => $designation ?: ($raw["service_{$lineNum}"] ?? 'Prestation'),
                    'Quantite'     => $qty,
                    'Prix_Unitaire'=> $prix,
                    'Total_Ligne'  => $qty * $prix,
                    'Ordre'        => $lineNum
                ];
            }
            $lineNum++;
        }
        // Si lignes envoyées comme tableau JSON
        if (empty($lignes) && isset($raw['lignes']) && is_array($raw['lignes'])) {
            $lignes = $raw['lignes'];
        }

        // Calculer totaux
        $montantHT  = array_sum(array_column($lignes, 'Total_Ligne'));
        $tauxTVA    = floatval($raw['Taux_TVA'] ?? 18);
        $montantTVA = $montantHT * ($tauxTVA / 100);
        $montantTTC = $montantHT + $montantTVA;

        $proforma = new Proforma();
        $reference = $proforma->genererReference();

        $proformaData = [
            'Reference'     => $reference,
            'Date_Emission' => $raw['Date_Emission'] ?? date('Y-m-d'),
            'Date_Validite' => $raw['Date_Validite'] ?? null,
            'ID_Client'     => $raw['ID_Client'],
            'Objet'         => $raw['Objet'],
            'Taux_TVA'      => $tauxTVA,
            'Montant_HT'    => $montantHT,
            'Montant_TVA'   => $montantTVA,
            'Montant_TTC'   => $montantTTC,
            'Statut'        => 'EN_ATTENTE',
            'ID_User'       => $this->user['ID_User'],
            'Date_Creation' => date('Y-m-d H:i:s')
        ];

        $id = $proforma->create($proformaData);

        if ($id) {
            foreach ($lignes as $ligne) {
                $ligne['ID_Proforma'] = $id;
                $ligne['ID_Proforma'] = $id;
                $cols = implode(', ', array_keys($ligne));
                $phs  = implode(', ', array_fill(0, count($ligne), '?'));
                $ins  = $this->db->prepare("INSERT INTO LIGNES_PROFORMA ({$cols}) VALUES ({$phs})");
                $ins->execute(array_values($ligne));
            }
            $this->success('Proforma créé avec succès', ['proforma_id' => $id, 'reference' => $reference], 201);
        } else {
            $this->sendError('Erreur lors de la création', 500);
        }
    }

    public function show($id) {
        $proforma = new Proforma();
        $proformaData = $proforma->findById($id);
        if (!$proformaData) { $this->sendError('Proforma non trouvé', 404); }

        $lignes   = $proforma->getLignes($id);
        $client   = new Client();
        $clientData = $client->findById($proformaData['ID_Client']);
        $parametre  = new ParametreEntreprise();
        $parametreData = $parametre->getParametres();

        $data = ['proforma' => $proformaData, 'lignes' => $lignes, 'client' => $clientData, 'parametres' => $parametreData, 'user' => $this->user];
        require_once ROOT_PATH . 'app/Views/proformas/show.php';
    }

    public function update($id) {
        $data     = $this->getJsonData();
        $proforma = new Proforma();
        if (!$proforma->findById($id)) { $this->sendError('Proforma non trouvé', 404); }
        if ($proforma->update($id, $data)) {
            $this->success('Proforma modifié avec succès');
        } else {
            $this->sendError('Erreur modification', 500);
        }
    }

    public function delete($id) {
        $proforma = new Proforma();
        if ($proforma->update($id, ['Statut' => 'REFUSE'])) {
            $this->success('Proforma supprimé');
        } else {
            $this->sendError('Erreur suppression', 500);
        }
    }

    public function convert($id) {
        $proforma     = new Proforma();
        $proformaData = $proforma->findById($id);
        if (!$proformaData) { $this->sendError('Proforma non trouvé', 404); }
        if (!in_array($proformaData['Statut'], ['EN_ATTENTE', 'ACCEPTE'])) {
            $this->sendError('Seul un proforma en attente ou accepté peut être converti', 400);
        }

        $facture   = new Facture();
        $reference = $facture->genererReference();

        $factureData = [
            'Reference'     => $reference,
            'Date_Emission' => date('Y-m-d'),
            'Date_Echeance' => date('Y-m-d', strtotime('+30 days')),
            'ID_Client'     => $proformaData['ID_Client'],
            'ID_Proforma'   => $id,
            'Objet'         => $proformaData['Objet'],
            'Taux_TVA'      => $proformaData['Taux_TVA'],
            'Montant_HT'    => $proformaData['Montant_HT'],
            'Montant_TVA'   => $proformaData['Montant_TVA'],
            'Montant_TTC'   => $proformaData['Montant_TTC'],
            'Montant_Paye'  => 0,
            'Statut'        => 'EN_ATTENTE',
            'ID_User'       => $this->user['ID_User'],
            'Date_Creation' => date('Y-m-d H:i:s')
        ];

        $factureId = $facture->create($factureData);
        if ($factureId) {
            $lignes = $proforma->getLignes($id);
            foreach ($lignes as $ligne) {
                unset($ligne['ID_Ligne'], $ligne['ID_Proforma']);
                $ligne['ID_Facture'] = $factureId;
                $cols = implode(', ', array_keys($ligne));
                $phs  = implode(', ', array_fill(0, count($ligne), '?'));
                $ins  = $this->db->prepare("INSERT INTO LIGNES_FACTURE ({$cols}) VALUES ({$phs})");
                $ins->execute(array_values($ligne));
            }
            $proforma->update($id, ['Statut' => 'CONVERTI', 'ID_Facture_Liee' => $factureId]);
            $this->success('Facture créée', ['facture_id' => $factureId, 'reference' => $reference], 201);
        } else {
            $this->sendError('Erreur création facture', 500);
        }
    }

    public function search() {
        $terme = $this->getQuery('q', '');
        if (empty($terme)) { $this->sendError('Terme requis', 400); }
        $proforma  = new Proforma();
        $proformas = $proforma->rechercher($terme);
        $this->success('Résultats', $proformas);
    }
}
?>
