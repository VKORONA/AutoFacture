# AutoFacture

AutoFacture est un projet de SaaS français de devis et facturation électronique, conçu pour les auto-entrepreneurs et les petites entreprises.

## Objectif

- créer des devis et factures simplement ;
- transformer une description brute en prestation professionnelle ;
- générer des factures Factur-X ;
- permettre au client de choisir sa plateforme agréée ;
- proposer une assistance IA locale avec recours optionnel à GPT-5.4 nano ;
- conserver une offre commerciale très accessible.

## État du projet

Le développement du MVP se déroule sur la branche :

```text
develop/mvp-france-ai
```

Le périmètre et les décisions techniques sont documentés dans le dossier [`docs`](docs).

## Installation de développement

Consultez :

- [`docs/local-development.md`](docs/local-development.md)
- [`docs/day-1-audit.md`](docs/day-1-audit.md)
- [`docs/mvp-scope.md`](docs/mvp-scope.md)

Après installation et migration, les paramètres français peuvent être appliqués avec :

```bash
php artisan autofacture:apply-french-defaults
```

## Sécurité

Aucune clé API, donnée client, facture réelle ou sauvegarde ne doit être ajoutée au dépôt. Les fonctions IA sont désactivées par défaut.

## Base open source et licence

AutoFacture est dérivé de **Crater**, une application de facturation open source développée avec Laravel et Vue.js.

Projet d'origine : `crater-invoice-inc/crater`

Le code dérivé reste soumis à la **GNU Affero General Public License version 3 (AGPL-3.0)**. Les notices de licence et l'historique Git doivent être conservés. Les modifications utilisées par les utilisateurs à travers le réseau doivent être mises à disposition conformément à cette licence.

Voir le fichier [`LICENSE`](LICENSE).
