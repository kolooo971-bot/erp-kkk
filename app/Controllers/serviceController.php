<?php
class ServiceController extends BaseController {
    public function index() {
        if ($this->isApiRequest()) {
            $stmt = $this->db->prepare("SELECT * FROM SERVICES ORDER BY Nom_Service ASC");
            $stmt->execute();
            $this->success('Catalogue', $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $data = ['user' => $this->user];
        require_once ROOT_PATH . 'app/Views/catalogue/index.php';
    }

    public function store() {
        $d = $this->getJsonData();
        if (empty($d['Nom_Service'])) $this->sendError('Nom requis', 400);
        $stmt = $this->db->prepare(
            "INSERT INTO SERVICES (Nom_Service, Description, Prix_Unitaire, Unite, Actif) VALUES (?,?,?,?,1)"
        );
        $ok = $stmt->execute([$d['Nom_Service'], $d['Description'] ?? '', $d['Prix_Unitaire'] ?? 0, $d['Unite'] ?? 'Forfait']);
        if ($ok) $this->success('Service créé', ['id' => $this->db->lastInsertId()], 201);
        else $this->sendError('Erreur création', 500);
    }

    public function update($id) {
        $d = $this->getJsonData();
        $stmt = $this->db->prepare(
            "UPDATE SERVICES SET Nom_Service=?, Description=?, Prix_Unitaire=?, Unite=? WHERE ID_Service=?"
        );
        $ok = $stmt->execute([$d['Nom_Service'], $d['Description'] ?? '', $d['Prix_Unitaire'] ?? 0, $d['Unite'] ?? 'Forfait', $id]);
        if ($ok) $this->success('Service modifié');
        else $this->sendError('Erreur modification', 500);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE SERVICES SET Actif=0 WHERE ID_Service=?");
        $stmt->execute([$id]);
        $this->success('Service désactivé');
    }
}
?>
