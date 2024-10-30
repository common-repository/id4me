<?php

use Id4me\RP\Model\ClaimRequest;
use Id4me\RP\Model\ClaimRequestList;

/**
 * Class Registration
 */
class ID4me_Registration {

	/**
	 * Hooks in registration form
	 */
	public function show_forms() {
		if ( isset( $_POST['id4me_flag'] ) ) {
			$this->show_id4me_registration_form();
		} else {
			$this->show_identifier_registration_form();
		}
	}

	/**
	 * Renders the ID4me registration form
	 */
	public function show_id4me_registration_form() {

		echo '
			<label for="nickname" id="id4me-nickname">
				' . esc_html__( 'Nickname', 'id4me' ) . '<br>
				<input type="text" name="nickname" id="nickname" value="' . esc_html( $_POST['nickname'] ) . '" />
			</label>
			
			<label for="given_name" id="id4me-given_name">
				' . esc_html__( 'Given name', 'id4me' ) . '<br>
				<input type="text" name="given_name" id="given_name" value="' . esc_html( $_POST['given_name'] ) . '" />
			</label>
			
			<label for="family_name" id="id4me-family_name">
				' . esc_html__( 'Family name', 'id4me' ) . '<br>
				<input type="text" name="family_name" id="family_name"  value="' . esc_html( $_POST['family_name'] ) . '" />
			</label>

			<label for="website" id="id4me-website">
				' . esc_html__( 'Website', 'id4me' ) . '<br>
				<input type="text" name="website" id="website" value="' . esc_html( $_POST['website'] ) . '" />
			</label>
			
			<input type="text"
				name="id4me_sub"
				id="id4me_sub" value="' . esc_html( $_POST['id4me_sub'] ) . '" hidden />

			<input type="text"
				name="id4me_iss"
				id="id4me_iss" value="' . esc_html( $_POST['id4me_iss'] ) . '" hidden />

			<input type="text"
				name="id4meIdentifier"
				id="id4meIdentifier" value="' . esc_html( $_POST['id4meIdentifier'] ) . '" hidden />

			<input type="text"
				name="id4me_flag"
				id="id4me_flag" value="true" hidden />

			<input type="hidden" name="id4me_errors" id="id4me_errors" />
		';
	}


	/**
	 * Renders identifier registration form
	 */
	public function show_identifier_registration_form() {

		$html = '
			<div id="id4me-registerdiv" class="id4me">
				<div id="id4me-button">
					<a id="id4me-button-anchor" class="button loginbutton" tabindex="0" type="button">
						<span id="id4me-button-text" class="Login-text">
							' . esc_html__( 'Register with ID4me', 'id4me' ) . '
						</span>
					</a>
				</div>

				<div id="id4me-handler-form" class="hiddenform">
					<p id="id4me-headline">
						<span>
							<a href="https://id4me.org/" class="image">
								<img src=" ' . plugins_url( 'assets/img/id4me-logo-secondary.svg', dirname( __FILE__ ) ) . ' " height="20px" />
							</a>
						</span>
					</p>

					<label for="id4me-input-registration" id="id4me-identifier">
						' . esc_html__( 'Enter your identifier:', 'id4me' ) . '<br>
						<input type="text"
							name="id4me_identifier"
							id="id4me-input-registration" value="' . ( isset( $_POST['id4me_identifier'] ) ? esc_html( $_POST['id4me_identifier'] ) : '' ) . '" />
					</label>

					<input type="hidden"
						name="id4me_input_given"
						id="id4me_input_given" value="' . ( isset( $_POST['id4me_input_given'] ) ? esc_html( $_POST['id4me_input_given'] ) : 'false' ) . '" />

					<input type="hidden" name="id4me_errors" id="id4me_errors" />
					
					<p class="submit">
						<button type="button" onclick="startRegistration();" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Register">' . __( 'Register	' ) . '</button>
					</p>
				</div>
			</div>';

		echo $html;
	}

	/**
	 * Generates the claimlist
	 *
	 * @return ClaimRequestList
	 */
	public static function get_claimlist_for_registration() {
		return new ClaimRequestList(
			new ClaimRequest( 'email', true ),
			new ClaimRequest( 'nickname' ),
			new ClaimRequest( 'given_name' ),
			new ClaimRequest( 'family_name' ),
			new ClaimRequest( 'preferred_username' ),
			new ClaimRequest( 'website' ),
			new ClaimRequest( 'id4me.identifier' )
		);
	}

	/**
	 * Checks if user with iss and sub already registered
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function validate_form( $errors ) {

		// First handle any errors passed from the form auto-post
		if ( isset( $_POST['id4me_errors'] ) && ! empty( $_POST['id4me_errors'] ) ) {
			try {
				$id4me_errors = json_decode( $_POST['id4me_errors'] );

				if ( is_array( $id4me_errors ) && count( $id4me_errors ) > 0 ) {
					foreach ( $id4me_errors as $id4me_error ) {
						$errors->add( 'id4me', sprintf( __( '<strong>ERROR</strong> : %s' ), $id4me_error ) );
					}

					return $errors;
				}
			} catch ( \Exception $e ) {
				$errors->add( 'id4me', sprintf( __( '<strong>ERROR</strong> : Unknown ID4me error - %s' ), $e->getMessage() ) );
				return $errors;
			}
		}

		// No ID4me errors, process the registration
		if ( isset( $_POST['id4meIdentifier'] ) ) {
			$id4me_user = new ID4me_User(
				sanitize_text_field( $_POST['id4meIdentifier'] ),
				sanitize_text_field( $_POST['id4me_sub'] ),
				sanitize_text_field( $_POST['id4me_iss'] )
			);
			$validated_user = $id4me_user->get_user_by_sub_and_iss();

			if ( ! empty( $validated_user ) ) {
				$errors->add( 'id4me', __( '<strong>ERROR</strong> : There is another user assigned to this ID4me Identity' ) );
			}
		}

		return $errors;
	}
}
