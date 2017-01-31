<?php

namespace ASURFIWordPress\Stores;

use ASURFIWordPress\Helpers\ConditionalHelper;
use ASURFIWordPress\Services\ASUDegreeService;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** ASU Degree Store
 * Providing a caching data store for the ASU Degrees Service
 * XML RPC API Docs: http://www.public.asu.edu/~lcabre/javadocs/dsws/
 */
class ASUDegreeStore {
  public static $transient_TTL = DAY_IN_SECONDS * 30;

  public static function get_programs( $college, $degree_level = 'graduate', $campus = null ) {
    $transient_name = ASUDegreeStore::get_transient_name( $college, $degree_level, $campus );
    if ( WP_DEBUG or false === ( $transient_content = get_transient( $transient_name ) ) ) {
      // transient doesn't exist, so regenerate the data and save the transient for next time
      try {
        $degree_service = new ASUDegreeService();
        $transient_content = $degree_service->get_majors_per_college( $college, $degree_level, $campus );
        set_transient( $transient_name, $transient_content, ASUDegreeStore::$transient_TTL );
      } catch (Exception $e) {
        // don't store an error in the transient
        error_log( $e->getMessage() );
      }
    }
    return $transient_content;
  }

  /** Get a unique encoded transient string given these three parameters.
   * Note: transient names shouldn't be longer than 40 characters
   */
  public static function get_transient_name( $college, $degree_level, $campus ) {
    // Need to coaurse variable spellings into one so we dont store duplicate transients.
    if ( ConditionalHelper::graduate( $degree_level ) ) {
      $degree_level = 'grad';
    } else {
      $degree_level = 'ugrad';
    }

    if ( ConditionalHelper::online( $campus ) ) {
      $campus = 'ONLNE';
    } elseif ( empty( $campus ) ) {
      $campus = '';
    } else {
      $campus = strtoupper( $campus );
    }

    $college = strtoupper( $college );

    // use md5 so we dont run into any length issues, md5 always returns 32 characters
    $hashed_parameters = md5( $college . ' ' . $degree_level . ' ' . $campus );

    return 'ASURFI' . $hashed_parameters;
  }


}
