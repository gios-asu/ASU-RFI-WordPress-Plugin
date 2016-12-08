<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;
use ASURFIWordPress\Services as Services;

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
   *     degreeLevel = 'ugrad' or 'grad' Default is 'ugrad'
   *     testmode = 'test' or leave blank for the default production
   *     source_id = integer site identifier (issued by Enrollment services department) will default to site wide setting 
   */
  public function asu_rfi_form( $atts, $content = '' ) {
    $view_data =  array(
          'form_endpoint' => self::DEVELOPMENT_FORM_ENDPOINT,
          'redirect_back_url' => get_permalink(),
          'source_id' => 87, // todo 
          'testmode' => 'Prod', // default to prod
          // 'first_name' => '',
          'degreeLevel' => 'ugrad', // or 'grad'
          'student_types' => Services\StudentTypeService::get_student_types()
        );

    if( isset($atts['testmode']) && 'test' == $atts['testmode'] ) {
      $view_data['testmode'] = 'Test';
    }

    $view_data = $this->look_for_a_submission_response( $view_data );

    // Figure out what form to show
    $view_name = 'rfi-form.simple-request-info-form';
    if(isset($atts['type']) && $atts['type'] == 'full') {
      $view_name = 'rfi-form.form';
    }

    $response = $this->view( $view_name )->add_data( $view_data )->build();
    return $response->content;
  }

  /** look_for_a_submission_response() 
   * Look at the statusFlag and msg query var and return a human readable message that can be used 
   */
  private function look_for_a_submission_response( $view_data ) {
    $response_status_code = get_query_var('statusFlag');
    if( $response_status_code ) {
      $message = get_query_var('msg');
      // we have submitted the request form and should display a success or error message 
      if( '200' == $response_status_code ) {
        $view_data['success_message'] = $message ? $message : 'Thank you for submitting';
      } else  {
        error_log('error submitting ASU RFI (code: '.$response_status_code.') : '.$message);
        $view_data['error_message'] = $message ? $message : 'Something went wrong with your submission';
      }
    }
    return $view_data;
  }

}
