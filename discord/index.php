<?php
$title = 'Get an invite to the /r/' . REDDIT_SUB_NAME . ' Discord!';
require('includes/common.php');

require('includes/header.php');

?>

<div class="container">
	<p>Hi! We're excited to welcome you to our Discord server. Joining the
		server requires a Reddit account at least a week old. Once your Reddit
		account is verified, you'll receive a single-use invite link. Caution:
		you can only request a Discord invite link
		<?php echo DISCORD_COOLDOWN_TIMER_STR ?>.</p>
	
	<p><a href="privacy">Read the privacy policy</a></p>
	
	<p>
		<label>
			<input type="checkbox" id="agree-to-rules" />
			I have read and agreed to the <a href="https://www.reddit.com/r/<?php echo REDDIT_SUB_NAME; ?>/wiki/rules">/r/<?php echo REDDIT_SUB_NAME; ?> rules</a>
		</label>
	</p>
	
	<div class="agreed-to-the-rules" style="display: none;">
		<p>
			<a href="join" class="btn btn-primary">Verify my Reddit Account</a>
		</p>
	</div>
</div>

<script type="text/javascript">
	$(function()
		{
			$('#agree-to-rules').change(function()
				{
					var $p = $('.agreed-to-the-rules');
					$(this).prop('checked') ? $p.show() : $p.hide();
				}).trigger('change');
		});
</script>

<?php

require('includes/footer.php');
