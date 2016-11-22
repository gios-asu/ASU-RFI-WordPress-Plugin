<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Form_Shortcodes extends Hook {
  private $path_to_views;

  public function __construct( $version ) {
    parent::__construct( 'asu-rfi-form-shortcodes', $version );
    $this->path_to_views = __DIR__ . '/../views/';
    $this->define_hooks();

    $instance = \Nectary\Configuration::get_instance();
    $instance->add( 'path_to_views', __DIR__ . '/../views/' );
  }

  public function define_hooks() {
    $this->add_action( 'wp_enqueue_scripts', $this, 'wp_enqueue_scripts' );
    $this->add_shortcode( 'asu-rfi-form', $this, 'asu_rfi_form' );
    // TODO: add url variables 'statusFlag' and 'msg' eg: ?statusFlag=200&msg=Sucessful%20submission

  }

  /**
   * Enqueue the CSS
   * Hooks onto `wp_enqueue_scritps`.
   */
  public function wp_enqueue_scripts() {
    $url_to_css_file = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/asu-rfi.css';
    wp_enqueue_style( $this->plugin_slug, $url_to_css_file, array(), $this->version );
  }

  public function asu_rfi_form( $atts, $content = '' ) {
     $response = view('rfi-form.form')->add_data(
        array(
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
          'student_types' => array(
            array('value' => 'Freshman', 'label' => 'Undergraduate Freshman'),
            array('value' => 'Transfer', 'label' => 'Undergraduate Transfer'),
            array('value' => 'Masters', 'label' => 'Graduate Masters'),
            array('value' => 'Doctoral', 'label' => 'Graduate Doctoral'),
            array('value' => 'cert', 'label' => 'Graduate Certificate'),
            array('value' => 'nd', 'label' => 'Graduate Non-degree')
          )
        )
    )->build();
    return $response->content;
  }

}
