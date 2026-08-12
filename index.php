<?php
/**
 * Landing page.
 *
 * The FAQ is defined once here and rendered twice — as the visible <details>
 * list and as FAQPage JSON-LD. Google requires the two to match; keeping one
 * source makes drift impossible.
 *
 * THREE entries is correct. A fourth ("Πόσο σύντομα μπορείτε να
 * ανταποκριθείτε σε επείγουσα ανάγκη;") once existed in the JSON-LD only and
 * was removed from the page deliberately. Do not "fix" the count by adding it
 * back — if it is ever wanted again, it goes in this array so both renderings
 * get it together.
 */

$faq = [
	[
		'q' => 'Πόσο κοστίζει η ενοικίαση γερανού στην Κρήτη;',
		'a' => 'Το κόστος εξαρτάται από το βάρος, την απόσταση και τη διάρκεια της εργασίας. Επικοινωνήστε μαζί μας για δωρεάν, γραπτή προσφορά.',
	],
	[
		'q' => 'Εξυπηρετείτε όλη την Κρήτη ή μόνο στο Ηράκλειο;',
		'a' => 'Η έδρα είναι στο Ηράκλειο, εξυπηρετούμε όμως εργασίες σε όλο το νησί: Χανιά, Ρέθυμνο, Άγιο Νικόλαο, Σητεία, Ιεράπετρα και Μοίρες.',
	],
	[
		'q' => 'Κάνετε ανύψωση και μεταφορά σκαφών;',
		'a' => 'Ναι, διαθέτουμε ειδικό εξάρτημα ανύψωσης σκαφών για ασφαλή ανέλκυση, καθέλκυση και μεταφορά σε μαρίνες και ναυπηγεία της Κρήτης.',
	],
];

/**
 * Result of a contact-form submission, set by send.php via a 303 redirect so a
 * refresh cannot resend the enquiry. Unknown values fall through to null.
 */
$form_messages = [
	'ok'        => [true,  'Το μήνυμά σας στάλθηκε. Θα επικοινωνήσουμε μαζί σας το συντομότερο δυνατό.'],
	'invalid'   => [false, 'Ελέγξτε τα στοιχεία σας — χρειαζόμαστε όνομα, μήνυμα και έναν τρόπο επικοινωνίας.'],
	'consent'   => [false, 'Χρειάζεται να συμφωνήσετε με την επικοινωνία για να σας απαντήσουμε.'],
	'ratelimit' => [false, 'Έχουν σταλεί πολλά μηνύματα από τη σύνδεσή σας. Δοκιμάστε αργότερα ή καλέστε μας στο 6949 776 292.'],
	'error'     => [false, 'Παρουσιάστηκε πρόβλημα στην αποστολή. Καλέστε μας στο 6949 776 292 ή στείλτε email στο info@vivilakiscranes.gr.'],
];
$form_status = null;
if (isset($_GET['sent']) && isset($form_messages[$_GET['sent']])) {
	[$ok, $text] = $form_messages[$_GET['sent']];
	$form_status = ['ok' => $ok, 'text' => $text];
}

$page_title       = 'Γερανοί και Μεταφορές Ηράκλειο Κρήτης | Βιβιλάκης Εμμανουήλ';
$page_description = 'Γερανός, ανυψωτικό όχημα και μεταφορές βαρέων φορτίων στην Κρήτη. Ανύψωση σκαφών, εργοτάξια, έκτακτες ανάγκες. Καλέστε τώρα για δωρεάν προσφορά.';
$canonical        = 'https://vivilakiscranes.gr/';

$business = [
	'@context' => 'https://schema.org',
	'@type' => ['LocalBusiness', 'MovingCompany'],
	'@id' => 'https://vivilakiscranes.gr/#business',
	'name' => 'Βιβιλάκης Εμμανουήλ Γερανοί και Μεταφορές',
	'description' => 'Υπηρεσίες γερανού, ανυψωτικού οχήματος και μεταφορών βαρέων φορτίων στην Κρήτη, με έδρα το Ηράκλειο.',
	'url' => 'https://vivilakiscranes.gr/',
	'telephone' => '+30-694-977-6292',
	'email' => 'info@vivilakiscranes.gr',
	'image' => 'https://vivilakiscranes.gr/og-cover.jpg',
	'logo' => 'https://vivilakiscranes.gr/logo.png',
	'priceRange' => '€€',
	'paymentAccepted' => 'Cash, Credit Card',
	'currenciesAccepted' => 'EUR',
	'address' => [
		'@type' => 'PostalAddress',
		'streetAddress' => 'Πάροδος Μάνου Κατράκη, Φοινικιά',
		'addressLocality' => 'Ηράκλειο',
		'addressRegion' => 'Κρήτη',
		'postalCode' => '71500',
		'addressCountry' => 'GR',
	],
	'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 35.292582, 'longitude' => 25.111259],
	'hasMap' => 'https://www.openstreetmap.org/?mlat=35.292582&mlon=25.111259#map=17/35.292582/25.111259',
	'areaServed' => array_merge(
		array_map(
			fn($n) => ['@type' => 'City', 'name' => $n],
			['Ηράκλειο', 'Χανιά', 'Ρέθυμνο', 'Άγιος Νικόλαος', 'Σητεία', 'Ιεράπετρα', 'Μοίρες']
		),
		[['@type' => 'AdministrativeArea', 'name' => 'Κρήτη']]
	),
	'openingHoursSpecification' => [[
		'@type' => 'OpeningHoursSpecification',
		'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		'opens' => '08:00',
		'closes' => '16:00',
	]],
	'sameAs' => [
		'https://www.facebook.com/profile.php?id=61574725953448',
		'https://www.instagram.com/vivilakis_cranes',
	],
	'makesOffer' => array_map(
		fn($n) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $n, 'areaServed' => 'Κρήτη']],
		['Ανυψώσεις με γερανό', 'Μεταφορές βαρέων φορτίων', 'Ανύψωση και μεταφορά σκαφών', 'Εργασίες με καλαθοφόρο ανυψωτικό']
	),
];

$faq_schema = [
	'@context' => 'https://schema.org',
	'@type' => 'FAQPage',
	'mainEntity' => array_map(fn($f) => [
		'@type' => 'Question',
		'name' => $f['q'],
		'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
	], $faq),
];

$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
$json_ld = '';
foreach ([$business, $faq_schema] as $block) {
	$json_ld .= "\t<script type=\"application/ld+json\">\n" . json_encode($block, $flags) . "\n\t</script>\n";
}

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<main id="main">

	<!-- HERO -->
	<div id="top" class="hero">
		<div class="hero-media">
			<!-- The poster is the LCP element and the permanent fallback. It is frame 1 of
			     hero-video, so the cross-fade is imperceptible. The <video> ships with NO
			     <source>: partials/footer.php injects them after window.load, and only when
			     motion preference and connection quality allow, so gated visitors download
			     zero video bytes. -->
			<div class="ph"><img src="/hero-poster.webp" width="1920" height="1080" fetchpriority="high" alt="Γερανοφόρο όχημα Βιβιλάκη με ιστιοφόρο σκάφος δεμένο σε πλατφόρμα μεταφοράς" class="hero-photo"></div>
			<video class="hero-video" muted playsinline loop preload="none" aria-hidden="true" tabindex="-1"></video>
		</div>
		<div class="hero-content">
			<div class="hero-badge">
				<span class="pill">Βιβιλάκης Εμμανουήλ</span>
				<span class="tag">Ο αξιόπιστος συνεργάτης σας στην Κρήτη</span>
			</div>
			<h1>Ασφαλείς ανυψώσεις και μεταφορές</h1>
			<p class="hero-sub">Εργασίες ανύψωσης και μεταφορές σε όλη την Κρήτη — βαριά φορτία, εργοτάξια, μεταφορές σκαφών και κοντέινερ, με ασφάλεια και συνέπεια.</p>
			<div class="hero-ctas">
				<a class="btn btn-accent" href="#contact">Ζητήστε προσφορά</a>
				<a class="link-arrow" href="#about" style="color:#fff">Μάθετε περισσότερα →</a>
			</div>
		</div>
	</div>

	<!-- COMPANY INTRO -->
	<section class="intro reveal" id="about">
		<div class="container intro-inner">
			<div class="logo-mark">
				<img class="logo-dark" src="/logo.svg" width="156" height="230" alt="Βιβιλάκης Εμμανουήλ – Γερανοί και Μεταφορές">
			</div>
			<h2>Βιβιλάκης Εμμανουήλ Γερανοί και Μεταφορές</h2>
			<p>Με έδρα το Ηράκλειο Κρήτης, εξειδικευόμαστε στις ανυψώσεις με γερανούς, τις ειδικές μεταφορές σε σκάφη και βαριά φορτία και τις εγκαταστάσεις βαρέως εξοπλισμού.
				Συνδυάζουμε σύγχρονο εξοπλισμό, τεχνογνωσία και συνέπεια, παρέχοντας ασφαλείς και αξιόπιστες λύσεις συνεχίζοντας μία οικογενειακή παράδοση στον χώρο που μετρά πάνω από 50 χρόνια.
			</p>
			<p>Διαθέτουμε γερανοφόρα οχήματα, καλαθοφόρο, κλαρκ, πλατφόρμες εργασίας προσωπικού και νταλίκα μεταφοράς σκαφών και βαρέων φορτίων.</p>
			<div class="social-row">
				<a target="_blank" rel="noopener" href="https://www.facebook.com/profile.php?id=61574725953448">
					<img src="/facebook.svg" alt="" width="32" height="32" style="width: 32px; height: 32px;"> Βιβιλάκης Εμμανουήλ - Γερανοί &amp; Ειδικές Μεταφορές</a>
				<a target="_blank" rel="noopener" href="https://www.instagram.com/vivilakis_cranes?igsh=MTA5aTFhanNqam5mbA==">
					<img src="/instagram.svg" alt="" width="32" height="32" style="width: 32px; height: 32px"> @vivilakis_cranes</a>
			</div>
		</div>
	</section>

	<!-- SERVICES -->
	<section class="section" id="services">
		<div class="container">
			<div class="section-head reveal">
				<h2>Υπηρεσίες</h2>
				<p>Προσφέρουμε ευρύ φάσμα υπηρεσιών σε ανυψώσεις και μεταφορές, ειδικευόμενοι σε μεταφορές σκαφών όπως και σε εργασίες εντός εργοταξίων.</p>
			</div>
			<div class="badge-row reveal">
				<span>Επαγγελματισμός</span>
				<span>Ασφάλεια</span>
				<span>Τεχνογνωσία</span>
				<span>Εμπειρία</span>
			</div>
			<div class="services-grid">
				<div class="service-item reveal">
					<h3>Ανυψώσεις με γερανό</h3>
					<p>Χρήση σύγχρονου και αξιόπιστου εξοπλισμού για κάθε είδους ανύψωση.</p>
				</div>
				<div class="service-item reveal">
					<h3>Μεταφορές Βαρέων Φορτίων</h3>
					<p>Μεταφορά μηχανημάτων, υλικών και εξοπλισμού σε όλη την Κρήτη, με ασφάλεια και συνέπεια.</p>
				</div>
				<div class="service-item reveal">
					<h3>Ανύψωση &amp; Μεταφορά Σκαφών</h3>
					<p>Ανέλκυση, καθέλκυση και μεταφορά σκαφών σε μαρίνες και ναυπηγεία.</p>
				</div>
				<div class="service-item reveal">
					<h3>Εργασίες με Καλαθοφόρο</h3>
					<p>Καλάθι εργασίας για προσωπικό έως 32 μέτρα ύψος.</p>
				</div>
				<div class="service-item reveal">
					<h3>Έκτακτες Ανάγκες</h3>
					<p>Άμεση ανταπόκριση για επείγουσες ανάγκες ανύψωσης ή μεταφοράς, όποτε προκύψουν.</p>
				</div>
				<div class="service-item reveal">
					<h3>Μηχανήματα έργου</h3>
					<p>Κλαρκ ανυψωτικής ικανότητας έως 7 τόννους και πλατφόρμες εργασίας προσωπικού.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- PARTNERSHIP / PHOTO SPLIT -->
	<section class="section" style="padding-top:0">
		<div class="container">
			<div class="split">
				<div class="reveal">
					<img src="/worksite.jpeg" width="1100" height="1467" loading="lazy" decoding="async" alt="Γερανός σε εργοτάξιο κατά την εκφόρτωση φορτίου" style="border-radius: 5%">
				</div>
				<div class="reveal">
					<h2>Σταθερές συνεργασίες με βάση την εμπιστοσύνη</h2>
					<p>Χτίζουμε μακροπρόθεσμες συνεργασίες με τους πελάτες και τους συνεργάτες μας. Βασιζόμαστε στην εμπιστοσύνη, την άμεση εξυπηρέτηση και το βέλτιστο αποτέλεσμα σε κάθε έργο.</p>
					<p>Βλέπουμε κάθε πελάτη και συνεργάτη ως πολύτιμο μέρος της αναπτυξιακής πορείας της εταιρείας μας και δεσμευόμαστε να τους παρέχουμε υπηρεσίες και υποστήριξη πρώτης τάξεως σε μακροπρόθεσμη βάση.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="section alt-none" id="faq" style="padding-top:0">
		<div class="container">
			<div class="section-head reveal">
				<h2>Συχνές Ερωτήσεις</h2>
			</div>
			<div class="faq-list reveal">
<?php foreach ($faq as $item): ?>
				<details class="faq-item">
					<summary><?= e($item['q']) ?></summary>
					<p><?= e($item['a']) ?></p>
				</details>
<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- CONTACT -->
	<section class="section" id="contact" style="background:var(--surface)">
		<div class="container">
			<div class="contact-grid">
				<div class="reveal">
					<h2>Αφήστε μας μήνυμα</h2>
					<p>Μπορείτε να επικοινωνήσετε μαζί μας μέσω της φόρμας. Απαντάμε το συντομότερο δυνατό.</p>

<?php if ($form_status !== null): ?>
					<p class="form-status <?= $form_status['ok'] ? 'is-ok' : 'is-error' ?>" role="status"><?= e($form_status['text']) ?></p>
<?php endif; ?>

					<!-- Posts to /send, not /send.php: the .htaccess redirect to the
					     extensionless URL is a 301, and a redirected POST becomes a GET
					     with no body. send.php redirects back here with ?sent=... -->
					<form action="/send" method="post" style="margin-top:28px">
						<!-- Honeypot. Off-screen rather than display:none, and hidden from
						     assistive tech and the tab order, so only a bot fills it. -->
						<div class="hp" aria-hidden="true">
							<label>Μην συμπληρώσετε αυτό το πεδίο
								<input type="text" name="website" tabindex="-1" autocomplete="off">
							</label>
						</div>
						<div class="field-row two">
							<label class="field"><input type="text" name="name" placeholder="Όνομα" required autocomplete="name"></label>
							<label class="field"><input type="text" name="company" placeholder="Εταιρεία (προαιρετικό)" autocomplete="organization"></label>
						</div>
						<div class="field-row">
							<label class="field"><input type="email" name="email" placeholder="Email" autocomplete="email"></label>
						</div>
						<div class="field-row">
							<label class="field"><input type="tel" name="phone" placeholder="Τηλέφωνο" required autocomplete="tel"></label>
						</div>
						<div class="field-row">
							<label class="field"><textarea name="message" placeholder="Μήνυμα" required></textarea></label>
						</div>
						<label class="consent">
							<input type="checkbox" name="consent" value="1" required style="margin-top:3px">
							<span>Συμφωνώ να επικοινωνήσετε μαζί μου σχετικά με το αίτημά μου.</span>
						</label>
						<button type="submit" class="btn" style="width:100%; background: var(--primary); color: var(--white)">Αποστολή</button>
					</form>
				</div>

				<div class="info-cards reveal">
					<div class="info-card">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
						<div>
							<h3>Γερανοί και Μεταφορές Βιβιλάκης Εμμανουήλ</h3>
							<div>Πάροδος Μάνου Κατράκη, Φοινικιά, Ηράκλειο Κρήτης, 71500</div>
						</div>
					</div>
					<div class="info-card">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg>
						<div>
							<h3>Email</h3>
							<a href="mailto:info@vivilakiscranes.gr">info@vivilakiscranes.gr</a>
						</div>
					</div>
					<div class="info-card">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.68 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.32 1.85.55 2.81.68A2 2 0 0 1 22 16.92z"/></svg>
						<div>
							<h3>Τηλέφωνο</h3>
							<a href="tel:+306949776292">6949 776 292</a>
							<div style="margin-top:4px;font-size:.85rem">Δευτέρα–Σάββατο, 8:00–16:00</div>
						</div>
					</div>

					<!-- Click-to-load Google map.
					     The default state is a static image built by tools/build-map.py:
					     no JS, no WebGL, no cookies, no request to anyone. Pressing the
					     button swaps in Google's interactive embed, which is the map most
					     visitors here recognise. Because loading it is the visitor's own
					     deliberate act, it is consent — so the site still needs no
					     site-wide cookie banner, and anyone who never clicks is never
					     exposed to Google at all. The choice is deliberately NOT
					     remembered, so no cookie or storage is written either way. -->
					<div class="map-card">
						<div class="map-static" id="mapStatic">
							<img src="/map.webp" srcset="/map.webp 1x, /map@2x.webp 2x"
							     width="600" height="340" loading="lazy" decoding="async"
							     alt="Δορυφορική εικόνα με τη θέση μας στη Φοινικιά Ηρακλείου, Πάροδος Μάνου Κατράκη">
							<button type="button" class="map-load" id="mapLoad">Άνοιγμα διαδραστικού χάρτη</button>
						</div>
						<div class="map-links">
							<a class="btn btn-accent" href="https://www.google.com/maps/dir/?api=1&amp;destination=35.292582%2C25.111259" target="_blank" rel="noopener">Οδηγίες πρόσβασης</a>
							<a class="link-arrow" href="https://www.google.com/maps/search/?api=1&amp;query=35.292582%2C25.111259" target="_blank" rel="noopener">Άνοιγμα στο Google Maps →</a>
						</div>
						<p class="map-attrib">Δορυφορική εικόνα: © Esri, Maxar, Earthstar Geographics. Ο διαδραστικός χάρτης φορτώνεται από την Google μόνο εφόσον τον ζητήσετε και ενδέχεται να τοποθετήσει cookies.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
