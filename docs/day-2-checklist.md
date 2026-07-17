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
- [x] Formulaire client simplifié et enrichi avec les champs français.
- [x] Distinction client professionnel / particulier ajoutée.
- [x] Validation serveur des identifiants clients ajoutée.
- [x] Commande Artisan de configuration française ajoutée.
- [x] Numérotation recommandée FAC / DEV / REG préparée.
- [x] Fuseau Europe/Paris et formats de dates français préparés.
- [x] Générateur de mentions légales françaises créé.
- [x] Mention « TVA non applicable, art. 293 B du CGI » prise en charge.
- [x] Mentions légales entreprise et client injectées dans les formats PDF.
- [x] Placeholders PDF SIREN, SIRET, TVA, APE, IBAN et BIC ajoutés.
- [x] Tests unitaires SIREN, SIRET et mentions légales ajoutés.

## Validation à effectuer sur le poste de développement

- [ ] Exécuter la migration sur MySQL.
- [ ] Exécuter la migration sur MariaDB si cette base reste supportée.
- [ ] Vérifier la création et la modification d'un client depuis l'interface.
- [ ] Générer un devis PDF avec société assujettie à la TVA.
- [ ] Générer un devis PDF avec franchise en base de TVA.
- [ ] Générer une facture PDF avec les coordonnées légales et bancaires attendues.
- [ ] Exécuter les tests PHP et le build Vue/Vite.

## Reporté au chantier Factur-X

- [ ] Créer le snapshot immuable de l'identité vendeur et acheteur.
- [ ] Reporter les identifiants légaux dans le XML Factur-X.
- [ ] Ajouter une validation complète de checksum IBAN avant affichage sur facture finale.

## Commandes de validation

```bash
php artisan migrate
php artisan autofacture:apply-french-defaults
php artisan test
npm run test
npm run build
```
