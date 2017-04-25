<?php
/**
 * Class AddressTestService
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */

use ASURFIWordPress\Services\AddressService;

/**
 * Address Service test cases
 * @group services
 * @group address-service
 */
class AddressTestService extends WP_UnitTestCase {

  function test_get_countries() {
    $countries = AddressService::get_countries();
    $this->assertInternalType('array', $countries);
    $this->assertNotEmpty($countries);
    $this->assertGreaterThan(200, count($countries), 'there should be more than 200 countries');

    $first_country = current($countries);
    $this->assertNotNull($first_country['name']);
    $this->assertNotNull($first_country['code']);
  }

  function test_get_states() {
    $states = AddressService::get_states( 'US' );
    $this->assertInternalType('array', $states);
    $this->assertNotEmpty($states);
    $this->assertGreaterThan(50, count($states), 'there should be more than 50 US states');

    $first_state = current($states);
    $this->assertNotNull($first_state['name']);
    $this->assertNotNull($first_state['code']);
  }


}
