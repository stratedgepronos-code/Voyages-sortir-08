# VS08 Circuits — Tour Opérateur

Plugin WordPress pour gérer des **circuits** (voyages organisés) : back-office, catégories pour le moteur de recherche, fiches circuits et liste avec filtres.

## Installation

1. Activer le plugin **VS08 Circuits** dans Extensions.
2. Créer les **catégories** (Destinations, Thèmes, Durées) dans **Circuits → Destinations / Thèmes / Durées**.
3. Ajouter des circuits : **Circuits → Ajouter un circuit**.

## Utilisation

- **Shortcode** : `[vs08_circuits]` sur une page pour afficher la liste des circuits avec filtres (destination, thème, durée, budget, tri).
- **Archive** : l’URL `/circuits/` liste aussi les circuits (avec les mêmes filtres possibles).
- **Fiche circuit** : chaque circuit a une page dédiée (titre, description, itinéraire jour par jour, inclus / non inclus, prix par période, bouton « Réserver »).

## Prix par période

Les prix sont saisis **par période** (du / au + prix par personne). Le front affiche « À partir de X € » (prix minimum des périodes). La recherche de vol reste à brancher sur votre tunnel de réservation (lien « Réserver ce circuit » pointe vers `/reservation/?circuit=ID`).

## Intégration vols

Le lien de réservation peut être adapté pour ouvrir le tunnel existant (vs08-voyages) avec le paramètre `circuit` et préremplir la recherche de vol ; les prix du circuit restent par période côté circuit.
