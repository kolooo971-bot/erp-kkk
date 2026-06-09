<?php
/**
 * Vue de connexion - ERP Kola
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si déjà connecté
if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
}

$pageTitle = 'Connexion';
$currentPage = 'login';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Connexion</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 28px;
        }
        .login-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-warning {
            background: #ffe;
            color: #996;
            border: 1px solid #fdd;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?= APP_NAME ?></h1>
            <p>Système de gestion d'entreprise</p>
        </div>
        
        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning">
                ⏱️ Votre session a expiré. Reconnectez-vous.
            </div>
        <?php endif; ?>
        
        <form id="login-form" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required placeholder="admin@kola.com">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
            </div>
            
            <button type="submit" class="btn-login">
                <span class="spinner" id="spinner"></span>
                <span id="btn-text">Se connecter</span>
            </button>
        </form>
        
        <div id="error-message"></div>
        
        <div class="login-footer">
            <p>© 2026 <?= APP_NAME ?>. Tous droits réservés.</p>
        </div>
    </div>
    
    <script>
        async function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btn-text');
            const errorDiv = document.getElementById('error-message');
            const form = document.getElementById('login-form');
            
            // Afficher le spinner
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Connexion en cours...';
            form.querySelector('button').disabled = true;
            
            try {
                const response = await fetch('<?= BASE_URL ?>api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        email: email,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirection réussie
                    window.location.href = data.data.redirect;
                } else {
                    // Afficher l'erreur
                    errorDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                errorDiv.innerHTML = `<div class="alert alert-danger">❌ Erreur de connexion: ${error.message}</div>`;
            } finally {
                // Masquer le spinner
                spinner.style.display = 'none';
                btnText.textContent = 'Se connecter';
                form.querySelector('button').disabled = false;
            }
        }
        
        // Focus sur l'email au chargement
        document.getElementById('email').focus();
    </script>
</body>
</html>