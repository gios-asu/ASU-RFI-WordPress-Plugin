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

  function test_get_available_enrollment_terms_for_undergrad() {
    $terms = ASUSemesterService::get_available_enrollment_terms( 'undergrad' );
    $this->assertInternalType('array', $terms);
    $this->assertGreaterThan(6, count($terms), 'there should be more than 6 terms');
  }

  function test_get_available_enrollment_terms_for_grad() {
    $terms = ASUSemesterService::get_available_enrollment_terms( 'grad' );
    $this->assertInternalType('array', $terms);
    $this->assertGreaterThan(4, count($terms), 'there should be more than 4 terms');
  }

}
