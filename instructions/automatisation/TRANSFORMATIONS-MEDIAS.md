# Moteur de dérivés médias — exemple de génération batch exécutable

Ce fichier est uniquement **Markdown** dans l'archive. Pour automatiser concrètement un projet, IA A copie/adapte le code dans `scripts/media/build_media.py` du projet cible. Nécessite Python 3.10+, **Pillow** (licence à vérifier pour version installée), **ffprobe**, **ffmpeg** et codecs configurés. Le code n'utilise aucun service externe et **ne modifie jamais la source**.

## Contrat de fichiers

- `assets-source/` ou l'emplacement source du client : originaux hors document root, jamais supprimés.
- `assets-source/media-rights.json` : autorisations vérifiées par un humain, **aucune supposition**.
- `assets-staging/generated/` : dérivés uniquement, peuvent être régénérés, pas de publication automatique.
- `public/media/generated/` : uniquement après `--publish`, ce qui veut dire *écrire dans le dossier public local*, et **non déployer sur Hostinger**. L'agent doit avoir l'autorisation d'éditer ce dossier et le droit d'utiliser le média.
- `assets-staging/media-generated.json` : manifest dérivés + hash/chemins + places suggérées.

Exemple du registre des droits (fichiers réels et justificatifs à compléter) :

```json
{
  "assets-source/hero.mp4": {
    "status": "verified",
    "source": "facture/autorisation interne réf. X",
    "license": "licence détenue par le propriétaire, justificatif conservé",
    "author": "à renseigner si connu"
  },
  "assets-source/team.jpg": {"status": "pending", "source": "", "license": ""}
}
```

Le script refuse le traitement d'un fichier `pending` ou non déclaré. Le `status=verified` doit être justifié indépendamment, et la simple présence du mot `verified` n'est **pas** une preuve juridique. IA B contrôle avant toute publication.

## Script build médias (Pillow et FFmpeg)

```python
# scripts/media/build_media.py — traiter uniquement les sources aux droits vérifiés
import argparse, hashlib, json, shutil, subprocess
from pathlib import Path
from PIL import Image, ImageOps

IMAGE_EXT={'.jpg','.jpeg','.png','.tif','.tiff','.webp'}
VIDEO_EXT={'.mp4','.mov','.m4v','.webm','.mkv'}
WIDTHS=(480,768,1200,1600)

def checksum(p):
    h=hashlib.sha256()
    with p.open('rb') as f:
        for b in iter(lambda:f.read(1024*1024),b''): h.update(b)
    return h.hexdigest()

def run(cmd):
    subprocess.run(cmd,check=True,capture_output=True,text=True,timeout=240)

def get_video(src):
    raw=subprocess.run(['ffprobe','-v','error','-show_entries',
        'format=duration:stream=width,height,codec_name','-of','json',str(src)],
        capture_output=True,text=True,check=True,timeout=25)
    return json.loads(raw.stdout)

def ensure_inside(root,path):
    p=(root/path).resolve()
    if not p.is_relative_to(root): raise ValueError('Chemin hors dépôt')
    return p

def describe(path,stem,kind):
    s=stem.lower()
    return 'hero' if 'hero' in s or 'accueil' in s else (
        'logo' if 'logo' in s else 'illustration/section à affecter')

def build_image(src,output,record):
    with Image.open(src) as raw:
        im=ImageOps.exif_transpose(raw)
        if im.mode not in ('RGB','RGBA'):
            im=im.convert('RGBA' if 'A' in im.getbands() else 'RGB')
        width,height=im.size
        widths=sorted(set([min(width,w) for w in WIDTHS]))
        for w in widths:
            h=max(1,round(height*w/width))
            obj=im.resize((w,h),Image.Resampling.LANCZOS)
            name=f'{src.stem}-{w}w.webp'
            target=output/name
            obj.save(target,format='WEBP',quality=79,method=6)
            record.append(target)

def build_video(src,output,record):
    meta=get_video(src)
    duration=float(meta.get('format',{}).get('duration') or 0)
    if duration<=0: raise ValueError('Durée vidéo impossible à vérifier')
    mp4=output/f'{src.stem}.mp4'
    poster=output/f'{src.stem}-poster.jpg'
    # Comparer qualité/poids ensuite ; paramètres volontairement prudents.
    run(['ffmpeg','-y','-v','error','-i',str(src),'-an','-vf',
         'scale=w=min(1280\\,iw):h=-2','-c:v','libx264','-preset','medium',
         '-crf','25','-pix_fmt','yuv420p','-movflags','+faststart',str(mp4)])
    record.append(mp4)
    run(['ffmpeg','-y','-v','error','-ss',str(min(duration/3,1.0)),
         '-i',str(src),'-frames:v','1','-q:v','3',str(poster)])
    record.append(poster)
    # WebM optionnel, uniquement si le codeur VP9 est présent.
    support=subprocess.run(['ffmpeg','-hide_banner','-encoders'],
        capture_output=True,text=True,timeout=25)
    if 'libvpx-vp9' in support.stdout:
        webm=output/f'{src.stem}.webm'
        run(['ffmpeg','-y','-v','error','-i',str(src),'-an','-vf',
             'scale=w=min(1280\\,iw):h=-2','-c:v','libvpx-vp9',
             '-crf','33','-b:v','0',str(webm)])
        record.append(webm)

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument('--root',default='.')
    ap.add_argument('--rights',default='assets-source/media-rights.json')
    ap.add_argument('--publish',action='store_true',
        help='Copier localement dans public/media/generated, pas déployer')
    args=ap.parse_args()
    root=Path(args.root).resolve()
    rightsfile=ensure_inside(root,args.rights)
    rights=json.loads(rightsfile.read_text(encoding='utf-8'))
    if not isinstance(rights,dict): raise SystemExit('Registre droits invalide')
    staging=root/'assets-staging/generated'
    staging.mkdir(parents=True,exist_ok=True)
    pub=root/'public/media/generated'
    report=[]
    for rel,lic in sorted(rights.items()):
        if not isinstance(lic,dict) or lic.get('status')!='verified':
            print('SKIP rights pending:',rel)
            continue
        if not lic.get('source') or not lic.get('license'):
            print('SKIP incomplete provenance:',rel)
            continue
        src=ensure_inside(root,rel)
        if not src.is_file(): raise FileNotFoundError(src)
        ext=src.suffix.lower()
        if ext not in IMAGE_EXT|VIDEO_EXT:
            print('SKIP unsupported format:',rel);continue
        # Le hash court évite des collisions entre noms identiques.
        dest=staging/(src.stem+'-'+checksum(src)[:10])
        dest.mkdir(parents=True,exist_ok=True)
        derived=[]
        if ext in IMAGE_EXT: build_image(src,dest,derived)
        else: build_video(src,dest,derived)
        outputs=[]
        for path in derived:
            if args.publish:
                target=pub/dest.name/path.name
                target.parent.mkdir(parents=True,exist_ok=True)
                shutil.copy2(path,target)
                candidate=target
            else: candidate=path
            outputs.append({'path':candidate.relative_to(root).as_posix(),
                'sha256':checksum(candidate),'bytes':candidate.stat().st_size})
        report.append({'source':rel,'source_sha256':checksum(src),
                       'rights':'verified','license':lic['license'],
                       'source_provenance':lic['source'],
                       'placement_suggestion':describe(src,src.stem,ext),
                       'alt_by_locale':{'fr':None,'en':None,'de':None,'it':None},
                       'outputs':outputs})
    result=root/'assets-staging/media-generated.json'
    result.parent.mkdir(parents=True,exist_ok=True)
    result.write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
    print('Générés:',len(report),'sources; manifest:',result,
          '; publication locale:',args.publish)
if __name__=='__main__':main()
```

**Usage** : d'abord `python scripts/media/build_media.py --root .`, QA, puis `--publish` après contrôle droit/visuel. Pour `.svg`, `.avif` source, `.heic`, sous-titres, langues et uploads d'utilisateurs, choisir outil dédié/validateur spécifique au lieu de forcer ce script. Les noms de sortie doivent être intégrés dans composants/pages par l'agent selon le framework et la destination proposée ; un script générique ne sait pas à lui seul quel visuel est commercialement pertinent.

## Contrôles de qualité complémentaires

- Ne pas publier si `alt_by_locale` essentiel reste `null` pour un visuel informatif ; les images décoratives reçoivent `alt=""` explicitement.
- Vérifier `hero.mp4` local/poster, réponse HTTP `206` pour Range sur hébergeur, `<video>` et styles mobiles selon `skills/VIDEO-HERO.md` ; mesurer avant/après.
- En cas de nom de fichier ou licence manquants, signaler sans essayer de « deviner » l'origine.
- Ajuster `crf`, codec, dimensions après comparaison visuelle et perf. Clip avec audio nécessaire → adaptation et droit audio à documenter.
- `--publish` ne déploie pas le site ; le déploiement Hostinger/Infomaniak reste interdit sans accord spécifique.
