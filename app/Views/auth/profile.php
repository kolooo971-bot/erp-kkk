<?php
$pageTitle = 'Mon profil';
$currentPage = 'profile';
ob_start();
?>

<div class="page-header">
    <div>
        <div class="page-title">Mon profil</div>
        <div class="page-sub">Gérer vos informations personnelles</div>
    </div>
</div>

<div class="container" style="max-width:700px">
    <!-- Profil utilisateur -->
    <div class="card" style="margin-bottom:24px">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
            Informations personnelles
        </div>
        
        <form id="profile-form" onsubmit="updateProfile(event)">
            <div class="form-row">
                <div class="field">
                    <label>Nom complet</label>
                    <input type="text" name="Nom_Complet" value="<?= htmlspecialchars($user['Nom_Complet'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="field">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['Email'] ?? '') ?>" disabled>
                    <small style="color:#999">Non modifiable</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="field">
                    <label>Rôle</label>
                    <input type="text" value="<?= ucfirst($user['Role'] ?? '') ?>" disabled>
                    <small style="color:#999">Assigné par un administrateur</small>
                </div>
            </div>
            
            <div style="text-align:right;margin-top:16px">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
    
    <!-- Sécurité -->
    <div class="card">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
            Sécurité
        </div>
        
        <form id="password-form" onsubmit="changePassword(event)">
            <div class="field" style="margin-bottom:14px">
                <label>Ancien mot de passe</label>
                <input type="password" name="old_password" required>
            </div>
            
            <div class="field" style="margin-bottom:14px">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required minlength="8">
                <small style="color:#999">Minimum 8 caractères</small>
            </div>
            
            <div class="field" style="margin-bottom:14px">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            
            <div style="text-align:right">
                <button type="submit" class="btn btn-warning">Changer le mot de passe</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function updateProfile(event) {
        event.preventDefault();
        const form = event.target;
        const nomComplet = form.querySelector('[name="Nom_Complet"]').value;
        
        try {
            const response = await apiCall('auth/profile', 'POST', {
                Nom_Complet: nomComplet
            });
            
            if (response.success) {
                showSuccess('Profil mis à jour avec succès');
                setTimeout(() => location.reload(), 1500);
            }
        } catch (error) {
            showError('Erreur lors de la mise à jour du profil');
        }
    }
    
    async function changePassword(event) {
        event.preventDefault();
        const form = event.target;
        const oldPassword = form.querySelector('[name="old_password"]').value;
        const newPassword = form.querySelector('[name="new_password"]').value;
        const confirmPassword = form.querySelector('[name="confirm_password"]').value;
        
        if (newPassword !== confirmPassword) {
            showError('Les mots de passe ne correspondent pas');
            return;
        }
        
        try {
            const response = await apiCall('auth/change-password', 'POST', {
                old_password: oldPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            });
            
            if (response.success) {
                showSuccess('Mot de passe changé avec succès');
                form.reset();
            }
        } catch (error) {
            showError('Erreur lors du changement de mot de passe');
        }
    }
</script>

<?php
$content = ob_get_clean();
$user = $data['user'] ?? $user ?? null;
include ROOT_PATH . 'app/Views/layouts/main.php';
?>