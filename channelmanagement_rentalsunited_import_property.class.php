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


class channelmanagement_rentalsunited_import_property
{
	
	public static function import_property( $channel , $remote_property_id = 0 , $proxy_id = 0 )
	{

		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');
		$channelmanagement_framework_singleton->init(999999999);


		$mapped_dictionary_items = channelmanagement_framework_utilities :: get_mapped_dictionary_items ( $channel , $mapped_to_jomres_only = true );

		$channelmanagement_framework_singleton->proxy_manager_id = $JRUser->userid;

        jr_import('channelmanagement_rentalsunited_communication');
        $channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

		$new_property_id = 0;

		try {
			$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
			set_showtime("property_managers_id" , $JRUser->id );
			$auth = get_auth();

			$output = array(
				"AUTHENTICATION" => $auth,
				"PROPERTY_ID" => $remote_property_id,
			);


			$tmpl = new patTemplate();
			$tmpl->addRows('pageoutput', array($output));
			$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
			$tmpl->readTemplatesFromInput('Pull_ListSpecProp_RQ.xml');
			$xml_str = $tmpl->getParsedTemplate();

			$remote_property = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListSpecProp_RQ' , $xml_str );

			// IsArchived
			if ($remote_property['Property']['IsArchived'] != "true" ) {
				$atts = '@attributes';

				// New we'll pull location information for this property. The location/information endpoint will try to fuzzy guess the region id if it can't find an exact match
				$response_location_information = $channelmanagement_framework_singleton->rest_api_communicate($channel, 'GET', 'cmf/location/information/' . $remote_property['Property']['Coordinates']['Latitude'] . '/' . $remote_property['Property']['Coordinates']['Longitude'] . '/');

				if (!isset($response_location_information->data->response->country_code) || trim($response_location_information->data->response->country_code) == '') {
					throw new Exception(jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_COUNTRY_CODE_NOT_FOUND', 'CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_COUNTRY_CODE_NOT_FOUND', false));
				}

				if (!isset($response_location_information->data->response->region_id) || trim($response_location_information->data->response->region_id) == '') {
					throw new Exception(jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REGION_ID_NOT_FOUND', 'CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REGION_ID_NOT_FOUND', false));
				}

				if ( $response_location_information->data->response->region_id != 0 ) {
					if (isset($response_location_information->data->response->country_code)) {
						$country_code = strtoupper(($response_location_information->data->response->country_code));
						$region_id = $response_location_information->data->response->region_id;
					}
				} else {
					$output = array(
						"AUTHENTICATION" => $auth,
						"LOCATION_ID" => $remote_property['Property']['DetailedLocationID']['value'],
					);

					$tmpl = new patTemplate();
					$tmpl->addRows('pageoutput', array($output));
					$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
					$tmpl->readTemplatesFromInput('Pull_GetLocationDetails_RQ.xml');
					$xml_str = $tmpl->getParsedTemplate();

					$remote_property_location = $channelmanagement_rentalsunited_communication->communicate( 'Pull_GetLocationDetails_RQ' , $xml_str );

					if (!isset($remote_property_location['Locations']['Location'][3]['value'])) {
						throw new Exception( "Pull_GetLocationDetails_RQ Cannot get detailed location information for property" );
					}
					$country_code = $remote_property_location->data->response->country_code;
					$location_information =channelmanagement_framework_utilities :: search_for_region_id ( $country_code , $remote_property_location['Locations']['Location'][3]['value'] );
					if (!isset($location_information->jomres_region_id)){
						throw new Exception( "Could not figure out region id" );
					}
					$region_id = $location_information->jomres_region_id;
				}

				// Find the local property type for this property
				$ptype = get_property_type_rentalsunited( $mapped_dictionary_items , $remote_property['Property']['ObjectTypeID']);
				$local_property_type	= $ptype['local_property_type'];
				$mrp_srp_flag 			= $ptype['mrp_srp_flag'];

				// local property type was never found for this property. Throw an error and stop trying as we can't configure the property
				if ( $local_property_type == 0 ) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYTYPE_NOTFOUND',false)." Remote property type ".$remote_property['Property']['ObjectTypeID'] );
				}

				if ( !isset($mrp_srp_flag) ) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND',false)." Remote property type ".$remote_property['Property']['ObjectTypeID'] );
				}

				$new_property_basics_array =  array (
					"property_name" => $remote_property['Property']['Name'] ,
					"remote_uid" => (int)$remote_property['Property']['ID']['value'] ,
					"country" => $country_code ,
					"region_id" => $region_id ,
					"ptype_id" => $local_property_type
					);

				// Create the new property
				$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'POST' , 'cmf/property/' , $new_property_basics_array );

				if (!isset($response->data->response)) {
					throw new Exception( "response->data->response not set, failed to create property." );
				}

				$new_property_id = $response->data->response;

				if ((int)$new_property_id < 1 ) {
					throw new Exception( "Did not receive new property uid, failed to create property." );
				}

				// Policy is to not allow editing of property configuration through the Jomres UI.
				// A normal user should not be able to  edit the settings locally, instead it is preferred that they change the settings on the parent and allow those changes to trickle down to the child. The only things that should be send upstream to the parent are bookings and reviews (todo).
				// To enforce that policy, we set the management_url flag, however if this server is in development mode (e.g. you are writing your own thin plugin) then it's helpful to set the allow_channel_property_local_admin option to 1 so that you can inspect the property's local configuration

				// If, as the thin plugin developer, you want to send more upstream then of course you can write your own code to support that and leave the management url unset

				$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
				$jrConfig = $siteConfig->get();

				// Management url
				$data_array = array (
					"property_uid"			=> $new_property_id,
					"management_url"		=> get_remote_admin_uri_rentalsunited(  $remote_property_id )
				);

				$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/management/url' , $data_array );

				$allow_channel_property_local_admin = 0;
				if ($jrConfig['development_production'] == 'development') { // If we are in development mode, we don't want to use caching
					$allow_channel_property_local_admin = 1;
				}

				$settings = array (
					"property_currencycode"					=> $remote_property['Property'][$atts]['Currency'],  // The property's currency code
					"singleRoomProperty"					=> $mrp_srp_flag, // Is the property an MRP or an SRP?
					"tariffmode" 							=> '2',  // Micromanage automatically
					"allow_channel_property_local_admin"	=> $allow_channel_property_local_admin
				);

				$post_data = array ( "property_uid"		=>$new_property_id , "params" => json_encode($settings) ); // mrConfig array values are property specific settings
				$settings_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/settings' , $post_data );

				// Pulls changelog events from the remote server, inserts them into the queue and then processes those queue items
				// These are the same tasks that are performed as cron jobs at quarter hourly intervals

				$MiniComponents = jomres_singleton_abstract::getInstance('mcHandler');
				$MiniComponents->specificEvent('06000', 'cron_get_remote_changelog_items', array());
				$MiniComponents->specificEvent('06000', 'cron_process_remote_changelog_items', array());


				// Publishing
				// We need to force a status review, where the system will see if the property is complete. If it is, we can publish it

				$data_array = array(
					"property_uid"	=> $new_property_id
				);

				$property_status_review_response = $channelmanagement_framework_singleton->rest_api_communicate($channel, 'PUT', 'cmf/property/status/review/', $data_array);

				if ($property_status_review_response->data->response->property_complete == true) {

					$property_status_response = $channelmanagement_framework_singleton->rest_api_communicate($channel, 'GET', 'cmf/property/status/' . $new_property_id, array());

					if (isset($property_status_response->data->response) && (int)$property_status_response->data->response->status_code == 2) {
						$data_array = array(
							"property_uid" => $new_property_id
						);
						$property_publish_response = $channelmanagement_framework_singleton->rest_api_communicate($channel, 'PUT', 'cmf/property/publish', $data_array);
					}

					return (object)array("success" => true, "new_property_id" => $new_property_id);
				} else {
					return (object)array("success" => false, "message" => "Property appeared to be created but is incomplete.");
				}
			}
		}
		catch (Exception $e)
		{
			if ($new_property_id > 0 ) {
				$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'DELETE' , 'cmf/property/local/'.$new_property_id );
				logging::log_message("Failed to create new property, so incomplete and removed again. Error message : ".$e->getMessage()." -- Remote property id ".$remote_property_id , 'RENTALS_UNITED', 'INFO' , '' );
			}

		}
	}
	
	
 	// Pass the OTA prop type, 
	public static function get_property_type_booking_model( $local_property_type = 0 )
	{

		if ( $local_property_type == 0 ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND',false) );
		}
		
		jr_import('jomres_property_types');
		$jomres_property_types = new jomres_property_types();
		$jomres_property_types->get_all_property_types();
		
		if (array_key_exists( $local_property_type , $jomres_property_types->property_types ) ) {
			if (isset($jomres_property_types->property_types [$local_property_type]['mrp_srp_flag'])) {
				return $jomres_property_types->property_types [$local_property_type]['mrp_srp_flag']; // 0 = mrp 1 = srp
			} else {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND_IN_PROPERTY_TYPE','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND_IN_PROPERTY_TYPE',false) );
			}
		} else {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND',false) );
		}
	
	}
}

