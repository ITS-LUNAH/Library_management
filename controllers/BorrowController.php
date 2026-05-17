<?php
// controllers/BorrowController.php

require_once __DIR__ . '/../models/Borrow.php';

class BorrowController
{
    private Borrow $model;

    public function __construct()
    {
        $this->model = new Borrow();
    }

    // ── GET /borrows ──────────────────────────────────────────────────────
    public function index(): void
    {
        // Mettre à jour les statuts en retard automatiquement
        $this->model->checkOverdue();

        $statut  = $_GET['statut']  ?? '';
        $search  = trim($_GET['search'] ?? '');
        $borrows = $this->model->getAll($statut, $search);
        $stats   = $this->model->stats();

        require __DIR__ . '/../views/borrows/list.php';
    }

    // ── GET|POST /borrows/borrow ──────────────────────────────────────────
    public function borrow(): void
    {
        $members = $this->model->getMembers();
        $books   = $this->model->getAvailableBooks();
        $errors  = [];
        $old     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $_POST;
            $errors = $this->validate($_POST);

            if (empty($errors)) {
                $result = $this->model->create($_POST);

                if ($result['ok']) {
                    $this->redirect('borrows', 'Emprunt enregistré avec succès.', 'success');
                } else {
                    $errors[] = $result['reason'];
                }
            }
        }

        require __DIR__ . '/../views/borrows/borrow.php';
    }

    // ── POST /borrows/return?id= ──────────────────────────────────────────
    public function return(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('borrows');
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->model->returnBook($id);

        if ($result['ok']) {
            if ($result['enRetard'] && $result['amende']) {
                $a = $result['amende'];
                $msg = sprintf(
                    'Livre retourné avec %d jour(s) de retard. Amende générée : %.2f €.',
                    $a['jours'],
                    $a['montant']
                );
                $this->redirect('borrows', $msg, 'error');
            } else {
                $this->redirect('borrows', 'Livre retourné avec succès. Aucun retard.', 'success');
            }
        } else {
            $this->redirect('borrows', $result['reason'], 'error');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function validate(array $data): array
    {
        $errors = [];

        if (empty($data['idMembre']) || (int)$data['idMembre'] <= 0) {
            $errors[] = 'Veuillez sélectionner un membre.';
        }
        if (empty($data['idLivre']) || (int)$data['idLivre'] <= 0) {
            $errors[] = 'Veuillez sélectionner un livre.';
        }

        $dateEmprunt      = $data['dateEmprunt']     ?? '';
        $dateRetourPrevue = $data['dateRetourPrevue'] ?? '';

        if (empty($dateEmprunt)) {
            $errors[] = 'La date d\'emprunt est obligatoire.';
        } elseif (!$this->isValidDate($dateEmprunt)) {
            $errors[] = 'La date d\'emprunt n\'est pas valide.';
        }

        if (empty($dateRetourPrevue)) {
            $errors[] = 'La date de retour prévue est obligatoire.';
        } elseif (!$this->isValidDate($dateRetourPrevue)) {
            $errors[] = 'La date de retour prévue n\'est pas valide.';
        } elseif ($dateRetourPrevue <= $dateEmprunt) {
            $errors[] = 'La date de retour prévue doit être postérieure à la date d\'emprunt.';
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
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