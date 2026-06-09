<?php
$pageTitle = 'Proforma ' . ($data['proforma']['Reference'] ?? '');
$currentPage = 'proformas';
$proforma  = $data['proforma'];
$lignes    = $data['lignes'];
$client    = $data['client'];
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Proforma <?= htmlspecialchars($proforma['Reference']) ?></div>
        <div class="page-sub">
            <?= htmlspecialchars($client['Nom_Client'] ?? '—') ?>
            &nbsp;·&nbsp;
            Émis le <?= date('d/m/Y', strtotime($proforma['Date_Emission'])) ?>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= BASE_URL ?>proformas" class="tb-btn">
            <i class="ti ti-arrow-left"></i> Retour
        </a>
        <?php if (in_array($proforma['Statut'], ['EN_ATTENTE', 'ACCEPTE'])): ?>
        <button class="tb-btn btn-primary" onclick="convertToFacture(<?= $proforma['ID_Proforma'] ?>)">
            <i class="ti ti-transfer"></i> Convertir en facture
        </button>
        <?php endif; ?>
    </div>
</div>

<?php
$statutLabels = ['EN_ATTENTE'=>'En attente','ACCEPTE'=>'Accepté','REFUSE'=>'Refusé','CONVERTI'=>'Converti'];
$statutColors = ['EN_ATTENTE'=>'var(--warning)','ACCEPTE'=>'var(--success)','REFUSE'=>'var(--danger)','CONVERTI'=>'var(--primary)'];
$statut = $proforma['Statut'];
?>
<div style="margin-bottom:16px">
    <span style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;
        background:<?= $statutColors[$statut] ?? '#999' ?>1a;color:<?= $statutColors[$statut] ?? '#999' ?>">
        <?= $statutLabels[$statut] ?? $statut ?>
    </span>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <div>
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
            <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--dark)">
                Détail des prestations — <?= htmlspecialchars($proforma['Objet'] ?? '') ?>
            </div>
            <table>
                <thead>
                    <tr><th>#</th><th>Désignation</th><th style="text-align:center">Qté</th><th style="text-align:right">P.U.</th><th style="text-align:right">Total</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($lignes)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">Aucune ligne</td></tr>
                    <?php else: ?>
                    <?php foreach ($lignes as $i => $ligne): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($ligne['Designation']) ?></td>
                        <td style="text-align:center"><?= $ligne['Quantite'] ?></td>
                        <td style="text-align:right"><?= number_format($ligne['Prix_Unitaire'], 0, ',', ' ') ?> FCFA</td>
                        <td style="text-align:right"><strong><?= number_format($ligne['Total_Ligne'], 0, ',', ' ') ?> FCFA</strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="padding:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <table style="width:260px;font-size:13px">
                    <tr><td style="padding:4px 0;color:var(--muted)">Sous-total HT</td><td style="text-align:right"><strong><?= number_format($proforma['Montant_HT'], 0, ',', ' ') ?> FCFA</strong></td></tr>
                    <tr><td style="padding:4px 0;color:var(--muted)">TVA (<?= $proforma['Taux_TVA'] ?>%)</td><td style="text-align:right"><?= number_format($proforma['Montant_TVA'], 0, ',', ' ') ?> FCFA</td></tr>
                    <tr style="border-top:2px solid var(--border)"><td style="padding:8px 0;font-weight:700;font-size:14px">Total TTC</td><td style="text-align:right;font-weight:800;font-size:15px;color:var(--primary)"><?= number_format($proforma['Montant_TTC'], 0, ',', ' ') ?> FCFA</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:12px">Client</div>
            <div style="font-size:13px">
                <div style="font-weight:600"><?= htmlspecialchars($client['Nom_Client'] ?? '—') ?></div>
                <div style="color:var(--muted);margin-top:4px"><?= htmlspecialchars($client['Adresse'] ?? '') ?></div>
                <div style="color:var(--muted)"><?= htmlspecialchars($client['Telephone'] ?? '') ?></div>
            </div>
        </div>
        <div class="card">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:12px">Dates</div>
            <table style="width:100%;font-size:13px">
                <tr><td style="color:var(--muted);padding:4px 0">Émission</td><td><?= date('d/m/Y', strtotime($proforma['Date_Emission'])) ?></td></tr>
                <?php if (!empty($proforma['Date_Validite'])): ?>
                <tr><td style="color:var(--muted);padding:4px 0">Validité</td><td><?= date('d/m/Y', strtotime($proforma['Date_Validite'])) ?></td></tr>
                <?php endif; ?>
            </table>
            <?php if (!empty($proforma['ID_Facture_Liee'])): ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
                <a href="<?= BASE_URL ?>factures/<?= $proforma['ID_Facture_Liee'] ?>" class="tb-btn btn-primary" style="width:100%;justify-content:center">
                    <i class="ti ti-receipt"></i> Voir la facture liée
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function convertToFacture(id) {
    if (!confirm('Convertir ce proforma en facture ?')) return;
    try {
        const result = await apiCall(`proformas/${id}/convert`, 'POST');
        showSuccess('Facture créée : ' + result.data.reference);
        setTimeout(() => { window.location = '<?= BASE_URL ?>factures/' + result.data.facture_id; }, 1000);
    } catch (e) {
        console.error('Erreur:', e);
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
