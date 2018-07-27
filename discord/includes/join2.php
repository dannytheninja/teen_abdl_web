<?php

// This is the second (and more complicated) half of the join flow. It's
// included from the top-level join.php after the user comes back from the
// reddit oauth page and we have a bearer token in hand.

/**
 * Show a message to the user indicating that they can't join the server right
 * now.
 *
 * @param string
 *   Message to include as the reason. Independent clause with no initial caps
 *   or trailing period. Example: "you are a big poopy pants"
 *
 * @return null
 *   Stops execution with exit()
 */
function cant_join($message)
{
	$title = 'Unable to join';
	require(ROOT . '/includes/header.php');

	?>
	<div class="container">
		<h1>Whoa there!</h1>
		<p>Unfortunately, we can't allow you to join the Discord server right
			now, because <?php echo htmlspecialchars($message); ?>. Sorry!</p>
		<p>If you think this is a mistake, you can
			<a href="https://reddit.com/message/compose?to=/r/<?php echo REDDIT_SUB_NAME; ?>">message the mods</a>.
		</p>
	</div>
	<?php
	
	require(ROOT . '/includes/footer.php');
	
	exit;
}

$reddit_userinfo = $_SESSION['reddit_account'];

if ($reddit_userinfo['is_suspended']) {
	cant_join('your Reddit account is suspended');
}

if (!$reddit_userinfo['has_verified_email'] && !$reddit_userinfo['verified']) {
	cant_join('your Reddit account doesn\'t have a verified email address on it');
}

if ($reddit_userinfo['created_utc'] > (time() - REDDIT_ACCOUNT_MIN_AGE)) {
	cant_join('your Reddit account is less than ' . REDDIT_ACCOUNT_MIN_AGE_STR . ' old');
}

$sub_settings = reddit_json_request($access_token, sprintf('r/%s/about.json', REDDIT_SUB_NAME));

if ($sub_settings['data']['user_is_banned']) {
	cant_join('you are banned from /r/' . REDDIT_SUB_NAME);
}

$history = check_history($_SESSION['reddit_account']['name'], $_SERVER['REMOTE_ADDR']);
foreach ($history as $entry) {
	if ($entry['invite_time'] > (time() - DISCORD_COOLDOWN_TIMER)) {
		$time_remaining = ($entry['invite_time'] + DISCORD_COOLDOWN_TIMER) - time();
		$time_left = calculate_time_left($time_remaining);
		cant_join(
			"your Reddit account or IP address last requested an invite too " .
			"recently. You can request another invite in $time_left."
		);
	}
}

$_SESSION['join_ok'] = true;
$_SESSION['xsrf_token'] = bin2hex(pseudorandom_bytes(32));

require(ROOT . '/includes/header.php');

?>
<div class="container">
	<h1>Thanks, <?php echo htmlspecialchars($reddit_userinfo['name']); ?>! You're good to go!</h1>
	<p>Please confirm for us that you're not a robot below, then click the button to join our Discord server.</p>
	
	<h3>After you join</h3>
	<p><strong>You must go to the <tt>#bot</tt> channel and set your age.</strong>
		You must type <tt>!adult</tt> if you are 18 or older, or <tt>!teen</tt>
		if you are 17 or younger. This must match your real-life, physical age,
		not any role-playing identity or persona.
		</p>
	<p>If you're an adult, you are welcome on our server, but we ask that you
		follow the <a href="https://www.reddit.com/r/Teen_ABDL/wiki/adults_roe">rules
		of engagement</a>. These guidelines set a standard for appropriately
		and safely interacting with teens, and are intended to prevent abuse.
		</p>
	
	<form method="post" name="gimme-mah-invite" action="invite" class="text-center">
		<div
			class="g-recaptcha"
			data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"
			data-callback="recaptcha_completed"
			style="width: 304px; margin: 1em auto;"
		></div>
		<input type="hidden" name="xsrf_token" value="<?php echo $_SESSION['xsrf_token']; ?>" />
		<button class="btn btn-primary btn-join-discord">
			Let's go!
		</button>
	</form>
</div>

<script type="text/javascript">
$(function()
	{
		var $form = $('form[name="gimme-mah-invite"]');
		$form.find('.btn-join-discord').hide();
		
		$form.submit(function()
			{
				var payload = $form.serialize();
				
				$.ajax({
					url:    'invite',
					data:   payload,
					dataType: 'json',
					method: 'POST',
					success: function(response)
						{
							console.debug(response);
							var invite_url = 'https://discord.gg/' + response;
							
							var $captcha = $form.find('.g-recaptcha');
							$captcha.remove();
							
							var $btn = $form.find('.btn-join-discord');
							$btn
								.removeClass('btn-primary')
								.addClass('btn-danger')
								.text('Join now!')
								.click(function()
									{
										window.location.href = invite_url;
										return false;
									});
						},
					error: function(jqXHR)
						{
							var $captcha = $form.find('.g-recaptcha');
							$captcha.remove();
							
							var error_msg;
							if (jqXHR.responseJSON) {
								error_msg = jqXHR.responseJSON.message;
							}
							else {
								error_msg = jqXHR.responseText;
							}
							
							$form.find('.btn-join-discord').hide();
							$form.append(
								$('<div />')
									.text(error_msg)
									.addClass('alert')
									.addClass('alert-danger')
							);
						}
				});
				
				return false;
			});
	});

function recaptcha_completed()
{
	var $form = $('form[name="gimme-mah-invite"]');
	$form.find('.btn-join-discord').show();
}
</script>
<?php

require(ROOT . '/includes/footer.php');