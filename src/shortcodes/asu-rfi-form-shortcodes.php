<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;
use ASURFIWordPress\Services as Services;
use ASURFIWordPress\Admin as Admin;


// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Form_Shortcodes extends Hook {
  use \ASURFIWordPress\Options_Handler_Trait;

  private $path_to_views;
  const PRODUCTION_FORM_ENDPOINT  = 'https://requestinfo.asu.edu/routing_form_post';
  const DEVELOPMENT_FORM_ENDPOINT = 'https://requestinfo-qa.asu.edu/routing_form_post';

  public function __construct() {
    parent::__construct( 'asu-rfi-form-shortcodes', ASU_RFI_WORDPRESS_PLUGIN_VERSION );
    $this->path_to_views = __DIR__ . '/../views/';
    $this->define_hooks();
  }

  public function define_hooks() {
    $this->add_action( 'wp_enqueue_scripts', $this, 'wp_enqueue_scripts' );
    $this->add_shortcode( 'asu-rfi-form', $this, 'asu_rfi_form' );
    $this->add_action( 'init', $this, 'setup_rewrites' );
  }

  private function view( $template_name ) {
    return new \Nectary\Factories\View_Factory( $template_name, $this->path_to_views );
  }



  /** Set up any url rewrites:
   * WordPress requires that you tell it that you are using
   * additional parameters.
   */
  public function setup_rewrites() {
    add_rewrite_tag( '%statusFlag%' , '([^&]+)' );
    add_rewrite_tag( '%msg%' , '([^&]+)' );
  }

  /**
   * Enqueue the CSS
   * Hooks onto `wp_enqueue_scritps`.
   */
  public function wp_enqueue_scripts() {
    $url_to_css_file = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/asu-rfi.css';
    wp_enqueue_style( $this->plugin_slug, $url_to_css_file, array(), $this->version );
  }

  /**
   * Handle the shortcode [asu-rfi-form]
   *   attributes:
   *     type = 'full' or leave blank for the default simple form
   *     degree_level = 'ugrad' or 'grad' Default is 'ugrad'
   *     test_mode = 'test' or leave blank for the default production
   *     source_id = integer site identifier (issued by Enrollment services department) will default to site wide setting
   */
  public function asu_rfi_form( $atts, $content = '' ) {
    $view_data = array(
          'form_endpoint' => self::DEVELOPMENT_FORM_ENDPOINT,
          'redirect_back_url' => get_permalink(),
          'source_id' => $value = $this->get_option_attribute_or_default(
              array(
                'name'      => Admin\ASU_RFI_Admin_Page::$options_name,
                'attribute' => Admin\ASU_RFI_Admin_Page::$source_id_option_name,
                'default'   => 0,
              )
          ),
          'testmode' => 'Prod', // default to production mode
          'degreeLevel' => 'ugrad', // default to und
          'enrollment_terms' => Services\ASUDegreeService::get_available_enrollment_terms(),
          'student_types' => Services\StudentTypeService::get_student_types(),
        );

    if ( isset( $atts['test_mode'] ) && 0 === strcasecmp( 'test', $atts['test_mode'] ) ) {
      $view_data['testmode'] = 'Test';
    }

    // Use the attribute source id over the sites option
    if ( isset( $atts['source_id'] ) ) {
      $view_data['source_id'] = intval( $atts['source_id'] );
    }

    // Use the attribute source id over the sites option
    if ( isset( $atts['degree_level'] ) && (
         0 === strcasecmp( 'grad', $atts['degree_level'] ) ||
         0 === strcasecmp( 'graduate', $atts['degree_level'] ) ) ) {
      $view_data['degreeLevel'] = 'grad';
      error_log( 'graduate level engage!' );
    }

    $view_data = $this->look_for_a_submission_response( $view_data );

    // Figure out which form to show
    $view_name = 'rfi-form.simple-request-info-form';
    if ( isset( $atts['type'] ) && 0 === strcasecmp( 'full', $atts['type'] ) ) {
      $view_name = 'rfi-form.form';
    }

    $response = $this->view( $view_name )->add_data( $view_data )->build();
    return $response->content;
  }

  /**
   * Look at the statusFlag and msg query var and return a human readable message that can be used
   */
  private function look_for_a_submission_response( $view_data ) {
    $response_status_code = get_query_var( 'statusFlag' );
    if ( $response_status_code ) {
      $message = get_query_var( 'msg' );
      // we have submitted the request form and should display a success or error message
      if ( 200 === intval( $response_status_code ) ) {
        $view_data['success_message'] = $message ? $message : 'Thank you for submitting';
      } else {
        error_log( 'error submitting ASU RFI (code: ' . $response_status_code . ') : ' . $message );
        $view_data['error_message'] = $message ? $message : 'Something went wrong with your submission';
      }
    }
    return $view_data;
  }

}
