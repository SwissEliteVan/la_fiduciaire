<?php
declare(strict_types=1);
$cabinet = $user['role'] === 'cabinet';
$categories = lf_categories();
$statuses = lf_statuses();
$more = count($pieces) > LF_PAGE_SIZE;
$pieces = array_slice($pieces, 0, LF_PAGE_SIZE);
$pageUrl = static fn(int $target): string => '/espace-client?' . http_build_query(array_filter([
    'periode' => $filters['period'],
    'statut' => $filters['status'],
    'dossier' => $cabinet ? $filters['dossier'] : '',
    'page' => $target,
])) . '#mes-pieces';
?>
<section class="lf-portal" aria-labelledby="tableau-titre">
    <div class="lf-toolbar">
        <div>
            <p class="lf-kicker"><?= $cabinet ? 'Espace cabinet' : 'Espace client' ?></p>
            <h1 id="tableau-titre"><?= $cabinet ? 'Dossiers clients' : 'Mes pièces comptables' ?></h1>
            <p class="lf-muted"><?= $cabinet ? 'Consultez les dépôts et mettez à jour leur statut.' : 'Dossier · ' . e($dossier) ?></p>
        </div>
        <form method="post" action="/espace-client/deconnexion">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <button type="submit" class="btn btn--secondary">Me déconnecter</button>
        </form>
    </div>

    <?php if ($notice): ?><p class="lf-feedback lf-feedback--ok" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="lf-feedback lf-feedback--error" id="depot-erreur" role="alert"><?= e($error) ?></p><?php endif; ?>

    <?php if (!$cabinet): ?>
        <section class="lf-panel" aria-labelledby="depot-titre">
            <h2 id="depot-titre">Déposer mes pièces</h2>
            <p class="lf-muted">Choisissez le même mois et la même catégorie pour toutes les pièces de cet envoi.</p>
            <form class="form lf-form" method="post" enctype="multipart/form-data" action="/espace-client/deposer"
                  <?= $error ? 'aria-describedby="depot-erreur"' : '' ?>>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <div class="lf-grid">
                    <div class="form__row">
                        <label for="depot-periode">Mois comptable</label>
                        <input type="month" name="periode" id="depot-periode" min="2000-01" max="2099-12"
                               pattern="20[0-9]{2}-(0[1-9]|1[0-2])" placeholder="AAAA-MM" required value="<?= e($uploadPeriod) ?>">
                    </div>
                    <div class="form__row">
                        <label for="depot-categorie">Catégorie</label>
                        <select name="categorie" id="depot-categorie" required>
                            <option value="">Choisir une catégorie</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $key === $uploadCategory ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form__row lf-drop">
                    <label for="depot-fichiers">Sélectionner les fichiers</label>
                    <p id="depot-aide" class="lf-muted">PDF, JPG ou PNG · 5 fichiers maximum par envoi · 10 Mo par fichier.</p>
                    <input id="depot-fichiers" name="pieces[]" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple required aria-describedby="depot-aide">
                </div>
                <button type="submit" class="btn btn--primary">Déposer les pièces</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="lf-panel" id="mes-pieces" aria-labelledby="pieces-titre">
        <h2 id="pieces-titre"><?= $cabinet ? 'Pièces reçues' : 'Historique des dépôts' ?></h2>
        <form class="form lf-filter" method="get" action="/espace-client#mes-pieces">
            <?php if ($cabinet): ?>
                <div class="form__row">
                    <label for="dossier">Dossier</label>
                    <select id="dossier" name="dossier">
                        <option value="">Tous les dossiers</option>
                        <?php foreach ($dossiers as $client): ?>
                            <option value="<?= e($client['id']) ?>" <?= (string) $client['id'] === $filters['dossier'] ? 'selected' : '' ?>><?= e($client['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form__row">
                <label for="filtre-periode">Mois</label>
                <input type="month" name="periode" id="filtre-periode" min="2000-01" max="2099-12"
                       pattern="20[0-9]{2}-(0[1-9]|1[0-2])" placeholder="AAAA-MM" value="<?= e($filters['period']) ?>">
            </div>
            <div class="form__row">
                <label for="filtre-statut">Statut</label>
                <select id="filtre-statut" name="statut">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $key === $filters['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filtrer</button>
            <a class="lf-reset" href="/espace-client#mes-pieces">Effacer les filtres</a>
        </form>
        <?php if (!$pieces): ?>
            <p class="lf-empty"><?= array_filter($filters) || $page > 1 ? 'Aucune pièce ne correspond à cette sélection.' : ($cabinet ? 'Aucune pièce reçue pour l’instant.' : 'Aucun dépôt pour l’instant. Vos pièces apparaîtront ici après leur envoi.') ?></p>
        <?php else: ?>
            <ul class="lf-list">
                <?php foreach ($pieces as $piece): ?>
                    <li class="lf-piece">
                        <div class="lf-piece__info">
                            <strong><?= e($piece['nom']) ?></strong>
                            <p class="lf-muted">
                                <?= $cabinet ? e($piece['dossier']) . ' · ' : '' ?>
                                <?= e($categories[$piece['categorie']] ?? 'Autres justificatifs') ?>
                                · <?= e($piece['periode']) ?> · <?= e(max(1, (int) ceil((int) $piece['octets'] / 1024))) ?> Ko
                            </p>
                        </div>
                        <span class="lf-status lf-status--<?= e($piece['statut']) ?>"><?= e($statuses[$piece['statut']]) ?></span>
                        <a class="btn btn--secondary" href="/espace-client/telecharger?id=<?= e($piece['id']) ?>"
                           aria-label="Télécharger <?= e($piece['nom']) ?>">Télécharger</a>
                        <?php if ($cabinet): ?>
                            <form method="post" action="/espace-client/statut" class="lf-piece__status form">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="id" value="<?= e($piece['id']) ?>">
                                <label class="visually-hidden" for="etat-<?= e($piece['id']) ?>">Statut de <?= e($piece['nom']) ?></label>
                                <div class="form__row">
                                    <select id="etat-<?= e($piece['id']) ?>" name="statut">
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?= e($key) ?>" <?= $key === $piece['statut'] ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn--secondary">Enregistrer</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($page > 1 || $more): ?>
            <nav class="lf-pagination" aria-label="Pages de résultats">
                <?php if ($page > 1): ?><a href="<?= e($pageUrl($page - 1)) ?>">Page précédente</a><?php endif; ?>
                <span>Page <?= e($page) ?></span>
                <?php if ($more): ?><a href="<?= e($pageUrl($page + 1)) ?>">Page suivante</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</section>
