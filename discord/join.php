<?php
require('includes/common.php');
$title = 'Get an invite to the /r/' . REDDIT_SUB_NAME . ' Discord!';

session_start();

if (isset($_SESSION['oauth_bearer_token']) &&
	$_SESSION['oauth_bearer_token']['expires'] > time() &&
	!empty($_SESSION['oauth_bearer_token']['access_token'])
) {
	$access_token = $_SESSION['oauth_bearer_token']['access_token'];
}
else if (isset($_GET['code'])) {
	// check state
	list($timestamp, $signature) = explode(',', $_GET['state']);
	if (hash_hmac('sha256', $timestamp, REDDIT_OAUTH_CLIENT_SECRET) !== $signature) {
		throw new \RuntimeException("Invalid state: bad signature");
	}
	if (abs(time() - intval($timestamp)) > 600) {
		throw new \RuntimeException("Invalid state: timestamp out of range");
	}
	$_SESSION['oauth_bearer_token'] = get_reddit_access_token($_GET['code']);
	$access_token = $_SESSION['oauth_bearer_token']['access_token'];
}


if (isset($_SESSION['reddit_account']) && !empty($access_token)) {
	require('includes/join2.php');
}
else if (isset($access_token)) {
	$reddit_user_info = get_reddit_account_info($access_token);
	$_SESSION['reddit_account'] = $reddit_user_info;
	session_write_close();
	
	header('Status: 302 Found');
	header('Location: ' . get_reddit_redirect_uri());
}
else {
	header('Status: 302 Found');
	header('Location: ' . get_reddit_oauth_url());
}