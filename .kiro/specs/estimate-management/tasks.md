# Implementation Plan: Gestion des Devis (`estimate-management`)

## Overview

Ce plan convertit le design en étapes de code incrémentales. Le backend (Laravel 11 / PHP) et le frontend (Nuxt 3 / TypeScript) sont traités séparément, puis intégrés. Chaque tâche s'appuie sur les précédentes et se termine par le câblage complet du système.

---

## Tasks

- [x] 1. Compléter le schéma de la base de données (migration alter)
  - [x] 1.1 Créer la migration `alter` pour la table `estimates`
    - Créer `database/migrations/YYYY_MM_DD_XXXXXX_add_missing_columns_to_estimates_table.php`
    - Ajouter les colonnes : `client_email` (string, NOT NULL), `client_phone` (string nullable), `status` (enum `pending`/`approved`/`rejected`, default `pending`), `total_amount` (decimal 10,2, default 0)
    - La méthode `down()` doit supprimer ces colonnes
    - _Requirements: 1.1, 1.2, 1.4_

  - [ ]* 1.2 Écrire le test de propriété PHPUnit — Property 1 : statut par défaut
    - **Property 1: default status is pending**
    - **Validates: Requirements 1.2**
    - Utiliser eris/eris avec 100 itérations minimum
    - Générer des payloads valides sans champ `status` et vérifier que le devis créé a `status = 'pending'`

  - [ ]* 1.3 Écrire le test de propriété PHPUnit — Property 2 : suppression en cascade
    - **Property 2: cascade delete removes items**
    - **Validates: Requirements 1.3**
    - Générer N lignes aléatoires, supprimer le devis parent, vérifier qu'aucune ligne `estimate_items` ne subsiste

- [x] 2. Finaliser le modèle Eloquent `Estimate`
  - [x] 2.1 Mettre à jour `app/Models/Estimate.php`
    - Compléter `$fillable` : ajouter `client_email`, `client_phone`, `status`, `total_amount`
    - Ajouter `$casts` : `estimate_date => 'date'`, `total_amount => 'decimal:2'`
    - Vérifier que la relation `hasMany(EstimateItem::class)` est présente
    - _Requirements: 2.1, 2.2, 2.4_

  - [x] 2.2 Vérifier `app/Models/EstimateItem.php`
    - Confirmer que `$fillable` contient `estimate_id`, `description`, `quantity`, `unit_price`
    - Confirmer que la relation `belongsTo(Estimate::class)` est présente
    - _Requirements: 2.3_

- [x] 3. Corriger le namespace et finaliser `EstimateController`
  - [x] 3.1 Corriger le namespace dans `app/Http/Controllers/Api/EstimateController.php`
    - Changer `namespace App\Http\Controllers;` en `namespace App\Http\Controllers\Api;`
    - Ajouter `use Illuminate\Database\Eloquent\ModelNotFoundException;` si nécessaire
    - _Requirements: 3.1, 3.2_

  - [x] 3.2 Compléter la méthode `store()` dans `EstimateController`
    - Ajouter les règles de validation manquantes : `client_email` (required|email|max:255), `client_phone` (nullable|string|max:20), `items.*.description` (required|string|max:255), `items` (required|array|min:1)
    - Calculer `total_amount = collect($validated['items'])->sum(fn($item) => $item['quantity'] * $item['unit_price'])`
    - Persister `client_email`, `client_phone`, `status` (défaut `pending`), `total_amount` lors de `Estimate::create()`
    - Retourner HTTP 201 avec `estimate_id`
    - _Requirements: 3.3, 3.4, 3.5, 4.1_

  - [x] 3.3 Compléter la méthode `index()` dans `EstimateController`
    - Remplacer `Estimate::with('items')->get()` par une requête paginée : `Estimate::with('items')->orderBy('created_at', 'desc')->paginate(15)`
    - Retourner la réponse JSON paginée
    - _Requirements: 3.6_

  - [x] 3.4 Vérifier les méthodes `updateStatus()` et `destroy()` dans `EstimateController`
    - Confirmer que `findOrFail()` est utilisé (retourne 404 automatiquement si non trouvé)
    - Confirmer que `updateStatus()` retourne HTTP 200
    - Confirmer que `destroy()` retourne HTTP 200
    - _Requirements: 3.7, 3.8, 3.9, 3.10_

  - [ ]* 3.5 Écrire les tests unitaires PHPUnit pour `EstimateController`
    - Créer `tests/Feature/EstimateControllerTest.php`
    - Tester : payload valide → 201 + `estimate_id`, email invalide → 422, `items` vide → 422, `quantity` = 0 → 422, `updateStatus` ID inexistant → 404, `destroy` ID inexistant → 404, `GET /api/estimates` sans token → 401, présence de `total_amount` dans la réponse JSON
    - _Requirements: 3.3, 3.4, 3.5, 3.8, 3.10, 4.2, 5.3_

  - [ ]* 3.6 Écrire le test de propriété PHPUnit — Property 3 : calcul du MontantTotal
    - **Property 3: total_amount = sum(qty * unit_price)**
    - **Validates: Requirements 4.1, 4.3**
    - Générer des séquences aléatoires de lignes (quantité positive, prix ≥ 0), créer le devis, vérifier `total_amount` persisté

  - [ ]* 3.7 Écrire le test de propriété PHPUnit — Property 4 : présence de `total_amount`
    - **Property 4: total_amount present in all responses**
    - **Validates: Requirements 4.2**
    - Vérifier que `total_amount` est présent dans la réponse de `POST /api/estimates` et dans chaque élément de `GET /api/estimates`

  - [ ]* 3.8 Écrire le test de propriété PHPUnit — Property 5 : rejet des payloads invalides
    - **Property 5: invalid payloads return 422**
    - **Validates: Requirements 3.4, 3.5**
    - Générer des payloads avec au moins une violation (email malformé, `items` vide, `quantity` < 1, `unit_price` < 0, `client_name` absent, `estimate_date` absente) et vérifier HTTP 422

  - [ ]* 3.9 Écrire le test de propriété PHPUnit — Property 6 : création réussie
    - **Property 6: valid payloads return 201**
    - **Validates: Requirements 3.3**
    - Générer des payloads valides aléatoires et vérifier HTTP 201 + `estimate_id` entier positif

  - [ ]* 3.10 Écrire le test de propriété PHPUnit — Property 7 : mise à jour du statut
    - **Property 7: status update persisted correctly**
    - **Validates: Requirements 3.7**
    - Pour chaque statut valide (`pending`, `approved`, `rejected`), vérifier que le statut persisté en base correspond au statut envoyé

  - [ ]* 3.11 Écrire le test de propriété PHPUnit — Property 8 : protection 401
    - **Property 8: unauthenticated requests return 401**
    - **Validates: Requirements 5.3**
    - Pour chacune des routes protégées, vérifier qu'une requête sans token retourne HTTP 401

  - [ ]* 3.12 Écrire le test de propriété PHPUnit — Property 9 : pagination et tri
    - **Property 9: pagination and ordering correct**
    - **Validates: Requirements 3.6**
    - Insérer N devis (N > 0), vérifier que la réponse contient au plus 15 éléments et que les éléments sont triés par `created_at` décroissant

- [x] 4. Checkpoint — Backend
  - Exécuter `php artisan migrate` et vérifier que la migration alter s'applique sans erreur
  - Exécuter `php artisan test` et s'assurer que tous les tests passent
  - Demander à l'utilisateur si des questions se posent avant de continuer.

- [x] 5. Configurer les routes API (`routes/api.php`)
  - [x] 5.1 Ajouter la route publique `POST /api/estimates`
    - Ajouter `Route::post('/estimates', [EstimateController::class, 'store']);` en dehors du groupe `auth:sanctum`
    - Vérifier que l'import `use App\Http\Controllers\Api\EstimateController;` est présent
    - _Requirements: 5.1, 5.2_

- [x] 6. Créer le store Pinia `stores/estimate.ts` (frontend)
  - [x] 6.1 Créer le fichier `stores/estimate.ts` avec les interfaces TypeScript et l'état initial
    - Définir les interfaces : `EstimateItem`, `EstimatePayload`, `Estimate`, `EstimateState`
    - Initialiser l'état : `estimates: []`, `pagination: null`, `submitting: false`, `loading: false`, `lastCreatedId: null`, `error: null`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

  - [x] 6.2 Implémenter l'action `submitEstimate(payload)` dans le store
    - Mettre `submitting` à `true` avant la requête, `false` dans le bloc `finally`
    - En cas de succès : stocker `lastCreatedId`, réinitialiser `error`
    - En cas d'échec : stocker le message d'erreur dans `error`
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 6.3 Implémenter les actions `fetchEstimates()`, `updateStatus()` et `deleteEstimate()` dans le store
    - `fetchEstimates(page?)` : mettre `loading` à `true`/`false`, stocker `estimates` et `pagination`
    - `updateStatus(id, status)` : mettre à jour le statut du devis correspondant dans `estimates` localement
    - `deleteEstimate(id)` : filtrer `estimates` pour retirer le devis supprimé
    - _Requirements: 6.5, 6.6, 6.7_

  - [ ]* 6.4 Écrire le test de propriété Vitest — Property 10 : cycle `submitting`
    - **Property 10: submitting state cycle**
    - **Validates: Requirements 6.2**
    - Utiliser fast-check pour simuler des appels `submitEstimate()` et vérifier que `submitting` passe à `true` puis revient à `false` (succès ou échec)

  - [ ]* 6.5 Écrire le test de propriété Vitest — Property 12 : retrait après suppression
    - **Property 12: deleted estimate removed from state**
    - **Validates: Requirements 6.7, 8.6**
    - Générer une liste aléatoire de devis dans l'état, appeler `deleteEstimate(id)`, vérifier que le devis n'est plus dans `estimates`

- [ ] 7. Créer la page publique `pages/devis.vue` (frontend)
  - [x] 7.1 Créer le squelette de `pages/devis.vue` avec les champs client et la structure de base
    - Ajouter les champs : `client_name`, `client_email`, `client_phone`, `estimate_date`
    - Connecter le composant au store `useEstimateStore()`
    - Gérer l'état `submitting` pour désactiver le bouton de soumission
    - _Requirements: 7.1, 7.6_

  - [x] 7.2 Implémenter le sélecteur de produits et la gestion des lignes de devis dans `pages/devis.vue`
    - Charger les produits depuis `GET /api/products` au montage du composant
    - Permettre l'ajout d'un produit (pré-remplir description et `unit_price`)
    - Permettre la modification de la quantité et la suppression d'une ligne
    - _Requirements: 7.2, 7.3, 7.4_

  - [~] 7.3 Implémenter le calcul du total en temps réel et la soumission dans `pages/devis.vue`
    - Calculer le total via une propriété computed : `sum(quantity * unit_price)`
    - Appeler `submitEstimate()` à la soumission
    - Afficher le message de confirmation en cas de succès, les erreurs en cas d'échec
    - Valider côté client avant soumission (champs obligatoires, au moins une ligne)
    - _Requirements: 7.5, 7.6, 7.7, 7.8, 7.9_

  - [ ]* 7.4 Écrire le test de propriété Vitest — Property 11 : total côté client
    - **Property 11: client-side total calculation**
    - **Validates: Requirements 7.5**
    - Utiliser fast-check pour générer des listes de lignes aléatoires et vérifier que le total affiché correspond à `sum(quantity * unit_price)`

- [ ] 8. Créer la page admin `pages/admin/devis.vue` (frontend)
  - [x] 8.1 Créer `pages/admin/devis.vue` avec le middleware d'authentification et le tableau des devis
    - Ajouter le middleware `auth` (redirection si non authentifié)
    - Appeler `fetchEstimates()` au montage
    - Afficher le tableau avec les colonnes : `client_name`, `client_email`, `client_phone`, `estimate_date`, `total_amount`, `status`, nombre de lignes, actions
    - Afficher l'indicateur de chargement pendant `loading`
    - _Requirements: 8.1, 8.2, 8.7, 8.9_

  - [~] 8.2 Implémenter les badges de statut et la mise à jour inline dans `pages/admin/devis.vue`
    - Afficher un badge coloré par statut (`pending` → jaune, `approved` → vert, `rejected` → rouge)
    - Ajouter un sélecteur de statut inline qui appelle `updateStatus(id, status)` à la sélection
    - Mettre à jour l'affichage sans rechargement de page
    - _Requirements: 8.3, 8.4_

  - [~] 8.3 Implémenter la suppression avec confirmation et la gestion des erreurs dans `pages/admin/devis.vue`
    - Afficher une modale de confirmation avant d'appeler `deleteEstimate(id)`
    - Retirer le devis de la liste après suppression réussie
    - Afficher les erreurs API dans l'interface
    - _Requirements: 8.5, 8.6, 8.8_

- [~] 9. Checkpoint final — Ensemble du système
  - Exécuter `php artisan test` côté backend et vérifier que tous les tests passent
  - Exécuter `npx vitest run` côté frontend et vérifier que tous les tests passent
  - Vérifier que `POST /api/estimates` est accessible sans authentification
  - Vérifier que les routes admin retournent 401 sans token
  - Demander à l'utilisateur si des questions se posent avant de clore.

---

## Notes

- Les tâches marquées `*` sont optionnelles et peuvent être ignorées pour un MVP rapide
- Chaque tâche référence les exigences spécifiques pour la traçabilité
- Les tests de propriétés backend utilisent **eris/eris** (PHPUnit, 100 itérations minimum)
- Les tests de propriétés frontend utilisent **fast-check** (Vitest)
- Les checkpoints garantissent une validation incrémentale
- La migration `alter` (tâche 1.1) doit être exécutée avant tout test backend impliquant les colonnes ajoutées

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "1.2", "1.3"] },
    { "id": 2, "tasks": ["3.1"] },
    { "id": 3, "tasks": ["3.2", "3.3", "3.4"] },
    { "id": 4, "tasks": ["3.5", "3.6", "3.7", "3.8", "3.9", "3.10", "3.11", "3.12", "5.1"] },
    { "id": 5, "tasks": ["6.1"] },
    { "id": 6, "tasks": ["6.2", "6.3"] },
    { "id": 7, "tasks": ["6.4", "6.5", "7.1"] },
    { "id": 8, "tasks": ["7.2"] },
    { "id": 9, "tasks": ["7.3", "8.1"] },
    { "id": 10, "tasks": ["7.4", "8.2"] },
    { "id": 11, "tasks": ["8.3"] }
  ]
}
```
