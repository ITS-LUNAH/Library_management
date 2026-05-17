<?php
// views/members/list.php
$flash = flash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Membres — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--ink); color: var(--white);
      display: inline-flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 700; letter-spacing: .02em;
      flex-shrink: 0;
    }
    .member-cell { display: flex; align-items: center; gap: 12px; }
    .member-cell .info { display: flex; flex-direction: column; }
    .member-cell .name { font-weight: 600; font-size: .9rem; }
    .member-cell .email { font-size: .78rem; color: var(--ink-muted); }
    .stat-card { border-top: 3px solid var(--accent); }
  </style>
</head>
<body>

<header class="site-header">
  <div class="inner">
    <a href="index.php" class="brand">Biblio<span>thèque</span></a>
    <nav class="nav">
      <a href="index.php?page=books">Livres</a>
      <a href="index.php?page=members" class="active">Membres</a>
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
        <h1>Gestion des membres</h1>
        <p class="sub"><?= count($members) ?> membre(s) affiché(s)</p>
      </div>
      <a href="index.php?page=members&action=add" class="btn btn-primary">+ Ajouter un membre</a>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="num"><?= (int)$stats['total'] ?></div>
        <div class="lbl">Total membres</div>
      </div>
      <div class="stat-card">
        <div class="num"><?= (int)$stats['actifs'] ?></div>
        <div class="lbl">Avec emprunt actif</div>
      </div>
      <div class="stat-card">
        <div class="num"><?= (int)$stats['nouveaux'] ?></div>
        <div class="lbl">Inscrits (30 jours)</div>
      </div>
    </div>

    <!-- Recherche -->
    <form method="GET" action="index.php" class="search-bar">
      <input type="hidden" name="page" value="members">
      <input type="text" name="search" placeholder="Rechercher par nom ou email…"
             value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit" class="btn btn-secondary">Rechercher</button>
      <?php if (!empty($_GET['search'])): ?>
        <a href="index.php?page=members" class="btn btn-secondary">✕ Effacer</a>
      <?php endif; ?>
    </form>

    <?php if (empty($members)): ?>
      <div class="table-wrap">
        <div class="empty-state">
          <div class="icon">👤</div>
          <h3>Aucun membre trouvé</h3>
          <p>Ajoutez votre premier membre ou modifiez votre recherche.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Membre</th>
            <th>Téléphone</th>
            <th class="hide-sm">Date d'inscription</th>
            <th style="text-align:center">Emprunts</th>
            <th style="text-align:center">Statut</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <?php
            // Initiales pour l'avatar
            $words    = explode(' ', trim($m['nom']));
            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
          ?>
          <tr>
            <td>
              <div class="member-cell">
                <div class="avatar"><?= $initials ?></div>
                <div class="info">
                  <span class="name"><?= htmlspecialchars($m['nom']) ?></span>
                  <span class="email"><?= htmlspecialchars($m['email']) ?></span>
                </div>
              </div>
            </td>
            <td style="color:var(--ink-soft)">
              <?= $m['telephone'] ? htmlspecialchars($m['telephone']) : '<span style="color:var(--ink-muted)">—</span>' ?>
            </td>
            <td class="hide-sm" style="color:var(--ink-soft);font-size:.85rem">
              <?= date('d/m/Y', strtotime($m['dateInscription'])) ?>
            </td>
            <td style="text-align:center">
              <?php if ((int)$m['emprunts_en_cours'] > 0): ?>
                <span class="badge badge-amber"><?= (int)$m['emprunts_en_cours'] ?> en cours</span>
              <?php else: ?>
                <span style="color:var(--ink-muted);font-size:.85rem"><?= (int)$m['total_emprunts'] ?> total</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <?php if ((int)$m['emprunts_en_cours'] > 0): ?>
                <span class="badge badge-green">Actif</span>
              <?php else: ?>
                <span class="badge" style="background:#f0f0f5;color:var(--ink-muted)">Inactif</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end">
                <a href="index.php?page=members&action=edit&id=<?= $m['idMembre'] ?>"
                   class="btn btn-secondary btn-sm">Modifier</a>
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="confirmDelete(<?= $m['idMembre'] ?>, '<?= htmlspecialchars(addslashes($m['nom'])) ?>', <?= (int)$m['emprunts_en_cours'] ?>)">
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

<!-- Modale confirmation suppression -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h3>Supprimer ce membre ?</h3>
    <p id="deleteMsg"></p>
    <div id="deleteWarning" style="display:none;background:#fef9e7;border:1px solid #f9ca7a;
         border-radius:8px;padding:10px 14px;font-size:.85rem;color:#7d5a0a;margin-bottom:16px">
      ⚠️ Ce membre a des emprunts en cours et ne peut pas être supprimé.
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
      <form method="POST" action="index.php?page=members&action=delete" id="deleteForm">
        <input type="hidden" name="id" id="deleteId">
        <button type="submit" class="btn btn-danger" id="deleteBtn">Confirmer</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, name, empruntsEnCours) {
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteMsg').textContent =
    'Voulez-vous supprimer le membre « ' + name + ' » ? Cette action est irréversible.';
  const warn = document.getElementById('deleteWarning');
  const btn  = document.getElementById('deleteBtn');
  if (empruntsEnCours > 0) {
    warn.style.display = 'block';
    btn.disabled       = true;
    btn.style.opacity  = '.45';
  } else {
    warn.style.display = 'none';
    btn.disabled       = false;
    btn.style.opacity  = '1';
  }
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