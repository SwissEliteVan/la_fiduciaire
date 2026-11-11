# Prompt 06 — Tests continus et correction encadrée

Étendre la CI actuelle du lanceur Windows aux tests du moteur travail-universel, multi-versions de Python et scénarios de concurrence, reprise, sauvegarde/restauration et index Git sensible.

N'autoriser une auto-correction qu'après reproduction d'un échec, zone de code limitée et test ciblé. Exécuter test ciblé, suite pertinente, relecture B. Séparer simulation, test local, navigateur Windows réel, API réelles, Docker et déploiement : ne pas déclarer PASS à tort.

Produire une matrice PASS/FAIL/NOT_RUN/BLOCKED, logs, captures si réellement obtenues, défauts résiduels et prochaine action. Ne pas affaiblir la CI pour obtenir du vert.
