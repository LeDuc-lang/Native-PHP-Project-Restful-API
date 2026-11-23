<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motus — Jouer à deux</title>
    <link rel="stylesheet" href="/assets/css/pico.min.css">
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="app-shell">
    <header class="site-header">
        <div class="brand">
            <div class="brand-tiles" aria-hidden="true">
                <span>M</span><span>O</span><span>T</span><span>U</span><span>S</span>
            </div>
            <div>
                <h1>Motus</h1>
                <p class="muted">API PHP native · jQuery · Projet collectif</p>
            </div>
        </div>
        <span class="header-note">Deux joueurs, six lettres, huit essais</span>
    </header>

    <div id="alertBox" class="alert" hidden role="status" aria-live="polite"></div>

    <!-- Zone affichée lorsque l'utilisateur n'est pas authentifié -->
    <section id="guestArea">
        <div class="auth-layout">
            <article class="auth-hero">
                <span class="eyebrow">Jeu à deux</span>
                <h2>Deux joueurs.<br>Un mot. Six lettres.</h2>
                <p>
                    Connectez-vous, défiez un joueur en ligne et trouvez le mot caché
                    avant la fin des huit propositions.
                </p>

                <ul class="auth-points">
                    <li>Un mot de 6 lettres à deviner</li>
                    <li>Rouge, jaune, bleu : chaque lettre vous guide</li>
                    <li>8 propositions et 30 secondes par coup</li>
                    <li>Les grilles se synchronisent automatiquement</li>
                </ul>

                <div class="demo-box">
                    <strong>Comptes de démonstration</strong><br>
                    <code>lucas.morel@example.com</code><br>
                    <code>clara.duval@example.com</code><br>
                    Mot de passe : <code>password</code>
                </div>
            </article>

            <div class="auth-forms">
                <article id="loginArticle" class="panel">
                    <header class="panel-head">
                        <div>
                            <span class="eyebrow">Connexion</span>
                            <h2>Bon retour</h2>
                        </div>
                    </header>

                    <form id="loginForm">
                        <label for="loginEmail">Adresse e-mail
                            <input type="email" id="loginEmail" name="email"
                                   autocomplete="username" required>
                        </label>
                        <label for="loginPassword">Mot de passe
                            <input type="password" id="loginPassword" name="password"
                                   autocomplete="current-password" required>
                        </label>
                        <button type="submit" class="btn-primary">Se connecter</button>
                    </form>

                    <p class="form-footer">
                        Pas encore de compte ?
                        <a href="#" id="showRegisterLink">Créer un compte</a>
                    </p>
                </article>

                <article id="registerArticle" class="panel" hidden>
                    <header class="panel-head">
                        <div>
                            <span class="eyebrow">Inscription</span>
                            <h2>Créer un compte</h2>
                        </div>
                    </header>

                    <form id="registerForm">
                        <div class="grid">
                            <label for="registerFirstName">Prénom
                                <input id="registerFirstName" name="first_name" maxlength="50" required>
                            </label>
                            <label for="registerLastName">Nom
                                <input id="registerLastName" name="last_name" maxlength="50" required>
                            </label>
                        </div>

                        <div class="grid">
                            <label for="registerPseudo">Pseudo
                                <input id="registerPseudo" name="pseudo" maxlength="50" required>
                            </label>
                            <label for="registerBirthDate">Date de naissance
                                <input type="date" id="registerBirthDate" name="birth_date" required>
                            </label>
                        </div>

                        <label for="registerGender">Genre
                            <select id="registerGender" name="gender" required>
                                <option value="m">Homme</option>
                                <option value="f">Femme</option>
                                <option value="o">Autre</option>
                            </select>
                        </label>

                        <label for="registerEmail">Adresse e-mail
                            <input type="email" id="registerEmail" name="email" required>
                        </label>

                        <label for="registerPassword">Mot de passe (6 caractères minimum)
                            <input type="password" id="registerPassword" name="password"
                                   minlength="6" autocomplete="new-password" required>
                        </label>

                        <button type="submit" class="btn-primary">Créer mon compte</button>
                    </form>

                    <p class="form-footer">
                        Déjà inscrit ?
                        <a href="#" id="showLoginLink">Se connecter</a>
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- Zone affichée lorsque l'utilisateur est authentifié -->
    <section id="accountArea" hidden>
        <div class="topbar">
            <div class="user-chip">
                <span class="avatar avatar-sm" id="navAvatar">?</span>
                <div>
                    <strong id="navPseudo"></strong>
                    <span class="muted" id="navEmail"></span>
                </div>
            </div>

            <form id="logoutForm" class="inline-form">
                <button type="submit" class="btn-ghost">Se déconnecter</button>
            </form>
        </div>

        <!-- Motus : lobby et partie à deux, synchronisés par polling AJAX -->
        <section id="motusArea" class="motus-card">
            <div class="motus-hero">
                <div class="motus-brand">
                    <div class="motus-logo" aria-hidden="true">
                        <span>M</span><span>O</span><span>T</span><span>U</span><span>S</span>
                    </div>
                    <div>
                        <h2>Partie à deux</h2>
                        <p class="muted">Défiez un joueur connecté et trouvez le mot de 6 lettres.</p>
                    </div>
                </div>

                <span id="motusPresence" class="status-pill status-lobby">Salon</span>
            </div>

            <ul class="motus-facts">
                <li><strong>6</strong> lettres</li>
                <li><strong>8</strong> propositions</li>
                <li><strong>30 s</strong> par coup</li>
                <li>Partie synchronisée en direct</li>
            </ul>

            <div id="motusLobby">
                <div class="section-head">
                    <h3>
                        Joueurs disponibles
                        <span id="availableCount" class="count-badge">0</span>
                    </h3>
                    <button id="refreshPlayersButton" type="button" class="btn-ghost btn-sm">
                        Actualiser
                    </button>
                </div>

                <div id="availablePlayers" class="players-grid" role="list">
                    <div class="empty-state">Chargement des joueurs…</div>
                </div>

                <p id="motusLobbyMessage" class="inline-hint" aria-live="polite"></p>
            </div>

            <div id="motusGame" hidden>
                <div id="motusCallout" class="callout" hidden>
                    <span class="callout-icon" aria-hidden="true">⚔</span>
                    <div class="callout-text">
                        <strong id="motusCalloutTitle"></strong>
                        <p id="motusCalloutText" class="muted"></p>
                    </div>
                    <button id="motusCalloutButton" type="button" class="btn-primary btn-sm">
                        Rejoindre la partie
                    </button>
                    <button id="declineGameButton" type="button" class="btn-ghost btn-sm">
                        Refuser
                    </button>
                </div>

                <div class="matchup">
                    <div class="player-card" id="motusPlayerCard1">
                        <span class="avatar avatar-sm" id="motusAvatar1">?</span>
                        <span class="player-name" id="motusPlayer1">Joueur 1</span>
                        <span class="player-tag" id="motusTag1" hidden>Vous</span>
                    </div>

                    <span class="vs">VS</span>

                    <div class="player-card" id="motusPlayerCard2">
                        <span class="avatar avatar-sm" id="motusAvatar2">?</span>
                        <span class="player-name" id="motusPlayer2">Joueur 2</span>
                        <span class="player-tag" id="motusTag2" hidden>Vous</span>
                    </div>
                </div>

                <div class="turn-banner" id="motusTurnBanner">
                    <div>
                        <h3 id="motusTurn">En attente…</h3>
                        <p class="muted" id="motusTurnHint"></p>
                    </div>
                    <div class="timer" id="motusTimerWrap" hidden>
                        <div class="timer-track"><span id="motusTimerBar"></span></div>
                        <strong id="motusTimer">30 s</strong>
                    </div>
                </div>

                <div class="game-body">
                    <div class="board-panel">
                        <div class="board-head">
                            <h3>Grille des propositions</h3>
                            <span id="motusCounter" class="count-badge">0 / 8</span>
                        </div>

                        <div id="attemptsBoard" class="motus-board"></div>
                        <p id="motusWordReveal" class="reveal" hidden></p>
                    </div>

                    <aside class="side-panel">
                        <form id="attemptForm" class="attempt-form">
                            <label for="attemptWord">Votre proposition</label>
                            <input id="attemptWord" maxlength="6" minlength="6"
                                   pattern="[A-Za-z]{6}" placeholder="MOTUS"
                                   autocomplete="off" autocapitalize="characters"
                                   spellcheck="false" aria-label="Mot de 6 lettres" required>
                            <button type="submit" class="btn-primary">Proposer le mot</button>
                        </form>

                        <p id="motusGameMessage" class="inline-hint" aria-live="polite"></p>

                        <div class="legend">
                            <span><i class="dot red"></i> Lettre bien placée</span>
                            <span><i class="dot yellow"></i> Lettre présente, mal placée</span>
                            <span><i class="dot blue"></i> Lettre absente du mot</span>
                        </div>

                        <div class="motus-actions">
                            <button type="button" id="leaveGameButton" class="btn-danger">
                                Quitter la partie
                            </button>
                            <button type="button" id="backToLobbyButton" class="btn-ghost" hidden>
                                Retour au salon
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <article class="panel profile-panel">
            <header class="panel-head">
                <div class="user-chip">
                    <span class="avatar avatar-lg" id="profileAvatarBadge">?</span>
                    <div>
                        <span class="eyebrow">Mon compte</span>
                        <h2 id="profilePseudo"></h2>
                        <p class="muted" id="profileEmail"></p>
                    </div>
                </div>
            </header>

            <dl class="profile-details">
                <div><dt>Nom complet</dt><dd id="profileFullName"></dd></div>
                <div><dt>Date de naissance</dt><dd id="profileBirthDate"></dd></div>
                <div><dt>Genre</dt><dd id="profileGender"></dd></div>
                <div><dt>Compte créé le</dt><dd id="profileCreatedAt"></dd></div>
            </dl>
        </article>

        <div class="account-grid">
            <article class="panel">
                <header class="panel-head">
                    <div>
                        <span class="eyebrow">Paramètres</span>
                        <h2>Modifier mon compte</h2>
                    </div>
                </header>

                <form id="updateForm">
                    <label for="updatePseudo">Pseudo
                        <input id="updatePseudo" name="pseudo" maxlength="50" required>
                    </label>

                    <label for="updateGender">Genre
                        <select id="updateGender" name="gender">
                            <option value="m">Homme</option>
                            <option value="f">Femme</option>
                            <option value="o">Autre</option>
                        </select>
                    </label>

                    <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                </form>
            </article>

            <article class="panel danger-panel">
                <header class="panel-head">
                    <div>
                        <span class="eyebrow">Zone sensible</span>
                        <h2>Supprimer mon compte</h2>
                    </div>
                </header>
                <p class="muted">
                    Cette action est définitive : votre compte, vos parties et vos jetons de
                    rafraîchissement seront supprimés.
                </p>

                <form id="deleteForm">
                    <button type="submit" class="btn-danger">
                        Supprimer définitivement mon compte
                    </button>
                </form>
            </article>
        </div>
    </section>

    <footer>
        Développement Web — Technos legacy — 2025-2026 · Motus collectif
    </footer>
</main>

<script>
    /*
     * Client jQuery de l'API.
     *
     * Principe général :
     *  - l'Access Token (JWT) est conservé dans localStorage et envoyé dans
     *    l'en-tête Authorization à chaque requête protégée ;
     *  - le Refresh Token est conservé dans un cookie HttpOnly, inaccessible
     *    au JavaScript, et permet de renouveler automatiquement l'Access Token ;
     *  - si une requête répond 401, on tente un rafraîchissement puis on
     *    rejoue une seule fois la requête initiale.
     */
    const API_URL = '/api';
    const ACCESS_TOKEN_KEY = 'accessToken';
    const USER_KEY = 'user';

    let currentUser = null;
    let refreshRequest = null;
    let alertTimer = null;

    // Motus : état local et temporisation du polling.
    const MOTUS_LOBBY_POLL_MS = 3000;
    const MOTUS_GAME_POLL_MS = 2000;
    let motusTimer = null;
    let motusActive = false;
    let motusGame = null;
    let motusStarting = false;
    let motusClock = null;
    let motusSecondsLeft = 0;
    let motusSecondsTotal = 30;
    let motusCalloutGameId = null;

    const GENDER_LABELS = {
        m: 'Homme',
        f: 'Femme',
        o: 'Autre'
    };

    // ------------------------------------------------------------------
    // Gestion de la session locale
    // ------------------------------------------------------------------

    function getAccessToken() {
        return localStorage.getItem(ACCESS_TOKEN_KEY);
    }

    function getStoredUser() {
        try {
            return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
        } catch (error) {
            return null;
        }
    }

    function storeSession(accessToken, user) {
        if (accessToken) {
            localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
        }
        if (user) {
            localStorage.setItem(USER_KEY, JSON.stringify(user));
            currentUser = user;
        }
    }

    function clearSession() {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        currentUser = null;
    }

    // ------------------------------------------------------------------
    // Interface
    // ------------------------------------------------------------------

    function showAlert(message, type) {
        const $box = $('#alertBox');

        $box
            .removeClass('alert-success alert-error')
            .addClass(type === 'error' ? 'alert-error' : 'alert-success')
            .text(message)
            .prop('hidden', false);

        clearTimeout(alertTimer);
        alertTimer = setTimeout(function () {
            $box.prop('hidden', true);
        }, 5000);
    }

    function showLoginCard() {
        $('#loginArticle').prop('hidden', false);
        $('#registerArticle').prop('hidden', true);
    }

    function showRegisterCard() {
        $('#loginArticle').prop('hidden', true);
        $('#registerArticle').prop('hidden', false);
    }

    function render() {
        const isAuthenticated = Boolean(currentUser && getAccessToken());

        $('#guestArea').prop('hidden', isAuthenticated);
        $('#accountArea').prop('hidden', !isAuthenticated);

        if (!isAuthenticated) {
            showLoginCard();
            stopMotusPolling();
            return;
        }

        $('#profilePseudo').text(currentUser.pseudo || '');
        $('#profileEmail').text(currentUser.email || '');
        $('#profileFullName').text(
            [currentUser.first_name, currentUser.last_name].filter(Boolean).join(' ')
        );
        $('#profileBirthDate').text(currentUser.birth_date || '—');
        $('#profileGender').text(GENDER_LABELS[currentUser.gender] || '—');
        $('#profileCreatedAt').text(currentUser.created_at || '—');

        const initial = String(currentUser.pseudo || '?').charAt(0).toUpperCase();
        $('#navPseudo').text(currentUser.pseudo || '');
        $('#navEmail').text(currentUser.email || '');
        $('#navAvatar').text(initial);
        $('#profileAvatarBadge').text(initial);

        $('#updatePseudo').val(currentUser.pseudo || '');
        $('#updateGender').val(currentUser.gender || 'o');

        startMotusPolling();
    }

    // ------------------------------------------------------------------
    // Requêtes AJAX et rafraîchissement automatique du token
    // ------------------------------------------------------------------

    function apiErrorMessage(xhr, fallbackMessage) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        if (xhr && xhr.status === 0) {
            return 'Serveur injoignable. Vérifiez que l\'application est démarrée.';
        }

        return fallbackMessage;
    }

    /**
     * Renouvelle l'Access Token grâce au Refresh Token contenu dans le cookie.
     * Une seule requête de rafraîchissement est effectuée à la fois, même si
     * plusieurs requêtes AJAX échouent simultanément.
     */
    function refreshAccessToken() {
        if (refreshRequest) {
            return refreshRequest;
        }

        refreshRequest = new Promise(function (resolve, reject) {
            $.ajax({
                url: API_URL + '/token/refresh',
                method: 'POST',
                dataType: 'json',
                xhrFields: {withCredentials: true}
            })
                .done(function (response) {
                    if (!response || !response.data || !response.data.accessToken) {
                        reject({responseJSON: {message: 'Réponse de rafraîchissement invalide'}});
                        return;
                    }

                    storeSession(response.data.accessToken, response.data.user || currentUser);
                    resolve(response.data.accessToken);
                })
                .fail(function (xhr) {
                    reject(xhr);
                })
                .always(function () {
                    refreshRequest = null;
                });
        });

        return refreshRequest;
    }

    /**
     * Wrapper autour de $.ajax.
     *
     * options.url      : chemin relatif à /api (ex. '/user/1')
     * options.method   : verbe HTTP (GET par défaut)
     * options.data     : données à envoyer (objet)
     * options.json     : true pour envoyer du JSON, false pour un formulaire
     * options.skipRefresh : true pour ne pas tenter de rafraîchir le token
     */
    function apiRequest(options, hasRetried) {
        options = options || {};

        return new Promise(function (resolve, reject) {
            const settings = {
                url: API_URL + options.url,
                method: options.method || 'GET',
                dataType: 'json',
                xhrFields: {withCredentials: true},
                headers: {}
            };

            const accessToken = getAccessToken();
            if (accessToken) {
                settings.headers.Authorization = 'Bearer ' + accessToken;
            }

            if (options.data !== undefined) {
                if (options.json) {
                    settings.contentType = 'application/json; charset=UTF-8';
                    settings.data = JSON.stringify(options.data);
                } else {
                    settings.data = options.data;
                }
            }

            $.ajax(settings)
                .done(function (response) {
                    resolve(response);
                })
                .fail(function (xhr) {
                    const canRefresh = xhr.status === 401
                        && !hasRetried
                        && !options.skipRefresh;

                    if (!canRefresh) {
                        reject(xhr);
                        return;
                    }

                    refreshAccessToken()
                        .then(function () {
                            return apiRequest(options, true);
                        })
                        .then(resolve)
                        .catch(function () {
                            clearSession();
                            render();
                            reject(xhr);
                        });
                });
        });
    }

    // ------------------------------------------------------------------
    // Motus : polling et rendu de la partie collective
    // ------------------------------------------------------------------

    function motusSetLobbyMessage(message) {
        $('#motusLobbyMessage').text(message || '');
    }

    function motusSchedule(delay) {
        clearTimeout(motusTimer);
        if (!motusActive) {
            return;
        }
        motusTimer = setTimeout(motusPoll, delay);
    }

    function motusPoll() {
        if (!motusActive || !currentUser) {
            return;
        }

        if (motusGame) {
            loadMotusGame(motusGame.id);
        } else {
            loadMotusLobby();
        }
    }

    function startMotusPolling() {
        if (motusActive) {
            return;
        }

        motusActive = true;
        $('#motusGame').prop('hidden', true);
        $('#motusLobby').prop('hidden', false);
        loadMotusLobby();
    }

    function motusSetPresence(label, variant) {
        $('#motusPresence')
            .text(label)
            .removeClass('status-lobby status-live status-over')
            .addClass('status-' + (variant || 'lobby'));
    }

    function motusUpdateTimerUI() {
        const total = Math.max(1, Number(motusSecondsTotal) || 30);
        const ratio = Math.max(0, Math.min(1, motusSecondsLeft / total));

        $('#motusTimer').text(motusSecondsLeft + ' s');
        $('#motusTimerBar').css('width', (ratio * 100) + '%');
        $('#motusTimerWrap').toggleClass('is-low', motusSecondsLeft <= 3);
    }

    function motusStopClock() {
        clearInterval(motusClock);
        motusClock = null;
        $('#motusTimerWrap').prop('hidden', true).removeClass('is-low');
    }

    function motusStartClock() {
        motusStopClock();
        $('#motusTimerWrap').prop('hidden', false);
        motusUpdateTimerUI();

        motusClock = setInterval(function () {
            if (motusSecondsLeft > 0) {
                motusSecondsLeft--;
                motusUpdateTimerUI();
            }
        }, 1000);
    }

    function motusCalloutSeenAlready(gameId) {
        try {
            return sessionStorage.getItem('motusCalloutSeen:' + gameId) === '1';
        } catch (error) {
            return false;
        }
    }

    function motusMarkCalloutSeen(gameId) {
        try {
            sessionStorage.setItem('motusCalloutSeen:' + gameId, '1');
        } catch (error) {
            // Stockage indisponible : le bandeau restera simplement affiché.
        }
        motusCalloutGameId = gameId;
    }

    /**
     * Affiche le bandeau « vous avez été défié » au joueur 2, avec un bouton
     * explicite pour rejoindre la partie. La grille reste visible en dessous :
     * la synchronisation AJAX demandée par le sujet n'est pas bloquée.
     */
    function motusUpdateCallout(game) {
        const myId = Number(currentUser.id);
        const isPlayer2 = Number(game.player2_id) === myId;
        const $callout = $('#motusCallout');

        if (game.status !== 'active' || !isPlayer2 || motusCalloutSeenAlready(game.id)) {
            $callout.prop('hidden', true);
            return;
        }

        $('#motusCalloutTitle').text((game.player1_pseudo || 'Un joueur') + ' vous a défié !');

        const isMyTurn = Number(game.current_turn_user_id) === myId;
        const hasStarted = Number(game.attempts_count) > 0;

        if (isMyTurn) {
            $('#motusCalloutText').text(
                'C\'est votre tour : cliquez sur « Rejoindre la partie » pour proposer un mot.'
            );
        } else if (hasStarted) {
            $('#motusCalloutText').text(
                'La partie est en cours. Cliquez sur « Rejoindre la partie » pour suivre la grille.'
            );
        } else {
            $('#motusCalloutText').text(
                'Vous êtes le joueur 2. Cliquez sur « Rejoindre la partie » pour afficher votre grille.'
            );
        }

        $callout.prop('hidden', false);
    }

    function stopMotusPolling() {
        motusActive = false;
        motusGame = null;
        clearTimeout(motusTimer);
        motusTimer = null;
        motusStopClock();
        $('#motusGame').prop('hidden', true);
        $('#motusLobby').prop('hidden', false);
        $('#motusCallout').prop('hidden', true);
        $('#attemptsBoard').empty();
        $('#motusGameMessage').text('');
        $('#motusWordReveal').prop('hidden', true).text('');
    }

    function loadMotusLobby() {
        if (!motusActive) {
            return;
        }

        motusSetPresence('Salon', 'lobby');

        apiRequest({url: '/game/current'})
            .then(function (response) {
                if (response.data) {
                    showMotusGame(response.data);
                    return;
                }

                return apiRequest({url: '/user/available'})
                    .then(function (available) {
                        if (motusGame) {
                            return;
                        }
                        renderAvailablePlayers(available.data || []);
                        motusSchedule(MOTUS_LOBBY_POLL_MS);
                    });
            })
            .catch(function (xhr) {
                if (xhr && xhr.status === 401) {
                    return;
                }

                motusSetLobbyMessage(apiErrorMessage(xhr, 'Salon momentanément indisponible.'));
                motusSchedule(MOTUS_LOBBY_POLL_MS);
            });
    }

    function loadMotusGame(gameId) {
        if (!motusActive) {
            return;
        }

        apiRequest({url: '/game/' + gameId})
            .then(function (response) {
                showMotusGame(response.data);
            })
            .catch(function (xhr) {
                if (xhr && xhr.status === 401) {
                    return;
                }

                if (xhr && (xhr.status === 403 || xhr.status === 404)) {
                    showMotusLobby();
                    motusSetLobbyMessage('Cette partie n\'est plus accessible.');
                    return;
                }

                motusSchedule(MOTUS_GAME_POLL_MS);
            });
    }

    function showMotusLobby() {
        motusGame = null;
        clearTimeout(motusTimer);
        motusTimer = null;
        motusStopClock();
        $('#motusGame').prop('hidden', true);
        $('#motusLobby').prop('hidden', false);
        $('#motusCallout').prop('hidden', true);

        if (motusActive) {
            loadMotusLobby();
        }
    }

    function renderAvailablePlayers(players) {
        const $list = $('#availablePlayers').empty();
        $('#availableCount').text(String(players.length));

        if (!players.length) {
            const $empty = $('<div class="empty-state"></div>');
            $empty.append($('<span class="empty-icon" aria-hidden="true"></span>').text('👥'));
            $empty.append(
                $('<span></span>').text(
                    'Aucun joueur disponible pour le moment. Connectez un second compte '
                    + '(autre navigateur ou fenêtre privée) pour lancer une partie.'
                )
            );
            $list.append($empty);
            return;
        }

        players.forEach(function (player) {
            const pseudo = player.pseudo || 'Joueur';
            const $tile = $('<div class="player-tile" role="listitem"></div>');

            $tile.append(
                $('<span class="avatar" aria-hidden="true"></span>')
                    .text(pseudo.charAt(0).toUpperCase())
            );

            const $info = $('<div class="player-info"></div>');
            $info.append($('<strong></strong>').text(pseudo));
            $info.append(
                $('<span class="player-status"></span>')
                    .append($('<i class="dot online" aria-hidden="true"></i>'))
                    .append('En ligne')
            );
            $tile.append($info);

            const $button = $('<button type="button" class="btn-primary btn-sm"></button>')
                .text('Défier');
            $button.on('click', function () {
                startMotusGame(player, $button);
            });

            $tile.append($button);
            $list.append($tile);
        });
    }

    function startMotusGame(player, $button) {
        if (motusStarting) {
            return;
        }

        motusStarting = true;
        $button.prop('disabled', true).attr('aria-busy', 'true');

        apiRequest({
            url: '/game/start',
            method: 'POST',
            json: true,
            data: {player2_id: player.id}
        })
            .then(function (response) {
                motusSetLobbyMessage('');
                showMotusGame(response.data);
                showAlert('Partie lancée contre ' + (player.pseudo || 'cet adversaire') + '.');
            })
            .catch(function (xhr) {
                motusSetLobbyMessage(apiErrorMessage(xhr, 'Impossible de lancer la partie.'));
                loadMotusLobby();
            })
            .finally(function () {
                motusStarting = false;
                $button.prop('disabled', false).removeAttr('aria-busy');
            });
    }

    function showMotusGame(game) {
        if (!motusActive || !game) {
            return;
        }

        motusGame = game;
        $('#motusLobby').prop('hidden', true);
        $('#motusGame').prop('hidden', false);
        renderMotusGame(game);
        motusUpdateCallout(game);

        if (game.status === 'active') {
            motusSchedule(MOTUS_GAME_POLL_MS);
            motusStartClock();
        } else {
            clearTimeout(motusTimer);
            motusTimer = null;
            motusStopClock();
        }
    }

    function renderMotusGame(game) {
        const myId = Number(currentUser.id);
        const player1Id = Number(game.player1_id);
        const player2Id = Number(game.player2_id);
        const isPlayer1 = player1Id === myId;
        const myPseudo = (isPlayer1 ? game.player1_pseudo : game.player2_pseudo) || currentUser.pseudo || 'Vous';
        const otherPseudo = (isPlayer1 ? game.player2_pseudo : game.player1_pseudo) || 'Adversaire';
        const canPlay = game.status === 'active' && Number(game.current_turn_user_id) === myId;
        const currentTurnId = Number(game.current_turn_user_id);

        const players = [
            {id: player1Id, pseudo: game.player1_pseudo || 'Joueur 1'},
            {id: player2Id, pseudo: game.player2_pseudo || 'Joueur 2'}
        ];

        players.forEach(function (player, index) {
            const number = index + 1;
            const pseudo = String(player.pseudo);

            $('#motusPlayer' + number).text(pseudo);
            $('#motusAvatar' + number).text(pseudo.charAt(0).toUpperCase());
            $('#motusTag' + number).prop('hidden', player.id !== myId);
            $('#motusPlayerCard' + number)
                .toggleClass('is-current', game.status === 'active' && player.id === currentTurnId)
                .toggleClass('is-you', player.id === myId);
        });

        let turnText = '';
        let turnHint = '';
        let bannerClass = '';

        if (game.status === 'active') {
            bannerClass = canPlay ? 'is-your-turn' : '';
            turnText = canPlay
                ? 'À vous de jouer, ' + myPseudo + ' !'
                : 'Au tour de ' + otherPseudo + '…';
            turnHint = canPlay
                ? 'Proposez un mot de 6 lettres avant la fin du chrono.'
                : 'La grille se met à jour automatiquement dès que votre adversaire a joué.';
        } else if (game.status === 'won') {
            bannerClass = 'is-finished';
            turnText = Number(game.winner_id) === myId
                ? 'Vous avez gagné !'
                : otherPseudo + ' a gagné.';
            turnHint = 'Le mot cible est révélé ci-dessous.';
        } else if (game.status === 'draw') {
            bannerClass = 'is-finished';
            turnText = 'Match nul';
            turnHint = 'Les 8 propositions ont été utilisées.';
        } else if (game.status === 'cancelled') {
            bannerClass = 'is-finished';
            turnText = 'Partie abandonnée';
            turnHint = 'Retournez au salon pour défier un autre joueur.';
        }

        $('#motusTurn').text(turnText);
        $('#motusTurnHint').text(turnHint);
        $('#motusTurnBanner')
            .removeClass('is-your-turn is-finished')
            .addClass(bannerClass);

        motusSecondsTotal = Number(game.turn_seconds || 30);
        motusSecondsLeft = game.status === 'active' ? Number(game.seconds_left || 0) : 0;
        motusUpdateTimerUI();
        $('#motusTimerWrap').prop('hidden', game.status !== 'active');

        $('#motusCounter').text(game.attempts_count + ' / ' + game.max_attempts);
        motusSetPresence(
            game.status === 'active' ? 'Partie en cours' : 'Partie terminée',
            game.status === 'active' ? 'live' : 'over'
        );

        if (game.status !== 'active' && game.target_word) {
            $('#motusWordReveal')
                .text('Mot : ' + String(game.target_word).toUpperCase())
                .prop('hidden', false);
        } else {
            $('#motusWordReveal').prop('hidden', true).text('');
        }

        const $board = $('#attemptsBoard').empty();
        (game.attempts || []).forEach(function (attempt, rowIndex) {
            const $row = $('<div class="motus-row"></div>');
            $row.append($('<span class="attempt-number"></span>').text(rowIndex + 1));
            $row.append($('<span class="motus-author"></span>').text(attempt.pseudo || 'Joueur'));

            const $letters = $('<div class="motus-letters"></div>');
            (attempt.result || []).forEach(function (color, index) {
                $letters.append(
                    $('<span class="motus-cell"></span>')
                        .addClass(color)
                        .text(String(attempt.word || '').charAt(index).toUpperCase())
                );
            });
            $row.append($letters);
            $board.append($row);
        });

        if (!game.attempts || !game.attempts.length) {
            const $empty = $('<div class="board-empty"></div>');
            $empty.append($('<span aria-hidden="true"></span>').text('✍️'));
            $empty.append(
                $('<span></span>').text(
                    'Aucune proposition pour le moment. Le premier mot lance la partie.'
                )
            );
            $board.append($empty);
        }

        $('#attemptWord').prop('disabled', !canPlay).val(canPlay ? $('#attemptWord').val() : '');
        $('#attemptForm').prop('hidden', game.status !== 'active');
        $('#attemptForm button[type="submit"]').prop('disabled', !canPlay);
        $('#leaveGameButton').prop('hidden', game.status !== 'active');
        $('#backToLobbyButton').prop('hidden', game.status === 'active');
    }

    function submitMotusAttempt() {
        if (!motusGame || !currentUser) {
            return;
        }

        const word = String($('#attemptWord').val() || '').trim().toLowerCase();

        if (!/^[a-z]{6}$/.test(word)) {
            $('#motusGameMessage').text('Le mot doit contenir exactement 6 lettres, sans accent ni espace.');
            return;
        }

        const $button = $('#attemptForm button[type="submit"]');
        $button.prop('disabled', true).attr('aria-busy', 'true');
        $('#motusGameMessage').text('Envoi de la proposition…');

        apiRequest({
            url: '/game/' + motusGame.id + '/attempt',
            method: 'POST',
            json: true,
            data: {word: word}
        })
            .then(function (response) {
                const game = response.data.game;
                $('#attemptWord').val('');
                $('#motusGameMessage').text(
                    game.status === 'won'
                        ? 'Mot trouvé, la partie est terminée.'
                        : (game.status === 'draw'
                            ? 'Les 8 propositions ont été utilisées.'
                            : 'Proposition envoyée.')
                );
                showMotusGame(game);

                if (game.status === 'won') {
                    showAlert(
                        Number(game.winner_id) === Number(currentUser.id)
                            ? 'Bravo, mot trouvé !'
                            : 'Mot trouvé par l\'adversaire.'
                    );
                } else if (game.status === 'draw') {
                    showAlert('Match nul : 8 propositions atteintes.');
                }
            })
            .catch(function (xhr) {
                $('#motusGameMessage').text(apiErrorMessage(xhr, 'Proposition refusée.'));
                if (xhr && xhr.status === 409) {
                    loadMotusGame(motusGame.id);
                }
            })
            .finally(function () {
                $button.prop('disabled', false).removeAttr('aria-busy');
                if (motusGame) {
                    renderMotusGame(motusGame);
                }
            });
    }

    function leaveMotusGame() {
        if (!motusGame) {
            return;
        }

        if (!window.confirm('Quitter cette partie ? Elle sera abandonnée pour les deux joueurs.')) {
            return;
        }

        const gameId = motusGame.id;
        const $button = $('#leaveGameButton');
        $button.prop('disabled', true).attr('aria-busy', 'true');

        apiRequest({
            url: '/game/' + gameId + '/cancel',
            method: 'POST'
        })
            .then(function () {
                showAlert('Partie abandonnée.');
                showMotusLobby();
            })
            .catch(function (xhr) {
                $('#motusGameMessage').text(apiErrorMessage(xhr, 'Impossible de quitter la partie.'));
            })
            .finally(function () {
                $button.prop('disabled', false).removeAttr('aria-busy');
            });
    }

    // ------------------------------------------------------------------
    // Restauration de la session au chargement de la page
    // ------------------------------------------------------------------

    function restoreSession() {
        const accessToken = getAccessToken();
        const user = getStoredUser();

        if (!accessToken) {
            clearSession();
            render();
            return;
        }

        currentUser = user;
        render();

        // Cas particulier : token présent mais données utilisateur absentes.
        if (!user) {
            refreshAccessToken()
                .then(function () {
                    render();
                })
                .catch(function () {
                    clearSession();
                    render();
                    showAlert('Session expirée, veuillez vous reconnecter.', 'error');
                });
            return;
        }

        // Vérifie que l'Access Token est toujours valide. En cas d'expiration,
        // apiRequest tente automatiquement un rafraîchissement.
        apiRequest({url: '/user/' + user.id})
            .then(function (response) {
                storeSession(getAccessToken(), response.data);
                render();
            })
            .catch(function () {
                clearSession();
                render();
                showAlert('Session expirée, veuillez vous reconnecter.', 'error');
            });
    }

    // ------------------------------------------------------------------
    // Événements
    // ------------------------------------------------------------------

    function bindEvents() {
        $('#showRegisterLink').on('click', function (event) {
            event.preventDefault();
            showRegisterCard();
        });

        $('#showLoginLink').on('click', function (event) {
            event.preventDefault();
            showLoginCard();
        });

        // 3.2 — Authentification
        $('#loginForm').on('submit', function (event) {
            event.preventDefault();

            const form = this;
            const $button = $(form).find('button[type="submit"]');

            $button.prop('disabled', true).attr('aria-busy', 'true');

            apiRequest({
                url: '/login',
                method: 'POST',
                data: {
                    email: $('#loginEmail').val().trim(),
                    password: $('#loginPassword').val()
                },
                skipRefresh: true
            })
                .then(function (response) {
                    storeSession(response.data.accessToken, response.data.user);
                    form.reset();
                    render();
                    showAlert('Connexion réussie. Bienvenue ' + response.data.user.pseudo + ' !');
                })
                .catch(function (xhr) {
                    showAlert(apiErrorMessage(xhr, 'Connexion impossible.'), 'error');
                })
                .finally(function () {
                    $button.prop('disabled', false).removeAttr('aria-busy');
                });
        });

        // 3.4 — Création d'un compte
        $('#registerForm').on('submit', function (event) {
            event.preventDefault();

            const form = this;
            const $button = $(form).find('button[type="submit"]');
            const data = {};

            $(form).serializeArray().forEach(function (field) {
                data[field.name] = field.value;
            });

            $button.prop('disabled', true).attr('aria-busy', 'true');

            apiRequest({
                url: '/user',
                method: 'POST',
                data: data,
                skipRefresh: true
            })
                .then(function (response) {
                    form.reset();
                    $('#loginEmail').val(data.email);
                    showLoginCard();
                    showAlert('Compte créé avec succès (' + response.data.pseudo + '). Vous pouvez vous connecter.');
                })
                .catch(function (xhr) {
                    showAlert(apiErrorMessage(xhr, 'Création du compte impossible.'), 'error');
                })
                .finally(function () {
                    $button.prop('disabled', false).removeAttr('aria-busy');
                });
        });

        // 3.3 — Désauthentification
        $('#logoutForm').on('submit', function (event) {
            event.preventDefault();

            apiRequest({url: '/logout', method: 'POST'})
                .then(function () {
                    clearSession();
                    render();
                    showAlert('Vous êtes déconnecté.');
                })
                .catch(function () {
                    // Même si le serveur ne répond pas, on supprime la session locale.
                    clearSession();
                    render();
                    showAlert('Session locale supprimée.', 'error');
                });
        });

        // 3.5 — Modification du compte (pseudo et genre uniquement)
        $('#updateForm').on('submit', function (event) {
            event.preventDefault();

            if (!currentUser) {
                return;
            }

            const $button = $(this).find('button[type="submit"]');
            $button.prop('disabled', true).attr('aria-busy', 'true');

            apiRequest({
                url: '/user/' + currentUser.id,
                method: 'PATCH',
                json: true,
                data: {
                    pseudo: $('#updatePseudo').val().trim(),
                    gender: $('#updateGender').val()
                }
            })
                .then(function (response) {
                    storeSession(getAccessToken(), response.data);
                    render();
                    showAlert('Compte mis à jour avec succès.');
                })
                .catch(function (xhr) {
                    showAlert(apiErrorMessage(xhr, 'Mise à jour impossible.'), 'error');
                })
                .finally(function () {
                    $button.prop('disabled', false).removeAttr('aria-busy');
                });
        });

        // 3.6 — Suppression du compte
        $('#deleteForm').on('submit', function (event) {
            event.preventDefault();

            if (!currentUser) {
                return;
            }

            const confirmed = window.confirm(
                'Supprimer définitivement votre compte ? Cette action est irréversible.'
            );

            if (!confirmed) {
                return;
            }

            const $button = $(this).find('button[type="submit"]');
            $button.prop('disabled', true).attr('aria-busy', 'true');

            apiRequest({
                url: '/user/' + currentUser.id,
                method: 'DELETE'
            })
                .then(function () {
                    clearSession();
                    render();
                    showAlert('Votre compte a été supprimé. À bientôt !');
                })
                .catch(function (xhr) {
                    showAlert(apiErrorMessage(xhr, 'Suppression impossible.'), 'error');
                })
                .finally(function () {
                    $button.prop('disabled', false).removeAttr('aria-busy');
                });
        });

        // Motus — soumission d'une proposition
        $('#attemptForm').on('submit', function (event) {
            event.preventDefault();
            submitMotusAttempt();
        });

        // Motus — forcer les lettres en majuscules dans le champ
        $('#attemptWord').on('input', function () {
            this.value = this.value.replace(/[^a-zA-Z]/g, '').slice(0, 6).toUpperCase();
        });

        // Motus — abandon et retour au salon
        $('#leaveGameButton').on('click', leaveMotusGame);
        $('#backToLobbyButton').on('click', function () {
            showMotusLobby();
        });

        // Motus — rafraîchissement manuel du salon
        $('#refreshPlayersButton').on('click', function () {
            motusSetLobbyMessage('');
            loadMotusLobby();
        });

        // Motus — invitation explicite du joueur 2
        $('#motusCalloutButton').on('click', function () {
            if (motusGame) {
                motusMarkCalloutSeen(motusGame.id);
            }
            $('#motusCallout').prop('hidden', true);

            const canPlay = motusGame
                && motusGame.status === 'active'
                && Number(motusGame.current_turn_user_id) === Number(currentUser.id);

            if (canPlay) {
                $('#attemptWord').trigger('focus');
            }
        });

        $('#declineGameButton').on('click', leaveMotusGame);
    }

    $(function () {
        bindEvents();
        restoreSession();
    });
</script>
</body>
</html>
