# Instructions pour les agents IA

Ce fichier est lu automatiquement par les agents de code (Claude Code, Codex, Cursor, OpenCode…).

## À qui tu parles

La personne qui utilise ce projet **n'est généralement pas développeuse**. Elle connaît son métier, pas la technique.

- Réponds en français, avec des phrases simples. Explique chaque terme technique la première fois (« une _route_, c'est une adresse de page, par ex. `/reservations` »).
- Avant un changement important, résume en 2-3 phrases ce que tu vas faire et pourquoi, sans jargon.
- Après chaque changement, dis **quoi regarder dans le navigateur** pour vérifier (adresse exacte, bouton à cliquer, résultat attendu).
- Quand une demande est floue, pose **une** question concrète avec des exemples de réponses plutôt que de deviner.
- Avance par petites étapes visibles : une fonctionnalité qui marche vaut mieux que trois à moitié faites.
- Ne dis jamais « c'est fait » sans avoir lancé `make qa` et vérifié que la page s'affiche.

## Démarrer un nouveau projet à partir de ce skeleton

Si le dossier s'appelle encore `audencia-project-skeleton` ou que la personne demande à créer un projet :

1. Demander le nom du projet s'il n'est pas donné (minuscules, chiffres, tirets : `gestion-salles`).
2. `bin/init-project <nom>` : renomme le projet, génère un secret, réinitialise Git.
3. `make build` puis `make up` (le premier build prend plusieurs minutes : prévenir la personne).
4. Vérifier `https://localhost:8443` (page d'accueil du skeleton, 4 indicateurs verts).
5. Proposer le premier commit (`chore: initialisation depuis audencia-project-skeleton`) et attendre l'accord.

## Stack

- Symfony 7.4 LTS, PHP 8.5, FrankenPHP (worker mode), le tout dans Docker. **Toutes les commandes PHP passent par le conteneur**, jamais par le PHP de la machine.
- Base de données SQLite (`var/data/`), Doctrine ORM + migrations.
- Twig pour les pages. Aucun outillage front imposé : choisir selon le besoin (AssetMapper, Tailwind, Stimulus…).

## Commandes

| Besoin                                            | Commande                                 |
| ------------------------------------------------- | ---------------------------------------- |
| Démarrer / arrêter                                | `make up` / `make down`                  |
| Logs                                              | `make logs`                              |
| Console Symfony                                   | `make sf c='make:controller'`            |
| Composer                                          | `make composer c='require symfony/form'` |
| Contrôles qualité (obligatoire avant de terminer) | `make qa`                                |
| Corriger le style                                 | `make cs-fix`                            |

Adresse locale : `https://localhost:8443` (ports définis dans `.env.local`, défaut 8000/8443).

## Règles de code

Référence complète : [`docs/bonnes-pratiques.md`](docs/bonnes-pratiques.md). La lire avant d'écrire du code. En résumé :

- `make qa` doit passer : PHP-CS-Fixer (règles Audencia), PHPStan niveau **max**, PHPUnit 13. Ne jamais baisser le niveau PHPStan ni ajouter d'`ignoreErrors` pour faire passer : corriger le code.
- Un test pour chaque fonctionnalité (`tests/`). Tests fonctionnels avec `WebTestCase` pour les pages.
- Textes affichés en français. Contrôleurs fins : la logique métier va dans `src/Service/`.
- Entités : identifiant UUIDv7 (`Uuid::v7()`, voir bonnes pratiques), attributs PHP `#[ORM\...]`, puis `make sf c='doctrine:migrations:diff'` et `make sf c='doctrine:migrations:migrate'`.
- Formulaires : classes `*Type` dans `src/Form/`, validation par contraintes `#[Assert\...]`.
- À la création de la première vraie page d'accueil, supprimer la page du skeleton : `src/Controller/WelcomeController.php`, `templates/welcome/`, `tests/Functional/WelcomeTest.php`.
- Pages d'erreur : `templates/bundles/TwigBundle/Exception/error.html.twig` (ne jamais afficher le message d'exception).

## Ce que tu ne fais pas sans accord explicite

- Commit ou push Git : proposer le message, attendre la validation.
- Supprimer des données : `docker compose down -v`, suppression de `var/data/`, migrations qui suppriment des colonnes ou tables.
- Mise en production, configuration serveur, DNS, certificats.
- Ajouter une authentification, stocker des données personnelles (RGPD) ou des moyens de paiement : signaler que ça nécessite la validation de la DSI.
- Mettre un mot de passe, une clé d'API ou un secret dans un fichier versionné : utiliser `.env.local` (non versionné).

## Dépannage courant

- `port is already allocated` : changer les ports dans `.env.local` puis `make down && make up`.
- Changement dans `Dockerfile` ou `frankenphp/` : `make build` puis `make up` (sinon l'ancienne image tourne).
- Page blanche ou erreur 500 en dev : `make logs`, la page d'erreur Symfony détaille la cause.
