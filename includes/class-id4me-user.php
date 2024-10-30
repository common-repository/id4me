<?php

/**
 * Class ID4me_WP_User_Meta
 */
class ID4me_User {

	/**
	 * ID4me username/domain
	 *
	 * @var string
	 */
	private $identifier;

	/**
	 * ID4me issuer
	 *
	 * @var string
	 */
	private $iss;

	/**
	 * ID4me sub
	 *
	 * @var string
	 */
	private $sub;

	/**
	 * Constructor.
	 *
	 * @param string $identifier
	 * @param string $sub
	 * @param string $iss
	 */
	public function __construct( $identifier, $sub, $iss ) {
		$this->identifier = $identifier;
		$this->sub = $sub;
		$this->iss = $iss;
	}

	/**
	 * Search the WP_User existing for the ID4me identifier
	 *
	 * @return WP_User
	 * @throws Exception
	 */
	public function get_wp_user() {

		$validated_user = $this->get_user_by_sub_and_iss();

		if ( count( $validated_user ) === 1 ) {
			return reset( $validated_user );
		} else {
			if ( ! $this->get_identifier() ) {
				throw new Exception( __( 'No identifier provided to identify the WordPress user', 'id4me' ) );
			}
			$users = $this->get_user_by_identifier();

			// Just one user must be found
			if ( count( $users ) > 1 ) {
				throw new Exception( __( 'Multiple WordPress users found with the provided identifier', 'id4me' ) );
			}
			if ( count( $users ) < 1 ) {
				throw new Exception( __( 'No WordPress users found with the provided identifier', 'id4me' ) );
			}
			if ( count( $users ) === 1 ) {
				self::save_iss_sub_identifier( $users[0]->ID, $this->get_sub(), $this->get_iss() );
				return reset( $users );
			}
		}
	}

	/**
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * @param string $identifier
	 */
	public function set_identifier( $identifier ) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function get_iss() {
		return $this->iss;
	}

	/**
	 * @return string
	 */
	public function get_sub() {
		return $this->sub;
	}

	/**
	 * Get user by identifier
	 *
	 * @return array
	 */
	public function get_user_by_identifier() {
		$query = new WP_User_Query(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'id4me_identifier',
						'value' => $this->get_identifier(),
					),
					array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'   => 'id4me_iss',
								'value' => '',
							),
							array(
								'key'     => 'id4me_iss',
								'compare' => 'NOT EXISTS',
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'   => 'id4me_sub',
								'value' => '',
							),
							array(
								'key'     => 'id4me_sub',
								'compare' => 'NOT EXISTS',
							),
						),
					),
				),
			)
		);

		return $query->get_results();
	}

	/**
	 * Get user by sub or iss
	 *
	 * @return array
	 */
	public function get_user_by_sub_and_iss() {
		$query = new WP_User_Query(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					0          => array(
						'key'   => 'id4me_iss',
						'value' => $this->get_iss(),
					),
					1          => array(
						'key'   => 'id4me_sub',
						'value' => $this->get_sub(),
					),
				),

			)
		);

		return $query->get_results();
	}

	/**
	 * Add the ID4me extra identifier field in the User Profile
	 *
	 * @action show_user_profile
	 * @action edit_user_profile
	 *
	 * @param WP_User $user
	 */
	public static function create_profile_fields( $user ) {

		$id4me_identifier = get_user_meta( $user->ID, 'id4me_identifier', true );
		$id4me_sub = get_user_meta( $user->ID, 'id4me_sub', true );
		$id4me_iss = get_user_meta( $user->ID, 'id4me_iss', true );
		?>
		<h3><?php esc_html_e( 'ID4me', 'id4me' ); ?></h3>

		<table class="form-table">
			<tr>
				<th><label for="id4me_identifier"><?php esc_html_e( 'ID4me identifier', 'id4me' ); ?></label></th>
				<td>
					<input type="text"
					       name="id4me_identifier"
					       id="id4me_identifier"
					       value="<?php echo esc_attr( $id4me_identifier ); ?>"
					       class="regular-text"/>

					<button onclick="startAuth('<?php esc_attr_e( 'Auth' ); ?>');" class="button button-primary" type="button" id="auth"><?php esc_html_e( 'Auth' ); ?></button>
					<br/>

					<p class="description" id="id4me-identifier-description">
						<?php _e( 'The identifier allows you to log in with ID4me; ex. <strong>domain.com</strong>', 'id4me' ); ?>
					</p>

				</td>
			</tr>
			<tr>
				<th><label for="id4me_iss"><?php esc_html_e( 'ID4me Iss', 'id4me' ); ?></label></th>
				<td>
					<input type="text" name="id4me_iss" class="regular-text" id="id4me_iss" value=<?php esc_attr_e( $id4me_iss ); ?>>
				</td>
			</tr>
			<tr>
				<th>
					<label for="id4me_sub"><?php esc_html_e( 'ID4me Sub', 'id4me' ); ?></label>
				</th>
				<td>
					<input type="text" name="id4me_sub" class="regular-text" id="id4me_sub" value=<?php esc_attr_e( $id4me_sub ); ?>>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the ID4me identifier, sub and iss during Profile Update
	 *
	 * @action personal_options_update
	 * @action edit_user_profile_update
	 *
	 * @param int $user_id
	 */
	public static function save_profile_fields( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			self::save_iss_sub_identifier( $user_id, $_POST['id4me_sub'], $_POST['id4me_iss'], sanitize_text_field( $_POST['id4me_identifier'] ) );
		}
	}

	/**
	 * Save the sub and iss during login
	 *
	 * @param int $user_id
	 * @param string $sub
	 * @param string $iss
	 * @param string $id4me_identifier
	 */
	public static function save_iss_sub_identifier( $user_id, $sub, $iss, $id4me_identifier = '' ) {

		update_user_meta( $user_id, 'id4me_sub', sanitize_text_field( $sub ) );
		update_user_meta( $user_id, 'id4me_iss', sanitize_text_field( $iss ) );

		if ( ! empty( $id4me_identifier ) ) {
			update_user_meta( $user_id, 'id4me_identifier', sanitize_text_field( $id4me_identifier ) );
		}

	}

	/**
	 * Saves user_meta_data in registrations process
	 *
	 * @param int $user_id
	 * @param string $nick_name
	 * @param string $website
	 * @param string $family_name
	 * @param string $given_name
	 */
	public static function save_user_meta_registration_data( $user_id, $nick_name, $website, $family_name, $given_name ) {
		update_user_meta( $user_id, 'id4me_nickname', sanitize_text_field( $nick_name ) );
		update_user_meta( $user_id, 'id4me_website', sanitize_text_field( $website ) );
		update_user_meta( $user_id, 'first_name', sanitize_text_field( $given_name ) );
		update_user_meta( $user_id, 'last_name', sanitize_text_field( $family_name ) );
	}

	/**
	 * Checks if is the same user based on saved sub
	 *
	 * @return bool
	 */
	public function is_very_same_user() {
		$user = get_users(
			array(
				'meta_key'   => 'id4me_sub',
				'meta_value' => $this->sub,
			)
		);
		$user = reset( $user );
		$user_id = $user->data->ID;

		return get_current_user_id() === $user_id;
	}
}
