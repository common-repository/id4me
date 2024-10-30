<?php

use Id4me\RP\HttpClient as HttpClientInterface;

/**
 * Class ID4me_Http_Client
 */
class ID4me_Http_Client implements HttpClientInterface {

	/**
	 * @param string $url
	 * @param array $headers
	 *
	 * @return array | WP_Error
	 * @throws Exception
	 */
	public function get( $url, array $headers = array() ) {

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( "Exception getting the resource $url" );
		}

		if ( ! is_wp_error( $response ) && key_exists( 'body', $response ) ) {
			return $response['body'];
		}

		return $response;
	}

	/**
	 * @param string $url
	 * @param array $body
	 * @param array $headers
	 *
	 * @return array | WP_Error
	 * @throws Exception
	 */
	public function post( $url, $body, array $headers = array() ) {

		$response = wp_remote_post(
			$url,
			array(
				'body'    => $body,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( "Exception POSTing to the resource $url" );
		}

		if ( ! is_wp_error( $response ) && key_exists( 'body', $response ) ) {
			return $response['body'];
		}

		return $response;
	}
}
