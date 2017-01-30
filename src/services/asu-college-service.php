<?php

namespace ASURFIWordPress\Services;

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/**
 * College Information Service for ASU
 */
class ASUCollegeService {

  public function __construct( $client = null ) {
    if ( null === $client ) {
      $client = new Client( ASU_DIRECTORY_XML_RPC_SERVER );
    }
    $this->client = $client;
  }

  /** Get Colleges Display names in an array 
   * 
   */
  public function get_colleges() {
    $request = new Request( 'eAdvisorDSFind.listColleges' );
    $response = $this->client->send( $request );
    return array_map( function( $item ) {
        return array( 'name' => $item->me['string'] );
    }, $response->val->me['array'] );

  }

  /** Get College code given the display name 
   *  eg: given 'Sustainability, School of' this will return 'CSS', 
   * Note: this is not the same as the college code in the program!
   */
  public function get_college_code( $college_name ) {
    $request = new Request( 'eAdvisorDSFind.getCollegeCodeForName', 
      array(
        new Value( $college_name, 'string' ) 
    ) );
    $response = $this->client->send( $request );
    return $response->val->me['string'];
  }
}