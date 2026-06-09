<?php
$pageTitle   = 'Nouvelle facture';
$currentPage = 'factures';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Nouvelle facture</div>
        <div class="page-sub">Remplir les informations</div>
    </div>
    <a href="<?= BASE_URL ?>factures" class="tb-btn">
        <i class="ti ti-arrow-left"></i> Retour
    </a>
</div>

<form id="facture-form" onsubmit="saveFacture(event)">
    <div class="grid2" style="margin-bottom:16px">

        <!-- Infos générales -->
        <div class="card">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:14px">Informations générales</div>

            <div class="field">
                <label>Client *</label>
                <select name="ID_Client" id="facture-client" required>
                    <option value="">— Sélectionner un client —</option>
                </select>
            </div>

            <div class="field">
                <label>Objet *</label>
                <input type="text" name="Objet" placeholder="Description du projet" required>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>Date d'émission</label>
                    <input type="date" name="Date_Emission" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="field">
                    <label>Date d'échéance</label>
                    <input type="date" name="Date_Echeance" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <div class="field">
                <label>TVA (%)</label>
                <select name="Taux_TVA" onchange="calcTotals()">
                    <option value="0">0%</option>
                    <option value="18" selected>18%</option>
                    <option value="5.5">5.5%</option>
                </select>
            </div>
        </div>

        <!-- Totaux -->
        <div class="card">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:14px">Totaux</div>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px">
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--muted)">Sous-total HT</span>
                    <span id="montant-ht" style="font-weight:600">0 FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--muted)">TVA</span>
                    <span id="montant-tva" style="font-weight:600">0 FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px;background:var(--bl3);border-radius:var(--rads)">
                    <span style="font-weight:700;color:var(--dark)">Total TTC</span>
                    <span id="montant-ttc" style="font-weight:800;font-size:16px;color:var(--bl)">0 FCFA</span>
                </div>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="tb-btn btn-primary" style="width:100%;justify-content:center" id="btn-save">
                    <i class="ti ti-check"></i> Créer la facture
                </button>
            </div>
        </div>
    </div>

    <!-- Lignes -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 16px;background:var(--bl);display:flex;align-items:center;justify-content:space-between">
            <span style="color:white;font-size:13px;font-weight:600">Lignes de prestations</span>
            <button type="button" class="tb-btn btn-sm" style="background:var(--or);color:white;border-color:var(--or)" onclick="addLine()">
                <i class="ti ti-plus"></i> Ajouter ligne
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Désignation</th>
                    <th style="width:80px">Qté</th>
                    <th style="width:120px">Prix unitaire</th>
                    <th style="width:120px">Total</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody id="facture-lines"></tbody>
        </table>
    </div>
</form>

<script>
let lineCount = 0;

document.addEventListener('DOMContentLoaded', async () => {
    await loadClientsForSelect();
    addLine();
});

async function loadClientsForSelect() {
    try {
        const result = await apiCall('clients');
        const select = document.getElementById('facture-client');
        (result.data || []).forEach(c => {
            const o = document.createElement('option');
            o.value = c.ID_Client;
            o.textContent = c.Nom_Client;
            select.appendChild(o);
        });
    } catch(e) { console.error(e); }
}

function addLine() {
    const n = ++lineCount;
    const tr = document.createElement('tr');
    tr.id = 'line-' + n;
    tr.innerHTML = `
        <td style="color:var(--muted)">${n}</td>
        <td>
            <input type="text" name="designation_${n}" placeholder="Description de la prestation"
                   style="border:1px solid var(--border);border-radius:4px;padding:5px 8px;width:100%" required>
        </td>
        <td>
            <input type="number" name="qty_${n}" value="1" min="0.01" step="0.01"
                   onchange="calcLine(${n})"
                   style="width:70px;border:1px solid var(--border);border-radius:4px;padding:5px 6px">
        </td>
        <td>
            <input type="number" name="price_${n}" value="0" min="0" step="1"
                   onchange="calcLine(${n})"
                   style="width:110px;border:1px solid var(--border);border-radius:4px;padding:5px 6px">
        </td>
        <td style="font-weight:600;text-align:right" id="total-${n}">0 FCFA</td>
        <td>
            <button type="button" class="tb-btn btn-sm" style="color:var(--danger)" onclick="removeLine(${n})">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('facture-lines').appendChild(tr);
}

function removeLine(n) {
    const el = document.getElementById('line-' + n);
    if (el) { el.remove(); calcTotals(); }
}

function calcLine(n) {
    const qty   = parseFloat(document.querySelector(`[name="qty_${n}"]`)?.value) || 0;
    const price = parseFloat(document.querySelector(`[name="price_${n}"]`)?.value) || 0;
    const total = qty * price;
    const el = document.getElementById('total-' + n);
    if (el) el.textContent = formatCurrency(total);
    calcTotals();
}

function calcTotals() {
    let ht = 0;
    for (let i = 1; i <= lineCount; i++) {
        const qty   = parseFloat(document.querySelector(`[name="qty_${i}"]`)?.value) || 0;
        const price = parseFloat(document.querySelector(`[name="price_${i}"]`)?.value) || 0;
        ht += qty * price;
    }
    const tva = parseFloat(document.querySelector('[name="Taux_TVA"]').value) || 0;
    const tvaAmt = ht * tva / 100;
    const ttc    = ht + tvaAmt;
    document.getElementById('montant-ht').textContent  = formatCurrency(ht);
    document.getElementById('montant-tva').textContent = formatCurrency(tvaAmt);
    document.getElementById('montant-ttc').textContent = formatCurrency(ttc);

    // champs cachés
    ['Montant_HT','Montant_TVA','Montant_TTC'].forEach(n => {
        let el = document.querySelector(`input[name="${n}"]`);
        if (!el) { el = document.createElement('input'); el.type='hidden'; el.name=n; document.getElementById('facture-form').appendChild(el); }
        el.value = n==='Montant_HT' ? ht : n==='Montant_TVA' ? tvaAmt : ttc;
    });
}

async function saveFacture(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader"></i> Enregistrement…';

    const formData = new FormData(document.getElementById('facture-form'));
    const data = {};
    for (let [k,v] of formData.entries()) data[k] = v;

    try {
        const result = await apiCall('factures/create', 'POST', data);
        showSuccess('Facture créée : ' + result.data.reference);
        setTimeout(() => { window.location = window.BASE_URL + 'factures/' + result.data.facture_id; }, 800);
    } catch(e) {
        console.error(e);
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Créer la facture';
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
