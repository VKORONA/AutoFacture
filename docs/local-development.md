# Développement local AutoFacture

## Prérequis Windows

- Git pour Windows
- Docker Desktop avec WSL 2
- Node.js 16.20.2 pour le frontend historique
- 8 Go de RAM minimum, 16 Go conseillés
- Ports 80 et 33006 disponibles

## Installation

Dans PowerShell :

```powershell
git clone https://github.com/VKORONA/AutoFacture.git
cd AutoFacture
git checkout develop/mvp-france-ai
Copy-Item .env.example .env
docker compose build --no-cache
docker compose up -d
```

Les identifiants MariaDB de `.env.example` et de `docker-compose.yml` sont identiques :

```text
Base : autofacture
Utilisateur : autofacture
Mot de passe : autofacture
Hôte interne : db
Port interne : 3306
```

Installer les dépendances et initialiser Laravel :

```powershell
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan autofacture:bootstrap-local
```

Installer ensuite les dépendances frontend sur Windows :

```powershell
npm install --legacy-peer-deps
npm run build
```

Le verrou NPM historique est ancien. `--legacy-peer-deps` est temporairement requis jusqu'à la modernisation du frontend.

## Connexion locale

Ouvrir :

```text
http://localhost
```

Compte de démonstration local uniquement :

```text
E-mail : admin@autofacture.local
Mot de passe : autofacture-dev
```

Ne jamais utiliser ce compte ni ce mot de passe en production.

## Vérification

```powershell
docker compose ps
docker compose exec app php artisan --version
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test
npm run test
npm run build
docker compose config --quiet
```

## Journaux

```powershell
docker compose logs app
docker compose logs nginx
docker compose logs db
docker compose logs scheduler
```

## Audit des dépendances

Sous Git Bash :

```bash
bash scripts/day1-audit.sh
```

Le script ne modifie pas les dépendances. Il produit uniquement un diagnostic.

## Règles de sécurité

- Ne jamais committer `.env`.
- Ne jamais placer une clé OpenAI ou une clé de plateforme agréée dans le navigateur.
- Générer une nouvelle `APP_KEY` pour chaque environnement.
- Laisser `AI_ENABLED=false` tant que le module IA n'est pas installé.
- En production, imposer `APP_ENV=production` et `APP_DEBUG=false`.
- Remplacer PHP 8.1 et Laravel 8 par des versions supportées avant ouverture commerciale.

## Remise à zéro locale

Cette commande détruit la base locale Docker :

```powershell
docker compose down -v
```

Puis reprendre l'installation depuis le début.
