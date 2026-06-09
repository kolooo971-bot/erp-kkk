<?php
$pageTitle   = 'Catalogue de services';
$currentPage = 'catalogue';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Catalogue de services</div>
        <div class="page-sub">Prestations et tarifs</div>
    </div>
    <button class="tb-btn btn-primary" onclick="openServiceModal()">
        <i class="ti ti-plus"></i> Nouveau service
    </button>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table>
        <thead>
            <tr>
                <th>Nom du service</th>
                <th>Description</th>
                <th>Unité</th>
                <th style="text-align:right">Prix unitaire</th>
                <th style="width:80px">Actions</th>
            </tr>
        </thead>
        <tbody id="catalogue-tbody">
            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">Chargement…</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="service-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:90%;max-width:460px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 id="modal-title" style="font-size:15px;font-weight:700">Nouveau service</h3>
            <button style="background:none;border:none;font-size:18px;cursor:pointer" onclick="closeServiceModal()">×</button>
        </div>
        <form id="service-form" onsubmit="saveService(event)">
            <input type="hidden" id="edit-service-id">
            <div class="field"><label>Nom *</label><input type="text" name="Nom_Service" id="f-nom" required></div>
            <div class="field"><label>Description</label><textarea name="Description" id="f-desc" rows="2" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px"></textarea></div>
            <div class="form-row">
                <div class="field">
                    <label>Prix unitaire (FCFA)</label>
                    <input type="number" name="Prix_Unitaire" id="f-prix" value="0" min="0">
                </div>
                <div class="field">
                    <label>Unité</label>
                    <select name="Unite" id="f-unite">
                        <option value="Forfait">Forfait</option>
                        <option value="m²">m²</option>
                        <option value="Unité">Unité</option>
                        <option value="Page">Page</option>
                        <option value="Heure">Heure</option>
                        <option value="Jour">Jour</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px">
                <button type="button" class="tb-btn" onclick="closeServiceModal()">Annuler</button>
                <button type="submit" class="tb-btn btn-primary"><i class="ti ti-check"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingServiceId = null;

document.addEventListener('DOMContentLoaded', loadCatalogue);

async function loadCatalogue() {
    try {
        const result = await apiCall('catalogue');
        const tbody  = document.getElementById('catalogue-tbody');
        const items  = result.data || [];
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">Aucun service</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        items.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-weight:600">${s.Nom_Service}</td>
                <td style="color:var(--muted);font-size:12px">${s.Description || '—'}</td>
                <td>${s.Unite || 'Forfait'}</td>
                <td style="text-align:right;font-weight:600;color:var(--bl)">${formatCurrency(s.Prix_Unitaire)}</td>
                <td>
                    <button class="tb-btn btn-sm" onclick="editService(${s.ID_Service},'${s.Nom_Service.replace(/'/g,"\\'")}','${(s.Description||'').replace(/'/g,"\\'")}',${s.Prix_Unitaire},'${s.Unite||'Forfait'}')">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button class="tb-btn btn-sm" style="color:var(--danger)" onclick="deleteService(${s.ID_Service})">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch(e) { console.error(e); }
}

function openServiceModal() {
    editingServiceId = null;
    document.getElementById('modal-title').textContent = 'Nouveau service';
    document.getElementById('service-form').reset();
    document.getElementById('edit-service-id').value = '';
    document.getElementById('service-modal').style.display = 'flex';
}

function editService(id, nom, desc, prix, unite) {
    editingServiceId = id;
    document.getElementById('modal-title').textContent = 'Modifier le service';
    document.getElementById('f-nom').value   = nom;
    document.getElementById('f-desc').value  = desc;
    document.getElementById('f-prix').value  = prix;
    document.getElementById('f-unite').value = unite;
    document.getElementById('edit-service-id').value = id;
    document.getElementById('service-modal').style.display = 'flex';
}

function closeServiceModal() {
    document.getElementById('service-modal').style.display = 'none';
}

async function saveService(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('service-form'));
    const data = {};
    for (let [k,v] of fd.entries()) data[k] = v;
    data.Prix_Unitaire = parseFloat(data.Prix_Unitaire) || 0;
    try {
        if (editingServiceId) {
            await apiCall('catalogue/' + editingServiceId, 'PUT', data);
        } else {
            await apiCall('catalogue/create', 'POST', data);
        }
        showSuccess('Service enregistré');
        closeServiceModal();
        loadCatalogue();
    } catch(e) { console.error(e); }
}

async function deleteService(id) {
    if (!confirmDelete('Supprimer ce service ?')) return;
    try {
        await apiCall('catalogue/' + id, 'DELETE');
        showSuccess('Service supprimé');
        loadCatalogue();
    } catch(e) { console.error(e); }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
