<?php
/**
 * Gallery.
 *
 * Same single-source rule as the $faq array in index.php: the manifest is the
 * only definition of what is on this page, and it is rendered twice — once as
 * the visible grid, once as ImageObject schema — so the two cannot drift.
 *
 * The manifest is generated; edit tools/gallery-alt.json and re-run
 * tools/build-gallery.py instead.
 */

$gallery = require __DIR__ . '/media/gallery/manifest.php';

$base             = 'https://vivilakiscranes.gr';
$page_title       = 'Γκαλερί — Φωτογραφίες από ανυψώσεις και μεταφορές | Βιβιλάκης Εμμανουήλ';
$page_description = 'Φωτογραφίες από εργασίες μας στην Κρήτη: ανυψώσεις με γερανό, μεταφορές σκαφών, εργοτάξια, κοντέινερ, μπομπίνες καλωδίου και εργασίες με καλαθοφόρο.';
$canonical        = $base . '/gallery';
$og_image         = $base . '/media/gallery/' . $gallery[0]['file'] . '.webp';

$collection = [
	'@context' => 'https://schema.org',
	'@type' => 'CollectionPage',
	'@id' => $canonical . '#page',
	'name' => 'Γκαλερί',
	'description' => $page_description,
	'url' => $canonical,
	'inLanguage' => 'el',
	'about' => ['@id' => $base . '/#business'],
	'hasPart' => array_map(fn($g) => [
		'@type' => 'ImageObject',
		'contentUrl' => $base . '/media/gallery/' . $g['file'] . '.webp',
		'thumbnailUrl' => $base . '/media/gallery/' . $g['file'] . '-thumb.webp',
		'width' => $g['w'],
		'height' => $g['h'],
		'name' => $g['alt'],
		'description' => $g['alt'],
		'creditText' => 'Βιβιλάκης Εμμανουήλ Γερανοί και Μεταφορές',
	], $gallery),
];

$breadcrumbs = [
	'@context' => 'https://schema.org',
	'@type' => 'BreadcrumbList',
	'itemListElement' => [
		['@type' => 'ListItem', 'position' => 1, 'name' => 'Αρχική', 'item' => $base . '/'],
		['@type' => 'ListItem', 'position' => 2, 'name' => 'Γκαλερί', 'item' => $canonical],
	],
];

$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
$json_ld = '';
foreach ([$collection, $breadcrumbs] as $block) {
	$json_ld .= "\t<script type=\"application/ld+json\">\n" . json_encode($block, $flags) . "\n\t</script>\n";
}

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<main id="main">

	<section class="section page-head">
		<div class="container">
			<nav class="breadcrumb" aria-label="Διαδρομή πλοήγησης">
				<a href="/">Αρχική</a> <span aria-hidden="true">›</span> <span aria-current="page">Γκαλερί</span>
			</nav>
			<div class="section-head">
				<h1>Γκαλερί</h1>
				<p>Φωτογραφίες από πραγματικές εργασίες σε όλη την Κρήτη — ανυψώσεις με γερανό, μεταφορές σκαφών και βαρέων φορτίων, εργοτάξια και εργασίες με καλαθοφόρο.</p>
			</div>
		</div>
	</section>

	<section class="section" style="padding-top:0">
		<div class="container">
			<ul class="gallery-grid" id="galleryGrid">
<?php foreach ($gallery as $i => $g): $f = '/media/gallery/' . $g['file']; ?>
				<li>
					<a class="gallery-item" href="<?= e($f) ?>.webp"
					   data-index="<?= $i ?>" data-full="<?= e($f) ?>.webp"
					   data-alt="<?= e($g['alt']) ?>"
					   data-w="<?= $g['w'] ?>" data-h="<?= $g['h'] ?>">
						<img src="<?= e($f) ?>-thumb.webp"
						     srcset="<?= e($f) ?>-thumb.webp 400w, <?= e($f) ?>-800.webp 800w"
						     sizes="(min-width:1024px) 33vw, (min-width:680px) 50vw, 100vw"
						     width="<?= $g['w'] ?>" height="<?= $g['h'] ?>"
						     loading="lazy" decoding="async"
						     alt="<?= e($g['alt']) ?>">
					</a>
				</li>
<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="section" style="background:var(--surface)">
		<div class="container" style="text-align:center">
			<div class="section-head">
				<h2>Χρειάζεστε γερανό για τη δική σας εργασία;</h2>
				<p>Πείτε μας τι θέλετε να ανυψώσετε ή να μεταφέρετε και θα σας προτείνουμε τον κατάλληλο εξοπλισμό.</p>
			</div>
			<div class="hero-ctas" style="justify-content:center;margin-top:0">
				<a class="btn btn-accent" href="/#contact">Ζητήστε προσφορά</a>
				<a class="link-arrow" href="tel:+306949776292">Καλέστε: 6949 776 292 →</a>
			</div>
		</div>
	</section>

</main>

<dialog class="lightbox" id="lightbox" aria-label="Προβολή φωτογραφίας">
	<button type="button" class="lb-btn lb-close" data-lb="close" aria-label="Κλείσιμο">&times;</button>
	<button type="button" class="lb-btn lb-prev" data-lb="prev" aria-label="Προηγούμενη φωτογραφία">&lsaquo;</button>
	<figure class="lb-figure">
		<img class="lb-img" id="lbImg" alt="">
		<figcaption class="lb-caption" id="lbCaption"></figcaption>
	</figure>
	<button type="button" class="lb-btn lb-next" data-lb="next" aria-label="Επόμενη φωτογραφία">&rsaquo;</button>
</dialog>

<script>
	(function(){
		var dlg = document.getElementById('lightbox');
		var grid = document.getElementById('galleryGrid');
		// No <dialog> support means the links still work: they open the full image.
		if(!dlg || !grid || typeof dlg.showModal !== 'function') return;

		var items = [].slice.call(grid.querySelectorAll('.gallery-item'));
		var img = document.getElementById('lbImg');
		var caption = document.getElementById('lbCaption');
		var current = 0;

		function show(i){
			current = (i + items.length) % items.length;
			var el = items[current];
			img.src = el.dataset.full;
			img.alt = el.dataset.alt;
			img.width = el.dataset.w;
			img.height = el.dataset.h;
			caption.textContent = el.dataset.alt;
		}

		grid.addEventListener('click', function(ev){
			var link = ev.target.closest('.gallery-item');
			if(!link || ev.metaKey || ev.ctrlKey || ev.shiftKey) return;
			ev.preventDefault();
			show(parseInt(link.dataset.index, 10));
			dlg.showModal();
		});

		dlg.addEventListener('click', function(ev){
			var action = ev.target.dataset ? ev.target.dataset.lb : null;
			if(action === 'close') dlg.close();
			else if(action === 'prev') show(current - 1);
			else if(action === 'next') show(current + 1);
			// Clicking the backdrop: the target is the dialog itself, not its contents.
			else if(ev.target === dlg) dlg.close();
		});

		// Esc is handled by <dialog> natively; arrows are not.
		dlg.addEventListener('keydown', function(ev){
			if(ev.key === 'ArrowLeft'){ ev.preventDefault(); show(current - 1); }
			else if(ev.key === 'ArrowRight'){ ev.preventDefault(); show(current + 1); }
		});

		// Return focus to the thumbnail that opened the lightbox.
		dlg.addEventListener('close', function(){
			if(items[current]) items[current].focus();
			img.removeAttribute('src');
		});
	})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
