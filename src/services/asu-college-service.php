<?php

namespace ASURFIWordPress\Services;

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;
use ASURFIWordPress\Helpers\ConditionalHelper;


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
   * Note: this is not the same as the college code for the programs!! 
   */
  public function get_college_code( $college_name ) {
    $request = new Request( 'eAdvisorDSFind.getCollegeCodeForName',
        array(
        new Value( $college_name, 'string' )
    ) );
    $response = $this->client->send( $request );
    return $response->val->me['string'];
  }


  /** Optionally append either UG or GR to the program_code if neither is already defined.
   * Since the postfix is the same for Undergraduate (UG) and Graduate (GR) programs then lets
   * add it if it gets ommited.
   */
  public static function add_degree_level_prefix( $program_code, $degree_level ) {
    $program_code = strtoupper( $program_code ); // they should all be UPPER CASE

    // Base Case: empty strings should return empty strings
    if( 1 > strlen($program_code) ) return $program_code;

    if ( ConditionalHelper::graduate( $degree_level ) ) {
      if ( starts_with( $program_code, 'GR' ) ) {
        return $program_code;
      } else {
        return 'GR' . $program_code;
      }
    } else {
       if ( starts_with( $program_code, 'UG' ) ) {
        return $program_code;
      } else {
        return 'UG' . $program_code;
      }
    }
    return $program_code;
  }


}
