<?php
require('includes/common.php');
$title = 'Moderator control panel';
session_start();

if (!can_mod()) {
	throw new \BadMethodCallException(
		"You aren't allowed to access this page"
	);
}

if (isset($_GET['query'])) {
	define('OUTPUT_JSON', 1);
	$table = $_GET['query'];

	$allowed_tables = ['invite_log', 'rejections'];

	if (!in_array($table, $allowed_tables, true)) {
		throw new \InvalidArgumentException('Not allowed to query that table');
	}

	$sort_column = [
		'invite_log' => 'invite_time',
		'rejections' => 'event_time',
	];

	$query = "SELECT * FROM $table";

	if (isset($_GET['marker']) && preg_match('/^[0-9]+$/', $_GET['marker'])) {
		$timestamp = $_GET['marker'];
		$query .= " WHERE {$sort_column[$table]} < $timestamp";
	}

	$query .= " ORDER BY {$sort_column[$table]} DESC LIMIT 10;";

	$pdo = get_pdo();
	$stmt = $pdo->prepare($query);
	$stmt->execute();
	$rows = [];
	while ($row = $stmt->fetch($pdo::FETCH_ASSOC)) {
		foreach ($row as $col => $val) {
			switch($col) {
				case 'ip_address':
					$row[$col] = censor_ip_address($val);
					break;
			}
		}
		$rows[] = $row;
	}

	header('Content-Type: application/json');
	echo json_encode($rows, JSON_PRETTY_PRINT);

	exit;
}
else if (isset($_GET['ignore'])) {
	$invite_id = intval($_GET['ignore']);

	$pdo = get_pdo();
	$stmt = $pdo->prepare('UPDATE invite_log SET ignore = 1 WHERE event_id = :event_id');
	$stmt->execute([':event_id' => $invite_id]);

	header('Content-Type: application/json');
	echo json_encode(true, JSON_PRETTY_PRINT);

	exit;
}
else if (isset($_GET['invite'])) {
    $redditUsername = $_GET['invite'];
    if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $redditUsername)) {
        throw new \RuntimeException(
            "Syntactically invalid reddit username"
        );
    }

    $invite = generate_discord_invite($redditUsername);

    log_invite($redditUsername, $_SERVER['REMOTE_ADDR'], $invite->code);

    header('Content-Type: application/json');
	echo json_encode($invite->code, JSON_PRETTY_PRINT);

	exit;
}

require('includes/header.php');

?>

<div class="container">
<h2>Admin create invite</h2>

<form id="frm-invite">
    <div class="form-group row">
        <label for="inviteUsername" class="form-label col-lg-3 col-sm-12 col-form-label">Reddit username:</label>
        <div class="controls col-lg-9 col-sm-12">
            <input class="form-control" type="text" name="invite" id="inviteUsername" />
        </div>
    </div>
    <div class="form-group row">
        <div class="controls col-lg-9 col-lg-offset-3 col-sm-12">
            <input type="submit" class="btn btn-primary" value="Create invitation" />
            <span id="invite-code-container"></span>
        </div>
    </div>
</form>

</div>

<div class="container-fluid">
<div class="row">
<div class="col-sm-6 col-xs-12">
<h2>Invites Issued</h2>
<table class="table table-bordered table-striped table-ajax" data-table="invite_log"></table>
</div>

<div class="col-sm-6 col-xs-12">
<h2>Rejections</h2>
<table class="table table-bordered table-striped table-ajax" data-table="rejections"></table>
</div>

</div>
</div>

<script type="text/javascript">
$(function()
	{
	    $('#frm-invite').on('submit', function()
	        {
	            let redditUsername = $('#inviteUsername').val();
	            do_invite(redditUsername);
	            return false;
	        });

		var baseurl = window.location.href;
		if (baseurl.indexOf('?') > -1) {
			baseurl = baseurl.substr(0, baseurl.indexOf('?'));
		}

		var marker_columns = {
			invite_log: 'invite_time',
			rejections: 'event_time',
		};

		$('.table-ajax').each(function()
			{
				var db_table = $(this).data('table'),
					$table = $(this).data('baseurl', baseurl);

				$.get(baseurl + '?query=' + db_table, function(result)
					{
						var thead = $('<thead />').appendTo($table);
						if (result.length < 1) {
							return;
						}

						for (var i in result[0]) {
							thead.append($('<th />').text(i));
						}

						var tbody = $('<tbody />').appendTo($table);
						draw_rows(tbody, result);
					});
			});

		$('.table-ajax').on('click', '.btn-ignore-invite', function()
			{
				var data = $(this).parents('tr:first').data('data');

				$(this).addClass('disabled');
				$.get(baseurl + '?ignore=' + data.event_id, function()
					{
						// do nothing
					});

				return false;
			});

		window.endless_scroll_debounce = false;
		$(window).bind('endless-scroll', function()
			{
				$('.table-ajax').each(function()
					{
						var $table = $(this);

						if ($table.data('at-end')) {
							return;
						}

						var last = $('tbody tr:last', this).data('data');
						if (!last) {
							return;
						}

						var db_table = $table.data('table');

						var url = baseurl +
									'?query=' + db_table +
									'&marker=' + last[marker_columns[db_table]]

						$.get(url, function(result) {
							draw_rows($table.find('tbody'), result);
						});
					});
			});

		window.scroll_timeout = null;
		$(window).bind('scroll', function()
			{
				if (window.scroll_timeout) {
					clearTimeout(window.scroll_timeout);
				}
				window.scroll_timeout = setTimeout(function()
					{
						$('body').trigger('scrollend');
					});
			});

		$('body').bind('scrollend wheel', function(ev)
			{
				if (window.scrollY + $(window).height() >= $(document).height() && !window.endless_scroll_debounce) {
					window.endless_scroll_debounce = true;
					setTimeout(function()
						{
							window.endless_scroll_debounce = false;
						}, 500);
					$(window).trigger('endless-scroll');
				}
			});

		setTimeout(function()
			{
				$('body').trigger('scrollend');
			}, 1000);
	});

async function do_invite(redditUsername)
{
    let result = await fetch('mod.php?invite=' + encodeURIComponent(redditUsername)),
        code = await result.json();

    $('#invite-code-container').empty()
        .append('Invite code copied to clipboard: ')
        .append($('<tt />').text(code));

    let codeElement = $('#invite-code-container').find('tt').get(0);

    let selection = window.getSelection(),
        range = document.createRange();

    range.selectNodeContents(codeElement);
    selection.removeAllRanges();
    selection.addRange(range);

    document.execCommand('copy');
}

function draw_rows($tbody, result)
{
	if (result.length == 0) {
		$tbody.parents('table').data('at-end', true);
		var ncols = $tbody.parents('table').find('th').length;

		$tbody.append(
			$('<td />')
				.attr('colspan', ncols)
				.addClass('text-muted text-center')
				.text('End of results')
		);
	}

	for (var i = 0; i < result.length; i++) {
		var row = $('<tr />').data('data', result[i]);
		for (var j in result[i]) {
			var raw_value = result[i][j],
				display_value = raw_value;
			switch(j) {
				case 'reddit_username':
					if (raw_value == '[none]') {
						break;
					}
					display_value = $('<span />')
						.append($('<a />')
							.attr('href', 'https://www.reddit.com/user/' + encodeURIComponent(raw_value))
							.text(raw_value)
						).html();
					break;
				case 'invite_time':
				case 'event_time':
					display_value = String(
						new Date(parseInt(raw_value) * 1000)
					).replace(/ \(.+\)$/, '');
					break;
				case 'ignore':
					display_value = $('<span />')
						.append($('<a />')
							.addClass('btn btn-sm btn-primary btn-ignore-invite' + (raw_value == 1 ? ' disabled' : ''))
							.attr('href', '#')
							.append(
								$('<i />').addClass('fas fa-ban')
							)
						).html();
					break;
				default:
					display_value = $('<span />').text(raw_value).html();
					break;
			}
			row.append($('<td />').data('raw-value', raw_value).html(display_value));
		}
		$tbody.append(row);
	}
}
</script>
<?php

require('includes/footer.php');
