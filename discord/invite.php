<?php
use RestCord\DiscordClient;

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
		$hours = floor($time_remaining / 3600);
		$h_plural = $hours === 1 ? '' : 's';
		$minutes = floor(($time_remaining % 3600) / 60);
		$m_plural = $minutes === 1 ? '' : 's';
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

$client = new DiscordClient(['token' => DISCORD_BOT_TOKEN]);
$channels = $client->guild->getGuildChannels(['guild.id' => DISCORD_GUILD_ID]);

foreach ($channels as $c) {
	if ($c->name === DISCORD_NOTIF_CHANNEL) {
		$notificationChannel = $c;
		break;
	}
}

if (!isset($notificationChannel)) {
	throw new \RuntimeException(
		"Cannot find notification channel in the selected guild"
	);
}

foreach ($channels as $c) {
	if ($c->name === DISCORD_INVITE_CHANNEL) {
		$inviteChannel = $c;
	}
}

$invite = $client->channel->createChannelInvite([
	'channel.id' => $inviteChannel->id,
	'max_age'    => 3600,
	'max_uses'   => 1,
	'temporary'  => true,
	'unique'     => true,
]);

$client->channel->createMessage([
	'channel.id' => $notificationChannel->id,
	'embed' => [
		'title'       => 'Inviting user',
		'description' => "Sending a new invitation",
		'fields'      => [
			['name' => 'Reddit username', 'value' => $_SESSION['reddit_account']['name']],
			['name' => 'Invite code', 'value' => $invite->code],
		],
		'color'       => 0x46A046,
	],
]);

log_invite($_SESSION['reddit_account']['name'], $_SERVER['REMOTE_ADDR'], $invite->code);

echo json_encode($invite->code);
session_destroy();