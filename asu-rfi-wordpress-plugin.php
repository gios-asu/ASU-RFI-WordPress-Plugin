<?php

/*
Plugin Name: ASU RFI WordPress Plugin
Plugin URI: http://github.com/gios-asu/ASU-RFI-WordPress-Plugin
Description: WordPress plugin to submit Request For Information requests into Salesforce
Version: 0.1.0
Author: Julie Ann Wrigley Global Institute of Sustainability
License: Copyright 2016

GitHub Plugin URI: https://github.com/gios-asu/ASU-RFI-WordPress-Plugin
GitHub Branch: production
*/


if ( ! function_exists( 'add_filter' ) ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

define( 'ASU_RFI_WORDPRESS_PLUGIN_VERSION', '0.1.0' );

define( 'ASU_DIRECTORY_XML_RPC_SERVER', 'https://webapp4.asu.edu/programs/XmlRpcServer');

require __DIR__ . '/vendor/autoload.php';

$registry = new \Honeycomb\Services\Register();
$registry->register(
    require __DIR__ . '/src/registry/wordpress-registry.php'
);

