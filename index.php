<?php

/*
Plugin Name: KMI Libary Files
Plugin URI: 
Description: Plugin to manage added library files.
Author: KMI
Version: 1.0
Author URI: 
*/

if(!defined('ABSPATH')) exit; // Exit if accessed directly

require_once 'kmi-libraryfiles.php';

register_activation_hook(__FILE__, 'kmi_activate_libary_files');

function kmi_activate_libary_files()
{
    error_log('KMI library files activated.');
}

register_deactivation_hook(__FILE__, 'kmi_deactivate_library_files');

function kmi_deactivate_library_files()
{
    error_log('KMI library files deactivated.');
}