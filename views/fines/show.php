<?php
// views/fines/show.php
// $fine : données de l'amende avec jointures membre/livre/emprunt
// $calc : résultat de Fine::calculer() — jours, montant, détail
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Détail de l'amende #<?= $fine['idAmende'] ?> — Bibliothèque</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Carte principale ── */
    .fine-hero {
      background: linear-gradient(135deg, var(--ink) 0%, #2c2c50 100%);
      border-radius: var(--radius);
      padding: 32px 36px;
      color: var(--white);
      margin-bottom: 28px;
      display: flex; align-items: center; gap: 32px; flex-wrap: wrap;
    }
    .fine-hero .amount {
      font-family: var(--font-head);
      font-size: 3.2rem; font-weight: 700; line-height: 1;
    }
    .fine-hero .amount.impayee { color: #ff8080; }
    .fine-hero .amount.payee   { color: #7cf5a7; }
    .fine-hero .sep { width: 1px; height: 60px; background: rgba(255,255,255,.2); }
    .fine-hero .meta { display: flex; flex-direction: column; gap: 6px; }
    .fine-hero .meta .badge-hero {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 14px; border-radius: 20px; font-size: .82rem; font-weight: 700;
      width: fit-content;
    }
    .fine-hero .meta .badge-hero.impayee { background: rgba(255,128,128,.2); color: #ff8080; }
    .fine-hero .meta .badge-hero.payee   { background: rgba(124,245,167,.2); color: #7cf5a7; }
    .fine-hero .meta p { font-size: .9rem; opacity: .8; }

    /* ── Grille de détails ── */
    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 24px;
    }
    .detail-card {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 20px 24px;
      box-shadow: var(--shadow);
    }
    .detail-card h3 {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--ink-muted);
      margin-bottom: 14px; padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }
    .detail-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 7px 0; font-size: .88rem;
    }
    .detail-row:not(:last-child) { border-bottom: 1px dotted var(--border); }
    .detail-row .key { color: var(--ink-muted); }
    .detail-row .val { font-weight: 600; text-align: right; }
    .detail-row .val.accent { color: var(--accent); }
    .detail-row .val.green  { color: var(--green); }

    /* ── Timeline calcul ── */
    .calc-breakdown {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 24px 28px;
      box-shadow: var(--shadow); margin-bottom: 24px;
      grid-column: 1 / -1;
    }
    .calc-breakdown h3 {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--ink-muted);
      margin-bottom: 20px; padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }
    .calc-steps {
      display: flex; align-items: stretch; gap: 0;
      flex-wrap: wrap;
    }
    .calc-step {
      flex: 1; min-width: 160px;
      display: flex; flex-direction: column;
      align-items: center; text-align: center;
      padding: 16px 12px; position: relative;
    }
    .calc-step:not(:last-child)::after {
      content: '→';
      position: absolute; right: -10px; top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem; color: var(--ink-muted); font-weight: 300;
      z-index: 1;
    }
    .calc-step .circle {
      width: 48px; height: 48px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; margin-bottom: 10px;
      border: 2px solid var(--border);
      background: var(--paper);
    }
    .calc-step .step-val {
      font-family: var(--font-head); font-size: 1.15rem; font-weight: 700;
      color: var(--ink); margin-bottom: 3px;
    }
    .calc-step .step-lbl {
      font-size: .75rem; color: var(--ink-muted); line-height: 1.3;
    }
    .calc-step.result .circle  { background: var(--accent); border-color: var(--accent); color: white; }
    .calc-step.result .step-val{ color: var(--accent); font-size: 1.4rem; }

    /* ── Paiement ── */
    .pay-section {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 24px 28px;
      box-shadow: var(--shadow); grid-column: 1 / -1;
    }
    .pay-section h3 {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--ink-muted);
      margin-bottom: 16px; padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }
    .paid-receipt {
      display: flex; align-items: center; gap: 14px;
      background: #edfaf3; border: 1px solid #b2dfcb;
      border-radius: 8px; padding: 14px 18px;
      color: #1a6b3c; font-size: .9rem;
    }
    .paid-receipt .icon { font-size: 1.8rem; }

    @media (max-width: 680px) {
      .detail-grid { grid-template-columns: 1fr; }
      .fine-hero { flex-direction: column; gap: 16px; }
      .fine-hero .sep { display: none; }
      .calc-step:not(:last-child)::after { display: none; }
    }
  </style>
</head>
<body>

<header class="site-header">
  <div class="inner">
    <a href="index.php" class="brand">Biblio<span>thèque</span></a>
    <nav class="nav">
      <a href="index.php?page=books">Livres</a>
      <a href="index.php?page=members">Membres</a>
      <a href="index.php?page=borrows">Emprunts</a>
      <a href="index.php?page=fines" class="active">Amendes</a>
    </nav>
  </div>
</header>

<main class="page">
  <div class="wrapper">

    <div class="page-head">
      <div>
        <h1>Détail de l'amende</h1>
        <p class="sub">Amende #<?= $fine['idAmende'] ?> — Emprunt #<?= $fine['idEmprunt'] ?></p>
      </div>
      <a href="index.php?page=fines" class="btn btn-secondary">← Retour</a>
    </div>

    <!-- ── Carte héro ── -->
    <div class="fine-hero">
      <div class="amount <?= $fine['statut'] ?>">
        <?= number_format($fine['montant'], 2) ?> DH
      </div>

      <div class="sep"></div>

      <div class="meta">
        <div class="badge-hero <?= $fine['statut'] ?>">
          <?php if ($fine['statut'] === 'impayee'): ?>
            ⚠ Amende impayée
          <?php else: ?>
            ✓ Amende payée
          <?php endif; ?>
        </div>
        <p>
          <strong><?= htmlspecialchars($fine['membre_nom']) ?></strong>
          · <?= htmlspecialchars($fine['livre_titre']) ?>
        </p>
        <p style="font-size:.82rem;opacity:.65">
          <?= $fine['nombreJoursRetard'] ?> jour<?= $fine['nombreJoursRetard'] > 1 ? 's' : '' ?> de retard
          × <?= number_format(TARIF_JOURNALIER, 2) ?> DH / jour
        </p>
      </div>
    </div>

    <!-- ── Calcul détaillé ── -->
    <div class="detail-grid">

      <div class="calc-breakdown">
        <h3>🧮 Détail du calcul de l'amende</h3>
        <div class="calc-steps">

          <div class="calc-step">
            <div class="circle">📅</div>
            <div class="step-val"><?= date('d/m/Y', strtotime($fine['dateRetourPrevue'])) ?></div>
            <div class="step-lbl">Date de retour<br>prévue</div>
          </div>

          <div class="calc-step">
            <div class="circle">📅</div>
            <div class="step-val"><?= date('d/m/Y', strtotime($fine['dateRetourReelle'])) ?></div>
            <div class="step-lbl">Date de retour<br>réelle</div>
          </div>

          <div class="calc-step">
            <div class="circle">⏱</div>
            <div class="step-val"><?= $fine['nombreJoursRetard'] ?> jour<?= $fine['nombreJoursRetard'] > 1 ? 's' : '' ?></div>
            <div class="step-lbl">Nombre de jours<br>de retard</div>
          </div>

          <div class="calc-step">
            <div class="circle">✕</div>
            <div class="step-val"><?= number_format(TARIF_JOURNALIER, 2) ?> DH</div>
            <div class="step-lbl">Tarif journalier<br>appliqué</div>
          </div>

          <div class="calc-step result">
            <div class="circle">=</div>
            <div class="step-val"><?= number_format($fine['montant'], 2) ?> DH</div>
            <div class="step-lbl">Montant total<br>de l'amende</div>
          </div>

        </div>

        <!-- Formule récapitulative -->
        <div style="margin-top:20px;padding:12px 16px;background:var(--paper);border-radius:8px;
                    text-align:center;font-size:.9rem;color:var(--ink-soft)">
          <code style="font-family:var(--font-mono,monospace);font-size:.92rem;color:var(--ink)">
            <?= $fine['nombreJoursRetard'] ?> jours
            × <?= number_format(TARIF_JOURNALIER, 2) ?> DH
            = <strong style="color:var(--accent)"><?= number_format($fine['montant'], 2) ?> DH</strong>
          </code>
        </div>
      </div>

      <!-- ── Infos membre ── -->
      <div class="detail-card">
        <h3>👤 Membre</h3>
        <div class="detail-row">
          <span class="key">Nom</span>
          <span class="val"><?= htmlspecialchars($fine['membre_nom']) ?></span>
        </div>
        <div class="detail-row">
          <span class="key">Email</span>
          <span class="val"><?= htmlspecialchars($fine['membre_email']) ?></span>
        </div>
        <?php if ($fine['membre_telephone']): ?>
        <div class="detail-row">
          <span class="key">Téléphone</span>
          <span class="val"><?= htmlspecialchars($fine['membre_telephone']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($fine['membre_adresse'])): ?>
        <div class="detail-row">
          <span class="key">Adresse</span>
          <span class="val" style="max-width:200px;text-align:right;font-size:.82rem">
            <?= htmlspecialchars($fine['membre_adresse']) ?>
          </span>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── Infos livre ── -->
      <div class="detail-card">
        <h3>📖 Livre emprunté</h3>
        <div class="detail-row">
          <span class="key">Titre</span>
          <span class="val"><?= htmlspecialchars($fine['livre_titre']) ?></span>
        </div>
        <div class="detail-row">
          <span class="key">Auteur</span>
          <span class="val"><?= htmlspecialchars($fine['livre_auteur']) ?></span>
        </div>
        <div class="detail-row">
          <span class="key">Emprunté le</span>
          <span class="val"><?= date('d/m/Y', strtotime($fine['dateEmprunt'])) ?></span>
        </div>
        <div class="detail-row">
          <span class="key">Retour prévu</span>
          <span class="val"><?= date('d/m/Y', strtotime($fine['dateRetourPrevue'])) ?></span>
        </div>
        <div class="detail-row">
          <span class="key">Retour réel</span>
          <span class="val accent"><?= date('d/m/Y', strtotime($fine['dateRetourReelle'])) ?></span>
        </div>
      </div>

      <!-- ── Section paiement ── -->
      <div class="pay-section">
        <h3>💳 Paiement</h3>

        <?php if ($fine['statut'] === 'payee'): ?>
          <div class="paid-receipt">
            <div class="icon">🎉</div>
            <div>
              <strong>Amende réglée</strong><br>
              Paiement enregistré le <strong><?= date('d/m/Y', strtotime($fine['datePaiement'])) ?></strong>
              pour un montant de <strong><?= number_format($fine['montant'], 2) ?> DH</strong>.
            </div>
          </div>

        <?php else: ?>
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--accent);font-family:var(--font-head)">
                <?= number_format($fine['montant'], 2) ?> DH à percevoir
              </div>
              <div style="font-size:.85rem;color:var(--ink-muted);margin-top:4px">
                Amende générée automatiquement lors du retour avec retard
              </div>
            </div>
            <form method="POST" action="index.php?page=fines&action=pay">
              <input type="hidden" name="id" value="<?= $fine['idAmende'] ?>">
              <button type="submit" class="btn btn-primary"
                      onclick="return confirm('Confirmer le paiement de <?= number_format($fine['montant'], 2) ?> DH ?')">
                ✓ Enregistrer le paiement
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /detail-grid -->

  </div>
</main>
</body>
</html>