<?php

// Reddit API client.

// Constants. Don't change these unless you *really* know what you're doing.
define('REDDIT_USER_AGENT', 'Mozilla/5.0 (compatible; /r/' . REDDIT_SUB_NAME . ' invite app 1.0)');
define('REDDIT_GENERAL_PREFIX', 'https://oauth.reddit.com/');
define('REDDIT_OAUTH_PREFIX', 'https://www.reddit.com/api/v1/');
define('REDDIT_API_PREFIX', 'https://oauth.reddit.com/api/v1/');

/**
 * Generate the redirect URI for the reddit oauth request. A half-hearted
 * attempt is made to detect HTTPS.
 *
 * @return string
 */
function get_reddit_redirect_uri()
{
	$protocol = !empty($_SERVER['HTTPS']) || $_SERVER['SERVER_PORT'] === 443 ? 'https' : 'http';
	$host = $_SERVER['SERVER_NAME'];
	return "$protocol://$host/discord/join";
}

/**
 * Generate a URL to the Reddit authorization page. The user should be
 * redirected with a 302 Found to this URL in order to authorize the
 * application.
 *
 * @return string
 */
function get_reddit_oauth_url()
{
	$state = sprintf("%s,%s", time(), hash_hmac('sha256', strval(time()), REDDIT_OAUTH_CLIENT_SECRET));
	return sprintf("https://www.reddit.com/api/v1/authorize?client_id=%s&response_type=code&state=%s&redirect_uri=%s&duration=temporary&scope=identity,read,mysubreddits",
		REDDIT_OAUTH_CLIENT_ID,
		$state,
		get_reddit_redirect_uri()
	);
}

/**
 * Given an authorization code (comes back from three-legged authorization flow)
 * reach out to reddit for a bearer token, which can be used to make API calls.
 *
 * @param string
 *   Authorization code, usually from $_GET['code']
 * @return array
 *   OAuth token structure
 * @throws RuntimeException
 *   Throws a RuntimeException if the request fails for any reason
 */
function get_reddit_access_token($authorization_code)
{
	$ch = curl_init();
	
	$parameters = [
		'grant_type' => 'authorization_code',
		'code' => $authorization_code,
		'redirect_uri' => get_reddit_redirect_uri(),
	];
	
	curl_setopt($ch, CURLOPT_URL, REDDIT_OAUTH_PREFIX . 'access_token');
	curl_setopt($ch, CURLOPT_USERAGENT, REDDIT_USER_AGENT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
	curl_setopt($ch, CURLOPT_USERPWD, sprintf("%s:%s", REDDIT_OAUTH_CLIENT_ID, REDDIT_OAUTH_CLIENT_SECRET));
	
	$response = curl_exec($ch);
	if (curl_errno($ch) === 0 && ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) === 200) {
		$data = json_decode($response, true);
		if (isset($data['access_token'])) {
			$data['expires'] = time() + $data['expires_in'];
			return $data;
		}
		
		throw new \RuntimeException("Request succeeded but no access_token in response: $response");
	}
	
	throw new \RuntimeException("Request for access token failed with status $status.\nResponse: $response");
}

/**
 * Execute a request to Reddit's API
 *
 * @param string
 *   Bearer token
 * @param string
 *   URI, the part after "/api/v1/"
 * @param array
 *   Optional payload. If unspecified, the request will be made using the GET
 *   method. Otherwise, will be sent as URL-encoded POST.
 * @return mixed
 *   JSON-decoded response
 * @throws RuntimeException
 *   Throws a RuntimeException if the request fails for any reason
 */
function reddit_api_request($bearer_token, $uri, $data = null)
{
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, REDDIT_API_PREFIX . $uri);
	curl_setopt($ch, CURLOPT_USERAGENT, REDDIT_USER_AGENT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $bearer_token"]);
	if (is_array($data)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	}
	
	$response = curl_exec($ch);
	if (curl_errno($ch) === 0 && ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) === 200) {
		$data = json_decode($response, true);
		if (json_last_error() === 0) {
			return $data;
		}
	}
	
	throw new \RuntimeException("Reddit API request failed with status $status.\nResponse: $response");
}

/**
 * Execute a request to Reddit's OAuth endpoint.
 *
 * @param string
 *   Bearer token
 * @param string
 *   URI, the part after "https://oauth.reddit.com/"
 * @param array
 *   Optional payload. If unspecified, the request will be made using the GET
 *   method. Otherwise, will be sent as URL-encoded POST.
 * @return mixed
 *   JSON-decoded response
 * @throws RuntimeException
 *   Throws a RuntimeException if the request fails for any reason
 */
function reddit_json_request($bearer_token, $uri, $data = null)
{
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url = REDDIT_GENERAL_PREFIX . $uri);
	curl_setopt($ch, CURLOPT_USERAGENT, REDDIT_USER_AGENT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $bearer_token"]);
	if (is_array($data)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	}
	
	$response = curl_exec($ch);
	if (curl_errno($ch) === 0 && ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) === 200) {
		$data = json_decode($response, true);
		if (json_last_error() === 0) {
			return $data;
		}
	}
	
	throw new \RuntimeException("Reddit API request failed with status $status.\nResponse: $response");
}

/**
 * Get information about the currently logged-in Reddit user.
 * @param string
 *   Bearer token
 * @return mixed
 *   JSON-decoded response
 * @throws RuntimeException
 *   Throws a RuntimeException if the request fails for any reason
 */
function get_reddit_account_info($bearer_token)
{
	return reddit_api_request($bearer_token, 'me');
}

/**
 * Get the user's karma in a subreddit.
 *
 * @param string
 *   Access token
 * @param string
 *   Subreddit name
 * @return array
 *   Associative array containing keys "comment_karma" and "link_karma"
 */
function get_subreddit_karma(string $access_token, string $subreddit)
{
	$karma = reddit_api_request($access_token, 'me/karma');
	if ($karma['kind'] !== 'KarmaList') {
		throw new \RuntimeException(
			"get_subreddit_karma(): expected KarmaList, got {$karma['kind']}"
		);
	}
	
	foreach ($karma['data'] as $entry) {
		if ($entry['sr'] === $subreddit) {
			return [
				'link_karma' => $entry['link_karma'],
				'comment_karma' => $entry['comment_karma'],
			];
		}
	}
	
	return [
		'link_karma' => 0,
		'comment_karma' => 0,
	];
}

/**
 * Return true if the user has mod rights
 *
 * @return bool
 */
function can_mod(): bool
{
	return session_status() === PHP_SESSION_ACTIVE &&
		array_key_exists('can_mod', $_SESSION) &&
		$_SESSION['can_mod'] === true;
}