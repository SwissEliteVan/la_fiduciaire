# PROMPT 17 — MESURE DU FUNNEL SANS VIOLER LES PRÉFÉRENCES

Définis et implémente un plan de mesure mesurant des **conversions réelles** : clic WhatsApp, appel, ouverture chat, qualification, formulaire envoyé avec succès, réservation confirmée, articles consultés/CTA blog, pop-up présentée/fermée/convertie. Inspecter présence réelle de GA, Matomo, Plausible ou autre ; aucune dépendance par défaut.

Chaque événement doit avoir : nom, définition, étape du tunnel, condition de déclenchement, destination, données transmises, base légale/consentement applicable, test d'émission/désactivation, durée de conservation. Interdire données personnelles en paramètres d'URL et logs tiers.

**Priorité** : consentement/CMP avant chargement des outils facultatifs, possibilité de refuser aussi facilement que d'accepter lorsque requis. Site simple sans tracking ? proposer logs agrégés ou mesure minimale non invasive selon cadre légal, sans inventer instrumentation.

**Livrer** : `docs/EVENTS_TRACKING.md`, modifications de code propres au stack, test Network sous refus/acceptation/retrait, vue funnel et limites des données manquantes.
