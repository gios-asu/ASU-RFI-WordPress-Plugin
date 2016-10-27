<?php
/**
 * Class AddressTestService
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */

use ASURFIWordPress\Services\AddressService;

/**
 * Address Service test cases
 */
class AddressTestService extends WP_UnitTestCase {

  function test_get_countries() {
    $countries = AddressService::get_countries();
    $this->assertInternalType('array', $countries);
    $this->assertNotEmpty($countries);
    $this->assertGreaterThan(200, count($countries), 'there should be more than 200 countries'); 
    $this->assertNotNull($countries[0]['name']);
    $this->assertNotNull($countries[0]['code']);
  }

}