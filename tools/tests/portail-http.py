#!/usr/bin/env python3
"""Recette MySQL optionnelle, sur une base et un stockage jetables.

Exige une configuration de recette avec `portal.backend => 'mysql'`,
`quote_backend => 'mysql'`, un secret local et un scanner réel (ou son double
Unix explicitement local). Sans `pdo_mysql`, cette recette n'est pas exécutable.
Ne pas exécuter en production.
"""
import base64
import http.client
import os
import re
import subprocess
import sys
import uuid
from urllib.parse import urlencode


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
            for key, value in fields.items():
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


def provision(php, action, email, *args):
    result = subprocess.run([php, 'tools/portal/comptes.php', action, email, *args], check=True, capture_output=True, text=True)
    match = re.search(r'^[a-f0-9]{64}$', result.stdout, re.M)
    return match[0] if match else None


def activate(browser, code, password, expected=303):
    csrf = browser.token('/espace-client/activation')
    return browser.request('/espace-client/activation', {'csrf': csrf, 'code': code, 'password': password, 'repeat': password}, expect=expected)


def login(browser, email, password):
    csrf = browser.token('/espace-client/connexion')
    browser.request('/espace-client/connexion', {'csrf': csrf, 'email': email, 'password': password}, expect=303)
    return browser.token('/espace-client')


def main():
    php = os.environ.get('PHP_BIN', 'php')
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8123
    run = uuid.uuid4().hex[:8]
    password = 'Fictif-Seulement-2026!'
    alpha, bravo, cabinet, public = [Browser(port) for _ in range(4)]
    email_a, email_b, email_c = [f'{tag}-{run}@example.invalid' for tag in ('alpha', 'bravo', 'cabinet')]
    name_a, name_b = f'Dossier Alpha {run}', f'Dossier Bravo {run}'
    code_a = provision(php, 'nouveau-client', email_a, name_a)
    code_b = provision(php, 'nouveau-client', email_b, name_b)
    code_c = provision(php, 'cabinet', email_c)

    home, _ = public.request('/')
    for marker in (b'hero-video.webm', b'hero-video.mp4', b'hero-video-poster.jpg', b'data-hero-toggle', b'la-fiduciaire.png', b'Espace client'):
        assert marker in home
    for path in ('/prestations', '/tarifs', '/zones', '/mentions-legales', '/assets/css/brand.css', '/assets/brand/apple-touch-icon.png'):
        public.request(path)
    for path in ('/app/config.local.php', '/app/portal/core.php', '/database/portal.sql', '/tools/portal/comptes.php', '/.git/config', '/storage/.gitkeep'):
        public.request(path, expect=404)
    _, headers = public.request('/espace-client', expect=303)
    assert headers['Location'] == '/espace-client/connexion' and 'no-store' in headers['Cache-Control']
    public.request('/espace-client/deposer', method='GET', expect=405)
    public.request('/espace-client/deconnexion', {'csrf': '0' * 64}, expect=403)
    print('Vitrine, accès aux fichiers internes, méthodes et CSRF : OK')

    activate(alpha, code_a, password)
    activate(alpha, code_a, password, 422)
    activate(bravo, code_b, password)
    activate(cabinet, code_c, password)
    csrf_a, csrf_b, csrf_c = [login(browser, email, password) for browser, email in
                              ((alpha, email_a), (bravo, email_b), (cabinet, email_c))]
    dashboard, headers = alpha.request('/espace-client')
    assert name_a.encode() in dashboard and name_b.encode() not in dashboard
    assert headers['X-Robots-Tag'] == 'noindex, nofollow'
    print('Activation à usage unique, sessions et identité du dossier : OK')

    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jOhUAAAAASUVORK5CYII=')
    pdf = b'%PDF-1.4\n% Document fictif pour test LF\n%%EOF\n'
    fields = {'csrf': csrf_a, 'periode': '2026-11', 'categorie': 'achats', 'dossier_id': '99999'}
    files = [('alpha-' + run + '.png', 'image/png', png), ('fictif-' + run + '.pdf', 'application/pdf', pdf)]
    alpha.request('/espace-client/deposer', fields, [('fichier.pdf', 'application/pdf', b'non pdf')], expect=422)
    alpha.request('/espace-client/deposer', fields, files * 3, expect=422)
    alpha.request('/espace-client/deposer', fields, [('volume.pdf', 'application/pdf', b'%PDF-' + b'x' * (10 * 1024 * 1024))], expect=422)
    alpha.request('/espace-client/deposer', fields, [('valide.png', 'image/png', png), ('refus.pdf', 'application/pdf', pdf + b'LF_SCANNER_REFUSE')], expect=422)
    alpha.request('/espace-client/deposer', fields, [('panne.pdf', 'application/pdf', pdf + b'LF_SCANNER_FAIL')], expect=503)
    dashboard, _ = alpha.request('/espace-client')
    assert b'telecharger?id=' not in dashboard
    alpha.request('/espace-client/deposer', fields, files, expect=303)
    dashboard, _ = alpha.request('/espace-client')
    ids = [value.decode() for value in re.findall(rb'telecharger\?id=([a-f0-9]{32})', dashboard)]
    assert len(ids) == 2 and b'2 pi\xc3\xa8ces re\xc3\xa7ues.' in dashboard
    key = ids[0]
    path = '/espace-client/telecharger?id=' + key
    binary, headers = alpha.request(path)
    assert binary in (pdf, png) and headers['Content-Disposition'].startswith('attachment;')
    assert headers['Content-Type'] == 'application/octet-stream'
    binary, _ = alpha.request(path, method='HEAD')
    assert not binary
    public.request(path, expect=303)
    bravo.request(path, expect=404)
    dashboard, _ = bravo.request('/espace-client?dossier=1')
    assert files[0][0].encode() not in dashboard
    cabinet.request(path)
    alpha.request('/espace-client/statut', {'csrf': csrf_a, 'id': key, 'statut': 'traite'}, expect=403)
    cabinet.request('/espace-client/deposer', {'csrf': csrf_c}, expect=403)
    cabinet.request('/espace-client/statut', {'csrf': csrf_c, 'id': key, 'statut': 'a_remplacer'}, expect=303)
    dashboard, _ = alpha.request('/espace-client?statut=a_remplacer')
    assert key.encode() in dashboard and 'À remplacer'.encode() in dashboard
    print('Dépôt multiple, limites, refus/panne du scanner, téléchargements et cloisonnement : OK')

    alpha.request('/espace-client/deconnexion', {'csrf': csrf_a}, expect=303)
    alpha.request(path, expect=303)
    code_b = provision(php, 'reinitialiser', email_b)
    bravo.request('/espace-client', expect=303)
    activate(bravo, code_b, password)
    login(bravo, email_b, password)
    provision(php, 'revoquer', email_b)
    bravo.request('/espace-client', expect=303)
    print('Déconnexion, réinitialisation et révocation : OK')

    page, _ = public.request('/devis')
    quote_id = re.search(rb'name="envoi" value="([a-f0-9]{32})"', page)[1].decode()
    quote = {'csrf': public.token('/devis'), 'envoi': quote_id, 'nom': 'Contact fictif',
             'email': 'invalide', 'message': f'Devis fictif {run}', 'consentement': '1'}
    public.request('/devis', quote, expect=422)
    quote['email'] = f'prospect-{run}@example.invalid'
    public.request('/devis', quote, expect=303)
    public.request('/devis', quote, expect=303)
    page, _ = public.request('/devis')
    assert b'Votre demande a' in page
    subprocess.run([php, '-r',
                    'require "app/bootstrap.php"; require "app/portal/core.php"; '
                    'if ((int) lf_query("SELECT COUNT(*) FROM demandes_devis WHERE email = ?", [$argv[1]])->fetchColumn() !== 1) exit(1);',
                    quote['email']], check=True)
    print('Devis invalide/valide et absence de doublon en base : OK')


if __name__ == '__main__':
    main()
