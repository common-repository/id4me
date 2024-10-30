<?php

/**
 * Class ID4me_Login
 */
class ID4me_Login extends ID4me_Env {

	/**
	 * Render the extra ID4me login form
	 *
	 * @action login_form
	 */
	public function login_form() {

		if ( $this->is_login_page() ) {
			$login_html = '
				<div id="id4me-logindiv" class="id4me" method="get">
					<div id="id4me-button">
						<a id="id4me-button-anchor" class="button loginbutton" tabindex="0" type="button">
	                    	<span id="id4me-button-text" class="Login-text">
								' . esc_html__( 'Log in with ID4me', 'id4me' ) . '
							</span>
	                     </a>

						<a id="id4me-button-reset" class="button loginbutton" tabindex="0" type="button">
							<span>' . esc_html__( 'Reset', 'id4me' ) . '</span>
						</a>
					</div>

					<div id="id4me-handler-form" class="hiddenform">
						<p id="id4me-headline">
							<span>
								<a href="https://id4me.org/" class="imgage">
									<img src=" ' . plugins_url( 'assets/img/id4me-logo-secondary.svg', dirname( __FILE__ ) ) . ' " height="20px"/>
								</a>
							</span>
						</p>

						<label for="id4me-input" id="id4me-identifier">
							' . esc_html__( 'Enter your identifier:', 'id4me' ) . '<br>
							<input type="text" form="id4me-loginform" name="id4me_identifier" id="id4me-input">
						</label>

						<p class="submit">
							<span class="forgetmenot">
								<label for="id4me-checkbox" id="id4me-rememberme">
									<input id="id4me-checkbox" form="id4me-loginform" type="checkbox" name="rememberme" value="yes">
									' . esc_html__( 'Remember Me' ) . '
								</label>
							</span>

							<label for="id4me-input-signin" id="id4me-signin">
								<button type="button" name="id4me_login_submit" id="id4me-input-signin" class="id4me-login-submit id4me-button button button-primary button-large">
									' . esc_html__( 'Anmelden' ) . '
								</button>
							</label>
						</p>
					</div>

					<div id="id4me-backtowp" class="hiddenform">
						<p class="id4me-login-or"><span>' . esc_html__( 'Or', 'id4me' ) . '</span></p>

						<a type="button" id="id4me-button-wp" tabindex="0" class="button loginbutton">
							<span>' . esc_html__( 'Log in with WordPress', 'id4me' ) . '</span>
						</a>
					</div>
				</div>
            ';

			echo $login_html;
		}
	}

	/**
	 * Add a hidden Submit-Form for ID4me
	 *
	 * @action login_form
	 */
	public function submit_login() {
		echo '
			<form id="id4me-loginform" class="id4me" method="get" style="display: none;">
				<label for="id4me-identifier" id="id4me-identifier">
					' . esc_html__( 'Enter your identifier:', 'id4me' ) . '<br>
					<input type="hidden" name="id4me_identifier" id="id4me-input2">
				</label>
				<input type="hidden" name="id4me_action" value="connect">
			</form>
		';
	}

	/**
	 * Insert an "id4me" extra body class to differentiate WP login / ID4me login
	 * @action login_body_class
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function login_body_class( $classes ) {

		// Are we on the ID4me login page?
		if ( ! empty( $_GET['id4me_action'] ) ) {
			$classes[] = 'id4me';
		}

		return $classes;
	}

	/**
	 * Check if we are in the WP login page
	 *
	 * @return boolean
	 */
	public function is_login_page() {

		return ! isset( $_REQUEST['action'] )
			|| ( 'login' === $_REQUEST['action'] )
			|| ( 'register' === $_REQUEST['action'] );
	}

	/**
	 * Add the alternative login CSS/JS scripts
	 * @action login_enqueue_scripts
	 */
	public function enqueue_scripts() {

		if ( $this->is_login_page() ) {

			// Additional CSS
			wp_enqueue_script( 'id4me-jquery', plugin_dir_url( __DIR__ ) . 'assets/composer/jquery/jquery-3.5.1.min.js' );
			wp_enqueue_style( 'id4me-login', plugin_dir_url( __DIR__ ) . 'assets/css/login.css' );
			wp_enqueue_script( 'id4me-javascript', plugin_dir_url( __DIR__ ) . 'assets/js/login.js' );
		}
	}
}
