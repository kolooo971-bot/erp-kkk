<?php
$pageTitle = isset($data['client']) ? 'Modifier le client' : 'Nouveau client';
$currentPage = 'clients';
$isEdit = isset($data['client']);
$client = $data['client'] ?? [];
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title"><?= $isEdit ? 'Modifier le client' : 'Nouveau client' ?></div>
        <div class="page-sub"><?= $isEdit ? htmlspecialchars($client['Nom_Client']) : 'Remplir les informations' ?></div>
    </div>
    <a href="<?= BASE_URL ?>clients" class="tb-btn">
        <i class="ti ti-arrow-left"></i> Retour
    </a>
</div>

<div class="card" style="max-width:640px">
    <form id="client-form" onsubmit="saveClient(event)">

        <div class="form-row">
            <div class="field">
                <label>Nom du client *</label>
                <input type="text" name="Nom_Client" required
                       value="<?= htmlspecialchars($client['Nom_Client'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Type de client</label>
                <select name="Type_Client">
                    <option value="ENTREPRISE" <?= ($client['Type_Client'] ?? '') === 'ENTREPRISE' ? 'selected' : '' ?>>Entreprise</option>
                    <option value="PARTICULIER" <?= ($client['Type_Client'] ?? '') === 'PARTICULIER' ? 'selected' : '' ?>>Particulier</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label>NIF</label>
                <input type="text" name="NIF" value="<?= htmlspecialchars($client['NIF'] ?? '') ?>">
            </div>
            <div class="field">
                <label>RCCM</label>
                <input type="text" name="RCCM" value="<?= htmlspecialchars($client['RCCM'] ?? '') ?>">
            </div>
        </div>

        <div class="field">
            <label>Adresse</label>
            <input type="text" name="Adresse" value="<?= htmlspecialchars($client['Adresse'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="field">
                <label>Téléphone *</label>
                <input type="text" name="Telephone" required
                       value="<?= htmlspecialchars($client['Telephone'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="Email" value="<?= htmlspecialchars($client['Email'] ?? '') ?>">
            </div>
        </div>

        <div class="field">
            <label>Personne de contact</label>
            <input type="text" name="Personne_Contact"
                   value="<?= htmlspecialchars($client['Personne_Contact'] ?? '') ?>">
        </div>

        <div class="field">
            <label>Observations</label>
            <textarea name="Observation" rows="3" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px;resize:vertical"><?= htmlspecialchars($client['Observation'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
            <a href="<?= BASE_URL ?>clients" class="tb-btn">Annuler</a>
            <button type="submit" class="tb-btn btn-primary">
                <i class="ti ti-check"></i> <?= $isEdit ? 'Enregistrer' : 'Créer le client' ?>
            </button>
        </div>
    </form>
</div>

<script>
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
const CLIENT_ID = <?= $isEdit ? $client['ID_Client'] : 'null' ?>;

async function saveClient(event) {
    event.preventDefault();
    const form = new FormHandler('client-form');
    const data = form.getData();

    try {
        if (IS_EDIT) {
            await apiCall(`clients/${CLIENT_ID}`, 'PUT', data);
            showSuccess('Client modifié avec succès');
        } else {
            await apiCall('clients/create', 'POST', data);
            showSuccess('Client créé avec succès');
        }
        setTimeout(() => { window.location = '<?= BASE_URL ?>clients'; }, 800);
    } catch (error) {
        console.error('Erreur:', error);
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>
