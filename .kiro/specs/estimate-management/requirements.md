# Requirements Document

## Introduction

Ce document décrit les exigences fonctionnelles du système de gestion des devis pour l'application de vente de carrelage Ayomide. Le système permet à un client de soumettre une demande de devis en ligne en sélectionnant des produits du catalogue, et à l'administrateur de consulter, gérer et mettre à jour le statut de ces devis depuis un espace sécurisé.

Le backend est développé avec Laravel 11 (API REST, authentification Sanctum). Le frontend est développé avec Nuxt 3 (store Pinia). La base de données utilisée en développement est SQLite.

---

## Glossary

- **Système** : l'ensemble de l'application (backend Laravel + frontend Nuxt 3).
- **API** : le backend Laravel exposant les endpoints REST.
- **Client** : un visiteur non authentifié qui soumet une demande de devis via le formulaire public.
- **Administrateur** : un utilisateur authentifié via Sanctum qui gère les devis depuis l'interface d'administration.
- **Devis** : une demande de devis soumise par un Client, contenant ses coordonnées et une liste de lignes de produits.
- **LigneDevis** : une ligne d'un Devis, associant une description de produit, une quantité et un prix unitaire.
- **Statut** : l'état d'avancement d'un Devis, pouvant prendre les valeurs `pending` (en attente), `approved` (approuvé) ou `rejected` (rejeté).
- **MontantTotal** : la somme calculée de toutes les LigneDevis d'un Devis (quantité × prix unitaire pour chaque ligne).
- **EstimateController** : le contrôleur Laravel situé dans le namespace `App\Http\Controllers\Api` qui gère les opérations sur les Devis.
- **EstimateStore** : le store Pinia du frontend gérant l'état et les actions liées aux Devis.
- **FormulaireDevis** : la page publique Nuxt 3 permettant au Client de composer et soumettre un Devis.
- **PageAdminDevis** : la page d'administration Nuxt 3 (`pages/admin/devis.vue`) permettant à l'Administrateur de gérer les Devis.
- **Catalogue** : la liste des produits disponibles exposée par l'API via `GET /api/products`.

---

## Requirements

### Requirement 1: Compléter le schéma de la base de données

**User Story:** En tant que développeur, je veux que la table `estimates` contienne tous les champs nécessaires, afin que les données d'un Devis soient complètes et exploitables.

#### Acceptance Criteria

1. THE **API** SHALL stocker pour chaque Devis les champs `client_name` (string, obligatoire), `client_email` (string, obligatoire), `client_phone` (string, optionnel), `estimate_date` (date, obligatoire), `status` (enum `pending`/`approved`/`rejected`, défaut `pending`) et `total_amount` (decimal 10,2, défaut 0).
2. THE **API** SHALL appliquer la valeur par défaut `pending` au champ `status` lors de la création d'un Devis.
3. THE **API** SHALL stocker pour chaque LigneDevis les champs `estimate_id` (clé étrangère), `description` (string), `quantity` (integer) et `unit_price` (decimal 10,2), avec suppression en cascade lorsque le Devis parent est supprimé.
4. WHEN une migration de modification de la table `estimates` est exécutée, THE **API** SHALL ajouter les colonnes `client_email`, `client_phone`, `status` et `total_amount` sans perte de données existantes.

---

### Requirement 2: Finaliser le modèle Eloquent Estimate

**User Story:** En tant que développeur, je veux que le modèle `Estimate` reflète tous les champs de la table, afin que les opérations de création et de mise à jour fonctionnent correctement.

#### Acceptance Criteria

1. THE **API** SHALL inclure `client_name`, `client_email`, `client_phone`, `estimate_date`, `status` et `total_amount` dans la liste `$fillable` du modèle `Estimate`.
2. THE **API** SHALL exposer une relation `hasMany` vers `EstimateItem` depuis le modèle `Estimate`.
3. THE **API** SHALL exposer une relation `belongsTo` vers `Estimate` depuis le modèle `EstimateItem`.
4. THE **API** SHALL caster le champ `total_amount` en type `decimal:2` et le champ `estimate_date` en type `date` dans le modèle `Estimate`.

---

### Requirement 3: Corriger le namespace et finaliser l'EstimateController

**User Story:** En tant que développeur, je veux que l'`EstimateController` soit dans le bon namespace et soit pleinement fonctionnel, afin que les routes API puissent le résoudre correctement.

#### Acceptance Criteria

1. THE **EstimateController** SHALL être déclaré dans le namespace `App\Http\Controllers\Api`.
2. THE **EstimateController** SHALL étendre `App\Http\Controllers\Controller`.
3. WHEN une requête `POST /api/estimates` est reçue avec des données valides, THE **EstimateController** SHALL créer un Devis, créer les LigneDevis associées, calculer le MontantTotal et retourner une réponse HTTP 201 contenant l'identifiant du Devis créé.
4. WHEN une requête `POST /api/estimates` est reçue, THE **EstimateController** SHALL valider que `client_name` est une chaîne de 255 caractères maximum, que `client_email` est une adresse email valide de 255 caractères maximum, que `client_phone` est optionnel et de 20 caractères maximum, que `estimate_date` est une date valide, que `items` est un tableau non vide, et que chaque élément de `items` contient `description` (string, max 255), `quantity` (entier ≥ 1) et `unit_price` (numérique ≥ 0).
5. IF la validation de la requête `POST /api/estimates` échoue, THEN THE **EstimateController** SHALL retourner une réponse HTTP 422 avec le détail des erreurs de validation.
6. WHEN une requête `GET /api/estimates` est reçue d'un Administrateur authentifié, THE **EstimateController** SHALL retourner la liste paginée des Devis avec leurs LigneDevis, triée par date de création décroissante, avec 15 éléments par page.
7. WHEN une requête `PATCH /api/estimates/{id}/status` est reçue d'un Administrateur authentifié avec un statut valide, THE **EstimateController** SHALL mettre à jour le Statut du Devis identifié et retourner une réponse HTTP 200.
8. IF le Devis ciblé par `PATCH /api/estimates/{id}/status` n'existe pas, THEN THE **EstimateController** SHALL retourner une réponse HTTP 404.
9. WHEN une requête `DELETE /api/estimates/{id}` est reçue d'un Administrateur authentifié, THE **EstimateController** SHALL supprimer le Devis et ses LigneDevis associées et retourner une réponse HTTP 200.
10. IF le Devis ciblé par `DELETE /api/estimates/{id}` n'existe pas, THEN THE **EstimateController** SHALL retourner une réponse HTTP 404.

---

### Requirement 4: Calcul automatique du MontantTotal

**User Story:** En tant qu'administrateur, je veux que le montant total d'un devis soit calculé automatiquement, afin de ne pas avoir à le saisir manuellement.

#### Acceptance Criteria

1. WHEN un Devis est créé via `POST /api/estimates`, THE **EstimateController** SHALL calculer le MontantTotal comme la somme de (`quantity` × `unit_price`) pour chaque LigneDevis et le persister dans le champ `total_amount` du Devis.
2. THE **API** SHALL retourner le champ `total_amount` dans toutes les réponses JSON relatives à un Devis.
3. FOR ALL Devis valides, le `total_amount` persisté SHALL être égal à la somme des produits `quantity × unit_price` de chaque LigneDevis associée (propriété d'invariant).

---

### Requirement 5: Configurer les routes API

**User Story:** En tant que développeur, je veux que toutes les routes des devis soient correctement déclarées dans `routes/api.php`, afin que les endpoints soient accessibles avec les bons niveaux de protection.

#### Acceptance Criteria

1. THE **API** SHALL exposer la route `POST /api/estimates` sans middleware d'authentification, permettant à tout Client de soumettre un Devis.
2. THE **API** SHALL exposer les routes `GET /api/estimates`, `PATCH /api/estimates/{id}/status` et `DELETE /api/estimates/{id}` sous le middleware `auth:sanctum`, réservant leur accès aux Administrateurs authentifiés.
3. IF une requête non authentifiée tente d'accéder à `GET /api/estimates`, `PATCH /api/estimates/{id}/status` ou `DELETE /api/estimates/{id}`, THEN THE **API** SHALL retourner une réponse HTTP 401.

---

### Requirement 6: Store Pinia — soumission d'un devis (frontend)

**User Story:** En tant que développeur frontend, je veux un store Pinia `estimate.ts` avec une action `submitEstimate()`, afin que le formulaire public puisse envoyer les données du devis à l'API Laravel.

#### Acceptance Criteria

1. THE **EstimateStore** SHALL exposer une action `submitEstimate(payload)` qui envoie une requête `POST /api/estimates` avec les données du Devis.
2. WHEN `submitEstimate(payload)` est appelée avec des données valides, THE **EstimateStore** SHALL mettre à jour un état `submitting` à `true` pendant la requête et à `false` à la fin.
3. WHEN la requête `POST /api/estimates` réussit, THE **EstimateStore** SHALL stocker l'identifiant du Devis créé dans un état `lastCreatedId` et réinitialiser le formulaire.
4. IF la requête `POST /api/estimates` échoue, THEN THE **EstimateStore** SHALL stocker le message d'erreur dans un état `error` et laisser `submitting` à `false`.
5. THE **EstimateStore** SHALL exposer une action `fetchEstimates()` qui envoie une requête `GET /api/estimates` avec le token Sanctum de l'Administrateur et stocke la liste paginée dans un état `estimates`.
6. THE **EstimateStore** SHALL exposer une action `updateStatus(id, status)` qui envoie une requête `PATCH /api/estimates/{id}/status` et met à jour le Statut du Devis correspondant dans l'état `estimates`.
7. THE **EstimateStore** SHALL exposer une action `deleteEstimate(id)` qui envoie une requête `DELETE /api/estimates/{id}` et retire le Devis correspondant de l'état `estimates`.

---

### Requirement 7: Formulaire public de demande de devis (frontend)

**User Story:** En tant que client, je veux pouvoir remplir un formulaire en ligne pour demander un devis en sélectionnant des produits du catalogue et en précisant les quantités, afin de recevoir une estimation du coût.

#### Acceptance Criteria

1. THE **FormulaireDevis** SHALL afficher les champs `client_name`, `client_email`, `client_phone` et `estimate_date`.
2. THE **FormulaireDevis** SHALL charger la liste des produits depuis le Catalogue via `GET /api/products` et permettre au Client d'ajouter des produits à son Devis.
3. WHEN le Client sélectionne un produit du Catalogue, THE **FormulaireDevis** SHALL pré-remplir la description et le prix unitaire de la LigneDevis correspondante.
4. THE **FormulaireDevis** SHALL permettre au Client d'ajouter plusieurs LigneDevis, de modifier la quantité de chacune et d'en supprimer.
5. THE **FormulaireDevis** SHALL afficher en temps réel le MontantTotal estimé calculé côté client comme la somme de (`quantity` × `unit_price`) pour chaque LigneDevis.
6. WHEN le Client soumet le formulaire, THE **FormulaireDevis** SHALL appeler `submitEstimate()` du **EstimateStore** et désactiver le bouton de soumission pendant l'envoi.
7. WHEN la soumission réussit, THE **FormulaireDevis** SHALL afficher un message de confirmation et réinitialiser le formulaire.
8. IF la soumission échoue, THEN THE **FormulaireDevis** SHALL afficher les messages d'erreur retournés par l'API sans réinitialiser les données saisies.
9. IF le Client tente de soumettre le formulaire sans avoir renseigné `client_name`, `client_email`, `estimate_date` ou sans avoir ajouté au moins une LigneDevis, THEN THE **FormulaireDevis** SHALL afficher des messages de validation et bloquer la soumission.

---

### Requirement 8: Page d'administration des devis (frontend)

**User Story:** En tant qu'administrateur, je veux une page d'administration qui affiche tous les devis reçus avec leurs détails, et me permette de changer leur statut ou de les supprimer, afin de gérer efficacement les demandes clients.

#### Acceptance Criteria

1. WHEN la **PageAdminDevis** est chargée par un Administrateur authentifié, THE **PageAdminDevis** SHALL appeler `fetchEstimates()` du **EstimateStore** et afficher la liste paginée des Devis.
2. THE **PageAdminDevis** SHALL afficher pour chaque Devis : `client_name`, `client_email`, `client_phone`, `estimate_date`, `total_amount`, `status` et le nombre de LigneDevis.
3. THE **PageAdminDevis** SHALL afficher le Statut de chaque Devis avec un indicateur visuel distinct pour chacune des trois valeurs (`pending`, `approved`, `rejected`).
4. WHEN l'Administrateur sélectionne un nouveau Statut pour un Devis, THE **PageAdminDevis** SHALL appeler `updateStatus(id, status)` du **EstimateStore** et mettre à jour l'affichage sans rechargement complet de la page.
5. WHEN l'Administrateur clique sur le bouton de suppression d'un Devis, THE **PageAdminDevis** SHALL afficher une confirmation avant d'appeler `deleteEstimate(id)` du **EstimateStore**.
6. WHEN la suppression est confirmée et réussit, THE **PageAdminDevis** SHALL retirer le Devis de la liste affichée sans rechargement complet de la page.
7. THE **PageAdminDevis** SHALL afficher un indicateur de chargement pendant les appels à l'API.
8. IF un appel API depuis la **PageAdminDevis** échoue, THEN THE **PageAdminDevis** SHALL afficher un message d'erreur explicite à l'Administrateur.
9. WHILE l'Administrateur n'est pas authentifié, THE **PageAdminDevis** SHALL rediriger vers la page de connexion.
