<?php

/**
 * A very simple JWT implementation for holding state (just HS256 signature)
 */
class ID4me_Simple_Jwt {

	/**
	 * Encodes JsonSerializable object or an array to JWT
	 *
	 * @param JsonSerializable | array $object payload of the JWT
	 * @param string $secret secret for the signature
	 *
	 * @return string
	 * @throws ID4me_Simple_Jwt_Encoding_Exception
	 */
	public static function get_jwt( $object, $secret ) {
		try {
			// Build the headers
			$headers = array(
				'alg' => 'HS256',
				'typ' => 'JWT',
			);
			$headers_encoded = self::base64url_encode( wp_json_encode( $headers, JSON_THROW_ON_ERROR ) );

			// Build the payload
			$payload_encoded = self::base64url_encode( wp_json_encode( $object, JSON_THROW_ON_ERROR ) );

			// Build the signature
			$signature = hash_hmac( 'SHA256', "$headers_encoded.$payload_encoded", $secret, true );
			$signature_encoded = self::base64url_encode( $signature );

			// Build and return the token
			$token = "$headers_encoded.$payload_encoded.$signature_encoded";

			return $token;

		} catch ( Exception $e ) {
			throw new ID4me_Simple_Jwt_Encoding_Exception( "Something went wrong when encoding JWT: $e" );
		}
	}

	/**
	 * Gets an array from JWT payload, if JWT valid
	 *
	 * @param string $jwt JWT content
	 * @param string $secret secret for the signature verification
	 *
	 * @return mixed
	 * @throws ID4me_Simple_Jwt_Invalid_Exception
	 * @throws ID4me_Simple_Jwt_Invalid_Header_Exception
	 * @throws ID4me_Simple_Jwt_Invalid_Signature_Exception
	 */
	public static function get_payload( $jwt, $secret ) {
		if ( count( explode( '.', $jwt ) ) !== 3 ) {
			throw new ID4me_Simple_Jwt_Invalid_Exception( 'Jwt structure incorrect. Exactly 3 section separated with dots expected' );
		}
		list( $headers, $payload, $signature ) = explode( '.', $jwt );

		// Decode and check the headers
		$headers_decoded = json_decode( base64_decode( $headers ), true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new ID4me_Simple_Jwt_Invalid_Header_Exception( "Invalid JWT header: $headers" );
		}

		if ( 'HS256' !== $headers_decoded['alg'] || 'JWT' !== $headers_decoded['typ'] ) {
			throw new ID4me_Simple_Jwt_Invalid_Header_Exception( "Invalid JWT header: $headers_decoded" );
		}

		// Decode payload
		$payload_decoded = json_decode( base64_decode( $payload ), true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new ID4me_Simple_Jwt_Invalid_Exception( "Invalid JWT payload: $payload" );
		}

		// Calculate signature to compare
		$signature_calculated = self::base64url_encode( hash_hmac( 'SHA256', "$headers.$payload", $secret, true ) );

		if ( $signature_calculated !== $signature ) {
			throw new ID4me_Simple_Jwt_Invalid_Signature_Exception( 'JWT Signature verification failed.' );
		}

		return $payload_decoded;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	private static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}
}
