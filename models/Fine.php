<?php
// models/Fine.php
// Gestion complète des amendes de retard — tarif : 5 DH/jour

require_once __DIR__ . '/../config/database.php';

define('TARIF_JOURNALIER', 5.00); // DH par jour de retard

class Fine
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ── Toutes les amendes avec détails emprunt/membre/livre ──────────────
    public function getAll(string $statut = '', string $search = ''): array
    {
        $where  = [];
        $params = [];

        if ($statut) {
            $where[]           = 'a.statut = :statut';
            $params[':statut'] = $statut;
        }
        if ($search) {
            $where[]      = '(m.nom LIKE :q OR l.titre LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        $sql = "SELECT
                    a.*,
                    e.dateEmprunt,
                    e.dateRetourPrevue,
                    e.dateRetourReelle,
                    e.idMembre,
                    e.idLivre,
                    m.nom        AS membre_nom,
                    m.email      AS membre_email,
                    m.telephone  AS membre_telephone,
                    l.titre      AS livre_titre,
                    l.auteur     AS livre_auteur
                FROM amendes a
                JOIN emprunts e ON e.idEmprunt = a.idEmprunt
                JOIN membres  m ON m.idMembre  = e.idMembre
                JOIN livres   l ON l.idLivre   = e.idLivre"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY a.idAmende DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Une amende par ID ─────────────────────────────────────────────────
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                a.*,
                e.dateEmprunt,
                e.dateRetourPrevue,
                e.dateRetourReelle,
                m.nom        AS membre_nom,
                m.email      AS membre_email,
                m.telephone  AS membre_telephone,
                m.adresse    AS membre_adresse,
                l.titre      AS livre_titre,
                l.auteur     AS livre_auteur
             FROM amendes a
             JOIN emprunts e ON e.idEmprunt = a.idEmprunt
             JOIN membres  m ON m.idMembre  = e.idMembre
             JOIN livres   l ON l.idLivre   = e.idLivre
             WHERE a.idAmende = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── Calculer une amende (sans la persister) ───────────────────────────
    public static function calculer(string $dateRetourPrevue, string $dateRetourReelle): array
    {
        $prevue  = new DateTime($dateRetourPrevue);
        $reelle  = new DateTime($dateRetourReelle);

        if ($reelle <= $prevue) {
            return ['jours' => 0, 'montant' => 0.0, 'enRetard' => false];
        }

        $jours   = (int) $prevue->diff($reelle)->days;
        $montant = round($jours * TARIF_JOURNALIER, 2);

        return [
            'jours'     => $jours,
            'montant'   => $montant,
            'enRetard'  => true,
            'tarif'     => TARIF_JOURNALIER,
            'detail'    => sprintf('%d jour(s) × %.2f DH = %.2f DH', $jours, TARIF_JOURNALIER, $montant),
        ];
    }

    // ── Créer ou recalculer une amende ────────────────────────────────────
    public function createOrUpdate(int $idEmprunt, int $joursRetard, float $montant): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO amendes (idEmprunt, montant, nombreJoursRetard, statut)
             VALUES (:idEmprunt, :montant, :jours, 'impayee')
             ON DUPLICATE KEY UPDATE
                 montant           = VALUES(montant),
                 nombreJoursRetard = VALUES(nombreJoursRetard)"
        );
        return $stmt->execute([
            ':idEmprunt' => $idEmprunt,
            ':montant'   => $montant,
            ':jours'     => $joursRetard,
        ]);
    }

    // ── Marquer une amende comme payée ────────────────────────────────────
    public function pay(int $id): array
    {
        $amende = $this->getById($id);
        if (!$amende) {
            return ['ok' => false, 'reason' => 'Amende introuvable.'];
        }
        if ($amende['statut'] === 'payee') {
            return ['ok' => false, 'reason' => 'Cette amende est déjà payée.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE amendes
             SET statut = 'payee', datePaiement = CURDATE()
             WHERE idAmende = :id"
        );
        $stmt->execute([':id' => $id]);

        return ['ok' => true, 'montant' => $amende['montant']];
    }

    // ── Stats globales ────────────────────────────────────────────────────
    public function stats(): array
    {
        return $this->db->query(
            "SELECT
                COUNT(*)                             AS total,
                SUM(statut = 'impayee')              AS impayees,
                SUM(statut = 'payee')                AS payees,
                COALESCE(SUM(montant), 0)            AS total_montant,
                COALESCE(SUM(montant * (statut = 'impayee')), 0) AS montant_du,
                COALESCE(SUM(montant * (statut = 'payee')),   0) AS montant_percu
             FROM amendes"
        )->fetch();
    }

    // ── Amendes d'un membre spécifique ────────────────────────────────────
    public function getByMembre(int $idMembre): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, l.titre AS livre_titre, e.dateRetourPrevue, e.dateRetourReelle
             FROM amendes a
             JOIN emprunts e ON e.idEmprunt = a.idEmprunt
             JOIN livres   l ON l.idLivre   = e.idLivre
             WHERE e.idMembre = :id
             ORDER BY a.idAmende DESC"
        );
        $stmt->execute([':id' => $idMembre]);
        return $stmt->fetchAll();
    }
}