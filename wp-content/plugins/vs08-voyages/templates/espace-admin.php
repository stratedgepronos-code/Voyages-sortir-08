<?php
if (!defined('ABSPATH')) exit;
$admin_view = get_query_var('vs08_admin');
$admin_order_id = (int) get_query_var('vs08_admin_order');
$current_user = wp_get_current_user();

// ── POST handler: notes admin ──
if (isset($_POST['vs08_admin_action']) && $_POST['vs08_admin_action'] === 'save_notes' && !empty($_POST['order_id'])) {
    $oid = intval($_POST['order_id']);
    if (wp_verify_nonce($_POST['_wpnonce'] ?? '', 'vs08_admin_notes_' . $oid) && current_user_can('manage_options')) {
        update_post_meta($oid, '_vs08_admin_notes', sanitize_textarea_field($_POST['admin_notes']));
    }
}

// ── Charger toutes les commandes VS08 ──
function vs08_admin_get_all_orders() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $orders = wc_get_orders(['limit' => -1, 'status' => array_keys(wc_get_order_statuses()), 'orderby' => 'date', 'order' => 'DESC']);
    $result = []; $today = date('Y-m-d');
    foreach ($orders as $order) {
        $data = VS08V_Traveler_Space::get_booking_data_from_order($order, true);
        if (!$data) continue;
        $params = $data['params'] ?? []; $depart = $params['date_depart'] ?? '';
        $is_circuit = isset($data['type']) && $data['type'] === 'circuit';
        $fact = $data['facturation'] ?? [];
        $result[] = [
            'order' => $order, 'data' => $data, 'date_depart' => $depart,
            'is_upcoming' => $depart && $depart >= $today,
            'type' => $is_circuit ? 'circuit' : 'golf',
            'titre' => $is_circuit ? ($data['circuit_titre'] ?? 'Circuit') : ($data['voyage_titre'] ?? 'Séjour golf'),
            'total' => (float)($data['total'] ?? 0),
            'client_name' => trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? '')),
            'client_email' => $fact['email'] ?? '',
        ];
    }
    $cache = $result;
    return $result;
}

get_header();
$all_orders = vs08_admin_get_all_orders();
$messages_admin = get_option('vs08_member_messages', []);
if (!is_array($messages_admin)) $messages_admin = [];
?>

<style>
.ea-page{display:flex;min-height:100vh;background:#f5f3ef;font-family:'Outfit',sans-serif}
.ea-sidebar{width:260px;background:linear-gradient(180deg,#0f2424 0%,#1a3e3e 100%);color:#fff;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;padding-top:72px}
.ea-sidebar-header{padding:28px 24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.ea-sidebar-logo{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#fff;letter-spacing:1px}
.ea-sidebar-sub{font-size:10px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:2px;margin-top:4px}
.ea-nav{flex:1;padding:16px 12px}
.ea-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;color:rgba(255,255,255,.55);text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;margin-bottom:4px}
.ea-nav-item:hover{background:rgba(89,183,183,.1);color:#fff}
.ea-nav-item.active{background:rgba(89,183,183,.15);color:#59b7b7;font-weight:700}
.ea-nav-icon{font-size:18px;width:24px;text-align:center}
.ea-nav-badge{margin-left:auto;background:#e8724a;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px}
.ea-sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,.08)}
.ea-nav-logout{color:rgba(255,255,255,.3)!important}
.ea-nav-logout:hover{color:#e8724a!important}
.ea-main{flex:1;padding:32px 40px;max-width:1200px;min-width:0}
.ea-page-title{font-family:'Playfair Display',serif;font-size:28px;color:#0f2424;margin:0 0 6px}
.ea-page-sub{font-size:14px;color:#6b7280;margin:0 0 28px}
.ea-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:32px}
.ea-kpi{background:#fff;border-radius:18px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,.04);position:relative;overflow:hidden}
.ea-kpi::after{content:'';position:absolute;top:0;right:0;width:80px;height:80px;border-radius:0 0 0 80px;opacity:.06}
.ea-kpi-1::after{background:#59b7b7}.ea-kpi-2::after{background:#c8a45e}.ea-kpi-3::after{background:#e8724a}.ea-kpi-4::after{background:#dc2626}
.ea-kpi-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;font-weight:600;margin-bottom:8px}
.ea-kpi-value{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;color:#0f2424}
.ea-kpi-detail{font-size:12px;color:#6b7280;margin-top:6px}
.ea-kpi-detail span{color:#059669;font-weight:700}
.ea-charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:32px}
.ea-chart-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-chart-title{font-size:15px;font-weight:700;color:#0f2424;margin:0 0 16px}
.ea-chart-wrap{position:relative;height:280px}
.ea-table-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04);margin-bottom:24px}
.ea-table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px}
.ea-table-title{font-size:17px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif}
.ea-table-search{padding:10px 16px;border:1.5px solid #e5e7eb;border-radius:100px;font-size:13px;width:280px;font-family:'Outfit',sans-serif;transition:border-color .2s}
.ea-table-search:focus{outline:none;border-color:#59b7b7}
.ea-table{width:100%;border-collapse:collapse}
.ea-table th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;padding:10px 12px;border-bottom:2px solid #f0ece4;font-weight:700}
.ea-table td{padding:12px;border-bottom:1px solid #f5f3ef;font-size:13px;color:#374151;vertical-align:middle}
.ea-table tr:hover td{background:#faf9f6}
.ea-table-link{color:#59b7b7;text-decoration:none;font-weight:600}
.ea-table-link:hover{text-decoration:underline}
.ea-badge{display:inline-block;padding:3px 10px;border-radius:100px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.ea-badge-golf{background:#edf8f8;color:#3d9a9a}.ea-badge-circuit{background:#fef3e8;color:#b85c1a}
.ea-badge-upcoming{background:#ecfdf5;color:#059669}.ea-badge-past{background:#f3f4f6;color:#6b7280}
.ea-badge-solde{background:#fef2f2;color:#dc2626}.ea-badge-paid{background:#ecfdf5;color:#059669}
.ea-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.ea-detail-card{background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.ea-detail-card h3{font-size:14px;font-weight:700;color:#0f2424;margin:0 0 14px;display:flex;align-items:center;gap:8px}
.ea-detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f3ef;font-size:13px}
.ea-detail-row:last-child{border-bottom:none}
.ea-detail-row span:first-child{color:#6b7280}
.ea-detail-row strong{color:#0f2424}
.ea-detail-full{grid-column:span 2}
.ea-back{display:inline-flex;align-items:center;gap:6px;color:#59b7b7;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:16px}
.ea-back:hover{text-decoration:underline}
.ea-btn{display:inline-block;padding:10px 22px;border-radius:100px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;text-decoration:none;cursor:pointer;border:none;transition:all .2s}
.ea-btn-primary{background:#59b7b7;color:#fff}.ea-btn-primary:hover{background:#3d9a9a;transform:translateY(-1px)}
.ea-btn-outline{background:#fff;color:#59b7b7;border:1.5px solid #59b7b7}.ea-btn-outline:hover{background:#edf8f8}
.ea-tabs{display:flex;gap:4px;margin-bottom:20px;background:#f0ece4;border-radius:100px;padding:4px}
.ea-tab{padding:8px 18px;border-radius:100px;border:none;background:none;font-size:13px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;color:#6b7280;transition:all .2s}
.ea-tab.active{background:#fff;color:#0f2424;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.ea-msg-item{display:flex;gap:16px;padding:16px;border-bottom:1px solid #f5f3ef;transition:background .15s}
.ea-msg-item:hover{background:#faf9f6}
.ea-msg-avatar{width:40px;height:40px;border-radius:50%;background:#edf8f8;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;color:#59b7b7;font-weight:700}
.ea-msg-content{flex:1;min-width:0}
.ea-msg-head{display:flex;justify-content:space-between;margin-bottom:4px}
.ea-msg-name{font-weight:700;font-size:14px;color:#0f2424}
.ea-msg-date{font-size:11px;color:#9ca3af}
.ea-msg-subj{font-size:13px;font-weight:600;color:#374151;margin-bottom:2px}
.ea-msg-body{font-size:12px;color:#6b7280;line-height:1.5}
.ea-msg-dossier{font-size:11px;color:#59b7b7;font-weight:600}
.ea-notes-textarea{width:100%;padding:12px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:13px;font-family:'Outfit',sans-serif;resize:vertical;min-height:80px;box-sizing:border-box}
.ea-notes-textarea:focus{outline:none;border-color:#59b7b7}
.ea-departures{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:8px}
.ea-departure-card{background:#fff;border-radius:14px;padding:16px 18px;box-shadow:0 2px 8px rgba(0,0,0,.04);border-left:4px solid #59b7b7;cursor:pointer;text-decoration:none;color:inherit;transition:all .2s}
.ea-departure-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
.ea-departure-card.circuit{border-left-color:#e8724a}
.ea-departure-date{font-size:20px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif}
.ea-departure-title{font-size:12px;color:#6b7280;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ea-departure-client{font-size:11px;color:#9ca3af;margin-top:2px}
@media(max-width:960px){
.ea-page{flex-direction:column}.ea-sidebar{width:100%;height:auto;position:relative}
.ea-kpi-grid{grid-template-columns:repeat(2,1fr)}.ea-charts-grid{grid-template-columns:1fr}
.ea-detail-grid{grid-template-columns:1fr}.ea-detail-full{grid-column:span 1}
.ea-departures{grid-template-columns:1fr}.ea-main{padding:20px 16px}
.ea-nav{display:flex;flex-wrap:wrap;gap:4px;padding:8px}.ea-nav-item{padding:8px 12px;font-size:12px}
.ea-sidebar-header{padding:16px}.ea-sidebar-footer{display:none}
}
</style>

<div class="ea-page">
<aside class="ea-sidebar">
    <div class="ea-sidebar-header">
        <div class="ea-sidebar-logo">Voyages Sortir 08</div>
        <div class="ea-sidebar-sub">Espace administration</div>
    </div>
    <?php
    $solde_pending = 0;
    foreach ($all_orders as $uo) {
        if (!$uo['is_upcoming']) continue;
        $si = VS08V_Traveler_Space::get_solde_info($uo['order']->get_id());
        if ($si && $si['solde_due'] && $si['solde'] > 0) $solde_pending++;
    }
    ?>
    <nav class="ea-nav">
        <a href="<?php echo home_url('/espace-admin/'); ?>" class="ea-nav-item <?php echo $admin_view === 'dashboard' ? 'active' : ''; ?>"><span class="ea-nav-icon">📊</span> Dashboard</a>
        <a href="<?php echo home_url('/espace-admin/dossiers/'); ?>" class="ea-nav-item <?php echo in_array($admin_view, ['dossiers','dossier']) ? 'active' : ''; ?>"><span class="ea-nav-icon">📁</span> Dossiers<?php if ($solde_pending): ?><span class="ea-nav-badge"><?php echo $solde_pending; ?></span><?php endif; ?></a>
        <a href="<?php echo home_url('/espace-admin/clients/'); ?>" class="ea-nav-item <?php echo $admin_view === 'clients' ? 'active' : ''; ?>"><span class="ea-nav-icon">👥</span> Clients</a>
        <a href="<?php echo home_url('/espace-admin/messages/'); ?>" class="ea-nav-item <?php echo $admin_view === 'messages' ? 'active' : ''; ?>"><span class="ea-nav-icon">💬</span> Messages<?php if (count($messages_admin)): ?><span class="ea-nav-badge"><?php echo count($messages_admin); ?></span><?php endif; ?></a>
        <a href="<?php echo home_url('/espace-admin/produits/'); ?>" class="ea-nav-item <?php echo $admin_view === 'produits' ? 'active' : ''; ?>"><span class="ea-nav-icon">🗺️</span> Produits</a>
        <a href="<?php echo home_url('/espace-voyageur/'); ?>" class="ea-nav-item" target="_blank"><span class="ea-nav-icon">👁️</span> Vue client</a>
        <a href="<?php echo admin_url(); ?>" class="ea-nav-item"><span class="ea-nav-icon">⚙️</span> WordPress</a>
    </nav>
    <div class="ea-sidebar-footer">
        <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="ea-nav-item ea-nav-logout"><span class="ea-nav-icon">⏻</span> Déconnexion</a>
    </div>
</aside>
<main class="ea-main">

<?php if ($admin_view === 'dashboard'): ?>
<?php
$today = date('Y-m-d'); $this_month = date('Y-m'); $this_year = date('Y');
$total_ca = 0; $ca_month = 0; $ca_year = 0; $count_golf = 0; $count_circuit = 0; $count_month = 0;
$upcoming_count = 0; $solde_total_amt = 0; $solde_count = 0;
$monthly_data = []; $type_data = ['golf' => 0, 'circuit' => 0]; $dest_data = [];
foreach ($all_orders as $o) {
    $oid = $o['order']->get_id(); $total_ca += $o['total'];
    $od = $o['order']->get_date_created(); $order_date = $od ? $od->format('Y-m') : ''; $order_year = $od ? $od->format('Y') : '';
    if ($order_date === $this_month) { $ca_month += $o['total']; $count_month++; }
    if ($order_year === $this_year) { $ca_year += $o['total']; }
    if ($o['type'] === 'golf') $count_golf++; else $count_circuit++;
    if ($o['is_upcoming']) $upcoming_count++;
    $type_data[$o['type']] += $o['total'];
    if ($order_date) { if (!isset($monthly_data[$order_date])) $monthly_data[$order_date] = ['ca'=>0,'count'=>0]; $monthly_data[$order_date]['ca'] += $o['total']; $monthly_data[$order_date]['count']++; }
    $dest = '';
    if ($o['type'] === 'circuit') { $cid=(int)($o['data']['circuit_id']??0); if ($cid && class_exists('VS08C_Meta')) { $cm=VS08C_Meta::get($cid); $dest=$cm['destination']??''; } }
    else { $vid=(int)($o['data']['voyage_id']??0); if ($vid && class_exists('VS08V_MetaBoxes')) { $vm=VS08V_MetaBoxes::get($vid); $dest=$vm['destination']??''; } }
    if ($dest) $dest_data[$dest] = ($dest_data[$dest] ?? 0) + 1;
    $si = VS08V_Traveler_Space::get_solde_info($oid);
    if ($si && $si['solde_due'] && $si['solde'] > 0) { $solde_total_amt += $si['solde']; $solde_count++; }
}
$chart_labels=[]; $chart_ca=[]; $chart_count=[];
for ($i=11;$i>=0;$i--) { $mx=date('Y-m',strtotime("-$i months")); $chart_labels[]=date('M Y',strtotime($mx.'-01')); $chart_ca[]=round($monthly_data[$mx]['ca']??0); $chart_count[]=$monthly_data[$mx]['count']??0; }
arsort($dest_data); $top_dests = array_slice($dest_data, 0, 6, true);
?>
<h1 class="ea-page-title">Dashboard</h1>
<p class="ea-page-sub">Vue d'ensemble — <?php echo date_i18n('l j F Y'); ?></p>
<div class="ea-kpi-grid">
    <div class="ea-kpi ea-kpi-1"><div class="ea-kpi-label">Réservations</div><div class="ea-kpi-value"><?php echo count($all_orders); ?></div><div class="ea-kpi-detail"><span>+<?php echo $count_month; ?></span> ce mois · ⛳ <?php echo $count_golf; ?> · 🗺️ <?php echo $count_circuit; ?></div></div>
    <div class="ea-kpi ea-kpi-2"><div class="ea-kpi-label">Chiffre d'affaires</div><div class="ea-kpi-value"><?php echo number_format($total_ca,0,',',' '); ?> €</div><div class="ea-kpi-detail"><span><?php echo number_format($ca_month,0,',',' '); ?> €</span> ce mois · <?php echo number_format($ca_year,0,',',' '); ?> € <?php echo $this_year; ?></div></div>
    <div class="ea-kpi ea-kpi-3"><div class="ea-kpi-label">Départs à venir</div><div class="ea-kpi-value"><?php echo $upcoming_count; ?></div><div class="ea-kpi-detail"><?php echo count($all_orders) - $upcoming_count; ?> voyages passés</div></div>
    <div class="ea-kpi ea-kpi-4"><div class="ea-kpi-label">Soldes en attente</div><div class="ea-kpi-value"><?php echo number_format($solde_total_amt,0,',',' '); ?> €</div><div class="ea-kpi-detail"><?php echo $solde_count; ?> dossier(s)</div></div>
</div>
<?php
$next_departures = array_filter($all_orders, function($o){ return $o['is_upcoming']; });
usort($next_departures, function($a,$b){ return strcmp($a['date_depart'], $b['date_depart']); });
$next_departures = array_slice($next_departures, 0, 6);
if (!empty($next_departures)): ?>
<div class="ea-table-card"><div class="ea-table-header"><span class="ea-table-title">🛫 Prochains départs</span></div>
<div class="ea-departures">
<?php foreach ($next_departures as $nd): ?>
<a href="<?php echo home_url('/espace-admin/dossier/'.$nd['order']->get_id().'/'); ?>" class="ea-departure-card <?php echo $nd['type']; ?>">
    <div class="ea-departure-date"><?php echo date('d/m',strtotime($nd['date_depart'])); ?> <span style="font-size:12px;font-weight:400;color:#9ca3af"><?php echo date('Y',strtotime($nd['date_depart'])); ?></span></div>
    <div class="ea-departure-title"><?php echo esc_html($nd['titre']); ?></div>
    <div class="ea-departure-client"><?php echo esc_html($nd['client_name']); ?> · VS08-<?php echo $nd['order']->get_id(); ?></div>
</a>
<?php endforeach; ?>
</div></div>
<?php endif; ?>
<div class="ea-charts-grid">
    <div class="ea-chart-card"><div class="ea-chart-title">📈 CA — 12 derniers mois</div><div class="ea-chart-wrap"><canvas id="ea-chart-ca"></canvas></div></div>
    <div class="ea-chart-card"><div class="ea-chart-title">🥧 Répartition Golf / Circuits</div><div class="ea-chart-wrap"><canvas id="ea-chart-type"></canvas></div></div>
</div>
<div class="ea-charts-grid">
    <div class="ea-chart-card"><div class="ea-chart-title">📊 Réservations / mois</div><div class="ea-chart-wrap"><canvas id="ea-chart-count"></canvas></div></div>
    <div class="ea-chart-card"><div class="ea-chart-title">🌍 Top destinations</div><div class="ea-chart-wrap"><canvas id="ea-chart-dest"></canvas></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
Chart.defaults.font.family="'Outfit',sans-serif";Chart.defaults.font.size=12;Chart.defaults.color='#6b7280';
new Chart(document.getElementById('ea-chart-ca'),{type:'bar',data:{labels:<?php echo json_encode($chart_labels); ?>,datasets:[{label:'CA (€)',data:<?php echo json_encode($chart_ca); ?>,backgroundColor:'rgba(89,183,183,.7)',borderRadius:8,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return v.toLocaleString('fr-FR')+' €'}}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('ea-chart-type'),{type:'doughnut',data:{labels:['Séjours Golf','Circuits'],datasets:[{data:[<?php echo round($type_data['golf']); ?>,<?php echo round($type_data['circuit']); ?>],backgroundColor:['#59b7b7','#e8724a'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('ea-chart-count'),{type:'line',data:{labels:<?php echo json_encode($chart_labels); ?>,datasets:[{label:'Résa',data:<?php echo json_encode($chart_count); ?>,borderColor:'#59b7b7',backgroundColor:'rgba(89,183,183,.1)',fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#59b7b7'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('ea-chart-dest'),{type:'bar',data:{labels:<?php echo json_encode(array_keys($top_dests)); ?>,datasets:[{data:<?php echo json_encode(array_values($top_dests)); ?>,backgroundColor:['#59b7b7','#e8724a','#c8a45e','#8b5cf6','#ec4899','#14b8a6'],borderRadius:6,borderSkipped:false}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}},y:{grid:{display:false}}}}});
</script>

<!-- Derniers messages + Activité récente -->
<div class="ea-charts-grid">
    <div class="ea-chart-card" style="height:auto">
        <div class="ea-chart-title">💬 Derniers messages clients</div>
        <?php
        $recent_msgs = array_reverse(array_slice($messages_admin, -5));
        if (empty($recent_msgs)): ?>
        <p style="color:#9ca3af;font-size:13px;text-align:center;padding:20px">Aucun message.</p>
        <?php else: foreach ($recent_msgs as $rm): ?>
        <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f5f3ef;font-size:12px">
            <div style="width:32px;height:32px;border-radius:50%;background:#edf8f8;display:flex;align-items:center;justify-content:center;color:#59b7b7;font-weight:700;font-size:13px;flex-shrink:0"><?php echo mb_strtoupper(mb_substr($rm['client_name'] ?? '?', 0, 1)); ?></div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;justify-content:space-between"><strong style="color:#0f2424"><?php echo esc_html($rm['client_name'] ?? ''); ?></strong><span style="color:#9ca3af;font-size:10px"><?php echo esc_html($rm['date_fmt'] ?? ''); ?></span></div>
                <div style="color:#374151;font-weight:600"><?php echo esc_html($rm['sujet'] ?? ''); ?></div>
                <div style="color:#6b7280"><?php echo esc_html(mb_substr($rm['message'] ?? '', 0, 60)); ?><?php echo mb_strlen($rm['message'] ?? '') > 60 ? '…' : ''; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="text-align:center;margin-top:12px"><a href="<?php echo home_url('/espace-admin/messages/'); ?>" class="ea-btn ea-btn-outline" style="padding:6px 16px;font-size:11px">Voir tous les messages →</a></div>
        <?php endif; ?>
    </div>
    <div class="ea-chart-card" style="height:auto">
        <div class="ea-chart-title">📋 Activité récente</div>
        <?php
        global $wpdb;
        $nl_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs08_newsletter WHERE active = 1");
        $nl_month = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}vs08_newsletter WHERE active = 1 AND created_at >= %s", date('Y-m-01 00:00:00')));
        ?>
        <div style="display:flex;gap:16px;margin-bottom:16px">
            <div style="flex:1;background:#edf8f8;border-radius:12px;padding:14px;text-align:center">
                <div style="font-size:24px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif"><?php echo $nl_count; ?></div>
                <div style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Abonnés newsletter</div>
            </div>
            <div style="flex:1;background:#fef3e8;border-radius:12px;padding:14px;text-align:center">
                <div style="font-size:24px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif"><?php echo $nl_month; ?></div>
                <div style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Ce mois</div>
            </div>
        </div>
        <?php
        // 5 dernières réservations
        $recent_orders = array_slice($all_orders, 0, 5);
        foreach ($recent_orders as $ro):
            $ro_date = $ro['order']->get_date_created() ? $ro['order']->get_date_created()->format('d/m/Y H:i') : '';
        ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f3ef;font-size:12px">
            <div>
                <span class="ea-badge ea-badge-<?php echo $ro['type']; ?>" style="font-size:9px"><?php echo $ro['type'] === 'golf' ? '⛳' : '🗺️'; ?></span>
                <a href="<?php echo home_url('/espace-admin/dossier/' . $ro['order']->get_id() . '/'); ?>" style="color:#59b7b7;font-weight:600;text-decoration:none">VS08-<?php echo $ro['order']->get_id(); ?></a>
                <span style="color:#6b7280"> — <?php echo esc_html($ro['client_name']); ?></span>
            </div>
            <span style="color:#9ca3af"><?php echo $ro_date; ?></span>
        </div>
        <?php endforeach; ?>
        <div style="text-align:center;margin-top:12px"><a href="<?php echo home_url('/espace-admin/dossiers/'); ?>" class="ea-btn ea-btn-outline" style="padding:6px 16px;font-size:11px">Voir tous les dossiers →</a></div>
    </div>
</div>

<?php elseif ($admin_view === 'dossiers'): ?>
<h1 class="ea-page-title">Dossiers</h1>
<p class="ea-page-sub"><?php echo count($all_orders); ?> réservation(s) <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=vs08_admin_export_csv'), 'vs08_export_csv')); ?>" class="ea-btn ea-btn-outline" style="margin-left:12px;padding:6px 16px;font-size:12px">📥 Exporter CSV</a></p>
<script>var EA_NONCE='<?php echo esc_js(wp_create_nonce('vs08_admin_actions')); ?>',EA_AJAX='<?php echo esc_url(admin_url('admin-ajax.php')); ?>';</script>
<div class="ea-tabs">
    <button class="ea-tab active" onclick="eaFilter('all',this)">Tous</button>
    <button class="ea-tab" onclick="eaFilter('upcoming',this)">À venir</button>
    <button class="ea-tab" onclick="eaFilter('past',this)">Passés</button>
    <button class="ea-tab" onclick="eaFilter('solde',this)">Solde dû</button>
</div>
<div class="ea-table-card">
    <div class="ea-table-header"><span class="ea-table-title">📁 Tous les dossiers</span><input type="text" class="ea-table-search" id="ea-search" placeholder="Rechercher…" oninput="eaSearch()"></div>
    <table class="ea-table" id="ea-tbl"><thead><tr><th>N°</th><th>Client</th><th>Voyage</th><th>Type</th><th>Départ</th><th>Total</th><th>Solde</th><th>Statut</th><th></th></tr></thead><tbody>
    <?php foreach ($all_orders as $o):
        $oid=$o['order']->get_id(); $si=VS08V_Traveler_Space::get_solde_info($oid);
        $sd=$si&&$si['solde_due']&&$si['solde']>0; $ip=$si&&!empty($si['soldé_paye']);
    ?>
    <tr data-up="<?php echo $o['is_upcoming']?'1':'0'; ?>" data-sd="<?php echo $sd?'1':'0'; ?>" data-q="<?php echo esc_attr(strtolower($oid.' '.$o['client_name'].' '.$o['titre'])); ?>">
        <td><a href="<?php echo home_url('/espace-admin/dossier/'.$oid.'/'); ?>" class="ea-table-link">VS08-<?php echo $oid; ?></a></td>
        <td><strong><?php echo esc_html($o['client_name']); ?></strong><br><span style="font-size:11px;color:#9ca3af"><?php echo esc_html($o['client_email']); ?></span></td>
        <td><?php echo esc_html(mb_substr($o['titre'],0,30)); ?></td>
        <td><span class="ea-badge ea-badge-<?php echo $o['type']; ?>"><?php echo $o['type']==='golf'?'Golf':'Circuit'; ?></span></td>
        <td><?php echo $o['date_depart']?date('d/m/Y',strtotime($o['date_depart'])):'—'; ?></td>
        <td style="font-weight:700"><?php echo number_format($o['total'],0,',',' '); ?> €</td>
        <td><?php if($sd): ?><span class="ea-badge ea-badge-solde"><?php echo number_format($si['solde'],0,',',' '); ?> €</span><?php elseif($ip): ?><span class="ea-badge ea-badge-paid">Soldé ✓</span><?php else: ?><span style="color:#9ca3af;font-size:11px">—</span><?php endif; ?></td>
        <td><span class="ea-badge ea-badge-<?php echo $o['is_upcoming']?'upcoming':'past'; ?>"><?php echo $o['is_upcoming']?'À venir':'Passé'; ?></span></td>
        <td><a href="<?php echo home_url('/espace-admin/dossier/'.$oid.'/'); ?>" class="ea-btn ea-btn-outline" style="padding:6px 14px;font-size:11px">Voir →</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
<script>
function eaFilter(f,b){document.querySelectorAll('.ea-tab').forEach(function(t){t.classList.remove('active')});b.classList.add('active');document.querySelectorAll('#ea-tbl tbody tr').forEach(function(r){if(f==='all')r.style.display='';else if(f==='upcoming')r.style.display=r.dataset.up==='1'?'':'none';else if(f==='past')r.style.display=r.dataset.up==='0'?'':'none';else if(f==='solde')r.style.display=r.dataset.sd==='1'?'':'none';})}
function eaSearch(){var q=document.getElementById('ea-search').value.toLowerCase();document.querySelectorAll('#ea-tbl tbody tr').forEach(function(r){r.style.display=r.dataset.q.indexOf(q)!==-1?'':'none'})}
</script>

<?php elseif ($admin_view === 'dossier' && $admin_order_id): ?>
<?php
$order = wc_get_order($admin_order_id);
if (!$order) { echo '<p>Dossier introuvable.</p>'; } else {
    $data = VS08V_Traveler_Space::get_booking_data_from_order($order, true);
    if (!$data) { echo '<p>Données introuvables.</p>'; } else {
        $is_circuit = isset($data['type']) && $data['type'] === 'circuit';
        $params=$data['params']??[]; $devis=$data['devis']??[]; $fact=$data['facturation']??[]; $voyageurs=$data['voyageurs']??[];
        $titre = $is_circuit ? ($data['circuit_titre'] ?? 'Circuit') : ($data['voyage_titre'] ?? 'Séjour golf');
        $total = (float)($data['total'] ?? 0);
        $si = VS08V_Traveler_Space::get_solde_info($admin_order_id);
        $contract_url = VS08V_Traveler_Space::get_contract_url($admin_order_id);
        $carnet_files = get_post_meta($admin_order_id, '_vs08_carnet_files', true); if (!is_array($carnet_files)) $carnet_files = [];
        $admin_notes = get_post_meta($admin_order_id, '_vs08_admin_notes', true) ?: '';
        if ($is_circuit) { $pid=(int)($data['circuit_id']??0); $m=class_exists('VS08C_Meta')?VS08C_Meta::get($pid):[]; }
        else { $pid=(int)($data['voyage_id']??0); $m=class_exists('VS08V_MetaBoxes')?VS08V_MetaBoxes::get($pid):[]; }
        $destination = $m['destination'] ?? '';
        $duree_n = (int)($m['duree'] ?? 7); $duree_j = (int)($m['duree_jours'] ?? ($duree_n + 1));
        $date_retour = !empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'].' +'.$duree_n.' days')) : '';
?>
<a href="<?php echo home_url('/espace-admin/dossiers/'); ?>" class="ea-back">← Retour aux dossiers</a>
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 class="ea-page-title">VS08-<?php echo $admin_order_id; ?> — <?php echo esc_html($titre); ?></h1>
        <p class="ea-page-sub" style="margin-bottom:0">
            <span class="ea-badge ea-badge-<?php echo $is_circuit?'circuit':'golf'; ?>"><?php echo $is_circuit?'Circuit':'Golf'; ?></span>
            <?php if($si&&$si['solde_due']): ?><span class="ea-badge ea-badge-solde">Solde dû</span><?php elseif($si&&!empty($si['soldé_paye'])): ?><span class="ea-badge ea-badge-paid">Soldé ✓</span><?php endif; ?>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?php echo esc_url($contract_url); ?>" target="_blank" class="ea-btn ea-btn-outline">📄 Contrat</a>
        <a href="<?php echo admin_url('post.php?post='.$admin_order_id.'&action=edit'); ?>" target="_blank" class="ea-btn ea-btn-outline">⚙️ WordPress</a>
        <a href="mailto:<?php echo esc_attr($fact['email']??''); ?>" class="ea-btn ea-btn-primary">✉️ Contacter</a>
        <?php if($si&&$si['solde_due']&&$si['solde']>0): ?>
        <button type="button" class="ea-btn ea-btn-outline" style="border-color:#e8724a;color:#e8724a" onclick="eaSendReminder(<?php echo $admin_order_id; ?>,this)">🔔 Envoyer rappel solde</button>
        <?php endif; ?>
        <button type="button" class="ea-btn ea-btn-outline" onclick="eaResendEmails(<?php echo $admin_order_id; ?>,this)">📧 Re-envoyer emails résa</button>
    </div>
</div>
<div class="ea-detail-grid">
    <div class="ea-detail-card"><h3>📋 Récapitulatif</h3>
        <div class="ea-detail-row"><span>Destination</span><strong><?php echo esc_html($destination); ?></strong></div>
        <div class="ea-detail-row"><span>Départ</span><strong><?php echo !empty($params['date_depart'])?date('d/m/Y',strtotime($params['date_depart'])):'—'; ?></strong></div>
        <?php if($date_retour): ?><div class="ea-detail-row"><span>Retour</span><strong><?php echo $date_retour; ?></strong></div><?php endif; ?>
        <div class="ea-detail-row"><span>Durée</span><strong><?php echo $duree_j; ?>j / <?php echo $duree_n; ?>n</strong></div>
        <div class="ea-detail-row"><span>Voyageurs</span><strong><?php echo (int)($devis['nb_total']??0); ?> pers.</strong></div>
        <?php if(!empty($params['aeroport'])): ?><div class="ea-detail-row"><span>Aéroport</span><strong><?php echo strtoupper($params['aeroport']); ?></strong></div><?php endif; ?>
        <?php if(!empty($params['vol_aller_num'])): ?><div class="ea-detail-row"><span>Vol aller</span><strong><?php echo esc_html($params['vol_aller_num']); ?></strong></div><?php endif; ?>
        <?php if(!empty($params['vol_retour_num'])): ?><div class="ea-detail-row"><span>Vol retour</span><strong><?php echo esc_html($params['vol_retour_num']); ?></strong></div><?php endif; ?>
    </div>
    <div class="ea-detail-card"><h3>💰 Facturation</h3>
        <div class="ea-detail-row"><span>Client</span><strong><?php echo esc_html(trim(($fact['prenom']??'').' '.strtoupper($fact['nom']??''))); ?></strong></div>
        <div class="ea-detail-row"><span>Email</span><strong><a href="mailto:<?php echo esc_attr($fact['email']??''); ?>" style="color:#59b7b7"><?php echo esc_html($fact['email']??''); ?></a></strong></div>
        <?php if(!empty($fact['tel'])): ?><div class="ea-detail-row"><span>Tél.</span><strong><a href="tel:<?php echo esc_attr($fact['tel']); ?>" style="color:#59b7b7"><?php echo esc_html($fact['tel']); ?></a></strong></div><?php endif; ?>
        <?php if(!empty($fact['adresse'])): ?><div class="ea-detail-row"><span>Adresse</span><strong><?php echo esc_html($fact['adresse'].', '.($fact['cp']??'').' '.($fact['ville']??'')); ?></strong></div><?php endif; ?>
        <div class="ea-detail-row" style="border-top:2px solid #f0ece4;padding-top:12px;margin-top:4px"><span style="font-weight:700">Total</span><strong style="font-size:18px"><?php echo number_format($total,2,',',' '); ?> €</strong></div>
        <?php if($si): ?>
        <div class="ea-detail-row"><span>Payé</span><strong style="color:#059669"><?php echo number_format($total-$si['solde'],2,',',' '); ?> €</strong></div>
        <?php if($si['solde_due']&&$si['solde']>0): ?>
        <div class="ea-detail-row"><span>Solde</span><strong style="color:#dc2626"><?php echo number_format($si['solde'],2,',',' '); ?> €</strong></div>
        <?php if(!empty($si['solde_date'])): ?><div class="ea-detail-row"><span>Échéance</span><strong style="color:#e8724a"><?php echo esc_html($si['solde_date']); ?></strong></div><?php endif; ?>
        <?php endif; endif; ?>
    </div>
    <div class="ea-detail-card"><h3>👥 Voyageurs (<?php echo count($voyageurs); ?>)</h3>
        <?php foreach ($voyageurs as $i => $v): $ddn=$v['ddn']??$v['date_naissance']??''; ?>
        <div class="ea-detail-row"><span><?php echo ($i+1).'. '.esc_html(($v['prenom']??'').' '.strtoupper($v['nom']??'')); ?></span><strong><?php if($ddn): ?>Né(e) <?php echo date('d/m/Y',strtotime($ddn)); ?><?php endif; ?><?php if(!empty($v['passeport'])): ?> · <?php echo esc_html($v['passeport']); ?><?php endif; ?></strong></div>
        <?php endforeach; ?>
    </div>
    <div class="ea-detail-card"><h3>📊 Détail prix</h3>
        <?php foreach ($devis['lines'] ?? [] as $l): ?><div class="ea-detail-row"><span><?php echo esc_html($l['label']); ?></span><strong><?php echo number_format($l['montant'],0,',',' '); ?> €</strong></div><?php endforeach; ?>
        <?php if(($data['assurance']??0)>0): ?><div class="ea-detail-row"><span>🛡️ Assurance</span><strong><?php echo number_format($data['assurance'],0,',',' '); ?> €</strong></div><?php endif; ?>
    </div>
    <div class="ea-detail-card ea-detail-full"><h3>📝 Notes internes</h3>
        <textarea class="ea-notes-textarea" id="ea-notes" placeholder="Notes privées…"><?php echo esc_textarea($admin_notes); ?></textarea>
        <div style="margin-top:10px;display:flex;align-items:center;gap:12px">
            <button type="button" class="ea-btn ea-btn-primary" onclick="eaSaveNotes(<?php echo $admin_order_id; ?>,this)">Sauvegarder</button>
            <span id="ea-notes-fb" style="font-size:12px;font-family:'Outfit',sans-serif"></span>
        </div>
    </div>
    <div class="ea-detail-card ea-detail-full"><h3>📋 Carnet de voyage (<?php echo count($carnet_files); ?>)</h3>
        <?php if(empty($carnet_files)): ?><p style="color:#9ca3af;font-size:13px">Aucun document. <a href="<?php echo admin_url('post.php?post='.$admin_order_id.'&action=edit'); ?>" target="_blank" style="color:#59b7b7">Uploader via WordPress →</a></p>
        <?php else: foreach ($carnet_files as $cf): if(empty($cf['url'])) continue; ?>
        <div class="ea-detail-row"><span><a href="<?php echo esc_url($cf['url']); ?>" target="_blank" style="color:#59b7b7;font-weight:600"><?php echo esc_html($cf['name']??basename($cf['url'])); ?></a></span><strong style="font-size:11px;color:#9ca3af"><?php echo esc_html($cf['date']??''); ?></strong></div>
        <?php endforeach; ?><p style="margin-top:12px"><a href="<?php echo admin_url('post.php?post='.$admin_order_id.'&action=edit'); ?>" target="_blank" class="ea-btn ea-btn-outline" style="padding:6px 14px;font-size:11px">📎 Ajouter</a></p><?php endif; ?>
    </div>
</div>
<?php }} ?>
<script>
var EA_NONCE_D='<?php echo esc_js(wp_create_nonce('vs08_admin_actions')); ?>',EA_AJAX_D='<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
function eaSaveNotes(oid,btn){
    btn.disabled=true;btn.textContent='Enregistrement…';
    var fb=document.getElementById('ea-notes-fb');
    var fd=new FormData();fd.append('action','vs08_admin_save_notes');fd.append('nonce',EA_NONCE_D);fd.append('order_id',oid);fd.append('notes',document.getElementById('ea-notes').value);
    fetch(EA_AJAX_D,{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(res){
        btn.disabled=false;btn.textContent='Sauvegarder';
        fb.textContent=res.success?'✅ Sauvegardé':'❌ Erreur';fb.style.color=res.success?'#059669':'#dc2626';
        setTimeout(function(){fb.textContent=''},3000);
    }).catch(function(){btn.disabled=false;btn.textContent='Sauvegarder';fb.textContent='❌ Erreur réseau';fb.style.color='#dc2626'});
}
function eaSendReminder(oid,btn){
    if(!confirm('Envoyer un rappel de solde au client ?'))return;
    btn.disabled=true;var orig=btn.textContent;btn.textContent='Envoi…';
    var fd=new FormData();fd.append('action','vs08_admin_send_reminder');fd.append('nonce',EA_NONCE_D);fd.append('order_id',oid);
    fetch(EA_AJAX_D,{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(res){
        btn.disabled=false;btn.textContent=res.success?'✅ Envoyé !':orig;
        if(res.success)setTimeout(function(){btn.textContent=orig},3000);
        else alert(res.data||'Erreur');
    }).catch(function(){btn.disabled=false;btn.textContent=orig;alert('Erreur réseau')});
}
function eaResendEmails(oid,btn){
    if(!confirm('Re-envoyer les emails de réservation (admin + client) ?'))return;
    btn.disabled=true;var orig=btn.textContent;btn.textContent='Envoi…';
    var fd=new FormData();fd.append('action','vs08_admin_resend_emails');fd.append('nonce',EA_NONCE_D);fd.append('order_id',oid);
    fetch(EA_AJAX_D,{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(res){
        btn.disabled=false;btn.textContent=res.success?'✅ Emails envoyés !':orig;
        if(!res.success)alert(res.data||'Erreur');
        else setTimeout(function(){btn.textContent=orig},3000);
    }).catch(function(){btn.disabled=false;btn.textContent=orig;alert('Erreur réseau')});
}
</script>

<?php elseif ($admin_view === 'clients'): ?>
<?php $users = get_users(['role__in'=>['customer','subscriber'],'orderby'=>'registered','order'=>'DESC','number'=>200]); ?>
<h1 class="ea-page-title">Clients</h1>
<p class="ea-page-sub"><?php echo count($users); ?> client(s) enregistré(s)</p>
<div class="ea-table-card">
    <div class="ea-table-header"><span class="ea-table-title">👥 Liste des clients</span><input type="text" class="ea-table-search" id="ea-csearch" placeholder="Rechercher…" oninput="var q=this.value.toLowerCase();document.querySelectorAll('#ea-ctbl tbody tr').forEach(function(r){r.style.display=r.dataset.q.indexOf(q)!==-1?'':'none'})"></div>
    <table class="ea-table" id="ea-ctbl"><thead><tr><th>Nom</th><th>Email</th><th>Tél.</th><th>Inscrit</th><th>Résa</th><th></th></tr></thead><tbody>
    <?php foreach ($users as $u):
        $name=trim(($u->first_name?:'').' '.strtoupper($u->last_name?:''))?:$u->display_name;
        $phone=get_user_meta($u->ID,'billing_phone',true);
        $nb=count(wc_get_orders(['customer'=>$u->ID,'limit'=>-1,'return'=>'ids']));
    ?>
    <tr data-q="<?php echo esc_attr(strtolower($name.' '.$u->user_email)); ?>">
        <td><strong><?php echo esc_html($name); ?></strong></td>
        <td><a href="mailto:<?php echo esc_attr($u->user_email); ?>" style="color:#59b7b7"><?php echo esc_html($u->user_email); ?></a></td>
        <td><?php echo esc_html($phone?:'—'); ?></td>
        <td style="font-size:12px;color:#9ca3af"><?php echo date('d/m/Y',strtotime($u->user_registered)); ?></td>
        <td><span class="ea-badge ea-badge-golf"><?php echo $nb; ?></span></td>
        <td><a href="mailto:<?php echo esc_attr($u->user_email); ?>" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">✉️</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>

<?php elseif ($admin_view === 'messages'): ?>
<?php $msgs = array_reverse($messages_admin); ?>
<h1 class="ea-page-title">Messages</h1>
<p class="ea-page-sub"><?php echo count($msgs); ?> message(s)</p>
<div class="ea-table-card">
<?php if(empty($msgs)): ?><p style="text-align:center;padding:40px;color:#9ca3af">Aucun message.</p>
<?php else: foreach($msgs as $msg): ?>
<div class="ea-msg-item">
    <div class="ea-msg-avatar"><?php echo mb_strtoupper(mb_substr($msg['client_name']??'?',0,1)); ?></div>
    <div class="ea-msg-content">
        <div class="ea-msg-head"><span class="ea-msg-name"><?php echo esc_html($msg['client_name']??''); ?></span><span class="ea-msg-date"><?php echo esc_html($msg['date_fmt']??$msg['date']??''); ?></span></div>
        <div class="ea-msg-subj"><?php echo esc_html($msg['sujet']??''); ?></div>
        <?php if(!empty($msg['dossier'])): ?><div class="ea-msg-dossier"><?php echo esc_html($msg['dossier']); ?></div><?php endif; ?>
        <div class="ea-msg-body"><?php echo nl2br(esc_html($msg['message']??'')); ?></div>
        <div style="margin-top:8px">
            <a href="mailto:<?php echo esc_attr($msg['client_email']??''); ?>?subject=Re: <?php echo rawurlencode($msg['sujet']??''); ?>" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">↩️ Répondre</a>
            <?php if(!empty($msg['email_sent'])): ?><span style="font-size:11px;color:#059669;margin-left:8px">✓ Email envoyé</span><?php else: ?><span style="font-size:11px;color:#dc2626;margin-left:8px">✗ Email non reçu</span><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

<?php elseif ($admin_view === 'produits'): ?>
<?php
$golf_posts = get_posts(['post_type'=>'vs08_voyage','posts_per_page'=>-1,'post_status'=>'any','orderby'=>'title','order'=>'ASC']);
$circuit_posts = get_posts(['post_type'=>'vs08_circuit','posts_per_page'=>-1,'post_status'=>'any','orderby'=>'title','order'=>'ASC']);
?>
<h1 class="ea-page-title">Produits</h1>
<p class="ea-page-sub">⛳ <?php echo count($golf_posts); ?> séjour(s) · 🗺️ <?php echo count($circuit_posts); ?> circuit(s)</p>
<div class="ea-tabs">
    <button class="ea-tab active" id="ea-pt-golf" onclick="document.getElementById('ea-pg').style.display='';document.getElementById('ea-pc').style.display='none';this.classList.add('active');document.getElementById('ea-pt-circ').classList.remove('active')">⛳ Golf</button>
    <button class="ea-tab" id="ea-pt-circ" onclick="document.getElementById('ea-pc').style.display='';document.getElementById('ea-pg').style.display='none';this.classList.add('active');document.getElementById('ea-pt-golf').classList.remove('active')">🗺️ Circuits</button>
</div>
<div class="ea-table-card" id="ea-pg"><table class="ea-table"><thead><tr><th>Titre</th><th>Destination</th><th>Durée</th><th>Prix</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($golf_posts as $gp): $gm=class_exists('VS08V_MetaBoxes')?VS08V_MetaBoxes::get($gp->ID):[]; ?>
<tr><td><strong><?php echo esc_html($gp->post_title); ?></strong></td><td><?php echo esc_html($gm['destination']??'—'); ?></td><td><?php echo (int)($gm['duree']??0); ?>n</td><td><?php echo isset($gm['prix_double'])?number_format((float)$gm['prix_double'],0,',',' ').' €':'—'; ?></td><td><span class="ea-badge <?php echo $gp->post_status==='publish'?'ea-badge-paid':'ea-badge-past'; ?>"><?php echo $gp->post_status==='publish'?'Publié':ucfirst($gp->post_status); ?></span></td><td><a href="<?php echo admin_url('post.php?post='.$gp->ID.'&action=edit'); ?>" target="_blank" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">Modifier</a> <?php if($gp->post_status==='publish'): ?><a href="<?php echo get_permalink($gp->ID); ?>" target="_blank" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">Voir</a><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="ea-table-card" id="ea-pc" style="display:none"><table class="ea-table"><thead><tr><th>Titre</th><th>Destination</th><th>Durée</th><th>Prix</th><th>Statut</th><th></th></tr></thead><tbody>
<?php foreach ($circuit_posts as $cp): $cm=class_exists('VS08C_Meta')?VS08C_Meta::get($cp->ID):[]; ?>
<tr><td><strong><?php echo esc_html($cp->post_title); ?></strong></td><td><?php echo esc_html($cm['destination']??'—'); ?></td><td><?php echo (int)($cm['duree']??0); ?>n</td><td><?php echo isset($cm['prix_double'])?number_format((float)$cm['prix_double'],0,',',' ').' €':'—'; ?></td><td><span class="ea-badge <?php echo $cp->post_status==='publish'?'ea-badge-paid':'ea-badge-past'; ?>"><?php echo $cp->post_status==='publish'?'Publié':ucfirst($cp->post_status); ?></span></td><td><a href="<?php echo admin_url('post.php?post='.$cp->ID.'&action=edit'); ?>" target="_blank" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">Modifier</a> <?php if($cp->post_status==='publish'): ?><a href="<?php echo get_permalink($cp->ID); ?>" target="_blank" class="ea-btn ea-btn-outline" style="padding:5px 12px;font-size:11px">Voir</a><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>

<?php endif; ?>

</main>
</div>
<?php get_footer(); ?>
