#!/usr/bin/env python3
"""Recette sans SQL, dans une copie temporaire. Aucun devis réel n’est consulté."""
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
from urllib.parse import urlencode


def main():
    source = Path(__file__).resolve().parents[2]
    php = os.environ.get('PHP_BIN', 'php')
    php = str(Path(php).resolve()) if '/' in php else php
    with tempfile.TemporaryDirectory(prefix='lf-devis-') as root:
        root = Path(root)
        app = root / 'site'
        app.mkdir()
        for dirname in ('app', 'assets'):
            shutil.copytree(source / dirname, app / dirname, ignore=shutil.ignore_patterns('config.local.php'))
        shutil.copy(source / 'index.php', app)
        (app / 'tools').mkdir()
        shutil.copy(source / 'tools/devis.php', app / 'tools')
        sessions = root / 'sessions'
        sessions.mkdir(mode=0o700)
        with socket.socket() as reservation:
            reservation.bind(('127.0.0.1', 0))
            port = reservation.getsockname()[1]

        def run(*args):
            return subprocess.check_output([php, '-n', *args], cwd=app, text=True)

        assert run('-r', 'echo extension_loaded("pdo_mysql") ? "MYSQL" : "SANS MYSQL";') == 'SANS MYSQL'
        with (root / 'server.log').open('w') as log:
            server = subprocess.Popen([php, '-n', '-d', f'session.save_path={sessions.resolve().as_posix()}',
                                       '-S', f'127.0.0.1:{port}', 'index.php'], cwd=app, stdout=log, stderr=log)
            cookie = None

            def request(path, expected, fields=None, method=None):
                nonlocal cookie
                conn = http.client.HTTPConnection('127.0.0.1', port, timeout=5)
                headers = {'Cookie': cookie} if cookie else {}
                if fields is not None:
                    headers['Content-Type'] = 'application/x-www-form-urlencoded'
                try:
                    conn.request(method or ('GET' if fields is None else 'POST'), path,
                                 None if fields is None else urlencode(fields), headers=headers)
                    result = conn.getresponse()
                    data = result.read().decode()
                    assert result.status == expected, (path, result.status, expected)
                    if result.headers.get('Set-Cookie'):
                        cookie = result.headers['Set-Cookie'].split(';', 1)[0]
                    assert 'Fatal error' not in data
                    return data, result.headers
                finally:
                    conn.close()

            try:
                for retry in range(40):
                    try:
                        home, headers = request('/', 200)
                        break
                    except ConnectionRefusedError:
                        if retry == 39:
                            raise
                        time.sleep(.05)
                assert 'vitrine.css' in home and 'la-fiduciaire.png' in home
                assert 'hero__media' in home and 'hero-video.webm' in home and 'hero-video.mp4' in home
                assert 'hero-video-poster.jpg' in home and 'data-hero-toggle' in home
                assert 'Votre comptabilité, en clair.' in home and 'Set-Cookie' not in headers
                assert 'href="/espace-client"' not in home
                for path in ('/prestations', '/tarifs', '/zones', '/mentions-legales', '/assets/css/vitrine.css'):
                    request(path, 200)
                request('/espace-client', 503)
                request('/tools/devis.php', 404)
                request('/app/portal/quote-store.php', 404)

                def form():
                    page, headers = request('/devis', 200)
                    assert 'no-store' in headers['Cache-Control'] and 'noindex' in headers['X-Robots-Tag']
                    assert 'quote-hero' in page
                    return {k: re.search(f'name="{k}" value="([a-f0-9]+)"', page)[1] for k in ('csrf', 'envoi')}

                quote = form() | {'nom': 'Contact fictif LF', 'email': 'test@example.invalid', 'message': 'Devis fictif', 'consentement': '1'}
                request('/devis', 403, quote | {'csrf': 'incorrect'})
                request('/devis', 405, method='PUT')
                request('/devis', 413, quote | {'message': 'x' * 33000})
                page, _ = request('/devis', 422, quote | {'email': 'invalide', 'message': '<b>Test</b>'})
                assert 'aria-invalid="true"' in page and '&lt;b&gt;Test&lt;/b&gt;' in page
                request('/devis', 422, quote | {'site_web': 'non vide'})
                storage = Path(run('tools/devis.php', 'chemin').strip())
                assert not storage.is_relative_to(app)
                if os.name != 'nt':
                    assert storage.stat().st_mode & 0o777 == 0o700
                assert not list(storage.glob('[a-f0-9]*.json'))
                request('/devis', 303, quote)
                request('/devis', 303, quote)
                page, _ = request('/devis', 200)
                assert 'Votre demande a été enregistrée' in page
                files = list(storage.glob('[a-f0-9]*.json'))
                assert len(files) == 1
                if os.name != 'nt':
                    assert files[0].stat().st_mode & 0o777 == 0o600
                assert quote['envoi'] in run('tools/devis.php', 'liste')
                data = json.loads(run('tools/devis.php', 'lire', quote['envoi']))
                assert data['email'] == quote['email'] and data['message'] == quote['message']
                assert '127.0.0.1' not in (storage / '.limites.json').read_text()
                request('/storage/' + files[0].name, 404)
                request('/' + storage.name + '/' + files[0].name, 404)

                quote |= form()
                for _ in range(7):
                    request('/devis', 422, quote | {'email': 'invalide'})
                request('/devis', 429, quote)
                if os.name != 'nt':
                    storage.chmod(0o755)
                    page, _ = request('/devis', 503, quote)
                    assert 'Votre demande a été enregistrée' not in page and str(storage) not in page
                    storage.chmod(0o700)
                assert len(list(storage.glob('[a-f0-9]*.json'))) == 1
            finally:
                server.terminate()
                server.wait(timeout=5)
        logs = (root / 'server.log').read_text()
        assert 'PHP Warning' not in logs and 'test@example.invalid' not in logs and 'Devis fictif' not in logs
        print('PASS · vitrine LF, devis sans SQL, CSRF, validation, taille, honeypot, dédoublonnage, CLI privée, quota et échec de stockage')


if __name__ == '__main__':
    main()
