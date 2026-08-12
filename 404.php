<?php
http_response_code(404);

$page_title       = 'Η σελίδα δεν βρέθηκε | Βιβιλάκης Εμμανουήλ';
$page_description = 'Η σελίδα που ζητήσατε δεν υπάρχει.';
$canonical        = 'https://vivilakiscranes.gr/404';
$robots           = 'noindex, follow';

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<main id="main">
	<section class="section" style="padding-top:188px">
		<div class="container">
			<div class="section-head">
				<h1>Η σελίδα δεν βρέθηκε</h1>
				<p>Η σελίδα που ζητήσατε δεν υπάρχει ή έχει μετακινηθεί. Μπορείτε να επιστρέψετε στην αρχική ή να επικοινωνήσετε μαζί μας απευθείας.</p>
			</div>
			<div class="hero-ctas" style="margin-top:0">
				<a class="btn btn-accent" href="/">Αρχική σελίδα</a>
				<a class="link-arrow" href="tel:+306949776292">Καλέστε: 6949 776 292 →</a>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
