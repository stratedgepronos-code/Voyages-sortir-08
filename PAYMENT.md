# Paiement & mode sandbox

## Page de paiement (créée dans le thème)

Tout a été mis en place dans le thème VS08 et le plugin. Il n'y a **pas de page séparée** à créer.  
**WooCommerce → Réglages → Avancé → Pages** : assignez la page « Paiement » (Checkout) à une page avec `[woocommerce_checkout]`. Le thème applique alors form-checkout.php, Paybox, récap. Après « Confirmation » du tunnel, redirection vers cette page (`wc_get_checkout_url()`).

- **WooCommerce → Réglages → Paiements** : activer Paybox (ou votre passerelle). Pour les tests : mode Sandbox / Test.
- Pour forcer une autre URL que la page Checkout : option « ID page » dans VS08 Réglages, ou filtre `vs08v_booking_checkout_redirect_url`.

## Erreur « Token invalide »

Correction appliquée : le formulaire envoie désormais le nonce de réservation (`vs08v_booking_nonce`). Si l'erreur réapparaît (cache, ancienne version) :

1. Vider le cache du site et du navigateur (Ctrl+Shift+R).
2. Ou activer le **mode sandbox** (voir ci‑dessous) pour ignorer la vérification du token en environnement de test.

## Mode sandbox (déverrouillage tests)

Pour tester sans être bloqué par le token de sécurité :

Dans **`wp-content/plugins/vs08-voyages/config.cfg`** (même fichier que pour la clé Duffel), ajouter :

```
VS08_SANDBOX_PAYMENT=1
```

En production, retirer cette ligne ou la mettre à `0`.
