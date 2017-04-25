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

  function test_degree_level_prefixer() {
    $abcgraduate = ASUCollegeService::add_degree_level_prefix('ABC', 'graduate');
    $this->assertEquals('GRABC', $abcgraduate);
    $abcundergraduate = ASUCollegeService::add_degree_level_prefix('UGABC', 'undergraduate');
    $this->assertEquals('UGABC', $abcundergraduate);
    $redundantabcundergraduate = ASUCollegeService::add_degree_level_prefix('UGUGABC', 'undergraduate');
    $this->assertEquals('UGUGABC', $redundantabcundergraduate);
  }

  function test_degree_level_prefixer_null_case() {
    $abcgraduate = ASUCollegeService::add_degree_level_prefix('', 'graduate');
    $this->assertEquals('', $abcgraduate);
  }

}
