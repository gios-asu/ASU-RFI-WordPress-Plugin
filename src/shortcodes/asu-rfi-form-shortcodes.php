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
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

// Avoid direct calls to this file

if (!defined('ASU_RFI_WORDPRESS_PLUGIN_VERSION')) {
  header('Status: 403 Forbidden');
  header('HTTP/1.1 403 Forbidden');
  exit();
}

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Form_Shortcodes extends Hook
{
  use \ASURFIWordPress\Options_Handler_Trait;

  private $path_to_views;
  private $currentEndPoint;
  const PRODUCTION_FORM_ENDPOINT  = 'https://requestinfo.asu.edu/routing_form_post';
  const DEVELOPMENT_FORM_ENDPOINT = 'https://requestinfo-qa.asu.edu/routing_form_post';
  const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';

  public function __construct()
  {
    parent::__construct('asu-rfi-form-shortcodes', ASU_RFI_WORDPRESS_PLUGIN_VERSION);
    $this->path_to_views = __DIR__ . '/../views/';
    $this->define_hooks();
    $this->currentEndPoint = self::PRODUCTION_FORM_ENDPOINT;
  }

  public function define_hooks()
  {
    $this->add_action('wp_enqueue_scripts', $this, 'wp_enqueue_scripts');
    $this->add_shortcode('asu-rfi-form', $this, 'asu_rfi_form');
    $this->add_action('init', $this, 'setup_rewrites');
    $this->add_action('wp', $this, 'add_http_cache_header');
    $this->add_action('wp_head', $this, 'add_html_cache_header');

    // form handling callbacks. We capture POST requests from both logged-in and
    // NOT logged-in users, and send them both to our RFI handling method.
    $this->add_action('admin_post_nopriv_rfi_form', $this, 'rfi_post');
    $this->add_action('admin_post_rfi_form', $this, 'rfi_post');

    // an attempt to extend the wp_remote_post() timeout
    $this->add_filter('http_request_timeout', $this, 'rfi_request_timeout');
  }

  /**
   * Shorthand view wrapper to make rendering a view using nectary's factories easier in this plugin
   */
  private function view($template_name)
  {
    return new \Nectary\Factories\View_Factory($template_name, $this->path_to_views);
  }

  /**
   * Do not cache any sensitive form data - ASU Web Application Security Standards
   */
  public function add_html_cache_header()
  {
    if ($this->current_page_has_rfi_shortcode()) {
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
  public function add_http_cache_header()
  {
    if ($this->current_page_has_rfi_shortcode()) {
      header('Cache-Control: no-Cache, no-Store, must-Revalidate');
      header('Pragma: no-Cache');
      header('Expires: 0');
    }
  }

  /**
   * Returns true if the page is using the [asu-rfi-form] shortcode, else false
   */
  private function current_page_has_rfi_shortcode()
  {
    global $post;
    return (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'asu-rfi-form'));
  }

  /** Set up any url rewrites:
   * WordPress requires that you tell it that you are using
   * additional parameters.
   */
  public function setup_rewrites()
  {
    add_rewrite_tag('%statusFlag%', '([^&]+)');
    add_rewrite_tag('%msg%', '([^&]+)');
  }

  /**
   * Enqueue CSS and JS
   * Hooks onto `wp_enqueue_scripts`.
   */
  public function wp_enqueue_scripts()
  {
    if ($this->current_page_has_rfi_shortcode()) {
      $url_to_css_file = plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/asu-rfi.css';
      wp_enqueue_style($this->plugin_slug, $url_to_css_file, array(), $this->version);
      $url_to_jquery_validator = plugin_dir_url(dirname(dirname(__FILE__))) . 'node_modules/jquery-validation/dist/jquery.validate.min.js';
      wp_enqueue_script('jquery-validation', $url_to_jquery_validator, array('jquery'), '1.16.0', false);
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
   *         restricted down to just the majors offered on that particular campus.
   *     semesters = comma-delimited list of semesters to which a student can apply for submission (values:
   *         fall, spring, summer)
   */
  public function asu_rfi_form($atts, $content = '')
  {
    error_log('Starting asu_rfi_form() method...');

    // if there are no attributes passed then $atts is not an array, its a string
    if (!is_array($atts)) {
      $atts = array();
    }
    error_log('Setting defaults...');

    ensure_default($atts, 'campus', null);
    ensure_default($atts, 'major_code', null);
    ensure_default($atts, 'degree_level', 'undergrad');
    ensure_default($atts, 'college_program_code', $this->get_option_attribute_or_default(
      array(
        'name'      => ASU_RFI_Admin_Page::$options_name,
        'attribute' => ASU_RFI_Admin_Page::$college_code_option_name,
        'default'   => null,
      )
    ));
    ensure_default($atts, 'semesters', null);
    ensure_default($atts, 'thank_you_page', '');
    ensure_default($atts, 'major_code_picker', 0);

    error_log('Creating view data...');

    $view_data = array(
      'form_endpoint' => esc_url(admin_url('admin-post.php')), // since we're using callbacks on admin-post now
      'thank_you' => $atts['thank_you_page'],
      'formUrl' => get_permalink(),
      'source_id' => $this->get_option_attribute_or_default(
        array(
          'name'      => ASU_RFI_Admin_Page::$options_name,
          'attribute' => ASU_RFI_Admin_Page::$source_id_option_name,
          'default'   => 0,
        )
      ),
      'site_key' => $this->get_option_attribute_or_default(
        array(
          'name'      => ASU_RFI_Admin_Page::$options_name,
          'attribute' => ASU_RFI_Admin_Page::$google_recaptcha_site_option_name,
          'default'   => null,
        )
      ),
      'enrollment_terms' => ASUSemesterService::get_available_enrollment_terms($atts['degree_level'], $atts['semesters']),
      'student_types' => StudentTypeService::get_student_types(),
      'college_program_code' => null,
      'major_code_picker' => $atts['major_code_picker'],
      'major_code' => $atts['major_code']
    );

    // sets the hidden form element 'testmode', defaulting to 'Prod'
    if (isset($atts['test_mode']) && 0 === strcasecmp('test', $atts['test_mode'])) {
      $view_data['testmode'] = 'Test';
    } else {
      $view_data['testmode'] = 'Prod';
    }

    // Use the attribute source id over the sites option
    if (isset($atts['source_id'])) {
      $view_data['source_id'] = intval($atts['source_id']);
    }

    // Use the attribute source id over the sites option
    if (ConditionalHelper::graduate($atts['degree_level'])) {
      $view_data['degreeLevel'] = 'grad';
      $view_data['student_types'] = StudentTypeService::get_student_types('grad');
    } elseif (ConditionalHelper::undergraduate($atts['degree_level'])) {
      $view_data['degreeLevel'] = 'ugrad';
      $view_data['student_types'] = StudentTypeService::get_student_types('undergrad');
    }

    // get the Majors offered for this college, degree level and/or campus
    if (isset($atts['college_program_code'])) {

      $atts['college_program_code'] = ASUCollegeService::add_degree_level_prefix(
        $atts['college_program_code'],
        $view_data['degreeLevel']
      );

      $view_data['college_program_code'] = $atts['college_program_code'];

      if ($atts['major_code_picker']) {
        $view_data['major_codes'] = ASUDegreeStore::get_programs(
          $atts['college_program_code'],
          $view_data['degreeLevel'],
          $atts['campus']
        );
      } elseif ('grad' === $view_data['degreeLevel'] && !empty($atts['major_code'])) {
        // since 'major code picker' is not used, if this is for a Graduate form
        // assign studentType to match the degree program (Masters, Doctoral, etc.)
        $programs = ASUDegreeStore::get_programs(
          $atts['college_program_code'],
          $view_data['degreeLevel'],
          $atts['campus']
        );
        // find major code in college's available degrees
        foreach ($programs as $program) {
          if ($program['value'] === $atts['major_code']) {
            $view_data['student_type'] = $program['type'];
            break;
          }
        }
      }
    }

    error_log('Checking for previous response data (aka querystring)...');
    $view_data = $this->add_previous_submission_response($view_data);

    // Figure out which form to show
    $view_name = 'rfi-form.simple-request-info-form';
    if (isset($atts['type']) && 0 === strcasecmp('full', $atts['type'])) {
      $view_name = 'rfi-form.form';
    }

    error_log('Adding view data end exiting...');

    $response = $this->view($view_name)->add_data($view_data)->build();
    return $response->content;
  }

  /**
   * Look at the statusFlag and msg query var and return a human readable message that can be used
   */
  private function add_previous_submission_response($view_data)
  {
    $response_status_code = get_query_var('statusFlag');
    if ($response_status_code) {
      $message = get_query_var('msg');
      // we have submitted the request form and should display a success or error message
      if (200 === intval($response_status_code)) {
        $view_data['success_message'] = 'Thank you for your submission!';
        $view_data['client_geo_location'] = Client_Geocoding_Service::client_geo_location();
      } else {
        error_log('error submitting ASU RFI (code: ' . $response_status_code . ') : ' . $message);
        $view_data['error_message'] = $message ? 'Error: ' . $message : 'Something went wrong with your submission';
      }
    }
    return $view_data;
  }


  /**
   * rfi_post()
   *
   * Our callback method for the wp_admin_post hook. Called when a form is submitted to Wordpress
   * that contains : <input type="hidden" name="action" value="rfi_form">. This is the logic only
   * for submitted forms (it is not called on a regular page render).
   */
  public function rfi_post()
  {
    // Step 1: Send the token (from our form) to Google for a reCAPTCHA score.
    error_log('Step 1: reCAPTCHA...');
    $verified = $this->recaptcha_verify();

    if (is_wp_error($verified)) {
      error_log('reCAPTCHA failed.');
      $this->redirect_with_error($verified, $_POST['formUrl']);
    }

    // Step 2: submit the form to our endpoint and redirect to the URL we get back
    error_log('Step 2: submitting the form...');
    $posted = $this->submit_form();

    if (is_wp_error($posted)) {
      error_log('Form submission FAILED! Redirecting to ' . $_POST['formUrl']);
      $this->redirect_with_error($posted, $_POST['formUrl']);
    }

    // if it's all good, we can redirect to the URL that came back from our method call
    error_log('Step 3: Success! Redirecting to ' . $posted);
    wp_redirect($posted);
    //exit;
  }

  /**
   * submit_form()
   *
   * Submits the POST data to our endpoint, returning a URL for redirection or a WP_Error
   * object if something did not work.
   */
  private function submit_form()
  {
    error_log('Submitting form to ASU endpoint...');
    // the actual form submission doesn't need our reCAPTCHA stuff
    unset($_POST['g-recaptcha-response']);
    unset($_POST['action']);
    unset($_POST['rfi-submit']);

    /**
     * determine which endpoint to use (normal, or QA) based on value we set in a hidden field.
     * We only expect 'Test' or 'Prod', and use 'Prod' for any value except 'Test'
     */
    switch ($_POST['testmode']) {
      case 'Test':
        $this->currentEndPoint = self::DEVELOPMENT_FORM_ENDPOINT;
        break;
      case 'Prod':
      default:
        $this->currentEndPoint = self::PRODUCTION_FORM_ENDPOINT;
    }
    error_log('Posting to endpoint: ' . $this->currentEndPoint);
    $start = time();
    error_log('Starting at ' . $start);

    // prepare guzzle request
    $guzzleClient = new Client([
      'base_uri' => $this->currentEndPoint,
      'timeout' => 20.0,
    ]);

    /**
     * Try the request. Our calling code is expecting WP_Errors, and NOT exceptions.
     * When an exception is raised, we'll return a suitable WP_Error object.
     */
    try {
      $response = $guzzleClient->request('POST', $this->currentEndPoint, [
        'form_params' => $_POST
      ]);
    } catch (RequestException $e) {
      // raised by network issues: timeout, DNS issue, etc.
      if ($e->hasResponse) {
        $eMsg = Psr7\str($e->getResponse()->getBody());
        return new WP_Error('network', 'A network error was reported: ' . $eMsg);
      }
    } catch (ClientException $e) {
      // raised on 400 errors
      if ($e->hasResponse) {
        $eMsg = Psr7\str($e->getResponse()->getBody());
        return new WP_Error('client', 'The server reported a client error: ' . $eMsg);
      }
    } catch (ServerException $e) {
      // raised by 500 errors
      if ($e->hasResponse) {
        $eMsg = Psr7\str($e->getResponse()->getBody());
        return new WP_Error('server', 'The server returned an internal error: ' . $eMsg);
      }
    } catch (\Exception $e) {
      return new WP_Error('unknown', 'An unknown error occurred while processing your request.');
    }

    $end = time();
    $diff = $end - $start;
    error_log('Post complete at: ' . $end . '(' . $diff . ' seconds)');

    /**
    * retrieve the response code from our request. Based on my testing, the endpoint is
    * using the standard 200 for success and 400 for an error.
    */

    // get the code
    $statusCode = $response->getStatusCode();
    $reason = $response->getReasonPhrase();


    // return a URL on a 200, and a WP_Error on any other code
    if (200 === $statusCode) {
      error_log('Result was a 200. Redirecting...');
      if (isset($_POST['thank_you']) && !empty($_POST['thank_you'])) {
        // if we're redirecting to a page that is not our original form, then we don't need
        // the querystring items, and can simply redirect.
        return $_POST['thank_you'];
      } else {
        // if there is no thank_you page set, go back to the form page with querystring vars
        return $this->buildRedirectUrl($_POST['formUrl']);
      }
    } else {
      return new \WP_Error('submit', 'We received an unknown status code while processing your request.');
    }
  }

  /**
   * recaptcha_verify()
   *
   * Retrieves a reCAPTCHA v3 score for the current request's token. Returns TRUE on success, otherwise
   * returns an instance of WP_Error with an appropriate message.
   */
  private function recaptcha_verify()
  {

    // make sure our form came through with the expected recaptcha token
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
      $token = $_POST['g-recaptcha-response'];
    } else {
      // we can't continue without the token, as it's required to verify with reCAPTCHA
      return new \WP_Error('recaptcha', 'Unable to verify via Google reCAPTCHA. No user token.');
    }

    // we also need to know our minimum required score, in order to decide what to do. The Google
    // score comes back as a Float, so we're casting here to make this a Float as well.
    $min_required_score = floatval(
      $this->get_option_attribute_or_default(
        array(
          'name'      => ASU_RFI_Admin_Page::$options_name,
          'attribute' => ASU_RFI_Admin_Page::$google_recaptcha_required_score_option_name,
          'default'   => 0.7,
        )
      )
    );

    /**
     * Google expects our secret key as well. It's stored in the plugin settings.
     */
    $secret_key = $this->get_option_attribute_or_default(
      array(
        'name'      => ASU_RFI_Admin_Page::$options_name,
        'attribute' => ASU_RFI_Admin_Page::$google_recaptcha_secret_option_name,
        'default'   => null,
      )
    );

    // Use the WordPress HTTP API to make the request for a reCAPTCHA score
    $data = [
      'secret' => $secret_key,
      'response' => $token,
    ];

    $recaptchaResult = wp_remote_post(self::RECAPTCHA_URL, array(
      'body' => $data,
    ));
    error_log('Verifying reCAPTCHA token with ' . self::RECAPTCHA_URL);
    // check to see if we got an error object.
    if (is_wp_error($recaptchaResult)) {
      return $recaptchaResult;
    }

    // decode our results, which are in the 'body' key of the array we get back
    // from wp_remote_post()
    $result = json_decode($recaptchaResult['body']);

    /**
     * the Google JSON will contain (among other fields):
     * - a 'success' field with either TRUE or FALSE
     * - a 'score' field (only on success) with a score between 0 and 1 (as a Float)
     * - an 'error-codes' field (only on error) with one, or more, erorr messages
     */

    if ($result->success) {
      error_log('Result was SUCCESS...');
      // we got a score, but was it good enough?
      if ($result->score >= $min_required_score) {
        error_log('Passing score of: ' . $result->score);
        // Yes! You passed!
        return true;
      } else {
        error_log('Failing score of: ' . $result->score);
        // No! You are a bot!
        return new \WP_Error('recaptcha', 'Insufficient score reported by Google reCAPTCHA');
      }
    } else {
      // we did NOT get a score. Gather the Google error(s) and return it/them.
      $my_error = new \WP_Error();

      // note: curly braces required here because of the dash in the property's name. Normally, you
      // would type $result->error-codes, but that's not valid in PHP.
      foreach ($result->{'error-codes'} as $thisError) {
        $my_error->add('recaptcha', ' Google reCAPTCHA reported ' . $thisError);
      }
      error_log('Result was ERROR: ' . $my_error->get_error_message());
      return $my_error;
    }
  }

  /**
   * redirect_with_error( WP_Error $error, String $url)
   *
   * Takes a WP_Error object and a URL, then redirects the user back to the RFI form with
   * the statusFlag set to 400 (to display as an error), and the first error message in the
   * object.
   */
  private function redirect_with_error($error, $url)
  {

    // clean up the URL
    $location = esc_url_raw($url);

    // retrieve the error message from the WP_Error object. WP_Error uses an array, and the
    // keys of the array are called error 'codes', so we're grabbing the first array key in
    // order to get its associated message.
    $code = $error->get_error_code();
    $message = $error->get_error_message($code);

    // Add a 400 code, and the error message, to the query string - using the names our
    // own code is expecting
    $location = add_query_arg('statusFlag', urlencode('400'), $location);
    $location = add_query_arg('msg', urlencode($message), $location);

    // send the user back to the form (hopefully), and display the error message
    wp_redirect($location);
    exit;
  }

  /**
   * Construct a success URL by appending the expected query string variables:
   *
   * statusFlag = 200 (this method only deals with successful submissions)
   * msg = a message to display. The add_previous_submission_response() method above
   * will use a default if none is provided here.
   */
  private function buildRedirectUrl($url)
  {
    // trim trailing slashes that may be on the URL, as we need to append query string items
    $redirectUrl = rtrim($url, '/');

    // add our success code and message
    $redirectUrl = add_query_arg(array(
      'statusFlag' => urlencode(200),
      'msg' => urlencode('Your request has been processed. Thank you for your interest!')
    ), $redirectUrl);

    return $redirectUrl;
  }

  /**
   * Filter callback for setting HTTP API request timeouts.
   */
  public function rfi_request_timeout($time)
  {
    // This works only if you don't set a timeout in the wp_remote_post() call itself

    return 20;
  }
}
