# Audit de création des factures — 18 juillet 2026

## État initial

- `master` contenait encore le socle Crater historique.
- `develop/hardening-modernization` était la branche distante la plus avancée.
- aucune autre copie complète d’AutoFacture n’a été trouvée sur le PC contrôlé ;
- le dossier local historique `AUTOFACTURE` ne contenait ni dépôt Git, ni `composer.json`, ni `artisan` ;
- aucun secret potentiel n’a été détecté dans les fichiers comparés ;
- `README.md` et `readme.md` coexistaient dans GitHub et entraient en conflit sur Windows.

## Cause du comportement assimilé à une simulation

Le formulaire envoyait une vraie requête `POST /api/v1/invoices`. Le serveur enregistrait réellement la facture, mais le store Vue ajoutait `response.data.invoice` alors que la ressource Laravel standard était renvoyée sous `response.data.data`. L’état de l’interface pouvait donc être incohérent avec la base.

La chaîne de création présentait aussi les risques suivants :

- absence de transaction globale ;
- numéro calculé sans verrou de concurrence ;
- totaux acceptés depuis le navigateur ;
- absence d’idempotence ;
- erreur PDF non remontée explicitement.

## Correction retenue

- service transactionnel `InvoiceCreator` ;
- verrou de l’entreprise pendant l’attribution du numéro ;
- génération du numéro côté serveur ;
- validation du rattachement du client à l’entreprise active ;
- recalcul des totaux côté serveur ;
- clé `client_request_id` et contraintes uniques ;
- réponse API contenant `data` et le champ de compatibilité `invoice` ;
- métadonnées explicites pour le PDF et l’envoi ;
- job PDF en échec explicite lorsque le stockage échoue.

## Vérifications automatisées

Les tests ajoutés contrôlent :

1. l’enregistrement réel de la facture et de ses lignes ;
2. sa présence dans la liste et après relecture ;
3. l’attribution du numéro côté serveur ;
4. l’idempotence d’une requête répétée ;
5. le recalcul des totaux ;
6. l’isolation de l’entreprise ;
7. la conservation de la facture avec une erreur PDF explicite.

## Vérification manuelle avant fusion

Sur un environnement local ou de démonstration sans données de production :

1. créer une facture ;
2. vérifier son numéro et ses totaux ;
3. revenir à la liste ;
4. ouvrir la fiche ;
5. rafraîchir la page ;
6. ouvrir le PDF ;
7. vérifier qu’une erreur de stockage PDF est affichée clairement lorsqu’elle est provoquée.
