<?php

/**
 * Get a PDO singleton.
 *
 * @return \PDO
 */
function get_pdo()
{
	static $PDO;
	
	if (!$PDO) {
		$PDO = new \PDO(DATABASE_DSN);
	}
	
	return $PDO;
}

/**
 * Get all history entries matching this user, in the correct historical
 * window. Subtracts the Discord cooldown timer from the current time, and uses
 * that as the minimum invite time.
 *
 * @param string
 *   Reddit account username
 * @param string
 *   End user's IP address
 */
function check_history($reddit_username, $remote_addr)
{
	$PDO = get_pdo();
	
	$stmt = $PDO->prepare('SELECT * FROM invite_log WHERE invite_time > :start_time AND (reddit_username = :reddit_username OR ip_address = :remote_addr);');
	
	$stmt->execute([
		':start_time'      => time() - DISCORD_COOLDOWN_TIMER,
		':reddit_username' => $reddit_username,
		':remote_addr'     => $remote_addr,
	]);
	
	$rows = [];
	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
		$rows[] = $row;
	}
	
	return $rows;
}

/**
 * Write a log entry to the database following the creation of an invite code.
 * This is atomic; once this function returns, the invite has been logged and
 * must be issued to the user, as it is the only code they will be able to
 * obtain until the cooldown timer expires.
 * 
 * @param string
 *   Reddit account username
 * @param string
 *   End user's IP address
 * @param string
 *   Discord invite code
 */
function log_invite($reddit_username, $remote_addr, $invite_code)
{
	$PDO = get_pdo();
	$PDO->beginTransaction();
	
	$stmt = $PDO->prepare('INSERT INTO invite_log (reddit_username, ip_address, invite_code, invite_time) VALUES (:reddit_username, :remote_addr, :invite_code, :invite_time);');
	
	$stmt->execute([
		':reddit_username' => $reddit_username,
		':remote_addr'     => $remote_addr,
		':invite_code'     => $invite_code,
		':invite_time'     => time(),
	]);
	
	$PDO->commit();
}

/**
 * Write a lot entry to the database following the rejection of an invitation.
 *
 * @param string
 *   Reddit username
 * @param string
 *   End user's IP address
 * @param string
 *   The reason the invitation request was rejected
 */
function log_rejection($reddit_username, $remote_addr, $reason)
{
	$PDO = get_pdo();
	$PDO->beginTransaction();
	
	$stmt = $PDO->prepare('INSERT INTO rejections (reddit_username, ip_address, event_time, reason) ' .
			'  VALUES (:reddit_username, :ip_address, :event_time, :reason);');
	
	$stmt->execute([
		':reddit_username' => $reddit_username,
		':ip_address'      => $remote_addr,
		':event_time'      => time(),
		':reason'          => $reason,
	]);
	
	$PDO->commit();
}