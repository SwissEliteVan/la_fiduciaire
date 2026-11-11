# PROMPT 05 — WHATSAPP + TÉLÉPHONE RÉELLEMENT OPÉRATIONNELS

Implémente les canaux de contact dans **[PROJET]**, stack **[VALIDÉ]**. Avant code : retrouver les coordonnées publiables, la politique d'horaires et le canal prioritaire. Si un numéro manque, produire un composant désactivé en attente de validation, **pas de faux numéro ni URL**.

**WhatsApp** : URL wa.me avec numéro E.164 sans signe + ni ponctuation, message encodé selon intention de la page, target et texte accessibles, attribution facultative sans données privées. **Téléphone** : `tel:` exact, numéro humain lisible, aucun déclenchement automatique. Intégrer hero/header/contact/footer et zone sticky mobile si elle ne masque rien (safe area, clavier mobile, bandeau consentement, chat).

Prévoir tracking de clic uniquement sous les conditions juridiques applicables, sans empêcher la navigation. Tester lien encodé, navigation clavier, focus, viewport 320/375/768px, contenu avec zoom, absence de liens factices.

**Livrables** : fichiers modifiés, paramètres éditables, inventaire de points d'entrée, checklist des URL et captures/tests si outil disponible. Signaler ce qui n'est pas testé sur appareil réel.
