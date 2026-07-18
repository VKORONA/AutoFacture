# Base de sécurité AutoFacture

## Secrets interdits dans Git

- `.env`
- clés OpenAI
- clés de plateformes agréées
- secrets Stripe
- mots de passe SMTP
- jetons OAuth
- sauvegardes de base de données
- documents clients

## Exigences avant mise en production

- `APP_ENV=production`
- `APP_DEBUG=false`
- clé d'application unique
- HTTPS obligatoire
- secrets chiffrés
- sauvegardes testées
- isolation multi-entreprises testée
- journal d'audit activé
- dépendances auditées
- politique de rétention des données définie

## Traitements IA

Les données ne sont envoyées à un fournisseur cloud que si le mode choisi par l'utilisateur l'autorise. Les calculs fiscaux, numérotations et validations réglementaires restent déterministes.

## Signalement

Ne jamais publier une facture réelle, une clé ou une donnée personnelle dans une issue publique. Utiliser uniquement des jeux de données fictifs pour reproduire un défaut.
