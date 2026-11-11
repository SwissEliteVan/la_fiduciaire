# Pipeline médias — de la découverte au déploiement local

**But :** réduire le travail manuel sans inventer licences ou visuels. Les scripts ici sont des **gabarits complets pour le projet réel** ; ils ne sont pas exécutés par ce kit de Markdown. L'agent les écrit dans `scripts/media/`, teste sur une copie, adapte les dépendances et garde **source originale intacte**.

## 1. Conditions et environnement

- Python 3.10+ ; `ffprobe`, `ffmpeg` disponibles pour vidéo ; `Pillow` facultatif pour créer des variantes d'images et poster WebP. Toutes ces commandes sont locales/CI.
- Générer `assets-source/media-rights.json` **par inspection de preuves**, structure : `{"images/bois.jpg":{"status":"verified","source":"...","license":"...","author":"..."}}` ; `pending` par défaut. Ce registre ne doit contenir aucun secret ni preuve personnelle.
- Ne jamais remplacer `assets-source/`. Dérivés dans staging **hors public**, publication explicite seulement si licence vérifiée et QA approuvée.

## 2. Script Python d'inventaire fonctionnel — à copier dans le projet

```python
# scripts/media/inventory.py — Python >=3.10, uniquement bibliothèques standard
import argparse, hashlib, json, subprocess, os
from pathlib import Path

SUFFIXES = {'.jpg','.jpeg','.png','.webp','.avif','.gif','.svg','.heic',
            '.mp4','.mov','.m4v','.webm','.mkv','.avi','.tif','.tiff'}
ROOT_HINTS = {'asset','assets','addets','media','medias','image','images','img',
              'photo','photos','pictures','video','videos','uploads','static','public'}
EXCLUDE = {'.git','node_modules','vendor','.next','out','dist','build','.cache',
           'assets-staging','coverage'}

def hashfile(p):
    h=hashlib.sha256()
    with p.open('rb') as f:
        for block in iter(lambda:f.read(1024*1024),b''): h.update(block)
    return h.hexdigest()

def probe(p):
    try:
        q=subprocess.run(['ffprobe','-v','error','-show_streams','-show_format',
                          '-of','json',str(p)],capture_output=True,text=True,
                          timeout=25,check=True)
        data=json.loads(q.stdout)
        stream=next((s for s in data.get('streams',[])
                     if s.get('codec_type')=='video'),{})
        return {'codec':stream.get('codec_name'),
                'width':stream.get('width'),'height':stream.get('height'),
                'fps':stream.get('avg_frame_rate'),
                'duration':data.get('format',{}).get('duration')}
    except (OSError,subprocess.CalledProcessError,ValueError,
            subprocess.TimeoutExpired): return {'probe':'unavailable'}

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument('--root',default='.')
    ap.add_argument('--output',default='assets-staging/media-inventory.json')
    args=ap.parse_args()
    root=Path(args.root).resolve(); results=[]; sha_map={}
    for base,dirs,filenames in os.walk(root):
        dirs[:]=[d for d in dirs if d not in EXCLUDE and not d.startswith('.git')]
        here=Path(base)
        for name in filenames:
            p=here/name
            if p.suffix.lower() not in SUFFIXES: continue
            relative=p.relative_to(root).as_posix()
            # Examine tout média, même dans un dossier au nom atypique.
            stat=p.stat(); sha=hashfile(p)
            item={'path':relative,'size_bytes':stat.st_size,'sha256':sha,
                  'extension':p.suffix.lower(),
                  'found_in_hint_folder':bool(set(x.lower() for x in p.parts)&ROOT_HINTS),
                  'duplicate_of':sha_map.get(sha),
                  'rights':'pending','published':False}
            sha_map.setdefault(sha,relative)
            item.update(probe(p))
            results.append(item)
    out=Path(args.output); out.parent.mkdir(parents=True,exist_ok=True)
    out.write_text(json.dumps({'root':str(root),'count':len(results),
        'items':sorted(results,key=lambda v:v['path'])},ensure_ascii=False,indent=2),
        encoding='utf-8')
    print('Inventaire:',len(results),'fichiers ->',out)
if __name__=='__main__': main()
```

Utilisation (depuis le dépôt cible) : `python scripts/media/inventory.py --root . --output assets-staging/media-inventory.json`. Ce script **ne déplace ni ne transforme**, ne lit pas une licence automatiquement, et `ffprobe` sur SVG/images peut échouer sans bloquer l'inventaire.

## 3. Transformation contrôlée et reproductible

Pour chaque source `verified`, IA A produit des commandes explicites et le manifest sortie (avec SHA) ; exemple :

```bash
# Image source, versions WebP si Sharp CLI/capacités adaptées ; syntaxe à valider selon outil installé
# Vidéo hero locale, à partir du fichier original, jamais d'une version déjà compressée
ffmpeg -i assets-source/hero-original.mp4 -an -vf 'scale=1280:-2' -c:v libx264 -crf 25 -preset slow -pix_fmt yuv420p -movflags +faststart assets-staging/hero.mp4
ffmpeg -i assets-source/hero-original.mp4 -an -vf 'scale=1280:-2' -c:v libvpx-vp9 -crf 33 -b:v 0 assets-staging/hero.webm
ffmpeg -ss 2 -i assets-source/hero-original.mp4 -frames:v 1 assets-staging/hero-poster.jpg
```

Comparaison QA : longueur/résolution/fps préservés ou changements justifiés, inspecter frames, écouter absence de piste audio si demandé, coût poids et crop mobile, erreurs navigateur et temps de chargement. Si échec conversion → garder original non publié et signaler `BLOCKED_MEDIA`. L'agent ne doit pas accepter le premier encodage sans mesure.

## 4. Integration & détection des inutilisés

- Lister les références dans HTML/TSX/CSS/JS/templates et config. Un média absent des références textuelles peut être chargé dynamiquement : marquer `possibly_unused`, jamais suppression automatique.
- Générer un manifest à jour des dérivés et SHA, alt FR/EN/DE/IT selon visuel et page, focal point, licence et crédits. Comparer aux références de build et requêtes réseau ; bloquer 404.
- `original` reste hors dossier web (sur disque de travail/stockage local autorisé), `derivative` sous `/media/...` du **domaine propriétaire**. Les dérivés sont publiés seulement après verdict droit `verified` + QA B.
- Mettre un contrôle `media:check` réel dans CI pour rejeter absence poster/vidéo et références cassées, en évitant de charger des médias multi-Mo dans l'artefact CI si une vérification des checksums suffit.

## 5. Fin du pipeline

Rapport `count, duplicates, originals, published derivatives, total MB saved, hero LCP impact, blocked rights, unused candidates, browser tests`. Ne jamais revendiquer des pourcentages gagnés sans mesures réelles.
