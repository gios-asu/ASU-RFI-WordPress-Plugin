<?php

namespace ASURFIWordPress\Services;

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
