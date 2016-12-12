<?php
namespace ASURFIWordPress\Admin;
use Honeycomb\Wordpress\Hook;
// use ASURFIWordPress;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Admin_Page extends Hook {
  use \ASURFIWordPress\Options_Handler_Trait;

  public static $options_name = 'asu-rfi-options';
  public static $options_group = 'asu-rfi-options_group';
  public static $source_id_option_name = 'source_id';
  public static $section_id = 'asu-rfi-section_id';
  public static $section_name = 'asu-rfi-section_name';
  public static $page_name = 'asu-rfi-admin-page';

 public function __construct( $version = '0.1' ) {
    parent::__construct( $version );

    $this->add_action( 'admin_menu', $this, 'admin_menu' );
    $this->add_action( 'admin_init', $this, 'admin_init' );

    // Set default options
    add_option(
        self::$options_name,
        array(
          self::$source_id_option_name => 0,
        )
    );

    $this->define_hooks();
  }


  /**
   * Add filters and actions
   *
   * @override
   */
  public function define_hooks() {
    $this->add_action( 'admin_init', $this, 'admin_init' );
  }

  /**
   * Set up administrative fields
   */
  public function admin_init() {
    register_setting(
        self::$options_group,
        self::$options_name,
        array( $this, 'form_submit' )
    );

    add_settings_section(
        self::$section_id,
        'ASU RFI Settings',
        array(
          $this,
          'print_section_info',
        ),
        self::$section_name
    );

    add_settings_field(
        self::$source_id_option_name,
        'Site Source Identifier',
        array(
          $this,
          'source_id_on_callback',
        ), // Callback
        self::$section_name,
        self::$section_id
    );
  }

  public function admin_menu() {
    $page_title = 'ASU RFI Plugin Settings';
    $menu_title = 'ASU RFI';
    $capability = 'manage_options';
    $path = plugin_dir_url( __FILE__ );

    add_options_page(
        'Settings Admin',
        'ASU RFI Form',
        $capability,
        self::$page_name,
        array( $this, 'render_admin_page' )
    );

  }

  public function render_admin_page() {
    ?>
    <div class="wrap">
        <h1>ASU Request For Information Form Settings</h1>
        <form method="post" action="options.php">
        <?php
            // This prints out all hidden setting fields
            settings_fields( self::$options_group );
            do_settings_sections( self::$section_name );
            submit_button();
        ?>
        </form>
    </div>
    <?php
  }


  /**
   * Print the section text
   */
  public function print_section_info() {
    print 'Enter your settings below:';
  }

  /**
   * Print the form section for the research group slugs
   */
  public function source_id_on_callback() {

    $value = $this->get_option_attribute_or_default(
        array(
          'name'      => self::$options_name,
          'attribute' => self::$source_id_option_name,
          'default'   => '',
        )
    );

    $html = <<<HTML
    <input type="text" id="%s" name="%s[%s]" value="%s"/><br/>
    <em>Source Identifiers are granted by the <a href="mailto:ecomm@asu.edu
">ASU Enrollment Services Department</a>, please contact them to uptain a source_id for your college or department.</em>
HTML;

    printf(
        $html,
        self::$source_id_option_name,
        self::$options_name,
        self::$source_id_option_name,
        $value
    );

  }

  /**
   * Handle form submissions for validations
   */
  public function form_submit( $input ) {
    // intval the source_id_option_name
    if ( isset( $input[ self::$source_id_option_name ] ) ) {
      $input[ self::$source_id_option_name ] = intval( $input[ self::$source_id_option_name ] );
    }
    return $input;
  }

}
