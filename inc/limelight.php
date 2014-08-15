<?php
/**
 * Limelight
 *
 * @package   limelight
 * @author    7/Apps <ryan@7apps.com>
 * @license   GPL-2.0+
 * @link      http://www.7apps.com
 * @copyright 7-30-2014 7/Apps
 */

/**
 * Limelight class.
 *
 * @package Limelight
 * @author  7/Apps <ryan@7apps.com>
 */
class Limelight {
    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @var     string
     */
    protected $version = "1.0.7";

    /**
     * Unique identifier for your plugin.
     *
     * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
     * match the Text Domain file header in the main plugin file.
     *
     * @var      string
     */
    public static $plugin_slug = "limelight";

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = null;

    /**
     * Key used for encrypting and decrypting passwords
     *
     * @var      string
     */
    public static $crypt_key = 'aJ5p2Yqd4Ri9wLjN';

    /*
     * Encrypt $string using $key
     */
    public static function encrypt_string( $string, $key ) {

        return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $key ), $string, MCRYPT_MODE_CBC, md5( md5( $key ) ) ) );
    }

    /*
     * Decrypt $string using $key
     */
    public static function decrypt_string( $string, $key ) {

        return rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $key ), base64_decode( $string ), MCRYPT_MODE_CBC, md5( md5( $key ) ) ), "\0" );
    }

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     */
    private function __construct() {

        // Load plugin text domain
        add_action("init", array($this, "load_plugin_textdomain"));

        // Add the options page and menu item.
        add_action("admin_menu", array($this, "add_admin_menu_pages"));

        // Load admin style sheet and JavaScript.
        add_action("admin_enqueue_scripts", array($this, "enqueue_admin_styles"));
        add_action("admin_enqueue_scripts", array($this, "enqueue_admin_scripts"));

        // Load public-facing style sheet and JavaScript.
        add_action("wp_enqueue_scripts", array($this, "enqueue_styles"));
        add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));

        // Define custom functionality.
        // Read more about actions and filters:
        // http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
        //
        // add_action("TODO", array($this, "action_method_name"));
        // add_filter("TODO", array($this, "filter_method_name"));

        $options = get_option('limelight_options');
        if ($options['verified']) {
            add_action("gform_after_submission", array($this, 'gform_after_submission'), 10, 2);
            add_action("gform_after_update_entry", array($this, 'gform_after_update_entry'), 10, 2);
            add_action("gform_delete_lead", array($this, 'gform_delete_lead'), 10, 1);
        }

        add_action('admin_init', array($this, 'limelight_admin_init'));

    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn"t been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @param    boolean $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
     */
    public static function activate($network_wide) {

        // Check for compatibility
        try {
            // check mycrypt
            if(!function_exists('mcrypt_encrypt')) {
                throw new Exception(__('Please enable \'php_mycrypt\' in PHP. It is needed to encrypt passwords.', self::$plugin_slug));
            }
        }
        catch(Exception $e) {
            deactivate_plugins($plugin_basename.'/backup.php', true);
            echo '<div id="message" class="error">' . $e->getMessage() . '</div>';
            trigger_error('Could not activate Limelight.', E_USER_ERROR);
        }

        // Default options
        $limelight_options = array (
            'endpoint' => 'http://events.7apps.io',
            'username' => '',
            'password' => '',
            'verified' => false
        );
        add_option('limelight_options', $limelight_options);

        LimelightModel::setup_database();
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @param    boolean $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
     */
    public static function deactivate($network_wide) {
        // TODO: Define deactivation functionality here
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {

        $domain = $this::$plugin_slug;
        $locale = apply_filters("plugin_locale", get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . "/" . $domain . "/" . $domain . "-" . $locale . ".mo");
        load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . "/lang/");
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen->id == $this->plugin_screen_hook_suffix) {
            wp_enqueue_style($this::$plugin_slug . "-admin-styles", plugins_url("../css/admin.css", __FILE__), array(),
                $this->version);
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen->id == $this->plugin_screen_hook_suffix) {
            wp_enqueue_script($this::$plugin_slug . "-admin-script", plugins_url("../js/limelight-admin.js", __FILE__),
                array("jquery"), $this->version);
        }

    }

    /**
     * Register and enqueue public-facing style sheet.
     */
    public function enqueue_styles() {

        wp_enqueue_style($this::$plugin_slug . "-plugin-styles", plugins_url("../css/public.css", __FILE__), array(),
            $this->version);
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     */
    public function enqueue_scripts() {

        wp_enqueue_script($this::$plugin_slug . "-plugin-script", plugins_url("../js/public.js", __FILE__), array("jquery"),
            $this->version);
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_admin_menu_pages() {

        add_menu_page(__("Limelight", $this::$plugin_slug), __("Limelight", $this::$plugin_slug) , "read", $this::$plugin_slug, false, false, '66');

        // Adding submenu pages
        $this->plugin_screen_hook_suffix = add_submenu_page($this::$plugin_slug, __("Limelight - Settings", $this::$plugin_slug), __("Settings", $this::$plugin_slug), "read", $this::$plugin_slug, array($this, "display_settings_page"));

        // add_submenu_page($this::$plugin_slug, __("Limelight - Attendees", $this::$plugin_slug), __("Attendees", $this::$plugin_slug), "read", $this::$plugin_slug."_attendees", array($this, "display_attendees_page"));

    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_settings_page() {

        if ( isset($_GET['id']) && is_numeric($_GET['id']) ) {
            if ( isset($_POST['submit']) ) {
                $this->process_edit_form_page();
            }

            include_once(PLUGIN_DIR . "views/admin-edit-form.php");
        } else {
            if ( isset($_POST['submit']) ) {
                $this->process_settings_page();
            }

            include_once(PLUGIN_DIR . "views/admin-settings.php");
        }
    }

    /**
     * Process the settings page for this plugin.
     */
    private function process_settings_page() {

        $res = LimelightAPI::make_api_request('GET', 'verify', false, $_POST['limelight_options']);

        $options = get_option('limelight_options');
        $options['endpoint'] = $_POST['limelight_options']['endpoint'];
        $options['username'] = $_POST['limelight_options']['username'];
        $options['password'] = $_POST['limelight_options']['password'];
        $options['verified'] = ($res === false) ? false : true;
        update_option('limelight_options', $options);
    }

    /**
     * Process the edit event page for this plugin.
     */
    private function process_edit_form_page() {

        $form_id = $_GET['id'];

        $settings = new stdClass();
        $settings->event_id = $_POST['event_id'];
        $settings->action   = $_POST['action'];

        if ( isset($_POST['inputs']) ) {
            $settings->inputs = $_POST['inputs'];
        }

        LimelightModel::update_form_settings($form_id, $settings);
    }

    /**
     * Render the attendees page for this plugin.
     */
    public function display_attendees_page() {

        include_once(PLUGIN_DIR . "views/admin-attendees.php");
    }

    /**
     * Register settings, add sections and fields
     */
    public function limelight_admin_init() {

        register_setting('limelight_options', 'limelight_options', array($this, 'limelight_options_validate') );
        add_settings_section('limelight_main', __( 'Settings', $this::$plugin_slug ), array($this, 'limelight_section'), 'limelight');
        add_settings_field('endpoint', __( 'Endpoint', $this::$plugin_slug ), array($this, 'limelight_endpoint'), 'limelight', 'limelight_main');
        add_settings_field('username', __( 'Username', $this::$plugin_slug ), array($this, 'limelight_username'), 'limelight', 'limelight_main');
        add_settings_field('password', __( 'Password', $this::$plugin_slug ), array($this, 'limelight_password'), 'limelight', 'limelight_main');
    }

    public function limelight_section() {

        echo '<p>' . __( 'Please enter your API connection details.', $this::$plugin_slug ) . '</p>';
    }

    public function limelight_endpoint() {

        $options = get_option('limelight_options');
        echo "<input id='endpoint' name='limelight_options[endpoint]' type='text' class='regular-text' value='{$options['endpoint']}' />";
    }

    public function limelight_username() {

        $options = get_option('limelight_options');
        echo "<input id='username' name='limelight_options[username]' type='text' class='regular-text' value='{$options['username']}' />";
    }

    public function limelight_password() {

        $options = get_option('limelight_options');
        $placeholder = '';
        if ( $options['password'] )
            $placeholder = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
        echo "<input id='password' name='limelight_options[password]' type='password' class='regular-text' value='' placeholder='{$placeholder}' />";
    }

    public function limelight_options_validate($input) {

        $limelight_options = get_option('limelight_options');

        $input['username'] = stripslashes(wp_filter_kses(addslashes(strip_tags($input['username']))));
        if ($input['username'] == '')
            $input['username'] = $limelight_options['username'];

        $input['password'] = stripslashes(wp_filter_kses(addslashes(strip_tags($input['password']))));
        if ($input['password'] == '')
            $input['password'] = $limelight_options['password'];
        else
            $input['password'] = $this::encrypt_string( $input['password'], $this::$crypt_key );

        return $input;
    }

    public static function get_event_name($id) {

        $form_settings = LimelightModel::get_form_settings($id);
        if (isset($form_settings->event_id) && is_numeric($form_settings->event_id)) {
            $event = LimelightAPI::get_event($form_settings->event_id);
            return $event->name;
        } else {
            return '<span class="required">Not Connected</span>';
        }
    }

    public static function get_action_type($id) {

        $form_settings = LimelightModel::get_form_settings($id);
        if (isset($form_settings->action)) {
            return LimelightAPI::$api_actions[ $form_settings->action ];
        } else {
            return '';
        }
    }

    /**
     * Translate GravityForms entry data into API POST fields
     */
    public static function get_field_data($entry, $form) {

        $form_settings = LimelightModel::get_form_settings($form['id']);

        $e2i = array_flip( get_object_vars($form_settings->inputs) );

        $fields = array();
        $fields['event_id'] = $form_settings->event_id;
        $fields['action']   = $form_settings->action;

        foreach ($form['fields'] as $field)
        {
            $field_id = $field['id'];

            if (!is_null($field['inputs']) && count($field['inputs']))
            {
                foreach ($field['inputs'] as $field_input)
                {
                    $fields['formdata['. $e2i[$field_id] .'][]'] = urlencode( $entry[$field_input['id']] );
                }
            }
            else if ($field['type'] == 'radio')
            {
                $fields['formdata['. $e2i[$field_id] .'][]'] = urlencode( $entry[$field_id] );
            }
            else
            {
                $fields['formdata['. $e2i[$field_id] .']'] = urlencode( $entry[$field_id] );
            }
        }

        return $fields;
    }

    /**
     * Add Limelight Attendee
     */
    public function gform_after_submission($entry, $form) {

        $fields = self::get_field_data($entry, $form);

        $attendee = LimelightAPI::add_attendee($fields);
        gform_update_meta($entry['id'], 'll_attendee_id', $attendee->id);
    }

    /**
     * Update Limelight Attendee
     */
    public function gform_after_update_entry($form, $entry_id) {

        $entry = RGFormsModel::get_lead($entry_id);

        $fields = self::get_field_data($entry, $form);

        $attendee_id = gform_get_meta($entry['id'], 'll_attendee_id');

        if ( $attendee_id != false && is_numeric($attendee_id) )
        {
            $res = LimelightAPI::update_attendee($attendee_id, $fields);
        }
        else
        {
            $attendee = LimelightAPI::add_attendee($fields);
            gform_update_meta($entry['id'], 'll_attendee_id', $attendee->id);
        }
    }

    /**
     * Delete Limelight Attendee
     */
    public function gform_delete_lead($entry_id) {

        $entry = RGFormsModel::get_lead($entry_id);
        $attendee_id = gform_get_meta($entry['id'], 'll_attendee_id');

        if ( $attendee_id != false && strlen($attendee_id) ) {
            $res = LimelightAPI::delete_attendee($attendee_id);
        }
    }

    /**
     * NOTE:  Filters are points of execution in which WordPress modifies data
     *        before saving it or sending it to the browser.
     *
     *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
     *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
     */
    public function filter_method_name() {
        // TODO: Define your filter hook callback here
    }

}
