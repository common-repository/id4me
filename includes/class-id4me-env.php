<?php

/**
 * Class ID4me_Exceptions
 */
class ID4me_Env {

	/**
	 * Log errors in a global form like in WordPress
	 * (so that we can collect them and show them when the time has come)
	 *
	 * @param string $message
	 */
	protected function log_error( $message ) {
		global $id4me_errors;

		if ( ! is_array( $id4me_errors ) ) {
			$id4me_errors = array();
		}
		$id4me_errors[] = $message;
	}

	/**
	 * Counts Errors in error array
	 *
	 * @return int
	 */
	public function error_count() {
		global $id4me_errors;

		if ( is_array( $id4me_errors ) ) {
			return count( $id4me_errors );
		} else {
			return 0;
		}
	}

	/**
	 * Generate HTML code to render the error messages
	 */
	protected function wp_send_json_errors_and_die() {
		global $id4me_errors;

		if ( ! empty( $id4me_errors ) ) {
			wp_send_json_error( $id4me_errors );
		}

		wp_send_json_error( __( 'Unknown error', 'id4me' ) );
	}

	/**
	 * Return row error messages
	 *
	 * @return array
	 */
	protected function get_errors() {
		global $id4me_errors;
		return $id4me_errors;
	}

	/**
	 * Return error messages as WP object
	 *
	 * @return WP_Error
	 */
	protected function get_wp_errors() {
		global $id4me_errors;

		$errors = new WP_Error();
		if ( is_array( $id4me_errors ) ) {
			foreach ( $id4me_errors as $error ) {
				$errors->add( 'id4me', "<strong>ERROR</strong> : $error" );
			}
		}

		return $errors;
	}

	/**
	 * Retrieve application environment
	 * (can be externally given through ENV param, in Docker for ex.)
	 *
	 * @return array | false | string
	 */
	protected function get_env() {

		$env = getenv( 'ID4ME_ENV' );
		$allowed_envs = array( 'dev', 'prod' );

		if ( empty( $env ) || ! in_array( $env, $allowed_envs ) ) {
			return 'prod';
		} else {
			return $env;
		}
	}
}
