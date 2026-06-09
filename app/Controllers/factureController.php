<?php
/**
 * FactureController - Gestion des factures
 */
class FactureController extends BaseController {

    public function index() {
        $facture  = new Facture();
        $page     = intval($this->getQuery('page', 1));
        $search   = $this->getQuery('search', '');
        $clientId = $this->getQuery('client', '');

        if (!empty($search)) {
            $factures = $facture->rechercher($search);
        } elseif (!empty($clientId)) {
            $stmt = $this->db->prepare(
                "SELECT f.*, c.Nom_Client FROM FACTURES f
                 LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                 WHERE f.ID_Client = ? AND f.Statut != 'ANNULEE'
                 ORDER BY f.Date_Emission DESC"
            );
            $stmt->execute([$clientId]);
            $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $pagination = $facture->paginate($page, 20);
            $factures   = $pagination['data'];
        }

        if ($this->isApiRequest()) {
            $this->success('Liste des factures', $factures);
        }

        $data = ['factures' => $factures, 'search' => $search, 'pagination' => $pagination ?? null, 'user' => $this->user];
        require_once ROOT_PATH . 'app/Views/factures/index.php';
    }

    public function create() {
        $data = ['user' => $this->user];
        require_once ROOT_PATH . 'app/Views/factures/create.php';
    }

    public function store() {
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
                    'Designation'   => $designation ?: 'Prestation',
                    'Quantite'      => $qty,
                    'Prix_Unitaire' => $prix,
                    'Total_Ligne'   => $qty * $prix,
                    'Ordre'         => $lineNum
                ];
            }
            $lineNum++;
        }
        if (empty($lignes) && isset($raw['lignes']) && is_array($raw['lignes'])) {
            $lignes = $raw['lignes'];
        }

        $montantHT  = array_sum(array_column($lignes, 'Total_Ligne'));
        $tauxTVA    = floatval($raw['Taux_TVA'] ?? 18);
        $montantTVA = $montantHT * ($tauxTVA / 100);
        $montantTTC = $montantHT + $montantTVA;

        $facture   = new Facture();
        $reference = $facture->genererReference();

        $factureData = [
            'Reference'     => $reference,
            'Date_Emission' => $raw['Date_Emission'] ?? date('Y-m-d'),
            'Date_Echeance' => $raw['Date_Echeance'] ?? date('Y-m-d', strtotime('+30 days')),
            'ID_Client'     => $raw['ID_Client'],
            'Objet'         => $raw['Objet'],
            'Taux_TVA'      => $tauxTVA,
            'Montant_HT'    => $montantHT,
            'Montant_TVA'   => $montantTVA,
            'Montant_TTC'   => $montantTTC,
            'Montant_Paye'  => 0,
            'Statut'        => 'EN_ATTENTE',
            'Date_Creation' => date('Y-m-d H:i:s')
        ];

        $id = $facture->create($factureData);
        if ($id) {
            foreach ($lignes as $ligne) {
                $ligne['ID_Facture'] = $id;
                $cols = implode(', ', array_keys($ligne));
                $phs  = implode(', ', array_fill(0, count($ligne), '?'));
                $ins  = $this->db->prepare("INSERT INTO LIGNES_FACTURE ({$cols}) VALUES ({$phs})");
                $ins->execute(array_values($ligne));
            }
            $this->success('Facture créée avec succès', ['facture_id' => $id, 'reference' => $reference], 201);
        } else {
            $this->sendError('Erreur lors de la création', 500);
        }
    }

    public function show($id) {
        $facture     = new Facture();
        $factureData = $facture->findById($id);
        if (!$factureData) { $this->sendError('Facture non trouvée', 404); }

        $lignes    = $facture->getLignes($id);
        $client    = new Client();
        $clientData = $client->findById($factureData['ID_Client']);

        $stmt = $this->db->prepare("SELECT * FROM PAIEMENTS WHERE ID_Facture = ? ORDER BY Date_Paiement DESC");
        $stmt->execute([$id]);
        $paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $parametre     = new ParametreEntreprise();
        $parametreData = $parametre->getParametres();

        $data = ['facture' => $factureData, 'lignes' => $lignes, 'client' => $clientData,
                 'paiements' => $paiements, 'parametres' => $parametreData, 'user' => $this->user];
        require_once ROOT_PATH . 'app/Views/factures/show.php';
    }

    public function update($id) {
        $data    = $this->getJsonData();
        $facture = new Facture();
        if ($facture->update($id, $data)) {
            $this->success('Facture modifiée');
        } else {
            $this->sendError('Erreur modification', 500);
        }
    }

    public function cancel($id) {
        $facture = new Facture();
        if ($facture->update($id, ['Statut' => 'ANNULEE'])) {
            $this->success('Facture annulée');
        } else {
            $this->sendError('Erreur annulation', 500);
        }
    }

    public function stats() {
        $facture = new Facture();
        $this->success('Statistiques', $facture->statistiques());
    }

    public function enRetard() {
        $facture = new Facture();
        $this->success('Factures en retard', $facture->enRetard());
    }

    public function search() {
        $terme = $this->getQuery('q', '');
        if (empty($terme)) { $this->sendError('Terme requis', 400); }
        $facture  = new Facture();
        $factures = $facture->rechercher($terme);
        $this->success('Résultats', $factures);
    }

    public function generatePDF($id) {
        $facture     = new Facture();
        $factureData = $facture->findById($id);
        if (!$factureData) { $this->sendError('Facture non trouvée', 404); }
        $lignes      = $facture->getLignes($id);
        $client      = new Client();
        $clientData  = $client->findById($factureData['ID_Client']);
        $parametre   = new ParametreEntreprise();
        $parametreData = $parametre->getParametres();

        header('Content-Type: text/html; charset=utf-8');
        $html = $this->buildInvoiceHTML($factureData, $lignes, $clientData, $parametreData);
        echo $html;
        exit;
    }

        private function buildInvoiceHTML($facture, $lignes, $client, $p) {
        $lignesHTML = '';
        $total = 0;
        foreach ($lignes as $i => $l) {
            $tot = floatval($l['Total_Ligne']);
            $total += $tot;
            $lignesHTML .= "
            <tr>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;color:#666'>".($i+1)."</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0'>".htmlspecialchars($l['Designation'])."</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;text-align:center'>".number_format($l['Quantite'],2,',',' ')."</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;text-align:right'>".number_format($l['Prix_Unitaire'],0,',',' ')." FCFA</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:600'>".number_format($tot,0,',',' ')." FCFA</td>
            </tr>";
        }
        $ht    = floatval($facture['Montant_HT']);
        $tva   = floatval($facture['Montant_TVA']);
        $ttc   = floatval($facture['Montant_TTC']);
        $paye  = floatval($facture['Montant_Paye']);
        $solde = $ttc - $paye;
        $taux  = floatval($facture['Taux_TVA']);
        $statuts = ['EN_ATTENTE'=>'En attente','PARTIELLE'=>'Partiellement payée','SOLDEE'=>'Soldée','ANNULEE'=>'Annulée'];
        $statutLabel = $statuts[$facture['Statut']] ?? $facture['Statut'];
        $statutColor = ['EN_ATTENTE'=>'#e67e22','PARTIELLE'=>'#3498db','SOLDEE'=>'#27ae60','ANNULEE'=>'#e74c3c'][$facture['Statut']] ?? '#999';
        $nomEnt = htmlspecialchars($p['Nom_Entreprise'] ?? 'Entreprise');
        $logoHtml = '';
        if (!empty($p['Logo']) && file_exists(ROOT_PATH . 'public/' . $p['Logo'])) {
            $logoHtml = "<img src='".BASE_URL.$p['Logo']."' style=' width:300px; height:120px ;object-fit:contain'><br>";
        }
        return "<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<title>Facture {$facture['Reference']}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#222;background:#fff;padding:30px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px}
  .company{ max-weight: 300px}
  .company-name{font-size:20px;font-weight:700;color:#003082}
  .company-info{font-size:11.5px;color:#666;margin-top:4px;line-height:1.6}
  .invoice-box{text-align:right; margin-top: 30px}
  .invoice-title{font-size:28px;font-weight:800;color:#003082;letter-spacing:1px}
  .invoice-ref{font-size:13px;color:#555;margin-top:4px}
  .badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;margin-top:6px}
  .parties{display:flex;gap:20px;margin-bottom:24px}
  .party{flex:1;background:#f8f9fb;border-radius:8px;padding:14px 16px}
  .party-title{font-size:10px;font-weight:700;text-transform:uppercase;color:#999;letter-spacing:1px;margin-bottom:8px}
  .party-name{font-weight:700;font-size:14px;color:#111;margin-bottom:4px}
  .party-info{font-size:11.5px;color:#666;line-height:1.7}
  .dates{display:flex;gap:10px;margin-bottom:24px}
  .date-box{flex:1;border:1px solid #e8e8e8;border-radius:6px;padding:10px 14px}
  .date-label{font-size:10px;font-weight:700;text-transform:uppercase;color:#999;letter-spacing:.5px}
  .date-val{font-size:13px;font-weight:600;color:#222;margin-top:3px}
  table{width:100%;border-collapse:collapse;margin-bottom:20px}
  thead tr{background:#003082;color:white}
  thead th{padding:10px 12px;text-align:left;font-size:12px;font-weight:600}
  thead th:nth-child(3),thead th:nth-child(4),thead th:nth-child(5){text-align:right}
  .totals{display:flex;justify-content:flex-end;margin-bottom:24px}
  .totals-box{width:260px}
  .total-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
  .total-row.big{font-size:16px;font-weight:800;color:#003082;border-bottom:none;padding-top:10px}
  .footer{border-top:1px solid #e8e8e8;padding-top:14px;font-size:11px;color:#999;text-align:center}
  @media print{body{padding:10px}.no-print{display:none}}
</style>
</head>
<body>
<div style='max-width:800px;margin:auto'>

  <!-- Header -->
  <div class='header'>
    <div class='company'>
      {$logoHtml}
    </div>
    <div class='invoice-box'>
      <div class='invoice-title'>FACTURE</div>
      <div class='invoice-ref'>{$facture['Reference']}</div>
      <div><span class='badge' style='background:{$statutColor}22;color:{$statutColor}'>{$statutLabel}</span></div>
    </div>
  </div>

  <!-- Parties -->
  <div class='parties'>
    <div class='party'>
      <div class='party-title'>Émetteur</div>
      <div class='party-name'>{$nomEnt}</div>
      <div class='party-info'>
        ".htmlspecialchars($p['RCCM'] ?  'RCCM : '.$p['RCCM'] : '')."
        ".htmlspecialchars($p['Adresse'] ?? '')."<br>
        ".htmlspecialchars($p['Telephone_Principal'] ?? '')." 
         ".($p['Email'] ? ' · '.htmlspecialchars($p['Email']) : '')."
      </div>
    </div>
    <div class='party'>
      <div class='party-title'>Facturé à</div>
      <div class='party-name'>".htmlspecialchars($client['Nom_Client'] ?? '—')."</div>
      <div class='party-info'>
        ".htmlspecialchars($client['Adresse'] ?? '')."<br>
        ".htmlspecialchars($client['Telephone'] ?? '')."
      </div>
    </div>
  </div>

  <!-- Dates -->
  <div class='dates'>
    <div class='date-box'>
      <div class='date-label'>Date d'émission</div>
      <div class='date-val'>".date('d/m/Y',strtotime($facture['Date_Emission']))."</div>
    </div>
    <div class='date-box'>
      <div class='date-label'>Échéance</div>
      <div class='date-val'>".date('d/m/Y',strtotime($facture['Date_Echeance']))."</div>
    </div>
    <div class='date-box'>
      <div class='date-label'>Objet</div>
      <div class='date-val'>".htmlspecialchars($facture['Objet'])."</div>
    </div>
  </div>

  <!-- Lignes -->
  <table>
    <thead>
      <tr>
        <th style='width:30px'>#</th>
        <th>Désignation</th>
        <th style='width:80px;text-align:right'>Qté</th>
        <th style='width:120px;text-align:right'>P.U. (FCFA)</th>
        <th style='width:130px;text-align:right'>Total (FCFA)</th>
      </tr>
    </thead>
    <tbody>{$lignesHTML}</tbody>
  </table>

  <!-- Totaux -->
  <div class='totals'>
    <div class='totals-box'>
      <div class='total-row'><span>Sous-total HT</span><span>".number_format($ht,0,',',' ')." FCFA</span></div>
      <div class='total-row'><span>TVA ({$taux}%)</span><span>".number_format($tva,0,',',' ')." FCFA</span></div>
      <div class='total-row'><span>Déjà payé</span><span style='color:#27ae60'>".number_format($paye,0,',',' ')." FCFA</span></div>
      <div class='total-row big'><span>Solde dû</span><span>".number_format($solde,0,',',' ')." FCFA</span></div>
    </div>
  </div>

  <!-- Paiement -->
  ".(!empty($p['Coordonnee_Bancaire']) ? "<div style='background:#f8f9fb;border-radius:8px;padding:14px 16px;margin-bottom:20px;font-size:12px'><strong>Coordonnées bancaires :</strong> ".htmlspecialchars($p['Coordonnee_Bancaire'])."</div>" : "")."
  ".(!empty($p['Moyen_Paiement_Mobile']) ? "<div style='background:#f8f9fb;border-radius:8px;padding:14px 16px;margin-bottom:20px;font-size:12px'><strong>Mobile Money :</strong> ".htmlspecialchars($p['Moyen_Paiement_Mobile'])."</div>" : "")."

  <!-- Footer -->
  <div class='footer'>
    ".htmlspecialchars($p['Mentions_Legales'] ?? 'Merci pour votre confiance.')."
  </div>

  <div class='no-print' style='margin-top:20px;text-align:center'>
    <button onclick='window.print()' style='padding:10px 24px;background:#003082;color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px'>
      Imprimer / Enregistrer PDF
    </button>
  </div>
</div>
</body></html>";
    }
       
}
?>
