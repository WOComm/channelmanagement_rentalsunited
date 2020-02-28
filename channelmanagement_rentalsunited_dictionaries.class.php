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


class channelmanagement_rentalsunited_dictionaries
{
    function __construct()
	{

		$this->current_channel_manager = "rentalsunited";
	
		if (!is_dir(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS)) {
			mkdir(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS);
			if (!is_dir(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS)) {
				throw new Exception("Cannot make ".JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS." directory, cannot continue.");
			}
		}
		
		$this->dictionaries = array();
		
		$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
		$jrConfig = $siteConfig->get();
		
		if ( trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_username"]) == '' ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET','CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET',false) );
		}
		
		if ( trim($jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_password"]) == '' ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET','CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET',false) );
		}
		
		$dictionary_array = $this->get_dictionary_initialisation_array();


        set_showtime("property_managers_id" , "system");

		if (!empty($dictionary_array)) {
            jr_import('channelmanagement_rentalsunited_communication');
            $channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

			foreach ($dictionary_array as $dictionary) {

				if (!file_exists(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS.$dictionary.'.json')) {
                    if (file_exists(RENTALS_UNITED_PLUGIN_ROOT.'templates'.JRDS."xml".JRDS.$dictionary.'.xml')) {
                        $auth = get_auth();

                        $output = array("AUTHENTICATION" => $auth);

                        $tmpl = new patTemplate();
                        $tmpl->addRows('pageoutput', array($output));
                        $tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
                        $tmpl->readTemplatesFromInput($dictionary . '.xml');
                        $xml_str = $tmpl->getParsedTemplate();

                        $response = $channelmanagement_rentalsunited_communication->communicate($dictionary, $xml_str);
                        if ($response != false) {
                            $encoded = json_encode($response, JSON_PRETTY_PRINT);
                            $file_contents = str_replace("@attributes", "xml_attributes", $encoded);
                            file_put_contents(JOMRES_CHANNEL_DICTIONARIES . JRDS . $this->current_channel_manager . JRDS . $dictionary . '.json', $file_contents);
                        } else {
                            file_put_contents(JOMRES_CHANNEL_DICTIONARIES . JRDS . $this->current_channel_manager . JRDS . $dictionary . '.json', '');
                            logging::log_message("Could not receive data from remote service, empty content returned for dictionary " . $dictionary, 'channel_management', 'ERROR', $this->current_channel_manager);
                        }
                    }
				} 
			}
			
			$this->read_dictionary_files();
		}
	}
    
	function read_dictionary_files()
	{
		$dictionary_array = $this->get_dictionary_initialisation_array();
		if (!empty($dictionary_array)) {
			foreach ($dictionary_array as $dictionary) {
				if (file_exists(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS.$dictionary.'.json')) {
					$this->dictionaries[$dictionary] = file_get_contents(JOMRES_CHANNEL_DICTIONARIES.JRDS.$this->current_channel_manager.JRDS.$dictionary.'.json');
				}
			}
		}
	}
	
	/*
	An array of dictionaries required for initialisation
	*/
	function get_dictionary_initialisation_array() 
	{

 		$arr = array (
			"Pull_ListStatuses_RQ",
			"Pull_ListPropTypes_RQ",
			"Pull_ListLocationTypes_RQ",
			//"Pull_ListLocations_RQ",
			"Pull_ListDestinations_RQ",
			"Pull_ListDistanceUnits_RQ",
			"Pull_ListCompositionRooms_RQ",
			"Pull_ListAmenities_RQ",
			"Pull_ListAmenitiesAvailableForRooms_RQ",
			"Pull_ListImageTypes_RQ",
			"Pull_ListPaymentMethods_RQ",
			"Pull_ListReservationStatuses_RQ",
			"Pull_ListDepositTypes_RQ",
			"Pull_ListLanguages_RQ",
			"Pull_ListPropExtStatuses_RQ",
			"Pull_ListChangeoverTypes_RQ",
			"Pull_ListOTAPropTypes_RQ"
		);

		return $arr;
	}

	/*
	Method to return dictionary lists that can be mapped to Jomres resources such as room types, property features etc
	*/
	function get_mappable_dictionary_items()
	{
		$arr = array (
			"Pull_ListOTAPropTypes_RQ" => array ( "type" => 'PropertyTypes', "sub_type" => "PropertyType" , "friendly_name" => jr_gettext('_JOMRES_FRONT_TARIFFS_ROOMTYPE','_JOMRES_FRONT_TARIFFS_ROOMTYPE',false) , "jomres_type" => "ptype" , "id_attribute" => 'OTACode' ),
			"Pull_ListAmenities_RQ" => array ( "type" => 'Amenities', "sub_type" => "Amenity" , "friendly_name" => jr_gettext('_JOMRES_COM_MR_VRCT_PROPERTYFEATURES_HEADER_LINK','_JOMRES_COM_MR_VRCT_PROPERTYFEATURES_HEADER_LINK',false) , "jomres_type" => "pfeature" , "id_attribute" => 'AmenityID' ),  
			"Pull_ListAmenitiesAvailableForRooms_RQ" =>  array ( "type" => 'AmenitiesAvailableForRooms', "sub_type" => "AmenitiesAvailableForRoom" , "friendly_name" => jr_gettext('_JOMRES_HRESOURCE_FEATURES','_JOMRES_HRESOURCE_FEATURES',false) , "jomres_type" => "rmfeature" , "id_attribute" => 'AmenityID'  ), 
			//"Pull_ListLocations_RQ" =>  array ( "type" => 'Destinations', "sub_type" => "Destination" , "friendly_name" => jr_gettext('_JOMRES_COM_MR_VRCT_PROPERTY_HEADER_REGION','_JOMRES_COM_MR_VRCT_PROPERTY_HEADER_REGION',false) , "jomres_type" => "location" , "id_attribute" => 'DestinationID'  ),
			"Pull_ListCompositionRooms_RQ" => array ( "type" => 'CompositionRooms', "sub_type" => "CompositionRoom" , "friendly_name" => jr_gettext('_JOMRES_COM_MR_VRCT_TAB_ROOMTYPES','_JOMRES_COM_MR_VRCT_TAB_ROOMTYPES',false) , "jomres_type" => "rmtype" , "id_attribute" => 'CompositionRoomID' ),
		);
		
		return $arr;
	}
}
