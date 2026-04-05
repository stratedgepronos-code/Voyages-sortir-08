# VS08 Theme — Déploiement sur Hostinger via Cursor

## Contexte
Le thème `vs08-theme` sur sortirmonde.fr a régressé (page d'accueil revenue à une version antérieure).
Ce ZIP contient la version correcte de tous les fichiers du thème.

## Serveur cible
- **Hébergeur** : Hostinger
- **Site** : sortirmonde.fr
- **Chemin FTP** : `public_html/wp-content/themes/vs08-theme/`
- **Protocole** : SFTP (port 65002) ou FTP classique

## Instructions pour Cursor

### Option 1 — SFTP via terminal intégré

```bash
# 1. Dézipper le thème localement
unzip vs08-theme.zip -d vs08-theme-deploy/

# 2. Se connecter en SFTP à Hostinger
sftp -P 65002 u123456789@sortirmonde.fr

# 3. Naviguer vers le dossier thème
cd public_html/wp-content/themes/vs08-theme/

# 4. Uploader tous les fichiers PHP (les plus importants)
put front-page.php
put header-mega-new.php
put footer.php
put functions.php
put single-vs08_voyage.php
put header.php
put index.php
put style.css

# 5. Uploader les pages
put page-golf.php
put page-sejour.php
put page-resultats.php
put page-destinations.php
put page-contact.php
put page-conditions.php
put page-rgpd.php
put page-mentions-legales.php
put page-faq.php
put page-reserver.php
put page-devis-golf.php
put page-avis-clients.php
put page-assurances.php
put page-comment-reserver.php
put page-qui-sommes-nous.php
put page-blog.php

# 6. Uploader les assets CSS/JS
cd assets/css/
lcd assets/css/
put -r *
cd ../js/
lcd ../js/
put -r *

# 7. Uploader WooCommerce templates
cd ../../woocommerce/
lcd ../../woocommerce/
put -r *
```

### Option 2 — Script rsync (si SSH disponible)

```bash
rsync -avz --exclude='assets/docs/' --exclude='assets/img/' \
  vs08-theme-deploy/ \
  u123456789@sortirmonde.fr:public_html/wp-content/themes/vs08-theme/
```

### Option 3 — Gestionnaire de fichiers Hostinger

1. Connecte-toi au **hPanel Hostinger** → Gestionnaire de fichiers
2. Navigue vers `public_html/wp-content/themes/vs08-theme/`
3. Upload le ZIP directement dans ce dossier
4. Extrais le ZIP sur place (clic droit → Extraire)
5. Supprime le ZIP après extraction

## Fichiers critiques (priorité si upload partiel)

Les 5 fichiers les plus importants à remplacer en premier :

| Fichier | Rôle |
|---------|------|
| `front-page.php` | Page d'accueil — hero, recherche, destinations, confiance |
| `header-mega-new.php` | Menu navigation mega-menu |
| `footer.php` | Footer avec contact, liens, newsletter |
| `functions.php` | Fonctions thème, hooks, enqueue assets |
| `single-vs08_voyage.php` | Page produit golf avec calculateur |

## Après déploiement

1. **Purger le cache** : hPanel → Cache Manager → Purge All
2. **Vérifier** : ouvrir sortirmonde.fr en navigation privée
3. **Hard refresh** : Ctrl+Shift+R sur la page d'accueil
