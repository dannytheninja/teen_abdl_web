<?php
require('includes/common.php');
$title = 'Get an invite to the /r/' . REDDIT_SUB_NAME . ' Discord!';
require('includes/header.php');

?>

<div class="container">
	<p>Hi! We're excited to welcome you to our Discord server. Joining the
		server requires a Reddit account at least a week old. Once your Reddit
		account is verified, you'll receive a single-use invite link. Caution:
		you can only request a Discord invite link
		<?php echo DISCORD_COOLDOWN_TIMER_STR ?>.</p>
	
	<p><a href="privacy">Read the privacy policy</a></p>
	
	<p class="term">
		<label>
			<input type="checkbox" />
			I have read and agreed to the <a href="https://www.reddit.com/r/<?php echo REDDIT_SUB_NAME; ?>/wiki/rules">/r/<?php echo REDDIT_SUB_NAME; ?> rules</a>
		</label>
	</p>
	
	<p class="term">
		<label>
			<input type="checkbox" />
			I understand that I can only request an invite once every 24 hours.
			If I lose my code after it's issued, I will need to wait.
		</label>
	</p>
	
	<p class="term">
		<label>
			<input type="checkbox" />
			I understand that invites are single-use. I will not give my code
			away to someone else unless I've received permission from the mods.
		</label>
	</p>
	
	<p class="term">
		<label>
			<input type="checkbox" />
			I understand that my Reddit account will become linked to my Discord
			account, and that being banned from one will result in me being
			banned from the other as well.
		</label>
	</p>
	
	<div class="term">
		<p>
			<a href="join" class="btn btn-primary">Verify my Reddit Account</a>
		</p>
	</div>
</div>

<script type="text/javascript">
	$(function()
		{
			$('.term:not(:first)').hide();
			$('.term :checkbox').change(function()
				{
					var parent = $(this).parents('.term:first');
					if ($(this).is(':checked')) {
						parent.nextAll('.term:first').show();
					}
					else {
						parent.nextAll('.term').hide()
							.find(':checkbox').prop('checked', false);
					}
				});
		});
</script>

<?php

require('includes/footer.php');
