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

  function test_shortcode_uses_source_id_from_db() {
    $source_id_default_in_db = 999; // see data-loader.php

    $result = do_shortcode( '[asu-rfi-form]' );
    $this->assertContains('value="'.$source_id_default_in_db.'"', $result);
  }

  function test_shortcode_uses_source_id_from_attribute_over_db() {
    $result = do_shortcode( '[asu-rfi-form source_id=12345]' );
    $this->assertContains('value="12345"', $result);
  }


}
