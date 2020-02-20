<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright 2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/
// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

class j06002channelmanagement_rentalsunited_dashboard {
	function __construct($componentArgs)
		{
		// Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
		$MiniComponents =jomres_getSingleton('mcHandler');
		if ($MiniComponents->template_touch)
			{
			$this->template_touchable=false; return;
			}

		$ePointFilepath = get_showtime('ePointFilepath');
		
		$current_channel = channelmanagement_framework_utilities :: get_current_channel ( $this , array ( "j06002channelmanagement_" , "_dashboard" ) );

		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
		
		jr_import('channelmanagement_framework_user_accounts');
		if (!class_exists('channelmanagement_framework_user_accounts')) {
			throw new Exception('Error: Channel management framework plugin not installed');
		}

		$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
		$user_accounts = $channelmanagement_framework_user_accounts->get_accounts_for_user($JRUser->id);

		if (empty($user_accounts)) {
			jomresRedirect(jomresURL(JOMRES_SITEPAGE_URL.'&task=channelmanagement_framework_user_accounts&jr_redirect_url='.jr_base64url_encode(getCurrentUrl())), '');
		}

		$local_properties = channelmanagement_framework_properties::get_local_property_ids_for_channel( (int)$JRUser->userid , $current_channel );
		
		$item = $MiniComponents->specificEvent('06002', 'channelmanagement_rentalsunited_setup' , array("output_now" => false ) );
		$dashboard_items = array ( $item );
		
		$items = get_showtime("cmf_dashboard_items");
		if (empty($items)) {
			$items = $dashboard_items;
		} else {
			$items = array_merge( $dashboard_items , $items );
		}
		set_showtime("cmf_dashboard_items" , $items );
	}

	// This must be included in every Event/Mini-component
	function getRetVals()
		{
		return null;
		}
	}
