<?php

/*
	Plugin Name: q2apro Upload Manager
	Plugin URI: http://www.q2apro.com/plugins/uploadmanager
	Plugin Description: A complete upload manager for question2answer with upload details, image rotate features and more!
	Plugin Version: 1.0
	Plugin Date: 2016-02-22
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: http://www.q2apro.com/pluginupdate?id=75
	
	Licence: Copyright © q2apro.com - All rights reserved
	
*/

if(!defined('QA_VERSION'))
{
	header('Location: ../../');
	exit;
}

// main page
qa_register_plugin_module('page', 'q2apro-uploadmanager-page.php', 'q2apro_uploadmanager_page', 'q2apro Upload Manager Page');

// rotate page
qa_register_plugin_module('page', 'q2apro-image-rotate-page.php', 'q2apro_image_rotate_page', 'q2apro Image Rotate Page');

// admin
qa_register_plugin_module('module', 'q2apro-uploadmanager-admin.php', 'q2apro_uploadmanager_admin', 'q2apro Upload Manager Admin');

// language file
qa_register_plugin_phrases('q2apro-uploadmanager-lang-*.php', 'q2apro_uploadmanager_lang');



/*
	Omit PHP closing tag to help avoid accidental output
*/