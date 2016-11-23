<?php

namespace ASURFIWordPress\Services;

// Avoid direct calls to this file
if ( ! defined( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

class StudentTypeService {

  public static function get_student_types() {
    return array(
        array('value' => 'Freshman', 'label' => 'Undergraduate Freshman'),
        array('value' => 'Transfer', 'label' => 'Undergraduate Transfer'),
        array('value' => 'Masters',  'label' => 'Graduate Masters'),
        array('value' => 'Doctoral', 'label' => 'Graduate Doctoral'),
        array('value' => 'cert',     'label' => 'Graduate Certificate'),
        array('value' => 'nd', '      label' => 'Graduate Non-degree')
      );
  }
}
