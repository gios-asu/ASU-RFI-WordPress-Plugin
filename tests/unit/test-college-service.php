<?php
/**
 * Class ASUCollegeServiceTest
 *
 * @package Asu_Rfi_Wordpress_Plugin

 */
use ASURFIWordPress\Services\ASUCollegeService;

/**
 * ASUCollegeService test case.
 * @group services 
 * @group asu-college-service
 */
class ASUCollegeServiceTest extends WP_UnitTestCase {

  function test_get_colleges() {
    $service = new ASUCollegeService();
    $colleges = $service->get_colleges();
    $this->assertInternalType('array', $colleges);
    $this->assertGreaterThan(4, count($colleges), 'there should be more than 4 colleges');  
  }

  function test_get_college_code() {
    $service = new ASUCollegeService();
    $code = $service->get_college_code('Sustainability, School of');
    $this->assertInternalType('string', $code);
    $this->assertEquals('CSS', $code);
  }

}