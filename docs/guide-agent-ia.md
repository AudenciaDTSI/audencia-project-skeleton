# Créer une application avec un agent IA

Ce guide s'adresse aux personnes **qui ne sont pas développeuses**. Il explique, étape par étape, comment créer une petite application web Audencia en se faisant aider par un agent IA, à partir de ce modèle (le *skeleton*).

Pas besoin de savoir programmer : vous décrivez ce que vous voulez en français, l'agent écrit le code. Votre rôle est de **bien expliquer votre besoin** et de **vérifier le résultat** dans votre navigateur.

> Temps à prévoir : 30 minutes pour l'installation (une seule fois), puis 5 minutes pour créer chaque nouveau projet.

---

## Sommaire

1. [Quelques mots de vocabulaire](#1-quelques-mots-de-vocabulaire)
2. [Installer les outils (une seule fois)](#2-installer-les-outils-une-seule-fois)
3. [Créer votre projet](#3-créer-votre-projet)
4. [Voir votre application](#4-voir-votre-application)
5. [Construire votre application avec l'agent](#5-construire-votre-application-avec-lagent)
6. [Sauvegarder votre travail](#6-sauvegarder-votre-travail)
7. [Reprendre le travail un autre jour](#7-reprendre-le-travail-un-autre-jour)
8. [En cas de problème](#8-en-cas-de-problème)
9. [Les limites : quand demander à la DSI](#9-les-limites--quand-demander-à-la-dsi)

---

## 1. Quelques mots de vocabulaire

| Mot | Ce que ça veut dire |
|---|---|
| **Terminal** | Une fenêtre où l'on tape des commandes au lieu de cliquer. Sur Mac : application *Terminal*. Sur Windows : *Ubuntu* (voir plus bas). |
| **Agent IA** | Un assistant (ici *Claude Code*) qui lit et écrit le code de votre projet à votre place, dans le terminal. Vous lui parlez en français. |
| **Docker** | Un logiciel qui fait tourner votre application dans une « boîte » isolée sur votre ordinateur. Il évite d'installer tout un tas d'outils techniques. |
| **Skeleton** | Ce modèle de projet : une application vide mais déjà configurée, prête à être complétée. |
| **Git / commit** | Un système de sauvegarde. Un *commit* est une sauvegarde nommée de votre projet, à laquelle on peut revenir. |
| **localhost** | « Mon ordinateur ». L'adresse `https://localhost:8443` affiche l'application qui tourne chez vous, invisible pour les autres. |

---

## 2. Installer les outils (une seule fois)

### 2.1 Docker Desktop

1. Télécharger Docker Desktop : <https://www.docker.com/products/docker-desktop/>
2. L'installer comme une application classique, puis **l'ouvrir**.
3. Attendre que l'icône de la baleine 🐳 (barre des menus sur Mac, barre des tâches sur Windows) indique *Docker Desktop is running*.

> ⚠️ Docker Desktop doit être **ouvert** à chaque fois que vous travaillez sur votre projet.

### 2.2 Ouvrir un terminal

- **Mac** : touche `Cmd` + `Espace`, taper *Terminal*, `Entrée`.
- **Windows** : il faut d'abord installer *WSL* (un Linux intégré à Windows). Ouvrir *PowerShell* en tant qu'administrateur et taper `wsl --install`, redémarrer, puis ouvrir l'application **Ubuntu** depuis le menu Démarrer. Toutes les commandes de ce guide se tapent dans *Ubuntu*. Dans Docker Desktop, activer *Settings → Resources → WSL integration → Ubuntu*.

### 2.3 Les outils de base (Git et Make)

- **Mac** : dans le terminal, taper puis valider avec `Entrée` :
  ```
  xcode-select --install
  ```
  Une fenêtre s'ouvre : cliquer sur *Installer*. (Si le message dit « already installed », c'est déjà bon.)
- **Windows (Ubuntu)** :
  ```
  sudo apt update && sudo apt install -y git make
  ```

### 2.4 Claude Code (l'agent IA)

1. Dans le terminal :
   ```
   curl -fsSL https://claude.ai/install.sh | bash
   ```
2. Fermer puis rouvrir le terminal.
3. Taper `claude` puis `Entrée`, et suivre les instructions pour vous connecter avec votre compte Audencia.
4. Pour quitter Claude Code : taper `/exit`.

### 2.5 Vérifier que tout est prêt

Copier-coller dans le terminal :

```
docker --version && git --version && make --version | head -1 && claude --version
```

Quatre lignes avec des numéros de version doivent s'afficher. Si une ligne affiche *command not found*, reprendre l'étape correspondante.

> 💡 Les autres agents IA (Cursor, Codex, OpenCode…) fonctionnent aussi : ils lisent les mêmes instructions (fichier `AGENTS.md`). Ce guide prend l'exemple de Claude Code.

---

## 3. Créer votre projet

### 3.1 Choisir un nom

En minuscules, sans espace ni accent, avec des tirets : `gestion-salles`, `inscriptions-evenements`, `suivi-stages`.

### 3.2 Lancer l'agent dans votre dossier de travail

Dans le terminal :

```
mkdir -p ~/Projets && cd ~/Projets && claude
```

(Cela crée un dossier *Projets* dans votre dossier personnel, s'y place et démarre l'agent.)

### 3.3 Demander à l'agent de créer le projet

Copier-coller ce message dans Claude Code, **en remplaçant `gestion-salles`** par votre nom de projet :

```
Crée un nouveau projet nommé "gestion-salles" à partir du template
https://github.com/AudenciaDTSI/audencia-project-skeleton :
1. clone le dépôt dans un dossier "gestion-salles" ;
2. place-toi dans ce dossier et suis les instructions du fichier AGENTS.md
   (section "Démarrer un nouveau projet") ;
3. dis-moi quelle adresse ouvrir dans mon navigateur pour vérifier.
Je ne suis pas développeur : explique-moi simplement chaque étape.
```

L'agent va vous demander des autorisations avant de lancer certaines commandes (« *Do you want to proceed?* »). Lisez ce qu'il propose et acceptez si ça correspond à la demande.

> ⏳ La première fois, la préparation prend **plusieurs minutes** (téléchargement des composants). C'est normal.

### 3.4 Travailler ensuite dans le bon dossier

Une fois le projet créé, quitter l'agent (`/exit`) et le relancer **depuis le dossier du projet**, pour qu'il lise les règles du projet :

```
cd ~/Projets/gestion-salles && claude
```

---

## 4. Voir votre application

1. Ouvrir dans votre navigateur : **<https://localhost:8443>**
2. Le navigateur affiche un avertissement de sécurité (« *Votre connexion n'est pas privée* »). C'est normal en local : le certificat est fabriqué par votre ordinateur. Cliquer sur *Paramètres avancés* puis *Continuer vers localhost*.
3. Vous voyez la page d'accueil du skeleton avec **quatre pastilles vertes** : tout fonctionne.

Cette page disparaîtra quand l'agent créera votre vraie page d'accueil.

---

## 5. Construire votre application avec l'agent

### 5.1 Décrire le besoin avant de demander du code

Commencez par expliquer **le contexte**, sans demander de code. Exemple :

```
Je veux une application pour gérer les réservations de salles du campus.
- Les salles ont un nom, un bâtiment et une capacité.
- Une réservation a une salle, une date, une heure de début et de fin,
  et le nom de la personne qui réserve.
- Deux réservations ne peuvent pas se chevaucher sur la même salle.
Ne code rien pour l'instant : propose-moi un découpage en petites étapes.
```

L'agent propose un plan. Corrigez-le si quelque chose ne correspond pas, puis demandez la première étape.

### 5.2 Avancer une étape à la fois

```
Fais l'étape 1 : la liste des salles, avec une page pour en ajouter une.
Quand c'est fini, dis-moi quelle page ouvrir pour tester.
```

Après chaque étape :
1. **Testez** dans le navigateur ce que l'agent vous indique.
2. Dites-lui ce qui ne va pas, **précisément** : quelle page, ce que vous avez fait, ce que vous attendiez, ce que vous voyez.
3. Quand c'est bon, **sauvegardez** (section 6) avant de passer à l'étape suivante.

### 5.3 Bien formuler ses demandes

| ❌ Trop vague | ✅ Précis |
|---|---|
| « Ça marche pas » | « Sur la page /salles, quand je clique sur *Ajouter* sans remplir le nom, j'ai une page d'erreur au lieu d'un message » |
| « Fais un truc joli » | « Mets le tableau des salles sur toute la largeur et trie-le par bâtiment » |
| « Ajoute la gestion des utilisateurs » | « Je veux que seules les personnes du service logistique puissent supprimer une salle. Comment fait-on ? » |
| Tout demander d'un coup | Une fonctionnalité par message |

Astuces :
- **Collez les messages d'erreur** tels quels dans la conversation, l'agent sait les lire.
- Une **capture d'écran** peut être glissée dans le terminal.
- Si vous ne comprenez pas une réponse : « Réexplique sans jargon ».
- Si l'agent part dans une mauvaise direction : appuyez sur `Échap` pour l'interrompre, puis réexpliquez.

### 5.4 La qualité est vérifiée automatiquement

Le projet contient des contrôles automatiques (style du code, détection d'erreurs, tests). L'agent est configuré pour les lancer (`make qa`) avant de dire qu'il a fini. Vous pouvez toujours lui demander : « Lance les contrôles qualité et dis-moi si tout est vert ».

---

## 6. Sauvegarder votre travail

Quand une étape fonctionne, demandez :

```
Tout fonctionne. Fais un commit avec un message qui résume ce qu'on vient de faire.
```

L'agent vous montre le message et attend votre accord. Pour revenir en arrière si une étape a tout cassé :

```
Les derniers changements ont cassé la page des salles.
Reviens à l'état du dernier commit.
```

> 💾 Ces sauvegardes sont **sur votre ordinateur uniquement**. Pour les mettre à l'abri (et les partager avec la DSI), demandez à la DSI la création d'un dépôt GitHub pour votre projet, puis à l'agent : « Envoie le projet sur ce dépôt : <adresse> ».

---

## 7. Reprendre le travail un autre jour

1. Ouvrir **Docker Desktop** et attendre qu'il soit prêt.
2. Dans le terminal :
   ```
   cd ~/Projets/gestion-salles && claude
   ```
3. Dire à l'agent :
   ```
   Démarre l'application et rappelle-moi où on en était
   (regarde les derniers commits).
   ```

Pour arrêter l'application en fin de journée : « Arrête l'application » (ou `make down` dans un terminal).

---

## 8. En cas de problème

| Ce que vous voyez | Quoi faire |
|---|---|
| `Cannot connect to the Docker daemon` | Docker Desktop n'est pas ouvert. L'ouvrir, attendre la baleine, recommencer. |
| `port is already allocated` | Un autre logiciel utilise le même port. Dire à l'agent : « Le port est déjà utilisé, change les ports dans .env.local ». |
| Le navigateur n'affiche rien / « Connexion refusée » | L'application n'est pas démarrée : demander « Démarre l'application ». |
| Avertissement « connexion non privée » | Normal en local, voir section 4. |
| Grande page rouge/grise avec du texte technique | C'est la page d'erreur détaillée du mode développement. Copier le titre de l'erreur et le donner à l'agent. |
| `command not found: claude` | Fermer et rouvrir le terminal. Sinon, refaire l'étape 2.4. |
| L'agent tourne en rond depuis longtemps | `Échap`, puis : « Arrête. Explique-moi le problème simplement et propose deux options. » |

Si rien ne marche : contacter la DSI en joignant le nom du projet et le message d'erreur exact.

---

## 9. Les limites : quand demander à la DSI

Ce modèle est fait pour **prototyper et construire des outils internes simples**. Certaines choses demandent l'avis de la DSI **avant** d'être faites, même si l'agent sait les coder :

- **Mettre l'application en ligne** (accessible à d'autres personnes que vous).
- **Connexion des utilisateurs** (comptes, mots de passe, connexion avec le compte Audencia).
- **Données personnelles** (étudiants, candidats, salariés…) : obligations RGPD.
- **Paiements**, envoi d'e-mails en masse, connexion à d'autres logiciels de l'école (Aurion, CRM…).
- **Mots de passe ou clés d'accès** : ne jamais les coller dans la conversation pour qu'ils finissent dans le code ; l'agent sait les placer dans un fichier protégé (`.env.local`).

L'agent est configuré pour vous le rappeler quand vous abordez ces sujets.
