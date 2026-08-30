# Préparation de la production AutoFacture

Ce document décrit les contrôles obligatoires avant d'accepter des données réelles. Le fichier `.env.production.example` fournit uniquement la structure : aucun secret réel ne doit être ajouté au dépôt.

## Conditions de mise en ligne

- domaine HTTPS définitif et certificats renouvelés automatiquement ;
- `APP_ENV=production`, `APP_DEBUG=false` et `LOG_LEVEL=warning` ;
- mot de passe MariaDB unique, injecté par le gestionnaire de secrets ;
- Redis persistant pour les sessions, le cache et les files de travaux ;
- service SMTP transactionnel configuré et testé ;
- workers `php artisan queue:work --sleep=1 --tries=3 --timeout=120` supervisés ;
- ordonnanceur `php artisan schedule:run` exécuté toutes les minutes ;
- sauvegarde quotidienne chiffrée hors serveur et restauration testée chaque mois ;
- supervision de `/api/health`, des erreurs HTTP, des files en échec et de l'espace disque ;
- valeurs OAuth SUPER PDP copiées exclusivement depuis la documentation officielle validée.

## Procédure de livraison

1. sauvegarder la base et les fichiers ;
2. installer les dépendances verrouillées avec `composer install --no-dev --classmap-authoritative` ;
3. compiler le frontend avec `npm run build` ;
4. exécuter `php artisan migrate --force` ;
5. exécuter `php artisan config:cache`, `route:cache` et `view:cache` ;
6. redémarrer PHP-FPM, les workers et l'ordonnanceur ;
7. contrôler `/api/health`, la connexion, la création client SIRET, un devis, une facture et un PDF ;
8. tester la restauration de la dernière sauvegarde sur un environnement isolé.

## Facturation électronique

Le connecteur n'est déclaré opérationnel que lorsque l'échange OAuth, le contrôle de session et les scénarios bac à sable sont verts. La réception, l'émission, les rejets, les statuts de cycle de vie et l'e-reporting restent bloqués tant que le contrat d'API officiel de la plateforme agréée n'a pas été validé.
