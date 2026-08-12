<?php
/**
 * Copy to config.php and fill in. config.php is gitignored — never commit it.
 *
 * pointer.gr is cPanel-based, so the mailbox created in cPanel is normally
 * reachable at mail.<domain>. Port 465 uses implicit TLS ('smtps'); port 587
 * uses STARTTLS ('tls'). If 465 is blocked outbound, try 587.
 *
 * The username is the full mailbox address, not just the local part.
 */

return [
	'smtp_host'   => 'mail.vivilakiscranes.gr',
	'smtp_port'   => 465,
	'smtp_secure' => 'smtps',            // 'smtps' for 465, 'tls' for 587
	'smtp_user'   => 'info@vivilakiscranes.gr',
	'smtp_pass'   => 'CHANGE-ME',

	// Envelope sender. Must be a mailbox on this domain or the message will
	// fail SPF/DMARC at the recipient and land in spam.
	'from_email'  => 'info@vivilakiscranes.gr',
	'from_name'   => 'vivilakiscranes.gr',

	// Where enquiries are delivered.
	'to_email'    => 'info@vivilakiscranes.gr',
	'to_name'     => 'Βιβιλάκης Εμμανουήλ',

	// Set true only while debugging; it prints the SMTP conversation.
	'debug'       => false,
];
