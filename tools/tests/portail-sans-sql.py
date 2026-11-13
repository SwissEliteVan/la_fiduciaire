#!/usr/bin/env python3
"""Recette portail sans SQL, dans une copie temporaire jetable.

Aucune base MySQL, aucun SQLite : comptes, métadonnées et pièces sont stockés
dans un dossier privé hors racine web. Comptes et documents fictifs uniquement.
"""
import http.client
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import uuid
from urllib.parse import urlencode

PHP = os.environ.get('PHP_BIN', 'php')


class Browser:
    def __init__(self, port):
        self.port = port
        self.cookie = None

    def request(self, path, fields=None, files=None, method=None, expect=200):
        conn = http.client.HTTPConnection('127.0.0.1', self.port, timeout=30)
        headers = {'Cookie': self.cookie} if self.cookie else {}
        body = None
        if files:
            boundary = 'lf-test-' + uuid.uuid4().hex
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
            body = b''
            for key, value in (fields or {}).items():
                body += f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
            for name, mime, data in files:
                body += (f'--{boundary}\r\nContent-Disposition: form-data; name="pieces[]"; filename="{name}"\r\n'
                         f'Content-Type: {mime}\r\n\r\n').encode() + data + b'\r\n'
            body += f'--{boundary}--\r\n'.encode()
        elif fields is not None:
            headers['Content-Type'] = 'application/x-www-form-urlencoded'
            body = urlencode(fields).encode()
        try:
            conn.request(method or ('POST' if body is not None else 'GET'), path, body=body, headers=headers)
            response = conn.getresponse()
            payload = response.read()
            if response.getheader('Set-Cookie'):
                self.cookie = response.getheader('Set-Cookie').split(';', 1)[0]
            if response.status != expect:
                raise AssertionError(f'{path.split("?")[0]}: attendu {expect}, obtenu {response.status}')
            assert b'Fatal error' not in payload and b'Warning:</b>' not in payload
            return payload, response.headers
        finally:
            conn.close()

    def token(self, path):
        html, _ = self.request(path)
        result = re.search(rb'name="csrf" value="([a-f0-9]{64})"', html)
        assert result, path
        return result[1].decode()


def provision(site, action, email, *args):
    result = subprocess.run([PHP, 'tools/portal/comptes.php', action, email, *args],
                            cwd=site, check=True, capture_output=True, text=True)
    match = re.search(r'^[a-f0-9]{64}$', result.stdout, re.M)
    return match[0] if match else None


def activate(browser, code, password):
    csrf = browser.token('/espace-client/activation')
    browser.request('/espace-client/activation',
                    {'csrf': csrf, 'code': code, 'password': password, 'repeat': password},
                    expect=303)


def login(browser, email, password):
    csrf = browser.token('/espace-client/connexion')
    browser.request('/espace-client/connexion',
                    {'csrf': csrf, 'email': email, 'password': password}, expect=303)
    browser.token('/espace-client')


def config_php(root, site):
    portal = str(root / 'private' / 'portal')
    quotes = str(root / 'private' / 'quotes')
    scanner = str(site / 'tools' / 'tests' / 'scanner-factice.php')
    php = str(Path(PHP).resolve()) if os.path.isabs(PHP) else PHP
    return f"""<?php
declare(strict_types=1);
return [
    'env' => 'local',
    'debug' => false,
    'locale' => 'fr',
    'base_url' => '',
    'db' => ['host' => 'localhost', 'port' => 3306, 'name' => 'REMPLACER_PAR_LE_NOM_DE_BASE',
             'user' => 'REMPLACER_PAR_UTILISATEUR', 'password' => 'REMPLACER_PAR_MOT_DE_PASSE',
             'charset' => 'utf8mb4'],
    'portal' => [
        'enabled' => true,
        'backend' => 'files',
        'storage_dir' => {json.dumps(portal)},
        'secret' => {json.dumps(os.urandom(32).hex())},
        'scanner' => '',
        'scanner_command' => [{json.dumps(php)}, {json.dumps(scanner)}],
    ],
    'quote_enabled' => true,
    'quote_backend' => 'files',
    'quote_storage_dir' => {json.dumps(quotes)},
    'contact' => ['email' => null, 'telephone' => null],
];
"""


def main():
    source = Path(__file__).resolve().parents[2]
    with tempfile.TemporaryDirectory(prefix='lf-portail-') as tmp:
        root = Path(tmp)
        site = root / 'site'
        site.mkdir()
        for dirname in ('app', 'assets'):
            shutil.copytree(source / dirname, site / dirname,
                            ignore=shutil.ignore_patterns('config.local.php'))
        shutil.copy(source / 'index.php', site)
        (site / 'tools' / 'portal').mkdir(parents=True)
        (site / 'tools' / 'tests').mkdir(parents=True)
        shutil.copy(source / 'tools' / 'devis.php', site / 'tools')
        shutil.copy(source / 'tools' / 'portal' / 'comptes.php', site / 'tools' / 'portal')
        shutil.copy(source / 'tools' / 'tests' / 'scanner-factice.php', site / 'tools' / 'tests')
        (root / 'private').mkdir(mode=0o700)
        (site / 'app' / 'config.local.php').write_text(config_php(root, site), encoding='utf-8')

        sessions = root / 'sessions'
        sessions.mkdir(mode=0o700)
        with socket.socket() as reservation:
            reservation.bind(('127.0.0.1', 0))
            port = reservation.getsockname()[1]

        log = (root / 'server.log').open('w')
        server = subprocess.Popen([
            PHP, '-n',
            '-d', f'session.save_path={sessions.resolve().as_posix()}',
            '-d', 'upload_max_filesize=10M',
            '-d', 'post_max_size=55M',
            '-d', 'max_file_uploads=6',
            '-S', f'127.0.0.1:{port}', 'index.php',
        ], cwd=site, stdout=log, stderr=log)

        try:
            public = Browser(port)
            for _ in range(60):
                try:
                    home, home_headers = public.request('/')
                    break
                except ConnectionRefusedError:
                    time.sleep(.05)
            else:
                raise AssertionError('serveur non démarré')

            for marker in (b'la-fiduciaire.png', b'brand.css', b'vitrine.css', b'hero-video.webm',
                           b'hero-video.mp4', b'hero-video-poster.jpg', b'data-hero-toggle',
                           b'Votre comptabilit', b'Espace client'):
                assert marker in home, marker
            assert 'Set-Cookie' not in home_headers
            engine = subprocess.check_output([
                PHP, '-n', '-r',
                'echo extension_loaded("pdo_mysql") ? "MYSQL" : "SANS MYSQL"; echo extension_loaded("pdo_sqlite") ? " SQLITE" : " SANS SQLITE";',
            ], cwd=site, text=True)
            assert engine == 'SANS MYSQL SANS SQLITE', engine
            for path in ('/prestations', '/tarifs', '/zones', '/mentions-legales',
                         '/assets/css/brand.css', '/assets/css/vitrine.css',
                         '/assets/brand/la-fiduciaire.png'):
                public.request(path)
            brand, _ = public.request('/assets/css/brand.css')
            vitrine, _ = public.request('/assets/css/vitrine.css')
            portal_css, _ = public.request('/assets/css/portal.css')
            assert b'#0f3d5a' in brand and b'#e8f3fa' in brand
            assert b'@media (max-width: 720px)' in vitrine
            assert b'@media (max-width: 640px)' in portal_css
            assert b'min-height: 44px' in vitrine and b'min-height: 44px' in portal_css

            _, redirect_headers = public.request('/espace-client', expect=303)
            assert redirect_headers['Location'] == '/espace-client/connexion'
            public.request('/app/portal/store.php', expect=404)
            public.request('/storage/state.json', expect=404)

            run = uuid.uuid4().hex[:8]
            password = 'Fictif-Seulement-2026!'
            cabinet, alpha, bravo = [Browser(port) for _ in range(3)]

            email_c = f'cabinet-{run}@example.invalid'
            email_a = f'alpha-{run}@example.invalid'
            email_b = f'bravo-{run}@example.invalid'
            dossier_b = f'Dossier Bravo {run}'

            code_c = provision(site, 'cabinet', email_c)
            code_b = provision(site, 'nouveau-client', email_b, dossier_b)
            assert code_c and code_b and code_c != code_b
            activate(cabinet, code_c, password)
            login(cabinet, email_c, password)
            activate(bravo, code_b, password)
            login(bravo, email_b, password)

            csrf_c = cabinet.token('/espace-client')
            cabinet.request('/espace-client/inviter',
                            {'csrf': csrf_c, 'email': email_a, 'dossier': '',
                             'dossier_nom': f'Dossier Alpha {run}'}, expect=303)
            page, _ = cabinet.request('/espace-client/inviter')
            match = re.search(rb'<code>([a-f0-9]{64})</code>', page)
            assert match, 'code d activation absent'
            code_a = match[1].decode()
            page, _ = cabinet.request('/espace-client/inviter')
            assert b'<code>' not in page, 'code affiche deux fois'
            activate(alpha, code_a, password)
            login(alpha, email_a, password)
            alpha.request('/espace-client/inviter', expect=403)
            csrf_c = cabinet.token('/espace-client')
            cabinet.request('/espace-client/inviter',
                            {'csrf': csrf_c, 'email': email_a, 'dossier': '',
                             'dossier_nom': 'Dossier en double'}, expect=422)
            csrf_act = alpha.token('/espace-client/activation')
            alpha.request('/espace-client/activation',
                          {'csrf': csrf_act, 'code': code_a, 'password': password, 'repeat': password},
                          expect=422)
            print('PASS · invitation cabinet, code unique, activation et connexion')
            # Prospect : devis réellement enregistré une seule fois.
            page, _ = public.request('/devis')
            quote_id = re.search(rb'name="envoi" value="([a-f0-9]{32})"', page)[1].decode()
            csrf_q = public.token('/devis')
            quote = {
                'csrf': csrf_q, 'envoi': quote_id, 'nom': 'Contact fictif',
                'email': f'prospect-{run}@example.invalid',
                'message': f'Devis fictif {run}', 'consentement': '1',
            }
            public.request('/devis', quote | {'email': 'invalide'}, expect=422)
            public.request('/devis', quote, expect=303)
            public.request('/devis', quote, expect=303)
            page, _ = public.request('/devis')
            assert 'Votre demande a été enregistrée'.encode() in page
            quote_files = list((root / 'private' / 'quotes').glob('[a-f0-9]*.json'))
            assert len(quote_files) == 1, 'devis enregistré plusieurs fois'
            page, _ = cabinet.request('/espace-client/demandes')
            assert quote['email'].encode() in page and quote['message'].encode() in page
            alpha.request('/espace-client/demandes', expect=403)
            print('PASS · devis unique, confirmation réelle, consultation cabinet et refus client')

            # Dépôt client : validations, scanner, deux pièces réelles.
            csrf_a = alpha.token('/espace-client')
            import base64
            png = base64.b64decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jOhUAAAAASUVORK5CYII=')
            pdf = b'%PDF-1.4\n% Document fictif pour test LF\n%%EOF\n'
            fields = {'csrf': csrf_a, 'periode': '2026-11', 'categorie': 'achats'}
            files = [(f'alpha-{run}.png', 'image/png', png), (f'fictif-{run}.pdf', 'application/pdf', pdf)]
            alpha.request('/espace-client/deposer', fields,
                          [('faux.pdf', 'application/pdf', b'non pdf')], expect=422)
            alpha.request('/espace-client/deposer', fields, files * 3, expect=422)
            alpha.request('/espace-client/deposer', fields,
                          [('refus.pdf', 'application/pdf', pdf + b'LF_SCANNER_REFUSE')], expect=422)
            alpha.request('/espace-client/deposer', fields,
                          [('panne.pdf', 'application/pdf', pdf + b'LF_SCANNER_FAIL')], expect=503)
            no_csrf = dict(fields)
            no_csrf.pop('csrf')
            alpha.request('/espace-client/deposer', no_csrf, files, expect=403)
            alpha.request('/espace-client/deposer', fields, files, expect=303)

            dashboard, _ = alpha.request('/espace-client')
            ids = [value.decode() for value in re.findall(rb'telecharger\?id=([a-f0-9]{32})', dashboard)]
            assert len(ids) == 2, ids
            assert files[0][0].encode() in dashboard and files[1][0].encode() in dashboard
            assert 'Reçu'.encode() in dashboard
            path = '/espace-client/telecharger?id=' + ids[0]
            binary, headers = alpha.request(path)
            assert binary in (png, pdf)
            assert headers['Content-Type'] == 'application/octet-stream'
            assert headers['Content-Disposition'].startswith('attachment;')
            binary, _ = alpha.request(path, method='HEAD')
            assert not binary
            public.request(path, expect=303)
            story = root / 'private' / 'portal' / 'documents'
            assert len(list(story.glob('[a-f0-9]*'))) == 2
            assert not list(story.glob('*.png')) and not list(story.glob('*.pdf'))
            public.request('/private/portal/documents/' + ids[0], expect=404)
            print('PASS · dépôt réel de 2 pièces, téléchargement identique, stockage privé renommé')

            # Cloisonnement : le second client ne voit ni ne télécharge.
            bravo.request(path, expect=404)
            bravo_dashboard, _ = bravo.request('/espace-client?dossier=1')
            assert files[0][0].encode() not in bravo_dashboard
            assert files[1][0].encode() not in bravo_dashboard
            print('PASS · second client : aucune vue, aucun téléchargement')

            # Cabinet : lecture par dossier, téléchargement, statut.
            cabinet_dashboard, _ = cabinet.request('/espace-client')
            assert files[0][0].encode() in cabinet_dashboard
            assert f'Dossier Alpha {run}'.encode() in cabinet_dashboard
            cabinet.request(path)
            csrf_c = cabinet.token('/espace-client')
            cabinet.request('/espace-client/statut',
                            {'csrf': csrf_c, 'id': ids[0], 'statut': 'a_remplacer'}, expect=303)
            alpha.request('/espace-client/statut',
                          {'csrf': csrf_a, 'id': ids[0], 'statut': 'traite'}, expect=403)
            cabinet.request('/espace-client/deposer', {'csrf': csrf_c}, expect=403)
            alpha_dashboard, _ = alpha.request('/espace-client?statut=a_remplacer')
            assert ids[0].encode() in alpha_dashboard and 'À remplacer'.encode() in alpha_dashboard
            print('PASS · cabinet : dossier, téléchargement, statut visible par le bon client')

            code_b2 = provision(site, 'reinitialiser', email_b)
            bravo.request('/espace-client', expect=303)
            activate(bravo, code_b2, password)
            login(bravo, email_b, password)
            provision(site, 'revoquer', email_b)
            bravo.request('/espace-client', expect=303)
            print('PASS · réinitialisation et révocation en mode fichiers')

            state = json.loads((root / 'private' / 'portal' / 'state.json').read_text(encoding='utf-8'))
            assert len(state['pieces']) == 2
            assert {piece['statut'] for piece in state['pieces']} == {'recu', 'a_remplacer'}
            assert any(event['action'] == 'depot' for event in state['evenements'])
            assert any(event['action'] == 'statut' for event in state['evenements'])
            print('PASS · métadonnées JSON cohérentes et événements journalisés')
        finally:
            server.terminate()
            server.wait(timeout=5)
            log.close()

        text = (root / 'server.log').read_text(encoding='utf-8', errors='replace')
        assert 'Fatal error' not in text and 'PHP Warning' not in text, text
        assert email_a not in text and quote['message'] not in text
        print('PASS · aucun warning PHP, aucune donnée personnelle dans le journal')


if __name__ == '__main__':
    main()
