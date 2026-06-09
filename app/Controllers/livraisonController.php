<?php
class LivraisonController extends BaseController {
    public function index() {
        if ($this->isApiRequest()) {
            $stmt = $this->db->prepare(
                "SELECT bl.*, f.Reference as Ref_Facture, c.Nom_Client
                 FROM BONS_LIVRAISON bl
                 LEFT JOIN FACTURES f ON bl.ID_Facture = f.ID_Facture
                 LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
                 ORDER BY bl.Date_Livraison DESC LIMIT 50"
            );
            $stmt->execute();
            $this->success('Bons de livraison', $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $data = ['user' => $this->user];
        require_once ROOT_PATH . 'app/Views/livraisons/index.php';
    }

    public function store() {
        $d = $this->getJsonData();
        if (empty($d['ID_Facture'])) $this->sendError('Facture requise', 400);
        $facture = new Facture();
        $f = $facture->findById($d['ID_Facture']);
        if (!$f) $this->sendError('Facture introuvable', 404);

        // Générer référence BL
        $annee = date('y');
        $this->db->prepare("INSERT IGNORE INTO SEQUENCES (Type_Document,Annee,Numero_Courant) VALUES ('BL',?,0)")->execute([$annee]);
        $this->db->prepare("UPDATE SEQUENCES SET Numero_Courant=Numero_Courant+1 WHERE Type_Document='BL' AND Annee=?")->execute([$annee]);
        $r = $this->db->prepare("SELECT Numero_Courant FROM SEQUENCES WHERE Type_Document='BL' AND Annee=?");
        $r->execute([$annee]);
        $num = $r->fetch(PDO::FETCH_ASSOC)['Numero_Courant'] ?? 1;
        $ref = "BL-{$annee}-" . str_pad($num, 4, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(
            "INSERT INTO BONS_LIVRAISON (Reference,ID_Facture,Date_Livraison,Statut,Observations,ID_User,Date_Creation)
             VALUES (?,?,?,?,?,?,?)"
        );
        $ok = $stmt->execute([
            $ref, $d['ID_Facture'], $d['Date_Livraison'] ?? date('Y-m-d'),
            'EN_COURS', $d['Observations'] ?? '', 1, date('Y-m-d H:i:s')
        ]);
        if ($ok) $this->success('BL créé', ['id' => $this->db->lastInsertId(), 'reference' => $ref], 201);
        else $this->sendError('Erreur création', 500);
    }

    public function updateStatut($id) {
        $d = $this->getJsonData();
        $stmt = $this->db->prepare("UPDATE BONS_LIVRAISON SET Statut=? WHERE ID_BL=?");
        $stmt->execute([$d['Statut'] ?? 'LIVRE', $id]);
        $this->success('Statut mis à jour');
    }

    public function generatePDF($id) {
        $stmt = $this->db->prepare(
            "SELECT bl.*, f.Reference as Ref_Facture, f.Objet, f.Date_Emission,
                    c.Nom_Client, c.Adresse as Adresse_Client, c.NIF as NIF_Client, c.Telephone
             FROM BONS_LIVRAISON bl
             LEFT JOIN FACTURES f ON bl.ID_Facture = f.ID_Facture
             LEFT JOIN CLIENTS c ON f.ID_Client = c.ID_Client
             WHERE bl.ID_BL = ?"
        );
        $stmt->execute([$id]);
        $bl = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bl) { $this->sendError('BL non trouvé', 404); }

        $facture = new Facture();
        $lignes  = $facture->getLignes($bl['ID_Facture']);
        $p       = (new ParametreEntreprise())->getParametres() ?? [];
        $nomEnt  = htmlspecialchars($p['Nom_Entreprise'] ?? 'Entreprise');
        $logoHtml = '';
        if (!empty($p['Logo']) && file_exists(ROOT_PATH . 'public/' . $p['Logo'])) {
            $logoHtml = "<img src='".BASE_URL.$p['Logo']."' style='height:56px;object-fit:contain;margin-bottom:4px'><br>";
        }
        $lignesHTML = '';
        foreach ($lignes as $i => $l) {
            $lignesHTML .= "<tr>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;color:#666'>".($i+1)."</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0'>".htmlspecialchars($l['Designation'])."</td>
                <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;text-align:center'>".number_format($l['Quantite'],2,',',' ')."</td>
            </tr>";
        }
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'>
        <title>BL {$bl['Reference']}</title>
        <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;padding:30px;color:#222}
        .header{display:flex;justify-content:space-between;margin-bottom:28px}
        .title{font-size:28px;font-weight:800;color:#003082;text-align:right}
        .parties{display:flex;gap:20px;margin-bottom:20px}
        .party{flex:1;background:#f8f9fb;border-radius:8px;padding:14px 16px}
        .party-title{font-size:10px;font-weight:700;text-transform:uppercase;color:#999;margin-bottom:8px}
        table{width:100%;border-collapse:collapse;margin-bottom:20px}
        thead tr{background:#003082;color:white}
        thead th{padding:10px 12px;text-align:left;font-size:12px}
        .footer{border-top:1px solid #e8e8e8;padding-top:14px;font-size:11px;color:#999;text-align:center}
        @media print{.no-print{display:none}}</style></head><body><div style='max-width:800px;margin:auto'>
        <div class='header'>
          <div>{$logoHtml}<div style='font-size:18px;font-weight:700;color:#003082'>{$nomEnt}</div>
          <div style='font-size:11.5px;color:#666;margin-top:4px'>".htmlspecialchars($p['Adresse']??'')."</div></div>
          <div><div class='title'>BON DE LIVRAISON</div>
          <div style='text-align:right;margin-top:6px;color:#555'>Réf. : {$bl['Reference']}<br>
          Date : ".date('d/m/Y',strtotime($bl['Date_Livraison']))."<br>
          Facture : {$bl['Ref_Facture']}</div></div>
        </div>
        <div class='parties'>
          <div class='party'><div class='party-title'>Émetteur</div><strong>{$nomEnt}</strong><br>
          <span style='font-size:11.5px;color:#666'>".htmlspecialchars($p['Adresse']??'')."</span></div>
          <div class='party'><div class='party-title'>Destinataire</div><strong>".htmlspecialchars($bl['Nom_Client'])."</strong><br>
          <span style='font-size:11.5px;color:#666'>".htmlspecialchars($bl['Adresse_Client']??'')."<br>".htmlspecialchars($bl['Telephone']??'')."</span></div>
        </div>
        <table>
          <thead><tr><th style='width:30px'>#</th><th>Désignation</th><th style='width:80px;text-align:right'>Qté</th></tr></thead>
          <tbody>{$lignesHTML}</tbody>
        </table>
        <div style='display:flex;gap:40px;margin-top:30px'>
          <div style='flex:1;border-top:1px solid #ccc;padding-top:8px;text-align:center;font-size:12px;color:#666'>Signature émetteur</div>
          <div style='flex:1;border-top:1px solid #ccc;padding-top:8px;text-align:center;font-size:12px;color:#666'>Signature récepteur</div>
        </div>
        ".(!empty($bl['Observations']) ? "<div style='margin-top:20px;background:#f8f9fb;padding:12px;border-radius:6px;font-size:12px'><strong>Observations :</strong> ".htmlspecialchars($bl['Observations'])."</div>" : "")."
        <div class='footer' style='margin-top:20px'>".htmlspecialchars($p['Mentions_Legales']??'Merci pour votre confiance.')."</div>
        <div class='no-print' style='margin-top:20px;text-align:center'>
          <button onclick='window.print()' style='padding:10px 24px;background:#003082;color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px'>Imprimer / Enregistrer PDF</button>
        </div>
        </div></body></html>";
        exit;
    }
}
?>
