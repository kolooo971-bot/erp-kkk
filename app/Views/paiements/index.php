<?php
$pageTitle = 'Paiements';
$currentPage = 'paiements';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Paiements</div>
        <div class="page-sub">Historique des encaissements</div>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table id="paiements-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Facture</th>
                <th>Client</th>
                <th>Mode</th>
                <th>Référence</th>
                <th style="text-align:right">Montant</th>
                <?php if ($data['user']['Role'] === 'ADMIN'): ?>
                <th style="width:60px">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['paiements'])): ?>
            <tr>
                <td colspan="7" style="text-align:center;color:var(--muted);padding:30px">
                    <i class="ti ti-credit-card" style="font-size:24px;display:block;margin-bottom:8px"></i>
                    Aucun paiement enregistré
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($data['paiements'] as $p): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($p['Date_Paiement'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>factures/<?= $p['ID_Facture'] ?>" style="color:var(--primary);font-weight:600">
                        <?= htmlspecialchars($p['Reference'] ?? 'N/A') ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($p['Nom_Client'] ?? '—') ?></td>
                <td>
                    <span style="padding:3px 8px;border-radius:4px;font-size:11px;background:var(--bg)">
                        <?= htmlspecialchars($p['Mode_Paiement']) ?>
                    </span>
                </td>
                <td style="color:var(--muted)"><?= htmlspecialchars($p['Reference'] ?? '—') ?></td>
                <td style="text-align:right;font-weight:700;color:var(--success)">
                    <?= number_format($p['Montant'], 0, ',', ' ') ?> FCFA
                </td>
                <?php if ($data['user']['Role'] === 'ADMIN'): ?>
                <td>
                    <button class="tb-btn btn-sm" style="color:var(--danger)"
                            onclick="deletePaiement(<?= $p['ID_Paiement'] ?>, <?= $p['ID_Facture'] ?>)">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function deletePaiement(id, factureId) {
    if (!confirmDelete('Supprimer ce paiement ? Le solde de la facture sera recalculé.')) return;
    try {
        await apiCall(`paiements/${id}`, 'DELETE');
        showSuccess('Paiement supprimé');
        setTimeout(() => location.reload(), 800);
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
