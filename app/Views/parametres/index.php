<?php
$pageTitle   = 'Paramètres entreprise';
$currentPage = 'parametres';
$p = $data['parametres'] ?? [];
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Paramètres de l'entreprise</div>
        <div class="page-sub">Informations affichées sur les factures et proformas</div>
    </div>
</div>

<div class="card" style="max-width:700px">
    <form id="param-form" onsubmit="saveParams(event)">

        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
            Identité
        </div>

        <!-- Logo upload -->
        <div class="field" style="margin-bottom:16px">
            <label>Logo de l'entreprise</label>
            <div style="display:flex;align-items:center;gap:14px">
                <div id="logo-preview" style="width:64px;height:64px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                    <?php if (!empty($p['Logo']) && file_exists(ROOT_PATH . 'public/' . $p['Logo'])): ?>
                        <img src="<?= BASE_URL . $p['Logo'] ?>" style="width:100%;height:100%;object-fit:contain">
                    <?php else: ?>
                        <i class="ti ti-photo" style="font-size:24px;color:var(--muted)"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="file" id="logo-file" accept="image/*" style="display:none" onchange="uploadLogo(this)">
                    <button type="button" class="tb-btn" onclick="document.getElementById('logo-file').click()">
                        <i class="ti ti-upload"></i> Choisir un logo
                    </button>
                    <div style="font-size:11px;color:var(--muted);margin-top:4px">PNG, JPG, SVG — max 1 000 Ko</div>
                </div>
            </div>
            <input type="hidden" name="Logo" id="logo-path" value="<?= htmlspecialchars($p['Logo'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="field">
                <label>Nom de l'entreprise *</label>
                <input type="text" name="Nom_Entreprise" value="<?= htmlspecialchars($p['Nom_Entreprise'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Sigle / Abréviation</label>
                <input type="text" name="Sigle" value="<?= htmlspecialchars($p['Sigle'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label>NIF</label>
                <input type="text" name="NIF" value="<?= htmlspecialchars($p['NIF'] ?? '') ?>">
            </div>
            <div class="field">
                <label>RCCM</label>
                <input type="text" name="RCCM" value="<?= htmlspecialchars($p['RCCM'] ?? '') ?>">
            </div>
        </div>

        <div style="font-size:13px;font-weight:700;color:var(--dark);margin:18px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
            Contact
        </div>

        <div class="field">
            <label>Adresse</label>
            <input type="text" name="Adresse" value="<?= htmlspecialchars($p['Adresse'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="field">
                <label>Téléphone principal</label>
                <input type="text" name="Telephone_Principal" value="<?= htmlspecialchars($p['Telephone_Principal'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Téléphone secondaire</label>
                <input type="text" name="Telephone_Secondaire" value="<?= htmlspecialchars($p['Telephone_Secondaire'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label>Email</label>
                <input type="email" name="Email" value="<?= htmlspecialchars($p['Email'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Site web</label>
                <input type="text" name="Site_Web" value="<?= htmlspecialchars($p['Site_Web'] ?? '') ?>">
            </div>
        </div>

        <div style="font-size:13px;font-weight:700;color:var(--dark);margin:18px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
            Paiement
        </div>

        <div class="field">
            <label>Coordonnées bancaires (IBAN / RIB)</label>
            <textarea name="Coordonnee_Bancaire" rows="2" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px"><?= htmlspecialchars($p['Coordonnee_Bancaire'] ?? '') ?></textarea>
        </div>

        <div class="field">
            <label>Mobile Money (Orange / Moov)</label>
            <input type="text" name="Moyen_Paiement_Mobile" value="<?= htmlspecialchars($p['Moyen_Paiement_Mobile'] ?? '') ?>">
        </div>

        <div class="field">
            <label>Mentions légales (bas de facture)</label>
            <textarea name="Mentions_Legales" rows="3" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px"><?= htmlspecialchars($p['Mentions_Legales'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px">
            <button type="submit" class="tb-btn btn-primary" id="btn-save">
                <i class="ti ti-check"></i> Sauvegarder
            </button>
        </div>
    </form>
</div>

<script>
async function uploadLogo(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 1024000) { alert('Logo  lourd (max 1 000 Ko)'); return; }

    const fd = new FormData();
    fd.append('logo', file);

    try {
        const resp = await fetch(window.BASE_URL + 'api/parametres/upload-logo', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('logo-path').value = data.data.path;
            const preview = document.getElementById('logo-preview');
            preview.innerHTML = `<img src="${window.BASE_URL + data.data.path}" style="width:100%;height:100%;object-fit:contain">`;
            showSuccess('Logo téléversé');
        } else {
            alert('Erreur : ' + data.message);
        }
    } catch(e) { console.error(e); }
}

async function saveParams(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-save');
    btn.disabled = true;

    const fd   = new FormData(document.getElementById('param-form'));
    const data = {};
    for (let [k,v] of fd.entries()) data[k] = v;

    try {
        await apiCall('parametres/update', 'POST', data);
        showSuccess('Paramètres sauvegardés');
    } catch(e) {
        console.error(e);
    } finally {
        btn.disabled = false;
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
