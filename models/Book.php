<?php
// models/Book.php

require_once __DIR__ . '/../config/database.php';

class Book
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM livres ORDER BY titre ASC');
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM livres WHERE idLivre = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function search(string $q): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM livres WHERE titre LIKE :q OR auteur LIKE :q ORDER BY titre ASC'
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO livres (titre, auteur, categorie, quantite, disponible)
             VALUES (:titre, :auteur, :categorie, :quantite, :disponible)'
        );
        return $stmt->execute([
            ':titre'      => trim($data['titre']),
            ':auteur'     => trim($data['auteur']),
            ':categorie'  => trim($data['categorie'] ?? ''),
            ':quantite'   => (int) $data['quantite'],
            ':disponible' => (int) $data['quantite'] > 0 ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE livres
             SET titre=:titre, auteur=:auteur, categorie=:categorie,
                 quantite=:quantite, disponible=:disponible
             WHERE idLivre=:id'
        );
        return $stmt->execute([
            ':titre'      => trim($data['titre']),
            ':auteur'     => trim($data['auteur']),
            ':categorie'  => trim($data['categorie'] ?? ''),
            ':quantite'   => (int) $data['quantite'],
            ':disponible' => (int) $data['quantite'] > 0 ? 1 : 0,
            ':id'         => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $check = $this->db->prepare(
            "SELECT COUNT(*) FROM emprunts WHERE idLivre=:id AND statut='en_cours'"
        );
        $check->execute([':id' => $id]);
        if ((int) $check->fetchColumn() > 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM livres WHERE idLivre=:id');
        return $stmt->execute([':id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM livres')->fetchColumn();
    }
}