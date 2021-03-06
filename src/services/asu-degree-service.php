<?php

namespace ASURFIWordPress\Services;

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;
use ASURFIWordPress\Helpers\ConditionalHelper;
use ASURFIWordPress\Services\ASUCampusService;

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
  private static $RPC_TIMEOUT = 10; // seconds

  public function __construct( $client = null ) {
    if ( null === $client ) {
      $client = new Client( ASU_DIRECTORY_XML_RPC_SERVER );
    }
    $this->client = $client;
  }

  /** Get all the programs across all campuses for a specific degree level in one array.
   * 'graduate' or 'undergraduate' are accepted values for degree levels.
   * returns an array.
   */
  public function get_programs_on_all_campuses( $degree_level = 'graduate' ) {
    $results = array();
    $campuses = ASUCampusService::get_campus_codes();
    foreach ( $campuses as $campus_code ) {
      $results_for_this_campus = $this->get_programs_per_campus( $degree_level, $campus_code );
      $results = array_merge( $results, $results_for_this_campus );
    }
    return $results;
  }

  /** Get Programs offered on a specific Campus
   * the response object is rather obscure to work with, it looks like this:
   *
   * @throws \Exception if unable to make RPC request
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
        new Value( false, 'boolean' ), // get both certificates and majors
        )
    );

    $response = $this->client->send( $request, ASUDegreeService::$RPC_TIMEOUT );
    if ( ! empty( $response->errstr ) ) {
      throw new \Exception( 'ASU Degree Service Error: ' . $response->errno . ': ' . $response->errstr . ' : ' . ASU_DIRECTORY_XML_RPC_SERVER );
    }
    $value = $response->value()->me;
    $value = array_pop( $value );

    return array_map( function( $item ) {
        $program = $item->me['struct'];
        return array(
          'majorcode'   => $program['AcadPlan']->me['string'],
          'majorname'   => $program['Descr100']->me['string'],
          'degreedesc'  => $program['DegreeDescrformal']->me['string'],
          'programname' => $program['DiplomaDescr']->me['string'],
          'programcode' => $program['AcadProg']->me['string'],
         );
    }, $value );

  }

  /** Get major programs for a college at a degree level and optionally for a campus.
   * if no campus is defined than all campuses are returned.
   */
  public function get_majors_per_college( $college_code, $degree_level = 'graduate', $campus = null ) {
    if ( empty( $campus ) ) {
      $programs = $this->get_programs_on_all_campuses( $degree_level );
    } else {
      $programs = $this->get_programs_per_campus( $degree_level , $campus );
    }

    return $this->filter_programs_for_a_college( $college_code, $programs );
  }

  /** Filter all programs and return just the programs that belong to a speicific college
   */
  private function filter_programs_for_a_college( $college_code, $all_programs ) {
    $subset = array();

    foreach ( $all_programs as $program ) {
      if ( 0 === strcasecmp( $college_code, $program['programcode'] ) ) {
        $formatted_program = array(
          'label' => $this->get_program_display_name( $program ),
          'type'  => $this->get_program_type_name( $program ),
          'value' => $program['majorcode'],
         );
        // there could be duplicate programs offered on multiple campuses
        if ( ! in_array( $formatted_program, $subset ) ) {
          $subset [] = $formatted_program;
        }
      }
    }
    asort( $subset );
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

  /** Get a program's degree type name ('Bachelor', Master', 'Doctor', 'Certificate', etc.)
   */
  private function get_program_type_name( $program ) {
    if ( false !== strpos( $program['degreedesc'], 'Bachelor' ) ) {
      $program_type_name = 'Bachelors';

    } elseif ( false !== strpos( $program['degreedesc'], 'Master' ) ) {
      $program_type_name = 'Masters';

    } elseif ( false !== strpos( $program['degreedesc'], 'Doctor' ) ) {
      $program_type_name = 'Doctoral';

    } elseif ( false !== strpos( $program['degreedesc'], 'Certificate' ) ) {
      // to match the studentType code
      $program_type_name = 'cert';

    } else {
      // other non-degree types, like "Pre-prof/Exploratory", aren't passed through
      // because the rfi form will not be able to auto-select the appropriate studentType dropdown.
      // the end-user will have to select the appropriate value themselves.
      $program_type_name = '';
    }

    return $program_type_name;
  }
}
