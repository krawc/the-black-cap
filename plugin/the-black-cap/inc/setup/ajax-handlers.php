<?php
defined( 'ABSPATH' ) || exit;

/**
 * AJAX: run a single setup step.
 * Request:  action=tbc_setup_run_step, step=<step_name>, _ajax_nonce=<nonce>
 * Response: { success: true, data: { logs: [], error: '', next_step: '' } }
 */
add_action( 'wp_ajax_tbc_setup_run_step', function (): void {
	check_ajax_referer( 'tbc_setup' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'error' => 'Insufficient permissions.' ], 403 );
	}

	$step = sanitize_key( $_POST['step'] ?? '' );
	if ( ! $step ) {
		wp_send_json_error( [ 'error' => 'No step specified.' ], 400 );
	}

	try {
		$runner = new TBC_Setup_Runner();
		$result = $runner->run_step( $step );
		wp_send_json_success( $result );
	} catch ( Throwable $e ) {
		wp_send_json_success( [
			'logs'      => [],
			'error'     => $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')',
			'next_step' => '',
		] );
	}
} );
