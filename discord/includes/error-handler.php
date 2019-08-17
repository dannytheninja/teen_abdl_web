<?php

/**
 * Handle exceptions in a graceful manner.
 */
set_exception_handler(function($exception)
	{
		header('Status: 500 Internal Server Error');
		
		$message = "[exception] " .
					get_class($exception) . ': ' .
					$exception->getMessage();

		log_rejection(
			$_SESSION['reddit_account']['name'] ?? '[none]',
			$_SERVER['REMOTE_ADDR'],
			$message
		);
		
		if (defined('OUTPUT_JSON')) {
			// Build a nice little JSON data structure
			$message = [
				'status' => 'error',
				'exception_class' => get_class($exception),
				'message' => $exception->getMessage(),
				'file' => substr($exception->getFile(), strlen(ROOT) + 1),
				'line' => $exception->getLine(),
			];
			
			echo json_encode($message, JSON_PRETTY_PRINT);
			exit;
		}
		else {
			// Send error as HTML
			$title = 'Error encountered';
			require ROOT . '/includes/header.php';
			
			echo '<div class="container">';
			echo '<h1>Error encountered!</h1>';
			printf("<h2><tt>%s</tt></h2>", get_class($exception));
			
			printf("<p>%s</p>", htmlspecialchars($exception->getMessage()));
			
			printf("<p>File: %s<br />Line: %d</p>", htmlspecialchars(substr($exception->getFile(), strlen(ROOT) + 1)), $exception->getLine());
			echo '</div>';
			
			require ROOT . '/includes/footer.php';
			exit;
		}
	});

/**
 * Specify that the current script will output JSON.
 * 
 * Sends a "Content-Type: application/json" header, and defines the OUTPUT_JSON
 * constant which signals to the exception handler that uncaught exceptions
 * should send JSON back to the browser.
 */
function output_json()
{
	header('Content-Type: application/json');
	define('OUTPUT_JSON', 1);
}