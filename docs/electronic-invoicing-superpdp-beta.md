# AutoFacture — intégration SUPER PDP (bêta d’onboarding)

## Objectif de ce premier incrément

Cette version ajoute un module **Facturation électronique** directement dans AutoFacture. Elle prépare la connexion d’une entreprise à son propre compte SUPER PDP sans collecter ses pièces d’identité.

Le parcours proposé est volontairement simple :

1. le client crée lui-même son compte SUPER PDP ;
2. il ajoute et fait vérifier son entreprise chez SUPER PDP ;
3. il crée les codes de liaison pour AutoFacture ;
4. il les saisit dans l’écran sécurisé AutoFacture ;
5. AutoFacture les chiffre et ne renvoie jamais la clé secrète au navigateur.

## Fonctionnalités disponibles

- menu principal **Facturation électronique** ;
- assistant visuel en trois étapes ;
- progression mémorisée par entreprise ;
- informations d’entreprise déjà affichées dans le guide ;
- ouverture du portail et de la documentation SUPER PDP dans un nouvel onglet ;
- sélection `sandbox` ou `production` ;
- stockage chiffré du `client_id` et du `client_secret` ;
- identifiant masqué après enregistrement ;
- clé secrète absente de toutes les réponses API ;
- parcours OAuth Authorization Code avec état aléatoire à usage unique et expiration à dix minutes ;
- échange et renouvellement des jetons, stockés avec le chiffrement Laravel ;
- contrôle de session distant avant d'afficher l'état « Connecté » ;
- remplacement ou suppression de la liaison ;
- droits d’écriture réservés au propriétaire de l’entreprise ;
- architecture permettant d’ajouter ultérieurement une autre plateforme agréée.

## Limite volontaire de cette bêta

Cette version **ne transmet encore aucune facture réelle**.

Les URL exactes restent vides tant qu'elles n'ont pas été obtenues et validées dans le bac à sable officiel SUPER PDP :

- URL d'autorisation OAuth ;
- URL d'échange des jetons ;
- URL de contrôle de session ;
- portées OAuth autorisées ;
- contrat OpenAPI/AFNOR à utiliser ;
- identifiants de bac à sable AutoFacture.

AutoFacture ne doit pas deviner ces paramètres. Tant qu'ils ne sont pas configurés, l'autorisation est bloquée et l'interface indique clairement que la configuration serveur est incomplète. Aucun état « Connecté » n'est simulé.

## Variables d’environnement

```dotenv
FEATURE_ELECTRONIC_INVOICING=true
EINVOICING_DEFAULT_PROVIDER=superpdp
SUPERPDP_ENVIRONMENT=sandbox
SUPERPDP_PORTAL_URL=https://www.superpdp.tech
SUPERPDP_DOCUMENTATION_URL=https://www.superpdp.tech/documentation/
SUPERPDP_AUTHORIZE_URL=
SUPERPDP_TOKEN_URL=
SUPERPDP_SESSION_URL=
SUPERPDP_SCOPES=
SUPERPDP_TEST_URL=
SUPERPDP_CLIENT_ID_HEADER=
SUPERPDP_CLIENT_SECRET_HEADER=
SUPERPDP_TIMEOUT=10
```

Aucun identifiant réel ne doit être enregistré dans `.env.example`, GitHub, les journaux ou les captures d’écran.

## Architecture ajoutée

```text
app/Domain/ElectronicInvoicing/
├── Contracts/ElectronicInvoiceProvider.php
├── Data/ProviderConnectionResult.php
└── Providers/SuperPdpProvider.php

app/Models/ElectronicInvoiceConnection.php
app/Http/Controllers/V1/Admin/ElectronicInvoicing/
├── ElectronicInvoiceConnectionController.php
└── SuperPdpOAuthController.php

resources/scripts/admin/views/electronic-invoicing/Index.vue
resources/scripts/admin/stores/electronic-invoicing.js
```

La table `electronic_invoice_connections` contient une seule configuration par entreprise. Les secrets utilisent les casts chiffrés de Laravel et dépendent de `APP_KEY`.

## Sécurité

- ne jamais changer `APP_KEY` sans procédure de rotation : les secrets deviendraient illisibles ;
- ne jamais journaliser les en-têtes d’authentification ;
- ne jamais renvoyer le secret enregistré dans l’API ;
- exiger HTTPS en production ;
- séparer strictement le bac à sable de la production ;
- réserver l’ajout et la suppression des codes au propriétaire de l’entreprise ;
- ajouter plus tard une rotation des secrets et un journal d’audit dédié.

## Test bêta local

Depuis `AutoFacture_Test` :

```powershell
git config remote.origin.fetch "+refs/heads/*:refs/remotes/origin/*"
git fetch origin --prune
git switch --track origin/feature/einvoicing-superpdp-onboarding

docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
npm.cmd install --legacy-peer-deps
npm.cmd run build
docker compose restart app nginx scheduler
```

Ouvrir `http://localhost`, effectuer `Ctrl + F5`, puis choisir **Facturation électronique** dans la barre latérale.

Pour cette première bêta, utiliser uniquement des codes de démonstration, jamais des codes de production.

## Prochains incréments

1. obtenir un compte éditeur et les accès sandbox officiels ;
2. renseigner et valider le parcours OAuth existant avec le contrat officiel ;
3. brancher le contrat de données officiel de la plateforme agréée ;
4. générer et valider Factur-X EN16931 ;
5. transmettre et recevoir une facture de test ;
6. recevoir, signer et rejouer de façon idempotente les webhooks ;
7. activer les centres ventes, achats, anomalies et journal de preuve ;
8. ajouter l’e-reporting et les statuts de paiement ;
9. effectuer une revue de sécurité avant toute production.
