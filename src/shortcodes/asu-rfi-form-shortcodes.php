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

  public function __construct() {
    parent::__construct( 'asu-rfi-form-shortcodes', ASU_RFI_WORDPRESS_PLUGIN_VERSION );
    $this->path_to_views = __DIR__ . '/../views/';
    $this->define_hooks();

    $instance = \Nectary\Configuration::get_instance();
    $instance->add( 'path_to_views', __DIR__ . '/../views/' );
  }

  public function define_hooks() {
    $this->add_action( 'wp_enqueue_scripts', $this, 'wp_enqueue_scripts' );
    $this->add_shortcode( 'asu-rfi-form', $this, 'asu_rfi_form' );
    $this->add_action( 'init', $this, 'setup_rewrites' );
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
   */
  public function asu_rfi_form( $atts, $content = '' ) {
    $view_data =  array(
          'form_endpoint' => 'https://requestinfo-qa.asu.edu/routing_form_post', // could aslo be requestinfo.asu.edu for prod
          'redirect_back_url' => get_permalink(),
          'source_id' => 87,
          'testmode' => 'Test',
          'first_name' => 'foo',
          'degreeLevel' => 'ugrad', // or 'grad'
          // 'first' => array(
          //   'required' => true,
          //   'placeholder' => 'First Name',
          //   'field_name' => 'firstName',
          //   'field_label' => 'First',
          //   'field_type' => 'text'),
          'student_types' => Services\StudentTypeService::get_student_types()
        );

    $response_status_code = get_query_var('statusFlag');
    if( $response_status_code ) {
      $message = get_query_var('msg');
      // we have submitted the request form and should display a success or error message 
      if( '200' == $response_status_code ) {
        $view_data['success_message'] = $message ? $message : 'Thank you for submitting';
      } else  {
        $view_data['error_message'] = $message ? $message : 'Something went wrong with your submission';
        error_log('error submitting ASU RFI (code: '.$response_status_code.') :'.$message);
      }
    }

    $response = view('rfi-form.form')->add_data($view_data )->build();
    return $response->content;
  }

}
