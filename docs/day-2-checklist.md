# Checklist jour 2 — France et rebranding

## Réalisé

- [x] README rebrandé AutoFacture avec attribution Crater et AGPL.
- [x] Logo principal remplacé par AutoFacture.
- [x] Migration des champs légaux français ajoutée.
- [x] Validation serveur du SIREN, SIRET, TVA, IBAN et BIC.
- [x] Contrôle de somme SIREN et SIRET ajouté.
- [x] Informations légales exposées dans `CompanyResource`.
- [x] Informations légales exposées dans `CustomerResource`.
- [x] Formulaire entreprise enrichi avec les champs français.
- [x] Modèle de saisie client enrichi.
- [x] Validation serveur des identifiants clients ajoutée.
- [x] Commande Artisan de configuration française ajoutée.
- [x] Numérotation recommandée FAC / DEV / REG préparée.
- [x] Fuseau Europe/Paris et formats de dates français préparés.
- [x] Générateur de mentions légales françaises créé.
- [x] Mention « TVA non applicable, art. 293 B du CGI » prise en charge.
- [x] Tests unitaires SIREN, SIRET et mentions légales ajoutés.

## À poursuivre dans le jour 2

- [ ] Ajouter les champs légaux au formulaire visuel de création client.
- [ ] Ajouter les traductions françaises dédiées dans `fr.json`.
- [ ] Vérifier la migration sur MySQL et MariaDB.
- [ ] Ajouter une validation complète de checksum IBAN.
- [ ] Vérifier la persistance de tous les champs depuis l'interface.
- [ ] Injecter les mentions françaises dans les modèles PDF existants.
- [ ] Ajouter l'identité légale dans les futurs snapshots Factur-X.

## Commandes de validation

```bash
php artisan migrate
php artisan autofacture:apply-french-defaults
php artisan test
npm run test
npm run build
```
