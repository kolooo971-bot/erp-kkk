<?php
// Tout en haut de api.php, AVANT ob_start()
if (session_status() === PHP_SESSION_NONE) session_start();
/**
 * API Router — sans authentification
 * Toute sortie PHP inattendue est capturée et retournée en JSON
 */
ob_start(); // Capturer tout output PHP (warnings, errors)
class ApiRouter {
    private $method;
    private $endpoint;
    private $params;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->parseUrl();
    }

    private function parseUrl() {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pos = strpos($url, '/api/');
        $url = $pos !== false ? substr($url, $pos + 5) : '';
        $url = trim($url, '/');
        $parts = explode('/', $url);
        $this->endpoint = $parts[0] ?? '';
        $this->params   = array_slice($parts, 1);
    }

    public function route() {
        // Vider tout output PHP parasite (warnings xdebug etc)
        ob_end_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        // DEBUG TEMPORAIRE - retire après
    error_log("SESSION dans API: " . json_encode($_SESSION['user'] ?? 'VIDE'));
    error_log("ID_User: " . ($_SESSION['user']['ID_User'] ?? 'NULL'));

        
        // ── CLIENTS ──────────────────────────────────────────────────
        if ($this->endpoint === 'clients') {
            $c = new ClientController();
            if ($this->method === 'GET') {
                if (empty($this->params))                                return $c->index();
                if ($this->params[0] === 'search')                       return $c->search();
                if (is_numeric($this->params[0]))                        return $c->show($this->params[0]);
            }
            if ($this->method === 'POST') {
                if (($this->params[0] ?? '') === 'create')               return $c->store();
                if (is_numeric($this->params[0] ?? ''))                  return $c->update($this->params[0]);
            }
            if ($this->method === 'PUT'    && is_numeric($this->params[0] ?? '')) return $c->update($this->params[0]);
            if ($this->method === 'DELETE' && is_numeric($this->params[0] ?? '')) return $c->delete($this->params[0]);
        }

        // ── PROFORMAS ─────────────────────────────────────────────────
        if ($this->endpoint === 'proformas') {
            $c = new ProformaController();
            if ($this->method === 'GET') {
                if (empty($this->params))                                return $c->index();
                if ($this->params[0] === 'search')                       return $c->search();
                if (is_numeric($this->params[0]))                        return $c->show($this->params[0]);
            }
            if ($this->method === 'POST') {
                if (($this->params[0] ?? '') === 'create')               return $c->store();
                if (is_numeric($this->params[0] ?? '') && ($this->params[1] ?? '') === 'convert') return $c->convert($this->params[0]);
                if (is_numeric($this->params[0] ?? ''))                  return $c->update($this->params[0]);
            }
            if ($this->method === 'PUT'    && is_numeric($this->params[0] ?? '')) return $c->update($this->params[0]);
            if ($this->method === 'DELETE' && is_numeric($this->params[0] ?? '')) return $c->delete($this->params[0]);
        }

        // ── FACTURES ──────────────────────────────────────────────────
        if ($this->endpoint === 'factures') {
            $c = new FactureController();
            if ($this->method === 'GET') {
                if (empty($this->params))                                return $c->index();
                if ($this->params[0] === 'search')                       return $c->search();
                if ($this->params[0] === 'stats')                        return $c->stats();
                if ($this->params[0] === 'en-retard')                    return $c->enRetard();
                if (is_numeric($this->params[0] ?? '') && ($this->params[1] ?? '') === 'pdf') return $c->generatePDF($this->params[0]);
                if (is_numeric($this->params[0] ?? ''))                  return $c->show($this->params[0]);
            }
            if ($this->method === 'POST') {
                if (($this->params[0] ?? '') === 'create')               return $c->store();
                if (is_numeric($this->params[0] ?? '') && ($this->params[1] ?? '') === 'cancel') return $c->cancel($this->params[0]);
                if (is_numeric($this->params[0] ?? ''))                  return $c->update($this->params[0]);
            }
            if ($this->method === 'PUT'    && is_numeric($this->params[0] ?? '')) return $c->update($this->params[0]);
        }

        // ── PAIEMENTS ─────────────────────────────────────────────────
        if ($this->endpoint === 'paiements') {
            $c = new PaiementController();
            if ($this->method === 'GET'    && empty($this->params))      return $c->index();
            if ($this->method === 'POST'   && ($this->params[0] ?? '') === 'create') return $c->store();
            if ($this->method === 'DELETE' && is_numeric($this->params[0] ?? '')) return $c->delete($this->params[0]);
            if ($this->method === 'POST'   && is_numeric($this->params[0] ?? '')) return $c->delete($this->params[0]);
        }

        // ── TRÉSORERIE ────────────────────────────────────────────────
        if ($this->endpoint === 'tresorerie') {
            $c = new TresorieController();
            if ($this->method === 'GET') {
                if (empty($this->params))                                return $c->stats();
                if ($this->params[0] === 'paiements-mode')               return $c->paiementsParMode();
                if ($this->params[0] === 'depenses')                     return $c->depensesMois();
                if ($this->params[0] === 'depenses-categorie')           return $c->depensesParCategorie();
            }
            if ($this->method === 'POST' && ($this->params[0] ?? '') === 'depenses') return $c->addDepense();
        }

        // ── DÉPENSES ──────────────────────────────────────────────────
        if ($this->endpoint === 'depenses') {
            $c = new TresorieController();
            if ($this->method === 'GET'  && empty($this->params))        return $c->depensesMois();
            if ($this->method === 'POST' && ($this->params[0] ?? '') === 'create') return $c->addDepense();
            if ($this->method === 'DELETE' && is_numeric($this->params[0] ?? '')) return $c->deleteDepense($this->params[0]);
        }

        // ── UTILISATEURS ──────────────────────────────────────────────
        if ($this->endpoint === 'utilisateurs') {
            $c = new UtilisateurController();
            if ($this->method === 'GET') {
                if (empty($this->params))                                return $c->index();
                if (is_numeric($this->params[0] ?? ''))                  return $c->show($this->params[0]);
            }
            if ($this->method === 'POST') {
                if (($this->params[0] ?? '') === 'create')               return $c->store();
                if (is_numeric($this->params[0] ?? ''))                  return $c->update($this->params[0]);
            }
            if ($this->method === 'PUT' && is_numeric($this->params[0] ?? '')) return $c->update($this->params[0]);
        }

        // ── DASHBOARD ─────────────────────────────────────────────────
        if ($this->endpoint === 'dashboard') {
            $c = new DashboardController();
            if ($this->method === 'GET') {
                if (($this->params[0] ?? '') === 'stats')                return $c->getStats();
                if (($this->params[0] ?? '') === 'graphiques')           return $c->getGraphiques();
            }
        }


        // ── CATALOGUE ─────────────────────────────────────────────────
        if ($this->endpoint === 'catalogue') {
            $c = new ServiceController();
            if ($this->method === 'GET'    && empty($this->params))                      return $c->index();
            if ($this->method === 'POST'   && ($this->params[0] ?? '') === 'create')     return $c->store();
            if ($this->method === 'PUT'    && is_numeric($this->params[0] ?? ''))        return $c->update($this->params[0]);
            if ($this->method === 'DELETE' && is_numeric($this->params[0] ?? ''))        return $c->delete($this->params[0]);
            if ($this->method === 'POST'   && is_numeric($this->params[0] ?? ''))        return $c->update($this->params[0]);
        }

        // ── PARAMETRES ────────────────────────────────────────────────
        // ── PARAMETRES ────────────────────────────────────────────────
        if ($this->endpoint === 'parametres') {
            $c = new ParametreController();
            if ($this->method === 'GET')  return $c->index();
            if ($this->method === 'POST' && ($this->params[0] ?? '') === 'upload-logo') return $c->uploadLogo();
            if ($this->method === 'POST') return $c->update();
        }

        // ── LIVRAISONS ────────────────────────────────────────────────
        if ($this->endpoint === 'livraisons') {
            $c = new LivraisonController();
            if ($this->method === 'GET'  && empty($this->params))                        return $c->index();
            if ($this->method === 'POST' && ($this->params[0] ?? '') === 'create')       return $c->store();
            if ($this->method === 'POST' && is_numeric($this->params[0] ?? ''))          return $c->updateStatut($this->params[0]);
        }

        // ── LOGIN stub ────────────────────────────────────────────────
       // ── LOGIN ─────────────────────────────────────────────────────
if ($this->endpoint === 'login' && $this->method === 'POST') {
    require_once ROOT_PATH . 'app/Controllers/authController.php';
    require_once ROOT_PATH . 'app/Models/utilisateur.php';
    $c = new AuthController();
    $c->login();
    return;
}

        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint non trouvé: ' . $this->endpoint]);
    }
}

// En cas d'erreur PHP non interceptée → retourner JSON
set_error_handler(function($errno, $errstr) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Erreur PHP: '.$errstr]);
    exit;
});
set_exception_handler(function($e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
});

$router = new ApiRouter();
$router->route();
?>
