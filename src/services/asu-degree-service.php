<?php

namespace ASURFIWordPress\Services;

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;


/** ASUDegreeService
 * Providing data from ASU Degrees
 * XML RPC API Docs: http://www.public.asu.edu/~lcabre/javadocs/dsws/
 * @group services
 * @group asu-degree-service
 */
class ASUDegreeService {
  const ASU_DIRECTORY_XML_RPC_SERVER = 'https://webapp4.asu.edu/programs/XmlRpcServer';

  public function __construct( $client = null) {
    if( null === $client ) {
      $client = new Client(self::ASU_DIRECTORY_XML_RPC_SERVER);  
    } 
    $this->client = $client;
  }

  /* enrollment terms
   * These are the peoplesoft term identifiers 
   *  "2101" for Spring, 2010, or  3
   *  "2104" for Summer, 2010, or  3
   *  "2107" for Fall, 2010, or  2
   *  "2109" for Winter, 2010 
   *  "2177" for Summer, 2017
   */
  public function get_available_enrollment_terms() {
    $semseter_names = array(
      1 => 'Spring',
      4 => 'Summer',
      7 => 'Fall',
      9 => 'Winter'
    );

    // TODO: this obviously should be more dynamic 
    $years = array( '2017', '2018', '2019' );
    $terms = array();

    foreach($years as $year) {
      foreach($semseter_names as $semseter_key => $semseter_name) {
        $terms[]= array(
          'code' => $this->encode_year_and_semester($year, $semseter_key), 
          'name' => $semseter_name.' '.$year);
      }
    }

    return $terms;
  }

  /** encode_year_and_semester( $year, $semester_number ): 
   * Given $year='2017', $semester_number = '4'
   * returns '2174'
   */
  private function encode_year_and_semester( $year, $semester_number ) {
    return substr($year,0,1).substr($year,2,2).$semester_number;
  }

  // public function get_campuses() {
  //   return array(
  //     'TEMPE' => 'Tempe, Az',
  //     'PLOY' => '',
  //     'TDPHX' => 'Down Town Phoenix',

  //     String campus = "TEMPE" String campus = "POLY" String campus = "DTPHX" String campus = "WEST" String campus = "ONLNE"
  //     );
  // }

  public function get_programs( $college ) {

  }

  public function get_colleges() {
    $request = new Request('eAdvisorDSFind.listColleges');
    $response = $this->client->send($request);
    return array_map( function( $item ) {
        return array( 'name' => $item->me['string'] );
    }, $response->val->me['array'] );

  }

}
