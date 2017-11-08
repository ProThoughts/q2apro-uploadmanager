<?php

/*
	Plugin Name: q2apro Upload Manager
	Plugin Author: q2apro.com
*/

	class q2apro_uploadmanager_admin
	{

		function option_default($option)
		{
			switch($option)
			{
				case 'q2apro_uploadmanager_enabled':
					return 1; // true
				case 'q2apro_uploadmanager_displayimages':
					return 0;
				case 'q2apro_uploadmanager_showpostlinks':
					return 0;
				case 'q2apro_uploadmanager_permission':
					return QA_PERMIT_ADMINS; // default level to access this page
				default:
					return null;				
			}
		}
			
		function allow_template($template)
		{
			return ($template!='admin');
		}       
			
		function admin_form(&$qa_content)
		{                       

			// process the admin form if admin hit Save-Changes-button
			$ok = null;
			if (qa_clicked('q2apro_uploadmanager_save'))
			{
				qa_opt('q2apro_uploadmanager_enabled', (bool)qa_post_text('q2apro_uploadmanager_enabled')); // empty or 1
				qa_opt('q2apro_uploadmanager_displayimages', (bool)qa_post_text('q2apro_uploadmanager_displayimages')); // empty or 1
				qa_opt('q2apro_uploadmanager_showpostlinks', (bool)qa_post_text('q2apro_uploadmanager_showpostlinks')); // empty or 1
				qa_opt('q2apro_uploadmanager_permission', (int)qa_post_text('q2apro_uploadmanager_permission')); // level
				$ok = qa_lang('admin/options_saved');
			}
			
			// form fields to display frontend for admin
			$fields = array();
			
			$fields[] = array(
				'type' => 'checkbox',
				'label' => qa_lang('q2apro_uploadmanager_lang/enable_plugin'),
				'tags' => 'name="q2apro_uploadmanager_enabled"',
				'value' => qa_opt('q2apro_uploadmanager_enabled'),
			);
			
			$fields[] = array(
				'type' => 'checkbox',
				'label' => qa_lang('q2apro_uploadmanager_lang/display_images'),
				'tags' => 'name="q2apro_uploadmanager_displayimages"',
				'value' => qa_opt('q2apro_uploadmanager_displayimages'),
			);
			
			$fields[] = array(
				'type' => 'checkbox',
				'label' => qa_lang('q2apro_uploadmanager_lang/show_postlinks'),
				'tags' => 'name="q2apro_uploadmanager_showpostlinks"',
				'value' => qa_opt('q2apro_uploadmanager_showpostlinks'),
			);
			
			$view_permission = (int)qa_opt('q2apro_uploadmanager_permission');
			$permitoptions = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);
			$pluginpageURL = qa_path('uploadmanager');
			
			$fields[] = array(
				'type' => 'static',
				'note' => qa_lang('q2apro_uploadmanager_lang/plugin_page_url').' <a target="_blank" href="'.$pluginpageURL.'">'.$pluginpageURL.'</a>',
			);
			$fields[] = array(
				'type' => 'select',
				'label' => qa_lang('q2apro_uploadmanager_lang/minimum_level'),
				'tags' => 'name="q2apro_uploadmanager_permission"',
				'options' => $permitoptions,
				'value' => $permitoptions[$view_permission],
			);
			$fields[] = array(
				'type' => 'static',
				'note' => '<span style="font-size:75%;color:#789;">'.strtr( qa_lang('q2apro_uploadmanager_lang/contact'), array( 
							'^1' => '<a target="_blank" href="http://www.q2apro.com/plugins/uploadmanager">',
							'^2' => '</a>'
						  )).'</span>',
			);
			
			return array(           
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'fields' => $fields,
				'buttons' => array(
					array(
						'label' => qa_lang_html('main/save_button'),
						'tags' => 'name="q2apro_uploadmanager_save"',
					),
				),
			);
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/