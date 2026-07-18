# Validation visuelle du parcours des avoirs

Le 18 juillet 2026, le workflow `AutoFacture Visual Smoke` (run 36) a exécuté le parcours complet dans Chromium en bureau (1440 x 1000) et sur mobile (390 x 844).

Le parcours couvre la connexion, le tableau de bord, la liste et la fiche d’une facture finalisée, la création d’un avoir partiel de 300,00 EUR TTC, son scellement, la liste et la fiche des avoirs, ainsi que le téléchargement de son PDF A4.

Résultat : succès sans erreur JavaScript ni exception de page. Le PDF a été retourné avec le type `application/pdf`, puis rendu à 200 dpi pour contrôle visuel. Les montants contrôlés sont 250,00 EUR HT, 50,00 EUR de TVA et 300,00 EUR TTC. Le statut est `Émis et scellé`, le traitement est `Imputé sur la facture` et la facture d’origine datée est référencée.

Les défauts suivants ont été détectés puis corrigés pendant l’exécution : point d’entrée Vite absent, chemins des chunks et polices incorrects, domaine Sanctum incomplet sur le port 8000, contexte d’entreprise absent sur le PDF d’avoir, textes de connexion hérités de Crater, liste des avoirs non responsive, date de facture absente du PDF et pays de démonstration incorrect.

La CI AutoFacture 401 a également validé le backend Laravel, les migrations et tests, Pint, Larastan, le lint et le build Vue ainsi que la construction Docker sur le même état fonctionnel.
