<?php

/**
 * Validate the recaptcha form response. Pulls in the response from POST.
 *
 * @return array
 *   JSON decoded data from Google API
 */
function recaptcha_validate()
{
	$ch = curl_init();
	
	$parameters = http_build_query([
			'secret'   => RECAPTCHA_SECRET_KEY,
			'response' => $_POST['g-recaptcha-response'],
			'remoteip' => $_SERVER['REMOTE_ADDR'],
	]);
	
	curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
	curl_setopt($ch, CURLOPT_USERAGENT, REDDIT_USER_AGENT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$response = curl_exec($ch);
	if (curl_errno($ch) === 0 && ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) === 200) {
		$data = json_decode($response, true);
		if (json_last_error() === 0) {
			return $data;
		}
	}
	
	throw new \RuntimeException("Recaptcha API request failed with status $status.\nResponse: $response");
}