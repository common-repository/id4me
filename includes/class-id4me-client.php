<?php

use Id4me\RP\Model\AuthorizationTokens;
use Id4me\RP\Model\UserInfo;
use Id4me\RP\Service as ID4meService;

// Require JWT library
require_once 'class-id4me-simple-jwt.php';

// Require exception classes
require_once 'exceptions/class-id4me-simple-jwt-exception.php';
require_once 'exceptions/class-id4me-simple-jwt-invalid-exception.php';
require_once 'exceptions/class-id4me-simple-jwt-invalid-header-exception.php';
require_once 'exceptions/class-id4me-simple-jwt-invalid-signature-exception.php';
require_once 'exceptions/class-id4me-simple-jwt-encoding-exception.php';

/**
 * Class ID4me_Client
 */
class ID4me_Client extends ID4me_Env {

	/**
	 * @var ID4meService
	 */
	private $id4me_service;

	const AUTHORIZE = 'authorize'; // Use case for authorization from Profile (new window)
	const REGISTER = 'register'; // Use case for registration of new user (new window)
	const AUTHORIZE_LOGIN = 'connect'; // Use case for authorization from Login screen
	const AUTH = 'auth'; // Used in id4me_action query parameter to indicate callback

	/**
	 * ID4me_Client constructor.
	 *
	 * @param ID4meService
	 */
	public function __construct( $id4me_service = null ) {

		if ( ! $id4me_service instanceof ID4meService ) {
			$this->set_id4me_service( new ID4meService( new ID4me_Http_Client() ) );
		} else {
			$this->set_id4me_service( $id4me_service );
		}
	}

	/**
	 * Start an authorization flow from Login screen.
	 * The function generated login link and redirects to it in case of success.
	 *
	 * @action authenticate
	 *
	 * @param WP_User | WP_Error | null $user
	 *
	 * @return WP_User | WP_Error | null
	 * @throws ErrorException
	 * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
	 */
	public function connect( $user ) {

		// Do nothing if a user is already logged in
		if ( $user instanceof WP_User || $user instanceof WP_Error ) {
			return $user;
		}

		if ( $this->is_action( 'connect' ) ) {
			$identifier = $this->get_param( 'id4me_identifier' );
			$login_link = $this->register_rp_and_get_authorization_link( $identifier, self::AUTHORIZE_LOGIN );

			if ( ! is_null( $login_link ) ) {
				$this->redirect( $login_link );
			} else {
				// If no redirection happened and no error is returned, something wrong happened
				if ( empty( $this->get_errors() ) ) {
					$this->log_error( __( 'An unexpected error has occurred, unable to redirect to Authority', 'id4me' ) );
				}
			}

			if ( 0 !== $this->error_count() ) {
				return $this->get_wp_errors();
			}
		}
	}

	/**
	 * Separate function for mocking purposes
	 *
	 * @param string $login_link
	 */
	protected function redirect( $login_link ) {
		wp_redirect( $login_link );
	}

	/**
	 * Log in user after successful authentication through the authority
	 *
	 * @action authenticate
	 *
	 * @param WP_User | WP_Error | null $user
	 *
	 * @return WP_User | WP_Error | null
	 * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
	 * @throws \Id4me\RP\Exception\InvalidIDTokenException
	 */
	public function auth( $user ) {

		// Do nothing if a user is already logged in
		if ( $user instanceof WP_User || $user instanceof WP_Error ) {
			return $user;
		}

		// Are we trying to log in with ID4me?
		if ( $this->is_action( self::AUTH ) ) {
			$code = $this->get_param( 'code' );
			$state = $this->get_param( 'state' );

			// Return variables
			$prepared_data = null;
			$user = null;
			$access_data = false;
			$origin = null;

			// Get the ID4me identifier that comes back after possible authentication
			$session_state = $this->retrieve_authorization_state_from_state_parameter( $state );
			$origin = $session_state->get_origin();

			if ( false !== $session_state ) {
				//  Discover again to know the authority
				$authority_hostname = $this->discover_authority( $session_state->get_identifier() );

				if ( false !== $authority_hostname ) {
					$authority = $this->retrieve_authority_data( $authority_hostname, $this->get_redirect_url() );

					if ( false !== $authority ) {
						$access_data = $this->authorize_user( $authority, $code );

						if ( false !== $access_data ) {
							if ( self::AUTHORIZE_LOGIN === $origin ) {
								$user = $this->finish_connect( $access_data );
							} elseif ( self::REGISTER === $origin ) {
								$prepared_data = $this->finish_registration( $authority, $access_data );
							}
						}
					}
				}
			}

			// Processing done, now depending on the use case delievering the results or an error
			if ( self::AUTHORIZE_LOGIN === $origin ) {
				if ( ! is_null( $user ) && 0 === $this->error_count() ) {
					return $user;
				} else {
					return $this->get_wp_errors();
				}
			} elseif ( self::REGISTER === $origin ) {
				if ( ! is_null( $prepared_data ) && 0 === $this->error_count() ) {
					$this->execute_js( $prepared_data );
				} else {
					$this->execute_js_errors( $session_state->get_origin() );
				}
			} elseif ( self::AUTHORIZE === $origin ) {
				if ( false !== $access_data && 0 === $this->error_count() ) {
					$id4me_user = new ID4me_User( $session_state->get_identifier(), $access_data['sub'], $access_data['iss'] );

					if ( count( $id4me_user->get_user_by_sub_and_iss() ) === 0 || $id4me_user->is_very_same_user() ) {
						$this->print_and_execute_js_in_popup( $access_data, $session_state->get_identifier() );
					} else {
						$this->alert_user_already_authorized();
					}
				} else {
					$this->execute_js_errors( $session_state->get_origin() );
				}
			} else {
				return $this->get_wp_errors();
			}
		}

		return null;
	}

	/**
	 * @param array $access_data
	 *
	 * @return bool | WP_User | null
	 */
	public function finish_connect( $access_data ) {

		// If authorization successful, retrieve corresponding WordPress user
		if ( is_array( $access_data ) && array_key_exists( 'identifier', $access_data ) ) {
			$user = $this->retrieve_wp_user( $access_data['identifier'], $access_data['sub'], $access_data['iss'] );
		} else {
			$user = null;
		}
		// We are in the authentication hook and just need to return a valid WP_User to log in
		if ( $user instanceof WP_User ) {
			return $user;
		}

		return null;
	}

	/**
	 * Finish registration in Popup flow (return results via Javascript)
	 *
	 * @param ID4me_WP_Authority $authority
	 * @param array $access_data
	 *
	 * @return array | null
	 */
	public function finish_registration( $authority, $access_data ) {

		try {
			$user_info = $this->get_user_info( $authority, $access_data );
			return $this->prepare_user_data( $user_info, $access_data['identifier'] );

		} catch ( Exception $e ) {
			$this->log_error( $e->getMessage() );
			return null;
		}
	}


	/**
	 * Gets user info
	 *
	 * @param ID4me_WP_Authority $authority
	 * @param array $access_data
	 *
	 * @return UserInfo
	 */
	public function get_user_info( $authority, $access_data ) {
		$tokens = new AuthorizationTokens( $access_data );

		return $this->id4me_service->getUserInfo(
			$this->get_config_object( $authority->get_configuration() ),
			unserialize( $authority->get_client() ),
			$tokens
		);
	}

	/**
	 * Fill in UserInfo into an array. Replace the identifier if empty.
	 *
	 * @param UserInfo $user_info
	 * @param string | NULL $id4me_identifier
	 *
	 * @return array
	 */
	private function prepare_user_data( $user_info, $id4me_identifier ) {
		return array(
			'id4me_sub'          => $user_info->getSub(),
			'id4me_iss'          => $user_info->getIss(),
			'email'              => $user_info->getEmail(),
			'nickname'           => $user_info->getNickname(),
			'preferred_username' => $user_info->getPreferredUsername(),
			'website'            => $user_info->getWebsite(),
			'family_name'        => $user_info->getFamilyName(),
			'given_name'         => $user_info->getGivenName(),
			'id4meIdentifier'    => empty( $user_info->getId4meIdentifier() ) ? $id4me_identifier : $user_info->getId4meIdentifier(),
		);
	}


	/**
	 * Call discover() from the ID4me Service to retrieve DNS TXT records from identifier domain
	 *
	 * @param string $identifier
	 *
	 * @return boolean | string
	 */
	public function discover_authority( $identifier ) {
		$identifier = sanitize_text_field( $identifier );

		if ( empty( $identifier ) ) {
			$this->log_error( __( 'Please enter a valid identifier', 'id4me' ) );
			return false;
		}

		try {
			return $this->id4me_service->discover( $identifier );

		} catch ( Exception $exception ) {
			$this->log_error( __( 'No DNS configuration found for identifier', 'id4me' ) );
			return false;
		}
	}

	/**
	 * Retrieves the ID4me credentials and data for the given authority:
	 *    - If already registered, load data from DB
	 *    - If not, register the authority and persist data
	 *
	 * @param string $authority_hostname
	 * @param string $redirect_url
	 *
	 * @return boolean | ID4me_WP_Authority
	 * @throws Exception
	 */
	public function retrieve_authority_data( $authority_hostname, $redirect_url ) {
		$authority = new ID4me_WP_Authority();

		// Load info from wp_authority DB table
		if ( ! $authority->load_by_hostname( $authority_hostname ) || $authority->has_expired() ) {

			// Accept localhost domains in dev mode
			if ( $this->get_env() === 'dev' ) {
				$application_type = 'native';
			} else {
				$application_type = 'web';
			}

			// OpenID config call
			try {
				$configuration = $this->id4me_service->getOpenIdConfig(
					$authority_hostname
				);
			} catch ( Exception $exception ) {
				/* translators: %s hostname of ID Authority */
				$this->log_error( sprintf( __( 'Cannot get configuration of ID Authority %s', 'id4me' ), $authority_hostname ) );
				return false;
			}

			// Registration
			try {
				$client = $this->id4me_service->register(
					$configuration,
					$this->get_client_name(),
					$redirect_url,
					$application_type
				);
			} catch ( Exception $exception ) {
				$this->log_error( __( 'Registration request returned an error', 'id4me' ) );
				return false;
			}

			// Save client/authority data in the authority DB table
			$authority->set_hostname( $authority_hostname );
			$authority->set_client_id( $client->getClientId() );
			$authority->set_client_secret( $client->getClientSecret() );
			$authority->set_configuration( $configuration->getData() );
			$authority->set_client( serialize( $client ) );

			// Set expiration date for the entry
			$expiration_time = (int) $client->getClientExpirationTime();

			if ( $client->getClientExpirationTime() !== 0 ) {
				$authority->set_expired( gmdate( 'Y-m-d H:i:s', $expiration_time ) );
			}

			if ( ! $authority->save() ) {
				$this->log_error( __( 'Unable to register client and authority data in the database', 'id4me' ) );
				return false;
			}
		}

		return $authority;
	}

	/**
	 * Builds login link for redirect or ajaxredirect
	 *
	 * @param string $authority
	 * @param string $identifier
	 * @param string $redirect_url
	 * @param string $use_case
	 *
	 * @return mixed
	 * @throws ID4me_Simple_Jwt_Encoding_Exception
	 */
	public function build_login_link( $authority, $identifier, $redirect_url, $use_case ) {

		if ( $authority instanceof ID4me_WP_Authority && ! empty( $authority->get_hostname() ) ) {

			// Memorize identifier hash for 10 min, so that we know which user is coming back to log in
			$model = new AuthorizationStateModel( $identifier, $use_case );

			$state = $this->get_state_from_model( $model );

			$prompt = null;
			$user_info_claims = null;

			if ( self::REGISTER == $use_case ) {
				$user_info_claims = $this->get_claimlist();
			}

			// Build authority login URL (identifier hash is used as state)
			$login_link = $this->id4me_service->getAuthorizationUrl(
				$this->get_config_object( $authority->get_configuration() ),
				$authority->get_client_id(),
				$identifier,
				$redirect_url,
				$state,
				$prompt,
				$user_info_claims
			);

			if ( empty( $login_link ) ) {
				$this->log_error( __( 'Request for authorization URL returned empty response', 'id4me' ) );
				return null;

			} else {
				return $login_link;
			}
		}

		return '';
	}

	/**
	 * @return \Id4me\RP\Model\ClaimRequestList
	 */
	protected function get_claimlist() {
		return ID4me_Registration::get_claimlist_for_registration();
	}

	public function get_state_jwt_secret() {
		// Use AUTH_KEY as a more secure way first
		if ( defined( 'AUTH_KEY' ) && AUTH_KEY !== 'put your unique phrase here' ) {
			return AUTH_KEY;
		}

		// The secret will be stored as an option. These JWTs do not store credentials in the end.
		// For the first run it will be generated randomly
		$secret = get_option( 'id4me.state_secret' );
		if ( ! $secret ) {
			$secret = bin2hex( random_bytes( 24 ) );
			add_option( 'id4me.state_secret', $secret );
		}

		return $secret;
	}

	/**
	 * Retrieve the identifier of the user who wants to log in
	 *
	 * @param AuthorizationStateModel $model
	 *
	 * @return string
	 * @throws ID4me_Simple_Jwt_Encoding_Exception
	 */
	public function get_state_from_model( $model ) {
		return ID4me_Simple_Jwt::get_jwt( $model, $this->get_state_jwt_secret() );
	}


	/**
	 * Retrieve the identifier of the user who wants to log in
	 *
	 * @param string $state
	 *
	 * @return mixed
	 */
	public function retrieve_authorization_state_from_state_parameter( $state ) {

		// Does state exists?
		if ( empty( $state ) ) {
			$this->log_error( __( 'No valid state returned from the ID Authority', 'id4me' ) );
			return false;
		}

		try {
			$jwt = ID4me_Simple_Jwt::get_payload( $state, $this->get_state_jwt_secret() );
			$authorization_state = ( new AuthorizationStateModel() )->fromArray( $jwt );
		} catch ( Exception $e ) {
			$this->log_error( __( 'Invalid state returned from the ID Authority', 'id4me' ) );
			return false;
		}

		$identifier = $authorization_state->get_identifier();
		$use_case = $authorization_state->get_origin();

		if ( empty( $identifier ) ) {
			$this->log_error( __( 'Unknown identifier given', 'id4me' ) );
			return false;
		}
		if ( empty( $use_case ) ) {
			$this->log_error( __( 'Use-case not set', 'id4me' ) );
			return false;
		}

		return $authorization_state;
	}

	/**
	 * Authorization process: validate user code and return the identifier of the authorized user
	 *
	 * @param ID4me_WP_Authority $authority
	 * @param string $code
	 *
	 * @return array | boolean
	 */
	public function authorize_user( $authority, $code ) {

		// Call authorization service to ensure user is logged in with ID4me
		try {
			$access_data = $this->id4me_service->authorize(
				$this->get_config_object( $authority->get_configuration() ),
				$code,
				$this->get_redirect_url(),
				$authority->get_client_id(),
				$authority->get_client_secret()
			);

		} catch ( Exception $exception ) {
			$this->log_error(
				sprintf(
					__( 'Authorization request from %s returned an error', 'id4me' ),
					$authority->get_hostname()
				)
			);
			return false;
		}

		return $access_data;
	}

	/**
	 * Select existing WP_User according to the given identifier, registered as metadata
	 *
	 * @param  $identifier
	 * @param  $sub
	 * @param  $iss
	 *
	 * @return bool | WP_User
	 */
	public function retrieve_wp_user( $identifier, $sub, $iss ) {
		try {
			$user = new ID4me_User( $identifier, $sub, $iss );
			return $user->get_wp_user();

		} catch ( Exception $exception ) {
			$this->log_error( $exception->getMessage() );
			return false;
		}
	}

	/**
	 * Check if we are in the ID4me pop-up ("connect" step)
	 *
	 * @param string $action
	 *
	 * @return boolean
	 */
	public function is_action( $action ) {
		return ! empty( $_GET['id4me_action'] )
			&& $_GET['id4me_action'] === $action;
	}

	/**
	 * Generates the OpenID configuration object for the ID4me service
	 * (uses a utility function from the service)
	 *
	 * @param string $json_configuration
	 *
	 * @return \Id4me\RP\Model\OpenIdConfig
	 */
	public function get_config_object( $json_configuration ) {

		if ( ! empty( $json_configuration ) ) {
			return $this->id4me_service->createOpenIdConfigFromJson( $json_configuration );
		}

		return null;
	}

	/**
	 * Build the URL where the user is redirected after login on the authority page,
	 * then use as safety check by the authorization process
	 *
	 * @return string
	 */
	public function get_redirect_url() {

		$redirect_url = add_query_arg(
			array(
				'id4me_action' => self::AUTH,
			),
			wp_login_url()
		);

		return $redirect_url;
	}

	/**
	 * Return client name
	 * (fixed, client domain (my current domain))
	 *
	 * @return string
	 */
	public function get_client_name() {
		return wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Get the given as parameter in the URL
	 *    - identifier to log in (ID4me username)
	 *    - redirect link to come back after authentication through the authority
	 *    - user token after authentication through the authority
	 *
	 * @param string $param
	 *
	 * @return boolean | string
	 */
	public function get_param( $param ) {

		if ( ! in_array( $param, array( 'id4me_identifier', 'code', 'state' ), true ) ) {
			return false;
		}
		if ( ! empty( $_GET[ $param ] ) ) {

			// sanitize_text_field() strips all the stuff we don't want in an URL param
			return sanitize_text_field( $_GET[ $param ] );
		}

		return false;
	}

	/**
	 * Save the ID4me service instance
	 *
	 * @param ID4meService $id4me_service
	 */
	public function set_id4me_service( ID4meService $id4me_service ) {

		if ( $id4me_service instanceof ID4meService ) {
			$this->id4me_service = $id4me_service;
		}
	}

	/**
	 * @action start_flow
	 *
	 * Start the authorization flow from User Profile in a new browser window.
	 * The function delivers an authorization link as a JSON output.
	 *
	 * @throws ErrorException
	 * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
	 */
	public function run_auth_flow() {
		$login_link = null;

		if ( ! isset( $_POST['identifier'] ) ) {
			parent::log_error( __( 'Missing identifier', 'id4me' ) );
		} else {
			$identifier = sanitize_text_field( $_POST['identifier'] );
			$login_link = $this->register_rp_and_get_authorization_link( $identifier, self::AUTHORIZE );
		}
		if ( null === $login_link ) {
			$this->wrap_json_error();
		} else {
			$this->wrap_json_success( $login_link );
		}
	}

	/**
	 * Separate function for mocking purposes
	 *
	 * @param string $login_link
	 */
	public function wrap_json_success( $login_link ) {
		wp_send_json_success( $login_link );
	}

	/**
	 * Separate function for mocking purposes
	 */
	public function wrap_json_error() {
		parent::wp_send_json_errors_and_die();
	}

	/**
	 * Gets the Hostname, register the client and redirects to authority
	 *
	 * @param string $identifier
	 * @param string $use_case
	 *
	 * @return string
	 * @throws ID4me_Simple_Jwt_Encoding_Exception
	 */
	public function register_rp_and_get_authorization_link( $identifier, $use_case ) {
		$redirect_url = $this->get_redirect_url();
		$authority_hostname = $this->discover_authority( $identifier );

		if ( ! $authority_hostname ) {
			// no error logging needed, it's done in $this->discover_authority()
			return null;
		}

		// Retrieve Authority configuration and credentials
		$authority = $this->retrieve_authority_data( $authority_hostname, $redirect_url );

		if ( ( ! $authority instanceof ID4me_WP_Authority ) || empty( $authority->get_hostname() ) ) {
			$this->log_error( __( 'Impossible to retrieve Authority configuration', 'id4me' ) );
			return null;
		}
		$login_link = $this->build_login_link( $authority, $identifier, $redirect_url, $use_case );

		if ( is_null( $login_link ) ) {
			$this->log_error( __( 'Loginlink could not be generated', 'id4me' ) );
			return null;
		}

		return $login_link;
	}

	/**
	 * Print Javascript code for execution in popup
	 *
	 * @param string $identifier
	 * @param array $data an array with all authorization tokens
	 */
	public function print_and_execute_js_in_popup( $data, $identifier ) {
		wp_die(
			"<script>
					window.opener.writeIntoInputFields( '" . esc_html( $data['sub'] ) . "' , '" . esc_html( $data['iss'] ) . "' , '" . esc_html( $identifier ) . "' );
					window.close();
					window.opener.enableDisableButton( false, '" . esc_html__( 'Auth' ) . "' );
		 		</script>"
		);
	}

	/**
	 * Prints Javascriptcode for execution in popup
	 */
	public function alert_user_already_authorized() {
		wp_die(
			"<script>
					window.close();
					window.opener.enableDisableButton( false, '" . esc_html__( 'Auth' ) . "' );
					window.opener.alert( '" . esc_html__( 'A User with this Identifier is already authorized' ) . "' );
		 		</script>"
		);
	}

	/**
	 *  Starts registrations flow with ID4me.
	 */
	public function register_id4me() {
		$login_link = null;

		if ( ! isset( $_POST['id4me_identifier'] ) ) {
			parent::log_error( __( 'Missing identifier', 'id4me' ) );
		} else {
			$identifier = sanitize_text_field( $_POST['id4me_identifier'] );
			$login_link = $this->register_rp_and_get_authorization_link( $identifier, self::REGISTER );
		}
		if ( null === $login_link ) {
			$this->wrap_json_error();
		} else {
			$this->wrap_json_success( $login_link );
		}
	}

	/**
	 * @param array $data
	 */
	public function execute_js( $data ) {
		wp_die(
			"<script>
					window.opener.showFilledInRegistrationForm(
						'" . esc_html( $data['email'] ) . "',
						'" . esc_html( $data['preferred_username'] ) . "',
						'" . esc_html( $data['nickname'] ) . "',
						'" . esc_html( $data['given_name'] ) . "',
						'" . esc_html( $data['family_name'] ) . "',
						'" . esc_html( $data['website'] ) . "',
						'" . esc_html( $data['id4meIdentifier'] ) . "',
						'" . esc_html( $data['id4me_sub'] ) . "',
						'" . esc_html( $data['id4me_iss'] ) . "');
					window.close();
		 		</script>"
		);
	}

	/**
	 * @param string $usecase
	 */
	public function execute_js_errors( $usecase ) {
		global $id4me_errors;
		$errors = wp_json_encode( $id4me_errors );

		if ( self::REGISTER === $usecase ) {
			wp_die(
				"<script>
					window.close();
					window.opener.report_errors( $errors )
		 		</script>"
			);
		} elseif ( self::AUTHORIZE === $usecase ) {
			wp_die(
				"<script>
					window.opener.enableDisableButton( false, '" . __( 'Auth' ) . "' );
					window.close();
					window.opener.alert_errors( $errors );
		 		</script>"
			);
		}
	}
}
