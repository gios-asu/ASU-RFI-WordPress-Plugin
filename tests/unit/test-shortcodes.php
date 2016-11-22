<?php
/**
 * Class ShortCodesTest
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */

/**
 * Shortcodes test case.
 */
class ShortCodesTest extends WP_UnitTestCase {

  /**
   * Lets make sure the shortcode actually gets defined
   */
  function test_asu_rfi_form_shortcode_exists() {
    $this->assertTrue( shortcode_exists( 'asu-rfi-form' ) );
  }

  function test_shortcode_returns_a_form() {
    $result = do_shortcode( '[asu-rfi-form]' );
    $this->assertContains('<form', $result);
  }


}
