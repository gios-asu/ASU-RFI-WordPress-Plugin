<?php

namespace ASURFIWordPress\Services;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}
/** StudentTypeService
 *   Helper service to provide the different student types as defined by the
 * routing data posting documentation from ASU Enrollment Services. Data is intended
 * to populate drop down selections.
 */
class StudentTypeService {

  public static function get_student_types() {
    return array(
        array( 'value' => 'Freshman', 'label' => 'Undergraduate Freshman Student' ),
        array( 'value' => 'Transfer', 'label' => 'Undergraduate Transfer Student' ),
        array( 'value' => 'Masters',  'label' => 'Graduate Masters Student' ),
        array( 'value' => 'Doctoral', 'label' => 'Graduate Doctoral Student' ),
        array( 'value' => 'cert',     'label' => 'Graduate Certificate Student' ),
        array( 'value' => 'nd',       'label' => 'Graduate Non-Degree Seeking Student' ),
      );
  }
}
