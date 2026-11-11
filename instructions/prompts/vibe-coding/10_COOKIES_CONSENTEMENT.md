# PROMPT 10 — CMP/BANDEAU COOKIES QUI BLOQUE VRAIMENT LES TRACEURS

Audite d'abord les cookies, scripts tiers, analytics, embeds vidéo et pixels réellement présents ; distingue nécessaires, préférences, statistiques et marketing. Identifie pays/public et régime applicable ; documente les points nécessitant un avis juridique. **Ne dis pas qu'une bannière est légalement obligatoire si aucun traceur optionnel soumis au consentement n'existe.** Le module de préférences reste prévu.

Si consentement requis, construis bandeau mobile/desktop avec `Tout accepter`, `Tout refuser`, `Personnaliser` de présentation équilibrée ; aucune case précochée pour facultatifs, sauvegarde du choix, modification dans footer, retrait de consentement, scripts **bloqués avant accord**, éventuelle suppression/destruction des traceurs accessibles après retrait.

Intégrer avec le stack retenu : vanilla, CMS plugin ou composant ; privilégier solution testable et maintenable. Couvrir scripts de chat tiers et autres widgets lorsque règle applicable. `docs/CONSENT_MATRIX.md` décrit fournisseurs, durée, catégorie, déclencheur, état opt-in/out, modalités de retrait.

**Tests obligatoires** : première visite vierge, refus, acceptation partielle, acceptation totale, changement, recharge, autre page, navigation mobile/clavier, vérification requêtes réseaux AVANT consentement. Pas de « conformité garantie » sans analyse juridique.
