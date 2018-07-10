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
