<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( 'Direct Access to this file is not allowed.' );
// ################################################################

class j21150channelmanagement_rentalsunited_sanity_checks 
{
	function __construct($componentArgs) {
		// Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return 
		$MiniComponents =jomres_getSingleton('mcHandler');
		if ($MiniComponents->template_touch)
			{
			$this->template_touchable=false; return;
			}
		
		$current_channel = channelmanagement_framework_utilities :: get_current_channel ( $this , array ( "j21150channelmanagement_" , "_sanity_checks" ) );
		
		$channel_sanity_checks_errors = get_showtime('channel_sanity_checks_errors');
		
		$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
		$jrConfig = $siteConfig->get();

		if ( !isset($jrConfig['channel_manager_framework_user_accounts']['rentalsunited'][ 'channel_management_rentals_united_username' ]) || trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited'][ 'channel_management_rentals_united_username' ]) == '' ) {
			$channel_sanity_checks_errors[] = jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE', 'CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE', false);
			}
		
		if ( !isset($jrConfig['channel_manager_framework_user_accounts']['rentalsunited'][ 'channel_management_rentals_united_password' ]) || trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited'][ 'channel_management_rentals_united_password' ]) == '' ) {
			$channel_sanity_checks_errors[] = jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE', 'CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE', false);
			}
		
		/* $dictionary_class_name = 'channelmanagement_'.$current_channel.'_dictionaries';
		jr_import($dictionary_class_name);
		if ( !class_exists($dictionary_class_name) ) {
			echo jr_gettext('CHANNELMANAGEMENT_FRAMEWORK_MAPPING_CHANNEL_DICTIONARY_CLASS_DOESNT_EXIST','CHANNELMANAGEMENT_FRAMEWORK_MAPPING_CHANNEL_DICTIONARY_CLASS_DOESNT_EXIST',false);
			return;
		}
		$dictionary_class = new $dictionary_class_name();
		$dictionary_items = $dictionary_class->get_mappable_dictionary_items();
		
		$required_rows = array();
		foreach ($dictionary_items as $key=>$val) {
			$required_rows[]
		} */
		
		set_showtime('channel_sanity_checks_errors' , $channel_sanity_checks_errors );
		
	}


	// This must be included in every Event/Mini-component
	function getRetVals()
		{
		return null;
		}
	}
