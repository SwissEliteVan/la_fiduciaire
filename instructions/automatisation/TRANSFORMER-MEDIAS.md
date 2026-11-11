# Transformer les médias — outil local reproductible (modèle complet)

**Ce fichier est uniquement Markdown.** IA A crée `scripts/media/build_media.py` dans le projet cible à partir du bloc Python ci-dessous, l'adapte et le soumet à IA B. **Le script ne publie rien** : ses sorties restent dans `media-staging/`, hors racine servie. Il ne supprime jamais de source. Une licence ne se déduit jamais du nom d'un fichier. Le code n'exécute aucune requête réseau.

## 1. Contrat d'entrée

Structure conseillée :

```text
assets-source/
  photos/atelier.jpg
  videos/hero.mov
  media-rights.json
media-staging/       # généré hors du répertoire public
public/media/        # copie après QA + autorisation de publication
```

Exemple `assets-source/media-rights.json` (les preuves doivent être réelles) :

```json
{
  "photos/atelier.jpg": {"status":"verified", "source":"contrat/autorisation conservée hors Git", "license":"usage commercial autorisé", "role":"section"},
  "videos/hero.mov": {"status":"verified", "source":"production interne documentée", "license":"usage commercial autorisé", "role":"hero"}
}
```

`status=verified` **nécessite** source et licence non vides. Un fichier `pending`, absent du registre, sous licence incertaine ou avec un filigrane suspect ne sera pas transformé. Les preuves personnelles/licences privées sont conservées hors Git ; le registre ne contient que des références non sensibles. L'auteur vérifie également le droit des personnes reconnaissables.

## 2. Script Python — inventaire, images, vidéos, posters et manifeste

Prérequis locaux : Python 3.10+, `ffmpeg`, `ffprobe`, `Pillow` (`pip install pillow` dans un environnement isolé, dépendance épinglée selon le projet). Aucun de ces outils ne doit être installé dans Hostinger pour servir des médias pré-générés.

```python
# scripts/media/build_media.py
"""Conversion contrôlée en staging : --execute obligatoire pour écrire."""
import argparse
import hashlib
import json
import re
import shutil
import subprocess
import unicodedata
from pathlib import Path

IMAGES = {'.jpg', '.jpeg', '.png', '.tiff', '.tif', '.webp'}
VIDEOS = {'.mp4', '.mov', '.mkv', '.webm', '.m4v'}


def digest(path):
    h = hashlib.sha256()
    with path.open('rb') as handle:
        for part in iter(lambda: handle.read(1024 * 1024), b''):
            h.update(part)
    return h.hexdigest()


def slug(text):
    text = unicodedata.normalize('NFKD', text)
    text = text.encode('ascii', 'ignore').decode('ascii').lower()
    return re.sub(r'[^a-z0-9]+', '-', text).strip('-') or 'media'


def run(cmd):
    result = subprocess.run(cmd, capture_output=True, text=True, timeout=900)
    if result.returncode:
        raise RuntimeError(result.stderr[-1500:])


def probe(path):
    p = subprocess.run(['ffprobe', '-v', 'error', '-show_entries',
         'format=duration:stream=width,height,codec_name', '-of', 'json', str(path)],
         capture_output=True, text=True, timeout=40, check=True)
    return json.loads(p.stdout)


def record(path, original, kind):
    return {'path': f'{path.parent.name}/{path.name}', 'bytes': path.stat().st_size,
            'sha256': digest(path), 'from': original, 'format': kind}


def image_variants(source, dest, rel, execute):
    if not execute:
        return [{'planned': str(dest / (slug(source.stem) + '-720.webp'))}]
    from PIL import Image, ImageOps
    products = []
    with Image.open(source) as source_image:
        image = ImageOps.exif_transpose(source_image)
        if image.mode not in ('RGB', 'RGBA'):
            image = image.convert('RGB')
        for width in sorted({min(w, image.width) for w in (480, 960, 1440)}):
            h = max(1, round(image.height * min(1, width / image.width)))
            w = min(width, image.width)
            resized = image.resize((w, h), Image.Resampling.LANCZOS)
            for fmt, ext, opts in [('WEBP', '.webp', {'quality': 80, 'method': 6}),
                                   ('AVIF', '.avif', {'quality': 48})]:
                output = dest / (slug(source.stem) + '-' + str(w) + ext)
                if output.exists():
                    products.append(record(output, rel, fmt))
                    continue
                try:
                    resized.save(output, format=fmt, **opts)
                    products.append(record(output, rel, fmt))
                except (OSError, ValueError) as err:
                    if fmt == 'WEBP':
                        raise
                    print('AVIF indisponible :', source.name, err)
    return products


def video_variants(source, dest, rel, role, execute):
    if not execute:
        return [{'planned': str(dest / (slug(source.stem) + '-720.mp4'))}]
    if not shutil.which('ffmpeg') or not shutil.which('ffprobe'):
        raise RuntimeError('ffmpeg et ffprobe requis pour les vidéos')
    info = probe(source)
    products = []
    stream = next((s for s in info.get('streams', []) if s.get('width')), {})
    actual_width = int(stream.get('width', 0))
    if actual_width < 16:
        raise ValueError('Largeur video invalide ou absente')
    for width in sorted({min(w, actual_width) for w in (720, 1280)}):
        for ext, args in [('.mp4', ['-c:v','libx264','-crf','25','-preset','medium',
                                     '-pix_fmt','yuv420p','-movflags','+faststart']),
                          ('.webm', ['-c:v','libvpx-vp9','-crf','33','-b:v','0'])]:
            output = dest / (slug(source.stem) + '-' + str(width) + ext)
            if not output.exists():
                run(['ffmpeg','-hide_banner','-loglevel','error','-nostdin','-n',
                     '-i',str(source),'-map','0:v:0','-an',
                     '-vf',f'scale=w=min({width}\\,iw):h=-2:flags=lanczos',
                     *args,str(output)])
            products.append(record(output, rel, ext.lstrip('.')))
    if role == 'hero':
        poster = dest / (slug(source.stem) + '-poster.jpg')
        if not poster.exists():
            run(['ffmpeg','-hide_banner','-loglevel','error','-nostdin','-n',
                 '-ss','0.5','-i',str(source),'-frames:v','1','-q:v','3',str(poster)])
        products.append(record(poster, rel, 'poster'))
    return products, info


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--source', default='assets-source')
    parser.add_argument('--staging', default='media-staging')
    parser.add_argument('--execute', action='store_true', help='écrit les dérivés en staging')
    args = parser.parse_args()
    src = Path(args.source).resolve()
    staging = Path(args.staging).resolve()
    if not src.is_dir() or src == staging or src in staging.parents or staging in src.parents:
        raise SystemExit('Source/staging absents, identiques ou imbriqués')
    rights_path = src / 'media-rights.json'
    rights = json.loads(rights_path.read_text(encoding='utf-8')) if rights_path.exists() else {}
    outputs = []
    blocked = []
    has_hero = False
    if args.execute:
        staging.mkdir(parents=True, exist_ok=True)
    for source in sorted(src.rglob('*')):
        if source.is_symlink() or not source.is_file():
            continue
        ext = source.suffix.lower()
        if ext not in IMAGES | VIDEOS:
            continue
        rel = source.relative_to(src).as_posix()
        details = rights.get(rel, {})
        if details.get('status') != 'verified' or not details.get('source') or not details.get('license'):
            blocked.append({'path': rel, 'reason': 'droits non vérifiés'})
            continue
        role = details.get('role', 'section')
        has_hero = has_hero or (role == 'hero' and ext in VIDEOS)
        # Sous-répertoire stable par chemin : pas de collision de noms entre dossiers
        unique = hashlib.sha256(rel.encode('utf-8')).hexdigest()[:10]
        dest = staging / unique
        if args.execute:
            dest.mkdir(parents=True, exist_ok=True)
        try:
            if ext in IMAGES:
                products = image_variants(source, dest, rel, args.execute)
                meta = {}
            else:
                products, meta = video_variants(source, dest, rel, role, args.execute)
            if not products:
                raise ValueError('Aucun format de sortie compatible genere')
            outputs.append({'original': rel, 'source_sha256': digest(source),
                            'role': role, 'output': products, 'video_info': meta})
        except (OSError, ValueError, RuntimeError, subprocess.CalledProcessError) as err:
            blocked.append({'path': rel, 'reason': 'conversion : ' + str(err)[:300]})
    manifest = {'execute': args.execute, 'files': outputs, 'blocked': blocked,
                'hero_source_verified': has_hero}
    if args.execute:
        (staging / 'manifest.json').write_text(json.dumps(manifest, indent=2,
                                            ensure_ascii=False), encoding='utf-8')
    print(json.dumps({'eligible_or_processed': len(outputs), 'blocked': len(blocked),
                      'hero_source_verified': has_hero, 'execute': args.execute}, ensure_ascii=False))
    if blocked or not has_hero:
        raise SystemExit('REVIEW_REQUIRED: médias bloqués ou vidéo hero vérifiée manquante')


if __name__ == '__main__':
    main()
```

Commandes dans le projet cible :

```bash
python scripts/media/build_media.py --source assets-source --staging media-staging
python scripts/media/build_media.py --source assets-source --staging media-staging --execute
```

`media-staging/manifest.json` contient les SHA et sorties. Ajouter `media-staging/` à `.gitignore`. **Ne pas committer les originaux privés** : si besoin, conserver `assets-source/` sur le poste du propriétaire ou un stockage autorisé chez l'hébergeur. Pour une source provenant de Git, son statut de licence reste indépendant. Prévoir `Pillow`/FFmpeg épinglés et contrôler le comportement réel de codecs/encodeurs disponibles. Si plusieurs langues exigent des médias différents, associer explicitement chaque source aux locales.

## 3. Revue obligatoire IA B avant publication

- Diff d'images aux résolutions utilisées ; EXIF et profil ICC significatifs ; transparence si logo ; vérifier visuels AVIF/WebP sans altération chromatique majeure.
- Frame par frame ou plusieurs instants significatifs pour la vidéo, premier et dernier frames de boucle, absence de son, crop mobile et lecture WebM/MP4 dans navigateur ; test visuel **réel**, pas seulement taille compressée.
- Vérifier posters lisibles avec texte en surimpression, `prefers-reduced-motion`, contrôles pause/reprise, coût LCP/INP/CLS, cache et `Content-Type`.
- Confirmer que `manifest.json` et la feuille de droits correspondent aux chemins publics proposés ; le script n'effectue **pas** ce lien automatiquement.
- Après approbation explicite et validation propriétaire des droits, déplacer/copier **uniquement les dérivés approuvés** dans le dossier média public du projet ; aucun transfert vers Hostinger/Infomaniak par ce script.

## 4. Limites connues

Le script ne comprend pas la sémantique des photos, ne produit pas de textes alternatifs fiables sans revue, ne lit pas de licence automatiquement, ne supprime aucun média inutile et ne lance aucun test navigateur. Les SVG/HEIC et profils couleur complexes nécessitent un traitement spécialisé, sans conversion aveugle. Aucun traitement en masse de vidéos géantes sur un runner GitHub gratuit n'est garanti ; privilégier une machine de build autorisée.
