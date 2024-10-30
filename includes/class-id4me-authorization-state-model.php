<?php

/**
 * Model holding data to save in the state and route to the callback
 */
class AuthorizationStateModel implements JsonSerializable {

	/**
	 * @var string
	 */
	private $identifier;

	/**
	 * @var string
	 */
	private $origin;

	/**
	 * AuthorizationStateModel constructor.
	 *
	 * @param string $identifier
	 * @param string $origin
	 */
	public function __construct( $identifier = null, $origin = null ) {
		$this->identifier = $identifier;
		$this->origin = $origin;
	}

	/**
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * @return string
	 */
	public function get_origin() {
		return $this->origin;
	}

	/**
	 * Serializes the object to Json (implements JsonSerializable)
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return array(
			'origin' => $this->origin,
			'identifier' => $this->identifier,
		);
	}

	/**
	 * Unserializes the object from Array
	 *
	 * @param array $data
	 *
	 * @return AuthorizationStateModel
	 */
	public function fromArray( $data ) {
		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}

		return $this;
	}
}
