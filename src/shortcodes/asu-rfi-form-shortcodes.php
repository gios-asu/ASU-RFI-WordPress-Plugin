<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Form_Shortcodes extends Hook {
  private $path_to_views;

  public function __construct() {
    $this->define_hooks();
    $this->path_to_views = __DIR__ . '/../views/';

    $instance = \Nectary\Configuration::get_instance();
    $instance->add( 'path_to_views', __DIR__ . '/../views/' );
  }

  public function define_hooks() {
    $this->add_shortcode( 'asu-rfi-form', $this, 'asu_rfi_form' );
  }

  public function asu_rfi_form( $atts, $content = '' ) {
     $response = view('rfi-form.form')->add_data(
        array(
          'redirect_back_url' => get_permalink(),
          'source_id' => 87,
          'testmode' => 'Test',
        )
    )->build();
    return $response->content;
  }

}
