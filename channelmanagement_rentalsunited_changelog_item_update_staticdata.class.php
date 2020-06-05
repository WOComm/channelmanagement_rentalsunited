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

require_once('XMLParser.php');
use XMLParser\XMLParser;

class channelmanagement_rentalsunited_changelog_item_update_staticdata
{


	function __construct($item = null )
	{
		$channel = 'rentalsunited';

		if (is_null($item)) {
			throw new Exception('Item object is empty');
		}


		$changelog_item = unserialize(base64_decode($item->item));

		if (!isset($changelog_item->remote_property_id)) {
			throw new Exception("remote_property_id not set");
		}

		if (!isset($changelog_item->local_property_id)) {
			throw new Exception("local_property_id not set");
		}

		if (!isset($changelog_item->manager_id)) {
			throw new Exception("manager_id not set");
		}


		/* Last modification of the property's data (living space, address, coordinates, amenities, composition, etc.) */
		jr_import('channelmanagement_rentalsunited_communication');
		$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();
		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');

		$mapped_dictionary_items = channelmanagement_framework_utilities :: get_mapped_dictionary_items ( $channel , $mapped_to_jomres_only = true );

		set_showtime("property_managers_id" , $changelog_item->manager_id );
		$auth = get_auth();

		$output = array(
			"AUTHENTICATION" => $auth,
			"PROPERTY_ID" => $changelog_item->remote_property_id,
		);


		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListSpecProp_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_property = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListSpecProp_RQ' , $xml_str , true  );

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


			// We need to collate information about the property, room features, property features etc. Duplicates can appear so we need to array unique the property later
			// Not the best place for this, probably needs to be in the cmf libs

			// room types
			$property_room_types = array();
			if (!empty($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'])){
				foreach ($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'] as $amenity) {
					$amenity_id = $amenity[$atts]['CompositionRoomID'];
					if ($amenity_id == 257 ) {  // Will this change?
						if ( isset($mapped_dictionary_items['Pull_ListCompositionRooms_RQ']) && array_key_exists ( $amenity_id , $mapped_dictionary_items['Pull_ListCompositionRooms_RQ'] ) ) {
							$arr = $mapped_dictionary_items['Pull_ListCompositionRooms_RQ'][$amenity_id];
							unset($arr->item);

							$count = 0;
							foreach ($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'] as $a) {
								$a_id = $a[$atts]['CompositionRoomID'];
								if ( $a_id == $amenity_id ) {
									$count++;
								}
							}
							$property_room_types[] = array ( "amenity" => $arr , "count" => $count , "max_guests" => $remote_property['Property']['StandardGuests'] ) ;
						}
					}
				}
				$property_room_types = array_unique($property_room_types, SORT_REGULAR);
			}

			// room features
			$property_room_features = array();
			if (!empty($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities']) && !empty($mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'] )){
				foreach ($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'] as $amenity) {
					$amenity_id = $amenity[$atts]['CompositionRoomID'];

					if ( isset($mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ']) && array_key_exists ( $amenity_id , $mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'] ) ) {
						$arr = $mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'][$amenity_id];
						unset($arr->item);
						$property_room_features[] = $arr;
					}

				}
				$property_room_features = array_unique($property_room_features, SORT_REGULAR);
			}

			// Property features
			$property_features = array();

			if (!empty($remote_property['Property']['Amenities']['Amenity'])){
				foreach ($remote_property['Property']['Amenities']['Amenity'] as $amenity) {
					$amenity_id = $amenity['value'];
					if (  isset($mapped_dictionary_items['Pull_ListAmenities_RQ']) && array_key_exists ( $amenity_id , $mapped_dictionary_items['Pull_ListAmenities_RQ'] ) ) {
						$arr = $mapped_dictionary_items['Pull_ListAmenities_RQ'][$amenity_id];
						unset($arr->item);
						$property_features[] = $arr;
					}

				}
				$property_features = array_unique($property_features, SORT_REGULAR);
			}

			// Find the local property type for this property
			$local_property_type = 0;

			if (isset($remote_property['Property']['ObjectTypeID'])){
				$local_property_type = 0;
				foreach ($mapped_dictionary_items['Pull_ListOTAPropTypes_RQ'] as $mapped_property_type) {
					if ($remote_property['Property']['ObjectTypeID'] == $mapped_property_type->remote_item_id) {

						$local_property_type = $mapped_property_type->jomres_id;
						$mrp_or_srp = channelmanagement_rentalsunited_import_property::get_property_type_booking_model( $local_property_type ); // Is this an MRP or SRP?
					}
				}

				// local property type was never found for this property. Throw an error and stop trying as we can't create the property
				if ( $local_property_type == 0 ) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYTYPE_NOTFOUND',false)." Remote property type ".$remote_property['Property']['ObjectTypeID'] );
				}

				if (!isset($mrp_or_srp)) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND',false) );
				}
			} else {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_REMOTEPROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_REMOTEPROPERTYTYPE_NOTFOUND',false) );
			}

			$new_property = new stdclass();

			$new_property->property_details['property_id']				= $remote_property['Property']['ID']['value'];
			$new_property->property_details['remote_ptype_id']			= $remote_property['Property']['ObjectTypeID'];
			$new_property->property_details['local_ptype_id']			= $local_property_type;

			$new_property->property_details['name']						= $remote_property['Property']['Name'];

			$new_property->property_details['street']					= $remote_property['Property']['Street'];
			$new_property->property_details['postcode']					= $remote_property['Property']['ZipCode'];
			$new_property->property_details['email']					= $remote_property['Property']['ArrivalInstructions']['Email'];
			$new_property->property_details['tel']						= $remote_property['Property']['ArrivalInstructions']['Phone'];
			$new_property->property_details['postcode']					= $remote_property['Property']['ZipCode'];
			$new_property->property_details['lat']						= $remote_property['Property']['Coordinates']['Latitude'];
			$new_property->property_details['long']						= $remote_property['Property']['Coordinates']['Longitude'];

			$new_property->deposits['remote_deposit_type_id']			= $remote_property['Property']['Deposit'][$atts]['DepositTypeID'];
			$new_property->deposits['remote_deposit_value'] 			= $remote_property['Property']['Deposit']['value'];

			$new_property->deposits['remote_security_deposit_type_id']	= $remote_property['Property']['Deposit'][$atts]['DepositTypeID'];
			$new_property->deposits['remote_security_deposit_value']	= $remote_property['Property']['Deposit']['value'];


			// Move to tariffs?
			$new_property->property_details['max_guests']				= $remote_property['Property']['CanSleepMax'];

			$new_property->remote_room_features				= $property_room_features;
			$new_property->remote_property_features			= $property_features;

			$new_property_basics_array =  array (
				"property_name" => $new_property->property_details['name'] ,
				"remote_uid" => $new_property->property_details['property_id'] ,
				"country" => $country_code ,
				"region_id" => $region_id ,
				"ptype_id" => $local_property_type
			);

			// Check and create settings
			$settings = array (
				"property_currencycode"		=> $remote_property['Property'][$atts]['Currency'],  // The property's currency code
				"singleRoomProperty"		=> $mrp_or_srp, // Is the property an MRP or an SRP?
				"tariffmode" 				=> '2'  // Micromanage automatically
			);

			// Room prices

			jr_import('channelmanagement_rentalsunited_import_prices');
			// $mrp_or_srp

			foreach ($property_room_types as $room_type ) {

				try { // It's ok-ish if this fails, the webhook watcher may put the prices right later
					channelmanagement_rentalsunited_import_prices::import_prices( $changelog_item->manager_id , $channel , $changelog_item->remote_property_id , $changelog_item->local_property_id , $remote_property['Property']['CanSleepMax'] , $room_type['amenity']->jomres_id );
				}
				catch (Exception $e) {
					logging::log_message("Failed to add property tariffs. Error message : ".$e->getMessage()." -- Remote property id ".$changelog_item->remote_property_id , 'RENTALS_UNITED', 'INFO' , '' );
				}

				// Trying to figure out how many rooms there are in the property.
				$number_of_rooms = floor( (int)$remote_property['Property']['CanSleepMax'] / (int)$remote_property['Property']['StandardGuests'] );
				if ( $number_of_rooms == 0 ) {
					$number_of_rooms = 1;
				}

				$data_array = array (
					"property_uid"	=> $changelog_item->local_property_id,
					"rooms"			=> json_encode( array($room_type))
				);

				$rooms_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/rooms/' , $data_array );
			}


			/*
			<DepositType DepositTypeID="1">No deposit</DepositType>
			<DepositType DepositTypeID="2">Percentage of total price (without cleaning)</DepositType>
			<DepositType DepositTypeID="3">Percentage of total price</DepositType>
			<DepositType DepositTypeID="4">Fixed amount per day</DepositType>
			<DepositType DepositTypeID="5">Flat amount per stay</DepositType>
			*/

			$deposit_type	= $remote_property['Property']['Deposit'][$atts]['DepositTypeID'];
			$deposit_value	= $remote_property['Property']['Deposit']['value'];

			switch ($deposit_type) {
				case 1:
					$settings['chargeDepositYesNo'] = "0";
					break;
				case 2:
				case 3:
					$settings['depositIsPercentage'] = "1";
					$settings['depositValue'] = $deposit_value;
					break;
				case 4:
					$settings['depositIsPercentage'] = "0"; // Type 4 in Jomres is not supported, so we will go with fixed amount per stay instead
					$settings['chargeDepositYesNo'] = $deposit_value;
					break;
				case 5:
					$settings['depositIsPercentage'] = "0";
					$settings['chargeDepositYesNo'] = $deposit_value;
					break;
			}

			$post_data = array ( "property_uid"		=> $changelog_item->local_property_id , "params" => json_encode($settings) ); // mrConfig array values are property specific settings
			$settings_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/settings' , $post_data );

			// Now that we have a new property setup, let's start adding it's various information items.

			// Location
			$data_array = array (
				"property_uid"		=> $changelog_item->local_property_id,
				"country_code"		=> $new_property_basics_array['country'],
				"region_id" 		=> $new_property_basics_array['region_id'],
				"lat" 				=> $new_property->property_details['lat'],
				"long" 				=> $new_property->property_details['long'],

			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/location/' , $data_array );

			// Contacts
			$data_array = array (
				"property_uid"		=> $changelog_item->local_property_id,
				"telephone"			=> $new_property->property_details['tel'],
				"fax" 				=> '',
				"email" 			=> $new_property->property_details['email']
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/contacts/' , $data_array );

			// Address
			$data_array = array (
				"property_uid"	=> $changelog_item->local_property_id,
				"house"			=> $new_property_basics_array['property_name'],  // The RU data I'm working with doesn't have address details, so to prevent the system from complaining that the property address details are incomplete, we'll set this to blank for now
				"street" 		=> ' ',
				"town" 			=> ' ',
				"postcode"		=> ' '
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/address/' , $data_array );

			// Stars
			$data_array = array (
				"property_uid"	=> $changelog_item->local_property_id,
				"stars"			=> 0,
				"superior" 		=> 0
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/stars/' , $data_array );

			// Property Features
			$features_str = '';
			if (!empty($property_features)) {
				foreach ( $property_features as $feature ) {
					$features_str .= $feature->jomres_id.",";
				}
			}

			$data_array = array (
				"property_uid"		=> $changelog_item->local_property_id,
				"features"			=> $features_str
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/features/' , $data_array );

			// Publishing
			$data_array = array (
				"property_uid"			=> $changelog_item->local_property_id
			);
			$property_status_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/status/review' , $data_array );

			if (isset($property_status_response->data->response) && $property_status_response->data->response->property_complete == true ) {
				$data_array = array (
					"property_uid"			=> $changelog_item->local_property_id
				);
				$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/publish/' , $data_array );
			} else {
				$data_array = array (
					"property_uid"			=> $changelog_item->local_property_id
				);
				$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/unpublish/' , $data_array );
			}
		} else { // Unpublish the local property and do nothing else
			$data_array = array (
				"property_uid"			=> $changelog_item->local_property_id
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/unpublish/' , $data_array );

		}
		$this->success = true; // Regardless of whether or not the property was published, the task was completed successfully and we will report success
	}


}