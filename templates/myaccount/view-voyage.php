<?php
if (!defined('ABSPATH')) exit;
$params = $data['params'] ?? [];
$devis = $data['devis'] ?? [];
$fact = $data['facturation'] ?? [];
$voyageurs = $data['voyageurs'] ?? [];
$voyage_id = (int)($data['voyage_id'] ?? 0);
$m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
$hotel_nom = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
$hotel_etoiles = $m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? '');
$destination = $m['destination'] ?? '';
$pension_labels = ['bb' => 'Petit-déjeuner', 'dp' => 'Demi-pension', 'pc' => 'Pension complète', 'ai' => 'Tout inclus', 'lo' => 'Logement seul'];
$pension_code = $m['pension'] ?? ($m['hotel']['pension'] ?? '');
$pension_label = $pension_labels[$pension_code] ?? '';
$total = (float)($data['total'] ?? 0);
$contract_url = VS08V_Traveler_Space::get_contract_url($order_id);
$can_pay_solde = $solde_info && $solde_info['solde_due'] && $solde_info['solde'] > 0;
$company = VS08V_Contract::COMPANY;
?>
<div class="vs08v-traveler-space vs08v-view-voyage">
	<div class="vs08v-voyage-header">
		<h1 class="vs08v-voyage-title"><?php echo esc_html($data['voyage_titre'] ?? 'Séjour golf'); ?></h1>
		<p class="vs08v-voyage-ref">N° contrat VS08-<?php echo $order_id; ?> · Départ le <?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : ''; ?></p>
	</div>

	<div class="vs08v-voyage-blocks">
		<section class="vs08v-voyage-block vs08v-block-recap">
			<h2>Récapitulatif</h2>
			<table class="vs08v-recap-table">
				<tr><th>Destination</th><td><?php echo esc_html($destination); ?></td></tr>
				<tr><th>Date de départ</th><td><?php echo $params['date_depart'] ? esc_html(date('d/m/Y', strtotime($params['date_depart']))) : '—'; ?></td></tr>
				<?php if ($hotel_nom): ?>
				<tr><th>Hébergement</th><td><?php echo esc_html($hotel_nom); ?><?php if ($hotel_etoiles): ?> <?php echo str_repeat('★', (int)$hotel_etoiles); ?><?php endif; ?></td></tr>
				<?php endif; ?>
				<?php if ($pension_label): ?>
				<tr><th>Pension</th><td><?php echo esc_html($pension_label); ?></td></tr>
				<?php endif; ?>
				<tr><th>Voyageurs</th><td><?php echo (int)($devis['nb_total'] ?? 0); ?> personne(s)</td></tr>
				<?php if (!empty($params['aeroport'])): ?>
				<tr><th>Aéroport</th><td><?php echo esc_html(strtoupper((string)($params['aeroport']??''))); ?></td></tr>
				<?php endif; ?>
				<?php if (!empty($params['vol_aller_num'])): ?>
				<tr><th>Vol aller</th><td><?php echo esc_html($params['vol_aller_num']); ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
				<?php endif; ?>
				<?php if (!empty($params['vol_retour_num'])): ?>
				<tr><th>Vol retour</th><td><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
				<?php endif; ?>
				<tr><th>Montant total</th><td><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></td></tr>
			</table>

			<?php if (!empty($voyageurs)): ?>
			<h3>Participants</h3>
			<ul class="vs08v-voyageurs-list">
				<?php foreach ($voyageurs as $v): ?>
				<li><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?>
					<?php if (!empty($v['date_naissance'])): ?> · Né(e) le <?php echo esc_html(date('d/m/Y', strtotime($v['date_naissance']))); ?><?php endif; ?>
					<?php if (!empty($v['passeport'])): ?> · Passeport <?php echo esc_html($v['passeport']); ?><?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</section>

		<?php if ($can_pay_solde): ?>
		<section class="vs08v-voyage-block vs08v-block-payment">
			<h2>Paiement du solde</h2>
			<p class="vs08v-solde-info">Solde restant à régler : <strong><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</strong>
				<?php if ($solde_info['solde_date']): ?> (avant le <?php echo esc_html($solde_info['solde_date']); ?>)<?php endif; ?></p>
			<div class="vs08v-payment-actions">
				<button type="button" class="vs08v-btn vs08v-btn-primary vs08v-btn-solde-cb" data-order-id="<?php echo $order_id; ?>">Payer le solde par carte bancaire</button>
				<button type="button" class="vs08v-btn vs08v-btn-secondary vs08v-btn-solde-agence" data-order-id="<?php echo $order_id; ?>">Paiement en agence</button>
			</div>
			<div class="vs08v-agence-info" id="vs08v-agence-info-<?php echo $order_id; ?>" style="display:none; margin-top:1rem; padding:1rem; background:#f8f8f8; border-radius:8px;">
				<p><strong>Paiement en agence</strong></p>
				<p><?php echo esc_html($company['name']); ?><br><?php echo esc_html($company['address']); ?><br><?php echo esc_html($company['city']); ?></p>
				<p>Tél. : <?php echo esc_html($company['tel']); ?></p>
				<p>Montant à régler : <strong><?php echo number_format($solde_info['solde'], 2, ',', ' '); ?> €</strong>. Précisez le n° de dossier : VS08-<?php echo $order_id; ?>.</p>
			</div>
		</section>
		<?php endif; ?>

		<section class="vs08v-voyage-block vs08v-block-docs">
			<h2>Documents</h2>
			<a href="<?php echo esc_url($contract_url); ?>" target="_blank" rel="noopener" class="vs08v-btn vs08v-btn-outline">Voir / imprimer le contrat de vente</a>
		</section>

		<section class="vs08v-voyage-block vs08v-block-question">
			<h2>Une question sur ce voyage ?</h2>
			<button type="button" class="vs08v-btn vs08v-btn-outline vs08v-btn-question" data-order-id="<?php echo $order_id; ?>">Poser une question</button>
		</section>
	</div>
</div>

<!-- Modale formulaire question -->
<div id="vs08v-question-modal" class="vs08v-modal" style="display:none" aria-hidden="true">
	<div class="vs08v-modal-backdrop"></div>
	<div class="vs08v-modal-content">
		<button type="button" class="vs08v-modal-close" aria-label="Fermer">&times;</button>
		<h3>Poser une question</h3>
		<form id="vs08v-question-form" class="vs08v-question-form">
			<input type="hidden" name="order_id" id="vs08v-question-order-id" value="">
			<p>
				<label for="vs08v-question-sujet">Sujet</label>
				<input type="text" name="sujet" id="vs08v-question-sujet" required placeholder="Ex. : horaires de vol">
			</p>
			<p>
				<label for="vs08v-question-message">Message</label>
				<textarea name="message" id="vs08v-question-message" rows="5" required placeholder="Votre question..."></textarea>
			</p>
			<p class="vs08v-form-actions">
				<button type="submit" class="vs08v-btn vs08v-btn-primary">Envoyer</button>
				<button type="button" class="vs08v-btn vs08v-modal-cancel">Annuler</button>
			</p>
		</form>
	</div>
</div>

<script>
(function() {
	var nonce = '<?php echo esc_js(wp_create_nonce('vs08v_traveler')); ?>';
	var modal = document.getElementById('vs08v-question-modal');
	var form = document.getElementById('vs08v-question-form');
	document.querySelectorAll('.vs08v-btn-question').forEach(function(btn) {
		btn.addEventListener('click', function() {
			document.getElementById('vs08v-question-order-id').value = this.getAttribute('data-order-id');
			document.getElementById('vs08v-question-sujet').value = '';
			document.getElementById('vs08v-question-message').value = '';
			modal.style.display = '';
			modal.setAttribute('aria-hidden', 'false');
		});
	});
	document.querySelectorAll('.vs08v-modal-close, .vs08v-modal-cancel, .vs08v-modal-backdrop').forEach(function(el) {
		el.addEventListener('click', function() {
			modal.style.display = 'none';
			modal.setAttribute('aria-hidden', 'true');
		});
	});
	form.addEventListener('submit', function(e) {
		e.preventDefault();
		var orderId = document.getElementById('vs08v-question-order-id').value;
		var sujet = document.getElementById('vs08v-question-sujet').value.trim();
		var message = document.getElementById('vs08v-question-message').value.trim();
		if (!sujet || !message) return;
		var btn = form.querySelector('button[type="submit"]');
		btn.disabled = true;
		btn.textContent = 'Envoi...';
		var fd = new FormData();
		fd.append('action', 'vs08v_traveler_question');
		fd.append('nonce', nonce);
		fd.append('order_id', orderId);
		fd.append('sujet', sujet);
		fd.append('message', message);
		fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (res.success) {
					alert(res.data.message || 'Message envoyé.');
					modal.style.display = 'none';
					modal.setAttribute('aria-hidden', 'true');
				} else {
					alert(res.data && res.data.message ? res.data.message : 'Erreur lors de l\'envoi.');
				}
			})
			.catch(function() { alert('Erreur réseau.'); })
			.finally(function() {
				btn.disabled = false;
				btn.textContent = 'Envoyer';
			});
	});

	document.querySelectorAll('.vs08v-btn-solde-cb').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var orderId = this.getAttribute('data-order-id');
			var fd = new FormData();
			fd.append('action', 'vs08v_traveler_solde');
			fd.append('nonce', nonce);
			fd.append('order_id', orderId);
			fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (res.success && res.data.redirect) {
						window.location.href = res.data.redirect;
					} else {
						alert(res.data && res.data.message ? res.data.message : 'Impossible de créer le paiement.');
					}
				})
				.catch(function() { alert('Erreur réseau.'); });
		});
	});
	document.querySelectorAll('.vs08v-btn-solde-agence').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var id = 'vs08v-agence-info-' + this.getAttribute('data-order-id');
			var el = document.getElementById(id);
			if (el) el.style.display = (el.style.display === 'none') ? '' : 'none';
		});
	});
})();
</script>
