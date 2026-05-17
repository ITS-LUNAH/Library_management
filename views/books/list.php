<?php
// views/books/list.php
$flash = flash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Livres — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="inner">
    <a href="index.php" class="brand">Biblio<span>thèque</span></a>
    <nav class="nav">
      <a href="index.php?page=books" class="active">Livres</a>
      <a href="index.php?page=members">Membres</a>
      <a href="index.php?page=borrows">Emprunts</a>
    </nav>
  </div>
</header>

<main class="page">
  <div class="wrapper">

    <?php if ($flash): ?>
      <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>">
        <?= $flash['type'] === 'success' ? '✓' : '✕' ?>
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="page-head">
      <div>
        <h1>Catalogue des livres</h1>
        <p class="sub"><?= count($books) ?> livre(s) trouvé(s)</p>
      </div>
      <a href="index.php?page=books&action=add" class="btn btn-primary">
        + Ajouter un livre
      </a>
    </div>

    <!-- Recherche -->
    <form method="GET" action="index.php" class="search-bar">
      <input type="hidden" name="page" value="books">
      <input type="text" name="search" placeholder="Rechercher par titre ou auteur…"
             value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit" class="btn btn-secondary">Rechercher</button>
      <?php if (!empty($_GET['search'])): ?>
        <a href="index.php?page=books" class="btn btn-secondary">✕ Effacer</a>
      <?php endif; ?>
    </form>

    <?php if (empty($books)): ?>
      <div class="table-wrap">
        <div class="empty-state">
          <div class="icon">📚</div>
          <h3>Aucun livre trouvé</h3>
          <p>Ajoutez votre premier livre ou modifiez votre recherche.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Titre</th>
            <th>Auteur</th>
            <th>Catégorie</th>
            <th style="text-align:center">Qté</th>
            <th style="text-align:center">Statut</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($books as $book): ?>
          <tr>
            <td style="color:var(--ink-muted);font-size:.8rem"><?= $book['idLivre'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($book['titre']) ?></td>
            <td style="color:var(--ink-soft)"><?= htmlspecialchars($book['auteur']) ?></td>
            <td>
              <?php if ($book['categorie']): ?>
                <span class="tag"><?= htmlspecialchars($book['categorie']) ?></span>
              <?php else: ?>
                <span style="color:var(--ink-muted)">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;font-weight:600"><?= $book['quantite'] ?></td>
            <td style="text-align:center">
              <?php if ($book['disponible']): ?>
                <span class="badge badge-green">Disponible</span>
              <?php else: ?>
                <span class="badge badge-red">Indisponible</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end">
                <a href="index.php?page=books&action=edit&id=<?= $book['idLivre'] ?>"
                   class="btn btn-secondary btn-sm">Modifier</a>
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="confirmDelete(<?= $book['idLivre'] ?>, '<?= htmlspecialchars(addslashes($book['titre'])) ?>')">
                  Supprimer
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
</main>

<!-- Modale de confirmation suppression -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h3>Supprimer ce livre ?</h3>
    <p id="deleteMsg">Cette action est irréversible.</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
      <form method="POST" action="index.php?page=books&action=delete" id="deleteForm">
        <input type="hidden" name="id" id="deleteId">
        <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, title) {
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteMsg').textContent =
    'Voulez-vous vraiment supprimer « ' + title + ' » ? Cette action est irréversible.';
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>