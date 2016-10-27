<?php

namespace ASURFIWordPress\Services;

class AddressService {



  /**
   *  Country Codes and Country names in ISO_3166-1_alpha-2 format:
   *   Array
   *    (
   *        [name] => United States
   *        [code] => US
   *    )
   *   
   */
  public static function getCountries() {
    $path_to_country_data = dirname(dirname(__DIR__)).'/vendor/mledoze/countries/dist/countries.json';

    $country_data = json_decode( file_get_contents( $path_to_country_data ));
    return array_map( function($item) {
      return array('name' => $item->name->common, 'code' => $item->cca2);
    }, $country_data);
  }
}