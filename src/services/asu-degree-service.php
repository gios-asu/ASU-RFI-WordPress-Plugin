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

/** ASUDegreeService
 * Providing data from ASU Degrees
 * XML RPC API Docs: http://www.public.asu.edu/~lcabre/javadocs/dsws/
 *
 * @group services
 * @group asu-degree-service
 */
class ASUDegreeService {
  const ASU_DIRECTORY_XML_RPC_SERVER = 'https://webapp4.asu.edu/programs/XmlRpcServer';

  public function __construct( $client = null ) {
    if ( null === $client ) {
      $client = new Client( self::ASU_DIRECTORY_XML_RPC_SERVER );
    }
    $this->client = $client;
  }

  /*
   * enrollment terms
   * These are the peoplesoft term identifiers
   *  "2101" for Spring, 2010, or  3
   *  "2104" for Summer, 2010, or  3
   *  "2107" for Fall, 2010, or  2
   *  "2109" for Winter, 2010
   *  "2177" for Summer, 2017
   */
  public static function get_available_enrollment_terms() {
    $semester_names = array(
      1 => 'Spring',
      4 => 'Summer',
      7 => 'Fall',
      9 => 'Winter',
    );

    // TODO: this obviously should be more dynamic
    $years = array( '2017', '2018', '2019' );
    $terms = array();

    foreach ( $years as $year ) {
      foreach ( $semester_names as $semester_key => $semester_name ) {
        $terms[] = array(
          'value' => self::get_peoplesoft_semester_code( $year, $semester_key ),
          'label' => $semester_name . ' ' . $year,
        );
      }
    }

    return $terms;
  }

  /*
   * get_peoplesoft_semester_code( $year, $semester_number ):
   * Given $year='2017', $semester_number = '4'
   * returns '2174'
   */
  private static function get_peoplesoft_semester_code( $year, $semester_number ) {
    return substr( $year,0,1 ) . substr( $year,2,2 ) . $semester_number;
  }

  // public function get_campuses() {
  // return array(
  // 'TEMPE' => 'Tempe, Az',
  // 'PLOY' => '',
  // 'TDPHX' => 'Down Town Phoenix',

  // String campus = "TEMPE" String campus = "POLY" String campus = "DTPHX" String campus = "WEST" String campus = "ONLNE"
  // );
  // }

  public function get_programs( $college ) {
    // $request = new Request( 'eAdvisorDSFind.findDegreeByCampusMapArray' );
    // $response = $this->client->send( $request );
    // return array_map( function( $item ) {
    //     return array( 'name' => $item->me['string'] );
    // }, $response->val->me['array'] );

  }

  /**
   * the response object is rather obscure to work with, it looks like this:
   * 
   PhpXmlRpc\Response Object (
    [val] => PhpXmlRpc\Value Object
        ( [me] => Array (
            [array] => Array (
                [0] => PhpXmlRpc\Value Object (
                    [me] => Array (
                        [struct] => Array (
                            [AcadCareer] => PhpXmlRpc\Value Object (
                                [me] => Array (
                                    [string] => "blah blah"
                                )
                              )
                              ....

   */
  public function get_programs_per_campus( $degree_level = 'graduate', $campus = 'TEMPE' ) {
    // the RPC endpoint expects specific spelling for grad and undergrad
    if( ConditionalHelper::graduate( $degree_level ) ) { 
      $program_to_search = 'graduate';
    } else {
      $program_to_search = 'undergrad';
    }

    if( ConditionalHelper::online( $campus ) ) { 
      // They want Online to be spelled this way..
      $campus = 'ONLNE';
    }

    
    $request = new Request( 'eAdvisorDSFind.findDegreeByCampusMapArray', 
      array(
        new Value( $campus, 'string' ), 
        new Value( $program_to_search, 'string'), 
        new Value( FALSE, 'boolean'),
      )
    );

    $response = $this->client->send( $request );
    $value = $response->value()->me;
    $value = array_pop($value);
    
    return array_map( function( $item ) {
      $program = $item->me['struct'];
        return array( 
          'majorcode'   => $program['AcadPlan']->me['string'],
          'majorname'   => $program['Descr100']->me['string'],
          'programname' => $program['DiplomaDescr']->me['string'],
          'programcode' => $program['AcadProg']->me['string'],
         );
    }, $value );

  }

  public function get_majors_per_college($college_code, $degree_level = 'graduate', $campus = 'TEMPE' ) {
    $programs = $this->get_programs_per_campus( $degree_level , $campus ); 
    $subset = array(); 

    foreach( $programs as $program ) {
      if ( 0 === strcasecmp( $college_code, $program['programcode'] ) ) {
        $subset []= array( 
          'label' => $this->get_display_name( $program ),
          'value' => $program['majorcode'],
         );
      }
    }
    return $subset;
  }

  public function get_colleges() {
    $request = new Request( 'eAdvisorDSFind.listColleges' );
    $response = $this->client->send( $request );
    return array_map( function( $item ) {
        return array( 'name' => $item->me['string'] );
    }, $response->val->me['array'] );

  }

  /** get_display_name( $program ) - if needed, append in () the last two digets
   * of the majorcode unless there is a () in the major name already.
   */
  private function get_display_name( $program ) {
    if ( strpos( $program['majorname'], '(' ) === FALSE ) {
      $last_two_letters_of_major_code = substr($program['majorcode'], -2 );
      return $program['majorname']." (".$last_two_letters_of_major_code.")";          
    } else {
      return $program['majorname'];
    }

  }
}
