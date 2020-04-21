<?php
require 'includes/common.php';
output_json();

session_start();
if (empty($_SESSION['join_ok'])) {
	throw new \BadMethodCallException("Session expired. Please restart the verification process.");
}

if (empty($_SESSION['reddit_account']['name'])) {
	throw new \LogicException("I forgot your Reddit username");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Status: 405 Method Not Allowed');
	throw new \BadMethodCallException("Expected POST");
}

if (empty($_SESSION['xsrf_token']) || $_POST['xsrf_token'] !== $_SESSION['xsrf_token']) {
	throw new \BadMethodCallException("Invalid XSRF token");
}

$history = check_history($_SESSION['reddit_account']['name'], $_SERVER['REMOTE_ADDR']);
foreach ($history as $entry) {
	if ($entry['invite_time'] > (time() - DISCORD_COOLDOWN_TIMER)) {
		$time_remaining = ($entry['invite_time'] + DISCORD_COOLDOWN_TIMER) - time();
		$time_left = calculate_time_left($time_remaining);
		throw new \BadMethodCallException(
			"Your Reddit account or IP address last requested an invite less " .
			"than 24 hours ago. You can request another invite in $hours " .
			"hour{$h_plural} and $minutes minute{$m_plural}."
		);
	}
}

$captcha = recaptcha_validate();
if (!$captcha['success']) {
	throw new \BadMethodCallException("You did not complete the reCAPTCHA challenge.");
}

$invite = generate_discord_invite($_SESSION['reddit_account']['name']);

log_invite($_SESSION['reddit_account']['name'], $_SERVER['REMOTE_ADDR'], $invite->code);

echo json_encode($invite->code);
session_destroy();