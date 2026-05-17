<?php
// models/Member.php

require_once __DIR__ . '/../config/database.php';

class Member
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ── Liste complète ────────────────────────────────────────────────────
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT m.*,
                    COUNT(e.idEmprunt) AS total_emprunts,
                    SUM(e.statut = "en_cours") AS emprunts_en_cours
             FROM membres m
             LEFT JOIN emprunts e ON e.idMembre = m.idMembre
             GROUP BY m.idMembre
             ORDER BY m.nom ASC'
        );
        return $stmt->fetchAll();
    }

    // ── Un membre par ID ──────────────────────────────────────────────────
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT m.*,
                    COUNT(e.idEmprunt) AS total_emprunts,
                    SUM(e.statut = "en_cours") AS emprunts_en_cours
             FROM membres m
             LEFT JOIN emprunts e ON e.idMembre = m.idMembre
             WHERE m.idMembre = :id
             GROUP BY m.idMembre'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── Recherche par nom ou email ────────────────────────────────────────
    public function search(string $q): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*,
                    COUNT(e.idEmprunt) AS total_emprunts,
                    SUM(e.statut = "en_cours") AS emprunts_en_cours
             FROM membres m
             LEFT JOIN emprunts e ON e.idMembre = m.idMembre
             WHERE m.nom LIKE :q OR m.email LIKE :q
             GROUP BY m.idMembre
             ORDER BY m.nom ASC'
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        return $stmt->fetchAll();
    }

    // ── Email déjà pris ? (exclut l'ID en cours de modif) ─────────────────
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM membres WHERE email = :email AND idMembre != :id'
        );
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Créer un membre ───────────────────────────────────────────────────
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO membres (nom, email, motDePasse, telephone, adresse, dateInscription)
             VALUES (:nom, :email, :motDePasse, :telephone, :adresse, :dateInscription)'
        );
        return $stmt->execute([
            ':nom'             => trim($data['nom']),
            ':email'           => strtolower(trim($data['email'])),
            ':motDePasse'      => password_hash($data['motDePasse'], PASSWORD_BCRYPT),
            ':telephone'       => trim($data['telephone'] ?? ''),
            ':adresse'         => trim($data['adresse'] ?? ''),
            ':dateInscription' => $data['dateInscription'] ?? date('Y-m-d'),
        ]);
    }

    // ── Modifier un membre ────────────────────────────────────────────────
    public function update(int $id, array $data): bool
    {
        // Si mot de passe fourni → on le rehashe, sinon on ne touche pas
        if (!empty($data['motDePasse'])) {
            $stmt = $this->db->prepare(
                'UPDATE membres
                 SET nom=:nom, email=:email, motDePasse=:motDePasse,
                     telephone=:telephone, adresse=:adresse
                 WHERE idMembre=:id'
            );
            return $stmt->execute([
                ':nom'        => trim($data['nom']),
                ':email'      => strtolower(trim($data['email'])),
                ':motDePasse' => password_hash($data['motDePasse'], PASSWORD_BCRYPT),
                ':telephone'  => trim($data['telephone'] ?? ''),
                ':adresse'    => trim($data['adresse'] ?? ''),
                ':id'         => $id,
            ]);
        }

        $stmt = $this->db->prepare(
            'UPDATE membres
             SET nom=:nom, email=:email, telephone=:telephone, adresse=:adresse
             WHERE idMembre=:id'
        );
        return $stmt->execute([
            ':nom'       => trim($data['nom']),
            ':email'     => strtolower(trim($data['email'])),
            ':telephone' => trim($data['telephone'] ?? ''),
            ':adresse'   => trim($data['adresse'] ?? ''),
            ':id'        => $id,
        ]);
    }

    // ── Supprimer un membre ───────────────────────────────────────────────
    public function delete(int $id): array
    {
        // Emprunts en cours ?
        $check = $this->db->prepare(
            "SELECT COUNT(*) FROM emprunts WHERE idMembre=:id AND statut='en_cours'"
        );
        $check->execute([':id' => $id]);
        if ((int) $check->fetchColumn() > 0) {
            return ['ok' => false, 'reason' => 'Ce membre a des emprunts en cours.'];
        }

        // Amendes impayées ?
        $check2 = $this->db->prepare(
            "SELECT COUNT(*) FROM amendes a
             JOIN emprunts e ON e.idEmprunt = a.idEmprunt
             WHERE e.idMembre=:id AND a.statut='impayee'"
        );
        $check2->execute([':id' => $id]);
        if ((int) $check2->fetchColumn() > 0) {
            return ['ok' => false, 'reason' => 'Ce membre a des amendes impayées.'];
        }

        $stmt = $this->db->prepare('DELETE FROM membres WHERE idMembre=:id');
        $stmt->execute([':id' => $id]);
        return ['ok' => true];
    }

    // ── Nombre total ──────────────────────────────────────────────────────
    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM membres')->fetchColumn();
    }

    // ── Stats pour le dashboard ───────────────────────────────────────────
    public function stats(): array
    {
        return $this->db->query(
            "SELECT
                COUNT(*)                              AS total,
                SUM(dateInscription >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS nouveaux,
                (SELECT COUNT(DISTINCT idMembre) FROM emprunts WHERE statut='en_cours') AS actifs
             FROM membres"
        )->fetch();
    }
}