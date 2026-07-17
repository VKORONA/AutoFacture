# Périmètre MVP AutoFacture

## Promesse produit

Transformer une description ou une dictée imprécise en devis professionnel, signaler les oublis, puis générer une facture électronique exploitable sans imposer un ERP.

## Inclus dans le MVP

1. Comptes, entreprises et utilisateurs.
2. Clients français.
3. Articles et prestations.
4. Devis et conversion en facture.
5. Factures, paiements et avoirs simples.
6. Reformulation professionnelle.
7. Découpage automatique en lignes de devis.
8. Questions sur les informations manquantes.
9. Contrôle de clarté et détection d'oublis.
10. Relances préparées automatiquement.
11. PDF de devis et de facture.
12. Factur-X.
13. Export manuel vers la plateforme agréée choisie.
14. Architecture pour futurs connecteurs API.

## Hors MVP

- ERP complet.
- Comptabilité complète.
- Paie.
- Gestion de stock avancée.
- Rapprochement bancaire automatique.
- Application mobile native.
- Analyse automatique de photos de chantier.
- Connexion simultanée à toutes les plateformes agréées.
- Agent IA local obligatoire.

## Répartition des traitements

### Code déterministe

- Numérotation.
- Totaux, remises et TVA.
- Mentions obligatoires.
- Lecture XML Factur-X.
- Validation XSD/Schematron.
- Statuts et journal d'audit.

### Mini-IA locale

- Correction et reformulation courte.
- Classification métier.
- Relance simple.
- Extraction courte depuis du texte.

### GPT-5.4 nano

- Secours lorsque le résultat local est invalide ou insuffisant.
- Devis plus complexe.
- Analyse de messages longs.
- Extraction difficile.

Aucune IA ne calcule ni ne valide les montants fiscaux.
