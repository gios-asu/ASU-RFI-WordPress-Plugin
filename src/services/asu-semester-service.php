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
  public static function get_available_enrollment_terms( $degree_level = 'undergrad', $semesters = null ) {
    // If semesters provided in shortcode attribute, override default semester list
    if ( !empty( $semesters ) ) {
      $semesters = array_map( 'trim', explode( ',', $semesters ) );
      $semester_names = array();

      foreach ( $semesters as $semester ) {
        if ( 'spring' === strtolower( $semester ) ) {
          $semester_names[1] = 'Spring';

        } elseif ( 'summer' === strtolower( $semester ) ) {
          $semester_names[4] = 'Summer';

        } elseif ( 'fall' === strtolower( $semester ) ) {
          $semester_names[7] = 'Fall';

        } elseif ( 'winter' === strtolower( $semester ) ) {
          $semester_names[9] = 'Winter';
        }
      }
    } else {
      // Enrollment services staff requested Summer session be included for undergrad form
      if ( 'undergrad' === $degree_level ) {
        $semester_names = array(
          1 => 'Spring',
          4 => 'Summer',
          7 => 'Fall',
          // 9 => 'Winter',
        );
      } else {
        $semester_names = array(
          1 => 'Spring',
          // 4 => 'Summer',
          7 => 'Fall',
          // 9 => 'Winter',
        );
      }
    }

    $years = array( date( 'Y' ), date( 'Y' ) + 1, date( 'Y' ) + 2 ); // this year and two years in the future
    $terms = array();

    foreach ( $years as $year ) {
      foreach ( $semester_names as $semester_key => $semester_name ) {
        $terms[] = array(
          'value' => self::get_peoplesoft_semester_code( $year, $semester_key ),
          'label' => $year . ' ' . $semester_name,
        );
      }
    }

    // in case `semesters` shortcode parameter list is out of order,
    // sort $terms by value to get terms correctly ordered (Spring, Summer, Fall)
    asort($terms);

    $current_month_number = date( 'n' ); // 1 = jan, 12 = dec

    // LOGIC
    // if date is: jan 1st X, first semester in array should be Summer of X
    // if date is: june 1st X, first semester in array should be Fall of X
    // if date is: aug 1st X, first semester in array should be Spring of X + 1
    if ( $current_month_number >= 1 && 1 === (int) substr( $terms[0]['value'], -1 ) ) {
      array_shift( $terms ); // remove the first Spring
    }
    if ( $current_month_number >= 5 && 4 === (int) substr( $terms[0]['value'], -1 ) ) {
      //drop the first occurrence of Summer, if present. so first term is Fall this year
      array_shift( $terms );
    }
    if ( $current_month_number >= 8 && 7 === (int) substr( $terms[0]['value'], -1 ) ) {
      //drop the first occurrence of Fall, if present. so first term is Spring next year
      array_shift( $terms );
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
