#!/bin/sh
# Double de test uniquement, ne détecte aucun logiciel malveillant.
[ "$#" -eq 3 ] && [ "$1" = --no-summary ] && [ "$2" = -- ] || exit 2
grep -aq LF_SCANNER_FAIL "$3" && exit 2
grep -aq LF_SCANNER_REFUSE "$3" && exit 1
exit 0
