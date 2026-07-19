# AutoFacture — Photos par ligne et annexes de devis

## Objectif

Cette bêta permet d’illustrer chaque ligne d’un devis sans laisser la taille ou l’orientation des photos perturber la mise en page du document.

Elle ajoute également une annexe structurée et des pièces jointes séparées.

## Règles de mise en page

- quatre photos maximum par ligne de devis ;
- formats acceptés : JPG, JPEG, PNG et WebP ;
- taille maximale : 12 Mo par photo ;
- orientation EXIF corrigée automatiquement ;
- conversion en JPEG ;
- image principale normalisée en `1600 × 1200` ;
- aperçu normalisé en `800 × 600` ;
- miniature normalisée en `240 × 180` ;
- cadre blanc 4:3 conservant l’intégralité de la photo, sans étirement ;
- une seule miniature uniforme dans le tableau principal ;
- photos détaillées disposées deux par rangée dans une annexe paginée ;
- les lignes sans photo conservent la mise en page habituelle.

## Annexe au devis

Le formulaire de devis contient désormais :

- un titre d’annexe ;
- un texte d’introduction ;
- une option pour inclure ou masquer l’annexe photographique ;
- des pièces jointes PDF ou image ;
- une limite de 15 Mo par pièce jointe.

Les photos rattachées aux lignes sont intégrées dans le PDF du devis.

Les PDF annexes externes sont :

- répertoriés dans la dernière partie du devis ;
- conservés comme fichiers séparés ;
- joints automatiquement à l’e-mail avec le devis lorsque l’envoi est activé.

Cette séparation évite de modifier ou de corrompre la pagination du devis principal.

## Sécurité

- fichiers exécutables refusés ;
- validation conjointe de l’extension et du type MIME ;
- noms de stockage aléatoires ;
- téléchargement protégé par authentification et appartenance à l’entreprise ;
- accès en modification contrôlé par les droits du devis ;
- fichiers supprimés du stockage lors de la suppression du devis ;
- métadonnées d’interface retirées avant l’écriture SQL.

## Organisation des fichiers

```text
storage/app/private/estimate-assets/
└── {entreprise}/
    └── {devis-ou-jeton-brouillon}/
        ├── photos/
        │   └── {identifiant-ligne}/
        │       ├── image.jpg
        │       ├── image-preview.jpg
        │       └── image-thumb.jpg
        └── attachments/
            └── piece-jointe.pdf
```

Les fichiers sont rattachés au devis lors de son premier enregistrement grâce à un jeton de brouillon et à un identifiant UUID stable pour chaque ligne.

## Test bêta local

Depuis le dossier `AutoFacture_Test` :

```powershell
git config remote.origin.fetch "+refs/heads/*:refs/remotes/origin/*"
git fetch origin --prune
git switch --track origin/feature/estimate-line-photos-attachments

docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
npm.cmd install --legacy-peer-deps
npm.cmd run build
docker compose restart app nginx scheduler
```

Puis :

1. créer un devis avec au moins deux lignes ;
2. importer une photo portrait, une photo paysage et une photo de grande résolution ;
3. vérifier que toutes les miniatures ont exactement la même proportion ;
4. ajouter un titre et un texte d’annexe ;
5. joindre un PDF ;
6. enregistrer le devis ;
7. actualiser la page ;
8. vérifier la persistance des photos et de la pièce jointe ;
9. ouvrir le PDF ;
10. contrôler la miniature dans le tableau et les photos détaillées dans l’annexe ;
11. envoyer un e-mail de test et vérifier que le PDF annexe est joint séparément.

Pour le premier contrôle, utiliser uniquement des photos et documents sans données sensibles.
