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


class channelmanagement_rentalsunited_list_remote_properties
{
    function __construct()
	{

	}
    
	function get_remote_properties()
	{
		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
		
		jr_import('channelmanagement_framework_user_accounts');
		$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
		$user_accounts = $channelmanagement_framework_user_accounts->get_accounts_for_user($JRUser->id);

		jr_import('channelmanagement_rentalsunited_communication');
		$this->channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();
		$this->channelmanagement_rentalsunited_communication->set_username($user_accounts['rentalsunited']['channel_management_rentals_united_username']);
		$this->channelmanagement_rentalsunited_communication->set_password($user_accounts['rentalsunited']['channel_management_rentals_united_password']);
		
		$property_data = $this->channelmanagement_rentalsunited_communication->communicate( array() , 'Pull_ListProp_RQ' );

		$remote_property_ids = array();
		if ($property_data['Status']["value"] == "Success" ) {
			foreach ($property_data["Properties"]["Property"] as $property) {
				$remote_property_ids[] = array ( "remote_property_id" => $property['ID']["value"] , "remote_property_name" => $property['Name'] );
				}
			}

		return $remote_property_ids;
	}


}
