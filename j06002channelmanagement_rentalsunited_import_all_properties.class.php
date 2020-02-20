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

class j06002channelmanagement_rentalsunited_import_all_properties {
	function __construct($componentArgs)
		{
		// Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
		$MiniComponents =jomres_getSingleton('mcHandler');
		if ($MiniComponents->template_touch)
			{
			$this->template_touchable=false; return;
			}

		$ePointFilepath = get_showtime('ePointFilepath');

		$current_channel = channelmanagement_framework_utilities :: get_current_channel ( $this , array ( "j06002channelmanagement_" , "_import_all_properties" ) );

		$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
		$jrConfig = $siteConfig->get();
		
		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
		
		
		//First we need to check that Site has set the RU username and password
		if ( trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_username"]) == '' ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET','CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET',false) );
		}
		
		if ( trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_password"]) == '' ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET','CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET',false) );
		}

		$local_properties = channelmanagement_framework_properties::get_local_property_ids_for_channel( (int)$JRUser->userid , $current_channel );

		$mapped_dictionary_items = channelmanagement_framework_utilities :: get_mapped_dictionary_items ( $current_channel , $mapped_to_jomres_only = true );

			jr_import('channelmanagement_rentalsunited_communication');
			$this->channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();
			$this->channelmanagement_rentalsunited_communication->set_username($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_username"]);
			$this->channelmanagement_rentalsunited_communication->set_password($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_password"]);
			
			$property_data = $this->channelmanagement_rentalsunited_communication->communicate( array() , 'Pull_ListProp_RQ' );

			if ($property_data['Status']["value"] == "Success" ) {
				foreach ($property_data["Properties"]["Property"] as $property) {
					try  {
						channelmanagement_rentalsunited_import_property::import_property( $current_channel , $property['ID']["value"] , $mapped_dictionary_items , $JRUser->userid );
					} catch (Exception $e) {
						logging::log_message(" Failed  to import property : ".$e->getMessage(), 'ERROR');
					}
				}
			}
		jomresRedirect( jomresURL( JOMRES_SITEPAGE_URL . "&task=channelmanagement_framework" ) );
	}

	// This must be included in every Event/Mini-component
	function getRetVals()
		{
		return null;
		}
	}

	


