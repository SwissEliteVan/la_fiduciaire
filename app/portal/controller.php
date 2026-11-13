<?php

declare(strict_types=1);

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/documents.php';
require_once __DIR__ . '/quote-store.php';

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
        '/espace-client/inviter' => ['GET', 'HEAD', 'POST'],
        '/espace-client/demandes' => ['GET', 'HEAD'],
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
    if (!lf_portal_enabled()) {
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
                lf_piece_status($id, $status, (string) $user['id']);
                $_SESSION['flash'] = 'Statut enregistré.';
                lf_redirect('/espace-client?dossier=' . rawurlencode((string) $piece['dossier_id']) . '#mes-pieces');

            case '/espace-client/inviter':
                lf_invite($user, $method);
                return;

            case '/espace-client/demandes':
                lf_quotes($user);
                return;

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
            $user = $email !== '' ? lf_account_by_email($email) : null;
            $hash = $user['password_hash'] ?? '$2y$12$0K/.EzGMS7C8Uf7wTyPnG.OV3sVWv2P6Gh17OVslGlDncZUOGfM8W';
            $verified = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
            if ($verified && is_array($user) && ($user['password_hash'] ?? null) !== null
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
            $updated = lf_account_activate($hash, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
            if ($updated) {
                lf_clear_identity();
                $_SESSION['activated'] = true;
                lf_redirect('/espace-client/connexion');
            }
            $error = 'Le code est invalide ou expiré. Demandez un nouveau code au cabinet.';
            $status = 422;
        }
    }
    render('portal-access', [
        'mode' => 'activate',
        'pageTitle' => 'Activation — La Fiduciaire',
        'error' => $error,
        'code' => preg_match('/^[a-f0-9]{64}$/D', lf_text($_GET, 'code')) === 1 ? lf_text($_GET, 'code') : '',
    ], $status);
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
/** Invitation cabinet : crée le dossier au besoin et affiche le code une seule fois. */
function lf_invite(array $user, string $method): void
{
    if ($user['role'] !== 'cabinet') {
        lf_error('Action réservée au cabinet', 'Seul le cabinet peut inviter un client.', 403);
        return;
    }

    $error = null;
    $status = 200;
    $once = $_SESSION['invite_once'] ?? null;
    unset($_SESSION['invite_once']);

    if ($method === 'POST') {
        if (!lf_rate('invitation', (string) $user['id'], 20)) {
            $error = 'Trop d’invitations en peu de temps. Réessayez dans quelques minutes.';
            $status = 429;
        } else {
            $email = lf_email(lf_text($_POST, 'email'));
            $dossierId = lf_text($_POST, 'dossier');
            $name = trim(lf_text($_POST, 'dossier_nom'));
            $nameLength = preg_match_all('/./us', $name);

            if ($email === null) {
                $error = 'Indiquez une adresse e-mail valide.';
                $status = 422;
            } elseif ($dossierId !== '' && (!ctype_digit($dossierId) || lf_dossier_name($dossierId) === null)) {
                $error = 'Choisissez un dossier existant dans la liste.';
                $status = 422;
            } elseif ($dossierId === '' && ($name === '' || $nameLength < 2 || $nameLength > 160)) {
                $error = 'Indiquez un nom de dossier de 2 à 160 caractères.';
                $status = 422;
            } elseif (lf_account_by_email($email) !== null) {
                $error = 'Un compte existe déjà pour cette adresse.';
                $status = 422;
            } else {
                try {
                    if ($dossierId === '') {
                        $dossierId = lf_dossier_create($name);
                    }
                    $token = bin2hex(random_bytes(32));
                    lf_account_create($email, $dossierId, false, hash('sha256', $token), time() + 86400);
                    $_SESSION['invite_once'] = [
                        'email'   => $email,
                        'code'    => $token,
                        'dossier' => lf_dossier_name($dossierId),
                    ];
                    lf_redirect('/espace-client/inviter');
                } catch (DomainException $exception) {
                    $error = $exception->getMessage();
                    $status = 422;
                }
            }
        }
    }

    render('portal-invite', [
        'pageTitle' => 'Inviter un client — La Fiduciaire',
        'dossiers'  => lf_dossiers(),
        'error'     => $error,
        'once'      => is_array($once) ? $once : null,
    ], $status);
}

/** Demande de devis : consultation réservée au cabinet. */
function lf_quotes(array $user): void
{
    if ($user['role'] !== 'cabinet') {
        lf_error('Action réservée au cabinet', 'Les demandes de devis ne sont visibles que par le cabinet.', 403);
        return;
    }
    render('portal-quotes', [
        'pageTitle' => 'Demandes de devis — La Fiduciaire',
        'quotes'    => lf_quote_list(100),
    ]);
}
