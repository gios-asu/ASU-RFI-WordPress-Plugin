<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;
use ASURFIWordPress\Services\ASUDegreeService;
use ASURFIWordPress\Services\StudentTypeService;
use ASURFIWordPress\Services\ASUCollegeService;
use ASURFIWordPress\Services\ASUSemesterService;
use ASURFIWordPress\Stores\ASUDegreeStore;
use ASURFIWordPress\Admin\ASU_RFI_Admin_Page;
use ASURFIWordPress\Helpers\ConditionalHelper;
use ASURFIWordPress\Services\Client_Geocoding_Service;


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
   *     degree_level = 'undergrad' or 'grad' Default is 'undergrad'
   *     test_mode = 'test' or leave blank for the default production
   *     source_id = integer site identifier (issued by Enrollment services department) will default to site wide setting
   *     college_program_code = 2-5 character string, usually all caps, like
   *         "LA" for College of Liberal Arts and Sciences or "SU" for "School of Sustainability".
   *         Will default to the value set in the RFI Admin Options menu.
   *     major_code_picker = boolean, if true then programs for the college will be provided in a dropdown
   *     major_code = string, if provided then no picker, just a hidden major code value
   *     campus = string, default is all campuses, if provided the major_code_picker will be
   *          restricted down to just the majors offered on that particular campus.
   */
  public function asu_rfi_form( $atts, $content = '' ) {
    // if there are no attributes passed then $atts is not an array, its a string
    if ( ! is_array( $atts ) ) {
      $atts = array();
    }
    ensure_default( $atts, 'campus', null );
    ensure_default( $atts, 'major_code', null );
    ensure_default( $atts, 'degree_level', 'undergrad' );
    ensure_default( $atts, 'college_program_code', $this->get_option_attribute_or_default(
        array(
                'name'      => ASU_RFI_Admin_Page::$options_name,
                'attribute' => ASU_RFI_Admin_Page::$college_code_option_name,
                'default'   => null,
    ) ) );

    $view_data = array(
          'form_endpoint' => self::PRODUCTION_FORM_ENDPOINT,
          'redirect_back_url' => get_permalink(),
          'source_id' => $this->get_option_attribute_or_default(
              array(
                'name'      => ASU_RFI_Admin_Page::$options_name,
                'attribute' => ASU_RFI_Admin_Page::$source_id_option_name,
                'default'   => 0,
              )
          ),
          'enrollment_terms' => ASUSemesterService::get_available_enrollment_terms(),
          'student_types' => StudentTypeService::get_student_types(),
          'college_program_code' => null,
          'major_code_picker' => false,
          'major_code' => $atts['major_code'],
        );

    if ( isset( $atts['test_mode'] ) && 0 === strcasecmp( 'test', $atts['test_mode'] ) ) {
      $view_data['testmode'] = 'Test';
    } else {
      $view_data['testmode'] = 'Prod'; // default to production mode
    }

    // Use the attribute source id over the sites option
    if ( isset( $atts['source_id'] ) ) {
      $view_data['source_id'] = intval( $atts['source_id'] );
    }

    // Use the attribute source id over the sites option
    if ( ConditionalHelper::graduate( $atts['degree_level'] ) ) {
      $view_data['degreeLevel'] = 'grad';
      $view_data['student_types'] = StudentTypeService::get_student_types( 'grad' );
    } elseif ( ConditionalHelper::undergraduate( $atts['degree_level'] ) ) {
      $view_data['degreeLevel'] = 'ugrad';
      $view_data['student_types'] = StudentTypeService::get_student_types( 'undergrad' );
    }

    // get the Majors offered for this college, degree level and/or campus
    if ( isset( $atts['college_program_code'] ) ) {

      $atts['college_program_code'] = ASUCollegeService::add_degree_level_prefix(
          $atts['college_program_code'],
          $view_data['degreeLevel']
      );

      $view_data['college_program_code'] = $atts['college_program_code'];

      if ( isset( $atts['major_code_picker'] ) ) {
        $view_data['major_codes'] = ASUDegreeStore::get_programs(
            $atts['college_program_code'],
            $view_data['degreeLevel'],
            $atts['campus']
        );
      }
    }

    $view_data = $this->add_previous_submission_response( $view_data );

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
  private function add_previous_submission_response( $view_data ) {
    $response_status_code = get_query_var( 'statusFlag' );
    if ( $response_status_code ) {
      $message = get_query_var( 'msg' );
      // we have submitted the request form and should display a success or error message
      if ( 200 === intval( $response_status_code ) ) {
        $view_data['success_message'] = 'Thank you for your submission!';
        $view_data['client_geo_location'] = Client_Geocoding_Service::client_geo_location();
      } else {
        error_log( 'error submitting ASU RFI (code: ' . $response_status_code . ') : ' . $message );
        $view_data['error_message'] = $message ? 'Error:' . $message : 'Something went wrong with your submission';
      }
    }
    return $view_data;
  }


}
