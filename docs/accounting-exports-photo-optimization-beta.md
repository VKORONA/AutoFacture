# AutoFacture — Exports comptables et optimisation des photos

## Objectif

Cette version ajoute un espace **Comptabilité** permettant de préparer un lot exploitable par un cabinet comptable, tout en optimisant les photos ajoutées aux devis.

## Contenu d’un lot comptable

Chaque archive ZIP contient au minimum :

```text
ecritures-universelles.csv
journal-ventes-fec-compatible.txt
reglements.csv
clients.csv
manifest.json
rapport-controle.pdf
```

Selon le profil sélectionné, elle contient aussi :

```text
ecritures-pennylane.csv
ecritures-ebp.csv
ecritures-sage.csv
ecritures-cegid.csv
```

Lorsque l’option des justificatifs est activée :

```text
Factures/
Avoirs/
Reglements/
```

Lorsque les annexes commerciales sont explicitement activées, AutoFacture ajoute les photos WebP et les pièces jointes des devis transformés en factures.

## FEC-compatible, pas FEC réglementaire complet

Le fichier `journal-ventes-fec-compatible.txt` contient les 18 colonnes réglementaires, dans l’ordre attendu :

```text
JournalCode
JournalLib
EcritureNum
EcritureDate
CompteNum
CompteLib
CompAuxNum
CompAuxLib
PieceRef
PieceDate
EcritureLib
Debit
Credit
EcritureLet
DateLet
ValidDate
Montantdevise
Idevise
```

Il est encodé en UTF-8 et séparé par tabulations.

AutoFacture ne présente pas ce fichier comme un FEC réglementaire complet. Il contient uniquement les écritures produites par le logiciel : ventes, avoirs et règlements. Un FEC officiel doit contenir l’intégralité de la comptabilité de l’exercice.

## Contrôles avant génération

Le lot est refusé lorsque :

- les débits et crédits ne sont pas égaux ;
- la période est invalide ;
- le profil n’est pas pris en charge ;
- l’archive ne peut pas être créée.

Le rapport de contrôle contient :

- le numéro unique du lot ;
- la période ;
- le profil ;
- les nombres de factures, avoirs, règlements et écritures ;
- le total débit ;
- le total crédit ;
- l’écart ;
- les éventuels avertissements liés aux pièces justificatives.

Le manifeste JSON contient une empreinte SHA-256 des fichiers produits. L’archive possède également sa propre empreinte SHA-256 enregistrée dans AutoFacture.

## Paramètres à faire valider par le comptable

- mode engagement ou recettes/dépenses ;
- journal de ventes ;
- journal de banque ;
- compte client collectif ;
- compte de ventes de prestations ;
- compte de ventes de marchandises ;
- compte bancaire ;
- compte des écarts d’arrondis ;
- comptes de TVA collectée pour 20 %, 10 %, 5,5 % et 2,1 %.

Les auxiliaires clients sont générés sous la forme `C00000001`.

## Règles documentaires

- seules les factures finalisées sont exportées ;
- seules les notes de crédit émises et scellées sont exportées ;
- les brouillons sont exclus ;
- les avoirs inversent les écritures de ventes ;
- les règlements débitent le compte bancaire et créditent le compte client ;
- un ajustement d’arrondi est ajouté uniquement lorsqu’un document présente un écart de centime.

## Optimisation des photos de devis

À l’import, chaque image est :

1. lue comme une véritable image ;
2. réorientée selon les informations EXIF ;
3. réencodée, ce qui supprime les métadonnées et coordonnées GPS ;
4. placée dans un cadre blanc 4:3 sans déformation ;
5. convertie en dérivés optimisés.

Dérivés créés :

| Usage | Format | Dimensions | Taille cible |
|---|---|---:|---:|
| Interface principale | WebP | 1600 × 1200 | 700 Ko |
| Aperçu | WebP | 800 × 600 | 260 Ko |
| Miniature | WebP | 320 × 240 | 80 Ko |
| PDF | JPEG | 1600 × 1200 | 900 Ko |

Si WebP n’est pas disponible dans l’environnement PHP, AutoFacture utilise un JPEG optimisé pour les trois variantes Web. Le PDF utilise toujours le dérivé JPEG dédié.

La compression diminue progressivement la qualité jusqu’à la taille cible, sans descendre sous un seuil de qualité défini. Le fichier original n’est pas conservé après traitement.

## Protection contre les doublons

Une empreinte SHA-256 est calculée sur l’image normalisée. Deux photos identiques ne peuvent pas être ajoutées deux fois à la même ligne de devis.

## Limites actuelles

- les profils EBP, Sage, Pennylane et Cegid fournissent un CSV préformaté ; le paramétrage final des colonnes doit être validé avec le cabinet et la version exacte de son logiciel ;
- Factur-X n’est pas encore inclus dans le lot tant que le générateur EN16931 n’est pas terminé ;
- les familles de produits ne sont pas encore reliées à des comptes de ventes distincts ;
- les écritures d’achats, de paie, d’inventaire et d’opérations diverses ne sont pas produites.

## Test local

```powershell
git fetch origin --prune
git switch --track origin/feature/accounting-exports-photo-optimization

docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
npm.cmd install --legacy-peer-deps
npm.cmd run build
docker compose restart app nginx scheduler
```

Ouvrir ensuite `http://localhost`, effectuer `Ctrl + F5`, puis choisir **Comptabilité** dans la barre latérale.
