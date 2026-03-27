<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) { wp_redirect(home_url('/')); exit; }

$admin_view = get_query_var('vs08_admin') ?: 'dashboard';
$admin_order_id = (int) get_query_var('vs08_admin_order');
$current_user = wp_get_current_user();

// ── Données globales ──
$all_orders = wc_get_orders(['limit' => -1, 'status' => array_keys(wc_get_order_statuses()), 'return' => 'objects']);
$bookings = [];
foreach ($all_orders as $order) {
    $golf = $order->get_meta('_vs08v_booking_data');
    $circuit = $order->get_meta('_vs08c_booking_data');
    if (!empty($golf) && is_array($golf)) {
        $golf['_order'] = $order;
        $golf['_type'] = 'golf';
        $bookings[] = $golf;
    } elseif (!empty($circuit) && is_array($circuit)) {
        $circuit['_order'] = $order;
        $circuit['_type'] = 'circuit';
        $bookings[] = $circuit;
    }
}
usort($bookings, function($a, $b) {
    return ($b['_order']->get_id()) - ($a['_order']->get_id());
});

// ── Stats ──
$total_bookings = count($bookings);
$total_revenue = 0;
$monthly_revenue = [];
$monthly_count = [];
$type_count = ['golf' => 0, 'circuit' => 0];
$dest_count = [];
$upcoming = [];
$status_count = ['acompte' => 0, 'solde_du' => 0, 'paye' => 0];
$this_month_revenue = 0;
$this_month_count = 0;
$cur_month = date('Y-m');

foreach ($bookings as $b) {
    $order = $b['_order'];
    $total = (float)($b['total'] ?? 0);
    $total_revenue += $total;
    $type_count[$b['_type']]++;

    $date_created = $order->get_date_created();
    $month_key = $date_created ? $date_created->format('Y-m') : $cur_month;
    $monthly_revenue[$month_key] = ($monthly_revenue[$month_key] ?? 0) + $total;
    $monthly_count[$month_key] = ($monthly_count[$month_key] ?? 0) + 1;

    if ($month_key === $cur_month) {
        $this_month_revenue += $total;
        $this_month_count++;
    }

    $dest = '';
    if ($b['_type'] === 'circuit') {
        $cid = (int)($b['circuit_id'] ?? 0);
        $m = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : [];
        $dest = $m['destination'] ?? '';
    } else {
        $vid = (int)($b['voyage_id'] ?? 0);
        $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : [];
        $dest = $m['destination'] ?? '';
    }
    if ($dest) $dest_count[$dest] = ($dest_count[$dest] ?? 0) + 1;

    $params = $b['params'] ?? [];
    $dep = $params['date_depart'] ?? '';
    if ($dep && $dep >= date('Y-m-d')) $upcoming[] = $b;

    $si = class_exists('VS08V_Traveler_Space') ? VS08V_Traveler_Space::get_solde_info($order->get_id()) : null;
    if ($si && $si['solde_due'] && $si['solde'] > 0) $status_count['solde_du']++;
    elseif ($si && !empty($si['soldé_paye'])) $status_count['paye']++;
    else $status_count['acompte']++;
}
arsort($dest_count);
ksort($monthly_revenue);
ksort($monthly_count);

// 12 derniers mois
$chart_labels = [];
$chart_revenue = [];
$chart_count = [];
for ($i = 11; $i >= 0; $i--) {
    $mk = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M Y', strtotime("-$i months"));
    $chart_revenue[] = $monthly_revenue[$mk] ?? 0;
    $chart_count[] = $monthly_count[$mk] ?? 0;
}

$avg_basket = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;
$avg_pax = 0;
$total_pax = 0;
foreach ($bookings as $b) { $total_pax += (int)($b['devis']['nb_total'] ?? 0); }
$avg_pax = $total_bookings > 0 ? round($total_pax / $total_bookings, 1) : 0;

// Messages
$all_messages = get_option('vs08_member_messages', []);
if (!is_array($all_messages)) $all_messages = [];

get_header();
?>

<style>
/* ── Admin space base ── */
.ea-page{display:flex;min-height:100vh;background:#f5f3ef;font-family:'Outfit',sans-serif}
.ea-sidebar{width:260px;background:#0f2424;color:#fff;padding:24px 0;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.ea-sidebar-header{padding:0 24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.ea-sidebar-logo{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#fff;margin:0}
.ea-sidebar-sub{font-size:11px;color:rgba(255,255,255,.4);margin-top:2px;letter-spacing:.5px}
.ea-nav{padding:16px 12px;flex:1}
.ea-nav-item{display:flex;align-items:center;gap:12px;padding:11px 16px;border-radius:12px;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;margin-bottom:4px}
.ea-nav-item:hover{background:rgba(89,183,183,.1);color:#fff}
.ea-nav-item.active{background:rgba(89,183,183,.15);color:#59b7b7;font-weight:700}
.ea-nav-icon{font-size:18px;width:24px;text-align:center}
.ea-nav-badge{margin-left:auto;background:#e8724a;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px}
.ea-sidebar-footer{padding:12px 24px;border-top:1px solid rgba(255,255,255,.08)}
.ea-sidebar-footer a{color:rgba(255,255,255,.4);font-size:12px;text-decoration:none}
.ea-sidebar-footer a:hover{color:#59b7b7}
.ea-main{flex:1;padding:32px 40px;overflow-x:hidden}

/* ── Dashboard ── */
.ea-dash-header{margin-bottom:28px}
.ea-dash-header h1{font-family:'Playfair Display',serif;font-size:28px;color:#0f2424;margin:0}
.ea-dash-header p{color:#6b7280;font-size:14px;margin:4px 0 0}
.ea-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.ea-kpi{background:#fff;border-radius:16px;padding:20px 24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-kpi-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:8px}
.ea-kpi-value{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#0f2424}
.ea-kpi-sub{font-size:12px;color:#6b7280;margin-top:4px}
.ea-kpi.accent .ea-kpi-value{color:#59b7b7}
.ea-kpi.orange .ea-kpi-value{color:#e8724a}
.ea-kpi.green .ea-kpi-value{color:#059669}
.ea-charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:28px}
.ea-chart-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-chart-card h3{font-size:15px;color:#0f2424;margin:0 0 16px;font-weight:700}
.ea-chart-box{position:relative;height:260px}
.ea-mini-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px}
.ea-upcoming-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-upcoming-card h3{font-size:15px;color:#0f2424;margin:0 0 16px;font-weight:700}
.ea-upcoming-item{display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid #f0ece4}
.ea-upcoming-item:last-child{border-bottom:none}
.ea-upcoming-type{font-size:18px;width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ea-upcoming-type.golf{background:#edf8f8}
.ea-upcoming-type.circuit{background:#fef3e8}
.ea-upcoming-info{flex:1;min-width:0}
.ea-upcoming-title{font-size:13px;font-weight:700;color:#0f2424;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ea-upcoming-meta{font-size:11px;color:#9ca3af}
.ea-upcoming-badge{font-size:11px;padding:4px 10px;border-radius:100px;font-weight:700;white-space:nowrap}
.ea-upcoming-badge.solde{background:#fef3e8;color:#e8724a}
.ea-upcoming-badge.ok{background:#edf8f0;color:#059669}

/* ── Dossiers ── */
.ea-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.ea-section-header h1{font-family:'Playfair Display',serif;font-size:24px;color:#0f2424;margin:0}
.ea-search-bar{display:flex;gap:10px;align-items:center}
.ea-search-input{padding:10px 16px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;width:260px;font-family:'Outfit',sans-serif;transition:border-color .2s}
.ea-search-input:focus{outline:none;border-color:#59b7b7}
.ea-filter-select{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:13px;font-family:'Outfit',sans-serif;background:#fff;cursor:pointer}
.ea-table-wrap{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.04);overflow:hidden}
.ea-table{width:100%;border-collapse:collapse;font-size:13px}
.ea-table th{background:#f9f7f4;padding:12px 16px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;font-weight:700;border-bottom:2px solid #e5e7eb}
.ea-table td{padding:12px 16px;border-bottom:1px solid #f0ece4;color:#333;vertical-align:middle}
.ea-table tr:hover{background:#fdfcfa}
.ea-table .ea-td-title{font-weight:700;color:#0f2424;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ea-status{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700}
.ea-status.solde{background:#fef3e8;color:#e8724a}
.ea-status.paye{background:#edf8f0;color:#059669}
.ea-status.acompte{background:#edf0ff;color:#4f46e5}
.ea-table-link{color:#59b7b7;font-weight:700;text-decoration:none;font-size:12px}
.ea-table-link:hover{text-decoration:underline}

/* ── Dossier detail ── */
.ea-detail-header{display:flex;align-items:center;gap:16px;margin-bottom:24px}
.ea-detail-header h1{font-family:'Playfair Display',serif;font-size:22px;color:#0f2424;margin:0;flex:1}
.ea-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.ea-detail-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-detail-card.full{grid-column:span 2}
.ea-detail-card h3{font-size:15px;color:#0f2424;margin:0 0 16px;font-weight:700}
.ea-detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0ece4;font-size:13px}
.ea-detail-row:last-child{border-bottom:none}
.ea-detail-row span:first-child{color:#6b7280}
.ea-detail-row strong{color:#0f2424}
.ea-detail-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
.ea-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:100px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:'Outfit',sans-serif;transition:all .2s;text-decoration:none}
.ea-btn-primary{background:#59b7b7;color:#fff}.ea-btn-primary:hover{background:#3d9a9a}
.ea-btn-outline{background:#fff;color:#0f2424;border:1.5px solid #e5e7eb}.ea-btn-outline:hover{border-color:#59b7b7;color:#59b7b7}
.ea-btn-orange{background:#e8724a;color:#fff}.ea-btn-orange:hover{background:#d4613a}
.ea-btn-green{background:#059669;color:#fff}.ea-btn-green:hover{background:#047857}
.ea-btn-sm{padding:6px 14px;font-size:12px}
.ea-back{color:#59b7b7;text-decoration:none;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px}
.ea-back:hover{text-decoration:underline}
.ea-note-box{margin-top:12px}
.ea-note-box textarea{width:100%;padding:12px;border:1.5px solid #e5e7eb;border-radius:12px;font-family:'Outfit',sans-serif;font-size:13px;resize:vertical;min-height:80px}
.ea-note-box textarea:focus{outline:none;border-color:#59b7b7}
.ea-pax-table{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px}
.ea-pax-table th{background:#f9f7f4;padding:8px 12px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px}
.ea-pax-table td{padding:8px 12px;border-bottom:1px solid #f0ece4}

/* ── Clients ── */
.ea-client-card{display:flex;align-items:center;gap:16px;padding:16px 20px;background:#fff;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.03);margin-bottom:8px;transition:all .2s}
.ea-client-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.ea-client-avatar{width:42px;height:42px;border-radius:50%;background:#edf8f8;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#59b7b7;flex-shrink:0}
.ea-client-info{flex:1}
.ea-client-name{font-weight:700;color:#0f2424;font-size:14px}
.ea-client-meta{font-size:12px;color:#9ca3af}
.ea-client-actions{display:flex;gap:8px}

/* ── Messages ── */
.ea-msg-card{background:#fff;border-radius:14px;padding:18px 20px;margin-bottom:10px;box-shadow:0 2px 8px rgba(0,0,0,.03);border-left:3px solid #59b7b7}
.ea-msg-card.unread{border-left-color:#e8724a}
.ea-msg-head{display:flex;justify-content:space-between;margin-bottom:8px}
.ea-msg-from{font-weight:700;color:#0f2424;font-size:14px}
.ea-msg-date{font-size:11px;color:#9ca3af}
.ea-msg-subj{font-size:13px;color:#59b7b7;font-weight:600;margin-bottom:6px}
.ea-msg-body{font-size:13px;color:#555;line-height:1.6}
.ea-msg-footer{display:flex;gap:8px;margin-top:10px}

/* ── Responsive ── */
@media(max-width:1024px){.ea-kpi-grid{grid-template-columns:repeat(2,1fr)}.ea-charts-grid{grid-template-columns:1fr}.ea-detail-grid{grid-template-columns:1fr}.ea-detail-card.full{grid-column:span 1}}
@media(max-width:768px){.ea-page{flex-direction:column}.ea-sidebar{width:100%;height:auto;position:relative;flex-direction:row;padding:12px}.ea-nav{display:flex;gap:4px;padding:0 8px;overflow-x:auto}.ea-nav-item{white-space:nowrap;padding:8px 12px;font-size:12px}.ea-sidebar-header,.ea-sidebar-footer{display:none}.ea-main{padding:20px 16px}.ea-kpi-grid{grid-template-columns:1fr 1fr}.ea-mini-grid{grid-template-columns:1fr}.ea-search-bar{flex-wrap:wrap}}
</style>

<div class="ea-page">

    <!-- ─── Sidebar ─── -->
    <aside class="ea-sidebar">
        <div class="ea-sidebar-header">
            <h2 class="ea-sidebar-logo">Voyages Sortir 08</h2>
            <div class="ea-sidebar-sub">ESPACE ADMINISTRATION</div>
        </div>
        <nav class="ea-nav">
            <a href="<?php echo esc_url(home_url('/espace-admin/')); ?>" class="ea-nav-item <?php echo $admin_view === 'dashboard' ? 'active' : ''; ?>">
                <span class="ea-nav-icon">📊</span> Dashboard
            </a>
            <a href="<?php echo esc_url(home_url('/espace-admin/dossiers/')); ?>" class="ea-nav-item <?php echo $admin_view === 'dossiers' ? 'active' : ''; ?>">
                <span class="ea-nav-icon">📁</span> Dossiers
                <span class="ea-nav-badge"><?php echo $total_bookings; ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/espace-admin/clients/')); ?>" class="ea-nav-item <?php echo $admin_view === 'clients' ? 'active' : ''; ?>">
                <span class="ea-nav-icon">👥</span> Clients
            </a>
            <a href="<?php echo esc_url(home_url('/espace-admin/messages/')); ?>" class="ea-nav-item <?php echo $admin_view === 'messages' ? 'active' : ''; ?>">
                <span class="ea-nav-icon">💬</span> Messages
                <?php if (!empty($all_messages)): ?><span class="ea-nav-badge"><?php echo count($all_messages); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo esc_url(home_url('/espace-admin/produits/')); ?>" class="ea-nav-item <?php echo $admin_view === 'produits' ? 'active' : ''; ?>">
                <span class="ea-nav-icon">🌍</span> Produits
            </a>
            <a href="<?php echo esc_url(home_url('/espace-voyageur/')); ?>" class="ea-nav-item">
                <span class="ea-nav-icon">👤</span> Espace membre
            </a>
            <a href="<?php echo esc_url(admin_url()); ?>" class="ea-nav-item">
                <span class="ea-nav-icon">⚙️</span> WordPress
            </a>
        </nav>
        <div class="ea-sidebar-footer">
            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Déconnexion</a>
        </div>
    </aside>

    <!-- ─── Main ─── -->
    <main class="ea-main">

<?php if ($admin_view === 'dashboard'): ?>
    <!-- ════════════════════════════════════════════════════════
         DASHBOARD
    ════════════════════════════════════════════════════════ -->
    <div class="ea-dash-header">
        <h1>Dashboard</h1>
        <p>Vue d'ensemble de votre activité · <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="ea-kpi-grid">
        <div class="ea-kpi">
            <div class="ea-kpi-label">Réservations totales</div>
            <div class="ea-kpi-value"><?php echo $total_bookings; ?></div>
            <div class="ea-kpi-sub"><?php echo $type_count['golf']; ?> golf · <?php echo $type_count['circuit']; ?> circuits</div>
        </div>
        <div class="ea-kpi accent">
            <div class="ea-kpi-label">Chiffre d'affaires total</div>
            <div class="ea-kpi-value"><?php echo number_format($total_revenue, 0, ',', ' '); ?> €</div>
            <div class="ea-kpi-sub">Panier moyen : <?php echo number_format($avg_basket, 0, ',', ' '); ?> €</div>
        </div>
        <div class="ea-kpi orange">
            <div class="ea-kpi-label">Ce mois-ci</div>
            <div class="ea-kpi-value"><?php echo number_format($this_month_revenue, 0, ',', ' '); ?> €</div>
            <div class="ea-kpi-sub"><?php echo $this_month_count; ?> réservation(s)</div>
        </div>
        <div class="ea-kpi green">
            <div class="ea-kpi-label">Départs à venir</div>
            <div class="ea-kpi-value"><?php echo count($upcoming); ?></div>
            <div class="ea-kpi-sub"><?php echo $status_count['solde_du']; ?> solde(s) en attente</div>
        </div>
    </div>

    <!-- KPI secondaires -->
    <div class="ea-kpi-grid" style="margin-bottom:28px">
        <div class="ea-kpi">
            <div class="ea-kpi-label">Voyageurs totaux</div>
            <div class="ea-kpi-value"><?php echo $total_pax; ?></div>
            <div class="ea-kpi-sub">Moy. <?php echo $avg_pax; ?> / dossier</div>
        </div>
        <div class="ea-kpi">
            <div class="ea-kpi-label">Dossiers soldés</div>
            <div class="ea-kpi-value"><?php echo $status_count['paye']; ?></div>
            <div class="ea-kpi-sub">sur <?php echo $total_bookings; ?> total</div>
        </div>
        <div class="ea-kpi">
            <div class="ea-kpi-label">Destinations</div>
            <div class="ea-kpi-value"><?php echo count($dest_count); ?></div>
            <div class="ea-kpi-sub">Top : <?php echo $dest_count ? esc_html(array_key_first($dest_count)) : '—'; ?></div>
        </div>
        <div class="ea-kpi">
            <div class="ea-kpi-label">Messages non-lus</div>
            <div class="ea-kpi-value"><?php echo count($all_messages); ?></div>
            <div class="ea-kpi-sub"><a href="<?php echo esc_url(home_url('/espace-admin/messages/')); ?>" style="color:#59b7b7;text-decoration:none">Voir →</a></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="ea-charts-grid">
        <div class="ea-chart-card">
            <h3>📈 Chiffre d'affaires mensuel</h3>
            <div class="ea-chart-box"><canvas id="ea-chart-revenue"></canvas></div>
        </div>
        <div class="ea-chart-card">
            <h3>🍩 Répartition par type</h3>
            <div class="ea-chart-box"><canvas id="ea-chart-type"></canvas></div>
        </div>
    </div>

    <div class="ea-charts-grid">
        <div class="ea-chart-card">
            <h3>📊 Réservations par mois</h3>
            <div class="ea-chart-box"><canvas id="ea-chart-count"></canvas></div>
        </div>
        <div class="ea-chart-card">
            <h3>🌍 Top destinations</h3>
            <div class="ea-chart-box"><canvas id="ea-chart-dest"></canvas></div>
        </div>
    </div>

    <div class="ea-mini-grid">
        <div class="ea-chart-card">
            <h3>📅 Statut des dossiers</h3>
            <div class="ea-chart-box" style="height:200px"><canvas id="ea-chart-status"></canvas></div>
        </div>
        <div class="ea-upcoming-card">
            <h3>✈️ Prochains départs</h3>
            <?php
            usort($upcoming, function($a, $b) { return strcmp($a['params']['date_depart'] ?? '', $b['params']['date_depart'] ?? ''); });
            $upcoming_display = array_slice($upcoming, 0, 8);
            if (empty($upcoming_display)): ?>
                <p style="color:#9ca3af;font-size:13px">Aucun départ à venir.</p>
            <?php else: foreach ($upcoming_display as $up):
                $o = $up['_order'];
                $si = VS08V_Traveler_Space::get_solde_info($o->get_id());
                $titre = $up['_type'] === 'circuit' ? ($up['circuit_titre'] ?? 'Circuit') : ($up['voyage_titre'] ?? 'Séjour golf');
                $dep = $up['params']['date_depart'] ?? '';
                $fact = $up['facturation'] ?? [];
                $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
            ?>
                <a href="<?php echo esc_url(home_url('/espace-admin/dossier/' . $o->get_id() . '/')); ?>" class="ea-upcoming-item" style="text-decoration:none;color:inherit">
                    <div class="ea-upcoming-type <?php echo $up['_type']; ?>"><?php echo $up['_type'] === 'circuit' ? '🗺️' : '⛳'; ?></div>
                    <div class="ea-upcoming-info">
                        <div class="ea-upcoming-title"><?php echo esc_html($titre); ?></div>
                        <div class="ea-upcoming-meta"><?php echo $dep ? date('d/m/Y', strtotime($dep)) : ''; ?> · <?php echo esc_html($client); ?></div>
                    </div>
                    <?php if ($si && $si['solde_due']): ?>
                    <span class="ea-upcoming-badge solde"><?php echo number_format($si['solde'], 0, ',', ' '); ?> €</span>
                    <?php else: ?>
                    <span class="ea-upcoming-badge ok">Soldé ✓</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        var labels = <?php echo json_encode($chart_labels); ?>;
        var revenue = <?php echo json_encode($chart_revenue); ?>;
        var counts = <?php echo json_encode($chart_count); ?>;

        // Revenue chart
        new Chart(document.getElementById('ea-chart-revenue'), {
            type:'bar', data:{labels:labels, datasets:[{label:'CA (€)',data:revenue,backgroundColor:'rgba(89,183,183,.3)',borderColor:'#59b7b7',borderWidth:2,borderRadius:8,hoverBackgroundColor:'rgba(89,183,183,.5)'}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return v.toLocaleString('fr-FR')+' €'}}},x:{grid:{display:false}}}}
        });

        // Type donut
        new Chart(document.getElementById('ea-chart-type'), {
            type:'doughnut', data:{labels:['Séjours Golf','Circuits'], datasets:[{data:[<?php echo $type_count['golf']; ?>,<?php echo $type_count['circuit']; ?>],backgroundColor:['#59b7b7','#e8724a'],borderWidth:0,hoverOffset:8}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:16,font:{family:'Outfit',size:13}}}},cutout:'65%'}
        });

        // Bookings count
        new Chart(document.getElementById('ea-chart-count'), {
            type:'line', data:{labels:labels, datasets:[{label:'Réservations',data:counts,borderColor:'#e8724a',backgroundColor:'rgba(232,114,74,.1)',fill:true,tension:.4,pointRadius:4,pointHoverRadius:7,pointBackgroundColor:'#e8724a'}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}
        });

        // Destinations bar
        var destLabels = <?php echo json_encode(array_keys(array_slice($dest_count, 0, 8))); ?>;
        var destValues = <?php echo json_encode(array_values(array_slice($dest_count, 0, 8))); ?>;
        var destColors = ['#59b7b7','#e8724a','#a78bfa','#f59e0b','#10b981','#ec4899','#6366f1','#14b8a6'];
        new Chart(document.getElementById('ea-chart-dest'), {
            type:'bar', data:{labels:destLabels, datasets:[{data:destValues,backgroundColor:destColors.slice(0,destLabels.length),borderRadius:8}]},
            options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}},y:{grid:{display:false}}}}
        });

        // Status donut
        new Chart(document.getElementById('ea-chart-status'), {
            type:'doughnut', data:{labels:['Acompte versé','Solde dû','Entièrement payé'], datasets:[{data:[<?php echo $status_count['acompte']; ?>,<?php echo $status_count['solde_du']; ?>,<?php echo $status_count['paye']; ?>],backgroundColor:['#6366f1','#e8724a','#059669'],borderWidth:0}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit',size:12}}}},cutout:'60%'}
        });
    })();
    </script>

<?php elseif ($admin_view === 'dossiers'): ?>
    <!-- ════════════════════════════════════════════════════════
         DOSSIERS
    ════════════════════════════════════════════════════════ -->
    <div class="ea-section-header">
        <h1>📁 Dossiers (<?php echo $total_bookings; ?>)</h1>
        <div class="ea-search-bar">
            <input type="text" class="ea-search-input" id="ea-search" placeholder="Rechercher (nom, n° dossier, destination…)">
            <select class="ea-filter-select" id="ea-filter-type">
                <option value="">Tous types</option>
                <option value="golf">⛳ Golf</option>
                <option value="circuit">🗺️ Circuit</option>
            </select>
            <select class="ea-filter-select" id="ea-filter-status">
                <option value="">Tous statuts</option>
                <option value="solde">Solde dû</option>
                <option value="paye">Soldé</option>
                <option value="acompte">Acompte</option>
            </select>
        </div>
    </div>

    <div class="ea-table-wrap">
        <table class="ea-table" id="ea-dossiers-table">
            <thead><tr>
                <th>N°</th><th>Type</th><th>Voyage</th><th>Client</th><th>Départ</th><th>Voyageurs</th><th>Total</th><th>Statut</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($bookings as $b):
                $o = $b['_order'];
                $oid = $o->get_id();
                $titre = $b['_type'] === 'circuit' ? ($b['circuit_titre'] ?? 'Circuit') : ($b['voyage_titre'] ?? 'Séjour golf');
                $fact = $b['facturation'] ?? [];
                $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
                $params = $b['params'] ?? [];
                $dep = $params['date_depart'] ?? '';
                $nb = (int)($b['devis']['nb_total'] ?? 0);
                $total = (float)($b['total'] ?? 0);
                $si = VS08V_Traveler_Space::get_solde_info($oid);
                $status_class = 'acompte'; $status_label = 'Acompte';
                if ($si && $si['solde_due'] && $si['solde'] > 0) { $status_class = 'solde'; $status_label = number_format($si['solde'], 0, ',', ' ') . ' € dû'; }
                elseif ($si && !empty($si['soldé_paye'])) { $status_class = 'paye'; $status_label = 'Soldé ✓'; }
                // Destination
                $dest = '';
                if ($b['_type'] === 'circuit') { $cid = (int)($b['circuit_id'] ?? 0); $mm = class_exists('VS08C_Meta') ? VS08C_Meta::get($cid) : []; $dest = $mm['destination'] ?? ''; }
                else { $vid = (int)($b['voyage_id'] ?? 0); $mm = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($vid) : []; $dest = $mm['destination'] ?? ''; }
            ?>
                <tr data-search="<?php echo esc_attr(strtolower($oid . ' ' . $titre . ' ' . $client . ' ' . $dest)); ?>" data-type="<?php echo esc_attr($b['_type']); ?>" data-status="<?php echo esc_attr($status_class); ?>">
                    <td><strong>VS08-<?php echo $oid; ?></strong></td>
                    <td><?php echo $b['_type'] === 'circuit' ? '🗺️' : '⛳'; ?></td>
                    <td class="ea-td-title"><?php echo esc_html($titre); ?></td>
                    <td><?php echo esc_html($client); ?></td>
                    <td><?php echo $dep ? date('d/m/Y', strtotime($dep)) : '—'; ?></td>
                    <td style="text-align:center"><?php echo $nb; ?></td>
                    <td><strong><?php echo number_format($total, 0, ',', ' '); ?> €</strong></td>
                    <td><span class="ea-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                    <td><a href="<?php echo esc_url(home_url('/espace-admin/dossier/' . $oid . '/')); ?>" class="ea-table-link">Ouvrir →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function(){
        var search = document.getElementById('ea-search');
        var filterType = document.getElementById('ea-filter-type');
        var filterStatus = document.getElementById('ea-filter-status');
        var rows = document.querySelectorAll('#ea-dossiers-table tbody tr');
        function filter(){
            var q = search.value.toLowerCase();
            var ft = filterType.value;
            var fs = filterStatus.value;
            rows.forEach(function(r){
                var match = true;
                if (q && r.dataset.search.indexOf(q) === -1) match = false;
                if (ft && r.dataset.type !== ft) match = false;
                if (fs && r.dataset.status !== fs) match = false;
                r.style.display = match ? '' : 'none';
            });
        }
        search.addEventListener('input', filter);
        filterType.addEventListener('change', filter);
        filterStatus.addEventListener('change', filter);
    })();
    </script>

<?php elseif ($admin_view === 'dossier' && $admin_order_id): ?>
    <!-- ════════════════════════════════════════════════════════
         DOSSIER DETAIL
    ════════════════════════════════════════════════════════ -->
    <?php
    $order = wc_get_order($admin_order_id);
    if (!$order) { echo '<p>Dossier introuvable.</p>'; } else {
        $data_golf = $order->get_meta('_vs08v_booking_data');
        $data_circuit = $order->get_meta('_vs08c_booking_data');
        $d = (!empty($data_circuit) && is_array($data_circuit)) ? $data_circuit : $data_golf;
        $is_circuit = is_array($data_circuit) && !empty($data_circuit);
        if (!is_array($d)) { echo '<p>Aucune donnée de réservation.</p>'; } else {
            $params = $d['params'] ?? [];
            $devis = $d['devis'] ?? [];
            $fact = $d['facturation'] ?? [];
            $voyageurs = $d['voyageurs'] ?? [];
            $titre = $is_circuit ? ($d['circuit_titre'] ?? 'Circuit') : ($d['voyage_titre'] ?? 'Séjour golf');
            $total = (float)($d['total'] ?? 0);
            $si = VS08V_Traveler_Space::get_solde_info($admin_order_id);
            $contract_url = VS08V_Traveler_Space::get_contract_url($admin_order_id);
            $dep = $params['date_depart'] ?? '';
            $client_name = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
            $client_email = $fact['email'] ?? '';
            $client_tel = $fact['tel'] ?? '';
            $notes = $order->get_meta('_vs08_admin_notes') ?: '';

            if ($is_circuit) {
                $pid = (int)($d['circuit_id'] ?? 0);
                $m = class_exists('VS08C_Meta') ? VS08C_Meta::get($pid) : [];
                $dest = $m['destination'] ?? '';
                $duree_n = (int)($m['duree'] ?? 7); $duree_j = (int)($m['duree_jours'] ?? ($duree_n+1));
            } else {
                $pid = (int)($d['voyage_id'] ?? 0);
                $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($pid) : [];
                $dest = $m['destination'] ?? '';
                $duree_n = (int)($m['duree'] ?? 7); $duree_j = $duree_n + 1;
            }
    ?>
    <a href="<?php echo esc_url(home_url('/espace-admin/dossiers/')); ?>" class="ea-back">← Retour aux dossiers</a>

    <div class="ea-detail-header">
        <h1><?php echo $is_circuit ? '🗺️' : '⛳'; ?> VS08-<?php echo $admin_order_id; ?> — <?php echo esc_html($titre); ?></h1>
        <span class="ea-status <?php echo ($si && $si['solde_due']) ? 'solde' : 'paye'; ?>" style="font-size:13px;padding:8px 16px">
            <?php echo ($si && $si['solde_due'] && $si['solde'] > 0) ? number_format($si['solde'], 0, ',', ' ') . ' € à régler' : 'Soldé ✓'; ?>
        </span>
    </div>

    <div class="ea-detail-grid">
        <!-- Récap -->
        <div class="ea-detail-card">
            <h3>📋 Récapitulatif</h3>
            <div class="ea-detail-row"><span>Destination</span><strong><?php echo esc_html($dest); ?></strong></div>
            <div class="ea-detail-row"><span>Date de départ</span><strong><?php echo $dep ? date('d/m/Y', strtotime($dep)) : '—'; ?></strong></div>
            <div class="ea-detail-row"><span>Durée</span><strong><?php echo $duree_j; ?>j / <?php echo $duree_n; ?>n</strong></div>
            <div class="ea-detail-row"><span>Aéroport</span><strong><?php echo esc_html(strtoupper($params['aeroport'] ?? '')); ?></strong></div>
            <div class="ea-detail-row"><span>Voyageurs</span><strong><?php echo (int)($devis['nb_total'] ?? 0); ?> pers.</strong></div>
            <?php if (!empty($params['vol_aller_num'])): ?>
            <div class="ea-detail-row"><span>Vol aller</span><strong><?php echo esc_html($params['vol_aller_num']); ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($params['vol_retour_num'])): ?>
            <div class="ea-detail-row"><span>Vol retour</span><strong><?php echo esc_html($params['vol_retour_num']); ?></strong></div>
            <?php endif; ?>
        </div>

        <!-- Facturation -->
        <div class="ea-detail-card">
            <h3>💳 Facturation & Paiement</h3>
            <div class="ea-detail-row"><span>Client</span><strong><?php echo esc_html($client_name); ?></strong></div>
            <div class="ea-detail-row"><span>Email</span><strong><a href="mailto:<?php echo esc_attr($client_email); ?>" style="color:#59b7b7"><?php echo esc_html($client_email); ?></a></strong></div>
            <div class="ea-detail-row"><span>Tél.</span><strong><?php echo esc_html($client_tel); ?></strong></div>
            <div class="ea-detail-row"><span>Adresse</span><strong><?php echo esc_html(($fact['adresse'] ?? '') . ', ' . ($fact['cp'] ?? '') . ' ' . ($fact['ville'] ?? '')); ?></strong></div>
            <div style="border-top:2px solid #0f2424;margin:12px 0 8px"></div>
            <?php foreach ($devis['lines'] ?? [] as $line): ?>
            <div class="ea-detail-row"><span><?php echo esc_html($line['label']); ?></span><strong><?php echo number_format($line['montant'], 0, ',', ' '); ?> €</strong></div>
            <?php endforeach; ?>
            <div class="ea-detail-row" style="font-size:16px;margin-top:8px"><span>Total</span><strong style="color:#0f2424;font-family:'Playfair Display',serif"><?php echo number_format($total, 0, ',', ' '); ?> €</strong></div>
            <?php if ($si && $si['solde_due'] && $si['solde'] > 0): ?>
            <div class="ea-detail-row" style="color:#e8724a"><span>Solde restant</span><strong><?php echo number_format($si['solde'], 0, ',', ' '); ?> €<?php if ($si['solde_date']): ?> · avant le <?php echo esc_html($si['solde_date']); ?><?php endif; ?></strong></div>
            <?php endif; ?>
        </div>

        <!-- Voyageurs -->
        <div class="ea-detail-card full">
            <h3>👥 Voyageurs (<?php echo count($voyageurs); ?>)</h3>
            <table class="ea-pax-table">
                <thead><tr><th>#</th><th>Nom</th><th>Prénom</th><th>Date de naissance</th><th>Passeport</th><th>Type</th></tr></thead>
                <tbody>
                <?php foreach ($voyageurs as $i => $v):
                    $ddn = $v['ddn'] ?? $v['date_naissance'] ?? '';
                    if ($ddn && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn)) $ddn = date('d/m/Y', strtotime($ddn));
                ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><strong><?php echo esc_html(strtoupper($v['nom'] ?? '')); ?></strong></td>
                    <td><?php echo esc_html($v['prenom'] ?? ''); ?></td>
                    <td><?php echo esc_html($ddn ?: '—'); ?></td>
                    <td><?php echo esc_html($v['passeport'] ?? '—'); ?></td>
                    <td><?php echo esc_html($v['type'] ?? 'voyageur'); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Notes internes -->
        <div class="ea-detail-card">
            <h3>📝 Notes internes</h3>
            <p style="font-size:12px;color:#9ca3af;margin:0 0 8px">Visible uniquement par les administrateurs.</p>
            <div class="ea-note-box">
                <textarea id="ea-notes" placeholder="Ajoutez des notes sur ce dossier…"><?php echo esc_textarea($notes); ?></textarea>
                <button type="button" class="ea-btn ea-btn-primary ea-btn-sm" style="margin-top:8px" onclick="eaSaveNotes()">Sauvegarder</button>
                <span id="ea-notes-fb" style="font-size:12px;color:#059669;margin-left:8px;display:none">✓ Sauvegardé</span>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="ea-detail-card">
            <h3>⚡ Actions rapides</h3>
            <div class="ea-detail-actions">
                <a href="<?php echo esc_url($contract_url); ?>" target="_blank" class="ea-btn ea-btn-outline">📄 Contrat de vente</a>
                <a href="<?php echo esc_url(admin_url('post.php?post=' . $admin_order_id . '&action=edit')); ?>" target="_blank" class="ea-btn ea-btn-outline">⚙️ Modifier dans WP</a>
                <a href="mailto:<?php echo esc_attr($client_email); ?>" class="ea-btn ea-btn-outline">✉️ Envoyer un email</a>
                <?php if ($si && $si['solde_due'] && $si['solde'] > 0): ?>
                <button type="button" class="ea-btn ea-btn-green" onclick="eaMarkPaid()">✅ Marquer comme soldé</button>
                <?php endif; ?>
                <a href="<?php echo esc_url(VS08V_Traveler_Space::voyage_url($admin_order_id)); ?>" target="_blank" class="ea-btn ea-btn-outline">👤 Voir côté client</a>
            </div>
        </div>
    </div>

    <script>
    function eaSaveNotes(){
        var fd = new FormData();
        fd.append('action','vs08_admin_save_notes');
        fd.append('nonce','<?php echo esc_js(wp_create_nonce('vs08_admin_actions')); ?>');
        fd.append('order_id',<?php echo $admin_order_id; ?>);
        fd.append('notes',document.getElementById('ea-notes').value);
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>',{method:'POST',body:fd})
            .then(function(r){return r.json()})
            .then(function(res){
                var fb=document.getElementById('ea-notes-fb');
                fb.style.display='inline';
                setTimeout(function(){fb.style.display='none'},3000);
            });
    }
    function eaMarkPaid(){
        if(!confirm('Marquer le dossier VS08-<?php echo $admin_order_id; ?> comme entièrement soldé ?'))return;
        var fd=new FormData();
        fd.append('action','vs08_admin_mark_paid');
        fd.append('nonce','<?php echo esc_js(wp_create_nonce('vs08_admin_actions')); ?>');
        fd.append('order_id',<?php echo $admin_order_id; ?>);
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>',{method:'POST',body:fd})
            .then(function(r){return r.json()})
            .then(function(res){if(res.success)location.reload();else alert(res.data||'Erreur');});
    }
    </script>

    <?php } } ?>

<?php elseif ($admin_view === 'clients'): ?>
    <!-- ════════════════════════════════════════════════════════
         CLIENTS
    ════════════════════════════════════════════════════════ -->
    <?php
    $clients = [];
    foreach ($bookings as $b) {
        $fact = $b['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (!$email) continue;
        if (!isset($clients[$email])) {
            $clients[$email] = [
                'name' => trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? '')),
                'email' => $email,
                'tel' => $fact['tel'] ?? '',
                'ville' => $fact['ville'] ?? '',
                'bookings' => 0,
                'total' => 0,
                'last_order' => 0,
            ];
        }
        $clients[$email]['bookings']++;
        $clients[$email]['total'] += (float)($b['total'] ?? 0);
        $oid = $b['_order']->get_id();
        if ($oid > $clients[$email]['last_order']) $clients[$email]['last_order'] = $oid;
    }
    usort($clients, function($a, $b) { return $b['last_order'] - $a['last_order']; });
    ?>
    <div class="ea-section-header">
        <h1>👥 Clients (<?php echo count($clients); ?>)</h1>
        <div class="ea-search-bar">
            <input type="text" class="ea-search-input" id="ea-client-search" placeholder="Rechercher un client…">
        </div>
    </div>

    <div id="ea-clients-list">
    <?php foreach ($clients as $c): ?>
    <div class="ea-client-card" data-search="<?php echo esc_attr(strtolower($c['name'] . ' ' . $c['email'] . ' ' . $c['ville'])); ?>">
        <div class="ea-client-avatar"><?php echo mb_strtoupper(mb_substr($c['name'], 0, 1)); ?></div>
        <div class="ea-client-info">
            <div class="ea-client-name"><?php echo esc_html($c['name']); ?></div>
            <div class="ea-client-meta"><?php echo esc_html($c['email']); ?> · <?php echo esc_html($c['tel']); ?><?php if ($c['ville']): ?> · <?php echo esc_html($c['ville']); ?><?php endif; ?></div>
            <div class="ea-client-meta"><?php echo $c['bookings']; ?> résa · <?php echo number_format($c['total'], 0, ',', ' '); ?> € total</div>
        </div>
        <div class="ea-client-actions">
            <a href="mailto:<?php echo esc_attr($c['email']); ?>" class="ea-btn ea-btn-outline ea-btn-sm">✉️ Email</a>
            <a href="<?php echo esc_url(home_url('/espace-admin/dossier/' . $c['last_order'] . '/')); ?>" class="ea-btn ea-btn-primary ea-btn-sm">Dernier dossier →</a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <script>
    document.getElementById('ea-client-search').addEventListener('input',function(){
        var q=this.value.toLowerCase();
        document.querySelectorAll('.ea-client-card').forEach(function(c){c.style.display=c.dataset.search.indexOf(q)!==-1?'':'none';});
    });
    </script>

<?php elseif ($admin_view === 'messages'): ?>
    <!-- ════════════════════════════════════════════════════════
         MESSAGES
    ════════════════════════════════════════════════════════ -->
    <div class="ea-section-header">
        <h1>💬 Messages clients (<?php echo count($all_messages); ?>)</h1>
    </div>

    <?php
    $msgs_display = array_reverse($all_messages);
    if (empty($msgs_display)): ?>
        <p style="color:#9ca3af;text-align:center;padding:40px">Aucun message pour le moment.</p>
    <?php else: foreach ($msgs_display as $mi => $msg): ?>
    <div class="ea-msg-card <?php echo empty($msg['email_sent']) ? 'unread' : ''; ?>">
        <div class="ea-msg-head">
            <div class="ea-msg-from"><?php echo esc_html($msg['client_name'] ?? ''); ?> · <a href="mailto:<?php echo esc_attr($msg['client_email'] ?? ''); ?>" style="color:#59b7b7;font-weight:400;font-size:13px"><?php echo esc_html($msg['client_email'] ?? ''); ?></a></div>
            <span class="ea-msg-date"><?php echo esc_html($msg['date_fmt'] ?? $msg['date'] ?? ''); ?></span>
        </div>
        <?php if (!empty($msg['dossier'])): ?><div class="ea-msg-subj">📁 <?php echo esc_html($msg['dossier']); ?></div><?php endif; ?>
        <div class="ea-msg-subj"><?php echo esc_html($msg['sujet'] ?? ''); ?></div>
        <div class="ea-msg-body"><?php echo nl2br(esc_html($msg['message'] ?? '')); ?></div>
        <div class="ea-msg-footer">
            <a href="mailto:<?php echo esc_attr($msg['client_email'] ?? ''); ?>?subject=Re: <?php echo esc_attr($msg['sujet'] ?? ''); ?>" class="ea-btn ea-btn-primary ea-btn-sm">↩️ Répondre</a>
            <?php if (!empty($msg['order_id'])): ?>
            <a href="<?php echo esc_url(home_url('/espace-admin/dossier/' . intval($msg['order_id']) . '/')); ?>" class="ea-btn ea-btn-outline ea-btn-sm">📁 Voir le dossier</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>

<?php elseif ($admin_view === 'produits'): ?>
    <!-- ════════════════════════════════════════════════════════
         PRODUITS
    ════════════════════════════════════════════════════════ -->
    <?php
    $golf_posts = get_posts(['post_type' => 'vs08_voyage', 'numberposts' => -1, 'post_status' => 'publish']);
    $circuit_posts = get_posts(['post_type' => 'vs08_circuit', 'numberposts' => -1, 'post_status' => 'publish']);
    ?>
    <div class="ea-section-header">
        <h1>🌍 Produits (<?php echo count($golf_posts) + count($circuit_posts); ?>)</h1>
    </div>

    <div class="ea-table-wrap">
        <table class="ea-table">
            <thead><tr><th>Type</th><th>Nom</th><th>Destination</th><th>Durée</th><th>Prix de base</th><th>Réservations</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($golf_posts as $gp):
                $gm = VS08V_MetaBoxes::get($gp->ID);
                $dest = $gm['destination'] ?? '';
                $duree = (int)($gm['duree'] ?? 7);
                $prix = (float)($gm['prix_double'] ?? 0);
                $resa_count = 0;
                foreach ($bookings as $b) { if (($b['voyage_id'] ?? 0) == $gp->ID) $resa_count++; }
            ?>
            <tr>
                <td>⛳ Golf</td>
                <td class="ea-td-title"><?php echo esc_html($gp->post_title); ?></td>
                <td><?php echo esc_html($dest); ?></td>
                <td><?php echo $duree + 1; ?>j / <?php echo $duree; ?>n</td>
                <td><?php echo $prix > 0 ? number_format($prix, 0, ',', ' ') . ' €' : '—'; ?></td>
                <td style="text-align:center"><?php echo $resa_count; ?></td>
                <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $gp->ID . '&action=edit')); ?>" target="_blank" class="ea-table-link">Modifier →</a></td>
            </tr>
            <?php endforeach; ?>
            <?php foreach ($circuit_posts as $cp):
                $cm = VS08C_Meta::get($cp->ID);
                $dest = $cm['destination'] ?? '';
                $duree = (int)($cm['duree'] ?? 7);
                $prix = (float)($cm['prix_double'] ?? 0);
                $resa_count = 0;
                foreach ($bookings as $b) { if (($b['circuit_id'] ?? 0) == $cp->ID) $resa_count++; }
            ?>
            <tr>
                <td>🗺️ Circuit</td>
                <td class="ea-td-title"><?php echo esc_html($cp->post_title); ?></td>
                <td><?php echo esc_html($dest); ?></td>
                <td><?php echo $duree + 1; ?>j / <?php echo $duree; ?>n</td>
                <td><?php echo $prix > 0 ? number_format($prix, 0, ',', ' ') . ' €' : '—'; ?></td>
                <td style="text-align:center"><?php echo $resa_count; ?></td>
                <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $cp->ID . '&action=edit')); ?>" target="_blank" class="ea-table-link">Modifier →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

    </main>
</div>

<?php get_footer(); ?>
