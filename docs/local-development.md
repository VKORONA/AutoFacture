# Développement local AutoFacture

## Prérequis

- Git
- Docker Desktop avec Docker Compose
- 8 Go de RAM minimum, 16 Go conseillés
- Ports 80 et 33006 disponibles

## Installation

```bash
git clone https://github.com/VKORONA/AutoFacture.git
cd AutoFacture
git checkout develop/mvp-france-ai
cp .env.example .env
docker compose build --no-cache
docker compose up -d
```

Installer ensuite les dépendances dans le conteneur applicatif :

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Pour le frontend, utiliser une version de Node compatible avec les dépendances historiques du projet :

```bash
npm ci
npm run build
```

L'application est ensuite accessible sur :

```text
http://localhost
```

## Vérification

```bash
docker compose ps
docker compose exec app php artisan --version
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test
npm run test
npm run build
```

## Audit des dépendances

Sous Linux, macOS ou Git Bash :

```bash
bash scripts/day1-audit.sh
```

Le script ne modifie pas les dépendances. Il produit uniquement un diagnostic.

## Règles de sécurité

- Ne jamais committer le fichier `.env`.
- Ne jamais mettre une clé OpenAI ou une clé de plateforme agréée dans le navigateur.
- Ne jamais utiliser les identifiants de développement en production.
- Générer une nouvelle `APP_KEY` pour chaque environnement.
- Laisser `AI_ENABLED=false` tant que le module IA n'est pas installé.
- En production, imposer `APP_ENV=production` et `APP_DEBUG=false`.

## Remise à zéro locale

Cette commande détruit uniquement la base locale Docker :

```bash
docker compose down -v
```

Puis relancer l'installation depuis le début.
