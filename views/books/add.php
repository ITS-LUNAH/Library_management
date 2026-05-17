<?php
// views/books/add.php
$categories = ['Roman','Science-fiction','Informatique','Jeunesse','Histoire','Biographie','Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajouter un livre — Bibliothèque</title>
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

    <div class="page-head">
      <div>
        <h1>Ajouter un livre</h1>
        <p class="sub">Remplissez les informations du nouveau livre</p>
      </div>
      <a href="index.php?page=books" class="btn btn-secondary">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="error-list">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST" action="index.php?page=books&action=add" novalidate>

        <div class="form-grid">

          <div class="form-group full">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" required
                   placeholder="Ex : Le Petit Prince"
                   value="<?= htmlspecialchars($old['titre'] ?? '') ?>"
                   class="<?= !empty($errors) && empty($old['titre']) ? 'error' : '' ?>">
          </div>

          <div class="form-group full">
            <label for="auteur">Auteur *</label>
            <input type="text" id="auteur" name="auteur" required
                   placeholder="Ex : Antoine de Saint-Exupéry"
                   value="<?= htmlspecialchars($old['auteur'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="categorie">Catégorie</label>
            <select id="categorie" name="categorie">
              <option value="">— Choisir —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>"
                  <?= ($old['categorie'] ?? '') === $cat ? 'selected' : '' ?>>
                  <?= $cat ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="quantite">Quantité *</label>
            <input type="number" id="quantite" name="quantite" required
                   min="0" placeholder="1"
                   value="<?= htmlspecialchars($old['quantite'] ?? '1') ?>">
            <span class="field-hint">0 = indisponible automatiquement</span>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Enregistrer le livre</button>
          <a href="index.php?page=books" class="btn btn-secondary">Annuler</a>
        </div>

      </form>
    </div>

  </div>
</main>
</body>
</html>