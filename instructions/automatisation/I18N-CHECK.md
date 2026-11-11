# Vérification de traduction — script exécutable à générer

Le kit fournit un modèle Python **sans dépendance** pour contrôler la parité des clés entre fichiers JSON de locale. À copier par l'agent dans le projet réel et à connecter aux tests CI. Le script ne traduit aucun texte et ne garantit pas la qualité linguistique.

```python
# scripts/check_locales.py
import json, sys
from pathlib import Path

DIR=Path(sys.argv[1] if len(sys.argv)>1 else 'locales')
LANGS=['fr','en','de','it']

def flatten(obj,prefix=''):
    result={}
    for k,v in obj.items():
        path=f'{prefix}.{k}' if prefix else k
        if isinstance(v,dict): result.update(flatten(v,path))
        else: result[path]=v
    return result

all_entries={}
for lang in LANGS:
    fn=DIR/f'{lang}.json'
    if not fn.is_file(): raise SystemExit(f'ERROR missing: {fn}')
    data=json.loads(fn.read_text(encoding='utf-8'))
    if not isinstance(data,dict): raise SystemExit(f'ERROR {fn} must contain object')
    all_entries[lang]=flatten(data)

base=set(all_entries['fr']); failed=False
for lang,data in all_entries.items():
    missing=sorted(base-set(data)); extra=sorted(set(data)-base)
    empty=sorted(k for k,v in data.items() if not isinstance(v,str) or not v.strip())
    if missing or extra or empty:
        failed=True
        print(f'{lang} missing={missing} extra={extra} empty={empty}')
    else: print(f'{lang}: {len(data)} strings; keys OK')
if failed: raise SystemExit(1)
```

Ce script suppose quatre fichiers JSON plats/imbriqués ; si le projet choisit namespaces, adapter récursivement. Étendre dans le projet à l'égalité des placeholders (`{name}`), URL localized `hreflang`, détection de chaînes en dur et E2E pour les débordements allemands. Les mentions légales requièrent une revue humaine qualifiée.
