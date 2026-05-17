<?php
// controllers/BookController.php

require_once __DIR__ . '/../models/Book.php';

class BookController
{
    private Book $model;

    public function __construct()
    {
        $this->model = new Book();
    }

    // GET  /books
    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $books  = $search ? $this->model->search($search) : $this->model->getAll();
        require __DIR__ . '/../views/books/list.php';
    }

    // GET  /books/add
    // POST /books/add
    public function add(): void
    {
        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $_POST;
            $errors = $this->validate($_POST);

            if (empty($errors)) {
                if ($this->model->create($_POST)) {
                    $this->redirect('books', 'Livre ajouté avec succès.', 'success');
                } else {
                    $errors[] = 'Erreur lors de l\'ajout du livre.';
                }
            }
        }

        require __DIR__ . '/../views/books/add.php';
    }

    // GET  /books/edit?id=
    // POST /books/edit?id=
    public function edit(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $book = $this->model->getById($id);

        if (!$book) {
            $this->redirect('books', 'Livre introuvable.', 'error');
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validate($_POST);

            if (empty($errors)) {
                if ($this->model->update($id, $_POST)) {
                    $this->redirect('books', 'Livre modifié avec succès.', 'success');
                } else {
                    $errors[] = 'Erreur lors de la modification.';
                }
            } else {
                // Garder les valeurs saisies
                $book = array_merge($book, $_POST);
            }
        }

        require __DIR__ . '/../views/books/edit.php';
    }

    // POST /books/delete?id=
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('books');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($this->model->delete($id)) {
            $this->redirect('books', 'Livre supprimé avec succès.', 'success');
        } else {
            $this->redirect('books', 'Impossible de supprimer : des emprunts sont en cours.', 'error');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['titre'] ?? ''))) {
            $errors[] = 'Le titre est obligatoire.';
        }
        if (empty(trim($data['auteur'] ?? ''))) {
            $errors[] = 'L\'auteur est obligatoire.';
        }
        if (!isset($data['quantite']) || (int)$data['quantite'] < 0) {
            $errors[] = 'La quantité doit être un nombre positif ou nul.';
        }

        return $errors;
    }

    private function redirect(string $page, string $message = '', string $type = 'success'): never
    {
        if ($message) {
            session_start();
            $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        }
        header('Location: index.php?page=' . $page);
        exit;
    }
}