# Connexion SSO Microsoft (Entra ID)

Toute l'application est réservée aux comptes Microsoft de l'organisation. Il n'y a ni formulaire de connexion ni mot de passe : une personne non connectée est envoyée vers Microsoft, puis revient connectée sur la page demandée.

## Selon l'environnement

| Environnement                    | Connexion                                                                               |
| -------------------------------- | --------------------------------------------------------------------------------------- |
| `prod` (et tout autre : qualif…) | SSO Microsoft                                                                           |
| `dev`                            | Formulaire `https://localhost:8443/connexion`, identifiants **`audencia` / `audencia`** |
| `test`                           | SSO avec un faux Microsoft (`tests/Double/FakeMicrosoftClient.php`), sans appel réseau  |

Le choix se fait dans `config/services.yaml` : l'alias `app.authenticator` pointe vers `App\Security\AzureAuthenticator`, sauf `when@dev` (`App\Security\DevLoginAuthenticator`). Le formulaire de dev (`DevLoginController`, `DevLoginAuthenticator`, `DevUserProvisioner`) porte `#[When(env: 'dev')]` : il n'existe dans aucun autre environnement, ce que vérifie `tests/Functional/Security/ProdLoginTest.php`.

## Règle : aucune page publique

`config/packages/security.yaml` exige une connexion pour toute adresse (`- { path: ^/, roles: ROLE_USER }`), dans tous les environnements. Les seules exceptions sont :

| Route                 | Adresse                | Rôle                                           |
| --------------------- | ---------------------- | ---------------------------------------------- |
| `connect_azure_start` | `/connect/azure`       | Redirige vers Microsoft                        |
| `connect_azure_check` | `/connect/azure/check` | Retour de Microsoft                            |
| `_logout_main`        | `/deconnexion`         | Déconnexion (POST + jeton CSRF)                |
| `app_login`           | `/connexion`           | Formulaire audencia / audencia, dev seulement  |
| `app_welcome`         | `/`                    | Page d'accueil du skeleton, dev/test seulement |

`tests/Functional/Security/AccessControlTest.php` parcourt toutes les routes et échoue si l'une d'elles est accessible sans connexion. Rendre une page publique demande d'ajouter une règle `PUBLIC_ACCESS` **et** de compléter `PUBLIC_ROUTES` dans ce test, avec validation de la DSI.

Seules les pages d'erreur (404, 500…) s'affichent sans connexion : elles ne contiennent aucune donnée.

## Configuration

1. Demander à la DSI une application Entra ID (« App registration ») avec :
   - type de comptes : **ce répertoire uniquement** (single tenant) ;
   - URL de redirection (plateforme Web) : `https://<domaine>/connect/azure/check` (une par environnement en ligne : qualif, prod…) ;
   - URL de déconnexion : `https://<domaine>/connect/azure` ;
   - un secret client.
2. Renseigner sur le serveur (variables d'environnement ou `make sf c='secrets:set AZURE_CLIENT_SECRET --env=prod'`), jamais dans un fichier versionné :

   ```dotenv
   AZURE_CLIENT_ID=<Application (client) ID>
   AZURE_CLIENT_SECRET=<valeur du secret>
   AZURE_TENANT_ID=<Directory (tenant) ID>
   ```

3. Vérifier `https://<domaine>/mon-compte` : Microsoft demande de se connecter, puis la page affiche le nom et l'e-mail.

Tant que la configuration est incomplète (ou si `AZURE_TENANT_ID` vaut `common`/`organizations`), `/connect/azure` affiche « La connexion Microsoft n'est pas encore configurée » et aucune page protégée n'est accessible.

## Fonctionnement

| Fichier                                  | Rôle                                                                                                                                                      |
| ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/Security/AzureAuthenticator.php`    | Envoie vers Microsoft, traite le retour, mémorise la page demandée. En cas d'échec, affiche `templates/security/login_failure.html.twig` (pas de boucle). |
| `src/Service/AzureUserProvisioner.php`   | Vérifie que le compte appartient au tenant configuré (claim `tid`), puis crée ou met à jour l'utilisateur.                                                |
| `src/Security/AzureLogoutListener.php`   | Après `/deconnexion`, déconnecte aussi la session Microsoft (sauf en dev : `app.sso_enabled` vaut `false`).                                               |
| `src/Security/AzureSsoConfiguration.php` | Lecture des variables `AZURE_*` et du paramètre `app.sso_enabled`.                                                                                        |
| `src/Entity/User.php`                    | Table `app_security_user`. Identifiant stable : `azureId` (claim `oid`), l'e-mail est recopié à chaque connexion.                                         |

Après connexion sans page demandée, redirection vers `app_account` (`/mon-compte`) : changer `AzureAuthenticator::DEFAULT_ROUTE` quand la vraie page d'accueil existe.

Tous les comptes reçoivent `ROLE_USER`. Pour un rôle supplémentaire (`ROLE_ADMIN`…), utiliser `User::setRoles()` (commande ou écran d'administration) et `#[IsGranted('ROLE_ADMIN')]` sur les contrôleurs concernés.

## Données personnelles

La table `app_security_user` contient nom, prénom, e-mail et date de dernière connexion : c'est un traitement de données personnelles à déclarer à la DSI (registre RGPD). N'y ajouter aucune autre donnée sans validation.

## Tests

En test, `config/services.yaml` remplace le client Microsoft (`knpu.oauth2.client.azure`) par `tests/Double/FakeMicrosoftClient.php` : `/connect/azure` renvoie directement vers `/connect/azure/check`, l'état OAuth est vérifié comme en vrai et le compte renvoyé est `tests/Double/FakeAzureAccount.php` (Camille Martin, tenant de `.env.test`).

- Parcours complet par le faux SSO : voir `tests/Functional/Security/SsoLoginTest.php` ; `FakeMicrosoftClient::signInAs([...])` change le compte (avec `$client->disableReboot()`).
- Pour tester une page protégée sans refaire le parcours : `$client->loginUser($user)` avec un `User` enregistré en base.
