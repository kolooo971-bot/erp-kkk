<?php
$pageTitle = 'Fiche client';
$currentPage = 'clients';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title"><?= htmlspecialchars($data['client']['Nom_Client']) ?></div>
        <div class="page-sub">
            <?= $data['client']['Type_Client'] === 'ENTREPRISE' ? 'Entreprise' : 'Particulier' ?>
            <?php if (!empty($data['client']['NIF'])): ?>
                &nbsp;·&nbsp; NIF : <?= htmlspecialchars($data['client']['NIF']) ?>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= BASE_URL ?>clients" class="tb-btn">
            <i class="ti ti-arrow-left"></i> Retour
        </a>
        <button class="tb-btn btn-primary" onclick="editClient(<?= $data['client']['ID_Client'] ?>)">
            <i class="ti ti-edit"></i> Modifier
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Infos client -->
    <div class="card">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:16px">
            <i class="ti ti-user"></i> Informations
        </div>
        <table style="width:100%;font-size:13px">
            <tr><td style="color:var(--muted);padding:5px 0;width:40%">Nom</td><td><?= htmlspecialchars($data['client']['Nom_Client']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">Type</td><td><?= htmlspecialchars($data['client']['Type_Client'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">NIF</td><td><?= htmlspecialchars($data['client']['NIF'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">RCCM</td><td><?= htmlspecialchars($data['client']['RCCM'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">Adresse</td><td><?= htmlspecialchars($data['client']['Adresse'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">Téléphone</td><td><?= htmlspecialchars($data['client']['Telephone'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">Email</td><td><?= htmlspecialchars($data['client']['Email'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0">Contact</td><td><?= htmlspecialchars($data['client']['Personne_Contact'] ?? '—') ?></td></tr>
        </table>
    </div>

    <!-- Statistiques client -->
    <div class="card">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:16px">
            <i class="ti ti-chart-bar"></i> Statistiques
        </div>
        <?php $stats = $data['stats']; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="background:var(--bg);border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:20px;font-weight:800;color:var(--primary)"><?= $stats['nb_factures'] ?? 0 ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">Factures</div>
            </div>
            <div style="background:var(--bg);border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:20px;font-weight:800;color:var(--success)"><?= number_format($stats['ca_total'] ?? 0, 0, ',', ' ') ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">CA total (FCFA)</div>
            </div>
            <div style="background:var(--bg);border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:20px;font-weight:800;color:var(--accent)"><?= number_format($stats['paye'] ?? 0, 0, ',', ' ') ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">Encaissé (FCFA)</div>
            </div>
            <div style="background:var(--bg);border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:20px;font-weight:800;color:var(--danger)"><?= number_format($stats['solde_restant'] ?? 0, 0, ',', ' ') ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px">Solde dû (FCFA)</div>
            </div>
        </div>
    </div>
</div>

<!-- Factures du client -->
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--dark)">
        <i class="ti ti-receipt"></i> Factures
    </div>
    <table id="factures-client-table">
        <thead>
            <tr>
                <th>Référence</th>
                <th>Date</th>
                <th>Objet</th>
                <th>Montant TTC</th>
                <th>Payé</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="factures-client-tbody">
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px">Chargement…</td></tr>
        </tbody>
    </table>
</div>

<script>
const CLIENT_ID = <?= $data['client']['ID_Client'] ?>;

document.addEventListener('DOMContentLoaded', () => {
    loadClientFactures();
});

async function loadClientFactures() {
    try {
        const result = await apiCall('factures?client=' + CLIENT_ID);
        const tbody = document.getElementById('factures-client-tbody');
        const factures = result.data || [];

        if (!factures.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px">Aucune facture pour ce client</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        factures.forEach(f => {
            const statutClass = {
                'SOLDEE': 'badge-success',
                'EN_ATTENTE': 'badge-warning',
                'PARTIELLE': 'badge-info',
                'ANNULEE': 'badge-danger'
            }[f.Statut] || 'badge-secondary';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${f.Reference}</strong></td>
                <td>${formatDate(f.Date_Emission)}</td>
                <td>${f.Objet || '—'}</td>
                <td><strong>${formatCurrency(f.Montant_TTC)}</strong></td>
                <td style="color:var(--success)">${formatCurrency(f.Montant_Paye)}</td>
                <td><span class="badge ${statutClass}">${f.Statut}</span></td>
                <td>
                    <a href="<?= BASE_URL ?>factures/${f.ID_Facture}" class="tb-btn btn-sm">
                        <i class="ti ti-eye"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        console.error('Erreur chargement factures:', e);
    }
}

function editClient(id) {
    window.location = `<?= BASE_URL ?>clients/${id}/edit`;
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
