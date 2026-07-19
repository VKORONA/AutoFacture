# AutoFacture — test bêta de l'interface Premium

Cette branche contient la refonte graphique Premium d'AutoFacture.

## Branche

```text
feature/premium-dashboard-ui
```

## Mise à jour d'une installation locale existante

Depuis le dossier `AutoFacture_Test` :

```powershell
git status
git fetch origin
git switch feature/premium-dashboard-ui
git pull --ff-only origin feature/premium-dashboard-ui
```

Conserver le fichier `.env` local. Ne pas exécuter `git clean`, `git reset --hard` ou `docker compose down -v`.

## Mise à jour de Laravel et de la base

```powershell
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
```

Les migrations sont additives et ne suppriment pas les clients, devis ou factures.

## Construction du frontend

Sous Windows PowerShell, utiliser `npm.cmd` :

```powershell
npm.cmd install --legacy-peer-deps
npm.cmd run build
```

Puis redémarrer les services applicatifs :

```powershell
docker compose restart app nginx scheduler
```

## Ouverture

```text
http://localhost
```

Effectuer un rafraîchissement complet avec `Ctrl + F5`.

## Scénario de contrôle visuel

1. Vérifier la barre latérale sombre et le logo AutoFacture.
2. Vérifier l'affichage du nom de l'utilisateur et de l'entreprise active.
3. Vérifier les quatre cartes du tableau de bord.
4. Vérifier le graphique CA HT N, CA HT N-1 et encaissements.
5. Vérifier la répartition des factures.
6. Ouvrir une facture depuis la liste récente.
7. Ouvrir un devis depuis la liste récente.
8. Tester les actions rapides : devis, facture, client et sauvegarde.
9. Réduire la largeur de la fenêtre pour contrôler la version mobile.
10. Vérifier qu'un devis et une facture créés avant la mise à jour sont toujours présents.

## Retour à la version fonctionnelle précédente

Aucune donnée n'est supprimée lors du changement de branche. Pour revenir au lot précédent :

```powershell
git switch feature/france-onboarding-estimates
npm.cmd run build
docker compose exec app php artisan optimize:clear
docker compose restart app nginx scheduler
```

Ne jamais ajouter l'option `-v` à `docker compose down`, car elle supprimerait le volume local de la base de données.
