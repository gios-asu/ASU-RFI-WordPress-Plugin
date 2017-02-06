<?php

namespace ASURFIWordPress\Services;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** ASU Semester Service
 * Providing information on asu semesters
 */
class ASUSemesterService {

  /**
   * Get Enrollment terms
   * These are the peoplesoft term identifiers
   *  "2101" for Spring, 2010, or  3
   *  "2104" for Summer, 2010, or  3
   *  "2107" for Fall, 2010, or  2
   *  "2109" for Winter, 2010
   *  "2177" for Summer, 2017
   *    Since this is for display purposes only, hide the smaller semesters that
   *    dont get included on public facing forms
   */
  public static function get_available_enrollment_terms() {
    $semester_names = array(
      1 => 'Spring',
      // 4 => 'Summer',
      7 => 'Fall',
      // 9 => 'Winter',
    );

    // TODO: this obviously should be more dynamic but it will do for now
    $years = array( '2017', '2018', '2019' );
    $terms = array();

    foreach ( $years as $year ) {
      foreach ( $semester_names as $semester_key => $semester_name ) {
        $terms[] = array(
          'value' => self::get_peoplesoft_semester_code( $year, $semester_key ),
          'label' => $year. ' ' . $semester_name,
        );
      }
    }

    return $terms;
  }

  /**
   * Get peoplesoft Semester Codes given ( $year, $semester_number ):
   * Given $year='2017', $semester_number = '4'
   * returns '2174'
   */
  private static function get_peoplesoft_semester_code( $year, $semester_number ) {
    return substr( $year,0,1 ) . substr( $year,2,2 ) . $semester_number;
  }
}
