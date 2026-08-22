<?php
/**
 * Contact form handler.
 *
 * Reached as POST /send (never /send.php): the .htaccess redirect from the
 * .php form to the extensionless URL is a 301, and browsers turn a redirected
 * POST into a GET and drop the body. Posting straight to /send avoids that.
 *
 * Post/Redirect/Get: this script never renders. It redirects back to
 * /#contact with a status flag, so a refresh cannot resend the enquiry.
 *
 * The site sets no cookies and this script starts no session — a contact form
 * with no authenticated state has no meaningful CSRF exposure, so a honeypot
 * and an IP rate limit do the work instead of a token.
 */

declare(strict_types=1);

require __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

const RATE_WINDOW  = 3600;   // seconds
const RATE_MAX     = 5;      // submissions per IP per window
const MAX_FIELD    = 200;    // chars for single-line fields
const MAX_MESSAGE  = 5000;

/** Redirect back to the form with a status flag and stop. */
function finish(string $status): never
{
	header('Location: /?sent=' . rawurlencode($status) . '#contact', true, 303);
	exit;
}

/**
 * Collapse a submitted value to a single safe line.
 *
 * Strips CR and LF outright: any of these values can reach a mail header, and
 * a newline there is a header-injection vector.
 */
function field(string $key, int $max = MAX_FIELD): string
{
	$raw = (string) ($_POST[$key] ?? '');
	$raw = str_replace(["\r", "\n", "\0"], ' ', $raw);
	$raw = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
	return mb_substr($raw, 0, $max, 'UTF-8');
}

/** Simple per-IP file counter. Not bulletproof, but it stops naive floods. */
function rate_limited(): bool
{
	$dir = __DIR__ . '/.ratelimit';
	if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
		return false;   // cannot track; fail open rather than block real enquiries
	}
	$file = $dir . '/' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'cli') . '.json';
	$now  = time();
	$hits = [];
	if (is_readable($file)) {
		$hits = json_decode((string) file_get_contents($file), true) ?: [];
	}
	$hits = array_values(array_filter($hits, fn($t) => is_int($t) && $t > $now - RATE_WINDOW));
	if (count($hits) >= RATE_MAX) {
		return true;
	}
	$hits[] = $now;
	@file_put_contents($file, json_encode($hits), LOCK_EX);
	return false;
}

// ---------------------------------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	finish('error');
}

// Honeypot: a real browser leaves this empty because it is hidden from layout.
if (field('website') !== '') {
	finish('ok');   // report success to the bot; send nothing
}

$name    = field('name');
$company = field('company');
$email   = field('email');
$phone   = field('phone');
$message = field('message', MAX_MESSAGE);
$consent = isset($_POST['consent']);

if ($name === '' || $message === '') {
	finish('invalid');
}
if (!$consent) {
	finish('consent');
}

// The form requires a phone; email is optional. At least one must be usable.
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	finish('invalid');
}
if ($phone === '' && $email === '') {
	finish('invalid');
}

if (rate_limited()) {
	finish('ratelimit');
}

/**
 * Defaults, overridden by config.php if it exists.
 *
 * The site runs on the same machine that hosts the mailbox, so the default
 * transport hands the message to the local mail queue: no socket, no DNS, no
 * credentials. SMTP auth is only ever needed to persuade a mail server to
 * relay for a *stranger*, which this script is not.
 *
 * config.php is optional. It exists to change the destination address, or to
 * point at a remote SMTP server if mail ever moves off this host.
 */
$defaults = [
	'transport'   => 'mail',             // 'mail' | 'sendmail' | 'smtp'

	'from_email'  => 'info@vivilakiscranes.gr',
	'from_name'   => 'vivilakiscranes.gr',
	'to_email'    => 'info@vivilakiscranes.gr',
	'to_name'     => 'Βιβιλάκης Εμμανουήλ',

	// Only read when transport is 'smtp'. An empty smtp_user means no auth,
	// which is what the local mail server expects from localhost.
	'smtp_host'   => 'localhost',
	'smtp_port'   => 25,
	'smtp_secure' => '',
	'smtp_user'   => '',
	'smtp_pass'   => '',

	'debug'       => false,
];
$config_file = __DIR__ . '/config.php';
$cfg = is_readable($config_file)
	? array_merge($defaults, (array) require $config_file)
	: $defaults;

$transport = (string) $cfg['transport'];

$mail = new PHPMailer(true);

try {
	if ($transport === 'smtp') {
		$mail->isSMTP();
		$mail->Host       = $cfg['smtp_host'];
		$mail->Port       = (int) $cfg['smtp_port'];
		$mail->SMTPAuth   = $cfg['smtp_user'] !== '';
		$mail->Username   = $cfg['smtp_user'];
		$mail->Password   = $cfg['smtp_pass'];
		$mail->SMTPSecure = $cfg['smtp_secure'];
		// Two separate clocks, and both need setting. Timeout bounds opening
		// the connection; Timelimit bounds *waiting for a reply*, defaults to
		// 300s and is doubled again during DATA. Left alone, a port that
		// accepts the connection and then says nothing — which is how this
		// host's filtered SMTP ports behave — parks the visitor's request for
		// ten minutes until the gateway gives up. set_time_limit() is no help
		// here: PHP's execution clock does not tick during blocked socket I/O.
		$mail->Timeout = 15;
		$mail->getSMTPInstance()->Timelimit = 15;
	} elseif ($transport === 'sendmail') {
		$mail->isSendmail();
	} else {
		$mail->isMail();
	}

	$mail->CharSet    = PHPMailer::CHARSET_UTF8;
	$mail->Encoding   = PHPMailer::ENCODING_BASE64;   // Greek text is not 7-bit safe
	if (!empty($cfg['debug'])) {
		$mail->SMTPDebug = 2;
	}

	// From must stay a mailbox on this domain so SPF/DMARC pass; the enquirer
	// goes in Reply-To so hitting reply in the inbox reaches them directly.
	// Sender is the envelope sender (-f): the mail server picks the DKIM key
	// from it. Irrelevant while delivery is local, but it is what keeps the
	// message out of the spam folder if enquiries are ever forwarded onward.
	$mail->setFrom($cfg['from_email'], $cfg['from_name']);
	$mail->Sender = $cfg['from_email'];
	$mail->addAddress($cfg['to_email'], $cfg['to_name']);
	if ($email !== '') {
		$mail->addReplyTo($email, $name);
	}

	// $name is attacker-controlled but has already had CR/LF stripped and been
	// length-capped; PHPMailer applies the RFC 2047 encoding the Greek needs.
	$mail->Subject = '[vivilakiscranes.gr] Νέο αίτημα από ' . $name;

	$rows = [
		'Όνομα'     => $name,
		'Εταιρεία'  => $company !== '' ? $company : '—',
		'Email'     => $email !== '' ? $email : '—',
		'Τηλέφωνο'  => $phone !== '' ? $phone : '—',
		'Συγκατάθεση' => 'Ναι — δόθηκε στη φόρμα στις ' . date('d/m/Y H:i'),
	];

	$text = '';
	foreach ($rows as $label => $value) {
		$text .= $label . ': ' . $value . "\n";
	}
	$text .= "\nΜήνυμα:\n" . $message . "\n";

	$html = '<table cellpadding="6" style="border-collapse:collapse;font-family:sans-serif;font-size:14px">';
	foreach ($rows as $label => $value) {
		$html .= '<tr><td style="border:1px solid #ddd"><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
			. '</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
	}
	$html .= '</table><p style="font-family:sans-serif;font-size:14px;white-space:pre-wrap">'
		. nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

	$mail->isHTML(true);
	$mail->Body    = $html;
	$mail->AltBody = $text;

	$mail->send();
	finish('ok');
} catch (MailException $e) {
	// Names the transport: the server panel's error log is the only channel
	// this script has, and "mail() is disabled" and "mailbox rejected" need
	// telling apart from it alone.
	error_log('send.php [' . $transport . ']: ' . $mail->ErrorInfo . ' | ' . $e->getMessage());
	finish('error');
}
