jQuery(document).ready(function($) {
	// Submit form and clear the site pages log
	$( "input[name='clear_site_log'][type='submit']" ).click(function () {
		$("input[name='clear_site_pages_log'][type='hidden']").attr( 'value', 'true' );

		// Submit the form
		$( "form#pc-st-clear-logs" ).submit();
	});

	// Submit form and clear the admin pages log
	$( "input[name='clear_admin_log'][type='submit']" ).click(function () {
		$("input[name='clear_admin_pages_log'][type='hidden']").attr( 'value', 'true' );

		// Submit the form
		$( "form#pc-st-clear-logs" ).submit();
	});
});