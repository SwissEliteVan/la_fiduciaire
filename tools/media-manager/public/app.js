'use strict';

/**
 * Interface du gestionnaire local de médias.
 * Aucune dépendance externe, aucun appel réseau hors 127.0.0.1.
 */
(function () {
    const etat = {
        token: null,
        data: null,
        filtres: { q: '', statut: '', categorie: '', decision: '' },
        selection: new Set(),
        annuler: [],
        detail: null,
        validation: null,
        occupe: false,
    };

    const $ = (selecteur, racine) => (racine || document).querySelector(selecteur);
    const $$ = (selecteur, racine) => Array.from((racine || document).querySelectorAll(selecteur));

    const LIBELLES_DECISION = {
        keep: 'Conserver',
        delete: 'Supprimer',
        replace: 'Remplacer',
        undecided: 'Non décidé',
    };

    const CLASSES_DECISION = {
        keep: 'conserver',
        delete: 'supprimer',
        replace: 'remplacer',
        undecided: 'nondecide',
    };

    function echapper(valeur) {
        return String(valeur === null || valeur === undefined ? '' : valeur).replace(/[&<>"']/g, (caractere) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[caractere]));
    }

    function octets(valeur) {
        const nombre = Number(valeur) || 0;
        if (nombre < 1024) {
            return nombre + ' o';
        }
        if (nombre < 1048576) {
            return (nombre / 1024).toFixed(1).replace('.', ',') + ' Ko';
        }
        return (nombre / 1048576).toFixed(2).replace('.', ',') + ' Mo';
    }

    function dateLisible(iso) {
        if (!iso) {
            return '—';
        }
        const date = new Date(iso);
        if (Number.isNaN(date.getTime())) {
            return String(iso);
        }
        return date.toLocaleString('fr-CH', { dateStyle: 'short', timeStyle: 'short' });
    }

    function dimensions(media) {
        const largeur = media.dimensions && media.dimensions.largeur;
        const hauteur = media.dimensions && media.dimensions.hauteur;
        return largeur && hauteur ? largeur + '×' + hauteur : 'n/d';
    }

    function toast(message, type) {
        const conteneur = $('#toasts');
        const element = document.createElement('div');
        element.className = 'toast' + (type ? ' toast--' + type : '');
        element.textContent = message;
        conteneur.appendChild(element);
        window.setTimeout(() => element.remove(), 5200);
    }

    function session(texte, alerte) {
        const element = $('#etat-session');
        element.textContent = texte;
        element.dataset.alerte = alerte ? '1' : '0';
    }

    function occupe(actif) {
        etat.occupe = actif;
        document.body.style.cursor = actif ? 'progress' : '';
        $$('#btn-scan, #btn-save, #btn-valider, #btn-export, #btn-appliquer').forEach((bouton) => {
            if (bouton.id === 'btn-appliquer') {
                return;
            }
            bouton.disabled = actif;
        });
    }

    async function api(route, options) {
        const config = Object.assign({ method: 'GET', credentials: 'same-origin', headers: {} }, options || {});
        const estFormulaire = config.body instanceof FormData;

        if (config.body && !estFormulaire) {
            config.headers['Content-Type'] = 'application/json';
            if (typeof config.body !== 'string') {
                config.body = JSON.stringify(config.body);
            }
        }
        if (config.method !== 'GET' && etat.token) {
            config.headers['X-MM-Token'] = etat.token;
        }

        const reponse = await fetch('/api/' + route, config);
        let donnees = null;
        try {
            donnees = await reponse.json();
        } catch (erreur) {
            donnees = null;
        }

        if (!reponse.ok || !donnees || donnees.ok === false) {
            const message = donnees && donnees.erreur ? donnees.erreur : 'Erreur HTTP ' + reponse.status;
            const erreur = new Error(message);
            erreur.donnees = donnees;
            erreur.statut = reponse.status;
            throw erreur;
        }

        return donnees;
    }

    async function chargerEtat() {
        if (!etat.token) {
            const sessionApi = await api('session');
            etat.token = sessionApi.token;
        }
        const reponse = await api('state');
        etat.token = reponse.token || etat.token;
        etat.data = reponse.etat;
        etat.validation = reponse.etat.validation || null;
        etat.selection = new Set();
        rendreTout();
    }

    function mediaParChemin(chemin) {
        if (!etat.data) {
            return null;
        }
        return etat.data.inventaire.find((media) => media.chemin === chemin) || null;
    }

    function decisionDe(media) {
        return media.decision || { decision: 'undecided' };
    }

    function urlFichier(chemin) {
        return '/api/file?path=' + encodeURIComponent(chemin) + '&t=' + encodeURIComponent(etat.token || '');
    }

    function urlImport(media) {
        return '/api/import-file?id=' + encodeURIComponent(media.id) + '&t=' + encodeURIComponent(etat.token || '');
    }

    function enregistrerAnnulation(entree) {
        etat.annuler.push(entree);
        if (etat.annuler.length > 30) {
            etat.annuler.shift();
        }
        $('#btn-annuler').disabled = etat.annuler.length === 0;
    }

    // ---------- Rendu ----------

    function rendreTout() {
        const data = etat.data;
        if (!data) {
            return;
        }

        $('#projet-nom').textContent = data.projet.nom;
        $('#projet-pile').textContent = data.projet.stack;
        $('#stockage').textContent = 'Dossier de gestion : ' + data.stockage + ' — créé automatiquement, hors répertoires publics';
        $('#btn-appliquer').disabled = !data.validation;

        rendreCompteurs();
        rendreCategories();
        rendreGrille();
        rendreSelection();
    }

    function rendreCompteurs() {
        const inventaire = etat.data.inventaire || [];
        const compter = (predicat) => inventaire.filter(predicat).length;

        $('#compteur-total').textContent = inventaire.length;
        $('#compteur-utilise').textContent = compter((m) => m.statut === 'UTILISÉ');
        $('#compteur-inutilise').textContent = compter((m) => m.statut === 'INUTILISÉ');
        $('#compteur-doublon').textContent = compter((m) => m.statut === 'DOUBLON');
        $('#compteur-incertain').textContent = compter((m) => m.statut === 'INCERTAIN');
        $('#compteur-conserver').textContent = compter((m) => decisionDe(m).decision === 'keep');
        $('#compteur-supprimer').textContent = compter((m) => decisionDe(m).decision === 'delete');
        $('#compteur-remplacer').textContent = compter((m) => decisionDe(m).decision === 'replace');
        $('#compteur-nondecides').textContent = compter((m) => decisionDe(m).decision === 'undecided');
    }

    function rendreCategories() {
        const select = $('#filtre-categorie');
        const categories = Array.from(new Set((etat.data.inventaire || []).map((media) => media.categorie))).sort();
        const valeur = select.value;

        select.innerHTML = '<option value="">Toutes</option>' + categories
            .map((categorie) => '<option value="' + echapper(categorie) + '">' + echapper(categorie) + '</option>')
            .join('');

        if (categories.includes(valeur)) {
            select.value = valeur;
        }
    }

    function itemsFiltres() {
        const recherche = etat.filtres.q.trim().toLowerCase();
        return (etat.data.inventaire || []).filter((media) => {
            if (etat.filtres.statut && media.statut !== etat.filtres.statut) {
                return false;
            }
            if (etat.filtres.categorie && media.categorie !== etat.filtres.categorie) {
                return false;
            }
            if (etat.filtres.decision && decisionDe(media).decision !== etat.filtres.decision) {
                return false;
            }
            if (recherche !== '') {
                const contenu = [
                    media.chemin,
                    media.nom,
                    media.format,
                    media.statut,
                    media.categorie,
                ].concat((media.usages || []).map((usage) => usage.fichier + ':' + usage.ligne));

                if (!contenu.join(' ').toLowerCase().includes(recherche)) {
                    return false;
                }
            }
            return true;
        });
    }

    function visuelCarte(media) {
        if (media.type === 'image') {
            return '<img src="' + echapper(urlFichier(media.chemin)) + '" alt="" loading="lazy" decoding="async">';
        }
        if (media.type === 'video') {
            return '<video src="' + echapper(urlFichier(media.chemin)) + '" preload="metadata" muted playsinline aria-label="Aperçu vidéo"></video>';
        }
        return '<span>' + echapper(String(media.format).toUpperCase()) + '</span>';
    }


    function boutonAction(media, decision, libelle) {
        const actif = decisionDe(media).decision === decision;
        const classe = decision === 'delete' ? ' btn--danger-leger' : '';

        return '<button type="button" class="btn btn--mini' + classe + '" data-action="' + decision + '" data-chemin="' +
            echapper(media.chemin) + '" aria-pressed="' + (actif ? 'true' : 'false') + '">' + libelle + '</button>';
    }

    function htmlCarte(media) {
        const choix = decisionDe(media).decision || 'undecided';
        const selectionnee = etat.selection.has(media.chemin);
        const usages = (media.usages || []).slice(0, 3)
            .map((usage) => '<li><code>' + echapper(usage.fichier) + ':' + usage.ligne + '</code></li>')
            .join('');
        const variantes = media.variantes && media.variantes.length
            ? '<span>' + media.variantes.length + ' variante(s)</span>'
            : '';
        const contrat = media.contrat
            ? '<span class="etiquette etiquette--' + echapper(media.contrat.statut) + '">contrat ' + echapper(media.contrat.statut) + '</span>'
            : '';
        const classesDecision = CLASSES_DECISION[choix] || 'nondecide';

        return '<article class="carte" role="listitem" data-chemin="' + echapper(media.chemin) + '" data-selectionnee="' + (selectionnee ? '1' : '0') + '">' +
            '<div class="carte__visuel" data-ouvrir="' + echapper(media.chemin) + '">' +
                '<input type="checkbox" class="carte__selection" data-selection="' + echapper(media.chemin) + '"' +
                    (selectionnee ? ' checked' : '') + ' aria-label="Sélectionner ' + echapper(media.nom) + '">' +
                '<span class="carte__type">' + echapper(media.type) + ' · ' + echapper(media.format) + '</span>' +
                visuelCarte(media) +
            '</div>' +
            '<div class="carte__corps">' +
                '<h3 class="carte__titre">' + echapper(media.nom) + '</h3>' +
                '<p class="carte__chemin">' + echapper(media.chemin) + '</p>' +
                '<div class="carte__meta"><span>' + dimensions(media) + '</span><span>' + octets(media.octets) + '</span><span>' +
                    echapper(media.categorie) + '</span>' + variantes + '</div>' +
                '<div class="carte__etiquettes">' +
                    '<span class="etiquette etiquette--' + echapper(media.statut) + '">' + echapper(media.statut) + '</span>' +
                    '<span class="etiquette etiquette--' + classesDecision + '">' + LIBELLES_DECISION[choix] + '</span>' +
                    contrat +
                '</div>' +
                '<div class="carte__usages">' +
                    (usages !== ''
                        ? '<strong>' + media.usages_total + ' usage(s)</strong><ul>' + usages + '</ul>'
                        : 'Aucune référence détectée') +
                '</div>' +
                '<div class="carte__actions">' +
                    boutonAction(media, 'keep', 'Conserver') +
                    boutonAction(media, 'delete', 'Supprimer') +
                    boutonAction(media, 'replace', 'Remplacer') +
                    boutonAction(media, 'undecided', 'Non décidé') +
                '</div>' +
                '<button type="button" class="carte__detail" data-ouvrir="' + echapper(media.chemin) + '">Détail, usages et contrat</button>' +
            '</div>' +
        '</article>';
    }

    function rendreGrille() {
        const grille = $('#grille');
        const items = itemsFiltres();

        grille.innerHTML = items.map(htmlCarte).join('');
        $('#etat-vide').hidden = items.length !== 0;
        $('#etat-vide').textContent = etat.data && (etat.data.inventaire || []).length === 0
            ? 'Aucun média détecté dans ce projet.'
            : 'Aucun média ne correspond aux filtres.';
        rendreSelection();
    }

    function rendreSelection() {
        const nombre = etat.selection.size;
        const visibles = itemsFiltres().length;
        $('#selection-info').textContent = nombre === 0
            ? visibles + ' média(s) affiché(s)'
            : nombre + ' sélectionné(s) sur ' + visibles;
        ['#btn-conserver-selection', '#btn-supprimer-selection', '#btn-nondecide-selection'].forEach((selecteur) => {
            $(selecteur).disabled = nombre === 0;
        });
        $('#btn-annuler').disabled = etat.annuler.length === 0;
    }


    // ---------- Mutations ----------

    async function definirDecision(chemin, decision, extra) {
        const media = mediaParChemin(chemin);
        if (!media || etat.occupe) {
            return;
        }

        const avant = media.decision ? JSON.parse(JSON.stringify(media.decision)) : null;
        occupe(true);
        session('Enregistrement…');

        try {
            const reponse = await api('decision', {
                method: 'POST',
                body: Object.assign({ chemin: chemin, decision: decision }, extra || {}),
            });

            media.decision = reponse.decision;
            etat.data.decisions[chemin] = reponse.decision;
            etat.data.revision = reponse.revision;
            etat.data.validation = null;
            etat.validation = null;
            enregistrerAnnulation({ type: 'decision', chemins: [chemin], avant: { [chemin]: avant } });

            $('#btn-appliquer').disabled = true;
            rendreTout();
            if (etat.detail === chemin) {
                rendreDetail();
            }
            session('Enregistré à ' + new Date().toLocaleTimeString('fr-CH') + ' · révision ' + reponse.revision);
        } catch (erreur) {
            toast(erreur.message, 'erreur');
            session('Échec de l’enregistrement', true);
        } finally {
            occupe(false);
        }
    }

    async function decisionEnLot(chemins, decision) {
        if (!chemins.length || etat.occupe) {
            return;
        }

        const avant = {};
        chemins.forEach((chemin) => {
            const media = mediaParChemin(chemin);
            avant[chemin] = media && media.decision ? JSON.parse(JSON.stringify(media.decision)) : null;
        });

        occupe(true);
        session('Enregistrement…');

        try {
            const reponse = await api('bulk', {
                method: 'POST',
                body: { chemins: chemins, decision: decision },
            });

            await chargerEtat();
            enregistrerAnnulation({ type: 'decision', chemins: chemins, avant: avant });
            session('Enregistré · ' + reponse.modifies + ' média(s) · révision ' + reponse.revision);
            toast(reponse.modifies + ' média(s) mis à jour.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
            session('Échec de l’enregistrement', true);
        } finally {
            occupe(false);
        }
    }

    async function annulerDernier() {
        const entree = etat.annuler.pop();
        if (!entree) {
            return;
        }

        occupe(true);
        try {
            for (const chemin of entree.chemins) {
                const precedente = entree.avant[chemin];
                const decision = precedente && precedente.decision ? precedente.decision : 'undecided';
                const reponse = await api('decision', {
                    method: 'POST',
                    body: { chemin: chemin, decision: decision },
                });
                const media = mediaParChemin(chemin);
                if (media) {
                    media.decision = reponse.decision;
                    etat.data.decisions[chemin] = reponse.decision;
                }
            }
            await chargerEtat();
            toast('Dernier choix annulé.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }


    async function televerser(chemin, fichier, portee, genere) {
        const media = mediaParChemin(chemin);
        if (!media || !fichier) {
            toast('Choisis d’abord un fichier local.', 'erreur');
            return;
        }

        const formulaire = new FormData();
        formulaire.append('chemin', chemin);
        formulaire.append('fichier', fichier);
        formulaire.append('portee', portee || 'globale');
        formulaire.append('generer_variantes', genere ? '1' : '0');

        occupe(true);
        session('Téléversement et analyse…');

        try {
            const reponse = await api('upload', { method: 'POST', body: formulaire });
            await chargerEtat();
            etat.detail = chemin;
            ouvrirDetail(chemin);
            const contrat = reponse.contrat || {};
            session('Fichier importé · contrat ' + (contrat.statut || 'n/d'));
            toast('Fichier reçu et comparé. Valide ou annule le remplacement.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
            session('Téléversement refusé', true);
        } finally {
            occupe(false);
        }
    }

    async function annulerRemplacement(chemin) {
        const media = mediaParChemin(chemin);
        const remplacement = media && media.decision ? media.decision.remplacement : null;
        if (!remplacement) {
            return;
        }

        occupe(true);
        try {
            await api('discard-import', {
                method: 'POST',
                body: { chemin_temp: remplacement.chemin_temp },
            });
            await chargerEtat();
            etat.detail = chemin;
            rendreDetail();
            toast('Fichier temporaire supprimé, remplacement annulé.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }

    async function enregistrerDerogation(chemin, accordee, motif) {
        occupe(true);
        try {
            const reponse = await api('derogation', {
                method: 'POST',
                body: { chemin: chemin, accordee: accordee, motif: motif },
            });
            const media = mediaParChemin(chemin);
            if (media) {
                media.decision = reponse.decision;
            }
            etat.data.revision = reponse.revision;
            etat.data.validation = null;
            $('#btn-appliquer').disabled = true;
            rendreTout();
            rendreDetail();
            toast(accordee ? 'Dérogation enregistrée.' : 'Dérogation retirée.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }


    // ---------- Détail ----------

    function htmlContrat(contrat) {
        if (!contrat) {
            return '';
        }

        const regles = (contrat.regles || []).map((regle) =>
            '<li class="regle">' +
                '<span class="regle__statut regle__statut--' + echapper(regle.statut) + '">' + echapper(regle.statut) + '</span>' +
                '<div><strong>' + echapper(regle.libelle) + '</strong>' +
                    '<p class="regle__detail">' + echapper(regle.detail) + '</p>' +
                    (regle.correctif ? '<p class="regle__correctif">Correctif attendu : ' + echapper(regle.correctif) + '</p>' : '') +
                '</div>' +
            '</li>'
        ).join('');

        return '<div class="bloc">' +
            '<h3>Contrat média — ' + echapper(contrat.statut) + ' (' + contrat.bloquant + ' bloquant, ' + contrat.avertissements + ' avertissement)</h3>' +
            '<ul class="liste-regles">' + regles + '</ul>' +
        '</div>';
    }

    function htmlUsages(media) {
        const usages = media.usages || [];
        if (usages.length === 0) {
            return '<p class="astuce">Aucune référence textuelle détectée. Un chargement dynamique reste possible : la décision reste à confirmer.</p>';
        }

        return '<ul class="usages-liste">' + usages.map((usage) =>
            '<li class="usage' + (usage.exact ? ' usage--exact' : '') + '">' +
                '<code>' + echapper(usage.fichier) + ':' + usage.ligne + ' ' + (usage.exact ? '(exact)' : '(partiel)') + '</code>' +
                '<span>' + echapper(usage.extrait) + '</span>' +
            '</li>'
        ).join('') + '</ul>';
    }

    function champPortee(media, decision) {
        const portee = decision.portee || 'globale';
        const fichiers = Array.from(new Set((media.usages || []).filter((usage) => usage.exact).map((usage) => usage.fichier)));
        const cibles = decision.cibles || [];

        return '<div class="option-ligne"><input type="radio" name="portee" value="globale" id="portee-globale"' +
            (portee === 'globale' ? ' checked' : '') + '><label for="portee-globale">Remplacer partout (toutes les références)</label></div>' +
            '<div class="option-ligne"><input type="radio" name="portee" value="pages" id="portee-pages"' +
            (portee === 'pages' ? ' checked' : '') + '><label for="portee-pages">Remplacer uniquement sur les pages cochées</label></div>' +
            '<div class="pages">' + fichiers.map((fichier) =>
                '<label class="option-ligne"><input type="checkbox" class="cible-page" value="' + echapper(fichier) + '"' +
                (cibles.includes(fichier) ? ' checked' : '') + '> <code>' + echapper(fichier) + '</code></label>'
            ).join('') + '</div>';
    }

    function htmlComparaison(media, remplacement) {
        const ancienVisuel = media.type === 'image'
            ? '<img src="' + echapper(urlFichier(media.chemin)) + '" alt="Aperçu du média actuel">'
            : '<video src="' + echapper(urlFichier(media.chemin)) + '" controls muted playsinline preload="metadata"></video>';
        const nouveauVisuel = remplacement.type === 'image'
            ? '<img src="' + echapper(urlImport(media)) + '" alt="Aperçu du nouveau média">'
            : '<video src="' + echapper(urlImport(media)) + '" controls muted playsinline preload="metadata"></video>';
        const largeur = remplacement.dimensions && remplacement.dimensions.largeur;
        const hauteur = remplacement.dimensions && remplacement.dimensions.hauteur;
        const nouvellesDimensions = largeur && hauteur ? largeur + '×' + hauteur : 'dimensions n/d';

        return '<div class="remplacement">' +
            '<div class="remplacement__cote"><h4>Actuel</h4>' + ancienVisuel +
                '<p class="remplacement__meta">' + echapper(media.chemin) + '<br>' + dimensions(media) + ' · ' + octets(media.octets) + ' · ' + echapper(media.format) + '</p>' +
            '</div>' +
            '<div class="remplacement__cote"><h4>Nouveau (temporaire)</h4>' + nouveauVisuel +
                '<p class="remplacement__meta">' + echapper(remplacement.nom_original || '') + '<br>' + nouvellesDimensions + ' · ' +
                    octets(remplacement.octets) + ' · ' + echapper(remplacement.format) + '<br>Empreinte ' +
                    echapper(String(remplacement.sha256 || '').slice(0, 16)) + '…</p>' +
            '</div>' +
        '</div>';
    }


    function ouvrirDetail(chemin) {
        if (!mediaParChemin(chemin)) {
            return;
        }

        etat.detail = chemin;
        rendreDetail();

        const modale = $('#modale-detail');
        if (!modale.open) {
            modale.showModal();
        }
    }

    function visuelGrand(media) {
        if (media.type === 'image') {
            return '<img src="' + echapper(urlFichier(media.chemin)) + '" alt="Aperçu de ' + echapper(media.nom) + '">';
        }
        if (media.type === 'video') {
            return '<video src="' + echapper(urlFichier(media.chemin)) + '" controls muted playsinline preload="metadata"></video>';
        }
        return '<audio src="' + echapper(urlFichier(media.chemin)) + '" controls preload="metadata"></audio>';
    }

    function boutonDetail(media, decision, libelle) {
        const actif = decisionDe(media).decision === decision;
        return '<button type="button" class="btn btn--mini' + (decision === 'delete' ? ' btn--danger-leger' : '') +
            '" data-detail="' + decision + '" aria-pressed="' + (actif ? 'true' : 'false') + '">' + libelle + '</button>';
    }

    function rendreDetail() {
        const media = mediaParChemin(etat.detail);
        const modale = $('#modale-detail');

        if (!media) {
            if (modale.open) {
                modale.close();
            }
            return;
        }

        const decision = decisionDe(media);
        const choix = decision.decision || 'undecided';
        const remplacement = decision.remplacement || null;
        const variantes = (media.variantes || []).length
            ? media.variantes.map((variante) => '<code>' + echapper(variante) + '</code>').join(' ')
            : 'aucune';

        let html = '';

        html += '<div class="apercu">' +
            '<div class="apercu__visuel">' + visuelGrand(media) + '</div>' +
            '<div><table class="donnees">' +
                '<tr><th>Chemin</th><td>' + echapper(media.chemin) + '</td></tr>' +
                '<tr><th>Type</th><td>' + echapper(media.type) + ' · ' + echapper(media.format) + '</td></tr>' +
                '<tr><th>Dimensions</th><td>' + dimensions(media) + '</td></tr>' +
                '<tr><th>Poids</th><td>' + octets(media.octets) + '</td></tr>' +
                '<tr><th>Statut</th><td>' + echapper(media.statut) + '</td></tr>' +
                '<tr><th>Catégorie</th><td>' + echapper(media.categorie) + '</td></tr>' +
                (media.duree ? '<tr><th>Durée</th><td>' + echapper(media.duree) + ' s</td></tr>' : '') +
                (media.codec ? '<tr><th>Codec</th><td>' + echapper(media.codec) + '</td></tr>' : '') +
                '<tr><th>Empreinte SHA-256</th><td><code>' + echapper(media.sha256 || 'n/d') + '</code></td></tr>' +
                '<tr><th>Modifié le</th><td>' + dateLisible(media.modifie_le) + '</td></tr>' +
                '<tr><th>Variantes</th><td>' + variantes + '</td></tr>' +
                '<tr><th>Doublons</th><td>' + ((media.doublons || []).length ? echapper(media.doublons.join(', ')) : 'aucun') + '</td></tr>' +
            '</table></div>' +
        '</div>';

        html += '<div class="bloc"><h3>Décision</h3>' +
            '<div class="carte__actions">' +
                boutonDetail(media, 'keep', 'Conserver') +
                boutonDetail(media, 'delete', 'Supprimer') +
                boutonDetail(media, 'replace', 'Remplacer') +
                boutonDetail(media, 'undecided', 'Non décidé') +
            '</div>' +
            '<label class="option-ligne"><span>Note interne</span>' +
                '<input type="text" id="note-detail" value="' + echapper(decision.note || '') + '" placeholder="Ex. visuel validé par le propriétaire">' +
            '</label>' +
            '<button type="button" class="btn btn--mini" data-detail="note">Enregistrer la note</button>' +
        '</div>';

        $('#titre-detail').textContent = media.nom;
        $('#detail-contenu').innerHTML = html;
        ajouterDetail(media, decision, choix, remplacement);
    }


    function ajouterDetail(media, decision, choix, remplacement) {
        let html = '';

        if (choix === 'replace' && remplacement) {
            html += '<div class="bloc"><h3>Remplacement préparé</h3>' +
                htmlComparaison(media, remplacement) +
                champPortee(media, decision) +
                '<label class="option-ligne"><input type="checkbox" id="genere-variantes"' + (decision.generer_variantes ? ' checked' : '') +
                    '> Générer les variantes AVIF/WebP à l’application et mesurer le gain (images)</label>' +
                '<div class="modale__boutons">' +
                    '<button type="button" class="btn btn--mini" data-detail="enregistrer-options">Enregistrer portée et options</button>' +
                    '<button type="button" class="btn btn--mini btn--danger-leger" data-detail="annuler-remplacement">Abandonner ce remplacement</button>' +
                '</div>' +
                '<p class="astuce">L’ancien fichier n’est remplacé qu’après « Appliquer » confirmé. L’import reste dans le dossier temporaire jusque-là.</p>' +
            '</div>';
        } else {
            const accept = media.type === 'video' ? 'video/*' : (media.type === 'audio' ? 'audio/*' : 'image/*');
            html += '<div class="bloc"><h3>Choisir un fichier local</h3>' +
                '<p class="astuce">Sélecteur de fichiers réel. Le fichier est envoyé dans <code>storage/media-manager/imports/</code>, comparé, puis conservé tant que tu n’as pas confirmé.</p>' +
                '<input type="file" id="fichier-remplacement" accept="' + accept + '">' +
                '<label class="option-ligne"><input type="checkbox" id="genere-variantes"> Générer les variantes AVIF/WebP à l’application et mesurer le gain (images)</label>' +
                '<button type="button" class="btn btn--principal" data-detail="televerser">Téléverser et comparer</button>' +
            '</div>';
        }

        if (choix === 'delete') {
            const fichiers = Array.from(new Set((media.usages || []).filter((usage) => usage.exact).map((usage) => usage.fichier)));
            html += '<div class="bloc"><h3>Suppression</h3>' +
                '<p class="astuce">Aucune suppression immédiate. Le fichier part en corbeille restaurable après application, et uniquement s’il n’est plus référencé.</p>' +
                (fichiers.length
                    ? '<label class="option-ligne"><input type="checkbox" id="retrait-demande"' +
                        (decision.retrait && decision.retrait.demande ? ' checked' : '') +
                        '> Demander le retrait des références simples (balises <code>&lt;source&gt;</code>, <code>&lt;img&gt;</code> autonomes) — vérifié pendant l’application</label>' +
                      '<button type="button" class="btn btn--mini" data-detail="retrait">Enregistrer la demande de retrait</button>'
                    : '<p>Média sans référence exacte : la corbeille suffira.</p>') +
            '</div>';
        }

        html += '<div class="bloc"><h3>Dérogation au contrat (si nécessaire)</h3>' +
            '<p class="astuce">Une dérogation est une décision explicite et tracée, jamais automatique. Elle est obligatoire pour appliquer un média dont le contrat est bloqué.</p>' +
            '<label class="option-ligne"><input type="checkbox" id="derogation-accordee"' +
                (decision.derogation && decision.derogation.accordee ? ' checked' : '') + '> J’accorde une dérogation pour ce média</label>' +
            '<label class="option-ligne"><span>Motif</span><input type="text" id="derogation-motif" value="' +
                echapper(decision.derogation ? decision.derogation.motif || '' : '') +
                '" placeholder="Ex. poster décoratif, pas de picture possible sans refonte"></label>' +
            '<button type="button" class="btn btn--mini" data-detail="derogation">Enregistrer la dérogation</button>' +
        '</div>';

        html += '<div class="bloc"><h3>Usages détectés (' + media.usages_total + ')</h3>' + htmlUsages(media) + '</div>';
        html += htmlContrat(media.contrat);

        $('#detail-contenu').insertAdjacentHTML('beforeend', html);
    }


    async function traiterDetail(action) {
        const chemin = etat.detail;
        const media = mediaParChemin(chemin);
        if (!media) {
            return;
        }

        if (action === 'keep' || action === 'delete' || action === 'replace' || action === 'undecided') {
            await definirDecision(chemin, action);
            return;
        }

        if (action === 'televerser') {
            const champFichier = $('#fichier-remplacement');
            const fichier = champFichier && champFichier.files ? champFichier.files[0] : null;
            const porteeChoisie = document.querySelector('input[name="portee"]:checked');
            const genere = $('#genere-variantes') ? $('#genere-variantes').checked : false;
            await televerser(chemin, fichier, porteeChoisie ? porteeChoisie.value : 'globale', genere);
            return;
        }

        if (action === 'annuler-remplacement') {
            await annulerRemplacement(chemin);
            return;
        }

        if (action === 'enregistrer-options') {
            const porteeChoisie = document.querySelector('input[name="portee"]:checked');
            const cibles = $$('.cible-page').filter((caseCoche) => caseCoche.checked).map((caseCoche) => caseCoche.value);
            const genere = $('#genere-variantes') ? $('#genere-variantes').checked : false;
            await definirDecision(chemin, 'replace', {
                portee: porteeChoisie ? porteeChoisie.value : 'globale',
                cibles: cibles,
                generer_variantes: genere,
            });
            return;
        }

        if (action === 'note') {
            const note = $('#note-detail') ? $('#note-detail').value : '';
            await definirDecision(chemin, decisionDe(media).decision || 'undecided', { note: note });
            return;
        }

        if (action === 'retrait') {
            const demande = $('#retrait-demande') ? $('#retrait-demande').checked : false;
            const fichiers = Array.from(new Set((media.usages || []).filter((usage) => usage.exact).map((usage) => usage.fichier)));
            await definirDecision(chemin, 'delete', { retrait: { demande: demande, fichiers: fichiers } });
            return;
        }

        if (action === 'derogation') {
            const accordee = $('#derogation-accordee') ? $('#derogation-accordee').checked : false;
            const motif = $('#derogation-motif') ? $('#derogation-motif').value.trim() : '';
            await enregistrerDerogation(chemin, accordee, motif);
        }
    }


    // ---------- Validation et application ----------

    async function ouvrirValidation() {
        occupe(true);
        try {
            const reponse = await api('validate', { method: 'POST', body: {} });
            etat.validation = reponse.validation;
            etat.data.validation = reponse.validation;
            $('#btn-appliquer').disabled = reponse.bloquants !== 0;
            rendreValidation(reponse.validation, reponse.bloquants);
            $('#confirmation-apply').value = '';
            $('#btn-confirmer-apply').disabled = true;
            $('#modale-validation').showModal();
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }

    function caseRecap(valeur, libelle) {
        return '<div class="recap__case"><strong>' + echapper(valeur) + '</strong><span>' + echapper(libelle) + '</span></div>';
    }

    function rendreValidation(validation, bloquants) {
        const recap = validation.recapitulatif || {};
        const ordre = { bloquant: 0, avertissement: 1, information: 2 };
        const conflits = (validation.conflits || []).slice().sort((a, b) => (ordre[a.gravite] || 9) - (ordre[b.gravite] || 9));

        let html = '<div class="recap">' +
            caseRecap(recap.nb_conserver || 0, 'à conserver') +
            caseRecap(recap.nb_supprimer || 0, 'à supprimer') +
            caseRecap(recap.nb_remplacer || 0, 'à remplacer') +
            caseRecap(recap.nb_non_decides || 0, 'non décidés') +
            caseRecap(octets(recap.octets_supprimes || 0), 'poids des suppressions') +
        '</div>';

        html += '<div class="bloc"><h3>Pages et fichiers affectés</h3>' +
            ((recap.pages_affectees || []).length
                ? '<div class="pages">' + recap.pages_affectees.map((page) => '<code>' + echapper(page) + '</code>').join(' ') + '</div>'
                : '<p class="astuce">Aucun fichier de code affecté.</p>') +
        '</div>';

        html += '<div class="bloc"><h3>Conflits et alertes (' + conflits.length + ')</h3>' +
            (conflits.length
                ? '<ul class="conflits">' + conflits.map((conflit) =>
                    '<li class="conflit conflit--' + echapper(conflit.gravite) + '">' +
                        '<strong>' + echapper(conflit.message) + ' — <code>' + echapper(conflit.media) + '</code></strong>' +
                        '<span>' + echapper(conflit.detail) + '</span>' +
                    '</li>'
                ).join('') + '</ul>'
                : '<p class="astuce">Aucun conflit détecté.</p>') +
        '</div>';

        html += '<div class="bloc"><h3>Contrat média des remplacements</h3>' +
            (Object.keys(validation.contrats || {}).length
                ? Object.entries(validation.contrats).map(([chemin, contrat]) =>
                    '<div><strong>' + echapper(chemin) + '</strong> — ' + echapper(contrat.statut) +
                    ' (' + contrat.bloquant + ' bloquant, ' + contrat.avertissements + ' avertissement)</div>'
                ).join('')
                : '<p class="astuce">Aucun remplacement à évaluer.</p>') +
        '</div>';

        if (bloquants > 0) {
            html += '<p class="avertissement"><strong>' + bloquants + ' conflit(s) bloquant(s).</strong> L’application restera refusée tant qu’ils ne sont pas résolus (conserver, remplacer, déroger avec motif).</p>';
        }

        $('#validation-contenu').innerHTML = html;
    }


    async function appliquerChangements() {
        const confirmation = $('#confirmation-apply').value.trim();
        if (confirmation !== 'APPLIQUER') {
            toast('Saisis exactement APPLIQUER.', 'erreur');
            return;
        }

        occupe(true);
        session('Application en cours : sauvegarde, copies, vérifications…');

        try {
            const reponse = await api('apply', {
                method: 'POST',
                body: { confirmation: confirmation },
            });

            $('#modale-validation').close();
            afficherRapport(reponse);
            await chargerEtat();
            session('Application terminée · ' + reponse.application.statut);
            toast('Application terminée. Sauvegarde : ' + reponse.sauvegarde, 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
            session('Application refusée ou interrompue', true);
            if (erreur.donnees && erreur.donnees.manifest) {
                afficherRapport({
                    application: null,
                    manifest: erreur.donnees.manifest,
                    sauvegarde: '',
                    rapport: erreur.donnees.rapport,
                });
            }
        } finally {
            occupe(false);
        }
    }

    function afficherRapport(reponse) {
        const manifest = reponse.manifest || {};
        const application = reponse.application || {};
        const remplacements = manifest.remplacements || [];
        const suppressions = manifest.suppressions || [];
        const corbeille = manifest.corbeille || [];
        const retraits = manifest.retraits || [];
        const tests = manifest.tests || {};
        const fumee = tests.fumee || {};

        let html = '<p><strong>Statut :</strong> ' + echapper(application.statut || manifest.statut || 'n/d') +
            ' · <strong>Sauvegarde :</strong> <code>' + echapper(reponse.sauvegarde || application.sauvegarde || '—') + '</code></p>';

        if (remplacements.length) {
            html += '<div class="bloc"><h3>Remplacements (' + remplacements.length + ')</h3><ul class="usages-liste">' +
                remplacements.map((item) =>
                    '<li class="usage usage--exact"><code>' + echapper(item.ancien) + ' → ' + echapper(item.nouveau) + '</code>' +
                    '<span>' + octets(item.octets) + ' · portée ' + echapper(item.portee) +
                    ((item.references_mises_a_jour || []).length ? ' · références mises à jour : ' + echapper(item.references_mises_a_jour.join(', ')) : '') +
                    '</span></li>'
                ).join('') + '</ul></div>';
        }

        if (retraits.length) {
            html += '<div class="bloc"><h3>Retraits demandés (' + retraits.length + ')</h3><ul class="usages-liste">' +
                retraits.map((item) =>
                    '<li class="usage"><code>' + echapper(item.media) + '</code><span>' +
                    echapper((item.fichiers || []).join(', ') || 'aucun fichier modifié') +
                    ' · références non retirées : ' + (item.refusees || 0) + '</span></li>'
                ).join('') + '</ul></div>';
        }

        if (suppressions.length) {
            html += '<div class="bloc"><h3>Suppressions (' + suppressions.length + ')</h3><ul class="usages-liste">' +
                suppressions.map((item) =>
                    '<li class="usage"><code>' + echapper(item.media) + '</code><span>statut : ' + echapper(item.statut) +
                    (item.raison ? ' — ' + echapper(item.raison) : '') + '</span></li>'
                ).join('') + '</ul></div>';
        }

        if (corbeille.length) {
            html += '<p class="astuce">Corbeille : <code>' + echapper(corbeille.join(', ')) + '</code></p>';
        }


        if (fumee && (fumee.routes || []).length) {
            html += '<div class="bloc"><h3>Test HTTP après application</h3><table class="donnees">' +
                '<tr><th>Route</th><th>Attendu</th><th>Obtenu</th></tr>' +
                fumee.routes.map((route) =>
                    '<tr><td><code>' + echapper(route.route) + '</code></td><td>' + route.attendu + '</td><td>' +
                    (route.obtenu === route.attendu ? 'OK ' : 'ÉCHEC ') + echapper(route.obtenu) + '</td></tr>'
                ).join('') + '</table></div>';
        } else if (fumee && fumee.execute === false) {
            html += '<p class="avertissement">Test HTTP non exécuté : ' + echapper(fumee.raison || 'raison inconnue') + '</p>';
        }

        if ((tests.lint || []).length) {
            html += '<p class="astuce">php -l : ' + tests.lint.map((resultat) =>
                echapper(resultat.chemin) + ' → ' + (resultat.code === 0 ? 'OK' : 'ERREUR')
            ).join(' · ') + '</p>';
        }

        if ((manifest.erreurs || []).length) {
            html += '<div class="bloc"><h3>Erreurs</h3><ul class="conflits">' +
                manifest.erreurs.map((erreur) => '<li class="conflit conflit--bloquant"><span>' + echapper(erreur) + '</span></li>').join('') +
            '</ul></div>';
        }

        if ((manifest.avertissements || []).length) {
            html += '<div class="bloc"><h3>Avertissements</h3><ul class="conflits">' +
                manifest.avertissements.map((alerte) => '<li class="conflit conflit--avertissement"><span>' + echapper(alerte) + '</span></li>').join('') +
            '</ul></div>';
        }

        $('#rapport-contenu').innerHTML = html;

        let boutons = '';
        if (application.id) {
            boutons += '<a class="btn btn--discret" href="/api/report?id=' + encodeURIComponent(application.id) +
                '&t=' + encodeURIComponent(etat.token) + '" download>Télécharger le rapport JSON</a>';
            boutons += '<button type="button" class="btn btn--danger-leger" data-rollback="' + echapper(application.id) +
                '">Restaurer cette sauvegarde</button>';
        }
        $('#rapport-boutons').innerHTML = boutons;
        $('#modale-rapport').showModal();
    }

    async function restaurer(applicationId) {
        if (!window.confirm('Restaurer la sauvegarde ' + applicationId + ' ? Les fichiers actuels seront remplacés par la version sauvegardée.')) {
            return;
        }

        occupe(true);
        try {
            const reponse = await api('rollback', { method: 'POST', body: { id: applicationId } });
            toast(
                reponse.restauration.ok ? 'Sauvegarde restaurée.' : 'Restauration partielle : vérifie les erreurs.',
                reponse.restauration.ok ? 'succes' : 'erreur'
            );
            $('#modale-rapport').close();
            await chargerEtat();
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }


    // ---------- Maintenance ----------

    async function scanner() {
        if (etat.occupe) {
            return;
        }
        occupe(true);
        session('Nouveau scan du projet…');
        try {
            const reponse = await api('scan', { method: 'POST', body: {} });
            etat.data = reponse.etat;
            etat.selection.clear();
            etat.validation = null;
            rendreTout();
            session('Scan terminé · ' + reponse.medias + ' média(s)');
            toast(reponse.medias + ' média(s) inventoriés.', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
            session('Scan impossible', true);
        } finally {
            occupe(false);
        }
    }

    async function enregistrerExplicitement() {
        occupe(true);
        try {
            const reponse = await api('save', { method: 'POST', body: {} });
            toast('JSON enregistré : ' + reponse.fichier, 'succes');
            session('Enregistré · empreinte ' + String(reponse.empreinte || '').slice(0, 12) + '…');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }

    async function importerJson(fichier) {
        if (!fichier) {
            return;
        }

        occupe(true);
        session('Lecture du JSON importé…');
        try {
            const texte = await fichier.text();
            const donnees = JSON.parse(texte);
            const reponse = await api('import', { method: 'POST', body: { etat: donnees } });
            await chargerEtat();
            const ignores = (reponse.ignores || []).length;
            const remplacements = (reponse.remplacements_ignores || []).length;
            toast(
                reponse.importes + ' décision(s) importée(s)' +
                (ignores ? ', ' + ignores + ' ignorée(s)' : '') +
                (remplacements ? ', ' + remplacements + ' remplacement(s) sans fichier temporaire' : '') + '.',
                'succes'
            );
            session('JSON importé · révision ' + reponse.revision);
        } catch (erreur) {
            toast('Import refusé : ' + erreur.message, 'erreur');
            session('Import refusé', true);
        } finally {
            occupe(false);
        }
    }

    async function reinitialiser() {
        const cibles = Array.from(etat.selection);
        const tout = cibles.length === 0;

        if (tout && !window.confirm('Réinitialiser toutes les décisions ? Les fichiers importés restent dans le dossier temporaire.')) {
            return;
        }

        occupe(true);
        try {
            const reponse = await api('reset', {
                method: 'POST',
                body: tout ? { tout: true } : { chemins: cibles },
            });
            await chargerEtat();
            toast(reponse.reinitialises + ' décision(s) réinitialisée(s).', 'succes');
        } catch (erreur) {
            toast(erreur.message, 'erreur');
        } finally {
            occupe(false);
        }
    }


    // ---------- Événements ----------

    function brancherEvenements() {
        $('#recherche').addEventListener('input', (evenement) => {
            etat.filtres.q = evenement.target.value;
            rendreGrille();
        });

        $('#filtre-statut').addEventListener('change', (evenement) => {
            etat.filtres.statut = evenement.target.value;
            rendreGrille();
        });

        $('#filtre-categorie').addEventListener('change', (evenement) => {
            etat.filtres.categorie = evenement.target.value;
            rendreGrille();
        });

        $('#filtre-decision').addEventListener('change', (evenement) => {
            etat.filtres.decision = evenement.target.value;
            rendreGrille();
        });

        $('#btn-tout-selectionner').addEventListener('click', () => {
            const visibles = itemsFiltres().map((media) => media.chemin);
            const tousSelectionnes = visibles.length > 0 && visibles.every((chemin) => etat.selection.has(chemin));

            if (tousSelectionnes) {
                visibles.forEach((chemin) => etat.selection.delete(chemin));
            } else {
                visibles.forEach((chemin) => etat.selection.add(chemin));
            }
            rendreGrille();
        });

        $('#btn-conserver-selection').addEventListener('click', () => decisionEnLot(Array.from(etat.selection), 'keep'));
        $('#btn-supprimer-selection').addEventListener('click', () => decisionEnLot(Array.from(etat.selection), 'delete'));
        $('#btn-nondecide-selection').addEventListener('click', () => decisionEnLot(Array.from(etat.selection), 'undecided'));
        $('#btn-reset').addEventListener('click', reinitialiser);
        $('#btn-annuler').addEventListener('click', annulerDernier);

        $('#btn-scan').addEventListener('click', scanner);
        $('#btn-save').addEventListener('click', enregistrerExplicitement);
        $('#btn-export').addEventListener('click', () => {
            window.location.href = '/api/export?t=' + encodeURIComponent(etat.token || '');
        });
        $('#btn-valider').addEventListener('click', ouvrirValidation);
        $('#btn-appliquer').addEventListener('click', ouvrirValidation);

        $('#fichier-json').addEventListener('change', (evenement) => {
            const fichier = evenement.target.files ? evenement.target.files[0] : null;
            importerJson(fichier).finally(() => {
                evenement.target.value = '';
            });
        });

        $('#grille').addEventListener('change', (evenement) => {
            const caseCoche = evenement.target.closest('[data-selection]');
            if (!caseCoche) {
                return;
            }

            const chemin = caseCoche.dataset.selection;
            if (caseCoche.checked) {
                etat.selection.add(chemin);
            } else {
                etat.selection.delete(chemin);
            }

            const carte = caseCoche.closest('.carte');
            if (carte) {
                carte.dataset.selectionnee = caseCoche.checked ? '1' : '0';
            }
            rendreSelection();
        });

        $('#grille').addEventListener('click', (evenement) => {
            if (evenement.target.closest('[data-selection]')) {
                return;
            }

            const bouton = evenement.target.closest('[data-action]');
            if (bouton) {
                evenement.preventDefault();
                definirDecision(bouton.dataset.chemin, bouton.dataset.action);
                return;
            }

            const ouvrir = evenement.target.closest('[data-ouvrir]');
            if (ouvrir) {
                ouvrirDetail(ouvrir.dataset.ouvrir);
            }
        });

        $('#detail-contenu').addEventListener('click', (evenement) => {
            const bouton = evenement.target.closest('[data-detail]');
            if (!bouton) {
                return;
            }
            evenement.preventDefault();
            traiterDetail(bouton.dataset.detail);
        });

        $('#confirmation-apply').addEventListener('input', (evenement) => {
            const bloquants = etat.validation
                ? (etat.validation.conflits || []).filter((conflit) => conflit.gravite === 'bloquant').length
                : 1;
            $('#btn-confirmer-apply').disabled = evenement.target.value.trim() !== 'APPLIQUER' || bloquants > 0;
        });

        $('#btn-confirmer-apply').addEventListener('click', appliquerChangements);
        $('#btn-fermer-validation').addEventListener('click', () => $('#modale-validation').close());

        $('#rapport-boutons').addEventListener('click', (evenement) => {
            const bouton = evenement.target.closest('[data-rollback]');
            if (bouton) {
                restaurer(bouton.dataset.rollback);
            }
        });

        document.addEventListener('click', (evenement) => {
            const fermeture = evenement.target.closest('[data-fermer]');
            if (fermeture) {
                const modale = fermeture.closest('dialog');
                if (modale) {
                    modale.close();
                }
            }
        });
    }

    async function demarrer() {
        brancherEvenements();
        try {
            await chargerEtat();
            session('Inventaire chargé · révision ' + etat.data.revision);
        } catch (erreur) {
            session('Chargement impossible : ' + erreur.message, true);
            $('#etat-vide').textContent = 'Chargement impossible : ' + erreur.message;
        }
    }

    demarrer();
}());

