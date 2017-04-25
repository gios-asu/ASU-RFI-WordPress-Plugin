<?php
/**
 * Class ASUDegreeStoreTest
 *
 * @package Asu_Rfi_Wordpress_Plugin

 */
use ASURFIWordPress\Stores\ASUDegreeStore;

/**
 * ASUDegreeStore test case.
 * @group stores
 * @group asu-degree-store
 */
class ASUDegreeStoreTest extends WP_UnitTestCase {

  function test_get_transient_name_returns_a_string() {
    $name = ASUDegreeStore::get_transient_name('abc','undergrad','hij');
    $this->assertInternalType('string', $name);
  }

  function test_get_transient_name_returns_a_unique_string_thats_case_insensitive() {
    $first = ASUDegreeStore::get_transient_name('abc','undergrad','hij');
    $second = ASUDegreeStore::get_transient_name('ABC','undergrad','hij');
    $this->assertEquals($first, $second);
  }

    function test_get_transient_name_returns_a_unique_string_given_different_inputs() {
    $first = ASUDegreeStore::get_transient_name('foo','undergrad','hij');
    $second = ASUDegreeStore::get_transient_name('bar','undergrad','hij');
    $this->assertNotEquals($first, $second);
  }

  function test_get_transient_name_length() {
    $name = ASUDegreeStore::get_transient_name('AREALLYREALLYREALLYREALLYLONGCOLLEGENAME','undergraduate','AREALLYLONGCAMPUSNAME');
    $this->assertLessThan(40, count($name), 'transient names should be less that 40 characters');
  }

  function test_get_programs() {
    // todo: mock the ASUDegreeService response
    $programs = ASUDegreeStore::get_programs('GRSU', 'graduate', NULL);
    $this->assertInternalType('array', $programs);
    $this->assertGreaterThan(4, count($programs));
  }

  // todo: test that the transeient actually gets stored and reused

}
