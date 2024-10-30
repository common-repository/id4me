<?php

/**
 * Class ID4me_WP_Authority
 */
class ID4me_WP_Authority {

	/**
	 * Unique ID
	 *
	 * @var int
	 */
	private $ID;

	/**
	 * Authority reference name/host (issuer)
	 *
	 * @var string
	 */
	private $hostname;

	/**
	 * Registration date and time
	 *
	 * @var string
	 */
	private $registered = '0000-00-00 00:00:00';

	/**
	 * Expiration date and time
	 *
	 * @var string
	 */
	private $expired;

	/**
	 * The client secret token for the Authority
	 *
	 * @var string
	 */
	private $client_secret;

	/**
	 * Authority configuration object
	 *
	 * @var string
	 */
	private $configuration;

	/**
	 * The client ID for the Authority
	 *
	 * @var string
	 */
	private $client_id;

	/**
	 * @var string
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param int $id
	 */
	public function __construct( $id = null ) {
		$this->set_ID( $id );
	}

	/**
	 * Find the entry in the DB by issuer and fill the object attributes accordingly
	 * (if said entry exists, otherwise returns false)
	 *
	 * @param string $hostname
	 * @param bool $return_data_set
	 *
	 * @return boolean
	 */
	public function load_by_hostname( $hostname ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}id4me_authorities` WHERE authority_hostname = %s LIMIT 1",
				$hostname
			),
			ARRAY_A
		);

		if ( ! is_array( $data ) || empty( $data['authority_id'] ) ) {
			return false;

		} else {
			$this->from_array( $data );

			return true;
		}
	}

	/**
	 * Save current object in a new DB entry
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;

		if ( ! $this->get_hostname() ) {
			return false;
		}

		if ( $this->get_hostname() ) {

			// Insert new authority in the database
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'id4me_authorities',
				array(
					'authority_registered'    => current_time( 'mysql' ),
					'authority_expired'       => $this->get_expired(),
					'authority_hostname'      => $this->get_hostname(),
					'authority_configuration' => $this->get_configuration(),
					'client_id'               => $this->get_client_id(),
					'client_secret'           => $this->get_client_secret(),
					'client'                  => $this->get_client(),
				)
			);

			// Save newly created ID
			if ( $inserted ) {
				$this->set_ID( $wpdb->insert_id );

				return true;
			}
		}

		return false;
	}

	/**
	 * Fill object with data from array
	 *
	 * @param array $data
	 */
	public function from_array( $data ) {

		if ( is_array( $data ) ) {

			if ( ! empty( $data['authority_id'] ) ) {
				$this->set_ID( $data['authority_id'] );
			}
			if ( ! empty( $data['authority_registered'] ) ) {
				$this->set_registered( $data['authority_registered'] );
			}
			if ( ! empty( $data['authority_expired'] ) ) {
				$this->set_expired( $data['authority_expired'] );
			}
			if ( ! empty( $data['authority_hostname'] ) ) {
				$this->set_hostname( $data['authority_hostname'] );
			}
			if ( ! empty( $data['authority_configuration'] ) ) {
				$this->set_configuration( $data['authority_configuration'] );
			}
			if ( ! empty( $data['client_id'] ) ) {
				$this->set_client_id( $data['client_id'] );
			}
			if ( ! empty( $data['client_secret'] ) ) {
				$this->set_client_secret( $data['client_secret'] );
			}
			if ( ! empty( $data['client'] ) ) {
				$this->set_client( $data['client'] );
			}
		}
	}

	/**
	 * Check expiration date: are the credentials still valid?
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function has_expired() {

		if ( ! empty( $this->get_expired() ) ) {

			$expiration_time = new DateTime( $this->get_expired() );
			$current_time = new DateTime( 'now' );

			return ( $expiration_time <= $current_time );
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function get_ID() { // phpcs:ignore
		return $this->ID;
	}

	/**
	 * @param int $id
	 */
	public function set_ID( $id ) { // phpcs:ignore
		$this->ID = (int) $id;
	}

	/**
	 * @return string
	 */
	public function get_hostname() {
		return $this->hostname;
	}

	/**
	 * @param string $hostname
	 */
	public function set_hostname( $hostname ) {
		$this->hostname = $hostname;
	}

	/**
	 * @return string
	 */
	public function get_registered() {
		return $this->registered;
	}

	/**
	 * @param string $registered
	 */
	public function set_registered( $registered ) {
		$this->registered = $registered;
	}

	/**
	 * @return string
	 */
	public function get_expired() {
		return $this->expired;
	}

	/**
	 * @param string $expired
	 */
	public function set_expired( $expired ) {
		$this->expired = $expired;
	}

	/**
	 * @return string
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * @param string $client_id
	 */
	public function set_client_id( $client_id ) {
		$this->client_id = $client_id;
	}

	/**
	 * @return string
	 */
	public function get_client_secret() {
		return $this->client_secret;
	}

	/**
	 * @param string $client_secret
	 */
	public function set_client_secret( $client_secret ) {
		$this->client_secret = $client_secret;
	}

	/**
	 * @return string
	 */
	public function get_configuration() {
		return $this->configuration;
	}

	/**
	 * @param string $configuration
	 */
	public function set_configuration( $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @return string
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * @param string $client
	 */
	public function set_client( $client ) {
		$this->client = $client;
	}

	/**
	 * Utility function to call on activation, so that the class
	 * can create the DB table it needs to work
	 */
	public static function create_table() {

		// Include WP database class
		global $wpdb;

		// Use WP character collate
		$charset_collate = $wpdb->get_charset_collate();

		// Create authority table
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}id4me_authorities` (
				`authority_id` bigint(20) unsigned NOT NULL auto_increment,
				`authority_hostname` varchar(150) NOT NULL,
				`authority_registered` datetime NOT NULL default '0000-00-00 00:00:00',
				`authority_expired` datetime NULL,
				`authority_configuration` text NOT NULL,
				`client_id` varchar(255) NOT NULL,
				`client_secret` varchar(255) NOT NULL,
				`client` TEXT,
				PRIMARY KEY (`authority_id`),
				UNIQUE (`authority_hostname`)
			) $charset_collate;"  // phpcs:ignore
		);
	}

	/**
	 * Utility function to call on deactivation to clean up the DB tables
	 */
	public static function delete_table() {

		// Include WP database class
		global $wpdb;

		// Delete authority table
		$wpdb->query(
			"DROP TABLE IF EXISTS `{$wpdb->prefix}id4me_authorities`;"
		);
	}

	/**
	 * Updates Database on plugin activation if version differs
	 */
	public static function id4me_database_update_from_1_0_3_to_1_0_4() {
		global $wpdb;

		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}id4me_authorities ADD client TEXT;"
		);
	}
}
