<?php
/**
 * Class ASUDegreeServiceTest
 *
 * @package Asu_Rfi_Wordpress_Plugin

 */
use ASURFIWordPress\Services\ASUDegreeService;

/**
 * ASUDegreeService test case.
 * @group services
 * @group asu-degree-service
 */
class ASUDegreeServiceTest extends WP_UnitTestCase {


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

  function test_get_programs_per_multiple_campuses() {
    $service = new ASUDegreeService();
    $programs = $service->get_programs_on_all_campuses('undergraduate');
    $this->assertInternalType('array', $programs);
    $this->assertGreaterThan(4, count($programs), 'there should be more than 4 programs');
  }

}
