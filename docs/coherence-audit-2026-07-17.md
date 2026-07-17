# Audit de cohérence AutoFacture — 17 juillet 2026

## Périmètre vérifié

- 40 commits du fork par rapport à `master` au début de l'audit
- configuration Laravel et Composer
- Docker et procédure d'installation
- workflows GitHub Actions
- champs français entreprise et client
- valeurs françaises par défaut
- masquage des fonctions Crater
- génération des mentions PDF
- tests ajoutés

## Anomalies corrigées

1. Métadonnées Composer indiquant MIT alors que le dépôt est sous AGPL-3.0.
2. Identifiants MariaDB différents entre `.env.example` et Docker Compose.
3. Versions et images Docker non alignées, image MariaDB non épinglée et ancien conteneur cron séparé.
4. Trois workflows en échec : ancienne CI Crater, CI frontend redondante et déploiement Uffizzi non configuré.
5. Paramètres français appliqués manuellement mais non appliqués aux nouvelles entreprises.
6. API rôles bloquée alors que l'écran Utilisateurs peut en dépendre.
7. API champs personnalisés bloquée alors que des formulaires historiques peuvent encore la consulter.
8. Sauvegarde, configuration SMTP et stockage accessibles aux locataires du futur SaaS.
9. Mentions légales client non échappées avant injection dans le PDF.
10. Mauvais nom de collection lors de la suppression d'un PDF existant.
11. Migration utilisant `after()`, incompatible avec les tests SQLite.
12. SIREN et SIRET acceptés même s'ils ne correspondaient pas à la même entreprise.
13. Régime de TVA et indicateur d'exonération pouvant se contredire.
14. Installation locale par seed ne finalisant pas l'état attendu par l'assistant Crater.

## État après corrections

- une seule CI AutoFacture est conservée ;
- les dépendances PHP sont installées depuis `composer.lock` ;
- les tests utilisent SQLite ;
- le frontend historique utilise temporairement `npm install --legacy-peer-deps` ;
- Docker utilise PHP 8.1, MariaDB 10.11 et Nginx 1.26 ;
- les paramètres français sont centralisés dans `FrenchCompanyDefaults` ;
- les nouvelles entreprises et l'entreprise de démonstration reçoivent automatiquement ces paramètres ;
- les fonctions d'exploitation du serveur sont masquées et bloquées pour les locataires ;
- les mentions PDF entreprise et client sont échappées ;
- une commande locale explicite finalise l'installation.

## Blocages avant fusion dans `master`

- la nouvelle CI doit terminer avec succès ;
- le build Vue doit confirmer la compatibilité du formulaire client simplifié ;
- les migrations doivent être testées sur SQLite et MariaDB ;
- un devis et une facture PDF doivent être générés manuellement ;
- l'interface entreprise doit être testée lors d'une erreur réseau ;
- le stockage de l'IBAN et du BIC doit être chiffré avant production.

## Blocages avant commercialisation

- Laravel 8 et PHP 8.1 ne doivent pas rester le socle de production ;
- le verrou NPM doit être régénéré avec une chaîne frontend supportée ;
- les factures finalisées doivent devenir immuables et les corrections passer par des avoirs ;
- l'isolation multi-entreprises doit avoir des tests d'intégration dédiés ;
- la génération Factur-X doit être validée par XSD et règles métier ;
- les sauvegardes et la configuration SMTP doivent être gérées par l'exploitant, pas par les locataires ;
- les coordonnées bancaires et secrets doivent être chiffrés au repos.

La branche reste une branche de développement. Elle ne doit pas encore être déployée auprès de clients réels.
