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

## Protection des accès directs

Le middleware global `EnsureFeatureIsEnabled` vérifie chaque requête avant son traitement.

- Une page d'administration désactivée redirige vers `/admin/dashboard`.
- Une route API désactivée répond en JSON avec le statut HTTP 404 et le code `FEATURE_DISABLED`.
- Les motifs d'URL protégés sont centralisés dans `config/autofacture.php`.
- Les tests couvrent une fonction active, une API désactivée, une page admin désactivée et un paramètre avancé désactivé.

Le masquage du menu et le blocage serveur utilisent donc la même source de configuration.
