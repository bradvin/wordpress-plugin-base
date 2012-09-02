<?php
/*
WP PluginBase
A simple base class for WordPress plugins. This class makes it really easy and straight forward to create useful plugins.
Simply inherit from WP_Pluginbase and override the methods of your choice.
Version: 2.0
Author: Brad Vincent
Author URI: http://themergency.com/
License: GPL2

TODO:

extract all overridable functions out into another file somehow

*/

if (!class_exists('wp_pluginbase_v2')) {

    abstract class wp_pluginbase_v2 {
      
        const NOTSET = 'NOT_SET';

        /* overridable variables */
        protected $plugin_slug = self::NOTSET;    //the slug (identifier) of the plugin
        protected $plugin_title = self::NOTSET;   //the friendly title of the plugin
        protected $plugin_version = self::NOTSET; //the version number of the plugin
        protected $has_settings = true;           //does the plugin have a settings page
        
        /* internally used variables */
        protected $plugin_file;                   //the filename of the plugin
        protected $plugin_dir;                    //the folder path of the plugin
        protected $plugin_dir_name;               //the folder name of the plugin
        protected $plugin_url;                    //the plugin url
        protected $mobile = false;                //reference to the mobile object
        
        /* internal private varaibles */
        private $_settings = array();             //the plugin settings array
        private $_settings_sections = array();    //the plugin sections array
        private $_settings_tabs = array();        //the plugin tabs array
        private $_admin_errors = false;           //store of admin errors

        /*
         * Constructor used to set some initial vars.
         * If the subclass makes use of a constructor, make sure the subclass calls parent::__construct()
         */
        function __construct() {
          //check we are using php 5
          $this->check_php_version('5.0.0');

          //use reflection to get the filename of the subclass that is inheriting from WP_PluginBase
          $reflector = new ReflectionObject($this);
          $this->plugin_file = $reflector->getFilename();
          $this->plugin_dir = dirname($this->plugin_file) . '/';
          $this->plugin_dir_name = plugin_basename($this->plugin_dir);
          $this->plugin_url = trailingslashit(plugins_url('', $this->plugin_file));

          //load any plugin base deps
          $this->load_dependancies();

          //get the plugin name from the filename. we assume the name of the plugin is the same as the file
          $this->init();
        }
        
        //load dependancies        
        function load_dependancies() {
          include_once( $this->plugin_dir . 'includes/WPPBUtils.php' );          
          include_once( $this->plugin_dir . 'includes/mobile.php' );
          
          if (class_exists('WPPBMobile')) {
            $this->mobile = new WPPBMobile();
          }
        }
        
        function get_plugin_path() {
          return $this->plugin_dir;
        }

        // check the version of PHP running on the server
        function check_php_version($ver) {
          $php_version = phpversion();
          if (version_compare($php_version, $ver) < 0) {
            throw new Exception("This plugin requires at least version $ver of PHP. You are running an older version ($php_version). Please upgrade!");
          }
        }

        // check the version of WP running
        function check_wp_version($ver) {
          global $wp_version;
          if (version_compare($wp_version, $ver) < 0) {
            throw new Exception("This plugin requires at least version $ver of WordPress. You are running an older version ($php_version). Please upgrade!");
          }
        }
        
        function check_plugin_vars() {
          if ($this->plugin_slug === self::NOTSET) {
            throw new Exception("Required plugin variable not set : 'plugin_slug'. Please set this in the init() function of your plugin.");
          }
          if ($this->plugin_title === self::NOTSET) {
            throw new Exception("Required plugin variable not set : 'plugin_title'. Please set this in the init() function of your plugin.");
          }
          if ($this->plugin_version === self::NOTSET) {
            throw new Exception("Required plugin variable not set : 'plugin_version'. Please set this in the init() function of your plugin.");
          }          
        }

        // initialize the plugin here!
        function init() {
          
          //check we have set our plugin variables!
          $this->check_plugin_vars();
          
          //set some default hooks for the WP admin
          if (is_admin()) {
            // Dashboard stuff
            add_action("wp_dashboard_setup", array(&$this, "admin_dashboard") );

            // Register options for the plugin
            add_action('admin_init', array(&$this, "admin_settings_init"));

            // Render CSS to the admin pages
            add_action('admin_print_styles', array(&$this, "admin_print_styles") );

            // Render JS to the admin pages
            add_action('admin_print_scripts',  array(&$this, "admin_print_scripts") );

            // Add or alter any admin menus
            add_action('admin_menu', array(&$this, "admin_add_menus"));

            add_action('admin_notices', array(&$this, "admin_show_messages"));
            
            
            add_filter('plugin_action_links_'.plugin_basename( $this->plugin_file ), array(&$this, 'admin_plugin_actions'), -10);
            
            add_filter('plugin_row_meta', array(&$this, 'plugin_row_meta'), 10, 2 );            

            $this->admin_init();

          } else {
            //register any shortcodes for the plugin
            $this->register_shortcodes();

            // Render JS to the front-end pages
            add_action('wp_enqueue_scripts', array( &$this, 'frontend_print_scripts'), 20 );

            // Render CSS to the front-end pages
            add_action('wp_print_styles', array(&$this, 'frontend_print_styles'));

            $this->frontend_init();
          }
        }
        
        function check_admin_settings_page() {
          return is_admin() && array_key_exists('page', $_GET) && ($_GET['page'] == $this->plugin_slug);
        }
        
        /**
         * gets the current post type in the WordPress Admin
         */
        function get_current_post_type() {
          global $get_current_post_type, $post, $typenow, $current_screen;
          
          if ($get_current_post_type)
            return $get_current_post_type;
          
          //we have a post so we cna just get the post type from that
          if ( $post && $post->post_type )
            $get_current_post_type = $post->post_type;
            
          //check the global $typenow - set in admin.php
          elseif( $typenow )
            $get_current_post_type = $typenow;
            
          //check the global $current_screen object - set in sceen.php
          elseif( $current_screen && $current_screen->post_type )
            $get_current_post_type = $current_screen->post_type;
          
          //lastly check the post_type querystring
          elseif( isset( $_REQUEST['post_type'] ) )
            $get_current_post_type = sanitize_key( $_REQUEST['post_type'] );
          
          return $get_current_post_type;
        }
        
        // register and enqueue a script
        function register_and_enqueue_js($file, $d = array(), $v = false, $f = false) {
          if ($v === false) {
            $v = $this->plugin_version;
          }

          $js_src_url = $file;
          if ( ! WPPBUtils::str_contains($file, '://') ) {
            $js_src_url = $this->plugin_url . 'js/' . $file;
          }            
          $h = str_replace('.', '-', pathinfo( $file, PATHINFO_FILENAME ) );

          wp_register_script(
                  $handle = $h,
                  $src = $js_src_url,
                  $deps = $d,
                  $ver = $v,
                  $in_footer = $f);

          wp_enqueue_script($h);
        }        

        function admin_print_script_if_exists($js_file) {
          
          if (file_exists($this->plugin_dir . 'js/' . $js_file)) {
            
            $this->register_and_enqueue_js($js_file, array('jquery'));
            
          }
        }        

        // enqueue the admin scripts
        function admin_print_scripts() {
          
          //add a general admin script
          $this->admin_print_script_if_exists( 'admin.js' );
          
          //if we are on the current plugin's settings page then check for file named /js/admin-settings.js
          if ( $this->check_admin_settings_page() ) {
            $this->admin_print_script_if_exists( 'admin-settings.js' );
            
            //check if we are using an upload setting and add media uploader scripts
            if ( $this->has_setting_of_type('image')) {
              wp_enqueue_script('media-upload');
              wp_enqueue_script('thickbox');
              $this->register_and_enqueue_js('admin-uploader.js', array('jquery','media-upload','thickbox'));
            }
            
            if ( $this->has_setting_of_type('license')) {
              $this->register_and_enqueue_js('admin-license-check.js', array('jquery'));
            }
          }
          
          //add any scripts for the current post type
          $post_type = $this->get_current_post_type();
          if (!empty($post_type)) {
            $this->admin_print_script_if_exists( 'admin-'.$post_type.'.js' );
          }
        }

        // register and enqueue a CSS
        function register_and_enqueue_css($file, $d = false, $v = false) {
          if ($v === false) {
            $v = $this->plugin_version;
          }

          $css_src_url = $file;
          if ( ! WPPBUtils::str_contains($file, '://') ) {
            $css_src_url = $this->plugin_url . 'css/' . $file;
          }

          $h = str_replace('.', '-', pathinfo( $file, PATHINFO_FILENAME ) );

          wp_register_style(
                  $handle = $h,
                  $src = $css_src_url,
                  $deps = $d,
                  $ver = $v);

          wp_enqueue_style($h);
        }        

        // regsiter the admin stylesheets
        function admin_print_styles() {
          
          //add a general admin stylesheet
          $this->admin_print_style_if_exists( 'admin.css' );          
          
          //if we are on the current plugin's settings page then check for file named /css/admin-settings.css
          if ( $this->check_admin_settings_page() ) {
            $this->admin_print_style_if_exists( 'admin-settings.css' );
            
            //Media Uploader Style
            wp_enqueue_style('thickbox');
          }
          
          //add any scripts for the current post type
          $post_type = $this->get_current_post_type();
          if (!empty($post_type)) {
            $this->admin_print_style_if_exists( 'admin-'.$post_type.'.css' );
          }
        }
        
        function admin_print_style_if_exists($css_file) {
          if (file_exists($this->plugin_dir . 'css/' . $css_file)) {
            
            $this->register_and_enqueue_css($css_file);
            
          }
        }          
        
        function admin_add_error_message($message, $session = false) {
          if ($session && isset($_SESSION)) {
            //save the errors to the sessions;
            $errors = $_SESSION[$this->plugin_slug . '_admin_errors'];
            //add the error
            $errors[] = $message;
            //save it back to the session
            $_SESSION[$this->plugin_slug . '_admin_errors'] = $errors;
          } else {
            //store it normally
            $this->_admin_errors[] = $message;
          }
        }
        
        function admin_show_messages() {
          
          if (isset($_SESSION)) {
          
            //first check the session
            $session_error_messages = $_SESSION[$this->plugin_slug . '_admin_errors'];
            if (is_array($session_error_messages)) {
              foreach ( $session_error_messages as $error ) {
                $this->admin_show_message($message, true);
              }
              
              //now clear the session
              $_SESSION[$this->plugin_slug . '_admin_errors'] = null;
            }
          
          }
          
          if (is_array($this->_admin_errors)) {
            foreach ( $this->_admin_errors as $error ) {
              $this->admin_show_message($message, true);
            }
          }
        }
        
        function admin_show_message($message, $error = false) {
          if ($error) {
            echo '<div id="message" class="error">';
          } else {
            echo '<div id="message" class="updated fade">';
          }

          echo "<p><strong>$message</strong></p></div>";
        }

        // register any options/settings we may want to store for this plugin
        function admin_settings_init() { }
        
        //check if we have any setting of a certain type
        function has_setting_of_type($type) {
          foreach ($this->_settings as $setting) {
            if ($setting['type'] == $type) return true;
          }
          
          return false;
        }

        // add a setting tab
        function admin_settings_add_tab( $tab_id, $title) {
          if (!array_key_exists($tab_id, $this->_settings_tabs)) {
            
            //pre action
            do_action($this->plugin_slug.'_pre_tab', $tab_id);
            
            $tab = array(
              'id'      => $tab_id,
              'title'   => $title
            );

            $this->_settings_tabs[$tab_id] = $tab;
            
            //post action
            do_action($this->plugin_slug.'_post_tab', $tab_id);
          }
        }

        // add a setting section
        function admin_settings_add_section( $section_id, $title, $desc='' ) {
          
          //check we have the section
          if (!array_key_exists($section_id, $this->_settings_sections)) {
            
            //pre action
            do_action($this->plugin_slug.'_pre_section', $section_id);            
            
            $section = array(
                'id'	=> $section_id,
                'title'	=> $title,
                'desc'	=> $desc
            );

            $this->_settings_sections[$section_id] = $section;

            $section_callback = create_function('',
                    'echo "' . $desc . '";');

            add_settings_section($section_id, $title, $section_callback, $this->plugin_slug);
            
            //post action
            do_action($this->plugin_slug.'_post_section', $section_id);       
          }
        }
        
        function admin_settings_add_section_to_tab( $tab_id, $section_id, $title, $desc='' ) {
          if (array_key_exists($tab_id, $this->_settings_tabs)) {
              
            //get the correct section id for the tab
            $section_id = $tab_id . '-' . $section_id;

            //add the section to the tab
            if (!array_key_exists($section_id, $this->_settings_sections)) {
              $this->_settings_tabs[$tab_id]['sections'][$section_id] = $section_id;
            }
            
            //add the section
            $this->admin_settings_add_section($section_id, $title, $desc);
            
          }
          return $section_id;
        }

        // add a settings field
        function admin_settings_add( $args = array() ) {

          $defaults = array(
              'id'          => 'default_field',
              'title'       => 'Default Field',
              'desc'        => '',
              'default'     => '',
              'placeholder' => '',
              'type'        => 'text',
              'section'     => '',
              'choices'     => array(),
              'class'       => '',
              'tab'         => '',
              'update_url'  => ''
          );

          extract( wp_parse_args( $args, $defaults ) );

          $field_args = array(
              'type'        => $type,
              'id'          => $id,
              'desc'        => $desc,
              'default'     => $default,
              'placeholder' => $placeholder,
              'choices'     => $choices,
              'label_for'   => $id,
              'class'       => $class,
              'update_url'  => $update_url
          );

          if (count($this->_settings) == 0){
              //only do this once
              register_setting($this->plugin_slug, $this->plugin_slug, array(&$this, 'admin_settings_validate'));
          }

          $this->_settings[] = $args;

          $section_id = WPPBUtils::to_key($section);

          //check we have the tab
          if (!empty($tab)) {
              $tab_id = WPPBUtils::to_key($tab);

              //add the tab
              $this->admin_settings_add_tab( $tab_id, WPPBUtils::to_title($tab) );
              
              //add the section
              $section_id = $this->admin_settings_add_section_to_tab( $tab_id, $section_id, WPPBUtils::to_title($section) );
          }
          else 
          {
            //just add the section
            $this->admin_settings_add_section($section_id, WPPBUtils::to_title($section));
          }

          //add the setting!
          add_settings_field( $id, $title, array(&$this, 'admin_settings_render'), $this->plugin_slug, $section_id, $field_args );
        }

        // render HTML for individual settings
        function admin_settings_render( $args = array() ) {

          extract( $args );

          $options = get_option( $this->plugin_slug );

          if ( !isset( $options[$id] ) && $type != 'checkbox' )
              $options[$id] = $default;

          $field_class = '';
          if ( $class != '' )
              $field_class = ' class="' . $class . '"';

          $errors = get_settings_errors($id);

          switch ( $type ) {

            case 'heading':
              echo '</td></tr><tr valign="top"><td colspan="2">' . $desc;
              break;
            
            case 'html':
              echo $desc;
              break;

            case 'checkbox':
              $checked = '';
              if ( isset( $options[$id] ) && $options[$id] == 'on' ) {
                $checked = ' checked="checked"';
              } else if ( $options === false && $default == 'on') {
                $checked = ' checked="checked"';
              }
              
              //echo '<input type="hidden" name="'.$this->plugin_slug.'[' . $id . '_default]" value="' . $default . '" />';
              echo '<input' . $field_class . ' type="checkbox" id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" value="on"' . $checked . ' /> <label for="' . $id . '"><small>' . $desc . '</small></label>';

              break;

            case 'select':
              echo '<select' . $field_class . ' name="'.$this->plugin_slug.'[' . $id . ']">';

              foreach ( $choices as $value => $label ) {
                  $selected = '';
                  if ( $options[$id] == $value )
                      $selected = ' selected="selected"';
                  echo '<option '.$selected.' value="' . $value . '">' . $label . '</option>';
              }

              echo '</select>';

              break;

            case 'radio':
              $i = 0;
              $saved_value = $options[$id];
              if (empty($saved_value)) { $saved_value = $default; }
              foreach ( $choices as $value => $label ) {
                  $selected = '';
                  if ( $saved_value == $value )
                      $selected = ' checked="checked"';
                  echo '<input' . $field_class . $selected . ' type="radio" name="'.$this->plugin_slug.'[' . $id . ']" id="' . $id . $i . '" value="' . $value . '"> <label for="' . $id . $i . '">' . $label . '</label>';
                  if ( $i < count( $choices ) - 1 )
                      echo '<br />';
                  $i++;
              }

              break;

            case 'textarea':
              echo '<textarea' . $field_class . ' id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" placeholder="' . $placeholder . '">' . esc_attr($options[$id]) . '</textarea>';

              break;

            case 'password':
              echo '<input' . $field_class . ' type="password" id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" value="' . esc_attr($options[$id]) . '" />';

              break;
            
            case 'license':
              
              echo '<input class="regular-text license-input '.$class.'" type="text" id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr($options[$id]) . '" />';
              echo '<input class="license-check-button" type="button" name="license_button" value="' . __('Validate', $this->plugin_slug) . '" />';
              echo '<input class="license-check-url" type="hidden" value="' . $update_url . '" />';
              echo '<input class="license-check-site" type="hidden" value="' . home_url() . '" />';
              
              echo '<div style="display:none" class="license-message"></div>';
              
              break;

            case 'text':
              echo '<input class="regular-text '.$class.'" type="text" id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr($options[$id]) . '" />';

              break;

            case 'checkboxlist':
              $i = 0;
              foreach ( $choices as $value => $label ) {

                $checked = '';
                if ( isset($options[$id][$value]) && $options[$id][$value] == 'true') {
                  $checked = 'checked="checked"';
                }

                echo '<input' . $field_class . ' ' . $checked . ' type="checkbox" name="'.$this->plugin_slug.'[' . $id . '|' . $value . ']" id="' . $id . $i . '" value="on"> <label for="' . $id . $i . '">' . $label . '</label>';
                if ( $i < count( $choices ) - 1 )
                  echo '<br />';
                $i++;
              }

              //echo '<div style="display:none"><input type="checkbox" checked="checked" name="'.$this->plugin_slug.'[' . $id . '|_none_]" id="' . $id . '_none" value="on"></div>';

              break;
            case 'image':
              echo '<input class="regular-text image-upload-url" type="text" id="' . $id . '" name="'.$this->plugin_slug.'[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr($options[$id]) . '" />';
              echo '<input id="st_upload_button" class="image-upload-button" type="button" name="upload_button" value="' . __('Select Image', $this->plugin_slug) . '" />';
              break;
            
            default:
              $this->custom_admin_settings_render($args);

              break;
          }

          if (is_array($errors)) {
            foreach ( $errors as $key => $details ) {
              echo "<span class='error'>{$details['message']}</span>";
            }
          }

          if ( $type != 'checkbox' && $type != 'heading' && $type != 'html' && $desc != '' )
            echo '<br /><small>' . $desc . '</small>';
        }

        function custom_admin_settings_render( $args = array() ) {
          
        }

        // validate our settings
        function admin_settings_validate($input) {

          //check to see if the options were reset
          if ( isset ( $input['reset-defaults'] ) ) {
            delete_option( $this->plugin_slug );
            add_settings_error(
              'reset',
              'reset_error',
              __('Settings restored to default values', $this->plugin_slug),
              'updated'
            );            
            return false;
          }
          
          
          
          
//            if (empty($input['sample_text'])) {
//
//                add_settings_error(
//                    'sample_text',           // setting title
//                    'sample_text_error',            // error ID
//                    'Please enter some sample text',   // error message
//                    'error'                        // type of message
//                );
//
//            }

          foreach ($this->_settings as $setting) {
            $this->admin_settings_validate_item( $setting, $input );
          }

          return $input;
        }

        function admin_settings_validate_item( $setting, &$input ) {
          //validate a single setting

          if ($setting['type'] == 'checkboxlist') {

            unset($checkboxarray);
            $check_values = array();
            foreach ($setting['choices'] as $value => $label ) {
              if ( !empty( $input[$setting['id'] . '|' . $value] ) ) {
                  // If it's not null, make sure it's true, add it to an array
                  $checkboxarray[$value] = 'true';
              }
              else {
                  $checkboxarray[$value] = 'false';
              }
            }

            if (!empty($checkboxarray)) {
              $input[$setting['id']] = $checkboxarray;
            }
            
          }
        }
        
        // override to register custom admin menus
        function admin_add_menus() {
          $this->admin_settings_add_menu();
        }
        
        // Add the 'Settings' link to the plugin page
        function admin_plugin_actions($links) {
          if ($this->has_settings) {
            $links[] = '<a href="options-general.php?page='.$this->plugin_slug.'"><b>Settings</b></a>';
          }
          return $links;
        }
        
        function plugin_row_meta( $links, $file ) {
          $plugin = plugin_basename($this->plugin_file);
          if ( $file == $plugin ) {
            $links = $this->admin_plugin_row_meta( $links );
          }
          return $links;
        }
        
        function admin_plugin_row_meta( $links ) {
          //change links here in an override
          return $links;
        }

        // add a settings admin menu
        function admin_settings_add_menu() {
          if ($this->has_settings) {
            $settings_title = $this->get_settings_title();
            add_options_page($settings_title, $settings_title,
                      'manage_options', $this->plugin_slug, array(&$this, "admin_settings_render_page"));
          }
        }

        // render the setting page
        function admin_settings_render_page() {
          //check if an settings.php file exists and if so, include it
          if (file_exists($this->plugin_dir . "settings.php")) {
            include_once($this->plugin_dir . "settings.php");
          } else {
            //render the settings using our default page
            include_once($this->plugin_dir . "includes/wp_pluginbase_v2_default_settings.php");

            $settings_summary = $this->apply_filters('settings_summary', '');
            $settings_title = $this->get_settings_title();
            
            wp_pluginbase_v2_default_settings::render( $settings_title, $settings_summary, $this->plugin_slug, $this->_settings_tabs);
          }
        }
        
        function get_settings_title(){
          $default_title = $this->plugin_title . __(' Settings', $this->plugin_slug);
          
          return $this->apply_filters('settings_title', $default_title);
        }
        
        function apply_filters($tag, $value) {
          if (!WPPBUtils::starts_with($tag, $this->plugin_slug))
            $tag = $this->plugin_slug.'-'.$tag;
          
          return apply_filters($tag, $value);
        }

        // make any changes to the admin dashboard here
        function admin_dashboard() { }
        
        function admin_init() { }

        //override to register custom frontend CSS
        function frontend_print_styles() { }
        
        //override to register custom frontend JS
        function frontend_print_scripts() { }

        function frontend_init() { }

        // register any custom shortcodes
        function register_shortcodes() { }

        // get a value using the transient API by the key
        // if the key does not exist, then call the function to get the value and store that
        function get_transient($key, $expiration, $function, $args = array()) {
          if ( false === ( $value = get_transient( $key ) ) ) {

            //nothing found, call the function
            $value = call_user_func_array( $function, $args );

            //store the transient
            set_transient( $key, $value, $expiration);

          }

          return $value;
        }

        // returns the current URL
        function current_url() {
          global $wp;
          $current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
          return $current_url;
        }

        // returns the current page name
        function current_page_name() {
          return basename($_SERVER['SCRIPT_FILENAME']);
        }

        // save a WP option for the plugin. Stores and array of data, so only 1 option is saved for the whole plugin to save DB space and so that the options table is not poluted
        function save_option($key, $value) {
          $options = get_option( $this->plugin_slug );
          if (!options) {
            //no options have been saved for this plugin
            add_option($this->plugin_slug, array($key => $value));
          } else {
            $options[$key] = $value;
            update_option($this->plugin_slug, $options);
          }
        }

        //get a WP option value for the plugin
        function get_option($key, $default = false) {
          $options = get_option( $this->plugin_slug );
          if ($options) {
            return ( array_key_exists($key, $options) ) ? $options[$key] : $default;
          }

          return $default;
        }
        
        function is_option_checked($key, $default = false) {
          $options = get_option( $this->plugin_slug );
          if ($options) {
            return array_key_exists($key, $options);
          }
          
          return $default;
        }

        function delete_option($key) {
          $options = get_option( $this->plugin_slug );
          if (options) {
            unset($options[$key]);
            update_option($this->plugin_slug, $options);
          }
        }
        
        function safe_get($array, $key, $default = NULL) {
          if (!is_array($array)) return $default;
          $value = array_key_exists($key, $array) ? $array[$key] : NULL;
          if ($value === NULL)
            return $default;

          return $value;
        }
    }
}