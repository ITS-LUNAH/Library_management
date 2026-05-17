<?php
// controllers/MemberController.php

require_once __DIR__ . '/../models/Member.php';

class MemberController
{
    private Member $model;

    public function __construct()
    {
        $this->model = new Member();
    }

    // ── GET /members ──────────────────────────────────────────────────────
    public function index(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $members = $search ? $this->model->search($search) : $this->model->getAll();
        $stats   = $this->model->stats();
        require __DIR__ . '/../views/members/list.php';
    }

    // ── GET|POST /members/add ─────────────────────────────────────────────
    public function add(): void
    {
        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $_POST;
            $errors = $this->validate($_POST, mode: 'create');

            if (empty($errors)) {
                if ($this->model->create($_POST)) {
                    $this->redirect('members', 'Membre ajouté avec succès.', 'success');
                } else {
                    $errors[] = 'Erreur lors de l\'ajout. Veuillez réessayer.';
                }
            }
        }

        require __DIR__ . '/../views/members/add.php';
    }

    // ── GET|POST /members/edit?id= ────────────────────────────────────────
    public function edit(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $member = $this->model->getById($id);

        if (!$member) {
            $this->redirect('members', 'Membre introuvable.', 'error');
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validate($_POST, mode: 'update', currentId: $id);

            if (empty($errors)) {
                if ($this->model->update($id, $_POST)) {
                    $this->redirect('members', 'Membre modifié avec succès.', 'success');
                } else {
                    $errors[] = 'Erreur lors de la modification. Veuillez réessayer.';
                }
            } else {
                $member = array_merge($member, $_POST); // conserver saisies
            }
        }

        require __DIR__ . '/../views/members/edit.php';
    }

    // ── POST /members/delete ──────────────────────────────────────────────
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('members');
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->model->delete($id);

        if ($result['ok']) {
            $this->redirect('members', 'Membre supprimé avec succès.', 'success');
        } else {
            $this->redirect('members', $result['reason'], 'error');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function validate(array $data, string $mode = 'create', int $currentId = 0): array
    {
        $errors = [];

        if (empty(trim($data['nom'] ?? ''))) {
            $errors[] = 'Le nom complet est obligatoire.';
        }

        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors[] = 'L\'adresse email est obligatoire.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide.';
        } elseif ($this->model->emailExists($email, $currentId)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        if ($mode === 'create') {
            if (empty($data['motDePasse'] ?? '')) {
                $errors[] = 'Le mot de passe est obligatoire.';
            } elseif (strlen($data['motDePasse']) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
        } elseif (!empty($data['motDePasse']) && strlen($data['motDePasse']) < 6) {
            $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        }

        if (!empty($data['telephone'])) {
            $tel = preg_replace('/\s+/', '', $data['telephone']);
            if (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $tel)) {
                $errors[] = 'Le numéro de téléphone n\'est pas valide.';
            }
        }

        if (!empty($data['dateInscription'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['dateInscription']);
            if (!$d || $d->format('Y-m-d') !== $data['dateInscription']) {
                $errors[] = 'La date d\'inscription n\'est pas valide.';
            }
        }

        return $errors;
    }

    private function redirect(string $page, string $message = '', string $type = 'success'): never
    {
        if ($message) {
            $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        }
        header('Location: index.php?page=' . $page);
        exit;
    }
}