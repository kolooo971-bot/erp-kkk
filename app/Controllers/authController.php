<?php
class AuthController {

    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function loginForm() {
        if (!empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
        require_once ROOT_PATH . 'app/Views/auth/login.php';
    }

    public function login() {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
            exit;
        }

        require_once ROOT_PATH . 'app/Models/utilisateur.php';
        $model = new Utilisateur();
        $user  = $model->findOne('Email', $email);

        if (!$user || !$user['Actif']) {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
            exit;
        }

        if (!password_verify($password, $user['Mot_De_Passe'])) {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
            exit;
        }

        $_SESSION['user'] = [
            'ID_User'     => $user['ID_User'],
            'Nom_Complet' => $user['Nom_Complet'],
            'Email'       => $user['Email'],
            'Role'        => $user['Role'],
            'Actif'       => $user['Actif'],
        ];

        $model->update($user['ID_User'], ['Derniere_Connexion' => date('Y-m-d H:i:s')]);

        echo json_encode(['success' => true, 'message' => 'Connecté', 'data' => ['redirect' => BASE_URL . 'dashboard']]);
        exit;
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}
?>