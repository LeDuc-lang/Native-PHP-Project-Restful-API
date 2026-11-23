# RESTful-like API en PHP natif

## Avant-propos

Ce projet à vocation pédagogique illustre une mise en œuvre simplifiée en PHP natif d'une API respectant en partie les
contraintes architecturales REST.

Pour plus de détails sur les contraintes architecturales en
question : [Representational state transfer — Wikipédia](https://fr.wikipedia.org/wiki/Representational_state_transfer#Contraintes_architecturales).

## Spécificités

- Implémentation en PHP natif sans dépendances tierces.
- Organisation selon un modèle en couches _Model_/_Controller_, le contrôleur étant en charge de la génération des
  réponses au format JSON.
- Gestion des erreurs personnalisées et journalisation des exceptions.
- Exemples d'opérations standards sur un jeu de données (modèle `User`) :
    - GET
    - POST
    - PUT
    - PATCH
    - DELETE
- Authentification via _JSON Web Token_ (JWT), voir :
  - [JSON Web Token — Wikipédia](https://fr.wikipedia.org/wiki/JSON_Web_Token)
  - [RFC 6750 - The OAuth 2.0 Authorization Framework: Bearer Token Usage](https://datatracker.ietf.org/doc/html/rfc6750)

## Modèle conceptuel des données

MCD des entités `user`, `refresh_token`, `game` et `game_attempt` avec spécification des types MySQL.
Le schéma relationnel détaillé de la partie collective est présent dans
[`docs/projet-collectif.md`](./docs/projet-collectif.md).

![Native_PHP_RESTful_API_MCD](./stuff/native_php_restful_api.png)

## _Endpoints_ disponibles

### Modèle `User`

- Récupération de tous les utilisateurs :

    ```shell
    GET /api/user
    ```

- Récupération d'un utilisateur en fonction de son identifiant (`user.id`) :

   ```shell
   GET /api/user/{id}
   ```

- Création d'un nouvel utilisateur :

    ```shell
    POST /api/user
    ```

- Modification d'un utilisateur :

    ```shell
    PATCH /api/user/{id}
    ```

- Remplacement d'un utilisateur :

    ```shell
    PUT /api/user/{id}
    ```

- Suppression d'un utilisateur en fonction de son identifiant (`user.id`) :

    ```shell
    DELETE /api/user/{id}
    ```

### Modèle `Game` (partie collective)

- Liste des joueurs connectés et disponibles :

    ```shell
    GET /api/user/available
    ```

- Démarrer une partie contre un joueur (`user.id`) :

    ```shell
    POST /api/game/start
    ```

- Récupérer la partie en cours de l'utilisateur authentifié :

    ```shell
    GET /api/game/current
    ```

- Récupérer l'état d'une partie :

    ```shell
    GET /api/game/{id}
    ```

- Proposer un mot de 6 lettres :

    ```shell
    POST /api/game/{id}/attempt
    ```

- Abandonner une partie :

    ```shell
    POST /api/game/{id}/cancel
    ```

### Authentification JWT

- Connexion :

    ```shell
    POST /api/login
    ```

- Déconnexion :

    ```shell
    POST /api/logout
    ```

- Reconnexion (rafraîchissement du jeton d’accès) :

    ```shell
    POST /api/token/refresh
    ```

## Travail individuel (interface client)

Le fichier `/public/client.php` fournit une interface complète en HTML, CSS et jQuery permettant de :

- créer un compte utilisateur ;
- s'authentifier et se déconnecter ;
- consulter et modifier son profil (pseudo, genre, avatar uniquement) ;
- supprimer son compte ;
- rafraîchir automatiquement l'Access Token grâce au Refresh Token lorsque celui-ci expire.

La modification et la suppression sont réservées au propriétaire du compte : le contrôleur frontal transmet
l'identifiant contenu dans le JWT au contrôleur, qui renvoie une erreur **403** en cas de tentative sur un autre compte.

La documentation détaillée (méthode AJAX, schéma du mécanisme d'authentification, détails d'implémentation et
scénarios de test) est disponible dans [`docs/travail-individuel.md`](./docs/travail-individuel.md).

## Projet collectif (Motus)

Le fichier `/public/client.php` contient également le jeu Motus à deux joueurs :

- liste des utilisateurs connectés et disponibles (présence mise à jour toutes les 3 secondes) ;
- création d'une partie en cliquant sur le bouton **Défier** d'un joueur : l'initiateur est le joueur 1 et joue le
  premier ; le joueur invité reçoit un bandeau avec un bouton **Rejoindre la partie** ;
- grille des propositions avec couleurs rouge / jaune / bleu, calculées par le serveur ;
- alternance des tours et synchronisation des deux écrans par **polling AJAX** (3 s dans le salon, 2 s en partie) ;
- limite de 8 propositions au total et de 30 secondes par proposition ;
- victoire, match nul ou abandon, avec révélation du mot cible en fin de partie.

La base de données est complétée par les tables `game` et `game_attempt` et par la colonne
`user.last_seen_at`. Pour une base déjà initialisée avant ces ajouts, exécuter :

```shell
mysql -u admin -p native_php_restful_api < stuff/migration_collectif.sql
```

Le support de présentation (base de données, démonstration, communication temps-réel, implémentation,
bilan et améliorations) est disponible dans [`docs/projet-collectif.md`](./docs/projet-collectif.md).

## Structure du projet

Arborescence des répertoires et sous-répertoires :
```console
├── public
│   ├── assets
│   │   ├── css
│   │   │   ├── app.css
│   │   │   └── pico.min.css
│   │   └── js
│   │       └── jquery-3.7.1.min.js
│   ├── client.php
│   ├── front_controller.php
│   ├── index.php
│   └── router.php
├── src
│   ├── Core
│   │   ├── Database.php
│   │   └── DotEnv.php
│   ├── Security
│   │   └── JwtService.php
│   ├── Controller
│   │   ├── GameController.php
│   │   └── UserController.php
│   └── Model
│       ├── Game.php
│       └── User.php
├── helpers
│   ├── global_helper.php
│   ├── http_response_helper.php
│   └── jwt_helper.php
├── docs
│   ├── projet-collectif.md
│   ├── travail-individuel.md
│   └── schema-authentification.svg
├── logs
└── stuff

```

Détails :
- `/public`: Point d'accès public (assets, client jQuery), initialisation des variables d'environnement (`.env` : connexion à la base de données, configuration JWT) et chargement des composants nécessaires (`index.php`), routeur du serveur PHP (`router.php`), et contrôleur frontal (`front_controller.php`, en charge du routage via l'analyse du format des requêtes ainsi que de la méthode (GET, POST, PATCH, etc.) utilisée par la requête).
- `/src/Core`: Composants du cœur applicatif.
- `/src/Security`: Composants de sécurité (JWT).
- `/src/Controller`: Implémentation de la logique (analyse des requêtes, application des traitements, élaboration des réponses).
- `/src/Model`: Implémentation des opérations en base de données. 
- `/helpers` : Scripts utilitaires.
- `/logs` : Destiné au stockage des fichiers de journalisation (ex. : `error.log`), ce dossier doit être créé manuellement (voir plus bas).
- `/stuff` : Fichiers et documents annexes.


## Installation du projet

### Pré-requis

- PHP 8.1 ou version supérieure.
- MySQL 8.0  ou version supérieure.
- Git.

### Déploiement

1. Décompression de l'archive :

    ```
    Native-PHP-RESTful-API-develop.zip
    ```

2. Création de la base de données :
   - Créer une base de données nommée `native_php_restful_api` (par exemple).
   - Importer le script de création de la base de données (fichier `/stuff/native_php_restful_api.sql`).
   - Si la base existait déjà avant l'ajout du jeu Motus, exécuter également `/stuff/migration_collectif.sql` (ajout de `user.last_seen_at`, `game` et `game_attempt`).

3. Configuration des paramètres de connexion à la base de données et des paramètres JWT (le fichier `/.env` doit être créé manuellement) :

   ```env
    DB_HOST=localhost
    DB_USER=
    DB_PASS=
    DB_NAME=native_php_restful_api

    JWT_SECRET=my_secret_key_not_really_so_secret_please_change_me
    JWT_TTL=300
    JWT_REFRESH_TTL=604800
    ```

4. Création d'un fichier de journalisation (_logging_) destiné à conserver une trace des exceptions PHP générées par l'application, avec **permissions suffisantes** (accès en écriture) :
   ```shell
   cd native_php_restful_api
   mkdir logs
   touch logs/error.log
   chmod 777 logs/error.log
    ```

5. Démarrage du serveur Web interne à PHP :

    ```shell
    cd native_php_restful_api
    php -S localhost:8000 -t public public/router.php
    ```

6. Vérification du fonctionnement de l'application via un client HTTP (ex. : navigateur) : http://localhost:8000/api/user.
   - Une réponse `{"status":"Error","message":"Unauthorized"}` (HTTP 401) est tout à fait normale : cette route est protégée par authentification.
   - Pour lancer le projet avec Docker : `docker compose up --build`, puis ouvrir http://localhost:8080/client.php.
   - Si Docker tourne sur un serveur distant (VPS / context Docker distant), remplacer `localhost` par l'IP du serveur : `http://<IP_DU_SERVEUR>:8080/client.php`. Deux PC doivent ouvrir cette même URL pour jouer ensemble ; `localhost` ne fonctionne que si le serveur est sur la machine du navigateur.

7. Vérification du fonctionnement du client jQuery via un navigateur : http://localhost:8000/client.php.
   - Compte de démonstration : `lucas.morel@example.com` / `password`.

## Gestion des erreurs

### Codes de réponses HTTP de l'API

Liste des codes utilisés (non exhaustive) :
- **400** - _Bad Request_ : Le corps de la requête est vide ou des données requises sont manquantes.
- **401** - _Unauthorized_ : L'utilisateur n'est pas authentifié.
- **403** - _Forbidden_ : L'accès à la ressource demandée est interdit.
- **404** - _Not Found_ : La route ou l'instance d'un modèle n'ont pu être trouvés.
- **405** - _Method Not Allowed_ : La méthode utilisée par la requête (GET, POST, PATCH, etc.) n'est pas autorisée.
- **500** - _Internal Server Error_ : Il s'agit d'une erreur interne du serveur non spécifiée.
- Etc.

Les codes d'état et messages associés (succès ou erreur) sont inclus à la réponse au format JSON afin de pouvoir traiter les cas particuliers côté client.

Pour le détail des codes d'état de réponse HTTP, voir : [Liste des codes HTTP — Wikipédia](https://fr.wikipedia.org/wiki/Liste_des_codes_HTTP).

### Error Log

La grande majorité des exceptions PHP générées côté serveur est journalisée dans le fichier `/logs/error.log`.

Ce fichier permet la surveillance de l'application en fonctionnement et constitue une aide pour l'identification de l'origine des exceptions PHP générées.

## Avertissement

Ce dépôt a été conçu à des fins pédagogiques pour illustrer des concepts et des principes spécifiques. Le code n'est pas destiné à être utilisé en production, car il peut ne pas répondre aux exigences de sécurité, de qualité, de robustesse et de performance nécessaires dans un environnement professionnel. Il sert uniquement à des fins d'apprentissage et ne doit pas être considéré comme un modèle de développement. Ce code est donc fourni à des fins éducatives et sans engagement de performance ou de fiabilité.

## License

Ce projet inspiré du projet [FarrelAD/Basic-PHP-RESTful-API](https://github.com/FarrelAD/Basic-PHP-RESTful-API) est open source et disponible sous licence MIT. N’hésitez pas à le modifier et à l’utiliser comme support d’apprentissage.