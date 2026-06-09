<?php
$pageTitle   = 'Bons de livraison';
$currentPage = 'livraisons';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Bons de livraison</div>
        <div class="page-sub">Suivi des livraisons</div>
    </div>
    <button class="tb-btn btn-primary" onclick="openBLModal()">
        <i class="ti ti-plus"></i> Nouveau BL
    </button>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Facture</th>
                <th>Client</th>
                <th>Date livraison</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="bl-tbody">
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Chargement…</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal Nouveau BL -->
<div id="bl-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:90%;max-width:480px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700">Nouveau bon de livraison</h3>
            <button style="background:none;border:none;font-size:18px;cursor:pointer" onclick="closeBLModal()">×</button>
        </div>
        <form id="bl-form" onsubmit="saveBL(event)">
            <div class="field">
                <label>Facture associée *</label>
                <select name="ID_Facture" id="bl-facture" required>
                    <option value="">— Sélectionner une facture —</option>
                </select>
            </div>
            <div class="field">
                <label>Date de livraison</label>
                <input type="date" name="Date_Livraison" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="field">
                <label>Observations</label>
                <textarea name="Observations" rows="2" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px"></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px">
                <button type="button" class="tb-btn" onclick="closeBLModal()">Annuler</button>
                <button type="submit" class="tb-btn btn-primary"><i class="ti ti-check"></i> Créer le BL</button>
            </div>
        </form>
    </div>
</div>

<script>
const STATUT_COLORS = {
    'EN_COURS': 'var(--warning)',
    'LIVRE':    'var(--success)',
    'PARTIEL':  'var(--accent)'
};

document.addEventListener('DOMContentLoaded', () => {
    loadBLs();
    loadFacturesForSelect();
});

async function loadBLs() {
    try {
        const result = await apiCall('livraisons');
        const tbody  = document.getElementById('bl-tbody');
        const bls    = result.data || [];
        if (!bls.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Aucun bon de livraison</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        bls.forEach(bl => {
            const color = STATUT_COLORS[bl.Statut] || '#999';
            const tr    = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${bl.Reference}</strong></td>
                <td><a href="${window.BASE_URL}factures/${bl.ID_Facture}" style="color:var(--bl)">${bl.Ref_Facture || bl.ID_Facture}</a></td>
                <td>${bl.Nom_Client || '—'}</td>
                <td>${bl.Date_Livraison ? new Date(bl.Date_Livraison).toLocaleDateString('fr-FR') : '—'}</td>
                <td><span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:${color}1a;color:${color}">${bl.Statut}</span></td>
                <td>
                    <button class="tb-btn btn-sm" onclick="marquerLivre(${bl.ID_BL})"><i class="ti ti-check"></i> Livré</button>
                    <button class="tb-btn btn-sm" onclick="printBL(${bl.ID_BL})"><i class="ti ti-printer"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch(e) { console.error(e); }
}

async function loadFacturesForSelect() {
    try {
        const result  = await apiCall('factures');
        const select  = document.getElementById('bl-facture');
        (result.data || []).forEach(f => {
            const o = document.createElement('option');
            o.value       = f.ID_Facture;
            o.textContent = `${f.Reference} — ${f.Nom_Client || ''}`;
            select.appendChild(o);
        });
    } catch(e) { console.error(e); }
}

function openBLModal() {
    document.getElementById('bl-modal').style.display = 'flex';
}
function closeBLModal() {
    document.getElementById('bl-modal').style.display = 'none';
}

async function saveBL(event) {
    event.preventDefault();
    const fd   = new FormData(document.getElementById('bl-form'));
    const data = {};
    for (let [k,v] of fd.entries()) data[k] = v;
    try {
        await apiCall('livraisons/create', 'POST', data);
        showSuccess('Bon de livraison créé');
        closeBLModal();
        loadBLs();
    } catch(e) { console.error(e); }
}

async function marquerLivre(id) {
    if (!confirm('Marquer comme livré ?')) return;
    try {
        await apiCall('livraisons/' + id, 'POST', { Statut: 'LIVRE' });
        showSuccess('Livraison confirmée');
        loadBLs();
    } catch(e) { console.error(e); }
}

function printBL(id) {
    window.open(window.BASE_URL + 'api/livraisons/' + id + '/pdf', '_blank');
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
