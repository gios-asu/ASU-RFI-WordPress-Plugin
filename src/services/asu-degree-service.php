<?php

namespace ASURFIWordPress\Services;

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;
use ASURFIWordPress\Helpers\ConditionalHelper;
use ASURFIWordPress\Services\CampusService;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** ASUDegreeService
 * Providing data from ASU Degrees
 * XML RPC API Docs: http://www.public.asu.edu/~lcabre/javadocs/dsws/
 */
class ASUDegreeService {

  public function __construct( $client = null ) {
    if ( null === $client ) {
      $client = new Client( ASU_DIRECTORY_XML_RPC_SERVER );
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

    // TODO: this obviously should be more dynamic but it will do for the next few years
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

  /** Get all the programs across all campuses for a specific degree level in one array.
   * 'graduate' or 'undergraduate' are accepted values for degree levels.
   * returns an array.
   */
  public function get_programs_on_all_campuses( $degree_level = 'graduate' ) {
    $results = array();
    $campuses = CampusService::get_campus_codes();
    foreach ( $campuses as $campus_code ) {
      $results_for_this_campus = $this->get_programs_per_campus( $degree_level, $campus_code );
      $results = array_merge( $results, $results_for_this_campus );
    }
    return $results;
  }

  /** Get Programs offered on a specific Campus
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
    if ( ConditionalHelper::graduate( $degree_level ) ) {
      $program_to_search = 'graduate';
    } else {
      $program_to_search = 'undergrad';
    }

    if ( ConditionalHelper::online( $campus ) ) {
      // They want Online to be spelled this way.
      $campus = 'ONLNE';
    }

    $request = new Request( 'eAdvisorDSFind.findDegreeByCampusMapArray',
        array(
        new Value( $campus, 'string' ),
        new Value( $program_to_search, 'string' ),
        new Value( false, 'boolean' ),
        )
    );

    $response = $this->client->send( $request );
    $value = $response->value()->me;
    $value = array_pop( $value );

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

  /** Get major programs
   */
  public function get_majors_per_college( $college_code, $degree_level = 'graduate', $campus = 'TEMPE' ) {
    $programs = $this->get_programs_per_campus( $degree_level , $campus );
    return $this->filter_programs_for_a_college( $college_code, $programs );
  }

  /** Filter all programs and return just the programs that belong to a speicific college
   */
  private function filter_programs_for_a_college( $college_code, $all_programs ) {
    $subset = array();

    foreach ( $all_programs as $program ) {
      if ( 0 === strcasecmp( $college_code, $program['programcode'] ) ) {
        $subset [] = array(
          'label' => $this->get_program_display_name( $program ),
          'value' => $program['majorcode'],
         );
      }
    }
    return $subset;
  }

  /** Get a programs Display Name( $program ) - if needed, append in () the last two digets
   * of the majorcode unless there is a () in the major name already.
   */
  private function get_program_display_name( $program ) {
    if ( strpos( $program['majorname'], '(' ) === false ) {
      $last_two_letters_of_major_code = substr( $program['majorcode'], -2 );
      return $program['majorname'] . ' (' . $last_two_letters_of_major_code . ')';
    } else {
      return $program['majorname'];
    }

  }
}
