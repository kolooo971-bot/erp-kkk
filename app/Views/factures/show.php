<?php
$pageTitle = 'Facture ' . ($data['facture']['Reference'] ?? '');
$currentPage = 'factures';
$facture = $data['facture'];
$lignes   = $data['lignes'];
$client   = $data['client'];
$paiements = $data['paiements'];
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Facture <?= htmlspecialchars($facture['Reference']) ?></div>
        <div class="page-sub">
            <?= htmlspecialchars($client['Nom_Client'] ?? '—') ?>
            &nbsp;·&nbsp;
            Émise le <?= date('d/m/Y', strtotime($facture['Date_Emission'])) ?>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= BASE_URL ?>factures" class="tb-btn">
            <i class="ti ti-arrow-left"></i> Retour
        </a>
        <a href="<?= BASE_URL ?>api/factures/<?= $facture['ID_Facture'] ?>/pdf" target="_blank" class="tb-btn">
            <i class="ti ti-file-type-pdf"></i> PDF
        </a>
        <?php if ($facture['Statut'] !== 'ANNULEE' && $facture['Statut'] !== 'SOLDEE'): ?>
        <button class="tb-btn btn-primary" onclick="openPaiementModal()">
            <i class="ti ti-credit-card"></i> Enregistrer un paiement
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Statut badge -->
<?php
$statutLabels = ['EN_ATTENTE'=>'En attente','PARTIELLE'=>'Partielle','SOLDEE'=>'Soldée','ANNULEE'=>'Annulée'];
$statutColors = ['EN_ATTENTE'=>'var(--warning)','PARTIELLE'=>'var(--accent)','SOLDEE'=>'var(--success)','ANNULEE'=>'var(--danger)'];
$statut = $facture['Statut'];
?>
<div style="margin-bottom:16px">
    <span style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $statutColors[$statut] ?? '#999' ?>1a;color:<?= $statutColors[$statut] ?? '#999' ?>">
        <?= $statutLabels[$statut] ?? $statut ?>
    </span>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <!-- Colonne gauche -->
    <div>
        <!-- Lignes facture -->
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
            <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--dark)">
                Détail des prestations
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th style="text-align:center">Qté</th>
                        <th style="text-align:right">P.U.</th>
                        <th style="text-align:right">Total</th>
                    </tr>
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
            <!-- Totaux -->
            <div style="padding:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <table style="width:260px;font-size:13px">
                    <tr>
                        <td style="padding:4px 0;color:var(--muted)">Sous-total HT</td>
                        <td style="text-align:right"><strong><?= number_format($facture['Montant_HT'], 0, ',', ' ') ?> FCFA</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:var(--muted)">TVA (<?= $facture['Taux_TVA'] ?>%)</td>
                        <td style="text-align:right"><?= number_format($facture['Montant_TVA'], 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <tr style="border-top:2px solid var(--border)">
                        <td style="padding:8px 0;font-weight:700;font-size:14px">Total TTC</td>
                        <td style="text-align:right;font-weight:800;font-size:15px;color:var(--primary)"><?= number_format($facture['Montant_TTC'], 0, ',', ' ') ?> FCFA</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Historique paiements -->
        <div class="card" style="padding:0;overflow:hidden">
            <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--dark)">
                Historique des paiements
            </div>
            <?php if (empty($paiements)): ?>
            <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">Aucun paiement enregistré</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Date</th><th>Mode</th><th>Référence</th><th style="text-align:right">Montant</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($paiements as $p): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($p['Date_Paiement'])) ?></td>
                        <td><?= htmlspecialchars($p['Mode_Paiement']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($p['Reference'] ?? '—') ?></td>
                        <td style="text-align:right;font-weight:700;color:var(--success)"><?= number_format($p['Montant'], 0, ',', ' ') ?> FCFA</td>
                        <?php if ($data['user']['Role'] === 'ADMIN'): ?>
                        <td><button class="tb-btn btn-sm" style="color:var(--danger)" onclick="deletePaiement(<?= $p['ID_Paiement'] ?>)"><i class="ti ti-trash"></i></button></td>
                        <?php else: ?><td></td><?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite -->
    <div>
        <!-- Résumé financier -->
        <div class="card" style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:12px">Résumé financier</div>
            <div style="display:grid;gap:10px;font-size:13px">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--muted)">Total TTC</span>
                    <strong><?= number_format($facture['Montant_TTC'], 0, ',', ' ') ?> FCFA</strong>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--muted)">Encaissé</span>
                    <strong style="color:var(--success)"><?= number_format($facture['Montant_Paye'], 0, ',', ' ') ?> FCFA</strong>
                </div>
                <div style="border-top:1px solid var(--border);padding-top:10px;display:flex;justify-content:space-between">
                    <span style="font-weight:700">Solde restant</span>
                    <strong style="color:var(--danger);font-size:15px"><?= number_format(($facture['Montant_TTC'] - $facture['Montant_Paye']), 0, ',', ' ') ?> FCFA</strong>
                </div>
            </div>
        </div>

        <!-- Info client -->
        <div class="card" style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:12px">Client</div>
            <div style="font-size:13px">
                <div style="font-weight:600"><?= htmlspecialchars($client['Nom_Client'] ?? '—') ?></div>
                <div style="color:var(--muted);margin-top:4px"><?= htmlspecialchars($client['Adresse'] ?? '') ?></div>
                <div style="color:var(--muted)"><?= htmlspecialchars($client['Telephone'] ?? '') ?></div>
            </div>
        </div>

        <!-- Dates -->
        <div class="card">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:12px">Dates</div>
            <table style="width:100%;font-size:13px">
                <tr><td style="color:var(--muted);padding:4px 0">Émission</td><td><?= date('d/m/Y', strtotime($facture['Date_Emission'])) ?></td></tr>
                <tr><td style="color:var(--muted);padding:4px 0">Échéance</td>
                    <td style="<?= (strtotime($facture['Date_Echeance']) < time() && $statut !== 'SOLDEE') ? 'color:var(--danger);font-weight:700' : '' ?>">
                        <?= date('d/m/Y', strtotime($facture['Date_Echeance'])) ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Modal Paiement -->
<div id="paiement-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:90%;max-width:440px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:15px;font-weight:700">Enregistrer un paiement</h3>
            <button style="background:none;border:none;font-size:18px;cursor:pointer" onclick="closePaiementModal()">×</button>
        </div>
        <form id="paiement-form" onsubmit="savePaiement(event)">
            <div class="field">
                <label>Montant *</label>
                <input type="number" name="Montant" step="1" min="1"
                       max="<?= $facture['Montant_TTC'] - $facture['Montant_Paye'] ?>" required
                       value="<?= $facture['Montant_TTC'] - $facture['Montant_Paye'] ?>">
            </div>
            <div class="field">
                <label>Mode de paiement</label>
                <select name="Mode_Paiement">
                    <option value="Especes">Espèces</option>
                    <option value="Cheque">Chèque</option>
                    <option value="Virement">Virement</option>
                    <option value="Orange_Money">Orange Money</option>
                    <option value="Moov_Money">Moov Money</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="field">
                <label>Date</label>
                <input type="date" name="Date_Paiement" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="field">
                <label>Référence / N° chèque</label>
                <input type="text" name="Reference" placeholder="Optionnel">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="tb-btn" onclick="closePaiementModal()">Annuler</button>
                <button type="submit" class="tb-btn btn-primary"><i class="ti ti-check"></i> Valider</button>
            </div>
        </form>
    </div>
</div>

<script>
const FACTURE_ID = <?= $facture['ID_Facture'] ?>;

function openPaiementModal() {
    document.getElementById('paiement-modal').style.display = 'flex';
}
function closePaiementModal() {
    document.getElementById('paiement-modal').style.display = 'none';
}

async function savePaiement(event) {
    event.preventDefault();
    const form = new FormHandler('paiement-form');
    const data = form.getData();
    data.ID_Facture = FACTURE_ID;
    data.Montant = parseFloat(data.Montant);

    try {
        await apiCall('paiements/create', 'POST', data);
        showSuccess('Paiement enregistré');
        closePaiementModal();
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        console.error('Erreur paiement:', e);
    }
}

async function deletePaiement(id) {
    if (!confirmDelete('Supprimer ce paiement ?')) return;
    try {
        await apiCall(`paiements/${id}`, 'DELETE');
        showSuccess('Paiement supprimé');
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        console.error('Erreur suppression:', e);
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
