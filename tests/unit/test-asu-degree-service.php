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
    $terms = ASUDegreeService::get_available_enrollment_terms();
    $this->assertInternalType('array', $terms);
    $this->assertGreaterThan(4, count($terms), 'there should be more than 4 terms');    
  }

  function test_get_colleges() {
    $service = new ASUDegreeService();
    $colleges = $service->get_colleges();
    $this->assertInternalType('array', $colleges);
    $this->assertGreaterThan(4, count($colleges), 'there should be more than 4 colleges');  
  }

  function test_get_programs_per_campus() {
    $service = new ASUDegreeService();
    $programs = $service->get_programs_per_campus();
    $this->assertInternalType('array', $programs);
    $this->assertGreaterThan(4, count($programs), 'there should be more than 4 programs');
  }
  function test_get_programs_per_campus_undergrad() {
    $service = new ASUDegreeService();
    $programs = $service->get_programs_per_campus('undergraduate');
    $this->assertInternalType('array', $programs);
    $this->assertGreaterThan(4, count($programs), 'there should be more than 4 programs');
  }

  function test_get_majors_per_college() {
    $service = new ASUDegreeService();
    $majors = $service->get_majors_per_college('GRSU', 'graduate');
    $this->assertInternalType('array', $majors);
    $this->assertGreaterThan(4, count($majors), 'there should be more than 4 majors');
  }

  function test_get_majors_per_college_undergrad() {
    $service = new ASUDegreeService();
    $majors = $service->get_majors_per_college('UGSU', 'undergraduate');
    $this->assertInternalType('array', $majors);
    $this->assertGreaterThan(1, count($majors), 'there should be more than 1 majors');
  }
}
