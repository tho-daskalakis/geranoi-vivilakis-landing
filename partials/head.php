<?php
/**
 * Shared document head.
 *
 * Set before including:
 *   $page_title, $page_description, $canonical  (required)
 *   $og_image, $og_type, $json_ld, $robots      (optional)
 */

if (!function_exists('e')) {
	function e(string $s): string {
		return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

$site_name   = 'Βιβιλάκης Εμμανουήλ – Γερανοί & Μεταφορές';
$base        = 'https://vivilakiscranes.gr';
$page_title  = $page_title ?? 'Γερανοί και Μεταφορές Ηράκλειο Κρήτης | Βιβιλάκης Εμμανουήλ';
$canonical   = $canonical ?? $base . '/';
$og_image    = $og_image ?? $base . '/og-cover.jpg';
$og_type     = $og_type ?? 'website';
$robots      = $robots ?? 'index, follow, max-image-preview:large';
$page_description = $page_description ?? '';
?>
<!DOCTYPE html>
<html lang="el">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= e($page_title) ?></title>

	<meta name="description" content="<?= e($page_description) ?>">
	<meta name="robots" content="<?= e($robots) ?>">
	<link rel="canonical" href="<?= e($canonical) ?>">
	<meta name="theme-color" content="#ffffff">

	<link rel="icon" type="image/svg+xml" href="/logo.svg">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
	<link rel="icon" type="image/png" sizes="48x48" href="/favicon-48.png">
	<link rel="apple-touch-icon" href="/apple-touch-icon.png">

	<!--
		HREFLANG: uncomment the "en" line only once https://vivilakiscranes.gr/en/ is actually published,
		otherwise Search Console will flag it as a broken alternate. Uncomment the .lang-btn in
		partials/header.php at the same time — one without the other gives a 404 or a broken alternate.
		<link rel="alternate" hreflang="en" href="https://vivilakiscranes.gr/en/">
	-->
	<link rel="alternate" hreflang="el" href="<?= e($base) ?>/">
	<link rel="alternate" hreflang="x-default" href="<?= e($base) ?>/">

	<meta property="og:type" content="<?= e($og_type) ?>">
	<meta property="og:site_name" content="<?= e($site_name) ?>">
	<meta property="og:title" content="<?= e($page_title) ?>">
	<meta property="og:description" content="<?= e($page_description) ?>">
	<meta property="og:image" content="<?= e($og_image) ?>">
	<meta property="og:locale" content="el_GR">
	<meta property="og:url" content="<?= e($canonical) ?>">
	<meta name="twitter:card" content="summary_large_image">
<?php if (!empty($json_ld)): ?>

<?= $json_ld ?>
<?php endif; ?>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">

	<link rel="stylesheet" href="/styles.css">
</head>
<body>
