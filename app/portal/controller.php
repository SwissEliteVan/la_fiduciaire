<?php

declare(strict_types=1);

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/documents.php';

function lf_portal_dispatch(string $path, string $method): void
{
    ini_set('display_errors', '0');
    lf_private_headers();
    $routes = [
        '/espace-client' => ['GET', 'HEAD'],
        '/espace-client/connexion' => ['GET', 'HEAD', 'POST'],
        '/espace-client/activation' => ['GET', 'HEAD', 'POST'],
        '/espace-client/deconnexion' => ['POST'],
        '/espace-client/deposer' => ['POST'],
        '/espace-client/telecharger' => ['GET', 'HEAD'],
        '/espace-client/statut' => ['POST'],
    ];
    if (!isset($routes[$path])) {
        lf_error('Page introuvable', 'Cette page ne fait pas partie de votre espace.', 404);
        return;
    }
    if (!in_array($method, $routes[$path], true)) {
        header('Allow: ' . implode(', ', $routes[$path]));
        lf_error('Action indisponible', 'Revenez à votre espace pour poursuivre.', 405);
        return;
    }
    if (config('portal.enabled', false) !== true) {
        lf_error('Espace client non ouvert', 'Le dépôt de pièces n’est pas encore ouvert. Contactez votre interlocuteur à La Fiduciaire.', 503);
        return;
    }

    try {
        lf_session();
        if ($method === 'POST' && !lf_csrf_ok()) {
            if ($path === '/espace-client/deposer' && $_POST === [] && $_FILES === []
                && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                lf_error('Envoi trop volumineux', 'Réduisez la taille du lot avant de réessayer.', 413);
            } else {
                lf_error('Formulaire expiré', 'Revenez à votre espace puis recommencez l’action.', 403);
            }
            return;
        }
        if ($path === '/espace-client/deconnexion') {
            lf_clear_identity();
            lf_redirect('/espace-client/connexion');
        }
        if ($path === '/espace-client/connexion') {
            lf_login($method);
            return;
        }
        if ($path === '/espace-client/activation') {
            lf_activate($method);
            return;
        }
        $user = lf_auth_user();
        if (!$user) {
            if ($method === 'POST') {
                lf_error('Connexion nécessaire', 'Connectez-vous de nouveau avant de reprendre cette action.', 401);
                return;
            }
            lf_redirect('/espace-client/connexion');
        }

        switch ($path) {
            case '/espace-client/deposer':
                if ($user['role'] !== 'client') {
                    lf_error('Action réservée aux clients', 'Les pièces sont déposées depuis un dossier client.', 403);
                    return;
                }
                $period = lf_text($_POST, 'periode');
                $category = lf_text($_POST, 'categorie');
                if (!lf_period($period) || !isset(lf_categories()[$category])) {
                    lf_show_dashboard($user, 'Indiquez un mois et une catégorie, puis sélectionnez les pièces.', 422, $period, $category);
                    return;
                }
                if (!lf_rate('upload', (string) $user['id'], 20)) {
                    lf_show_dashboard($user, 'Limite temporaire de dépôts atteinte. Réessayez dans quelques minutes.', 429, $period, $category);
                    return;
                }
                try {
                    $files = $_FILES['pieces'] ?? null;
                    if (!is_array($files)) {
                        throw new DomainException('Choisissez au moins un fichier.');
                    }
                    $count = lf_save_uploads($user, $period, $category, lf_uploads($files));
                } catch (DomainException $error) {
                    lf_show_dashboard($user, $error->getMessage(), 422, $period, $category);
                    return;
                }
                $_SESSION['flash'] = $count === 1 ? '1 pièce reçue.' : $count . ' pièces reçues.';
                lf_redirect('/espace-client#mes-pieces');

            case '/espace-client/telecharger':
                lf_download($user, lf_text($_GET, 'id'), $method === 'HEAD');
                return;

            case '/espace-client/statut':
                if ($user['role'] !== 'cabinet') {
                    lf_error('Action réservée au cabinet', 'Seul le cabinet peut modifier le statut d’une pièce.', 403);
                    return;
                }
                $id = lf_text($_POST, 'id');
                $status = lf_text($_POST, 'statut');
                if (!isset(lf_statuses()[$status])) {
                    lf_error('Statut invalide', 'Sélectionnez un statut proposé dans la liste.', 422);
                    return;
                }
                $piece = lf_piece($user, $id);
                if (!$piece) {
                    lf_error('Pièce introuvable', 'Cette pièce n’est pas disponible.', 404);
                    return;
                }
                $db = lf_db();
                $db->beginTransaction();
                try {
                    $changed = lf_query('UPDATE lf_pieces SET statut = ? WHERE id = ? AND statut <> ?', [$status, $id, $status])->rowCount();
                    if ($changed) {
                        lf_audit($id, $user, 'statut', $status);
                    }
                    $db->commit();
                } catch (Throwable $error) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    throw $error;
                }
                $_SESSION['flash'] = 'Statut enregistré.';
                lf_redirect('/espace-client?dossier=' . rawurlencode((string) $piece['dossier_id']) . '#mes-pieces');

            default:
                lf_show_dashboard($user);
        }
    } catch (Throwable $error) {
        error_log('[LF portail] Service indisponible (' . get_class($error) . ').');
        lf_error('Espace temporairement indisponible', 'L’action n’a pas pu être confirmée. Vérifiez votre historique avant de réessayer.', 503);
    }
}

function lf_login(string $method): void
{
    $error = null;
    $email = '';
    $status = 200;
    if ($method === 'POST') {
        $email = lf_email(lf_text($_POST, 'email')) ?? '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $allowedIp = lf_rate('connexion-ip', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), 40);
        $allowedAccount = $allowedIp && ($email === '' || lf_rate('connexion-compte', $email, 12));
        if (!$allowedAccount) {
            $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
            $status = 429;
        } else {
            $user = $email !== '' ? lf_query('SELECT id, role, dossier_id, password_hash, version_session FROM lf_comptes
                WHERE email = ? AND actif = 1', [$email])->fetch() : false;
            $hash = $user['password_hash'] ?? '$2y$12$0K/.EzGMS7C8Uf7wTyPnG.OV3sVWv2P6Gh17OVslGlDncZUOGfM8W';
            $verified = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
            if ($verified && $user && $user['password_hash'] !== null
                && (($user['role'] === 'client') === ($user['dossier_id'] !== null))) {
                lf_clear_identity();
                $_SESSION['uid'] = (string) $user['id'];
                $_SESSION['version'] = (string) $user['version_session'];
                $_SESSION['vu'] = $_SESSION['debut'] = time();
                lf_redirect('/espace-client');
            }
            $error = 'L’adresse e-mail ou le mot de passe est incorrect.';
            $status = 422;
        }
    }
    render('portal-access', ['mode' => 'login', 'pageTitle' => 'Connexion — La Fiduciaire', 'error' => $error, 'email' => $email], $status);
}

function lf_activate(string $method): void
{
    $error = null;
    $status = 200;
    if ($method === 'POST') {
        $token = lf_text($_POST, 'code');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $repeat = is_string($_POST['repeat'] ?? null) ? $_POST['repeat'] : '';
        if (!lf_rate('activation-ip', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), 20)) {
            $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
            $status = 429;
        } elseif (strlen($password) > 72 || !preg_match('//u', $password) || preg_match_all('/./us', $password) < 12 || str_contains($password, "\0") || $password !== $repeat) {
            $error = 'Les deux mots de passe doivent correspondre et respecter la longueur indiquée.';
            $status = 422;
        } elseif (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $error = 'Le code est invalide ou expiré. Demandez un nouveau code au cabinet.';
            $status = 422;
        } else {
            $hash = hash('sha256', $token);
            $exists = lf_query('SELECT id FROM lf_comptes WHERE activation_hash = ? AND activation_expire > UTC_TIMESTAMP() AND actif = 1',
                [$hash])->fetchColumn();
            $updated = $exists ? lf_query('UPDATE lf_comptes SET password_hash = ?, activation_hash = NULL, activation_expire = NULL,
                version_session = version_session + 1 WHERE id = ? AND activation_hash = ? AND activation_expire > UTC_TIMESTAMP() AND actif = 1',
                [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $exists, $hash])->rowCount() : 0;
            if ($updated === 1) {
                lf_clear_identity();
                $_SESSION['activated'] = true;
                lf_redirect('/espace-client/connexion');
            }
            $error = 'Le code est invalide ou expiré. Demandez un nouveau code au cabinet.';
            $status = 422;
        }
    }
    render('portal-access', ['mode' => 'activate', 'pageTitle' => 'Activation — La Fiduciaire', 'error' => $error], $status);
}

function lf_show_dashboard(array $user, ?string $error = null, int $status = 200, ?string $period = null, ?string $category = null): void
{
    $data = lf_listing($user);
    $data['user'] = $user;
    $data['error'] = $error;
    $data['uploadPeriod'] = $period ?? (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('Y-m');
    $data['uploadCategory'] = $category ?? '';
    $data['notice'] = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    $data['pageTitle'] = ($user['role'] === 'cabinet' ? 'Dossiers clients' : 'Mes pièces comptables') . ' — La Fiduciaire';
    render('portal-dashboard', $data, $status);
}
