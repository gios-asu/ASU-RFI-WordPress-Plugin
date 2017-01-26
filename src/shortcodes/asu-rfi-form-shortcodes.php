<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;
use ASURFIWordPress\Services as Services;
use ASURFIWordPress\Admin as Admin;
use ASURFIWordPress\Helpers\ConditionalHelper;


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
    $this->add_action( 'wp', $this, 'add_http_cache_header' );
    $this->add_action( 'wp_head', $this, 'add_html_cache_header' );
  }

  /**
   * Shorthand view wrapper to make rendering a view using nectary's factories easier in this plugin
   */
  private function view( $template_name ) {
    return new \Nectary\Factories\View_Factory( $template_name, $this->path_to_views );
  }

  /**
   * Do not cache any sensitive form data - ASU Web Application Security Standards
   */
  public function add_html_cache_header() {
    if ( $this->current_page_has_rfi_shortcode() ) {
      echo '<meta http-equiv="Pragma" content="no-cache"/>
            <meta http-equiv="Expires" content="-1"/>
            <meta http-equiv="Cache-Control" content="no-store,no-cache" />';
    }
  }

  /**
   * Do not cache any sensitive form data - ASU Web Application Security Standards
   * This call back needs to hook after send_headers since we depend on the $post variable
   * and that is not populated at the time of send_headers.
   */
  public function add_http_cache_header() {
    if ( $this->current_page_has_rfi_shortcode() ) {
      header( 'Cache-Control: no-Cache, no-Store, must-Revalidate' );
      header( 'Pragma: no-Cache' );
      header( 'Expires: 0' );
    }
  }

  /**
   * Returns true if the page is using the [asu-rfi-form] shortcode, else false
   */
  private function current_page_has_rfi_shortcode() {
    global $post;
    return ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'asu-rfi-form' ) );
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
   * Enqueue CSS and JS
   * Hooks onto `wp_enqueue_scripts`.
   */
  public function wp_enqueue_scripts() {
    if ( $this->current_page_has_rfi_shortcode() ) {
      $url_to_css_file = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/asu-rfi.css';
      wp_enqueue_style( $this->plugin_slug, $url_to_css_file, array(), $this->version );
      $url_to_jquery_validator = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'bower_components/jquery-validation/dist/jquery.validate.min.js';
      wp_enqueue_script( 'jquery-validation', $url_to_jquery_validator, array( 'jquery' ), '1.16.0', false );
    }
  }

  /**
   * Handle the shortcode [asu-rfi-form]
   *   attributes:
   *     type = 'full' or leave blank for the default simple form
   *     degree_level = 'ugrad' or 'grad' Default is 'ugrad'
   *     test_mode = 'test' or leave blank for the default production
   *     source_id = integer site identifier (issued by Enrollment services department) will default to site wide setting
   *     college_program_code = 4 character string, usually all caps, like 
   *         "GRLA" for College of Liberal Arts and Sciences or "GRSU" for "School of Sustainability"  
   *     major_code_picker = boolean, if true then programs for the college will be provided in a dropdown
   *     major_code = string, if provided then no picker, just a hidden major code value 
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
          'degreeLevel' => 'ugrad', // default to undergrad
          'enrollment_terms' => Services\ASUDegreeService::get_available_enrollment_terms(),
          'student_types' => Services\StudentTypeService::get_student_types(),
          'college_program_code' => null,
          'major_code_picker' => false,
          'major_code' => null,
        );

    if ( isset( $atts['test_mode'] ) && 0 === strcasecmp( 'test', $atts['test_mode'] ) ) {
      $view_data['testmode'] = 'Test';
    }

    // Use the attribute source id over the sites option
    if ( isset( $atts['source_id'] ) ) {
      $view_data['source_id'] = intval( $atts['source_id'] );
    }

    // Use the attribute source id over the sites option
    if ( isset( $atts['degree_level'] ) && ConditionalHelper::graduate( $atts['degree_level']) ) {
      $view_data['degreeLevel'] = 'grad';
      $view_data['student_types'] = Services\StudentTypeService::get_student_types('grad');
    } else if ( isset( $atts['degree_level'] ) && ConditionalHelper::undergraduate( $atts['degree_level']) ) {
      $view_data['degreeLevel'] = 'ugrad';
      $view_data['student_types'] = Services\StudentTypeService::get_student_types('undergrad');
    }

    if( isset( $atts['college_program_code'] ) ) {
      $view_data['college_program_code'] = $atts['college_program_code'];

      if( isset( $atts['major_code_picker'] ) ) {
        $service = new Services\ASUDegreeService();
        $view_data['major_codes'] = $service->get_majors_per_college( $atts['college_program_code'], $view_data['degreeLevel']);
      }
    }

    if( isset( $atts['major_code'])) {
      $view_data['major_code'] = $atts['major_code'];
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
        $view_data['success_message'] = 'Thank you for your submission!';
      } else {
        error_log( 'error submitting ASU RFI (code: ' . $response_status_code . ') : ' . $message );
        $view_data['error_message'] = $message ? 'Error:' . $message : 'Something went wrong with your submission';
      }
    }
    return $view_data;
  }

}
