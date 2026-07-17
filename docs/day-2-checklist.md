# Checklist jour 2 — France et rebranding

## Réalisé

- [x] README rebrandé AutoFacture avec attribution Crater et AGPL.
- [x] Logo principal remplacé par AutoFacture.
- [x] Migration des champs légaux français ajoutée.
- [x] Validation serveur du SIREN, SIRET, TVA, IBAN et BIC.
- [x] Informations légales exposées dans `CompanyResource`.
- [x] Formulaire entreprise enrichi avec les champs français.
- [x] Modèle de saisie client enrichi.
- [x] Validation serveur des identifiants clients ajoutée.
- [x] Commande Artisan de configuration française ajoutée.
- [x] Numérotation recommandée FAC / DEV / REG préparée.
- [x] Fuseau Europe/Paris et formats de dates français préparés.

## À poursuivre dans le jour 2

- [ ] Ajouter les champs légaux au formulaire visuel de création client.
- [ ] Ajouter les traductions françaises dédiées dans `fr.json`.
- [ ] Vérifier la migration sur MySQL et MariaDB.
- [ ] Ajouter des tests de validation SIREN/SIRET/TVA/IBAN.
- [ ] Vérifier la persistance de tous les champs depuis l'interface.
- [ ] Préparer les mentions françaises à afficher sur devis et factures.
- [ ] Ajouter le cas « franchise en base de TVA » aux modèles PDF.
- [ ] Ajouter l'identité légale dans les futurs snapshots Factur-X.

## Commandes de validation

```bash
php artisan migrate
php artisan autofacture:apply-french-defaults
php artisan test
npm run test
npm run build
```
