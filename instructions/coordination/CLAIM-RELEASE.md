# Messages GitHub normalisés — schéma de coordination

Chaque message commence par un identifiant vérifiable, écrit en texte simple dans **l'issue dédiée**. Champs obligatoires ; remplir `inconnu` au lieu d'inventer. Les dates sont ISO-8601 UTC ; les SHA sont ceux vérifiés à l'instant d'utilisation.

## CLAIM (avant écriture)

```text
[CLAIM v1] task=TASK-001 agent=A role=AUTHOR issue=#N
base=FULL_SHA branch=main created_at=YYYY-MM-DDTHH:MM:SSZ
files=app/page.tsx,components/Hero.tsx
objective=Ajouter une vidéo hero locale sans régression mobile
acceptance=Poster et fallback valides / mobile 390px / réduit mouvement
checks=lint,typecheck,build,e2e-hero
status=CLAIMED
```

1. Recharger issue + commits juste avant de modifier ; claims non libérés et chemins vérifiés **avant** écriture. Le premier claim ne donne pas de verrou technique. En cas d'égalité/race, **stopper les deux**, propriétaire arbitre.
2. Les globs trop larges (`*`, `app/**`) ne sont pas acceptables sans raison justifiée. Les fichiers de gouvernance NOW/CONTEXT inclus explicitement.
3. Pas de « timeout » implicite du claim. Après interruption, prendre contact, ne pas voler le claim.

## REVIEW REQUEST (auteur)

```text
[REVIEW-REQUEST v1] task=TASK-001 agent=A base=... head=...
commit_or_diff=URL_ou_diff_local
checks=lint:PASS exit0; typecheck:PASS exit0; build:NOT_RUN(reason)
media=manifest_sha256:...; license:verified/pending
asks=Regarder hero mobile, reduced motion, français/allemand
```

## REVIEW (relecteur)

```text
[REVIEW v1] task=TASK-001 agent=B verdict=FIX_REQUIRED|APPROVED_WITH_RESERVES|APPROVED
reviewed_sha=... evidence=URL_et_fichiers
blockers=P0: chemin:ligne + reproduction + fix minimal
warnings=P1: ...
independent_tests=playwright:NOT_RUN(no browser)
next=Fix précis, puis nouveau review
```

## FIX (auteur)

```text
[FIX v1] task=TASK-001 agent=A reviewed_sha=...
addressed=P0-1: fichier + modification + test
checks=e2e-hero:PASS exit0
re_review_requested=yes
```

## RELEASE puis HANDOFF

```text
[RELEASE v1] task=TASK-001 agent=A final_sha=...
branch=main files=... review=B:APPROVED_WITH_RESERVES OR NOT_RECEIVED
checks=lint:PASS/types:PASS/build:PASS/e2e:PASS
risks=... production_deployment=NO
status=RELEASED
[HANDOFF v1] task=TASK-001 next=TASK-002 blocker=none
context=projet/CONTEXT.md issue=#N
```

**Invariant :** `APPROVED` ≠ « publié en production ». Si aucun verdict de B disponible, écrire explicitement REVIEW_PENDING et laisser la tâche en attente ou l'escalader selon risque ; ne pas fabriquer de validation.
