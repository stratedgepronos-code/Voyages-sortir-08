<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$view         = get_query_var('vs08_espace');
$order_id     = (int) get_query_var('vs08_voyage_order');
$profile_photo_id = VS08V_Traveler_Space::get_profile_photo_id($current_user->ID);
$profile_photo_url = $profile_photo_id ? wp_get_attachment_image_url($profile_photo_id, 'thumbnail') : '';
$saved_facturation = VS08V_Traveler_Space::get_saved_facturation($current_user->ID);
$saved_voyageurs   = VS08V_Traveler_Space::get_saved_voyageurs($current_user->ID);

get_header();
?>

<div class="ev-page">

    <!-- ─── Mobile nav bar (visible < 960px) ─── -->
    <div class="ev-mobile-nav">
        <a href="<?php echo esc_url(VS08V_Traveler_Space::base_url()); ?>" class="ev-mn-item <?php echo $view === 'dashboard' ? 'active' : ''; ?>"><span>🏠</span>Accueil</a>
        <a href="<?php echo esc_url(VS08V_Traveler_Space::list_url()); ?>" class="ev-mn-item <?php echo $view === 'list' ? 'active' : ''; ?>"><span>✈</span>Voyages</a>
        <a href="<?php echo esc_url(VS08V_Traveler_Space::profile_url()); ?>" class="ev-mn-item <?php echo $view === 'profil' ? 'active' : ''; ?>"><span>👤</span>Profil</a>
        <a href="<?php echo esc_url(VS08V_Traveler_Space::favoris_url()); ?>" class="ev-mn-item <?php echo $view === 'favoris' ? 'active' : ''; ?>"><span>❤</span>Favoris</a>
        <a href="<?php echo esc_url(home_url('/espace-voyageur/contact/')); ?>" class="ev-mn-item <?php echo $view === 'contact' ? 'active' : ''; ?>"><span>✉</span>Contact</a>
        <?php if (current_user_can('manage_options')): ?>
        <a href="<?php echo esc_url(home_url('/espace-admin/')); ?>" class="ev-mn-item"><span>⚙️</span>Admin</a>
        <?php endif; ?>
    </div>

    <!-- ─── Sidebar navigation ─── -->
    <aside class="ev-sidebar">
        <div class="ev-sidebar-user">
            <?php if ($profile_photo_url): ?>
            <div class="ev-avatar ev-avatar-img" style="background-image:url(<?php echo esc_url($profile_photo_url); ?>); background-size:cover;"></div>
            <?php else: ?>
            <div class="ev-avatar"><?php echo esc_html(mb_strtoupper(mb_substr($current_user->first_name ?: $current_user->display_name, 0, 1))); ?></div>
            <?php endif; ?>
            <div class="ev-user-info">
                <span class="ev-user-name"><?php echo esc_html($current_user->first_name ? $current_user->first_name . ' ' . $current_user->last_name : $current_user->display_name); ?></span>
                <span class="ev-user-email"><?php echo esc_html($current_user->user_email); ?></span>
            </div>
        </div>

        <nav class="ev-nav">
            <a href="<?php echo esc_url(VS08V_Traveler_Space::base_url()); ?>" class="ev-nav-item <?php echo $view === 'dashboard' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">🏠</span> Tableau de bord
            </a>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::list_url()); ?>" class="ev-nav-item <?php echo $view === 'list' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">✈</span> Mes voyages
            </a>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::profile_url()); ?>" class="ev-nav-item <?php echo $view === 'profil' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">👤</span> Mon profil
            </a>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::favoris_url()); ?>" class="ev-nav-item <?php echo $view === 'favoris' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">❤</span> Mes favoris
            </a>
            <a href="<?php echo esc_url(home_url('/espace-voyageur/contact/')); ?>" class="ev-nav-item <?php echo $view === 'contact' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">✉</span> Nous contacter
            </a>
        </nav>

        <?php if (current_user_can('manage_options')): ?>
        <a href="<?php echo esc_url(home_url('/espace-admin/')); ?>" class="ev-nav-item" style="background:rgba(89,183,183,.1);margin:0 12px 8px;border-radius:12px">
            <span class="ev-nav-icon">⚙️</span> Espace admin
        </a>
        <?php endif; ?>

        <div class="ev-sidebar-footer">
            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="ev-nav-item ev-nav-logout">
                <span class="ev-nav-icon">⏻</span> Déconnexion
            </a>
        </div>
    </aside>

    <!-- ─── Main content ─── -->
    <main class="ev-main">

        <?php if ($view === 'detail' && $order_id): ?>

            <?php
            if (!VS08V_Traveler_Space::current_user_can_view_order($order_id)) {
                echo '<div class="ev-alert">Voyage introuvable ou accès refusé.</div>';
            } else {
                $order      = wc_get_order($order_id);
                $data       = VS08V_Traveler_Space::get_booking_data_from_order($order, true);
                $is_circuit = isset($data['type']) && $data['type'] === 'circuit';
                if ($is_circuit): // ── Détail Circuit ──
                    $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
                    $params     = $data['params'] ?? [];
                    $devis      = $data['devis'] ?? [];
                    $fact       = $data['facturation'] ?? [];
                    $voyageurs  = $data['voyageurs'] ?? [];
                    $circuit_id = (int)($data['circuit_id'] ?? 0);
                    $m          = class_exists('VS08C_Meta') ? VS08C_Meta::get($circuit_id) : [];
                    $destination = $m['destination'] ?? '';
                    $total      = (float)($data['total'] ?? 0);
                    $contract_url = VS08V_Traveler_Space::get_contract_url($order_id);
                    $galerie    = $m['galerie'] ?? ($m['photos'] ?? []);
                    $cover      = '';
                    if (!empty($galerie)) {
                        $first = $galerie[0];
                        $cover = is_array($first) ? ($first['url'] ?? '') : $first;
                    }
                    if (!$cover) $cover = get_the_post_thumbnail_url($circuit_id, 'large');
                    $duree_n    = (int)($m['duree'] ?? 7);
                    $duree_j    = (int)($m['duree_jours'] ?? ($duree_n + 1));
                    $date_retour = !empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'] . ' +' . $duree_n . ' days')) : '';
                    $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','mixed'=>'Selon programme'];
                    $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4×4','voiture'=>'Voiture de location','train'=>'Train','mixed'=>'Transport mixte'];
                    $pension_label = $pension_labels[$m['pension'] ?? ''] ?? '';
                    $transport_label = $transport_labels[$m['transport'] ?? ''] ?? '';
                    $guide_lang = $m['guide_lang'] ?? '';
                    $aeroport_depart = $params['aeroport'] ?? '';
                    $aeroport_dest = $m['iata_dest'] ?? '';
                    $can_pay_solde = $solde_info && $solde_info['solde_due'] && $solde_info['solde'] > 0;
                    $company = VS08V_Contract::COMPANY;
                    $nb_etapes = is_array($m['jours'] ?? null) ? count($m['jours']) : 0;
            ?>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::list_url()); ?>" class="ev-back">&larr; Retour à mes voyages</a>

            <!-- Hero -->
            <div class="ev-detail-hero" <?php if ($cover): ?>style="background-image:linear-gradient(180deg,rgba(26,58,58,.45) 0%,rgba(26,58,58,.7) 100%),url(<?php echo esc_url($cover); ?>)"<?php endif; ?>>
                <div class="ev-detail-hero-content">
                    <h1><?php echo esc_html($data['circuit_titre'] ?? 'Circuit'); ?></h1>
                    <p>N° VS08-<?php echo $order_id; ?> · Départ le <?php echo !empty($params['date_depart']) ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></p>
                </div>
                <?php if ($can_pay_solde): ?>
                <div class="ev-detail-hero-badge">Solde à régler</div>
                <?php elseif ($solde_info && !empty($solde_info['soldé_paye'])): ?>
                <div class="ev-detail-hero-badge ev-detail-hero-badge-solde">Soldé</div>
                <?php endif; ?>
                <span class="ev-badge ev-badge-circuit">Circuit</span>
            </div>

            <div class="ev-detail-grid">

                <!-- Récapitulatif -->
                <section class="ev-card ev-card-recap">
                    <h2>Récapitulatif du circuit</h2>
                    <div class="ev-recap-rows">
                        <div class="ev-recap-row"><span>Destination</span><strong><?php echo esc_html($destination); ?></strong></div>
                        <div class="ev-recap-row"><span>Date de départ</span><strong><?php echo !empty($params['date_depart']) ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></strong></div>
                        <?php if ($date_retour): ?>
                        <div class="ev-recap-row"><span>Date de retour</span><strong><?php echo esc_html($date_retour); ?></strong></div>
                        <?php endif; ?>
                        <div class="ev-recap-row"><span>Durée</span><strong><?php echo $duree_j; ?> jours / <?php echo $duree_n; ?> nuits</strong></div>
                        <?php if ($nb_etapes): ?>
                        <div class="ev-recap-row"><span>Étapes</span><strong><?php echo $nb_etapes; ?> étapes</strong></div>
                        <?php endif; ?>
                        <?php if ($pension_label): ?>
                        <div class="ev-recap-row"><span>Formule</span><strong><?php echo esc_html($pension_label); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($transport_label): ?>
                        <div class="ev-recap-row"><span>Transport</span><strong><?php echo esc_html($transport_label); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($guide_lang): ?>
                        <div class="ev-recap-row"><span>Guide</span><strong><?php echo esc_html($guide_lang); ?></strong></div>
                        <?php endif; ?>
                        <div class="ev-recap-row"><span>Voyageurs</span><strong><?php echo (int)($devis['nb_total'] ?? 0); ?> personne(s)</strong></div>
                        <?php if ($aeroport_depart): ?>
                        <div class="ev-recap-row"><span>Aéroport de départ</span><strong><?php echo esc_html(strtoupper($aeroport_depart)); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($aeroport_dest): ?>
                        <div class="ev-recap-row"><span>Aéroport destination</span><strong><?php echo esc_html(strtoupper($aeroport_dest)); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_aller_num'])): ?>
                        <div class="ev-recap-row"><span>Vol aller</span><strong><?php echo esc_html($params['vol_aller_num']); ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_retour_num'])): ?>
                        <div class="ev-recap-row"><span>Vol retour</span><strong><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></strong></div>
                        <?php endif; ?>
                        <div class="ev-recap-row ev-recap-total"><span>Montant total</span><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></div>
                    </div>
                </section>

                <div class="ev-cards-pax-docs">
                <!-- Participants -->
                <?php if (!empty($voyageurs)): ?>
                <section class="ev-card ev-card-pax">
                    <h2>Participants</h2>
                    <div class="ev-pax-list">
                        <?php foreach ($voyageurs as $i => $v):
                            $ddn = $v['ddn'] ?? $v['date_naissance'] ?? '';
                        ?>
                        <div class="ev-pax-item">
                            <div class="ev-pax-num"><?php echo $i + 1; ?></div>
                            <div class="ev-pax-info">
                                <strong><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?></strong>
                                <span class="ev-pax-type ev-pax-type-golfeur">Voyageur</span>
                                <?php if ($ddn): ?>
                                    <span>Né(e) le <?php echo esc_html(date('d/m/Y', strtotime($ddn))); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($v['passeport'])): ?>
                                    <span>Passeport <?php echo esc_html($v['passeport']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Documents -->
                <section class="ev-card ev-card-docs">
                    <h2>Documents</h2>
                    <a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener" class="ev-btn ev-btn-outline">Voir / imprimer le contrat de vente</a>
                    <div class="ev-docs-upload">
                        <p class="ev-docs-upload-desc">Envoyez des documents à l'agence (photos, pièces…) pour ce voyage.</p>
                        <form class="ev-form-documents" data-order-id="<?php echo $order_id; ?>" enctype="multipart/form-data" method="post">
                            <?php wp_nonce_field('vs08v_send_documents', 'vs08v_docs_nonce'); ?>
                            <input type="file" name="vs08v_docs[]" class="ev-input-files" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                            <button type="submit" class="ev-btn ev-btn-outline ev-btn-docs-send">Envoyer des documents à l'agence</button>
                            <span class="ev-docs-feedback" aria-live="polite"></span>
                        </form>
                    </div>
                </section>
                </div>

                <!-- Paiements en attente de validation -->
                <?php if (!empty($solde_info['pending_payments'])): ?>
                <section class="ev-card ev-card-pending">
                    <h2>Paiements en attente de validation</h2>
                    <p class="ev-pending-desc">Les paiements ci-dessous ont été enregistrés et sont en attente de confirmation par l'agence.</p>
                    <div class="ev-pending-list">
                        <?php foreach ($solde_info['pending_payments'] as $pp): ?>
                        <div class="ev-pending-item">
                            <div class="ev-pending-icon">⏳</div>
                            <div class="ev-pending-info">
                                <strong><?php echo number_format($pp['amount'], 2, ',', ' '); ?> €</strong>
                                <span><?php echo esc_html($pp['method']); ?> — <?php echo esc_html($pp['date']); ?></span>
                            </div>
                            <span class="ev-badge ev-badge-pending">En attente</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Paiement solde -->
                <?php if ($can_pay_solde): ?>
                <section class="ev-card ev-card-payment">
                    <h2>Paiement du solde</h2>
                    <div class="ev-solde-amount">
                        <span class="ev-solde-label">Solde restant</span>
                        <span class="ev-solde-value"><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</span>
                        <?php if ($solde_info['solde_date']): ?>
                        <span class="ev-solde-deadline">À régler avant le <?php echo esc_html($solde_info['solde_date']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ev-payment-btns">
                        <button type="button" class="ev-btn ev-btn-primary ev-btn-solde-cb" data-order-id="<?php echo $order_id; ?>" data-solde="<?php echo esc_attr($solde_info['solde']); ?>">Payer le solde entier (<?php echo number_format($solde_info['solde'], 0, ',', ' '); ?> €)</button>
                        <button type="button" class="ev-btn ev-btn-ghost ev-btn-solde-agence" data-order-id="<?php echo $order_id; ?>">Payer en agence</button>
                    </div>
                    <div class="ev-solde-partiel">
                        <p class="ev-solde-partiel-label">Ou régler une partie du solde par carte :</p>
                        <div class="ev-solde-partiel-row">
                            <input type="number" class="ev-solde-partiel-input" step="0.01" min="1" max="<?php echo esc_attr($solde_info['solde']); ?>" placeholder="Montant en €" data-order-id="<?php echo $order_id; ?>" data-solde-max="<?php echo esc_attr($solde_info['solde']); ?>">
                            <span class="ev-solde-partiel-euro">€</span>
                            <button type="button" class="ev-btn ev-btn-outline ev-btn-solde-partiel" data-order-id="<?php echo $order_id; ?>" data-solde-max="<?php echo esc_attr($solde_info['solde']); ?>">Payer ce montant</button>
                        </div>
                    </div>
                    <div class="ev-agence-info" id="ev-agence-<?php echo $order_id; ?>" style="display:none">
                        <p><strong><?php echo esc_html($company['name'] ?? ''); ?></strong></p>
                        <p><?php echo esc_html($company['address'] ?? ''); ?><br><?php echo esc_html($company['city'] ?? ''); ?></p>
                        <p>Tél. : <?php echo esc_html($company['tel'] ?? ''); ?></p>
                        <p>Montant : <strong><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</strong> — Dossier VS08-<?php echo $order_id; ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- À faire avant le départ -->
                <?php
                $is_upcoming_trip = !empty($params['date_depart']) && $params['date_depart'] >= date('Y-m-d');
                if ($is_upcoming_trip):
                    $checklist_items = VS08V_Traveler_Space::get_checklist_items();
                    $saved_checklist = VS08V_Traveler_Space::get_saved_checklist($order_id, $current_user->ID);
                ?>
                <section class="ev-card ev-card-checklist">
                    <h2>À faire avant le départ</h2>
                    <p class="ev-checklist-desc">Cochez au fur et à mesure pour ne rien oublier.</p>
                    <ul class="ev-checklist-list" data-order-id="<?php echo $order_id; ?>">
                        <?php foreach ($checklist_items as $key => $label): ?>
                        <li class="ev-checklist-item">
                            <label class="ev-checklist-label">
                                <input type="checkbox" class="ev-checklist-cb" value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $saved_checklist, true) ? ' checked' : ''; ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="ev-checklist-docs">
                        <a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener">📄 Télécharger le contrat</a>
                        · <a href="<?php echo esc_url(home_url('/assurances')); ?>">🛡 Infos assurance voyage</a>
                    </p>
                </section>
                <?php endif; ?>

                <!-- Donner son avis (voyages passés) -->
                <?php
                $can_review = !$is_upcoming_trip && VS08V_Traveler_Space::can_review($order_id, $current_user->ID);
                if ($can_review):
                ?>
                <section class="ev-card ev-card-review">
                    <h2>Donner votre avis</h2>
                    <p>Vous avez effectué ce circuit ? Votre avis aide les futurs voyageurs.</p>
                    <form class="ev-review-form" data-order-id="<?php echo $order_id; ?>">
                        <div class="ev-review-stars">
                            <span class="ev-star-label">Note :</span>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" class="ev-star" data-star="<?php echo $s; ?>" aria-label="<?php echo $s; ?> étoile(s)">★</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" value="">
                        </div>
                        <textarea name="review_text" class="ev-review-textarea" rows="4" placeholder="Racontez votre expérience…"></textarea>
                        <button type="submit" class="ev-btn ev-btn-primary">Publier mon avis</button>
                        <span class="ev-review-feedback" aria-live="polite"></span>
                    </form>
                </section>
                <?php endif; ?>

                <!-- Carnet de voyage (documents admin → client) -->
                <?php
                $carnet_files = get_post_meta($order_id, '_vs08_carnet_files', true);
                if (!is_array($carnet_files)) $carnet_files = [];
                if (!empty($carnet_files)):
                ?>
                <section class="ev-card ev-card-carnet">
                    <h2>📋 Carnet de voyage</h2>
                    <p style="font-size:13px;color:#6b7280;margin:0 0 14px;font-family:'Outfit',sans-serif">Vos documents de voyage sont prêts. Téléchargez-les avant votre départ.</p>
                    <div class="ev-carnet-files">
                        <?php foreach ($carnet_files as $cf):
                            $fname = $cf['name'] ?? basename($cf['url'] ?? '');
                            $furl = $cf['url'] ?? '';
                            $fdate = $cf['date'] ?? '';
                            if (!$furl) continue;
                            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                            $icon = '📄';
                            if (in_array($ext, ['pdf'])) $icon = '📕';
                            elseif (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $icon = '🖼️';
                            elseif (in_array($ext, ['doc','docx'])) $icon = '📝';
                        ?>
                        <a href="<?php echo esc_url($furl); ?>" target="_blank" rel="noopener" class="ev-carnet-file">
                            <span class="ev-carnet-icon"><?php echo $icon; ?></span>
                            <span class="ev-carnet-info">
                                <strong><?php echo esc_html($fname); ?></strong>
                                <?php if ($fdate): ?><span style="font-size:11px;color:#9ca3af">Ajouté le <?php echo esc_html(date_i18n('j M Y', strtotime($fdate))); ?></span><?php endif; ?>
                            </span>
                            <span class="ev-carnet-dl">Télécharger</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Question -->
                <section class="ev-card ev-card-question">
                    <h2>Une question ?</h2>
                    <p class="ev-question-intro">Posez-nous une question relative à ce voyage. Nous vous répondrons dans les meilleurs délais.</p>
                    <button type="button" class="ev-btn ev-btn-outline ev-btn-open-question ev-btn-question-spaced" data-order-id="<?php echo $order_id; ?>">Poser une question</button>
                </section>

            </div><!-- /ev-detail-grid -->
            <?php else: // ── Détail Golf (existant) ──
                $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
                $params     = $data['params'] ?? [];
                $devis      = $data['devis'] ?? [];
                $fact       = $data['facturation'] ?? [];
                $voyageurs  = $data['voyageurs'] ?? [];
                $voyage_id  = (int)($data['voyage_id'] ?? 0);
                $m          = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
                $hotel_nom  = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
                $hotel_etoiles = $m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? '');
                $destination = $m['destination'] ?? '';
                $pension_labels = ['bb' => 'Petit-déjeuner', 'dp' => 'Demi-pension', 'pc' => 'Pension complète', 'ai' => 'Tout inclus', 'lo' => 'Logement seul'];
                $pension_code = $m['pension'] ?? ($m['hotel']['pension'] ?? '');
                $pension_label = $pension_labels[$pension_code] ?? '';
                $total = (float)($data['total'] ?? 0);
                $contract_url = VS08V_Traveler_Space::get_contract_url($order_id);
                $can_pay_solde = $solde_info && $solde_info['solde_due'] && $solde_info['solde'] > 0;
                $company = VS08V_Contract::COMPANY;
                $galerie = $m['galerie'] ?? [];
                $cover = !empty($galerie[0]) ? $galerie[0] : '';
                $duree = (int)($m['duree'] ?? 7);
                $date_retour = '';
                if (!empty($params['date_depart']) && $duree > 0) {
                    $date_retour = date('d/m/Y', strtotime($params['date_depart'] . ' +' . $duree . ' days'));
                }
                $aeroport_depart = $params['aeroport'] ?? '';
                $aeroport_dest = $m['iata_dest'] ?? '';
            ?>

            <a href="<?php echo esc_url(VS08V_Traveler_Space::list_url()); ?>" class="ev-back">&larr; Retour à mes voyages</a>

            <!-- Hero -->
            <div class="ev-detail-hero" <?php if ($cover): ?>style="background-image:linear-gradient(180deg,rgba(26,58,58,.45) 0%,rgba(26,58,58,.7) 100%),url(<?php echo esc_url($cover); ?>)"<?php endif; ?>>
                <div class="ev-detail-hero-content">
                    <h1><?php echo esc_html((string)($data['voyage_titre'] ?? 'Votre séjour')); ?></h1>
                    <p>N° VS08-<?php echo $order_id; ?> · Départ le <?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></p>
                </div>
                <?php if ($can_pay_solde): ?>
                <div class="ev-detail-hero-badge">Solde à régler</div>
                <?php elseif ($solde_info && !empty($solde_info['soldé_paye'])): ?>
                <div class="ev-detail-hero-badge ev-detail-hero-badge-solde">Soldé</div>
                <?php endif; ?>
            </div>

            <div class="ev-detail-grid">

                <!-- Récapitulatif -->
                <section class="ev-card ev-card-recap">
                    <h2>Récapitulatif du séjour</h2>
                    <div class="ev-recap-rows">
                        <div class="ev-recap-row"><span>Destination</span><strong><?php echo esc_html((string)($destination ?? '')); ?></strong></div>
                        <div class="ev-recap-row"><span>Date de départ</span><strong><?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></strong></div>
                        <?php if ($date_retour): ?>
                        <div class="ev-recap-row"><span>Date de retour</span><strong><?php echo esc_html((string)($date_retour ?? '')); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($hotel_nom): ?>
                        <div class="ev-recap-row"><span>Hébergement</span><strong><?php echo esc_html((string)($hotel_nom ?? '')); ?><?php if ($hotel_etoiles): ?> <?php echo str_repeat('★', (int)$hotel_etoiles); ?><?php endif; ?></strong></div>
                        <?php endif; ?>
                        <?php if ($pension_label): ?>
                        <div class="ev-recap-row"><span>Pension</span><strong><?php echo esc_html((string)($pension_label ?? '')); ?></strong></div>
                        <?php endif; ?>
                        <div class="ev-recap-row"><span>Voyageurs</span><strong><?php echo (int)($devis['nb_total'] ?? 0); ?> personne(s)</strong></div>
                        <?php if ($aeroport_depart): ?>
                        <div class="ev-recap-row"><span>Aéroport de départ</span><strong><?php echo esc_html(strtoupper((string)($aeroport_depart ?? ''))); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($aeroport_dest): ?>
                        <div class="ev-recap-row"><span>Aéroport de destination</span><strong><?php echo esc_html(strtoupper((string)($aeroport_dest ?? ''))); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_aller_num'])): ?>
                        <div class="ev-recap-row"><span>Vol aller</span><strong><?php echo esc_html((string)($params['vol_aller_num'] ?? '')); ?> — <?php echo esc_html((string)($params['vol_aller_depart'] ?? '')); ?> → <?php echo esc_html((string)($params['vol_aller_arrivee'] ?? '')); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_retour_num'])): ?>
                        <div class="ev-recap-row"><span>Vol retour</span><strong><?php echo esc_html((string)($params['vol_retour_num'] ?? '')); ?> — <?php echo esc_html((string)($params['vol_retour_depart'] ?? '')); ?> → <?php echo esc_html((string)($params['vol_retour_arrivee'] ?? '')); ?></strong></div>
                        <?php endif; ?>
                        <div class="ev-recap-row ev-recap-total"><span>Montant total</span><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></div>
                    </div>
                </section>

                <div class="ev-cards-pax-docs">
                <!-- Participants (gauche) -->
                <?php if (!empty($voyageurs)): ?>
                <section class="ev-card ev-card-pax">
                    <h2>Participants</h2>
                    <div class="ev-pax-list">
                        <?php foreach ($voyageurs as $i => $v):
                            $is_golfeur = (isset($v['type']) && $v['type'] === 'golfeur') || (!isset($v['type']) && $i < (int)($params['nb_golfeurs'] ?? 1));
                            $type_label = $is_golfeur ? 'Golfeur' : 'Non golfeur';
                            $ddn = $v['ddn'] ?? $v['date_naissance'] ?? '';
                        ?>
                        <div class="ev-pax-item">
                            <div class="ev-pax-num"><?php echo $i + 1; ?></div>
                            <div class="ev-pax-info">
                                <strong><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?></strong>
                                <span class="ev-pax-type ev-pax-type-<?php echo $is_golfeur ? 'golfeur' : 'nongolfeur'; ?>"><?php echo esc_html($type_label); ?></span>
                                <?php if ($ddn): ?>
                                    <span>Né(e) le <?php echo esc_html(date('d/m/Y', strtotime($ddn))); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($v['passeport'])): ?>
                                    <span>Passeport <?php echo esc_html((string)($v['passeport'] ?? '')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Documents (droite) -->
                <section class="ev-card ev-card-docs">
                    <h2>Documents</h2>
                    <a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener" class="ev-btn ev-btn-outline">Voir / imprimer le contrat de vente</a>
                    <div class="ev-docs-upload">
                        <p class="ev-docs-upload-desc">Envoyez des documents à l'agence (photos, pièces…) pour ce voyage.</p>
                        <form class="ev-form-documents" data-order-id="<?php echo $order_id; ?>" enctype="multipart/form-data" method="post">
                            <?php wp_nonce_field('vs08v_send_documents', 'vs08v_docs_nonce'); ?>
                            <input type="file" name="vs08v_docs[]" class="ev-input-files" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                            <button type="submit" class="ev-btn ev-btn-outline ev-btn-docs-send">Envoyer des documents à l'agence</button>
                            <span class="ev-docs-feedback" aria-live="polite"></span>
                        </form>
                    </div>
                </section>
                </div>

                <!-- Paiements en attente de validation (virement / cheque) -->
                <?php if (!empty($solde_info['pending_payments'])): ?>
                <section class="ev-card ev-card-pending">
                    <h2>Paiements en attente de validation</h2>
                    <p class="ev-pending-desc">Les paiements ci-dessous ont été enregistrés et sont en attente de confirmation par l'agence.</p>
                    <div class="ev-pending-list">
                        <?php foreach ($solde_info['pending_payments'] as $pp): ?>
                        <div class="ev-pending-item">
                            <div class="ev-pending-icon">⏳</div>
                            <div class="ev-pending-info">
                                <strong><?php echo number_format($pp['amount'], 2, ',', ' '); ?> €</strong>
                                <span><?php echo esc_html($pp['method']); ?> — <?php echo esc_html($pp['date']); ?></span>
                            </div>
                            <span class="ev-badge ev-badge-pending">En attente</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Paiement solde -->
                <?php if ($can_pay_solde): ?>
                <section class="ev-card ev-card-payment">
                    <h2>Paiement du solde</h2>
                    <div class="ev-solde-amount">
                        <span class="ev-solde-label">Solde restant</span>
                        <span class="ev-solde-value"><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</span>
                        <?php if ($solde_info['solde_date']): ?>
                        <span class="ev-solde-deadline">À régler avant le <?php echo esc_html($solde_info['solde_date']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ev-payment-btns">
                        <button type="button" class="ev-btn ev-btn-primary ev-btn-solde-cb" data-order-id="<?php echo $order_id; ?>" data-solde="<?php echo esc_attr($solde_info['solde']); ?>">Payer le solde entier (<?php echo number_format($solde_info['solde'], 0, ',', ' '); ?> €)</button>
                        <button type="button" class="ev-btn ev-btn-ghost ev-btn-solde-agence" data-order-id="<?php echo $order_id; ?>">Payer en agence</button>
                    </div>
                    <div class="ev-solde-partiel">
                        <p class="ev-solde-partiel-label">Ou régler une partie du solde par carte :</p>
                        <div class="ev-solde-partiel-row">
                            <input type="number" class="ev-solde-partiel-input" step="0.01" min="1" max="<?php echo esc_attr($solde_info['solde']); ?>" placeholder="Montant en €" data-order-id="<?php echo $order_id; ?>" data-solde-max="<?php echo esc_attr($solde_info['solde']); ?>">
                            <span class="ev-solde-partiel-euro">€</span>
                            <button type="button" class="ev-btn ev-btn-outline ev-btn-solde-partiel" data-order-id="<?php echo $order_id; ?>" data-solde-max="<?php echo esc_attr($solde_info['solde']); ?>">Payer ce montant</button>
                        </div>
                    </div>
                    <div class="ev-agence-info" id="ev-agence-<?php echo $order_id; ?>" style="display:none">
                        <p><strong><?php echo esc_html((string)($company['name'] ?? '')); ?></strong></p>
                        <p><?php echo esc_html((string)($company['address'] ?? '')); ?><br><?php echo esc_html((string)($company['city'] ?? '')); ?></p>
                        <p>Tél. : <?php echo esc_html((string)($company['tel'] ?? '')); ?></p>
                        <p>Montant : <strong><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</strong> — Dossier VS08-<?php echo $order_id; ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <!-- À faire avant le départ (voyages à venir) -->
                <?php
                $is_upcoming_trip = !empty($params['date_depart']) && $params['date_depart'] >= date('Y-m-d');
                if ($is_upcoming_trip):
                    $checklist_items = VS08V_Traveler_Space::get_checklist_items();
                    $saved_checklist = VS08V_Traveler_Space::get_saved_checklist($order_id, $current_user->ID);
                ?>
                <section class="ev-card ev-card-checklist">
                    <h2>À faire avant le départ</h2>
                    <p class="ev-checklist-desc">Cochez au fur et à mesure pour ne rien oublier.</p>
                    <ul class="ev-checklist-list" data-order-id="<?php echo $order_id; ?>">
                        <?php foreach ($checklist_items as $key => $label): ?>
                        <li class="ev-checklist-item">
                            <label class="ev-checklist-label">
                                <input type="checkbox" class="ev-checklist-cb" value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $saved_checklist, true) ? ' checked' : ''; ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="ev-checklist-docs">
                        <a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener">📄 Télécharger le contrat</a>
                        <?php if (home_url('/assurances')): ?>
                        · <a href="<?php echo esc_url(home_url('/assurances')); ?>">🛡 Infos assurance voyage</a>
                        <?php endif; ?>
                    </p>
                </section>
                <?php endif; ?>

                <!-- Donner son avis (voyages passés) -->
                <?php
                $can_review = !$is_upcoming_trip && VS08V_Traveler_Space::can_review($order_id, $current_user->ID);
                if ($can_review):
                    $review_voyage_id = (int)($data['voyage_id'] ?? 0);
                ?>
                <section class="ev-card ev-card-review">
                    <h2>Donner votre avis</h2>
                    <p>Vous avez effectué ce séjour ? Votre avis aide les futurs voyageurs.</p>
                    <form class="ev-review-form" data-order-id="<?php echo $order_id; ?>">
                        <div class="ev-review-stars">
                            <span class="ev-star-label">Note :</span>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" class="ev-star" data-rating="<?php echo $s; ?>" aria-label="<?php echo $s; ?> étoile(s)">★</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" class="ev-review-rating" value="0">
                        </div>
                        <label for="ev-review-comment">Votre avis (optionnel)</label>
                        <textarea id="ev-review-comment" name="comment" rows="4" placeholder="Décrivez votre expérience..."></textarea>
                        <button type="submit" class="ev-btn ev-btn-primary">Publier mon avis</button>
                    </form>
                </section>
                <?php endif; ?>

                <!-- Carnet de voyage (documents admin → client) -->
                <?php
                $g_carnet_files = get_post_meta($order_id, '_vs08_carnet_files', true);
                if (!is_array($g_carnet_files)) $g_carnet_files = [];
                if (!empty($g_carnet_files)):
                ?>
                <section class="ev-card ev-card-carnet">
                    <h2>📋 Carnet de voyage</h2>
                    <p style="font-size:13px;color:#6b7280;margin:0 0 14px;font-family:'Outfit',sans-serif">Vos documents de voyage sont prêts. Téléchargez-les avant votre départ.</p>
                    <div class="ev-carnet-files">
                        <?php foreach ($g_carnet_files as $gcf):
                            $gfname = $gcf['name'] ?? basename($gcf['url'] ?? '');
                            $gfurl = $gcf['url'] ?? '';
                            $gfdate = $gcf['date'] ?? '';
                            if (!$gfurl) continue;
                            $gext = strtolower(pathinfo($gfname, PATHINFO_EXTENSION));
                            $gicon = '📄';
                            if (in_array($gext, ['pdf'])) $gicon = '📕';
                            elseif (in_array($gext, ['jpg','jpeg','png','gif','webp'])) $gicon = '🖼️';
                            elseif (in_array($gext, ['doc','docx'])) $gicon = '📝';
                        ?>
                        <a href="<?php echo esc_url($gfurl); ?>" target="_blank" rel="noopener" class="ev-carnet-file">
                            <span class="ev-carnet-icon"><?php echo $gicon; ?></span>
                            <span class="ev-carnet-info">
                                <strong><?php echo esc_html($gfname); ?></strong>
                                <?php if ($gfdate): ?><span style="font-size:11px;color:#9ca3af">Ajouté le <?php echo esc_html(date_i18n('j M Y', strtotime($gfdate))); ?></span><?php endif; ?>
                            </span>
                            <span class="ev-carnet-dl">Télécharger</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Question -->
                <section class="ev-card ev-card-question">
                    <h2>Une question ?</h2>
                    <p class="ev-question-intro">Posez-nous une question relative à ce voyage. Nous vous répondrons dans les meilleurs délais.</p>
                    <button type="button" class="ev-btn ev-btn-outline ev-btn-open-question ev-btn-question-spaced" data-order-id="<?php echo $order_id; ?>">Poser une question</button>
                </section>

            </div>

            <!-- Modale question -->
            <div id="ev-modal" class="ev-modal" style="display:none">
                <div class="ev-modal-backdrop"></div>
                <div class="ev-modal-box">
                    <button type="button" class="ev-modal-close">&times;</button>
                    <h3>Poser une question</h3>
                    <form id="ev-question-form">
                        <input type="hidden" name="order_id" id="ev-q-oid" value="">
                        <label for="ev-q-sujet">Sujet</label>
                        <input type="text" name="sujet" id="ev-q-sujet" required placeholder="Ex. : horaires de vol">
                        <label for="ev-q-message">Message</label>
                        <textarea name="message" id="ev-q-message" rows="5" required placeholder="Votre question..."></textarea>
                        <div class="ev-form-actions">
                            <button type="submit" class="ev-btn ev-btn-primary">Envoyer</button>
                            <button type="button" class="ev-btn ev-btn-ghost ev-modal-cancel">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            (function(){
                var nonce = '<?php echo esc_js(wp_create_nonce('vs08v_traveler')); ?>';
                var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                var modal = document.getElementById('ev-modal');

                if (modal) {
                    document.querySelectorAll('.ev-btn-open-question').forEach(function(b){
                        b.addEventListener('click', function(){
                            var oid = document.getElementById('ev-q-oid');
                            if (oid) oid.value = this.dataset.orderId;
                            modal.style.display = '';
                        });
                    });
                    document.querySelectorAll('.ev-modal-close,.ev-modal-cancel,.ev-modal-backdrop').forEach(function(el){
                        el.addEventListener('click', function(){ modal.style.display = 'none'; });
                    });
                }

                var form = document.getElementById('ev-question-form');
                if (form) {
                    form.addEventListener('submit', function(e){
                        e.preventDefault();
                        var btn = form.querySelector('[type=submit]');
                        btn.disabled = true; btn.textContent = 'Envoi...';
                        var fd = new FormData();
                        fd.append('action','vs08v_traveler_question');
                        fd.append('nonce', nonce);
                        fd.append('order_id', document.getElementById('ev-q-oid').value);
                        fd.append('sujet', document.getElementById('ev-q-sujet').value);
                        fd.append('message', document.getElementById('ev-q-message').value);
                        fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
                        .then(function(r){return r.json();})
                        .then(function(res){
                            alert(res.success ? (res.data.message||'Envoyé !') : (res.data&&res.data.message||'Erreur'));
                            if(res.success) modal.style.display = 'none';
                        })
                        .catch(function(){alert('Erreur réseau.');})
                        .finally(function(){btn.disabled=false;btn.textContent='Envoyer';});
                    });
                }

                function handleSoldeResponse(res, btn) {
                    if (!res.success) {
                        alert(res.data && res.data.message || 'Erreur');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    var d = res.data;
                    if (d.mode === 'paybox_mail') {
                        var card = btn ? btn.closest('.ev-card') : null;
                        var msgHtml = '<div class="ev-solde-confirmation" style="background:#e0f7f0;border:1px solid #22c55e;border-radius:10px;padding:18px 22px;margin-top:14px;animation:fadeIn .4s">'
                            + '<p style="margin:0 0 6px;font-weight:700;color:#166534;">✅ ' + (d.message || 'Lien de paiement envoyé !') + '</p>';
                        if (d.payment_url) {
                            msgHtml += '<p style="margin:0"><a href="' + d.payment_url + '" target="_blank" style="color:#2a7f7f;font-weight:600;text-decoration:underline;">Payer maintenant en ligne →</a></p>';
                        }
                        msgHtml += '</div>';
                        if (card) {
                            var existing = card.querySelector('.ev-solde-confirmation');
                            if (existing) existing.remove();
                            card.insertAdjacentHTML('beforeend', msgHtml);
                        } else {
                            alert(d.message || 'Lien de paiement envoyé par email.');
                        }
                        if (btn) btn.disabled = false;
                    } else if (d.redirect) {
                        window.location.href = d.redirect;
                    } else {
                        alert(d.message || 'Opération effectuée.');
                        if (btn) btn.disabled = false;
                    }
                }

                function soldeFetch(fd, btn, labelOriginal) {
                    var controller = new AbortController();
                    var timer = setTimeout(function(){ controller.abort(); }, 45000);
                    fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin', signal:controller.signal})
                    .then(function(r){
                        clearTimeout(timer);
                        if (!r.ok) {
                            return r.text().then(function(t){ throw new Error('HTTP ' + r.status + (t ? ': ' + t.substring(0,300) : '')); });
                        }
                        return r.json();
                    })
                    .then(function(res){ handleSoldeResponse(res, btn); })
                    .catch(function(err){
                        clearTimeout(timer);
                        console.error('Solde error:', err);
                        var msg = err.name === 'AbortError' ? 'Délai dépassé (45s). Le serveur met trop de temps à répondre.' : 'Erreur : ' + err.message;
                        alert(msg);
                        btn.disabled = false;
                        btn.textContent = labelOriginal;
                    });
                }

                document.querySelectorAll('.ev-btn-solde-cb').forEach(function(b){
                    b.addEventListener('click', function(e){
                        e.preventDefault();
                        var btn = this;
                        var label = btn.textContent;
                        btn.disabled = true;
                        btn.textContent = 'Envoi en cours…';
                        var fd = new FormData();
                        fd.append('action','vs08v_traveler_solde');
                        fd.append('nonce', nonce);
                        fd.append('order_id', this.dataset.orderId);
                        soldeFetch(fd, btn, label);
                    });
                });

                document.querySelectorAll('.ev-btn-solde-partiel').forEach(function(b){
                    b.addEventListener('click', function(e){
                        e.preventDefault();
                        var btn = this;
                        var orderId = this.dataset.orderId;
                        var maxVal = parseFloat(this.dataset.soldeMax) || 0;
                        var card = this.closest('.ev-card');
                        var input = card ? card.querySelector('.ev-solde-partiel-input') : null;
                        var amount = input ? parseFloat(input.value) : 0;
                        if (!amount || amount <= 0) {
                            alert('Veuillez saisir un montant à régler.');
                            return;
                        }
                        if (amount > maxVal) {
                            alert('Le montant ne peut pas dépasser le solde restant (' + maxVal.toFixed(2).replace('.', ',') + ' €).');
                            return;
                        }
                        var label = btn.textContent;
                        btn.disabled = true;
                        btn.textContent = 'Envoi en cours…';
                        var fd = new FormData();
                        fd.append('action', 'vs08v_traveler_solde');
                        fd.append('nonce', nonce);
                        fd.append('order_id', orderId);
                        fd.append('amount', amount);
                        soldeFetch(fd, btn, label);
                    });
                });

                document.querySelectorAll('.ev-btn-solde-agence').forEach(function(b){
                    b.addEventListener('click', function(){
                        var card = this.closest('.ev-card');
                        if (!card) return;
                        var el = card.querySelector('.ev-agence-info');
                        if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
                    });
                });

                var checklistList = document.querySelector('.ev-checklist-list');
                if (checklistList) {
                    var orderIdChecklist = checklistList.dataset.orderId;
                    checklistList.querySelectorAll('.ev-checklist-cb').forEach(function(cb){
                        cb.addEventListener('change', function(){
                            var checked = [];
                            checklistList.querySelectorAll('.ev-checklist-cb:checked').forEach(function(c){ checked.push(c.value); });
                            var fd = new FormData();
                            fd.append('action', 'vs08v_traveler_checklist');
                            fd.append('nonce', nonce);
                            fd.append('order_id', orderIdChecklist);
                            checked.forEach(function(k){ fd.append('checked[]', k); });
                            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(res){ if (!res.success) console.warn('Checklist', res); });
                        });
                    });
                }

                var reviewForm = document.querySelector('.ev-review-form');
                if (reviewForm) {
                    var orderIdReview = reviewForm.dataset.orderId;
                    var ratingInput = reviewForm.querySelector('.ev-review-rating');
                    var selectedRating = 0;
                    reviewForm.querySelectorAll('.ev-star').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            selectedRating = parseInt(this.dataset.rating, 10);
                            ratingInput.value = selectedRating;
                            reviewForm.querySelectorAll('.ev-star').forEach(function(s){ s.classList.remove('active'); });
                            for (var i = 0; i < selectedRating; i++) reviewForm.querySelectorAll('.ev-star')[i].classList.add('active');
                        });
                    });
                    reviewForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        if (selectedRating < 1) { alert('Veuillez choisir une note.'); return; }
                        var fd = new FormData();
                        fd.append('action', 'vs08v_traveler_review');
                        fd.append('nonce', nonce);
                        fd.append('order_id', orderIdReview);
                        fd.append('rating', selectedRating);
                        fd.append('comment', document.getElementById('ev-review-comment').value);
                        var submitBtn = reviewForm.querySelector('[type=submit]');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Envoi...';
                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                alert(res.success ? (res.data.message || 'Merci !') : (res.data && res.data.message || 'Erreur'));
                                if (res.success) reviewForm.closest('.ev-card-review').innerHTML = '<p class="ev-review-thanks">Merci pour votre avis !</p>';
                            })
                            .catch(function(){ alert('Erreur réseau.'); })
                            .finally(function(){ submitBtn.disabled = false; submitBtn.textContent = 'Publier mon avis'; });
                    });
                }

                var docsForm = document.querySelector('.ev-form-documents');
                if (docsForm) {
                    var feedbackEl = docsForm.querySelector('.ev-docs-feedback');
                    var orderIdDocs = docsForm.dataset.orderId;
                    docsForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        var fileInput = docsForm.querySelector('.ev-input-files');
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            if (feedbackEl) { feedbackEl.textContent = 'Veuillez sélectionner au moins un fichier.'; feedbackEl.style.color = '#c00'; }
                            return;
                        }
                        var fd = new FormData(docsForm);
                        fd.append('action', 'vs08v_traveler_send_documents');
                        fd.append('order_id', orderIdDocs);
                        if (feedbackEl) feedbackEl.textContent = 'Envoi en cours...';
                        var submitBtn = docsForm.querySelector('.ev-btn-docs-send');
                        if (submitBtn) submitBtn.disabled = true;
                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                if (feedbackEl) {
                                    feedbackEl.textContent = res.success ? (res.data && res.data.message || 'Documents envoyés.') : (res.data && res.data.message || 'Erreur');
                                    feedbackEl.style.color = res.success ? 'var(--ev-teal)' : '#c00';
                                }
                                if (res.success && fileInput) fileInput.value = '';
                            })
                            .catch(function(){ if (feedbackEl) { feedbackEl.textContent = 'Erreur réseau.'; feedbackEl.style.color = '#c00'; } })
                            .finally(function(){ if (submitBtn) submitBtn.disabled = false; });
                    });
                }
            })();
            </script>

            <?php endif; ?>
            <?php } ?>

        <?php elseif ($view === 'dashboard'): ?>

            <?php
            $dash_orders = VS08V_Traveler_Space::get_voyage_orders();
            $dash_upcoming = array_values(array_filter($dash_orders, function ($v) { return $v['is_upcoming']; }));
            $dash_past     = array_values(array_filter($dash_orders, function ($v) { return !$v['is_upcoming']; }));
            $dash_reminders = [];
            foreach ($dash_upcoming as $item) {
                $oid = $item['order']->get_id();
                $si  = VS08V_Traveler_Space::get_solde_info($oid);
                if (!$si || empty($si['solde_due'])) {
                    continue;
                }
                $d = $item['booking_data'];
                $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                $titre_rem  = $is_circuit ? ($d['circuit_titre'] ?? 'Circuit') : ($d['voyage_titre'] ?? 'Votre voyage');
                $dash_reminders[] = [
                    'order_id' => $oid,
                    'titre'    => $titre_rem,
                    'solde'    => $si['solde'],
                    'date_lim' => $si['solde_date'] ?? '',
                    'url'      => VS08V_Traveler_Space::voyage_url($oid),
                ];
            }
            $dash_upcoming_slice = array_slice($dash_upcoming, 0, 3);

            $post_types_new = ['vs08_voyage'];
            if (post_type_exists('vs08_circuit')) {
                $post_types_new[] = 'vs08_circuit';
            }
            $dash_latest = new WP_Query([
                'post_type'      => $post_types_new,
                'post_status'    => 'publish',
                'posts_per_page' => 6,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            ]);

            $vs08_res_url = function_exists('vs08_mega_resultats_url') ? vs08_mega_resultats_url() : home_url('/resultats-recherche');

            /** Première image utile : à la une WP puis galerie métier (souvent pas de featured image sur les CPT). */
            $ev_dash_cover = function (int $post_id): string {
                $thumb = get_the_post_thumbnail_url($post_id, 'medium');
                if ($thumb) {
                    return $thumb;
                }
                $pt = get_post_type($post_id);
                if ($pt === 'vs08_circuit' && class_exists('VS08C_Meta')) {
                    $m = VS08C_Meta::get($post_id);
                    foreach ($m['galerie'] ?? [] as $u) {
                        $u = is_string($u) ? trim($u) : '';
                        if ($u !== '') {
                            return $u;
                        }
                    }
                }
                if ($pt === 'vs08_voyage' && class_exists('VS08V_MetaBoxes')) {
                    $m = VS08V_MetaBoxes::get($post_id);
                    foreach ($m['galerie'] ?? [] as $u) {
                        if (is_string($u) && trim($u) !== '') {
                            return trim($u);
                        }
                        if (is_array($u) && !empty($u['url'])) {
                            return (string) $u['url'];
                        }
                    }
                }
                return '';
            };
            ?>

            <div class="ev-list-header ev-dash-header">
                <h1>Bonjour <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?></h1>
                <p>Votre espace Voyages Sortir 08 : rappels, dossiers, idées de voyages et message direct à l’équipe.</p>
            </div>

            <?php if (!empty($dash_reminders)) : ?>
            <section class="ev-dash-section ev-dash-alerts" aria-label="Rappels importants">
                <h2 class="ev-dash-h2">📌 Rappels</h2>
                <div class="ev-dash-alert-list">
                    <?php foreach ($dash_reminders as $dr) : ?>
                    <a href="<?php echo esc_url($dr['url']); ?>" class="ev-dash-alert-card">
                        <div class="ev-dash-alert-body">
                            <strong><?php echo esc_html($dr['titre']); ?></strong>
                            <span class="ev-dash-alert-meta">Dossier VS08-<?php echo (int) $dr['order_id']; ?> · Solde <?php echo number_format((float) $dr['solde'], 0, ',', ' '); ?> €<?php if (!empty($dr['date_lim'])) : ?> à régler avant le <?php echo esc_html($dr['date_lim']); ?><?php endif; ?></span>
                        </div>
                        <span class="ev-dash-alert-cta">Ouvrir le dossier →</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <div class="ev-dash-two">
                <section class="ev-dash-section">
                    <div class="ev-dash-section-head">
                        <h2 class="ev-dash-h2">✈️ Vos voyages</h2>
                        <a href="<?php echo esc_url(VS08V_Traveler_Space::list_url()); ?>" class="ev-dash-link-all">Tout voir →</a>
                    </div>
                    <?php if (empty($dash_orders)) : ?>
                    <div class="ev-dash-empty-inline">
                        <span class="ev-dash-empty-ic" aria-hidden="true">🧳</span>
                        <p><strong>Aucun dossier pour l’instant</strong></p>
                        <p class="ev-dash-muted">Dès votre première réservation (séjour, circuit…), elle apparaîtra ici avec le suivi et les échéances.</p>
                        <a href="<?php echo esc_url($vs08_res_url); ?>" class="ev-btn ev-btn-primary ev-dash-btn-inline">Parcourir nos voyages</a>
                    </div>
                    <?php else : ?>
                    <?php if (!empty($dash_upcoming_slice)) : ?>
                    <p class="ev-dash-sub">À venir</p>
                    <div class="ev-dash-trip-mini">
                        <?php
                        foreach ($dash_upcoming_slice as $item) :
                            $ord = $item['order'];
                            $d   = $item['booking_data'];
                            $p   = $d['params'] ?? [];
                            $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                            if ($is_circuit) {
                                $cid = (int) ($d['circuit_id'] ?? 0);
                                $mm  = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
                                $gal = $mm['galerie'] ?? [];
                                $img = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : '';
                                if (!$img) {
                                    $img = get_the_post_thumbnail_url($cid, 'medium') ?: '';
                                }
                                $titre = $d['circuit_titre'] ?? 'Circuit';
                                $dest  = $mm['destination'] ?? '';
                            } else {
                                $vid = (int) ($d['voyage_id'] ?? 0);
                                $mm  = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                                $gal = $mm['galerie'] ?? [];
                                $img = !empty($gal[0]) ? $gal[0] : '';
                                $titre = $d['voyage_titre'] ?? 'Votre voyage';
                                $dest  = $mm['destination'] ?? '';
                            }
                            $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                            ?>
                        <a href="<?php echo esc_url($lnk); ?>" class="ev-dash-trip-card">
                            <div class="ev-dash-trip-img" <?php if ($img) : ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>><?php if (!$img) : ?><span><?php echo $is_circuit ? '🗺️' : '🌍'; ?></span><?php endif; ?></div>
                            <div>
                                <strong><?php echo esc_html($titre); ?></strong>
                                <span><?php echo !empty($p['date_depart']) ? esc_html(date('d/m/Y', strtotime($p['date_depart']))) : '—'; ?><?php if ($dest) : ?> · <?php echo esc_html($dest); ?><?php endif; ?></span>
                            </div>
                        </a>
                            <?php
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php
                    $dash_past_slice = array_slice($dash_past, 0, 2);
                    if (!empty($dash_past_slice)) :
                        ?>
                    <p class="ev-dash-sub">Récemment</p>
                    <div class="ev-dash-trip-mini">
                        <?php
                        foreach ($dash_past_slice as $item) :
                            $ord = $item['order'];
                            $d   = $item['booking_data'];
                            $p   = $d['params'] ?? [];
                            $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                            if ($is_circuit) {
                                $cid = (int) ($d['circuit_id'] ?? 0);
                                $mm  = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
                                $gal = $mm['galerie'] ?? [];
                                $img = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : '';
                                if (!$img) {
                                    $img = get_the_post_thumbnail_url($cid, 'medium') ?: '';
                                }
                                $titre = $d['circuit_titre'] ?? 'Circuit';
                                $dest  = $mm['destination'] ?? '';
                            } else {
                                $vid = (int) ($d['voyage_id'] ?? 0);
                                $mm  = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                                $gal = $mm['galerie'] ?? [];
                                $img = !empty($gal[0]) ? $gal[0] : '';
                                $titre = $d['voyage_titre'] ?? 'Votre voyage';
                                $dest  = $mm['destination'] ?? '';
                            }
                            $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                            ?>
                        <a href="<?php echo esc_url($lnk); ?>" class="ev-dash-trip-card ev-dash-trip-card--past">
                            <div class="ev-dash-trip-img" <?php if ($img) : ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>><?php if (!$img) : ?><span><?php echo $is_circuit ? '🗺️' : '🌍'; ?></span><?php endif; ?></div>
                            <div>
                                <strong><?php echo esc_html($titre); ?></strong>
                                <span><?php echo !empty($p['date_depart']) ? esc_html(date('d/m/Y', strtotime($p['date_depart']))) : '—'; ?><?php if ($dest) : ?> · <?php echo esc_html($dest); ?><?php endif; ?></span>
                            </div>
                        </a>
                            <?php
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="ev-dash-section">
                    <h2 class="ev-dash-h2">✨ Nouveautés sur le site</h2>
                    <?php if ($dash_latest->have_posts()) : ?>
                    <div class="ev-dash-new-grid">
                        <?php
                        while ($dash_latest->have_posts()) :
                            $dash_latest->the_post();
                            $pid = get_the_ID();
                            $pt  = get_post_type($pid);
                            $thumb = $ev_dash_cover($pid);
                            $is_circ = ($pt === 'vs08_circuit');
                            $badge = $is_circ ? 'Circuit' : 'Séjour';
                            ?>
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="ev-dash-new-card">
                            <div class="ev-dash-new-img" <?php if ($thumb) : ?>style="background-image:url(<?php echo esc_url($thumb); ?>)"<?php endif; ?>><?php if (!$thumb) : ?><span><?php echo $is_circ ? '🗺️' : '🌍'; ?></span><?php endif; ?>
                                <span class="ev-dash-new-badge"><?php echo esc_html($badge); ?></span>
                            </div>
                            <span class="ev-dash-new-title"><?php echo esc_html(get_the_title()); ?></span>
                        </a>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                    <a href="<?php echo esc_url($vs08_res_url); ?>" class="ev-dash-link-all">Rechercher un voyage →</a>
                    <?php else : ?>
                    <p class="ev-dash-muted">Les prochaines offres seront affichées ici.</p>
                    <?php endif; ?>
                </section>
            </div>

            <div class="ev-dash-qna-row">
                <section class="ev-dash-section ev-dash-ask">
                    <h2 class="ev-dash-h2">💬 Une question ?</h2>
                    <p class="ev-dash-muted">Envoyez-nous un message — nous répondons sous 24–48h ouvrées.</p>
                    <form id="ev-dash-contact-form" class="ev-dash-form">
                        <?php $dash_user_orders = VS08V_Traveler_Space::get_voyage_orders(); ?>
                        <div class="ev-dash-form-row">
                            <label for="ev-dash-msg-order">Concerne (optionnel)</label>
                            <select id="ev-dash-msg-order" name="order_id">
                                <option value="">— Question générale —</option>
                                <?php foreach ($dash_user_orders as $uo) : ?>
                                    <?php
                                    $uo_d = $uo['booking_data'];
                                    $uo_titre = $uo_d['voyage_titre'] ?? ($uo_d['circuit_titre'] ?? 'Voyage');
                                    $uo_date = !empty($uo_d['params']['date_depart']) ? date('d/m/Y', strtotime($uo_d['params']['date_depart'])) : '';
                                    ?>
                                <option value="<?php echo $uo['order']->get_id(); ?>">VS08-<?php echo $uo['order']->get_id(); ?> — <?php echo esc_html($uo_titre); ?><?php if ($uo_date) : ?> (<?php echo esc_html($uo_date); ?>)<?php endif; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ev-dash-form-row">
                            <label for="ev-dash-msg-sujet">Sujet *</label>
                            <input type="text" id="ev-dash-msg-sujet" name="sujet" required placeholder="Ex. : modification, question sur mon dossier…">
                        </div>
                        <div class="ev-dash-form-row">
                            <label for="ev-dash-msg-body">Message *</label>
                            <textarea id="ev-dash-msg-body" name="message" rows="4" required placeholder="Votre message…"></textarea>
                        </div>
                        <button type="submit" class="ev-btn ev-btn-primary" id="ev-dash-msg-submit">Envoyer</button>
                        <p class="ev-dash-msg-feedback" id="ev-dash-msg-feedback" hidden></p>
                    </form>
                </section>
                <aside class="ev-dash-section ev-dash-loyalty" aria-label="Conseils">
                    <h3 class="ev-dash-h3">💡 Pour vous accompagner</h3>
                    <ul class="ev-dash-loyalty-list">
                        <li><a href="<?php echo esc_url(VS08V_Traveler_Space::favoris_url()); ?>">Vos favoris</a> — retrouvez les formules enregistrées.</li>
                        <li><a href="<?php echo esc_url(home_url('/espace-voyageur/contact/')); ?>">Contact complet</a> — historique des messages et coordonnées.</li>
                        <li>Pensez à finaliser tôt votre dossier pour les meilleures disponibilités vols et hébergements.</li>
                    </ul>
                </aside>
            </div>

            <script>
            (function(){
                var form = document.getElementById('ev-dash-contact-form');
                var fb = document.getElementById('ev-dash-msg-feedback');
                var btn = document.getElementById('ev-dash-msg-submit');
                if (!form || !fb || !btn) return;
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var sujet = document.getElementById('ev-dash-msg-sujet').value.trim();
                    var message = document.getElementById('ev-dash-msg-body').value.trim();
                    var orderId = document.getElementById('ev-dash-msg-order').value;
                    if (!sujet || !message) {
                        fb.hidden = false;
                        fb.className = 'ev-dash-msg-feedback error';
                        fb.textContent = 'Veuillez remplir le sujet et le message.';
                        return;
                    }
                    btn.disabled = true;
                    var prev = btn.textContent;
                    btn.textContent = 'Envoi…';
                    fb.hidden = true;
                    var fd = new FormData();
                    fd.append('action', 'vs08v_member_contact');
                    fd.append('nonce', '<?php echo esc_js(wp_create_nonce('vs08v_member_contact')); ?>');
                    fd.append('sujet', sujet);
                    fd.append('message', message);
                    fd.append('order_id', orderId);
                    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            btn.disabled = false;
                            btn.textContent = prev;
                            fb.hidden = false;
                            if (res.success) {
                                fb.className = 'ev-dash-msg-feedback success';
                                fb.textContent = typeof res.data === 'string' ? res.data : 'Message envoyé !';
                                form.reset();
                            } else {
                                fb.className = 'ev-dash-msg-feedback error';
                                fb.textContent = (typeof res.data === 'string' ? res.data : (res.data && res.data.message)) || 'Erreur lors de l’envoi.';
                            }
                        })
                        .catch(function(){
                            btn.disabled = false;
                            btn.textContent = prev;
                            fb.hidden = false;
                            fb.className = 'ev-dash-msg-feedback error';
                            fb.textContent = 'Erreur réseau. Réessayez.';
                        });
                });
            })();
            </script>

        <?php elseif ($view === 'profil'): ?>

            <div class="ev-list-header">
                <h1>Mon profil</h1>
                <p>Ces informations pourront être pré-remplies lors de vos prochaines réservations. Photo de profil optionnelle.</p>
            </div>

            <div class="ev-cards-profil-voyageurs">
            <section class="ev-card ev-card-profil">
                <form id="ev-profile-form" class="ev-profile-form">
                    <div class="ev-profile-photo-row">
                        <div class="ev-profile-photo-wrap">
                            <?php if ($profile_photo_url): ?>
                            <div class="ev-profile-photo-preview" id="ev-profile-photo-preview" style="background-image:url(<?php echo esc_url($profile_photo_url); ?>);"></div>
                            <?php else: ?>
                            <div class="ev-profile-photo-preview ev-profile-photo-placeholder" id="ev-profile-photo-preview">👤</div>
                            <?php endif; ?>
                            <input type="hidden" name="profile_photo_id" id="ev-profile-photo-id" value="<?php echo (int) $profile_photo_id; ?>">
                            <label for="ev-profile-photo-input" class="ev-btn ev-btn-ghost ev-profile-photo-btn">Changer la photo</label>
                            <input type="file" id="ev-profile-photo-input" accept="image/*" style="position:absolute;opacity:0;width:0;height:0;">
                        </div>
                    </div>
                    <div class="ev-profile-grid">
                        <p class="ev-field">
                            <label for="ev-profil-prenom">Prénom</label>
                            <input type="text" id="ev-profil-prenom" name="prenom" value="<?php echo esc_attr($saved_facturation['prenom'] ?? $current_user->first_name); ?>">
                        </p>
                        <p class="ev-field">
                            <label for="ev-profil-nom">Nom</label>
                            <input type="text" id="ev-profil-nom" name="nom" value="<?php echo esc_attr($saved_facturation['nom'] ?? $current_user->last_name); ?>">
                        </p>
                        <p class="ev-field ev-field-full">
                            <label for="ev-profil-email">Email</label>
                            <input type="email" id="ev-profil-email" name="email" value="<?php echo esc_attr($saved_facturation['email'] ?? $current_user->user_email); ?>">
                        </p>
                        <p class="ev-field ev-field-full">
                            <label for="ev-profil-tel">Téléphone</label>
                            <input type="text" id="ev-profil-tel" name="tel" value="<?php echo esc_attr($saved_facturation['tel'] ?? ''); ?>" placeholder="06 12 34 56 78">
                        </p>
                        <p class="ev-field ev-field-full">
                            <label for="ev-profil-adresse">Adresse (facturation)</label>
                            <textarea id="ev-profil-adresse" name="adresse" rows="2" placeholder="Numéro et voie"><?php echo esc_textarea($saved_facturation['adresse'] ?? ''); ?></textarea>
                        </p>
                        <p class="ev-field">
                            <label for="ev-profil-cp">Code postal</label>
                            <input type="text" id="ev-profil-cp" name="cp" value="<?php echo esc_attr($saved_facturation['cp'] ?? ''); ?>" placeholder="08000">
                        </p>
                        <p class="ev-field">
                            <label for="ev-profil-ville">Ville</label>
                            <input type="text" id="ev-profil-ville" name="ville" value="<?php echo esc_attr($saved_facturation['ville'] ?? ''); ?>" placeholder="Charleville-Mézières">
                        </p>
                    </div>
                    <div class="ev-form-actions ev-profile-actions">
                        <button type="submit" class="ev-btn ev-btn-primary">Enregistrer mon profil</button>
                    </div>
                </form>
            </section>

            <section class="ev-card ev-card-voyageurs">
                <h2 class="ev-card-title">Voyageurs réguliers</h2>
                <p class="ev-card-desc">Enregistrez des voyageurs (vous, conjoint, amis) pour pré-remplir leurs infos lors de vos réservations. Le type golfeur / non-golfeur se choisit sur la page du séjour.</p>
                <ul class="ev-voyageurs-list" id="ev-voyageurs-list">
                    <?php foreach ($saved_voyageurs as $i => $v): ?>
                    <li class="ev-voyageur-item" data-index="<?php echo (int) $i; ?>">
                        <span class="ev-voyageur-nom"><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?></span>
                        <button type="button" class="ev-btn ev-btn-ghost ev-voyageur-edit" data-index="<?php echo (int) $i; ?>" aria-label="Modifier">Modifier</button>
                        <button type="button" class="ev-btn ev-btn-ghost ev-voyageur-delete" data-index="<?php echo (int) $i; ?>" aria-label="Supprimer">Supprimer</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form id="ev-voyageur-form" class="ev-voyageur-form ev-profile-form">
                    <input type="hidden" name="index" id="ev-voyageur-index" value="-1">
                    <div class="ev-profile-grid">
                        <p class="ev-field">
                            <label for="ev-voy-prenom">Prénom *</label>
                            <input type="text" id="ev-voy-prenom" name="prenom" required placeholder="Jean">
                        </p>
                        <p class="ev-field">
                            <label for="ev-voy-nom">Nom *</label>
                            <input type="text" id="ev-voy-nom" name="nom" required placeholder="Dupont">
                        </p>
                        <p class="ev-field">
                            <label for="ev-voy-ddn-trigger">Date de naissance</label>
                            <div id="ev-voy-ddn-wrap" class="ev-ddn-wrap" style="position:relative">
                                <div id="ev-voy-ddn-trigger" class="ev-ddn-trigger" role="button" tabindex="0" aria-label="Choisir la date de naissance" onclick="if(window.evCalDDN) window.evCalDDN.toggle()">🎂 JJ/MM/AAAA</div>
                                <input type="hidden" id="ev-voy-ddn" name="ddn" value="">
                            </div>
                        </p>
                        <p class="ev-field">
                            <label for="ev-voy-passeport">N° Passeport</label>
                            <input type="text" id="ev-voy-passeport" name="passeport" placeholder="XX000000">
                        </p>
                    </div>
                    <div class="ev-form-actions">
                        <button type="submit" class="ev-btn ev-btn-primary">Ajouter ce voyageur</button>
                        <button type="button" class="ev-btn ev-btn-ghost ev-voyageur-cancel" style="display:none">Annuler</button>
                    </div>
                </form>
            </section>
            </div>
            <script>
            (function(){
                var nonce = '<?php echo esc_js(wp_create_nonce('vs08v_traveler')); ?>';
                var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                var form = document.getElementById('ev-profile-form');
                var photoInput = document.getElementById('ev-profile-photo-input');
                var photoIdInput = document.getElementById('ev-profile-photo-id');
                var preview = document.getElementById('ev-profile-photo-preview');

                photoInput.addEventListener('change', function(){
                    if (!this.files || !this.files[0]) return;
                    var fd = new FormData();
                    fd.append('action', 'vs08v_traveler_profile_photo');
                    fd.append('nonce', nonce);
                    fd.append('photo', this.files[0]);
                    fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            if (res.success && res.data.url) {
                                photoIdInput.value = res.data.attachment_id || '';
                                preview.style.backgroundImage = 'url(' + res.data.url + ')';
                                preview.classList.remove('ev-profile-photo-placeholder');
                                preview.textContent = '';
                            }
                        });
                });

                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var btn = form.querySelector('[type=submit]');
                    btn.disabled = true;
                    btn.textContent = 'Enregistrement...';
                    var fd = new FormData(form);
                    fd.append('action', 'vs08v_traveler_profile_save');
                    fd.append('nonce', nonce);
                    fd.append('profile_photo_id', photoIdInput.value);
                    fd.append('prenom', document.getElementById('ev-profil-prenom').value);
                    fd.append('nom', document.getElementById('ev-profil-nom').value);
                    fd.append('email', document.getElementById('ev-profil-email').value);
                    fd.append('tel', document.getElementById('ev-profil-tel').value);
                    fd.append('adresse', document.getElementById('ev-profil-adresse').value);
                    fd.append('cp', document.getElementById('ev-profil-cp').value);
                    fd.append('ville', document.getElementById('ev-profil-ville').value);
                    fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            alert(res.success ? (res.data.message || 'Profil enregistré.') : (res.data && res.data.message || 'Erreur'));
                            if (res.success) btn.textContent = 'Enregistrer mon profil';
                        })
                        .catch(function(){ alert('Erreur réseau.'); })
                        .finally(function(){ btn.disabled = false; });
                });

                var voyageursList = document.getElementById('ev-voyageurs-list');
                var voyageurForm = document.getElementById('ev-voyageur-form');
                var voyageurIndexInput = document.getElementById('ev-voyageur-index');
                var voyageursData = <?php echo json_encode(array_values($saved_voyageurs)); ?>;

                function evInitCalDDN() {
                    if (typeof VS08Calendar === 'undefined') return;
                    if (window.evCalDDN) return;
                    var evCalDDN = new VS08Calendar({
                        el: '#ev-voy-ddn-wrap',
                        mode: 'date',
                        inline: false,
                        input: '#ev-voy-ddn',
                        title: 'Date de naissance',
                        subtitle: 'Voyageur régulier',
                        yearRange: [1920, new Date().getFullYear()],
                        maxDate: new Date(),
                        onConfirm: function(dt) {
                            var trigger = document.getElementById('ev-voy-ddn-trigger');
                            if (trigger && dt) {
                                var d = new Date(dt);
                                trigger.textContent = '🎂 ' + d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
                                trigger.style.color = '#0f2424';
                                trigger.style.fontWeight = '600';
                                trigger.style.borderColor = '#3d9a9a';
                            }
                        }
                    });
                    window.evCalDDN = evCalDDN;
                }
                if (typeof VS08Calendar !== 'undefined') {
                    evInitCalDDN();
                } else {
                    document.addEventListener('DOMContentLoaded', evInitCalDDN);
                    window.addEventListener('load', evInitCalDDN);
                }

                function parseDDN(str) {
                    if (!str || !String(str).trim()) return '';
                    var s = String(str).trim();
                    var m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                    if (m) return m[3] + '-' + m[2].padStart(2,'0') + '-' + m[1].padStart(2,'0');
                    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
                    return s;
                }
                function formatDDNDisplay(iso) {
                    if (!iso) return '🎂 JJ/MM/AAAA';
                    var normalized = parseDDN(iso);
                    var d = new Date(normalized + 'T12:00:00');
                    if (isNaN(d.getTime())) return '🎂 JJ/MM/AAAA';
                    return '🎂 ' + d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
                }
                function renderVoyageursList() {
                    if (!voyageursList) return;
                    voyageursList.innerHTML = voyageursData.map(function(v, i) {
                        return '<li class="ev-voyageur-item" data-index="' + i + '">' +
                            '<span class="ev-voyageur-nom">' + (v.prenom || '') + ' ' + (v.nom || '').toUpperCase() + '</span> ' +
                            '<button type="button" class="ev-btn ev-btn-ghost ev-voyageur-edit" data-index="' + i + '" aria-label="Modifier">Modifier</button> ' +
                            '<button type="button" class="ev-btn ev-btn-ghost ev-voyageur-delete" data-index="' + i + '" aria-label="Supprimer">Supprimer</button></li>';
                    }).join('');
                }
                if (voyageursList) {
                    voyageursList.addEventListener('click', function(e) {
                        var edit = e.target.closest('.ev-voyageur-edit');
                        var del = e.target.closest('.ev-voyageur-delete');
                        if (edit) onEditVoyageur.call(edit);
                        if (del) onDeleteVoyageur.call(del);
                    });
                }
                function onEditVoyageur() {
                    var i = parseInt(this.dataset.index, 10);
                    var v = voyageursData[i];
                    if (!v) return;
                    document.getElementById('ev-voy-prenom').value = v.prenom || '';
                    document.getElementById('ev-voy-nom').value = v.nom || '';
                    var ddnInp = document.getElementById('ev-voy-ddn');
                    var ddnTrigger = document.getElementById('ev-voy-ddn-trigger');
                    var ddnVal = parseDDN(v.ddn || '') || (v.ddn || '');
                    if (ddnInp) ddnInp.value = ddnVal;
                    if (ddnTrigger) {
                        ddnTrigger.textContent = formatDDNDisplay(ddnVal);
                        ddnTrigger.style.color = ddnVal ? '#0f2424' : '';
                        ddnTrigger.style.fontWeight = ddnVal ? '600' : '';
                        ddnTrigger.style.borderColor = ddnVal ? '#3d9a9a' : '';
                    }
                    document.getElementById('ev-voy-passeport').value = v.passeport || '';
                    voyageurIndexInput.value = i;
                    voyageurForm.querySelector('button[type="submit"]').textContent = 'Mettre à jour';
                    voyageurForm.querySelector('.ev-voyageur-cancel').style.display = 'inline-block';
                }
                function onDeleteVoyageur() {
                    var i = parseInt(this.dataset.index, 10);
                    if (!confirm('Retirer ce voyageur de la liste ?')) return;
                    var fd = new FormData();
                    fd.append('action', 'vs08v_traveler_voyageur_delete');
                    fd.append('nonce', nonce);
                    fd.append('index', i);
                    fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            if (res.success && res.data.voyageurs) { voyageursData = res.data.voyageurs; renderVoyageursList(); }
                            else alert(res.data && res.data.message || 'Erreur');
                        });
                }
                if (voyageurForm) {
                    voyageurForm.querySelector('.ev-voyageur-cancel').addEventListener('click', function(){
                        voyageurIndexInput.value = '-1';
                        voyageurForm.reset();
                        var ddnTrigger = document.getElementById('ev-voy-ddn-trigger');
                        if (ddnTrigger) { ddnTrigger.textContent = '🎂 JJ/MM/AAAA'; ddnTrigger.style.color = ''; ddnTrigger.style.fontWeight = ''; ddnTrigger.style.borderColor = ''; }
                        document.getElementById('ev-voy-ddn').value = '';
                        voyageurForm.querySelector('button[type="submit"]').textContent = 'Ajouter ce voyageur';
                        this.style.display = 'none';
                    });
                    voyageurForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        var prenom = document.getElementById('ev-voy-prenom').value.trim();
                        var nom = document.getElementById('ev-voy-nom').value.trim();
                        if (!prenom || !nom) { alert('Prénom et nom obligatoires.'); return; }
                        var fd = new FormData();
                        fd.append('action', 'vs08v_traveler_voyageur_save');
                        fd.append('nonce', nonce);
                        fd.append('prenom', prenom);
                        fd.append('nom', nom);
                        fd.append('ddn', document.getElementById('ev-voy-ddn').value);
                        fd.append('passeport', document.getElementById('ev-voy-passeport').value);
                        fd.append('type', 'golfeur');
                        fd.append('index', voyageurIndexInput.value);
                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                if (res.success && res.data.voyageurs) {
                                    voyageursData = res.data.voyageurs;
                                    renderVoyageursList();
                                    voyageurIndexInput.value = '-1';
                                    voyageurForm.reset();
                                    var ddnTrigger = document.getElementById('ev-voy-ddn-trigger');
                                    if (ddnTrigger) { ddnTrigger.textContent = '🎂 JJ/MM/AAAA'; ddnTrigger.style.color = ''; ddnTrigger.style.fontWeight = ''; ddnTrigger.style.borderColor = ''; }
                                    document.getElementById('ev-voy-ddn').value = '';
                                    voyageurForm.querySelector('button[type="submit"]').textContent = 'Ajouter ce voyageur';
                                    voyageurForm.querySelector('.ev-voyageur-cancel').style.display = 'none';
                                    alert(res.data.message || 'Voyageur enregistré.');
                                } else alert(res.data && res.data.message || 'Erreur');
                            })
                            .catch(function(){ alert('Erreur réseau.'); });
                    });
                }
            })();
            </script>

        <?php elseif ($view === 'favoris'): ?>

            <?php
            $wishlist_ids = VS08V_Traveler_Space::get_wishlist($current_user->ID);
            $wishlist_posts = [];
            foreach ($wishlist_ids as $pid) {
                $p = get_post($pid);
                if ($p && $p->post_type === 'vs08_voyage' && $p->post_status === 'publish') {
                    $wishlist_posts[] = $p;
                }
            }
            ?>
            <div class="ev-list-header">
                <h1>Mes favoris</h1>
                <p>Séjours que vous avez mis de côté. Réservez quand vous voulez.</p>
            </div>
            <?php if (empty($wishlist_posts)): ?>
            <div class="ev-empty-state">
                <div class="ev-empty-icon">❤</div>
                <h2>Aucun séjour en favori</h2>
                <p>Parcourez nos séjours golf et cliquez sur « Ajouter à ma liste » pour les retrouver ici.</p>
                <a href="<?php echo esc_url(home_url('/golf')); ?>" class="ev-btn ev-btn-primary">Voir les séjours</a>
            </div>
            <?php else: ?>
            <div class="ev-grid">
                <?php foreach ($wishlist_posts as $p):
                    $vid = $p->ID;
                    $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                    $gal = $mm['galerie'] ?? [];
                    $img = !empty($gal[0]) ? $gal[0] : '';
                    $dest = $mm['destination'] ?? '';
                    $lnk = get_permalink($p);
                ?>
                <article class="ev-trip-card ev-trip-card-favori">
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <div class="ev-trip-img" <?php if ($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                            <?php if (!$img): ?><span class="ev-trip-placeholder">🌍</span><?php endif; ?>
                        </div>
                        <div class="ev-trip-body">
                            <h3><?php echo esc_html($p->post_title); ?></h3>
                            <?php if ($dest): ?><p class="ev-trip-meta"><?php echo esc_html($dest); ?></p><?php endif; ?>
                            <span class="ev-trip-cta">Voir le séjour →</span>
                        </div>
                    </a>
                    <button type="button" class="ev-favori-remove ev-btn ev-btn-ghost" data-voyage-id="<?php echo $vid; ?>" title="Retirer des favoris">Retirer</button>
                </article>
                <?php endforeach; ?>
            </div>
            <script>
            (function(){
                var nonce = '<?php echo esc_js(wp_create_nonce('vs08v_traveler')); ?>';
                var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                document.querySelectorAll('.ev-favori-remove').forEach(function(btn){
                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        var vid = this.dataset.voyageId;
                        var card = this.closest('.ev-trip-card-favori');
                        var fd = new FormData();
                        fd.append('action', 'vs08v_traveler_wishlist');
                        fd.append('nonce', nonce);
                        fd.append('voyage_id', vid);
                        fd.append('wishlist_action', 'remove');
                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res){ if (res.success) card.remove(); });
                    });
                });
            })();
            </script>
            <?php endif; ?>

        <?php elseif ($view === 'contact'): ?>

            <div class="ev-list-header">
                <h1>Nous contacter</h1>
                <p>Envoyez-nous un message, nous vous répondrons dans les meilleurs délais.</p>
            </div>

            <style>
            .ev-msg-grid{display:grid;grid-template-columns:1fr 320px;gap:24px;margin-top:8px}
            .ev-msg-form-card{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05)}
            .ev-msg-form-card h2{font-family:'Playfair Display',serif;font-size:20px;color:#0f2424;margin:0 0 20px}
            .ev-msg-field{margin-bottom:16px}
            .ev-msg-field label{display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;font-family:'Outfit',sans-serif}
            .ev-msg-field select,.ev-msg-field input,.ev-msg-field textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fff;transition:border-color .2s;resize:vertical}
            .ev-msg-field select:focus,.ev-msg-field input:focus,.ev-msg-field textarea:focus{outline:none;border-color:#59b7b7}
            .ev-msg-btn{width:100%;padding:14px;background:#59b7b7;color:#fff;border:none;border-radius:100px;font-size:15px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .25s}
            .ev-msg-btn:hover{background:#3d9a9a;transform:translateY(-1px)}
            .ev-msg-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
            .ev-msg-feedback{margin-top:12px;padding:12px 16px;border-radius:12px;font-size:13px;font-family:'Outfit',sans-serif;display:none}
            .ev-msg-feedback.success{display:block;background:#edf8f0;color:#059669;border:1px solid #a7f3d0}
            .ev-msg-feedback.error{display:block;background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
            .ev-msg-info{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05);align-self:start}
            .ev-msg-info h3{font-size:14px;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;margin:0 0 16px;font-family:'Outfit',sans-serif;font-weight:700}
            .ev-msg-info-item{display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid #f0ece4}
            .ev-msg-info-item:last-child{border-bottom:none}
            .ev-msg-info-ic{font-size:20px;flex-shrink:0;margin-top:2px}
            .ev-msg-info-text{font-family:'Outfit',sans-serif;font-size:13px;color:#4a5568;line-height:1.5}
            .ev-msg-info-text strong{color:#0f2424;display:block;margin-bottom:2px}
            .ev-msg-info-text a{color:#59b7b7;text-decoration:none;font-weight:600}
            .ev-msg-sent-list{margin-top:24px}
            .ev-msg-sent-list h3{font-size:14px;color:#0f2424;margin:0 0 12px;font-family:'Outfit',sans-serif;font-weight:700}
            .ev-msg-sent-item{background:#f9f6f0;border-radius:12px;padding:14px 16px;margin-bottom:8px;font-family:'Outfit',sans-serif}
            .ev-msg-sent-item .ev-msg-sent-head{display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;margin-bottom:6px}
            .ev-msg-sent-item .ev-msg-sent-subj{font-size:13px;font-weight:700;color:#0f2424}
            .ev-msg-sent-item .ev-msg-sent-body{font-size:12px;color:#6b7280;margin-top:4px;line-height:1.5}
            @keyframes evMsgSlideIn{from{opacity:0;transform:translateY(-10px);max-height:0}to{opacity:1;transform:translateY(0);max-height:200px}}
            @media(max-width:768px){.ev-msg-grid{grid-template-columns:1fr}}
            </style>

            <div class="ev-msg-grid">
                <div class="ev-msg-form-card">
                    <h2>✉️ Nouveau message</h2>
                    <form id="ev-contact-form">
                        <?php
                        // Lister les voyages du client pour le sélecteur
                        $user_orders = VS08V_Traveler_Space::get_voyage_orders();
                        ?>
                        <div class="ev-msg-field">
                            <label>Concerne (optionnel)</label>
                            <select name="order_id" id="ev-msg-order">
                                <option value="">— Question générale —</option>
                                <?php foreach ($user_orders as $uo):
                                    $uo_d = $uo['booking_data'];
                                    $uo_titre = $uo_d['voyage_titre'] ?? ($uo_d['circuit_titre'] ?? 'Voyage');
                                    $uo_date = !empty($uo_d['params']['date_depart']) ? date('d/m/Y', strtotime($uo_d['params']['date_depart'])) : '';
                                ?>
                                <option value="<?php echo $uo['order']->get_id(); ?>">VS08-<?php echo $uo['order']->get_id(); ?> — <?php echo esc_html($uo_titre); ?><?php if ($uo_date): ?> (<?php echo $uo_date; ?>)<?php endif; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ev-msg-field">
                            <label>Sujet *</label>
                            <input type="text" name="sujet" id="ev-msg-sujet" required placeholder="Ex : Question sur mon vol, modification de dates...">
                        </div>
                        <div class="ev-msg-field">
                            <label>Message *</label>
                            <textarea name="message" id="ev-msg-body" rows="6" required placeholder="Écrivez votre message ici..."></textarea>
                        </div>
                        <button type="submit" class="ev-msg-btn" id="ev-msg-submit">Envoyer le message →</button>
                        <div class="ev-msg-feedback" id="ev-msg-feedback"></div>
                    </form>

                    <?php
                    // Afficher les messages déjà envoyés
                    $sent_messages = get_user_meta($current_user->ID, '_vs08_messages_sent', true);
                    if (!is_array($sent_messages)) $sent_messages = [];
                    $sent_messages = array_reverse(array_slice($sent_messages, -10)); // 10 derniers
                    ?>
                    <div class="ev-msg-sent-list" id="ev-msg-sent-list" <?php if (empty($sent_messages)): ?>style="display:none"<?php endif; ?>>
                        <h3>📨 Messages envoyés</h3>
                        <div id="ev-msg-sent-items">
                        <?php foreach ($sent_messages as $sm): ?>
                        <div class="ev-msg-sent-item">
                            <div class="ev-msg-sent-head">
                                <span><?php echo esc_html($sm['date'] ?? ''); ?></span>
                                <?php if (!empty($sm['order_id'])): ?><span>Dossier VS08-<?php echo intval($sm['order_id']); ?></span><?php endif; ?>
                            </div>
                            <div class="ev-msg-sent-subj"><?php echo esc_html($sm['sujet'] ?? ''); ?></div>
                            <div class="ev-msg-sent-body"><?php echo esc_html(mb_substr($sm['message'] ?? '', 0, 120)); ?><?php echo mb_strlen($sm['message'] ?? '') > 120 ? '…' : ''; ?></div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="ev-msg-info">
                    <h3>Coordonnées</h3>
                    <div class="ev-msg-info-item">
                        <span class="ev-msg-info-ic">📞</span>
                        <div class="ev-msg-info-text"><strong>Téléphone</strong><a href="tel:0326652863">03 26 65 28 63</a><br>Lun — Ven · 9h — 18h30</div>
                    </div>
                    <div class="ev-msg-info-item">
                        <span class="ev-msg-info-ic">✉️</span>
                        <div class="ev-msg-info-text"><strong>Email</strong><a href="mailto:resa@voyagessortir08.com">resa@voyagessortir08.com</a></div>
                    </div>
                    <div class="ev-msg-info-item">
                        <span class="ev-msg-info-ic">📍</span>
                        <div class="ev-msg-info-text"><strong>En agence</strong>24 rue Léon Bourgeois<br>51000 Châlons-en-Champagne</div>
                    </div>
                    <div class="ev-msg-info-item">
                        <span class="ev-msg-info-ic">💬</span>
                        <div class="ev-msg-info-text"><strong>WhatsApp</strong><a href="https://wa.me/33326652863">Nous écrire sur WhatsApp</a></div>
                    </div>
                </div>
            </div>

            <script>
            (function(){
                var form = document.getElementById('ev-contact-form');
                var fb = document.getElementById('ev-msg-feedback');
                var btn = document.getElementById('ev-msg-submit');
                if (!form) return;
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var sujetEl = document.getElementById('ev-msg-sujet');
                    var msgEl = document.getElementById('ev-msg-body');
                    var orderEl = document.getElementById('ev-msg-order');
                    var sujet = sujetEl.value.trim();
                    var message = msgEl.value.trim();
                    var orderId = orderEl.value;
                    if (!sujet || !message) { fb.className='ev-msg-feedback error'; fb.textContent='Veuillez remplir tous les champs.'; return; }
                    btn.disabled = true; btn.textContent = 'Envoi en cours…';
                    fb.className='ev-msg-feedback'; fb.style.display='none';
                    var fd = new FormData();
                    fd.append('action', 'vs08v_member_contact');
                    fd.append('nonce', '<?php echo esc_js(wp_create_nonce('vs08v_member_contact')); ?>');
                    fd.append('sujet', sujet);
                    fd.append('message', message);
                    fd.append('order_id', orderId);
                    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {method:'POST', body:fd})
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            btn.disabled = false; btn.textContent = 'Envoyer le message →';
                            if (res.success) {
                                fb.className='ev-msg-feedback success';
                                fb.textContent='✅ Message envoyé avec succès ! Nous vous répondrons dans les meilleurs délais.';
                                // Injecter le message dans la liste immédiatement
                                var list = document.getElementById('ev-msg-sent-list');
                                var items = document.getElementById('ev-msg-sent-items');
                                if (list && items) {
                                    list.style.display = '';
                                    var now = new Date();
                                    var dateFmt = String(now.getDate()).padStart(2,'0') + '/' + String(now.getMonth()+1).padStart(2,'0') + '/' + now.getFullYear() + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
                                    var dossierTxt = orderId ? 'Dossier VS08-' + orderId : '';
                                    var preview = message.length > 120 ? message.substring(0, 120) + '…' : message;
                                    var newItem = document.createElement('div');
                                    newItem.className = 'ev-msg-sent-item';
                                    newItem.style.animation = 'evMsgSlideIn .4s ease';
                                    newItem.innerHTML = '<div class="ev-msg-sent-head"><span>' + dateFmt + '</span>' + (dossierTxt ? '<span>' + dossierTxt + '</span>' : '') + '</div>'
                                        + '<div class="ev-msg-sent-subj">' + sujet.replace(/</g,'&lt;') + '</div>'
                                        + '<div class="ev-msg-sent-body">' + preview.replace(/</g,'&lt;') + '</div>';
                                    items.insertBefore(newItem, items.firstChild);
                                }
                                form.reset();
                                // Scroll vers le feedback
                                fb.scrollIntoView({behavior:'smooth', block:'nearest'});
                            } else {
                                fb.className='ev-msg-feedback error'; fb.textContent=res.data || 'Erreur lors de l\'envoi.';
                            }
                        }).catch(function(){
                            btn.disabled = false; btn.textContent = 'Envoyer le message →';
                            fb.className='ev-msg-feedback error'; fb.textContent='Erreur de connexion. Réessayez.';
                        });
                });
            })();
            </script>

        <?php else: ?>

            <?php
            $voyage_orders = VS08V_Traveler_Space::get_voyage_orders();
            $upcoming = array_filter($voyage_orders, function ($v) { return $v['is_upcoming']; });
            $past     = array_filter($voyage_orders, function ($v) { return !$v['is_upcoming']; });
            $has_any  = !empty($voyage_orders);
            ?>

            <div class="ev-list-header">
                <h1>Mes voyages</h1>
                <p>Bienvenue <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>. Vos dossiers séjours et circuits : détails, soldes et documents.</p>
            </div>

            <?php if ($has_any): ?>

            <div class="ev-tabs">
                <button class="ev-tab active" data-tab="upcoming">À venir</button>
                <button class="ev-tab" data-tab="past">Passés</button>
                <button class="ev-tab" data-tab="all">Tous</button>
            </div>

            <!-- ── Panel : À venir ── -->
            <div class="ev-panel" data-panel="upcoming">
                <?php if (empty($upcoming)): ?>
                    <p class="ev-empty-panel">Aucun voyage à venir pour le moment.</p>
                <?php else: ?>
                    <div class="ev-grid">
                    <?php foreach ($upcoming as $item):
                        $ord  = $item['order'];
                        $d    = $item['booking_data'];
                        $p    = $d['params'] ?? [];
                        $dv   = $d['devis'] ?? [];
                        $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                        if ($is_circuit):
                            $cid = (int)($d['circuit_id'] ?? 0);
                            $mm = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : '';
                            if (!$img) $img = get_the_post_thumbnail_url($cid, 'medium');
                            $dest = $mm['destination'] ?? ''; $hnom = '';
                            $titre = $d['circuit_titre'] ?? 'Circuit';
                            $si = VS08V_Traveler_Space::get_solde_info($ord->get_id());
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0);
                            $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : '';
                            $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? '');
                            $titre = $d['voyage_titre'] ?? 'Votre voyage';
                            $si = VS08V_Traveler_Space::get_solde_info($ord->get_id());
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '🌍'; ?></span><?php endif; ?>
                                <span class="ev-badge ev-badge-upcoming">À venir</span>
                                <?php if ($is_circuit): ?><span class="ev-badge ev-badge-circuit">Circuit</span><?php endif; ?>
                                <?php if ($si && $si['solde_due']): ?>
                                <span class="ev-badge ev-badge-solde">Solde à régler<?php if (!empty($si['solde_date'])): ?><span class="ev-badge-solde-date">avant le <?php echo esc_html($si['solde_date']); ?></span><?php endif; ?></span>
                                <?php elseif ($si && !empty($si['soldé_paye'])): ?>
                                <span class="ev-badge ev-badge-solde-paid">Soldé</span>
                                <?php endif; ?>
                            </div>
                            <div class="ev-trip-body">
                                <h3><?php echo esc_html($titre); ?></h3>
                                <p class="ev-trip-meta"><?php echo $p['date_depart'] ? esc_html(date('d/m/Y', strtotime($p['date_depart']))) : ''; ?><?php if($dest): ?> — <?php echo esc_html($dest); ?><?php endif; ?></p>
                                <?php if($hnom): ?><p class="ev-trip-hotel"><?php echo esc_html($hnom); ?></p><?php endif; ?>
                                <p class="ev-trip-pax"><?php echo (int)($dv['nb_total'] ?? 0); ?> voyageur(s) · VS08-<?php echo $ord->get_id(); ?></p>
                                <?php if ($si && $si['solde_due']): ?>
                                <p class="ev-trip-solde">Solde : <?php echo number_format($si['solde'], 0, ',', ' '); ?> €<?php if (!empty($si['solde_date'])): ?> avant le <?php echo esc_html($si['solde_date']); ?><?php endif; ?></p>
                                <?php endif; ?>
                                <span class="ev-trip-cta"><?php echo $is_circuit ? 'Voir le circuit →' : 'Voir le voyage →'; ?></span>
                            </div>
                        </article>
                    </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Panel : Passés ── -->
            <div class="ev-panel" data-panel="past" style="display:none">
                <?php if (empty($past)): ?>
                    <p class="ev-empty-panel">Aucun voyage passé.</p>
                <?php else: ?>
                    <div class="ev-grid">
                    <?php foreach ($past as $item):
                        $ord = $item['order']; $d = $item['booking_data']; $p = $d['params'] ?? []; $dv = $d['devis'] ?? [];
                        $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                        if ($is_circuit):
                            $cid = (int)($d['circuit_id'] ?? 0); $mm = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : '';
                            if (!$img) $img = get_the_post_thumbnail_url($cid, 'medium');
                            $dest = $mm['destination'] ?? ''; $hnom = ''; $titre = $d['circuit_titre'] ?? 'Circuit';
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0); $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? ''); $titre = $d['voyage_titre'] ?? 'Votre voyage';
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '🌍'; ?></span><?php endif; ?>
                                <span class="ev-badge ev-badge-past">Passé</span>
                                <?php if ($is_circuit): ?><span class="ev-badge ev-badge-circuit">Circuit</span><?php endif; ?>
                                <?php
                                $si_past = VS08V_Traveler_Space::get_solde_info($ord->get_id());
                                if ($si_past && !empty($si_past['soldé_paye'])): ?>
                                <span class="ev-badge ev-badge-solde-paid">Soldé ✓</span>
                                <?php endif; ?>
                            </div>
                            <div class="ev-trip-body">
                                <h3><?php echo esc_html($titre); ?></h3>
                                <p class="ev-trip-meta"><?php echo $p['date_depart'] ? esc_html(date('d/m/Y', strtotime($p['date_depart']))) : ''; ?><?php if($dest): ?> — <?php echo esc_html($dest); ?><?php endif; ?></p>
                                <?php if($hnom): ?><p class="ev-trip-hotel"><?php echo esc_html($hnom); ?></p><?php endif; ?>
                                <p class="ev-trip-pax"><?php echo (int)($dv['nb_total'] ?? 0); ?> voyageur(s) · VS08-<?php echo $ord->get_id(); ?></p>
                                <span class="ev-trip-cta"><?php echo $is_circuit ? 'Revoir le circuit →' : 'Revoir le voyage →'; ?></span>
                            </div>
                        </article>
                    </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Panel : Tous ── -->
            <div class="ev-panel" data-panel="all" style="display:none">
                <?php if (empty($voyage_orders)): ?>
                    <p class="ev-empty-panel">Aucun voyage.</p>
                <?php else: ?>
                    <div class="ev-grid">
                    <?php foreach ($voyage_orders as $item):
                        $ord = $item['order']; $d = $item['booking_data']; $p = $d['params'] ?? []; $dv = $d['devis'] ?? [];
                        $is_circuit = isset($item['type']) && $item['type'] === 'circuit';
                        if ($is_circuit):
                            $cid = (int)($d['circuit_id'] ?? 0); $mm = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : '';
                            if (!$img) $img = get_the_post_thumbnail_url($cid, 'medium');
                            $dest = $mm['destination'] ?? ''; $hnom = ''; $titre = $d['circuit_titre'] ?? 'Circuit';
                            $si = VS08V_Traveler_Space::get_solde_info($ord->get_id());
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0); $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? ''); $titre = $d['voyage_titre'] ?? 'Votre voyage';
                            $si = $item['is_upcoming'] ? VS08V_Traveler_Space::get_solde_info($ord->get_id()) : null;
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '🌍'; ?></span><?php endif; ?>
                                <span class="ev-badge <?php echo $item['is_upcoming'] ? 'ev-badge-upcoming' : 'ev-badge-past'; ?>"><?php echo $item['is_upcoming'] ? 'À venir' : 'Passé'; ?></span>
                                <?php if ($is_circuit): ?><span class="ev-badge ev-badge-circuit">Circuit</span><?php endif; ?>
                                <?php if ($si && $si['solde_due']): ?>
                                <span class="ev-badge ev-badge-solde">Solde à régler<?php if (!empty($si['solde_date'])): ?><span class="ev-badge-solde-date">avant le <?php echo esc_html($si['solde_date']); ?></span><?php endif; ?></span>
                                <?php elseif ($si && !empty($si['soldé_paye'])): ?>
                                <span class="ev-badge ev-badge-solde-paid">Soldé</span>
                                <?php endif; ?>
                            </div>
                            <div class="ev-trip-body">
                                <h3><?php echo esc_html($titre); ?></h3>
                                <p class="ev-trip-meta"><?php echo $p['date_depart'] ? esc_html(date('d/m/Y', strtotime($p['date_depart']))) : ''; ?><?php if($dest): ?> — <?php echo esc_html($dest); ?><?php endif; ?></p>
                                <?php if($hnom): ?><p class="ev-trip-hotel"><?php echo esc_html($hnom); ?></p><?php endif; ?>
                                <p class="ev-trip-pax"><?php echo (int)($dv['nb_total'] ?? 0); ?> voyageur(s) · VS08-<?php echo $ord->get_id(); ?></p>
                                <span class="ev-trip-cta"><?php echo $is_circuit ? 'Voir le circuit →' : 'Voir le voyage →'; ?></span>
                            </div>
                        </article>
                    </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <script>
            (function(){
                var tabs = document.querySelectorAll('.ev-tabs .ev-tab');
                var panels = document.querySelectorAll('.ev-panel');
                tabs.forEach(function(tab){
                    tab.addEventListener('click', function(){
                        var t = this.dataset.tab;
                        tabs.forEach(function(x){x.classList.remove('active');});
                        panels.forEach(function(p){p.style.display = p.dataset.panel===t ? '' : 'none';});
                        this.classList.add('active');
                    });
                });
            })();
            </script>

            <?php else: ?>

            <div class="ev-empty-state">
                <div class="ev-empty-icon" aria-hidden="true">🧳</div>
                <h2>Pas encore de voyage dans votre espace</h2>
                <p>Dès que vous réservez un séjour, un circuit ou une formule avec nous, votre dossier apparaît ici.<br>Vous y retrouverez les détails, les échéances de solde et pourrez nous écrire en un clic.</p>
                <a href="<?php echo esc_url(function_exists('vs08_mega_resultats_url') ? vs08_mega_resultats_url() : home_url('/resultats-recherche')); ?>" class="ev-btn ev-btn-primary">Découvrir nos voyages</a>
            </div>

            <?php endif; ?>

        <?php endif; ?>

    </main>

</div>

<script>
(function () {
    var hdr = document.getElementById('header');
    if (!hdr || !hdr.getBoundingClientRect) return;
    function syncEvHeaderBottom() {
        var bottom = Math.round(hdr.getBoundingClientRect().bottom);
        if (bottom < 32) return;
        document.documentElement.style.setProperty('--ev-header-bottom-px', bottom + 'px');
    }
    syncEvHeaderBottom();
    window.addEventListener('resize', syncEvHeaderBottom);
    window.addEventListener('scroll', syncEvHeaderBottom, { passive: true });
    if (typeof ResizeObserver !== 'undefined') {
        try { new ResizeObserver(syncEvHeaderBottom).observe(hdr); } catch (e) {}
    }
    var n = 0;
    var tick = setInterval(function () {
        syncEvHeaderBottom();
        if (++n >= 25) clearInterval(tick);
    }, 120);
})();
</script>
<?php get_footer(); ?>
