<?php

namespace ASURFIWordPress\Helpers;


// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** Helpers for conditionals
 *
 */
class ConditionalHelper {


	/** 
   * return true if the input is any one of the ways you might spell graduate
   */
	public static function graduate( $input ) {
    if( 0 === strcasecmp( 'grad', $input ) ||
        0 === strcasecmp( 'graduate', $input ) ) {
      return true;
    } 
    return false;
  }


  /** 
   * return true if the input is any one of the ways you might spell undergraduate
   */
  public static function undergraduate( $input ) {
    if( 0 === strcasecmp( 'ugrad', $input ) ||
        0 === strcasecmp( 'undergrad', $input ) ||
        0 === strcasecmp( 'undergraduate', $input ) ) {
      return true;
    } 
    return false;
  }

}