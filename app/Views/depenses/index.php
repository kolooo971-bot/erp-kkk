<?php
$pageTitle   = 'Dépenses';
$currentPage = 'depenses';
ob_start();
?>
<div class="page-header">
    <div>
        <div class="page-title">Dépenses</div>
        <div class="page-sub">Suivi des décaissements</div>
    </div>
    <button class="tb-btn btn-primary" onclick="openDepenseModal()">
        <i class="ti ti-plus"></i> Nouvelle dépense
    </button>
</div>

<!-- KPI -->
<div class="kpi-row" id="depense-kpis">
    <div class="kpi orange"><div class="kpi-label">Ce mois</div><div class="kpi-value" id="kpi-mois" style="font-size:17px">0</div><div class="kpi-sub">FCFA décaissés</div></div>
    <div class="kpi red"><div class="kpi-label">Cette année</div><div class="kpi-value" id="kpi-annee" style="font-size:17px">0</div><div class="kpi-sub">FCFA total</div></div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table>
        <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th style="text-align:right">Montant</th><th>Actions</th></tr></thead>
        <tbody id="depenses-tbody"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">Chargement…</td></tr></tbody>
    </table>
</div>

<!-- Modal -->
<div id="depense-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:90%;max-width:440px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:15px;font-weight:700">Nouvelle dépense</h3>
            <button onclick="closeDepenseModal()" style="background:none;border:none;font-size:20px;cursor:pointer">×</button>
        </div>
        <form id="depense-form" onsubmit="saveDepense(event)">
            <div class="field"><label>Libellé *</label><input type="text" name="Libelle" required placeholder="Ex: Achat papier A4"></div>
            <div class="form-row">
                <div class="field"><label>Montant *</label><input type="number" name="Montant" required min="1" step="1" placeholder="FCFA"></div>
                <div class="field"><label>Date</label><input type="date" name="Date_Depense" value="<?= date('Y-m-d') ?>"></div>
            </div>
            <div class="field">
                <label>Catégorie</label>
                <select name="Categorie">
                    <option value="Fournitures">Fournitures</option>
                    <option value="Transport">Transport</option>
                    <option value="Loyer">Loyer</option>
                    <option value="Salaires">Salaires</option>
                    <option value="Communication">Communication</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="field"><label>Observation</label><input type="text" name="Observation" placeholder="Optionnel"></div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="tb-btn" onclick="closeDepenseModal()">Annuler</button>
                <button type="submit" class="tb-btn btn-primary"><i class="ti ti-check"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadDepenses);

async function loadDepenses() {
    try {
        const r = await apiCall('tresorerie');
        const depenses = r.data?.depenses_detail || [];
        renderDepenses(depenses);

        // KPIs
        const totalMois = r.data?.depenses || 0;
        document.getElementById('kpi-mois').textContent = totalMois.toLocaleString('fr-FR');

        // Total année
        const stmtAnnee = await apiCall('tresorerie');
        document.getElementById('kpi-annee').textContent = totalMois.toLocaleString('fr-FR');
    } catch(e) { console.error(e); }
}

function renderDepenses(depenses) {
    const tbody = document.getElementById('depenses-tbody');
    if (!depenses.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">Aucune dépense ce mois</td></tr>';
        return;
    }
    tbody.innerHTML = depenses.map(d => `
        <tr>
            <td style="color:var(--muted)">${formatDate(d.Date_Depense)}</td>
            <td><strong>${d.Libelle}</strong></td>
            <td><span style="padding:3px 8px;background:var(--bg);border-radius:4px;font-size:11px">${d.Categorie || '—'}</span></td>
            <td style="text-align:right;font-weight:700;color:var(--danger)">${formatCurrency(d.Montant)}</td>
            <td></td>
        </tr>`).join('');
}

function openDepenseModal() { document.getElementById('depense-modal').style.display = 'flex'; }
function closeDepenseModal() { document.getElementById('depense-modal').style.display = 'none'; }

async function saveDepense(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('depense-form'));
    const data = {};
    for (let [k,v] of formData.entries()) data[k] = v;
    data.Montant = parseFloat(data.Montant);

    try {
        await apiCall('tresorerie/depenses', 'POST', data);
        showSuccess('Dépense enregistrée');
        closeDepenseModal();
        document.getElementById('depense-form').reset();
        document.querySelector('[name="Date_Depense"]').value = new Date().toISOString().split('T')[0];
        loadDepenses();
    } catch(err) { console.error(err); }
}
</script>
<?php
$content = ob_get_clean();
$user    = $data['user'] ?? $user ?? null;
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
