<?php
/**
 * Optional overrides for send.php. Copy to config.php only if you need one.
 *
 * THE SITE SENDS MAIL WITHOUT THIS FILE. The web server and the mailbox
 * info@vivilakiscranes.gr are the same machine, so send.php hands the message
 * straight to the local mail queue — no socket, no DNS lookup, no password.
 * SMTP credentials only ever exist to persuade a mail server to relay for a
 * machine it does not know, and this is not one.
 *
 * Every key below is optional; whatever you omit keeps the default from
 * $defaults in send.php. Delete the lines you are not changing.
 */

return [
	// ---------------------------------------------------------------------
	// Transport. Default 'mail' hands off to the local queue via PHP's mail().
	// Fall down this ladder only if the error log says the rung above failed:
	//   'mail'      PHP mail() → the server's own queue.          (default)
	//   'sendmail'  the /usr/sbin/sendmail binary, if mail() is
	//               listed in disable_functions.
	//   'smtp'      a real SMTP conversation. Needed only if the
	//               mailbox moves to a different server.
	// ---------------------------------------------------------------------
	// 'transport' => 'mail',

	// ---------------------------------------------------------------------
	// Addresses. from_email must be a mailbox on this domain, or the message
	// fails SPF/DMARC at any recipient outside this server and lands in spam.
	// ---------------------------------------------------------------------
	// 'from_email' => 'info@vivilakiscranes.gr',
	// 'from_name'  => 'vivilakiscranes.gr',
	// 'to_email'   => 'info@vivilakiscranes.gr',
	// 'to_name'    => 'Βιβιλάκης Εμμανουήλ',

	// ---------------------------------------------------------------------
	// Read only when transport is 'smtp'.
	//
	// Against the local mail server, leave smtp_user empty: send.php then
	// skips authentication, which is what localhost is trusted to do.
	//
	//   'transport'   => 'smtp',
	//   'smtp_host'   => 'localhost',
	//   'smtp_port'   => 25,
	//   'smtp_secure' => '',
	//   'smtp_user'   => '',
	//
	// Against a remote mailbox, the username is the full address, not just
	// the local part. Port 465 uses implicit TLS ('smtps'), port 587 uses
	// STARTTLS ('tls'); change smtp_port and smtp_secure together. Note that
	// outbound SMTP ports are commonly filtered on shared hosts — that is
	// exactly what made this form hang before it used the local queue.
	//
	//   'transport'   => 'smtp',
	//   'smtp_host'   => 'mail.example.gr',
	//   'smtp_port'   => 465,
	//   'smtp_secure' => 'smtps',
	//   'smtp_user'   => 'info@example.gr',
	//   'smtp_pass'   => '…',
	// ---------------------------------------------------------------------

	// Prints the SMTP conversation into the response, which breaks the
	// redirect. Debugging only, and only with transport 'smtp'.
	// 'debug' => false,
];
