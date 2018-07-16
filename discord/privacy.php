<?php
require('includes/common.php');
$title = 'Get an invite to the /r/' . REDDIT_SUB_NAME . ' Discord!';
require('includes/header.php');

?>

<div class="container">
	<h1>Privacy Policy</h1>
	
	<p>
		Our subreddit uses this page to restrict access to our Discord server to
		established Reddit accounts. In order to provide the necessary
		functionality, we need to keep track of the following information:
	</p>
	
	<ul>
		<li>Your Reddit username</li>
		<li>When your Reddit account was created</li>
		<li>The timestamp of your last invite request</li>
		<li>Your IP address</li>
	</ul>
	
	<p>
		To limit abuse, invitations are restricted to one per IP address
		<strong>and</strong> per Reddit username, <?php echo DISCORD_COOLDOWN_TIMER_STR ?>.
	</p>
</div>


<?php

require('includes/footer.php');
