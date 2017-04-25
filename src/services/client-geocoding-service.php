<?php
namespace ASURFIWordPress\Services;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** Client Geocoding Service - Provides a service for finding the geographical locations
 * of the web server's client.
 */
class Client_Geocoding_Service {

  private static $geocoding_service_prodiver = 'http://freegeoip.net/json/%s';

  /**
   * Client_geo_location - returns a string of json from the geocoding providor given
   * the clients IP address.
   * The reason to do the geocoding server side is to prevent any race condition on the
   * clients browser at the sacrifice of page load time.
   * eg: {"ip":"8.8.8.8","country_code":"US","country_name":"United States","region_code":"CA","region_name":"California","city":"Mountain View","zip_code":"94035","time_zone":"America/Los_Angeles","latitude":37.386,"longitude":-122.0838,"metro_code":807}
   *
   * @return json string
   */
  public static function client_geo_location() {
    $ipinfo = self::get_ipinfo();
    if ( ! $ipinfo || empty( trim( $ipinfo ) ) || 'undefined' === trim( $ipinfo ) ) {
      return 'null';
    }
    return $ipinfo;
  }


  /**
   * Connects to service provider and returns a json object with the
   *
   * @return json
   */
  private static function get_ipinfo() {
    $remote_addr = self::get_client_ip_address();
    $url = sprintf( self::$geocoding_service_prodiver , $remote_addr );
    if ( empty( $remote_addr ) ) {
      return 'null';
    }

    $options = array();
    $options['timeout'] = 5; // seconds
    $ipinfo = wp_remote_fopen( $url , $options );

    if ( $ipinfo === false ) {
      error_log( 'Error requesting Client Geocoding Service: ' . $url );
      return 'null';
    }
    return trim( $ipinfo );
  }

  /**
   * Refer to below website
   * https://www.chriswiegman.com/2014/05/getting-correct-ip-address-php/
   */
  private static function get_client_ip_address() {
    // Just get the headers if we can or else use the SERVER global
    if ( function_exists( 'apache_request_headers' ) ) {
      $headers = apache_request_headers();
    } else {
      $headers = $_SERVER;
    }
    if ( ! empty( $headers['HTTP_CLIENT_IP'] ) ) {
      // check ip from share internet
      $the_ip = $headers['HTTP_CLIENT_IP'];
    } elseif ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
      // Get the forwarded IP if it exists
      $the_ip = $headers['X-Forwarded-For'];
    } elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
      $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
      $the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ); // @codingStandardsIgnoreLine
    }
    // allow others to apply their own filters for discovering the client ip address
    return apply_filters( 'wpb_get_ip', $the_ip );
  }
}
