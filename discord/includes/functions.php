<?php

/**
 * Random string generator. Not really cryptographically secure, but better than
 * rand(), mt_rand() or reading single bytes straight from /dev/urandom.
 *
 * @param int
 *   Length of the generated string
 * @param int
 *   Number of bytes of /dev/urandom input for one byte of output. Each byte
 *   will be XOR'ed together to increase the entropy of the output.
 */
function pseudorandom_bytes($len, $entropy_increase_factor = 1024)
{
	$fp = fopen('/dev/urandom', 'r');
	if (!$fp) {
		throw new \RuntimeException("Cannot open /dev/urandom");
	}
	
	$str = '';
	for ($i = 0; $i < $len; $i++) {
		$byte = 0;
		for ($j = 0; $j < $entropy_increase_factor; $j++) {
			$byte ^= ord(fread($fp, 1));
		}
		$str .= chr($byte);
	}
	
	fclose($fp);
	
	return $str;
}

/**
 * Humanize a time from an integer number of seconds to a phrase like
 * "X hours and Y minutes."
 *
 * @param int
 * @return string
 */
function calculate_time_left($time_remaining)
{
	$hours = intval(floor($time_remaining / 3600));
	$h_plural = $hours === 1 ? 'hour' : 'hours';
	$minutes = intval(floor(($time_remaining % 3600) / 60));
	$m_plural = $minutes === 1 ? 'minute' : 'minutes';
	$time_left = '';
	if ($hours > 0) {
		$time_left .= "$hours $h_plural";
	}
	if ($hours > 0 && $minutes > 0) {
		$time_left .= " and ";
	}
	if ($minutes > 0) {
		$time_left .= "$minutes $m_plural";
	}
	if (empty($time_left)) {
		$time_left .= "less than a minute";
	}
	
	return $time_left;
}

/**
 * Censor an IP address
 *
 * @param string
 * @return string
 */
function censor_ip_address($ip)
{
	if (strpos($ip, ':') !== false) {
		// IPv6
		$separator = ':';
	}
	else if (strpos($ip, '.') !== false) {
		// IPv4
		$separator = '.';
	}
	if (isset($separator)) {
		$ip = explode($separator, $ip);
		$dont_censor = array_slice($ip, 0, 2);
		$censor = array_slice($ip, 2, 6);
		$censor = preg_replace('/[a-f0-9]/', 'x', implode($separator, $censor));
		$ip = implode($separator, $dont_censor) . $separator . $censor;
	}
	return $ip;
}