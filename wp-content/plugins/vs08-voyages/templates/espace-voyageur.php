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
            <a href="<?php echo esc_url(VS08V_Traveler_Space::base_url()); ?>" class="ev-nav-item <?php echo $view === 'list' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">✈</span> Mes voyages
            </a>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::profile_url()); ?>" class="ev-nav-item <?php echo $view === 'profil' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">👤</span> Mon profil
            </a>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::favoris_url()); ?>" class="ev-nav-item <?php echo $view === 'favoris' ? 'active' : ''; ?>">
                <span class="ev-nav-icon">❤</span> Mes favoris
            </a>
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="ev-nav-item">
                <span class="ev-nav-icon">⛳</span> Nos séjours
            </a>
            <a href="<?php echo esc_url(home_url('/contact')); ?>" class="ev-nav-item">
                <span class="ev-nav-icon">✉</span> Nous contacter
            </a>
        </nav>

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
                if ($is_circuit): // ── Détail Circuit (contrat de vente, récap) ──
                    $params     = $data['params'] ?? [];
                    $devis      = $data['devis'] ?? [];
                    $fact       = $data['facturation'] ?? [];
                    $voyageurs  = $data['voyageurs'] ?? [];
                    $circuit_id = (int)($data['circuit_id'] ?? 0);
                    $m          = class_exists('VS08C_Meta') ? VS08C_Meta::get($circuit_id) : [];
                    $destination = $m['destination'] ?? '';
                    $total      = (float)($data['total'] ?? 0);
                    $contract_url = VS08V_Traveler_Space::get_contract_url($order_id);
                    $galerie    = $m['galerie'] ?? [];
                    $cover      = !empty($galerie[0]) ? $galerie[0] : '';
                    $duree_n    = (int)($m['duree'] ?? 7);
                    $duree_j    = (int)($m['duree_jours'] ?? ($duree_n + 1));
                    $date_retour = !empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'] . ' +' . $duree_n . ' days')) : '';
                    $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','mixed'=>'Selon programme'];
                    $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4x4','voiture'=>'Voiture','train'=>'Train','mixed'=>'Transport mixte'];
                    $pension_label = $pension_labels[$m['pension'] ?? ''] ?? '';
                    $transport_label = $transport_labels[$m['transport'] ?? ''] ?? '';
            ?>
            <a href="<?php echo esc_url(VS08V_Traveler_Space::base_url()); ?>" class="ev-back">&larr; Retour à mes voyages</a>
            <div class="ev-detail-hero" <?php if ($cover): ?>style="background-image:linear-gradient(180deg,rgba(26,58,58,.45) 0%,rgba(26,58,58,.7) 100%),url(<?php echo esc_url($cover); ?>)"<?php endif; ?>>
                <div class="ev-detail-hero-content">
                    <h1><?php echo esc_html($data['circuit_titre'] ?? 'Circuit'); ?></h1>
                    <p>N° VS08-<?php echo $order_id; ?> · Départ le <?php echo !empty($params['date_depart']) ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></p>
                </div>
                <span class="ev-badge ev-badge-circuit">Circuit</span>
            </div>
            <div class="ev-voyage-blocks">
                <section class="ev-voyage-block">
                    <h2>Récapitulatif</h2>
                    <table class="vs08v-recap-table">
                        <tr><th>Destination</th><td><?php echo esc_html($destination); ?></td></tr>
                        <tr><th>Date de départ</th><td><?php echo !empty($params['date_depart']) ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></td></tr>
                        <tr><th>Durée</th><td><?php echo $duree_j; ?> jours / <?php echo $duree_n; ?> nuits</td></tr>
                        <?php if ($pension_label): ?><tr><th>Formule</th><td><?php echo esc_html($pension_label); ?></td></tr><?php endif; ?>
                        <?php if ($transport_label): ?><tr><th>Transport</th><td><?php echo esc_html($transport_label); ?></td></tr><?php endif; ?>
                        <tr><th>Voyageurs</th><td><?php echo (int)($devis['nb_total'] ?? 0); ?> personne(s)</td></tr>
                        <tr><th>Montant total</th><td><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></td></tr>
                    </table>
                    <?php if (!empty($voyageurs)): ?>
                    <h3>Participants</h3>
                    <ul class="vs08v-voyageurs-list">
                        <?php foreach ($voyageurs as $v): ?>
                        <li><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?>
                            <?php if (!empty($v['date_naissance'])): ?> · Né(e) le <?php echo esc_html(date('d/m/Y', strtotime($v['date_naissance']))); ?><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>
                <section class="ev-voyage-block">
                    <h2>Contrat de vente</h2>
                    <p>Téléchargez ou consultez votre contrat de vente.</p>
                    <a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener" class="ev-btn ev-btn-primary">Voir le contrat de vente</a>
                </section>
            </div>
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

            <a href="<?php echo esc_url(VS08V_Traveler_Space::base_url()); ?>" class="ev-back">&larr; Retour à mes voyages</a>

            <!-- Hero -->
            <div class="ev-detail-hero" <?php if ($cover): ?>style="background-image:linear-gradient(180deg,rgba(26,58,58,.45) 0%,rgba(26,58,58,.7) 100%),url(<?php echo esc_url($cover); ?>)"<?php endif; ?>>
                <div class="ev-detail-hero-content">
                    <h1><?php echo esc_html((string)($data['voyage_titre'] ?? 'Séjour golf')); ?></h1>
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
                <form id="ev-voyageur-form" class="ev-voyageur-form">
                    <input type="hidden" name="index" id="ev-voyageur-index" value="-1">
                    <div class="ev-voyageur-fields">
                        <p class="ev-field">
                            <label for="ev-voy-prenom">Prénom *</label>
                            <input type="text" id="ev-voy-prenom" name="prenom" required placeholder="Jean">
                        </p>
                        <p class="ev-field">
                            <label for="ev-voy-nom">Nom *</label>
                            <input type="text" id="ev-voy-nom" name="nom" required placeholder="Dupont">
                        </p>
                        <p class="ev-field ev-field-ddn">
                            <label>Date de naissance</label>
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
                            <?php if (!$img): ?><span class="ev-trip-placeholder">⛳</span><?php endif; ?>
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

        <?php else: ?>

            <?php
            $voyage_orders = VS08V_Traveler_Space::get_voyage_orders();
            $upcoming = array_filter($voyage_orders, function ($v) { return $v['is_upcoming']; });
            $past     = array_filter($voyage_orders, function ($v) { return !$v['is_upcoming']; });
            $has_any  = !empty($voyage_orders);
            ?>

            <div class="ev-list-header">
                <h1>Mes voyages</h1>
                <p>Bienvenue <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>. Retrouvez ici vos réservations, réglez un solde ou posez-nous une question.</p>
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
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : '';
                            $dest = $mm['destination'] ?? ''; $hnom = '';
                            $titre = $d['circuit_titre'] ?? 'Circuit';
                            $si = null;
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0);
                            $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : '';
                            $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? '');
                            $titre = $d['voyage_titre'] ?? 'Séjour golf';
                            $si = VS08V_Traveler_Space::get_solde_info($ord->get_id());
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '⛳'; ?></span><?php endif; ?>
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
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = ''; $titre = $d['circuit_titre'] ?? 'Circuit';
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0); $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? ''); $titre = $d['voyage_titre'] ?? 'Séjour golf';
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '⛳'; ?></span><?php endif; ?>
                                <span class="ev-badge ev-badge-past">Passé</span>
                                <?php if ($is_circuit): ?><span class="ev-badge ev-badge-circuit">Circuit</span><?php endif; ?>
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
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = ''; $titre = $d['circuit_titre'] ?? 'Circuit';
                            $si = null;
                        else:
                            $vid = (int)($d['voyage_id'] ?? 0); $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
                            $gal = $mm['galerie'] ?? []; $img = !empty($gal[0]) ? $gal[0] : ''; $dest = $mm['destination'] ?? ''; $hnom = $mm['hotel_nom'] ?? ($mm['hotel']['nom'] ?? ''); $titre = $d['voyage_titre'] ?? 'Séjour golf';
                            $si = $item['is_upcoming'] ? VS08V_Traveler_Space::get_solde_info($ord->get_id()) : null;
                        endif;
                        $lnk = VS08V_Traveler_Space::voyage_url($ord->get_id());
                    ?>
                    <a href="<?php echo esc_url($lnk); ?>" class="ev-card-link">
                        <article class="ev-trip-card">
                            <div class="ev-trip-img" <?php if($img): ?>style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
                                <?php if(!$img): ?><span class="ev-trip-placeholder"><?php echo $is_circuit ? '🗺️' : '⛳'; ?></span><?php endif; ?>
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
                <div class="ev-empty-icon">⛳</div>
                <h2>Vous n'avez pas encore de réservation</h2>
                <p>Dès que vous aurez réservé un séjour golf avec nous, vos voyages apparaîtront ici.<br>Vous pourrez consulter vos détails, régler un solde ou nous poser une question.</p>
                <a href="<?php echo esc_url(home_url('/golf')); ?>" class="ev-btn ev-btn-primary">Découvrir nos séjours golf</a>
            </div>

            <?php endif; ?>

        <?php endif; ?>

    </main>

</div>

<?php get_footer(); ?>
