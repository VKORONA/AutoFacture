# Cartographie des menus AutoFacture

## Source technique

Les menus sont définis dans `config/crater.php`, enregistrés dans `AppServiceProvider`, filtrés par `GeneratesMenuTrait`, puis renvoyés par `/api/v1/bootstrap` au store Pinia global.

## Menu principal conservé dans le MVP

| Fonction | Route | État MVP |
|---|---|---|
| Tableau de bord | `/admin/dashboard` | visible |
| Clients | `/admin/customers` | visible |
| Articles / prestations | `/admin/items` | visible |
| Devis | `/admin/estimates` | visible |
| Factures | `/admin/invoices` | visible |
| Paiements | `/admin/payments` | visible |
| Utilisateurs | `/admin/users` | visible |
| Paramètres | `/admin/settings` | visible |

## Menu principal masqué

| Fonction | Route | Raison |
|---|---|---|
| Factures récurrentes | `/admin/recurring-invoices` | hors périmètre initial |
| Dépenses | `/admin/expenses` | comptabilité avancée repoussée |
| Rapports | `/admin/reports` | interface à simplifier |
| Modules | `/admin/modules` | marketplace Crater non retenue |

## Paramètres conservés

- Compte
- Informations de l'entreprise
- Préférences
- Personnalisation
- Notifications
- Taxes
- Modes de paiement
- Notes
- Configuration du courrier
- Stockage des fichiers
- Sauvegardes

## Paramètres masqués

- Rôles avancés
- Fournisseur de taux de change
- Champs personnalisés
- Catégories de dépenses
- Mise à jour intégrée de Crater

## Principe retenu

Le code, les routes et les tables ne sont pas supprimés. La réponse du bootstrap filtre les menus selon `config/autofacture.php` et les variables `FEATURE_*`.

Cette solution permet de réactiver une fonction sans migration ni perte de données.

## Limite temporaire

Le masquage de menu n'est pas une autorisation de sécurité. Une route existante peut encore être atteinte directement par un utilisateur disposant des permissions Crater correspondantes. Un garde de fonctionnalité côté serveur sera ajouté avant la bêta pour bloquer aussi les routes des fonctions désactivées.
