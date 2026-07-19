# AutoFacture — Audit technique du jour 1

Date : 17 juillet 2026
Branche : `develop/mvp-france-ai`
Base : fork de `crater-invoice-inc/crater`

## Objectif

Établir une base reproductible et sûre avant d'ajouter les fonctions françaises, Factur-X et le copilote IA.

## État du socle

- Backend : Laravel 8
- PHP déclaré : 7.4 ou 8.0
- Frontend : Vue 3 ancien, Vite 2, Tailwind 3
- API/authentification : Laravel Sanctum
- PDF : Dompdf
- Base : MySQL/MariaDB
- Paiement : Stripe
- Licence du dépôt : AGPL-3.0

## Risques bloquants identifiés

1. Projet amont très peu maintenu depuis 2023.
2. Dépendances Composer et NPM anciennes.
3. `.env.example` contenait une clé d'application fixe, un mode production avec debug actif et des identifiants par défaut.
4. Images Docker non épinglées ou anciennes.
5. Absence de CI visible sur le commit de base.
6. Cloisonnement multi-entreprises à auditer avant toute ouverture SaaS.
7. Factures finalisées, avoirs et numérotation française à sécuriser avant commercialisation.
8. Licence AGPL à respecter pour le fork et ses modifications accessibles par le réseau.

## Décisions du jour 1

- Ne pas ajouter d'IA avant d'avoir un socle installable et testable.
- Conserver temporairement Laravel 8 pour obtenir rapidement un MVP.
- Utiliser PHP 8.0 pour le premier démarrage reproductible, puis préparer une migration progressive.
- Garder `master` intact ; tous les travaux sont réalisés sur `develop/mvp-france-ai`.
- Interdire toute clé secrète dans Git.
- Préparer dès maintenant les variables des futurs fournisseurs IA, désactivées par défaut.
- Ne pas supprimer physiquement de modules métier avant d'avoir validé leurs dépendances ; ils seront d'abord masqués par configuration lors du chantier UX.

## Contrôles à exécuter localement

```bash
composer validate --strict
composer audit
npm audit --production
php artisan test
npm run test
npm run build
```

Les résultats doivent être copiés dans une issue GitHub dédiée, sans clé ni donnée personnelle.

## Modules à conserver pour le MVP

- Entreprises et utilisateurs
- Clients
- Articles/prestations
- Devis
- Factures
- Paiements
- Modèles PDF
- Envoi par courriel
- Paramètres

## Modules à masquer dans la première interface

- Rapports avancés
- Dépenses avancées
- Facturation récurrente
- Portail et fonctions non indispensables à la démonstration
- Modules expérimentaux ou non finalisés

Le masquage sera effectué après identification précise des routes et composants afin de ne pas casser les relations existantes.

## Critères de fin du jour 1

- Branche de développement créée.
- Configuration exemple sécurisée.
- Documentation d'installation locale ajoutée.
- Script d'audit des dépendances ajouté.
- CI minimale ajoutée.
- Périmètre fonctionnel du MVP documenté.
- Aucun changement métier irréversible.
