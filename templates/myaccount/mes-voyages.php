<?php
if (!defined('ABSPATH')) exit;
$myaccount_url = wc_get_page_permalink('myaccount');
$has_any = !empty($voyage_orders);
?>
<div class="vs08v-traveler-space vs08v-mes-voyages">
	<div class="vs08v-mes-voyages-header">
		<h2 class="vs08v-mes-voyages-title">Mes voyages</h2>
		<p class="vs08v-mes-voyages-desc">Bienvenue dans votre espace voyageur. Consultez vos réservations à venir et passées, réglez un solde ou posez une question.</p>
	</div>
	<?php if ($has_any): ?>
	<div class="vs08v-voyages-tabs">
		<button type="button" class="vs08v-tab active" data-tab="upcoming">À venir</button>
		<button type="button" class="vs08v-tab" data-tab="past">Passés</button>
		<button type="button" class="vs08v-tab" data-tab="all">Tous</button>
	</div>

	<div class="vs08v-voyages-panel vs08v-panel-upcoming" data-panel="upcoming">
		<?php if (empty($upcoming)): ?>
			<p class="vs08v-empty">Aucun voyage à venir.</p>
		<?php else: ?>
			<div class="vs08v-voyages-grid">
				<?php foreach ($upcoming as $item):
					$order = $item['order'];
					$data = $item['booking_data'];
					$params = $data['params'] ?? [];
					$devis = $data['devis'] ?? [];
					$voyage_id = (int)($data['voyage_id'] ?? 0);
					$m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
					$galerie = $m['galerie'] ?? [];
					$img = !empty($galerie[0]) ? $galerie[0] : '';
					$destination = $m['destination'] ?? '';
					$hotel_nom = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
					$solde_info = VS08V_Traveler_Space::get_solde_info($order->get_id());
					$link = wc_get_endpoint_url(VS08V_Traveler_Space::ENDPOINT_VIEW, $order->get_id(), $myaccount_url);
				?>
				<article class="vs08v-voyage-card">
					<a href="<?php echo esc_url($link); ?>" class="vs08v-voyage-card-link">
						<div class="vs08v-voyage-card-img"<?php if ($img): ?> style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
							<?php if (!$img): ?><span class="vs08v-voyage-card-placeholder">⛳</span><?php endif; ?>
							<span class="vs08v-voyage-badge vs08v-badge-upcoming">À venir</span>
							<?php if ($solde_info && $solde_info['solde_due']): ?>
								<span class="vs08v-voyage-badge vs08v-badge-solde">Solde à régler</span>
							<?php endif; ?>
						</div>
						<div class="vs08v-voyage-card-body">
							<h3 class="vs08v-voyage-card-title"><?php echo esc_html($data['voyage_titre'] ?? 'Séjour golf'); ?></h3>
							<p class="vs08v-voyage-card-meta">
								<?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : ''; ?>
								<?php if ($destination): ?> — <?php echo esc_html($destination); ?><?php endif; ?>
							</p>
							<?php if ($hotel_nom): ?><p class="vs08v-voyage-card-hotel"><?php echo esc_html($hotel_nom); ?></p><?php endif; ?>
							<p class="vs08v-voyage-card-pax"><?php echo (int)($devis['nb_total'] ?? 0); ?> voyageur(s) · N° VS08-<?php echo $order->get_id(); ?></p>
							<?php if ($solde_info && $solde_info['solde_due']): ?>
								<p class="vs08v-voyage-solde-due">Solde : <?php echo number_format($solde_info['solde'], 0, ',', ' '); ?> € avant le <?php echo esc_html($solde_info['solde_date']); ?></p>
							<?php endif; ?>
							<span class="vs08v-voyage-card-cta">Voir le voyage</span>
						</div>
					</a>
				</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="vs08v-voyages-panel vs08v-panel-past" data-panel="past" style="display:none">
		<?php if (empty($past)): ?>
			<p class="vs08v-empty">Aucun voyage passé.</p>
		<?php else: ?>
			<div class="vs08v-voyages-grid">
				<?php foreach ($past as $item):
					$order = $item['order'];
					$data = $item['booking_data'];
					$params = $data['params'] ?? [];
					$devis = $data['devis'] ?? [];
					$voyage_id = (int)($data['voyage_id'] ?? 0);
					$m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
					$galerie = $m['galerie'] ?? [];
					$img = !empty($galerie[0]) ? $galerie[0] : '';
					$destination = $m['destination'] ?? '';
					$hotel_nom = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
					$link = wc_get_endpoint_url(VS08V_Traveler_Space::ENDPOINT_VIEW, $order->get_id(), $myaccount_url);
				?>
				<article class="vs08v-voyage-card">
					<a href="<?php echo esc_url($link); ?>" class="vs08v-voyage-card-link">
						<div class="vs08v-voyage-card-img"<?php if ($img): ?> style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
							<?php if (!$img): ?><span class="vs08v-voyage-card-placeholder">⛳</span><?php endif; ?>
							<span class="vs08v-voyage-badge vs08v-badge-past">Passé</span>
						</div>
						<div class="vs08v-voyage-card-body">
							<h3 class="vs08v-voyage-card-title"><?php echo esc_html($data['voyage_titre'] ?? 'Séjour golf'); ?></h3>
							<p class="vs08v-voyage-card-meta">
								<?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : ''; ?>
								<?php if ($destination): ?> — <?php echo esc_html($destination); ?><?php endif; ?>
							</p>
							<?php if ($hotel_nom): ?><p class="vs08v-voyage-card-hotel"><?php echo esc_html($hotel_nom); ?></p><?php endif; ?>
							<p class="vs08v-voyage-card-pax"><?php echo (int)($devis['nb_total'] ?? 0); ?> voyageur(s) · N° VS08-<?php echo $order->get_id(); ?></p>
							<span class="vs08v-voyage-card-cta">Voir le voyage</span>
						</div>
					</a>
				</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="vs08v-voyages-panel vs08v-panel-all" data-panel="all" style="display:none">
		<?php if (empty($voyage_orders)): ?>
			<p class="vs08v-empty">Aucun voyage.</p>
		<?php else: ?>
			<div class="vs08v-voyages-grid">
				<?php foreach ($voyage_orders as $item):
					$order = $item['order'];
					$data = $item['booking_data'];
					$params = $data['params'] ?? [];
					$devis = $data['devis'] ?? [];
					$voyage_id = (int)($data['voyage_id'] ?? 0);
					$m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
					$galerie = $m['galerie'] ?? [];
					$img = !empty($galerie[0]) ? $galerie[0] : '';
					$destination = $m['destination'] ?? '';
					$hotel_nom = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
					$solde_info = $item['is_upcoming'] ? VS08V_Traveler_Space::get_solde_info($order->get_id()) : null;
					$link = wc_get_endpoint_url(VS08V_Traveler_Space::ENDPOINT_VIEW, $order->get_id(), $myaccount_url);
				?>
				<article class="vs08v-voyage-card">
					<a href="<?php echo esc_url($link); ?>" class="vs08v-voyage-card-link">
						<div class="vs08v-voyage-card-img"<?php if ($img): ?> style="background-image:url(<?php echo esc_url($img); ?>)"<?php endif; ?>>
							<?php if (!$img): ?><span class="vs08v-voyage-card-placeholder">⛳</span><?php endif; ?>
							<span class="vs08v-voyage-badge <?php echo $item['is_upcoming'] ? 'vs08v-badge-upcoming' : 'vs08v-badge-past'; ?>"><?php echo $item['is_upcoming'] ? 'À venir' : 'Passé'; ?></span>
							<?php if ($solde_info && $solde_info['solde_due']): ?>
								<span class="vs08v-voyage-badge vs08v-badge-solde">Solde à régler</span>
							<?php endif; ?>
						</div>
						<div class="vs08v-voyage-card-body">
							<h3 class="vs08v-voyage-card-title"><?php echo esc_html($data['voyage_titre'] ?? 'Séjour golf'); ?></h3>
							<p class="vs08v-voyage-card-meta">
								<?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : ''; ?>
								<?php if ($destination): ?> — <?php echo esc_html($destination); ?><?php endif; ?>
							</p>
							<?php if ($hotel_nom): ?><p class="vs08v-voyage-card-hotel"><?php echo esc_html($hotel_nom); ?></p><?php endif; ?>
							<p class="vs08v-voyage-card-pax"><?php echo (int)($devis['nb_total'] ?? 0); ?> voyageur(s) · N° VS08-<?php echo $order->get_id(); ?></p>
							<span class="vs08v-voyage-card-cta">Voir le voyage</span>
						</div>
					</a>
				</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php else: ?>
	<div class="vs08v-empty-state">
		<div class="vs08v-empty-state-icon">⛳</div>
		<h3 class="vs08v-empty-state-title">Vous n'avez pas encore de réservation</h3>
		<p class="vs08v-empty-state-text">Dès que vous aurez réservé un séjour golf avec nous, vos voyages apparaîtront ici. Vous pourrez consulter le détail, régler un solde ou nous poser une question.</p>
		<a href="<?php echo esc_url(home_url('/golf')); ?>" class="vs08v-btn vs08v-btn-primary vs08v-empty-state-cta">Découvrir nos séjours golf</a>
	</div>
	<?php endif; ?>
</div>

<script>
(function() {
	var tabs = document.querySelectorAll('.vs08v-voyages-tabs .vs08v-tab');
	var panels = document.querySelectorAll('.vs08v-voyages-panel');
	tabs.forEach(function(tab) {
		tab.addEventListener('click', function() {
			var t = this.getAttribute('data-tab');
			tabs.forEach(function(x) { x.classList.remove('active'); });
			panels.forEach(function(p) {
				p.style.display = (p.getAttribute('data-panel') === t) ? '' : 'none';
			});
			this.classList.add('active');
		});
	});
})();
</script>
