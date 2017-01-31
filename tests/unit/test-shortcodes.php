<?php
/**
 * Class ShortCodesTest
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */

/**
 * Shortcodes test case.
 * @group shortcodes
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
    $this->assertContains('name="source_id" value="'.$source_id_default_in_db.'"', $result);
  }

  function test_shortcode_uses_source_id_from_attribute_over_db() {
    $result = do_shortcode( '[asu-rfi-form source_id=12345]' );
    $this->assertContains('name="source_id" value="12345"', $result);
  }

  function test_shortcode_default_test_mode() {
    $result = do_shortcode( '[asu-rfi-form]' );
    $this->assertContains('name="testmode" value="Prod"', $result);
  }

  function test_shortcode_test_mode() {
    $result = do_shortcode( '[asu-rfi-form test_mode="test"]' );
    $this->assertContains('name="testmode" value="Test"', $result);
  }

  function test_shortcode_college_code() {
    $result = do_shortcode( '[asu-rfi-form college_program_code="ABCD"]' );
    $this->assertContains('ABCD"', $result); // Note: the program code gets the GR or UG appended to it
  }

  function test_shortcode_major_code() {
    $result = do_shortcode( '[asu-rfi-form major_code="ABCD"]' );
    $this->assertContains('"ABCD"', $result);
  }

  // TODO: need to mock services
  function test_shortcode_major_picker() {
    $result = do_shortcode( '[asu-rfi-form major_code_picker=true college_program_code="SU"]' );
    $this->assertContains('<select name="poiCode"', $result);
  }

}
