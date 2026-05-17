<?php
// controllers/FineController.php

require_once __DIR__ . '/../models/Fine.php';

class FineController
{
    private Fine $model;

    public function __construct()
    {
        $this->model = new Fine();
    }

    // ── GET /fines ─────────────────────────────────────────────────────────
    public function index(): void
    {
        $statut  = $_GET['statut']  ?? '';
        $search  = trim($_GET['search'] ?? '');
        $fines   = $this->model->getAll($statut, $search);
        $stats   = $this->model->stats();
        require __DIR__ . '/../views/fines/list.php';
    }

    // ── GET /fines/show?id= ───────────────────────────────────────────────
    public function show(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $fine = $this->model->getById($id);

        if (!$fine) {
            $this->redirect('fines', 'Amende introuvable.', 'error');
        }

        // Recalcul de détail pour l'affichage
        $calc = Fine::calculer($fine['dateRetourPrevue'], $fine['dateRetourReelle']);

        require __DIR__ . '/../views/fines/show.php';
    }

    // ── POST /fines/pay?id= ────────────────────────────────────────────────
    public function pay(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fines');
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->model->pay($id);

        if ($result['ok']) {
            $msg = sprintf('Amende de %.2f DH marquée comme payée.', $result['montant']);
            $this->redirect('fines', $msg, 'success');
        } else {
            $this->redirect('fines', $result['reason'], 'error');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function redirect(string $page, string $message = '', string $type = 'success'): never
    {
        if ($message) {
            $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        }
        header('Location: index.php?page=' . $page);
        exit;
    }
}