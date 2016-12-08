<?php

namespace ASURFIWordPress;

trait Options_Handler_Trait {
  /**
    * Get Option Attribute or a default value - just a wrapper on get_option.
    *  NOTE: it does escape html, so if your expecting back you need to decode it.
    */
  public function get_option_attribute_or_default( $options ) {
    $options_name      = array_key_exists( 'name', $options ) ? $options['name'] : '';
    $options_parameter = array_key_exists( 'attribute', $options ) ? $options['attribute'] : '';
    $default           = array_key_exists( 'default', $options ) ? $options['default'] : '';

    $options = get_option( $options_name );

    if ( isset( $options[ $options_parameter ] ) ) {
      $default = esc_attr( $options[ $options_parameter ] );
    }

    return $default;
  }
}