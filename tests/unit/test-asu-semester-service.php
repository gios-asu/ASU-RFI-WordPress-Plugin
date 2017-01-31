<?php
/**
 * Class ASUSemesterServiceTest
 *
 * @package Asu_Rfi_Wordpress_Plugin

 */
use ASURFIWordPress\Services\ASUSemesterService;

/**
 * ASUSemesterService test case.
 * @group services 
 * @group asu-semester-service
 */
class ASUSemesterServiceTest extends WP_UnitTestCase {

  function test_get_available_enrollment_terms() {
    $terms = ASUSemesterService::get_available_enrollment_terms();
    $this->assertInternalType('array', $terms);
    $this->assertGreaterThan(4, count($terms), 'there should be more than 4 terms');    
  }

}