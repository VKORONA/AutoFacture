# Module Déclaration micro-entrepreneur — bêta

## Objectif

Le module prépare une déclaration de chiffre d’affaires pour une entreprise au régime micro en séparant les encaissements selon leur nature :

- vente de marchandises ou fourniture de biens — BIC ;
- prestation commerciale ou artisanale — BIC ;
- activité libérale non réglementée — BNC ;
- activité libérale réglementée relevant de la Cipav — BNC.

La classification est enregistrée sur le produit du catalogue, puis copiée sur chaque ligne de devis et de facture. Cette photographie reste attachée au document : modifier ultérieurement la fiche produit ne requalifie pas les anciennes factures.

## Base de calcul

Le chiffre d’affaires préparé est calculé à partir des règlements réellement enregistrés dans AutoFacture, à la date d’encaissement.

Pour une facture partiellement payée, le règlement TTC est ramené proportionnellement au chiffre d’affaires HT de la facture, puis ventilé entre les catégories selon le poids des lignes. Les remises de facture sont prises en compte. La TVA collectée n’entre pas dans le chiffre d’affaires micro déclaré.

Les charges ou dépenses professionnelles ne sont pas soustraites du chiffre d’affaires à déclarer.

## Barème versionné

Le barème local est stocké dans `config/micro-entrepreneur.php` par date d’entrée en vigueur. La première table est datée du 1er janvier 2026.

Le module distingue :

- le taux de cotisations sociales ;
- la contribution à la formation professionnelle ;
- le versement libératoire de l’impôt, lorsqu’il est activé ;
- l’ACRE, avec une date de fin et un facteur de taux configurables.

Le barème affiché est une estimation préparatoire. Le décompte émis par l’Urssaf reste la référence opposable. Les taux peuvent être remplacés par entreprise grâce aux surcharges de barème, sans modifier le code.

## Ajustements

Un ajustement manuel signé permet d’intégrer :

- un encaissement antérieur importé ;
- un règlement reçu hors AutoFacture ;
- un remboursement ou une annulation ;
- un reclassement exceptionnel.

Chaque ajustement conserve sa date, sa catégorie, son montant, son motif et son auteur.

## Données non classées

Un règlement sans facture ou dont les données sont insuffisantes est exclu du calcul et signalé dans le tableau. Il doit être rattaché à une facture classée ou régularisé par un ajustement manuel.

## Télétransmission Urssaf

La génération du tableau et du CSV fonctionne sans accès externe.

L’envoi direct à l’Urssaf reste désactivé par défaut. L’API Tierce Déclaration auto-entrepreneur nécessite une habilitation officielle et des identifiants attribués au logiciel agissant comme tiers déclarant. Les variables suivantes sont donc vides dans `.env.example` :

```dotenv
URSSAF_MICRO_DECLARATION_ENABLED=false
URSSAF_MICRO_DECLARATION_BASE_URL=
URSSAF_MICRO_DECLARATION_CLIENT_ID=
URSSAF_MICRO_DECLARATION_CLIENT_SECRET=
```

Aucun bouton d’envoi définitif ne doit être activé avant l’obtention et la validation de cette habilitation. Une future intégration devra également exiger une confirmation explicite de l’utilisateur pour chaque déclaration.

## Parcours de test

1. Ouvrir **Produits & services** et créer au moins un article dans chacune des catégories utilisées.
2. Créer une facture avec des lignes de vente et de prestation.
3. Enregistrer un règlement partiel ou total.
4. Ouvrir **Déclaration micro**.
5. Vérifier la période, la ventilation, les taux et le montant provisionné.
6. Ajouter un ajustement manuel de test puis le supprimer.
7. Exporter le CSV annuel.
8. Vérifier que le statut de télétransmission indique **Habilitation requise**.

## Limites de la bêta

- aucune récupération des déclarations déjà déposées sur le portail Urssaf ;
- aucune télétransmission réelle ;
- aucun prélèvement bancaire ;
- aucun calcul de l’impôt sur le revenu hors versement libératoire ;
- aucun conseil juridique, social ou fiscal personnalisé ;
- les règlements non enregistrés dans AutoFacture doivent être ajoutés manuellement.
