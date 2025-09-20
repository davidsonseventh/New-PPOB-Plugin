<?php

class WPPOB_Login_Core {

    public function run() {
        $this->load_dependencies();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    private function load_dependencies() {
        require_once WP_PPOB_LOGIN_PLUGIN_DIR . 'includes/class-wppob-fonnte-api.php';
        require_once WP_PPOB_LOGIN_PLUGIN_DIR . 'includes/class-wppob-login-form-handler.php';
        new WPPOB_Login_Form_Handler();
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'wppob-login-style', WP_PPOB_LOGIN_PLUGIN_URL . 'assets/css/style.css', array(), '1.0', 'all' );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'wppob-login-script', WP_PPOB_LOGIN_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'wppob-login-script', 'wppob_login_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'wppob-dashboard',
            'Pengaturan Login',
            'Pengaturan Login',
            'manage_options',
            'wppob-login-settings',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'wppob_login_settings_group', 'wppob_fonnte_api_key' );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Pengaturan Login PPOB</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'wppob_login_settings_group' ); ?>
                <?php do_settings_sections( 'wppob_login_settings_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Fonnte API Key</th>
                        <td><input type="text" name="wppob_fonnte_api_key" value="<?php echo esc_attr( get_option('wppob_fonnte_api_key') ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
