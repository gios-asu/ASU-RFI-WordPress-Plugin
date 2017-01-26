<?php
/**
 * Class ConditionalHelperTest
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */
use ASURFIWordPress\Helpers\ConditionalHelper;

/**
 * ConditionalHelper test case.
 * @group helpers
 */
class ConditionalHelperTest extends WP_UnitTestCase {

  function test_grad_helper() {
    $this->assertTrue(ConditionalHelper::graduate('grad'));
    $this->assertTrue(ConditionalHelper::graduate('Graduate'));
    $this->assertFalse(ConditionalHelper::graduate('foo'));
  }

  function test_under_grad_helper() {
    $this->assertTrue(ConditionalHelper::undergraduate('uGrad'));
    $this->assertTrue(ConditionalHelper::undergraduate('UNDERgraduate'));
    $this->assertTrue(ConditionalHelper::undergraduate('undergrad'));
    $this->assertFalse(ConditionalHelper::undergraduate('foo'));
  }

}
