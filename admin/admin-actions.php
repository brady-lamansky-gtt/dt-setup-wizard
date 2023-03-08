<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Setup_Wizard_Actions
 */
class Disciple_Tools_Setup_Wizard_Actions {

    public $token = 'disciple_tools_setup_wizard';

    private static $_instance = null;

    /**
     * Disciple_Tools_Setup_Wizard_Actions Instance
     *
     * Ensures only one instance of Disciple_Tools_Setup_Wizard_Actions is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Setup_Wizard_Actions instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( 'wp_ajax_dt_setup_wizard_ajax', [ $this, 'handle_ajax' ] );
    } // End __construct()


    public function handle_ajax( )
    {
        dt_write_log('handle_ajax_post');
        $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : null;
        $value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : null;
        // $enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

        $response_code = 400;
        $response = [
            'success' => false,
            'message' => '',
        ];
        if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
            $response['message'] = 'Insecure request';
        } else if ( empty( $key ) ) {
            $response['message'] = 'Missing config key';
        } else if ( !current_user_can('manage_dt') ) {
            $response['message'] = 'Insufficient permissions';
        } else {
            switch ($key) {
                case 'plugin:install':
                    $slug = $this->install_plugin($value);

                    $response_code = 200;
                    $response['success'] = true;
                    $response['message'] = 'Plugin installed';
                    $response['slug'] = $slug;
                    break;
                case 'plugin:activate':
                    $result = activate_plugin($value);

                    if (is_wp_error($result)) {
                        $response_code = 500;
                        $response['error'] = $result;
                    } else {
                        $response_code = 200;
                        $response['success'] = true;
                        $response['message'] = 'Plugin activated';
                    }
                    break;
                default:
                    $response['message'] = 'No matching action';
                    dt_write_log("key: $key");
                    break;
            }
        }
        wp_send_json( $response, $response_code );

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    public function install_plugin( $download_url ) {
        set_time_limit( 0 );
        $folder_name = explode( '/', $download_url );
        $plugin_slug = $folder_name[4];
        $folder_name = get_home_path() . 'wp-content/plugins/' . $folder_name[4] . '.zip';
        if ( $folder_name != '' ) {
            //download the zip file to plugins
            file_put_contents( $folder_name, file_get_contents( $download_url ) );
            // get the absolute path to $file
            $folder_name = realpath( $folder_name );
            //unzip
            WP_Filesystem();
            $unzip = unzip_file( $folder_name, realpath( get_home_path() . 'wp-content/plugins/' ) );
            //remove the file
            unlink( $folder_name );

            return $plugin_slug;
        }
    }
}
Disciple_Tools_Setup_Wizard_Actions::instance();
