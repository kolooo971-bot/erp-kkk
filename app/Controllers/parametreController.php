<?php
class ParametreController extends BaseController {

    public function index() {
        if ($this->isApiRequest()) {
            $p = new ParametreEntreprise();
            $this->success('Paramètres', $p->getParametres());
        }
        $parametre = new ParametreEntreprise();
        $data = ['parametres' => $parametre->getParametres(), 'user' => $this->user];
        require_once ROOT_PATH . 'app/Views/parametres/index.php';
    }

    public function update() {
        $d = $this->getJsonData();
        $p = new ParametreEntreprise();
        if ($p->updateParametres($d)) $this->success('Paramètres sauvegardés');
        else $this->sendError('Erreur sauvegarde', 500);
    }

    public function uploadLogo() {
        // Vérifier qu'un fichier a été envoyé
        if (empty($_FILES['logo'])) {
            $this->sendError('Aucun fichier reçu', 400);
            return;
        }

        $file = $_FILES['logo'];

        // Vérifier les erreurs PHP d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop lourd (limite php.ini)',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop lourd (limite formulaire)',
                UPLOAD_ERR_PARTIAL    => 'Téléversement partiel',
                UPLOAD_ERR_NO_FILE    => 'Aucun fichier envoyé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
                UPLOAD_ERR_EXTENSION  => 'Extension PHP bloquante',
            ];
            $msg = $errors[$file['error']] ?? 'Erreur upload (code ' . $file['error'] . ')';
            $this->sendError($msg, 400);
            return;
        }

        // Vérifier le type MIME réel (pas celui déclaré par le client)
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $this->sendError('Format non autorisé : ' . $mime, 400);
            return;
        }

        // Vérifier la taille (max 1 Mo)
        if ($file['size'] > 1024000) {
            $this->sendError('Fichier lourd (max 1 000 Ko)', 400);
            return;
        }

        // Préparer le dossier destination
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'logo_' . time() . '.' . $ext;
        $dir      = rtrim(ROOT_PATH, '/') . '/public/uploads/';

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $this->sendError('Impossible de créer le dossier uploads', 500);
                return;
            }
        }

        if (!is_writable($dir)) {
            $this->sendError('Dossier uploads non accessible en écriture', 500);
            return;
        }

        // Déplacer le fichier
        $dest = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->sendError('Échec du déplacement du fichier', 500);
            return;
        }

        // Sauvegarder le chemin en base
        $path = 'uploads/' . $filename;
        $p    = new ParametreEntreprise();
        $param = $p->getParametres();

        if ($param) {
            $p->update($param['ID_Entreprise'], ['Logo' => $path]);
        } else {
            $p->create(['Logo' => $path, 'Nom_Entreprise' => 'Mon Entreprise']);
        }

        $this->success('Logo téléversé avec succès', ['path' => $path]);
    }
}
?>