<?php
/**
 * Class ASUDegreeServiceTest
 *
 * @package Asu_Rfi_Wordpress_Plugin
 */
use ASURFIWordPress\Services\ASUDegreeService;

/**
 * ASUDegreeService test case.
 */
class ASUDegreeServiceTest extends WP_UnitTestCase {

  function test_get_available_enrollment_terms() {
    $service = new ASUDegreeService();
    $terms = $service->get_available_enrollment_terms();
    $this->assertInternalType('array', $terms);
    $this->assertGreaterThan(4, count($terms), 'there should be more than 4 terms'); 
    print_r($terms);
    
  }
}
