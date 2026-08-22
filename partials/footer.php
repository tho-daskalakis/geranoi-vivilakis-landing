<footer>
	<div class="container">
		<div class="footer-top">
			<div class="footer-brand">
				<div class="logo-area" style="display:flex;gap:10px;align-items:center">
					<span class="logo-mark">
						<img class="logo-light" src="/logo-white.svg" width="156" height="230" alt="Βιβιλάκης Εμμανουήλ – Γερανοί και Μεταφορές">
					</span>
					<span class="logo-word">Βιβιλάκης Εμμανουήλ</span>
				</div>
				<p>Ο αξιόπιστος συνεργάτης σας στην Κρήτη</p>
				<div class="footer-social">
					<a href="https://www.instagram.com/vivilakis_cranes" target="_blank" rel="noopener" aria-label="Instagram">
						<span><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.8,2H16.2C19.4,2 22,4.6 22,7.8V16.2A5.8,5.8 0 0,1 16.2,22H7.8C4.6,22 2,19.4 2,16.2V7.8A5.8,5.8 0 0,1 7.8,2M7.6,4A3.6,3.6 0 0,0 4,7.6V16.4C4,18.39 5.61,20 7.6,20H16.4A3.6,3.6 0 0,0 20,16.4V7.6C20,5.61 18.39,4 16.4,4H7.6M17.25,5.5A1.25,1.25 0 0,1 18.5,6.75A1.25,1.25 0 0,1 17.25,8A1.25,1.25 0 0,1 16,6.75A1.25,1.25 0 0,1 17.25,5.5M12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9Z"/></svg></span>
					</a>
					<a href="https://www.facebook.com/profile.php?id=61574725953448" target="_blank" rel="noopener" aria-label="Facebook">
						<span><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/></svg></span>
					</a>
				</div>
			</div>
		</div>

		<div class="footer-cols">
			<div>
				<h4>Μενού</h4>
				<ul>
					<li><a href="/">Αρχική</a></li>
					<li><a href="/#about">Εταιρεία</a></li>
					<li><a href="/#services">Υπηρεσίες</a></li>
					<li><a href="/gallery">Γκαλερί</a></li>
					<li><a href="/#contact">Επικοινωνία</a></li>
					<li><a href="/privacy">Πολιτική Απορρήτου</a></li>
				</ul>
			</div>
			<div>
				<h4>Υπηρεσίες</h4>
				<ul>
					<li><a href="/#services">Ανυψώσεις με Γερανό</a></li>
					<li><a href="/#services">Μεταφορές Βαρέων Φορτίων</a></li>
					<li><a href="/#services">Ανύψωση &amp; Μεταφορά Σκαφών</a></li>
					<li><a href="/#services">Εργασίες με Καλαθοφόρο</a></li>
				</ul>
			</div>
			<div>
				<h4>Επικοινωνία</h4>
				<p style="color:var(--footer-fg);font-size:.9rem;margin-bottom:0">Καλέστε μας Δευ–Σάβ 8:00–16:00</p>
				<a class="phone" href="tel:+306949776292">6949 776 292 →</a>
				<p style="color:var(--footer-fg);font-size:.9rem;margin:16px 0 0">Ή στείλτε μας email απευθείας:</p>
				<a href="mailto:info@vivilakiscranes.gr" style="color:#fff;font-weight:600">info@vivilakiscranes.gr →</a>
			</div>
		</div>

		<div class="footer-bottom">
			<span>© <?= date('Y') ?> Γερανοί και Μεταφορές Βιβιλάκης Εμμανουήλ</span>
		</div>
	</div>
</footer>

<script>
	(function(){
		// Header: transparent -> solid on scroll
		var header = document.getElementById('siteHeader');
		function onScroll(){
			if(window.scrollY > 40){ header.classList.add('scrolled'); }
			else { header.classList.remove('scrolled'); }
		}
		onScroll();
		window.addEventListener('scroll', onScroll, {passive:true});

		// Mobile nav
		var menuBtn = document.getElementById('menuToggle');
		var mobileNav = document.getElementById('mobileNav');
		menuBtn.addEventListener('click', function(){
			var open = mobileNav.classList.toggle('open');
			menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		// Scroll-reveal
		var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if(!reduceMotion && 'IntersectionObserver' in window){
			var els = document.querySelectorAll('.reveal');
			var io = new IntersectionObserver(function(entries){
				entries.forEach(function(entry){
					if(entry.isIntersecting){
						entry.target.classList.add('is-visible');
						io.unobserve(entry.target);
					}
				});
			}, {threshold:0.12, rootMargin:'0px 0px -40px 0px'});
			els.forEach(function(el){ io.observe(el); });
		} else {
			document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('is-visible'); });
		}

		// Click-to-load map. Nothing reaches Google until the visitor asks for it,
		// which is what lets this site stay free of a cookie banner. The choice is
		// deliberately not remembered — persisting it would mean writing storage,
		// and the static map is a perfectly good default on every visit.
		var mapBtn = document.getElementById('mapLoad');
		if(mapBtn){
			mapBtn.addEventListener('click', function(){
				var holder = document.getElementById('mapStatic');
				if(!holder) return;
				var frame = document.createElement('iframe');
				frame.src = 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d974.2079015353709!2d25.111556271097946!3d35.2923880866169!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2sgr!4v1786559335462!5m2!1sen!2sgr';
				frame.title = 'Διαδραστικός χάρτης Google με τη θέση μας';
				frame.loading = 'lazy';
				frame.allowFullscreen = true;
				frame.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
				holder.replaceChildren(frame);
			}, {once:true});
		}

		// Hero video: injected only after everything else has loaded, and only when the
		// visitor's motion preference and connection can afford it. The <video> ships with
		// no <source>, so a gated visitor downloads zero video bytes rather than
		// downloading and then pausing. The poster is the LCP element either way.
		var video = document.querySelector('.hero-video');
		if(video && !reduceMotion){
			var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
			var slow = conn && (conn.saveData === true ||
				['slow-2g','2g','3g'].indexOf(conn.effectiveType) !== -1);
			if(!slow){
				var start = function(){
					var idle = window.requestIdleCallback || function(fn){ return setTimeout(fn, 200); };
					idle(function(){
						// MP4 only: measured on this footage, VP9/WebM came out within 4%
						// of H.264 for the same quality, which does not pay for a second
						// file. Every browser in use decodes H.264.
						var src = document.createElement('source');
						src.src = '/media/hero-video.mp4';
						src.type = 'video/mp4';
						video.appendChild(src);
						video.addEventListener('canplay', function(){
							video.classList.add('is-playing');
						}, {once:true});
						video.load();
						var p = video.play();
						// Autoplay can still be refused; the poster simply stays.
						if(p && p.catch){ p.catch(function(){}); }
					});
				};
				if(document.readyState === 'complete'){ start(); }
				else { window.addEventListener('load', start, {once:true}); }
			}
		}
	})();
</script>
</body>
</html>
