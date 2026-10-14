# Bonnes pratiques des projets Audencia

Conventions communes aux applications Symfony de la DSI, consolidées à partir des projets existants (candidat, aurion-middleware, events…).
Elles s'appliquent à tout projet créé depuis ce skeleton, qu'il soit écrit par une personne ou par un agent IA.

> Un projet peut avoir des règles plus strictes (dans son `AGENTS.md`), jamais plus souples.

---

## 1. Principes

- **Simple d'abord.** Pas de couche d'abstraction non demandée : pas de *base repository*, pas d'interface sans seconde implémentation, pas de pattern « pour plus tard ».
- **Petites étapes livrables.** Une fonctionnalité terminée et testée vaut mieux que plusieurs à moitié faites.
- **Vérifier plutôt que supposer.** Les API du framework changent d'une version à l'autre. En cas de doute : `bin/console about`, `debug:router`, `debug:container`, `debug:autowiring <nom>`, `debug:config <bundle>`, et la source dans `vendor/`.
- **Branche principale `main`** sur tous les projets.
- **Documentation et commentaires en français.** Un commentaire explique un *pourquoi* non évident, jamais ce que le code dit déjà.

## 2. Code PHP

- `declare(strict_types=1);` dans chaque fichier PHP.
- Promotion de propriétés dans le constructeur ; `readonly` sur les DTO et value objects (`final readonly class`).
- Classes `final` par défaut.
- **Attributs PHP** pour les métadonnées framework : `#[Route]`, `#[IsGranted]`, `#[ORM\...]`, `#[Assert\...]`, `#[AsEventListener]`, `#[Autowire]`…
- **Autowiring et autoconfiguration** par défaut. Une définition de service en YAML est un dernier recours.
- Pas de `array{clé: Type}` comme type de retour : créer un **DTO** dans `src/Dto/`. `list<Type>` reste acceptable pour une collection homogène.
- DTO toujours construit via des méthodes de fabrique (`self::payment()`, `self::refund()`) : constructeur `private`.
- **Montants en centimes** (`int`), jamais en `float`. Getter `getPriceInEuros()` uniquement pour l'affichage ; `MoneyType` avec `'divisor' => 100`.
- Énumérations PHP (`enum`) pour les statuts et listes fermées. Elles portent leurs clés de traduction (`getTranslationKey()`) au lieu de dupliquer les correspondances dans les formulaires et les templates.

## 3. Architecture Symfony

| Dossier | Rôle |
|---|---|
| `src/Controller/` | Reçoit la requête, appelle un service, rend une réponse. **Aucune logique métier, aucune transformation de données.** |
| `src/Service/` | Logique métier. |
| `src/Entity/`, `src/Repository/` | Modèle Doctrine et requêtes. |
| `src/Form/` | Formulaires, suffixe `Type` (`ReservationType`). Pas de logique métier. |
| `src/Dto/` | Objets de transfert immuables. |
| `src/Enum/` | Énumérations métier. |
| `templates/` | Twig, organisé par zone (`front_office/`, `back_office/`, `shared/`) quand le projet en a plusieurs. |

- Accès refusé par défaut : chaque route d'administration porte `#[IsGranted]` ; la logique d'autorisation complexe va dans un **Voter**.

## 4. Base de données (Doctrine)

- Mapping par attributs `#[ORM\...]`.
- **Identifiant en UUIDv7** pour toute entité (jamais d'entier auto-incrémenté) : non devinable dans les URL, triable par date de création, généré côté PHP avant l'enregistrement.
  ```php
  use Symfony\Bridge\Doctrine\Types\UuidType;
  use Symfony\Component\Uid\Uuid;

  #[ORM\Id]
  #[ORM\Column(type: UuidType::NAME, unique: true)]
  private Uuid $id;

  public function __construct()
  {
      $this->id = Uuid::v7();
  }
  ```
- Tables nommées `app_<module>_<entité>` via `#[ORM\Table(name: '...')]`.
- Toute modification de schéma passe par une **migration** :
  1. `make sf c='doctrine:migrations:status'` : exécuter d'abord les migrations en attente ;
  2. `make sf c='doctrine:migrations:diff'` ;
  3. **relire la migration** et retirer ce qui ne concerne pas la fonctionnalité (Doctrine embarque parfois des écarts sans rapport) ;
  4. `make sf c='doctrine:migrations:migrate'`.
- Une migration qui supprime une colonne ou une table se valide avec l'équipe avant d'être écrite.
- SQLite par défaut dans le skeleton ; passer à PostgreSQL dès que l'application part en production partagée.

## 5. Tests

- **Toute fonctionnalité a son test.** Elle n'est terminée qu'avec un test qui l'exerce comme le ferait un utilisateur ou un appelant.
- `tests/Unit/` : services et logique métier, dépendances doublées (pas de base de données).
- `tests/Functional/` : vraies requêtes HTTP via `WebTestCase` (statut, contenu, redirections, formulaires).
- Jamais d'appel à un système externe réel (Aurion, API tierces, SFTP) dans les tests : doubler le service qui y accède.
- Méthodes de test en `camelCase` avec l'attribut `#[Test]`.

## 6. Qualité

`make qa` doit passer avant tout commit. Il enchaîne :

| Outil | Règle |
|---|---|
| PHP-CS-Fixer | Règles Audencia (`@Symfony` + custom fixers). `make cs-fix` corrige. |
| PHPStan | Niveau **max**. On corrige la cause : ni `@phpstan-ignore`, ni baseline, ni `ignoreErrors`, ni cast décoratif pour faire taire une erreur. |
| PHPUnit 13 | Toute la suite au vert, sans dépréciation ni avertissement. |

La CI GitHub relance les mêmes contrôles sur chaque push et pull request.

## 7. Sécurité et données

- **Aucun secret dans Git** : mots de passe, clés d'API, jetons.
  - En local : `.env.local` (non versionné).
  - Hors local : coffre Symfony (`config/secrets/`, `make sf c='secrets:set NOM'`). Les fichiers du coffre ne se modifient qu'avec cette commande.
- **Données personnelles** (étudiants, candidats, salariés) : jamais dans les logs ; un identifiant technique suffit au diagnostic. Toute nouvelle collecte se valide avec la DSI (RGPD).
- Élargir les droits d'un rôle se décide explicitement, ça ne se déduit pas d'un besoin ponctuel.
- Authentification, paiement, envoi d'e-mails en masse, connexion à un logiciel de l'école : validation DSI préalable.
- Mises à jour de sécurité : `make composer c='audit'` régulièrement ; rester sur une version LTS de Symfony.

## 8. Documentation

- `README.md` : à quoi sert le projet, comment le démarrer.
- `AGENTS.md` : règles du projet pour les agents IA (et `CLAUDE.md` qui l'importe). Le mettre à jour quand une règle ou une zone sensible apparaît.
- `docs/<contexte>/` : un dossier par contexte fonctionnel (architecture, règles métier, décisions techniques), mis à jour avec le code.
- Pas de fichier de documentation créé « au cas où » : seulement quand il apporte une information introuvable dans le code.

## 9. Travailler avec un agent IA

- Lancer l'agent **depuis le dossier du projet**, pour qu'il lise `AGENTS.md`.
- Décrire le besoin et faire valider un plan avant de faire coder.
- Relire chaque modification proposée ; tester dans le navigateur ; commiter étape par étape.
- Ne jamais coller de secret dans la conversation.
- Guide détaillé pour les personnes non développeuses : [guide-agent-ia.md](guide-agent-ia.md).
