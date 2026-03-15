<?php
/**
 * Template Name: Page Golf
 */
get_header(); ?>

<style>
.vs08-page-hero{position:relative;min-height:50vh;display:flex;align-items:center;background-size:cover;background-position:center}
.vs08-page-hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.9) 0%,rgba(15,36,36,.5) 100%)}
.vs08-page-hero-content{position:relative;z-index:2;padding:140px 80px 80px;max-width:800px}
.vs08-page-hero-content h1{font-size:clamp(36px,5vw,58px);color:#fff;margin-bottom:14px;font-family:'Playfair Display',serif;line-height:1.1}
.vs08-page-hero-content h1 em{color:#7ecece;font-style:italic}
.vs08-hero-badge{display:inline-block;color:#7ecece;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:18px;font-family:'Outfit',sans-serif}
.vs08-hero-desc{font-size:16px;color:rgba(255,255,255,.72);line-height:1.75;font-weight:300;font-family:'Outfit',sans-serif;max-width:520px;margin-top:12px}
.vs08-page-hero-stats{display:flex;gap:36px;margin-top:32px}
.vs08-page-hero-stats span{display:block;font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#7ecece}
.vs08-page-hero-stats small{display:block;font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:1px;margin-top:3px;font-family:'Outfit',sans-serif}
.vs08-golf-page{background:#f9f6f0;padding:60px 0 80px}
.vs08-golf-page-inner{max-width:1500px;margin:0 auto;padding:0 60px;display:grid;grid-template-columns:240px 1fr 260px;gap:28px;align-items:start}
.vs08-filters-sidebar{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.vs08-right-sidebar{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.vs08-filter-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.vs08-filter-card-title{font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin-bottom:18px;font-family:'Outfit',sans-serif;display:flex;justify-content:space-between;align-items:center}
.vs08-filter-reset-btn{background:none;border:none;color:#6b7280;font-size:11px;cursor:pointer;font-family:'Outfit',sans-serif;text-decoration:underline;padding:0}
.vs08-filter-reset-btn:hover{color:#e8724a}
.vs08-filter-group{margin-bottom:18px}
.vs08-filter-group:last-child{margin-bottom:0}
.vs08-filter-group label{display:block;font-size:12px;font-weight:600;color:#1a3a3a;margin-bottom:8px;font-family:'Outfit',sans-serif}
.vs08-filter-group select{width:100%;border:1.5px solid #f0f2f4;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;outline:none;cursor:pointer;-webkit-appearance:none;transition:border-color .2s}
.vs08-filter-group select:focus{border-color:#59b7b7}
.vs08-filter-count{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:14px;text-align:center}
.vs08-filter-count strong{color:#59b7b7}
.vs08-sidebar-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.vs08-sidebar-devis{background:#0f2424;text-align:center}
.vs08-sidebar-icon{font-size:32px;margin-bottom:12px}
.vs08-sidebar-devis h3{color:#fff;font-size:19px;margin-bottom:10px;font-family:'Playfair Display',serif}
.vs08-sidebar-devis p{color:rgba(255,255,255,.5);font-size:13px;line-height:1.65;font-family:'Outfit',sans-serif}
.vs08-sidebar-card h4{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#59b7b7;margin-bottom:14px;font-family:'Outfit',sans-serif}
.vs08-sidebar-tel-num{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#0f2424;margin-bottom:6px}
.vs08-sidebar-hours{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;line-height:1.8}
.vs08-sidebar-inclus ul{list-style:none;display:flex;flex-direction:column;gap:9px}
.vs08-sidebar-inclus li{font-size:13px;color:#1a3a3a;font-family:'Outfit',sans-serif;line-height:1.4}
.vs08-results-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.vs08-results-count{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif}
.vs08-results-count strong{color:#0f2424;font-size:16px}
.vs08-results-sort select{border:1.5px solid #f0f2f4;border-radius:10px;padding:8px 12px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fff;outline:none;cursor:pointer}
.vs08-golf-list{display:flex;flex-direction:column;gap:20px}
.vs08-golf-card{background:#fff;border-radius:20px;overflow:hidden;display:flex;flex-direction:row;transition:transform .35s,box-shadow .35s;cursor:pointer;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.vs08-golf-card:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.14)}
.vs08-golf-card-img{position:relative;overflow:hidden;width:290px;flex-shrink:0}
.vs08-golf-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;display:block;min-height:220px}
.vs08-golf-card:hover .vs08-golf-card-img img{transform:scale(1.07)}
.vs08-golf-card-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(15,36,36,.2) 0%,transparent 60%)}
.vs08-badge{position:absolute;top:14px;left:14px;padding:5px 13px;border-radius:100px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#fff;font-family:'Outfit',sans-serif;z-index:2}
.vs08-badge-new{background:#3d9a9a}
.vs08-badge-promo{background:#e8724a}
.vs08-badge-best{background:#c9a84c}
.vs08-golf-card-body{padding:26px 28px;flex:1;display:flex;flex-direction:column;justify-content:space-between}
.vs08-card-country{font-size:11px;color:#59b7b7;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;font-family:'Outfit',sans-serif;margin-bottom:6px}
.vs08-golf-card-body h3{font-size:21px;font-weight:700;color:#0f2424;margin-bottom:10px;line-height:1.25;font-family:'Playfair Display',serif}
.vs08-golf-card-desc{font-size:13px;color:#6b7280;line-height:1.65;font-family:'Outfit',sans-serif;margin-bottom:14px}
.vs08-golf-card-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.vs08-tag{background:#edf8f8;color:#3d9a9a;font-size:11px;font-weight:600;padding:4px 10px;border-radius:100px;font-family:'Outfit',sans-serif}
.vs08-niveau{font-size:11px;font-weight:600;padding:4px 10px;border-radius:100px;font-family:'Outfit',sans-serif}
.vs08-niveau-debutant{background:#e8f8f0;color:#2d8a5a}
.vs08-niveau-intermediaire{background:#edf8f8;color:#3d9a9a}
.vs08-niveau-confirme{background:#fff3e8;color:#b85c1a}
.vs08-golf-card-footer{display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid #f0f2f4}
.vs08-price-from{display:block;font-size:10px;color:#6b7280;text-transform:uppercase;font-family:'Outfit',sans-serif}
.vs08-price-amount{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#3d9a9a;line-height:1}
.vs08-price-per{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
.vs08-btn-card{display:inline-block;background:#59b7b7;color:#fff;padding:12px 24px;border-radius:100px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;transition:all .25s;text-decoration:none;white-space:nowrap}
.vs08-btn-card:hover{background:#3d9a9a;color:#fff}
.vs08-btn-coral{display:inline-block;background:#e8724a;color:#fff;padding:13px 26px;border-radius:100px;font-size:14px;font-weight:700;font-family:'Outfit',sans-serif;transition:all .3s;text-decoration:none;margin-top:16px}
.vs08-btn-coral:hover{background:#d4603c;color:#fff}
.vs08-btn-ghost{display:inline-block;color:rgba(255,255,255,.75);border:1.5px solid rgba(255,255,255,.25);padding:13px 24px;border-radius:100px;font-size:14px;font-weight:500;font-family:'Outfit',sans-serif;transition:all .3s;text-decoration:none}
.vs08-btn-ghost:hover{border-color:#7ecece;color:#7ecece}
.vs08-no-results{padding:60px 40px;text-align:center;background:#fff;border-radius:20px}
.vs08-no-results span{font-size:44px;display:block;margin-bottom:16px}
.vs08-no-results h3{font-size:22px;margin-bottom:10px;font-family:'Playfair Display',serif}
.vs08-no-results p{color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:20px}
.vs08-cta-section{padding:0 80px 80px;background:#f9f6f0}
.vs08-cta-inner{border-radius:28px;background:linear-gradient(135deg,rgba(15,36,36,.97) 0%,rgba(26,58,58,.92) 60%,rgba(89,183,183,.25) 100%),url('https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=1400&q=80') center/cover;padding:70px 80px;display:flex;justify-content:space-between;align-items:center;gap:40px}
.vs08-cta-content h2{font-size:36px;color:#fff;margin-bottom:12px;line-height:1.2;font-family:'Playfair Display',serif}
.vs08-cta-content h2 em{color:#7ecece;font-style:italic}
.vs08-cta-content p{color:rgba(255,255,255,.6);font-size:15px;max-width:420px;line-height:1.65;font-family:'Outfit',sans-serif}
.vs08-cta-actions{display:flex;gap:16px;align-items:center;flex-shrink:0}
@media(max-width:1100px){.vs08-golf-page-inner{grid-template-columns:240px 1fr;padding:0 40px}.vs08-golf-card-img{width:220px}}
@media(max-width:900px){.vs08-golf-page-inner{grid-template-columns:1fr;padding:0 24px}.vs08-filters-sidebar{position:static}.vs08-golf-card{flex-direction:column}.vs08-golf-card-img{width:100%;height:220px}.vs08-page-hero-content{padding:120px 24px 60px}.vs08-cta-section{padding:0 24px 60px}.vs08-cta-inner{flex-direction:column;padding:36px 24px}}
</style>

<!-- HERO -->
<section class="vs08-page-hero" style="background-image:url('https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=1920&q=80');">
    <div class="vs08-page-hero-overlay"></div>
    <div class="vs08-page-hero-content">
        <p class="vs08-hero-badge">⛳ &nbsp;Séjours Golf</p>
        <h1>Nos séjours golf <em>tout compris</em></h1>
        <p class="vs08-hero-desc">Parcours d'exception, hôtels de charme, vols inclus. Choisissez votre destination et laissez-vous guider.</p>
        <div class="vs08-page-hero-stats">
            <div><span>32</span><small>Séjours dispo</small></div>
            <div><span>18</span><small>Pays couverts</small></div>
            <div><span>250+</span><small>Parcours partenaires</small></div>
        </div>
    </div>
</section>

<section class="vs08-golf-page">
    <div class="vs08-golf-page-inner">

        <!-- SIDEBAR GAUCHE -->
        <aside class="vs08-filters-sidebar">

            <div class="vs08-filter-card">
                <div class="vs08-filter-card-title">
                    <span>🔍 Filtres</span>
                    <button class="vs08-filter-reset-btn" onclick="vs08ResetFilters()">Tout effacer</button>
                </div>
                <div class="vs08-filter-group">
                    <label>Destination</label>
                    <select id="filter-dest" onchange="vs08FilterCards()">
                        <option value="">Toutes les destinations</option>
                        <option value="portugal">🇵🇹 Portugal</option>
                        <option value="espagne">🇪🇸 Espagne</option>
                        <option value="maroc">🇲🇦 Maroc</option>
                        <option value="thailande">🇹🇭 Thaïlande</option>
                        <option value="irlande">🇮🇪 Irlande</option>
                    </select>
                </div>
                <div class="vs08-filter-group">
                    <label>Durée</label>
                    <select id="filter-duree" onchange="vs08FilterCards()">
                        <option value="">Toutes les durées</option>
                        <option value="7">7 nuits</option>
                        <option value="10">10 nuits</option>
                        <option value="14">14 nuits</option>
                    </select>
                </div>
                <div class="vs08-filter-group">
                    <label>Budget max / pers.</label>
                    <select id="filter-budget" onchange="vs08FilterCards()">
                        <option value="">Tous les budgets</option>
                        <option value="1000">Moins de 1 000€</option>
                        <option value="1500">Moins de 1 500€</option>
                        <option value="2000">Moins de 2 000€</option>
                    </select>
                </div>
                <div class="vs08-filter-group">
                    <label>Niveau golfeur</label>
                    <select id="filter-niveau" onchange="vs08FilterCards()">
                        <option value="">Tous les niveaux</option>
                        <option value="debutant">Débutant</option>
                        <option value="intermediaire">Intermédiaire</option>
                        <option value="confirme">Confirmé</option>
                    </select>
                </div>
                <p class="vs08-filter-count" id="vs08-filter-count"><strong>6</strong> séjours trouvés</p>
            </div>

        </aside>

        <!-- CONTENU -->
        <div>
            <div class="vs08-results-header">
                <p class="vs08-results-count"><strong id="vs08-count-num">6</strong> séjours golf disponibles</p>
                <div class="vs08-results-sort">
                    <select onchange="vs08SortCards(this.value)">
                        <option value="default">Trier : Recommandés</option>
                        <option value="prix-asc">Prix croissant</option>
                        <option value="prix-desc">Prix décroissant</option>
                    </select>
                </div>
            </div>

            <div class="vs08-golf-list" id="vs08-golf-grid">
                <?php foreach (vs08_get_sejours() as $s) : ?>
                <div class="vs08-golf-card"
                     data-dest="<?php echo esc_attr($s['dest_key']); ?>"
                     data-duree="<?php echo esc_attr($s['duree_key']); ?>"
                     data-budget="<?php echo esc_attr($s['prix_num']); ?>"
                     data-niveau="<?php echo esc_attr($s['niveau_key']); ?>">
                    <div class="vs08-golf-card-img">
                        <?php if ($s['badge']) : ?><span class="vs08-badge vs08-badge-<?php echo esc_attr($s['badge_type']); ?>"><?php echo esc_html($s['badge']); ?></span><?php endif; ?>
                        <img src="<?php echo esc_url($s['img']); ?>" alt="<?php echo esc_attr($s['title']); ?>" loading="lazy">
                        <div class="vs08-golf-card-overlay"></div>
                    </div>
                    <div class="vs08-golf-card-body">
                        <div>
                            <p class="vs08-card-country"><?php echo esc_html($s['flag'] . ' ' . $s['destination']); ?></p>
                            <h3><?php echo esc_html($s['title']); ?></h3>
                            <p class="vs08-golf-card-desc"><?php echo esc_html($s['desc']); ?></p>
                            <div class="vs08-golf-card-meta">
                                <?php foreach ($s['tags'] as $tag) : ?><span class="vs08-tag"><?php echo esc_html($tag); ?></span><?php endforeach; ?>
                                <span class="vs08-niveau vs08-niveau-<?php echo esc_attr($s['niveau_key']); ?>"><?php echo esc_html($s['niveau']); ?></span>
                            </div>
                        </div>
                        <div class="vs08-golf-card-footer">
                            <div>
                                <span class="vs08-price-from">Dès</span>
                                <span class="vs08-price-amount"><?php echo esc_html($s['prix']); ?></span>
                                <span class="vs08-price-per">/personne tout compris</span>
                            </div>
                            <a href="<?php echo esc_url($s['url']); ?>" class="vs08-btn-card">Voir ce séjour →</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="vs08-no-results" id="vs08-no-results" style="display:none;">
                    <span>🔍</span>
                    <h3>Aucun séjour ne correspond</h3>
                    <p>Essayez d'élargir vos filtres ou <a href="<?php echo home_url('/contact'); ?>">contactez-nous</a> pour un séjour sur mesure.</p>
                    <button onclick="vs08ResetFilters()" style="background:#e8724a;color:#fff;border:none;padding:12px 24px;border-radius:100px;font-family:'Outfit',sans-serif;font-weight:700;cursor:pointer;margin-top:8px;">Voir tous les séjours</button>
                </div>
            </div>
        </div><!-- /results -->

        <!-- SIDEBAR DROITE -->
        <aside class="vs08-right-sidebar">

            <div class="vs08-sidebar-card vs08-sidebar-devis">
                <div class="vs08-sidebar-icon">🏌️</div>
                <h3>Séjour sur mesure ?</h3>
                <p>Parlez-nous de votre projet et recevez une proposition sous 24h.</p>
                <a href="<?php echo home_url('/contact'); ?>" class="vs08-btn-coral">Demander un devis</a>
            </div>

            <div class="vs08-sidebar-card">
                <h4>📞 Conseiller disponible</h4>
                <p class="vs08-sidebar-tel-num">03 26 65 28 63</p>
                <p class="vs08-sidebar-hours">
                    <strong>Lun – Ven</strong><br>09h00 – 12h00 / 14h00 – 18h30<br>
                    <strong>Samedi</strong><br>09h00 – 12h00 / 14h00 – 18h00
                </p>
            </div>

            <div class="vs08-sidebar-card vs08-sidebar-inclus">
                <h4>Toujours inclus ✅</h4>
                <ul>
                    <li>✈️ Vols aller-retour</li>
                    <li>🏨 Hôtel sélectionné</li>
                    <li>⛳ Green fees</li>
                    <li>🚌 Transferts aéroport ou voiture de location</li>
                    <li>👤 Conseiller dédié</li>
                </ul>
            </div>

        </aside>

    </div>
</section>

<section class="vs08-cta-section">
    <div class="vs08-cta-inner">
        <div class="vs08-cta-content">
            <h2>Vous ne trouvez pas<br><em>votre séjour idéal ?</em></h2>
            <p>Chaque séjour peut être personnalisé : dates, durée, hôtel, nombre de parcours. Dites-nous ce que vous voulez, on s'occupe du reste.</p>
        </div>
        <div class="vs08-cta-actions">
            <a href="<?php echo home_url('/contact'); ?>" class="vs08-btn-coral" style="margin-top:0;">Créer mon séjour sur mesure</a>
            <a href="tel:0326652863" class="vs08-btn-ghost">📞 03 26 65 28 63</a>
        </div>
    </div>
</section>

<script>
function vs08FilterCards(){var dest=document.getElementById('filter-dest').value,duree=document.getElementById('filter-duree').value,budget=document.getElementById('filter-budget').value,niveau=document.getElementById('filter-niveau').value,cards=document.querySelectorAll('.vs08-golf-card'),visible=0;cards.forEach(function(c){var ok=true;if(dest&&c.dataset.dest!==dest)ok=false;if(duree&&c.dataset.duree!==duree)ok=false;if(budget&&parseInt(c.dataset.budget)>parseInt(budget))ok=false;if(niveau&&c.dataset.niveau!==niveau)ok=false;c.style.display=ok?'':'none';if(ok)visible++;});document.getElementById('vs08-no-results').style.display=visible===0?'block':'none';document.getElementById('vs08-count-num').textContent=visible;var fc=document.getElementById('vs08-filter-count');if(fc)fc.innerHTML='<strong>'+visible+'</strong> séjour'+(visible>1?'s':'')+' trouvé'+(visible>1?'s':'');}
function vs08ResetFilters(){['filter-dest','filter-duree','filter-budget','filter-niveau'].forEach(function(id){document.getElementById(id).value='';});vs08FilterCards();}
function vs08SortCards(val){var list=document.getElementById('vs08-golf-grid'),cards=Array.from(document.querySelectorAll('.vs08-golf-card'));if(val==='prix-asc')cards.sort(function(a,b){return parseInt(a.dataset.budget)-parseInt(b.dataset.budget);});else if(val==='prix-desc')cards.sort(function(a,b){return parseInt(b.dataset.budget)-parseInt(a.dataset.budget);});cards.forEach(function(c){list.appendChild(c);});}
</script>

<?php
function vs08_get_sejours(){return[['title'=>'Séjour Golf & Soleil Costa Vicentina','destination'=>'Portugal — Algarve','flag'=>'🇵🇹','dest_key'=>'portugal','duree_key'=>'7','prix'=>'1 290€','prix_num'=>1290,'niveau'=>'Tous niveaux','niveau_key'=>'intermediaire','badge'=>'Nouveauté','badge_type'=>'new','desc'=>'5 parcours d\'exception, hôtel 5★ vue Atlantique, green fees & transferts inclus. Le Portugal du golf à son meilleur.','tags'=>['✈️ Vol inclus','🌙 7 nuits','⛳ 5 parcours','🏨 Hôtel 5★'],'img'=>'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=700&q=80','url'=>home_url('/sejour/portugal-algarve')],['title'=>'Golf Royal & Riad sous le soleil marocain','destination'=>'Maroc — Agadir','flag'=>'🇲🇦','dest_key'=>'maroc','duree_key'=>'7','prix'=>'890€','prix_num'=>890,'niveau'=>'Débutant bienvenu','niveau_key'=>'debutant','badge'=>'Promo','badge_type'=>'promo','desc'=>'Parcours royaux et nuits en riad traditionnel. Le golf autrement, sous un ciel toujours bleu.','tags'=>['✈️ Vol inclus','🌙 7 nuits','⛳ 3 parcours'],'img'=>'https://images.unsplash.com/photo-1553603227-2358aabe821e?w=700&q=80','url'=>home_url('/sejour/maroc-agadir')],['title'=>'Costa del Sol Luxury Golf Resort','destination'=>'Espagne — Marbella','flag'=>'🇪🇸','dest_key'=>'espagne','duree_key'=>'10','prix'=>'1 590€','prix_num'=>1590,'niveau'=>'Confirmé','niveau_key'=>'confirme','badge'=>'Best-seller','badge_type'=>'best','desc'=>'Parcours de championnat face à la mer, resort 5★ avec spa et piscine à débordement.','tags'=>['✈️ Vol inclus','🌙 10 nuits','⛳ 4 parcours','💆 Spa inclus'],'img'=>'https://images.unsplash.com/photo-1593111774240-d529f12cf4bb?w=700&q=80','url'=>home_url('/sejour/espagne-marbella')],['title'=>'Golf & Temples — Phuket & Hua Hin','destination'=>'Thaïlande — Phuket','flag'=>'🇹🇭','dest_key'=>'thailande','duree_key'=>'14','prix'=>'2 190€','prix_num'=>2190,'niveau'=>'Intermédiaire','niveau_key'=>'intermediaire','badge'=>'','badge_type'=>'','desc'=>'Golf tropical sur des parcours de rêve, entre rizières, temples et plages de sable blanc.','tags'=>['✈️ Vol inclus','🌙 14 nuits','⛳ 6 parcours'],'img'=>'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=700&q=80','url'=>home_url('/sejour/thailande-phuket')],['title'=>'Wild Atlantic Golf — Kerry & Dingle','destination'=>'Irlande — Kerry','flag'=>'🇮🇪','dest_key'=>'irlande','duree_key'=>'7','prix'=>'1 190€','prix_num'=>1190,'niveau'=>'Confirmé','niveau_key'=>'confirme','badge'=>'Nouveauté','badge_type'=>'new','desc'=>'Links authentiques battus par le vent de l\'Atlantique. Une expérience de golf inoubliable dans les paysages irlandais.','tags'=>['✈️ Vol inclus','🌙 7 nuits','⛳ Links authentiques'],'img'=>'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=700&q=80','url'=>home_url('/sejour/irlande-kerry')],['title'=>'Golf & Gastronomie — Lisbonne & Cascais','destination'=>'Portugal — Lisbonne','flag'=>'🇵🇹','dest_key'=>'portugal','duree_key'=>'10','prix'=>'1 490€','prix_num'=>1490,'niveau'=>'Tous niveaux','niveau_key'=>'intermediaire','badge'=>'','badge_type'=>'','desc'=>'Golf en bord d\'Atlantique le matin, gastronomie portugaise et pasteis de nata le soir.','tags'=>['✈️ Vol inclus','🌙 10 nuits','⛳ 3 parcours','🍷 Gastronomie'],'img'=>'https://images.unsplash.com/photo-1555881400-74d7acaacd8b?w=700&q=80','url'=>home_url('/sejour/portugal-lisbonne')]];}
get_footer();
