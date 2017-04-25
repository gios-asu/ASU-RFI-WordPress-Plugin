<?php

namespace ASURFIWordPress\Services;


// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

/** Campus Service - services for the Various ASU Campuses
 */
class ASUCampusService {

	/** Get Campus codes as defined by http://www.public.asu.edu/~lcabre/javadocs/dsws/
	 *	Since there is no actual endpoint to get these, we'll have to hardcode them here.
	 */
	public static function get_campus_codes() {
		// TODO: Thunderbird and Lake Havasu City campus?
		return array( 'TEMPE', 'POLY', 'DTPHX', 'WEST', 'ONLNE' );
	}

}
