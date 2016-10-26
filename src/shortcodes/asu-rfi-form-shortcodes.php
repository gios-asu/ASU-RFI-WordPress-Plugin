<?php
namespace ASURFIWordPress\Shortcodes;
use Honeycomb\Wordpress\Hook;

/** ASU_RFI_Form_Shortcodes
 * provides the shortcode [asu-rfi-form]
 */
class ASU_RFI_Form_Shortcodes extends Hook {

  public function __construct() {
    $this->define_hooks();
  }

  public function define_hooks() {
    $this->add_shortcode( 'asu-rfi-form', $this, 'asu_rfi_form' );
  }

  public function asu_rfi_form( $atts, $content = '' ) {
    return 'Hello from the ASU RFI Form';
  }

}
