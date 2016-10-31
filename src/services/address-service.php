<?php

namespace ASURFIWordPress\Services;

use Phine\Country\Loader\Loader;


/** AddressService
 * Providing data on postal addresses to aide in Form building.
 * Depends on the "mledoze/countries" package.
 */
class AddressService {

  /**
   * Country names and Country codes in ISO_3166-1_alpha-2 format indexed on country code:
   *   [code] => Array
   *    (
   *        [name] => United States
   *        [code] => US
   *    )
   */
  public static function get_countries() {
    $loader = new Loader();
    $countries = $loader->loadCountries();

    return array_map( function( $item ) {
        return array( 'name' => $item->getShortName(), 'code' => $item->getAlpha2Code() );
    }, $countries);
  }


  public static function get_states( $country_code ) {
    $loader = new Loader();
    $subdivisions = $loader->loadSubdivisions( $country_code );

    return array_map( function( $item ) {
        return array( 'name' => $item->getName(), 'code' => $item->getCode() );
    }, $subdivisions);
  }


}
