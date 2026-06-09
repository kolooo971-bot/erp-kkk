<?php
$pageTitle = 'Utilisateurs';
$currentPage = 'utilisateurs';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Gestion des utilisateurs</div>
        <div class="page-sub"><?= count($data['users']) ?> utilisateur(s)</div>
    </div>
    <button class="tb-btn btn-primary" onclick="openUserForm()">
        <i class="ti ti-plus"></i> Nouvel utilisateur
    </button>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table>
        <thead>
            <tr>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Dernière connexion</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['users'])): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:var(--muted);padding:30px">Aucun utilisateur</td>
            </tr>
            <?php else: ?>
            <?php foreach ($data['users'] as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                            <?= mb_strtoupper(mb_substr($u['Nom_Complet'], 0, 2)) ?>
                        </div>
                        <strong><?= htmlspecialchars($u['Nom_Complet']) ?></strong>
                    </div>
                </td>
                <td style="color:var(--muted)"><?= htmlspecialchars($u['Email']) ?></td>
                <td>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                        background:<?= $u['Role'] === 'ADMIN' ? 'var(--primary)1a' : 'var(--bg)' ?>;
                        color:<?= $u['Role'] === 'ADMIN' ? 'var(--primary)' : 'var(--muted)' ?>">
                        <?= $u['Role'] ?>
                    </span>
                </td>
                <td>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                        background:<?= $u['Actif'] ? 'var(--success)1a' : 'var(--danger)1a' ?>;
                        color:<?= $u['Actif'] ? 'var(--success)' : 'var(--danger)' ?>">
                        <?= $u['Actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td style="color:var(--muted)">
                    <?= $u['Derniere_Connexion'] ? date('d/m/Y H:i', strtotime($u['Derniere_Connexion'])) : 'Jamais' ?>
                </td>
                <td>
                    <div style="display:flex;gap:4px">
                        <button class="tb-btn btn-sm" onclick="editUser(<?= $u['ID_User'] ?>)">
                            <i class="ti ti-edit"></i>
                        </button>
                        <?php if ($u['Actif'] && $u['ID_User'] !== $data['user']['ID_User']): ?>
                        <button class="tb-btn btn-sm" style="color:var(--danger)"
                                onclick="disableUser(<?= $u['ID_User'] ?>, '<?= htmlspecialchars($u['Nom_Complet']) ?>')">
                            <i class="ti ti-user-off"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Formulaire Utilisateur -->
<div id="user-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:90%;max-width:460px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 id="modal-title" style="font-size:15px;font-weight:700">Nouvel utilisateur</h3>
            <button style="background:none;border:none;font-size:18px;cursor:pointer" onclick="closeUserForm()">×</button>
        </div>
        <form id="user-form" onsubmit="saveUser(event)">
            <input type="hidden" id="edit-user-id" value="">
            <div class="field">
                <label>Nom complet *</label>
                <input type="text" name="Nom_Complet" id="f-nom" required>
            </div>
            <div class="field">
                <label>Email *</label>
                <input type="email" name="Email" id="f-email" required>
            </div>
            <div class="field" id="field-password">
                <label>Mot de passe *</label>
                <input type="password" name="Mot_De_Passe" id="f-password" minlength="6">
            </div>
            <div class="field">
                <label>Rôle</label>
                <select name="Role" id="f-role">
                    <option value="EMPLOYE">Employé</option>
                    <option value="ADMIN">Administrateur</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="tb-btn" onclick="closeUserForm()">Annuler</button>
                <button type="submit" class="tb-btn btn-primary"><i class="ti ti-check"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingUserId = null;

function openUserForm() {
    editingUserId = null;
    document.getElementById('modal-title').textContent = 'Nouvel utilisateur';
    document.getElementById('user-form').reset();
    document.getElementById('edit-user-id').value = '';
    document.getElementById('field-password').style.display = '';
    document.getElementById('f-password').required = true;
    document.getElementById('user-modal').style.display = 'flex';
}

async function editUser(id) {
    try {
        const result = await apiCall(`utilisateurs/${id}`);
        const u = result.data;
        editingUserId = id;
        document.getElementById('modal-title').textContent = 'Modifier l\'utilisateur';
        document.getElementById('edit-user-id').value = id;
        document.getElementById('f-nom').value = u.Nom_Complet;
        document.getElementById('f-email').value = u.Email;
        document.getElementById('f-role').value = u.Role;
        document.getElementById('field-password').style.display = 'none';
        document.getElementById('f-password').required = false;
        document.getElementById('user-modal').style.display = 'flex';
    } catch (e) {
        console.error('Erreur:', e);
    }
}

function closeUserForm() {
    document.getElementById('user-modal').style.display = 'none';
}

async function saveUser(event) {
    event.preventDefault();
    const form = new FormHandler('user-form');
    const data = form.getData();

    try {
        if (editingUserId) {
            delete data.Mot_De_Passe;
            await apiCall(`utilisateurs/${editingUserId}`, 'PUT', data);
            showSuccess('Utilisateur modifié');
        } else {
            await apiCall('utilisateurs/create', 'POST', data);
            showSuccess('Utilisateur créé');
        }
        closeUserForm();
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        console.error('Erreur:', e);
    }
}

async function disableUser(id, nom) {
    if (!confirmDelete(`Désactiver l'utilisateur "${nom}" ?`)) return;
    try {
        await apiCall(`utilisateurs/${id}`, 'POST', { Actif: 0 });
        showSuccess('Utilisateur désactivé');
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
