<?php
// models/Borrow.php

require_once __DIR__ . '/../config/database.php';

class Borrow
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ── Liste complète avec jointures ─────────────────────────────────────
    public function getAll(string $statut = '', string $search = ''): array
    {
        $where  = [];
        $params = [];

        if ($statut) {
            $where[]           = 'e.statut = :statut';
            $params[':statut'] = $statut;
        }
        if ($search) {
            $where[]           = '(m.nom LIKE :q OR l.titre LIKE :q)';
            $params[':q']      = '%' . $search . '%';
        }

        $sql = "SELECT
                    e.*,
                    m.nom        AS membre_nom,
                    m.email      AS membre_email,
                    l.titre      AS livre_titre,
                    l.auteur     AS livre_auteur,
                    a.idAmende   AS amende_id,
                    a.montant    AS amende_montant,
                    a.statut     AS amende_statut,
                    DATEDIFF(COALESCE(e.dateRetourReelle, CURDATE()), e.dateRetourPrevue)
                                 AS jours_retard
                FROM emprunts e
                JOIN membres m ON m.idMembre = e.idMembre
                JOIN livres  l ON l.idLivre  = e.idLivre
                LEFT JOIN amendes a ON a.idEmprunt = e.idEmprunt"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY e.dateEmprunt DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Un emprunt par ID ─────────────────────────────────────────────────
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT e.*,
                    m.nom   AS membre_nom,
                    m.email AS membre_email,
                    l.titre AS livre_titre,
                    l.auteur AS livre_auteur,
                    DATEDIFF(COALESCE(e.dateRetourReelle, CURDATE()), e.dateRetourPrevue)
                             AS jours_retard
             FROM emprunts e
             JOIN membres m ON m.idMembre = e.idMembre
             JOIN livres  l ON l.idLivre  = e.idLivre
             WHERE e.idEmprunt = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── Vérifier si le membre a déjà ce livre en cours ────────────────────
    public function alreadyBorrowed(int $idMembre, int $idLivre): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM emprunts
             WHERE idMembre = :m AND idLivre = :l AND statut = 'en_cours'"
        );
        $stmt->execute([':m' => $idMembre, ':l' => $idLivre]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Créer un emprunt ──────────────────────────────────────────────────
    public function create(array $data): array
    {
        $idMembre = (int) $data['idMembre'];
        $idLivre  = (int) $data['idLivre'];

        // 1. Livre disponible ?
        $livre = $this->db->prepare(
            'SELECT quantite, disponible FROM livres WHERE idLivre = :id'
        );
        $livre->execute([':id' => $idLivre]);
        $l = $livre->fetch();

        if (!$l || !$l['disponible']) {
            return ['ok' => false, 'reason' => 'Ce livre n\'est pas disponible.'];
        }

        // 2. Déjà emprunté par ce membre ?
        if ($this->alreadyBorrowed($idMembre, $idLivre)) {
            return ['ok' => false, 'reason' => 'Ce membre a déjà emprunté ce livre et ne l\'a pas encore rendu.'];
        }

        // 3. Date retour prévue cohérente
        $dateEmprunt      = $data['dateEmprunt']     ?? date('Y-m-d');
        $dateRetourPrevue = $data['dateRetourPrevue'] ?? '';

        if ($dateRetourPrevue <= $dateEmprunt) {
            return ['ok' => false, 'reason' => 'La date de retour prévue doit être postérieure à la date d\'emprunt.'];
        }

        // 4. Insertion dans une transaction
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO emprunts (idMembre, idLivre, dateEmprunt, dateRetourPrevue, statut)
                 VALUES (:idMembre, :idLivre, :dateEmprunt, :dateRetourPrevue, 'en_cours')"
            );
            $stmt->execute([
                ':idMembre'        => $idMembre,
                ':idLivre'         => $idLivre,
                ':dateEmprunt'     => $dateEmprunt,
                ':dateRetourPrevue'=> $dateRetourPrevue,
            ]);

            $this->db->commit();
            return ['ok' => true];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['ok' => false, 'reason' => 'Erreur base de données : ' . $e->getMessage()];
        }
    }

    // ── Retourner un livre ────────────────────────────────────────────────
    public function returnBook(int $id): array
    {
        $emprunt = $this->getById($id);
        if (!$emprunt) {
            return ['ok' => false, 'reason' => 'Emprunt introuvable.'];
        }
        if ($emprunt['statut'] === 'retourne') {
            return ['ok' => false, 'reason' => 'Ce livre a déjà été retourné.'];
        }

        try {
            $this->db->beginTransaction();

            $today    = date('Y-m-d');
            $enRetard = $today > $emprunt['dateRetourPrevue'];
            $newStatut = 'retourne';

            $stmt = $this->db->prepare(
                "UPDATE emprunts
                 SET dateRetourReelle = :today, statut = :statut
                 WHERE idEmprunt = :id"
            );
            $stmt->execute([
                ':today'  => $today,
                ':statut' => $newStatut,
                ':id'     => $id,
            ]);

            // Calcul amende si retard
            $amende = null;
            if ($enRetard) {
                $joursRetard = (int) (new DateTime($emprunt['dateRetourPrevue']))->diff(new DateTime($today))->days;
                $tarifJour   = 1.50; // €/jour — configurable
                $montant     = round($joursRetard * $tarifJour, 2);

                $stmtA = $this->db->prepare(
                    "INSERT INTO amendes (idEmprunt, montant, nombreJoursRetard, statut)
                     VALUES (:idEmprunt, :montant, :jours, 'impayee')
                     ON DUPLICATE KEY UPDATE montant = :montant, nombreJoursRetard = :jours"
                );
                $stmtA->execute([
                    ':idEmprunt' => $id,
                    ':montant'   => $montant,
                    ':jours'     => $joursRetard,
                ]);
                $amende = ['montant' => $montant, 'jours' => $joursRetard];
            }

            $this->db->commit();
            return ['ok' => true, 'enRetard' => $enRetard, 'amende' => $amende];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['ok' => false, 'reason' => 'Erreur base de données : ' . $e->getMessage()];
        }
    }

    // ── Marquer les emprunts dépassés en "en_retard" ──────────────────────
    public function checkOverdue(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE emprunts SET statut = 'en_retard'
             WHERE statut = 'en_cours' AND dateRetourPrevue < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ── Stats pour dashboard ──────────────────────────────────────────────
    public function stats(): array
    {
        return $this->db->query(
            "SELECT
                COUNT(*)                               AS total,
                SUM(statut = 'en_cours')               AS en_cours,
                SUM(statut = 'en_retard')              AS en_retard,
                SUM(statut = 'retourne')               AS retournes
             FROM emprunts"
        )->fetch();
    }

    // ── Membres disponibles (pour select) ────────────────────────────────
    public function getMembers(): array
    {
        return $this->db->query(
            'SELECT idMembre, nom, email FROM membres ORDER BY nom ASC'
        )->fetchAll();
    }

    // ── Livres disponibles (pour select) ─────────────────────────────────
    public function getAvailableBooks(): array
    {
        return $this->db->query(
            'SELECT idLivre, titre, auteur FROM livres WHERE disponible = 1 ORDER BY titre ASC'
        )->fetchAll();
    }
}