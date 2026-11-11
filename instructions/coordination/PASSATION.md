# Passation compacte — une session, un état

Ne pas recoller la conversation. Copier uniquement les preuves nécessaires dans issue et `CONTEXT`. Taille cible du texte de passation : **moins de 250 mots** hors liens. L'historique long reste dans GitHub Issues / commits.

```text
[HANDOFF v1] projet=[nom] task=[ID] author=[A/B]
repo=[URL] branch=[main] base=[SHA] final=[SHA|uncommitted]
claim=[issue lien] review=[issue lien|PENDING]
changement=[3 points maximum]
checks=[commandes + codes réels]
media=[manifest/sha/licences, test hero]
i18n=[langues & tests]
risks=[P0/P1 et données manquantes]
next=[UNE action concrète <15 min lorsque possible]
permissions=[prod NON/autorisée précisément]
```

### Reprise automatique en session

Lire par ordre : `AGENTS.md`, `NOW`, `CONTEXT`, ADR des décisions importantes, dernier RELEASE et commits entre SHA enregistré et `origin/main`. Ne charger les anciens journaux que si conflit. Recalculer le nouveau SHA et vérifier le claim. Aucun « travail réalisé » par simple déduction d'un ancien compte rendu.
