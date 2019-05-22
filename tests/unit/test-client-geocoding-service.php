<?php
/**
 * Class Client_Geocoding_Service Test
 *
 * @package Asu_Rfi_Wordpress_Plugin

 */
use ASURFIWordPress\Services\Client_Geocoding_Service;

/**
 * Client_Geocoding_Service  test case.
 * @group services
 * @group client-geocoding-service
 */
class Client_Geocoding_Service_Test extends WP_UnitTestCase {


  function test_get_client_ip() {
    $geo_location_json_string = Client_Geocoding_Service::client_geo_location();
    $this->assertInternalType('string', $geo_location_json_string);
    $this->assertContains('country', $geo_location_json_string);
  }

  function test_get_client_ip_with_external_ip() {
    function apache_request_headers() {
      // Using IP Address, and corresponding results, from IPStack's own documentation,
      // as the original 8.8.8.8 was no longer providing a ZIP code
      return array('HTTP_CLIENT_IP' => '134.201.250.155');
    }
    $geo_location_json_string = Client_Geocoding_Service::client_geo_location();
    $this->assertInternalType('string', $geo_location_json_string);
    $this->assertContains('"country_code":"US"', $geo_location_json_string);
    $this->assertContains('"zip_code":"91773"', $geo_location_json_string);
  }


}
