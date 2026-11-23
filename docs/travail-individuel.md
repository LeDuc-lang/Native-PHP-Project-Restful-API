# Travail préalable — Partie individuelle

> Développement Web — Technos legacy — 2025-2026  
> Implémentation d'une API REST en PHP natif et d'un client jQuery.

Ce document présente le travail individuel demandé au point **3** du sujet :

1. l'exploration et le déploiement du projet ;
2. la présentation de la méthode AJAX ;
3. le mécanisme d'authentification (Access Token / Refresh Token) ;
4. les détails d'implémentation côté front-end et côté back-end ;
5. les scénarios de test.

---

## 1. Exploration et déploiement

### 1.1 Arborescence utile

```text
├── public/
│   ├── index.php              Point d'entrée (charge .env et le contrôleur frontal)
│   ├── router.php             Routeur du serveur PHP (URL /api/... -> index.php)
│   ├── front_controller.php   Routage HTTP + vérification du JWT
│   ├── client.php             Interface jQuery (travail individuel)
│   └── assets/                Pico CSS + jQuery 3.7.1
├── src/
│   ├── Controller/UserController.php
│   ├── Model/User.php
│   ├── Core/Database.php, DotEnv.php
│   └── Security/JwtService.php
├── helpers/
│   ├── global_helper.php
│   ├── http_response_helper.php
│   └── jwt_helper.php
├── docs/                      Ce document + schéma d'authentification
└── stuff/native_php_restful_api.sql
```

### 1.2 Démarrage

**Avec Docker (recommandé) :**

```bash
docker compose up --build
```

L'application est disponible sur <http://localhost:8080/client.php> (port défini par `APP_PORT` dans `.env`).
Si Docker tourne sur un serveur distant (VPS / context Docker distant), remplacer `localhost` par l'IP du
serveur : `http://<IP_DU_SERVEUR>:8080/client.php`.
La base MySQL est initialisée automatiquement à partir de `stuff/native_php_restful_api.sql`.

**Avec PHP natif :**

```bash
php -S localhost:8000 -t public public/router.php
```

> Le fichier `.env` fourni contient `DB_HOST=db` (nom du service Docker). En PHP natif, remplacer par
> `DB_HOST=localhost` et renseigner les identifiants MySQL locaux.

**Compte de démonstration :** `lucas.morel@example.com` / `password`.

### 1.3 Vérification rapide

```bash
curl -i http://localhost:8080/api/user
# HTTP/1.1 401 Unauthorized -> la route est protégée par JWT
```

---

## 2. La méthode AJAX

### 2.1 Définition

**AJAX** signifie *Asynchronous JavaScript and XML*. Ce n'est pas un langage ni une bibliothèque, mais une
**technique** qui consiste à envoyer une requête HTTP en arrière-plan avec JavaScript, puis à mettre à jour
une partie de la page avec la réponse, **sans rechargement complet** de celle-ci.

Le navigateur utilise l'objet `XMLHttpRequest` (ou l'API moderne `fetch`). jQuery fournit une abstraction
pratique : `$.ajax()`, `$.get()`, `$.post()`, etc.

### 2.2 Principe

```text
Utilisateur                Navigateur (JavaScript)                 Serveur
    |                            |                                   |
    |-- action (clic) ---------->|                                   |
    |                            |-- requête HTTP (AJAX) ----------->|
    |                            |                                   |-- traitement
    |                            |<-- réponse JSON ------------------|
    |                            |-- mise à jour du DOM              |
    |<-- affichage immédiat -----|                                   |
```

Caractéristiques :

- **asynchrone** : la page reste utilisable pendant la requête ;
- **partielle** : seule une zone du DOM est mise à jour ;
- **légère** : on ne transfère que les données utiles (JSON), pas toute la page HTML ;
- **découplée** : le serveur expose une API, le client peut être remplacé.

Aujourd'hui, le format d'échange est presque toujours **JSON**, même si le nom AJAX fait référence à XML.

### 2.3 Exemple jQuery utilisé dans le projet

```javascript
$.ajax({
    url: '/api/login',
    method: 'POST',
    data: {email: email, password: password}, // encodé en application/x-www-form-urlencoded
    dataType: 'json'
})
    .done(function (response) {
        // Cas de réussite : la réponse HTTP est 2xx
        console.log(response.data.accessToken);
    })
    .fail(function (xhr) {
        // Cas d'échec : 4xx, 5xx, erreur réseau...
        console.error(xhr.status, xhr.responseJSON.message);
    });
```

Le projet encapsule `$.ajax` dans la fonction `apiRequest()` (voir § 4.3) afin de centraliser :

- l'ajout de l'en-tête `Authorization: Bearer <accessToken>` ;
- l'envoi du cookie de rafraîchissement (`withCredentials`) ;
- le rafraîchissement automatique du token en cas de réponse `401`.

### 2.4 Avantages / limites

| Avantages | Limites |
|---|---|
| Meilleure réactivité perçue | Complexité JavaScript supplémentaire |
| Moins de bande passante | Gestion manuelle des états d'erreur |
| Séparation front / back | SEO plus délicat si tout est chargé en JS |
| API réutilisable par d'autres clients | Nécessite une API bien structurée |

---

## 3. Mécanisme d'authentification

### 3.1 Les deux jetons

| | **Access Token** | **Refresh Token** |
|---|---|---|
| Nature | JWT signé en HMAC-SHA256 | Chaîne aléatoire de 64 octets (128 caractères hexadécimaux) |
| Durée de vie | Courte : `JWT_TTL` = 300 s (5 min) | Longue : `JWT_REFRESH_TTL` = 604 800 s (7 jours) |
| Stockage client | `localStorage` | Cookie `HttpOnly`, `SameSite=Lax` |
| Transmission | En-tête `Authorization: Bearer ...` | Cookie envoyé automatiquement par le navigateur |
| Vérification serveur | Signature + date d'expiration (`exp`) | Hash bcrypt en base + `revoked = 0` + `expires_at > NOW()` |
| Révocable | Non (mais durée très courte) | Oui (colonne `revoked`) |
| Rôle | Prouver l'identité sur chaque requête | Obtenir un nouvel Access Token sans redemander le mot de passe |

Le Refresh Token n'est **jamais** stocké en clair en base : seul son hash bcrypt est conservé. Le cookie
`HttpOnly` empêche le JavaScript de le lire (protection contre le vol via XSS).

### 3.2 Schéma explicatif

Le schéma ci-dessous est également disponible au format image : [`schema-authentification.svg`](./schema-authentification.svg).

```mermaid
sequenceDiagram
    autonumber
    actor U as Utilisateur
    participant C as Client jQuery<br/>(navigateur)
    participant A as API PHP<br/>(front_controller)
    participant D as MySQL

    U->>C: Saisit email + mot de passe
    C->>A: POST /api/login
    A->>D: SELECT * FROM user WHERE email = ...
    D-->>A: Utilisateur + hash du mot de passe
    A->>A: password_verify()
    A->>A: Crée l'Access Token JWT<br/>(exp = maintenant + 5 min)
    A->>D: INSERT refresh_token<br/>(hash bcrypt, expires_at = maintenant + 7 j)
    A-->>C: 200 { accessToken, user }<br/>Set-Cookie: refresh_token (HttpOnly)
    C->>C: localStorage.setItem('accessToken', ...)
    C->>A: GET /api/user/1<br/>Authorization: Bearer &lt;accessToken&gt;
    A->>A: Vérifie signature + expiration
    A-->>C: 200 { data }

    Note over C,A: 5 minutes plus tard : l'Access Token est expiré
    C->>A: GET /api/user/1 (ancien token)
    A-->>C: 401 Unauthorized
    C->>A: POST /api/token/refresh<br/>(cookie HttpOnly envoyé automatiquement)
    A->>D: SELECT ... WHERE revoked = 0<br/>AND expires_at > NOW()
    D-->>A: Refresh Token valide
    A-->>C: 200 { accessToken, user }
    C->>C: Remplace l'Access Token dans localStorage
    C->>A: Rejoue GET /api/user/1<br/>Authorization: Bearer &lt;nouveau token&gt;
    A-->>C: 200 { data }
```

### 3.3 Déroulement détaillé

1. **Connexion** : le client envoie `email` et `password` à `POST /api/login`.
2. **Vérification** : le serveur recherche l'utilisateur par email et compare le mot de passe avec
   `password_verify()` (hash bcrypt généré à l'inscription).
3. **Création de l'Access Token** : `JwtService::createToken()` construit un JWT
   `header.payload.signature` avec `user_id`, `email`, `iat` et `exp`.
4. **Création du Refresh Token** : une valeur aléatoire est générée, hashée avec `password_hash()`, puis
   enregistrée dans la table `refresh_token` avec sa date d'expiration.
5. **Réponse** : l'Access Token est renvoyé dans le JSON ; le Refresh Token part dans un cookie
   `HttpOnly` (inaccessible à JavaScript).
6. **Requêtes protégées** : le client ajoute `Authorization: Bearer <accessToken>`. Le contrôleur frontal
   vérifie la signature et l'expiration. En cas de succès, `user_id` est transmis au contrôleur.
7. **Expiration** : après 5 minutes, l'API répond `401`.
8. **Rafraîchissement** : le client appelle `POST /api/token/refresh`. Le navigateur joint automatiquement
   le cookie. Le serveur vérifie que le token existe, n'est pas révoqué et n'est pas expiré, puis émet un
   nouvel Access Token.
9. **Reprise** : le client rejoue automatiquement la requête initiale, une seule fois.
10. **Déconnexion** : `POST /api/logout` révoque le Refresh Token en base (`revoked = 1`) et supprime le
    cookie. Le client supprime l'Access Token du `localStorage`.

### 3.4 Pourquoi deux jetons ?

- Un Access Token **court** limite les dégâts s'il est volé : il expire vite.
- Un Refresh Token **long** évite à l'utilisateur de ressaisir son mot de passe toutes les 5 minutes.
- Le Refresh Token étant révocable, une déconnexion peut réellement mettre fin à la session.
- Le Refresh Token étant dans un cookie `HttpOnly`, il est moins exposé au XSS que le `localStorage`.

---

## 4. Implémentation côté front-end

Fichier concerné : `public/client.php`.

### 4.1 Deux états d'interface

| État | Zone affichée | Contenu |
|---|---|---|
| Non authentifié | `#guestArea` | Formulaire de connexion **ou** formulaire de création de compte |
| Authentifié | `#accountArea` | Profil, bouton de déconnexion, formulaire de modification, bouton de suppression |

Le passage d'un état à l'autre est centralisé dans `render()` : les formulaires de connexion et de
déconnexion ne sont jamais visibles en même temps.

### 4.2 Fonctions principales

| Fonction | Rôle |
|---|---|
| `apiRequest(options)` | Enveloppe `$.ajax`, ajoute le JWT et gère le `401` |
| `refreshAccessToken()` | Appelle `POST /api/token/refresh` (une seule requête à la fois) |
| `restoreSession()` | Au chargement, vérifie le token stocké et restaure le profil |
| `storeSession()` / `clearSession()` | Écriture / suppression dans `localStorage` |
| `render()` | Bascule entre les zones « visiteur » et « connecté » |
| `showAlert()` | Affiche un message de succès ou d'erreur |

### 4.3 Rafraîchissement automatique

Le cœur du mécanisme est la fonction `apiRequest()` :

```javascript
function apiRequest(options, hasRetried) {
    return new Promise(function (resolve, reject) {
        // ... construction de la requête avec le Bearer token ...

        $.ajax(settings)
            .done(resolve)
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
                        return apiRequest(options, true); // une seule reprise
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
```

Points importants :

- le paramètre `hasRetried` empêche une boucle infinie ;
- `refreshRequest` (dans `refreshAccessToken`) sert de **verrou** : si plusieurs requêtes échouent en même
  temps, un seul appel à `/api/token/refresh` est effectué ;
- `skipRefresh: true` est utilisé pour `/api/login` et l'inscription, afin de ne pas tenter de rafraîchir
  un token inexistant ;
- en cas d'échec du rafraîchissement, la session locale est supprimée et l'utilisateur revient au
  formulaire de connexion.

### 4.4 Détail des formulaires

- **Connexion** (`#loginForm`) : `POST /api/login`, données encodées en formulaire (`$_POST` côté PHP).
- **Création** (`#registerForm`) : `POST /api/user`, tous les champs requis, puis retour au formulaire de
  connexion avec l'email prérempli.
- **Déconnexion** (`#logoutForm`) : formulaire ne contenant qu'un bouton, `POST /api/logout`.
- **Modification** (`#updateForm`) : `PATCH /api/user/{id}` en JSON, avec **uniquement** `pseudo`,
  `gender` et `avatar`.
- **Suppression** (`#deleteForm`) : `DELETE /api/user/{id}`, avec confirmation `window.confirm()`.

---

## 5. Implémentation côté back-end

### 5.1 Routage

`public/front_controller.php` analyse l'URL et la méthode HTTP :

| Méthode | URL | Authentification | Action |
|---|---|---|---|
| `POST` | `/api/login` | Non | `authenticate()` |
| `POST` | `/api/token/refresh` | Cookie refresh | `reauthenticate()` |
| `POST` | `/api/user` | Non | `create()` (inscription) |
| `GET` | `/api/user` | Oui | `list()` |
| `GET` | `/api/user/{id}` | Oui | `get()` |
| `PATCH` | `/api/user/{id}` | Oui + propriétaire | `update()` |
| `DELETE` | `/api/user/{id}` | Oui + propriétaire | `delete()` |
| `POST` | `/api/logout` | Oui | `deauthenticate()` |

### 5.2 Vérification du JWT

`helpers/jwt_helper.php` récupère l'en-tête `Authorization` de manière robuste (PHP natif, Apache, etc.),
puis `JwtService::verifyToken()` :

1. découpe le token en trois parties ;
2. recalcule la signature HMAC-SHA256 et la compare avec `hash_equals()` ;
3. décode le payload ;
4. vérifie que `exp` est supérieur à l'heure actuelle.

Si le token est absent, mal formé, falsifié ou expiré, l'API renvoie `401`.

### 5.3 Propriété du compte (points 3.5 et 3.6)

Le contrôleur frontal transmet l'identifiant issu du JWT au contrôleur :

```php
$authenticatedUserId = (int)$payload['user_id'];

$userController->update($userId, $authenticatedUserId);
$userController->delete($userId, $authenticatedUserId);
```

Dans `UserController`, une vérification empêche toute action sur le compte d'un autre utilisateur :

```php
if ($authenticatedUserId !== null && $id !== (int)$authenticatedUserId) {
    sendResponseCustom('You are not allowed to update this account', null, 'Error', 403);
    return;
}
```

Ainsi, même un utilisateur authentifié qui modifierait l'URL (`/api/user/1` au lieu de `/api/user/6`)
recevrait une réponse **403 Forbidden**.

### 5.4 Rafraîchissement (point 3.7)

`UserController::reauthenticate()` :

1. lit le cookie `refresh_token` ;
2. cherche un token correspondant avec `User::findByToken()` ;
3. la requête SQL filtre `revoked = 0` **et** `expires_at > NOW()` ;
4. compare la valeur reçue avec le hash bcrypt stocké (`password_verify`) ;
5. émet un nouvel Access Token et renvoie aussi le profil public.

### 5.5 Validation et sécurité

- champs obligatoires vérifiés à l'inscription ;
- email validé avec `filter_var(..., FILTER_VALIDATE_EMAIL)` ;
- genre limité à `m`, `f`, `o` ;
- date de naissance validée avec `checkdate()` ;
- mot de passe de 6 caractères minimum, hashé avec `password_hash()` ;
- pseudo et email uniques (réponse `409` en cas de doublon) ;
- seuls `pseudo`, `gender` et `avatar` sont acceptés en modification ;
- le mot de passe et le token ne sont jamais renvoyés au client (`publicUser()`).

---

## 6. Scénarios de test

### 6.1 Tests de l'API avec curl

```bash
# 1. Création d'un compte (id attendu : 6)
curl -i -X POST http://localhost:8080/api/user \
  -d "first_name=Test" \
  -d "last_name=Utilisateur" \
  -d "pseudo=TestUser" \
  -d "birth_date=2000-01-01" \
  -d "gender=o" \
  -d "avatar=test.jpg" \
  -d "email=test.user@example.com" \
  -d "password=password"

# 2. Connexion + conservation du cookie de rafraîchissement
curl -i -c cookies.txt -X POST http://localhost:8080/api/login \
  -d "email=test.user@example.com" \
  -d "password=password"

# Récupération du token dans la réponse JSON (avec jq)
TOKEN=$(curl -s -c cookies.txt -X POST http://localhost:8080/api/login \
  -d "email=test.user@example.com" -d "password=password" | jq -r '.data.accessToken')

# 3. Requête protégée
curl -i -H "Authorization: Bearer $TOKEN" http://localhost:8080/api/user/6

# 4. Modification de son propre compte -> 200
curl -i -X PATCH http://localhost:8080/api/user/6 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"pseudo":"TestUserUpdated","gender":"m","avatar":"new-avatar.jpg"}'

# 5. Tentative de modification d'un autre compte -> 403
curl -i -X PATCH http://localhost:8080/api/user/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"pseudo":"Hacked"}'

# 6. Rafraîchissement du token grâce au cookie
curl -i -b cookies.txt -c cookies.txt -X POST http://localhost:8080/api/token/refresh

# 7. Déconnexion
curl -i -b cookies.txt -X POST http://localhost:8080/api/logout \
  -H "Authorization: Bearer $TOKEN"

# 8. Suppression de son compte
curl -i -X DELETE http://localhost:8080/api/user/6 \
  -H "Authorization: Bearer $TOKEN"
```

### 6.2 Tester l'expiration et le rafraîchissement automatique

1. Dans `.env`, mettre temporairement `JWT_TTL=10`, puis redémarrer l'application
   (`docker compose up -d --build` ou relancer `php -S`).
2. Se connecter dans l'interface.
3. Attendre une quinzaine de secondes.
4. Cliquer sur « Enregistrer les modifications » (ou recharger la page).
5. Dans l'onglet **Réseau** du navigateur, on observe :
   - la requête initiale en `401` ;
   - un `POST /api/token/refresh` en `200` ;
   - la reprise automatique de la requête initiale en `200`.

L'utilisateur n'a jamais eu à ressaisir son mot de passe.

### 6.3 Tests manuels de l'interface

| # | Action | Résultat attendu |
|---|---|---|
| 1 | Ouvrir `/client.php` | Formulaire de connexion visible, zone compte masquée |
| 2 | Se connecter avec un mauvais mot de passe | Message d'erreur, formulaire toujours visible |
| 3 | Se connecter avec le compte de démo | Profil affiché, formulaire caché, bouton de déconnexion visible |
| 4 | Recharger la page | La session est restaurée automatiquement |
| 5 | Modifier pseudo / genre / avatar | Message de succès, profil mis à jour |
| 6 | Se déconnecter | Retour au formulaire de connexion |
| 7 | Créer un compte avec un pseudo existant | Message « Pseudo or email is already used » |
| 8 | Supprimer son compte | Retour au formulaire de connexion, compte supprimé de la base |

---

## 7. Limites et améliorations possibles

- **Stockage de l'Access Token** : `localStorage` est simple mais vulnérable au XSS. En production, on
  privilégierait un cookie `HttpOnly` + `Secure`, ou un stockage en mémoire.
- **Cookie `Secure`** : la valeur `secure => false` est adaptée au HTTP local ; passer à `true` en HTTPS.
- **Rotation du Refresh Token** : le token actuel n'est pas renouvelé à chaque rafraîchissement. Une
  rotation avec détection de réutilisation renforcerait la sécurité.
- **CSRF** : `SameSite=Lax` protège partiellement ; un jeton CSRF serait préférable pour les routes à
  cookie.
- **Rate limiting** : aucune limitation du nombre de tentatives de connexion.
- **Avatar** : le champ ne stocke qu'un nom de fichier ; il n'y a pas d'upload ni de validation d'image.
- **Pagination** : `GET /api/user` renvoie tous les utilisateurs.

---

## 8. Récapitulatif des fichiers modifiés / ajoutés

| Fichier | Modification |
|---|---|
| `public/client.php` | Interface complète (connexion, inscription, profil, modification, suppression, déconnexion) + AJAX + refresh automatique |
| `public/front_controller.php` | Transmission de l'identifiant authentifié pour contrôler la propriété du compte |
| `public/router.php` | **Ajout** : routeur pour le serveur PHP intégré |
| `public/index.php` | La racine `/` sert aussi le client |
| `src/Controller/UserController.php` | Validation, création, mise à jour, suppression, propriété, réponses de login enrichies |
| `src/Model/User.php` | `findById()` retourne une ligne, refresh token expiré ignoré, `create()` retourne l'id |
| `helpers/jwt_helper.php` | Lecture robuste de l'en-tête `Authorization` |
| `Dockerfile` | Utilisation de `router.php` |
| `README.md` | Endpoints et procédure de déploiement corrigés |
| `docs/travail-individuel.md` | **Ajout** : ce document |
| `docs/schema-authentification.svg` | **Ajout** : schéma d'authentification |
