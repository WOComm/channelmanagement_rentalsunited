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

class channelmanagement_rentalsunited_changelog_item_update_pricing
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


		jr_import('channelmanagement_rentalsunited_communication');
		$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

		$mapped_dictionary_items = channelmanagement_framework_utilities :: get_mapped_dictionary_items ( $channel , $mapped_to_jomres_only = true );

		// Getting mapped dictionary items resets the proxy id, so we need to reset it here to the changelog item's manager id
		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');
		$channelmanagement_framework_singleton->proxy_manager_id = $changelog_item->manager_id;

		set_showtime("property_managers_id" , $changelog_item->manager_id );
		$auth = get_auth();

		$date_from	=  date("Y-m-d");
		$date_to	= date('Y-m-d',strtotime(date("Y-m-d") .'+1 year'));

		$output = array(
			"AUTHENTICATION"	=> $auth,
			"PROPERTY_ID"		=> $changelog_item->remote_property_id,
			"DATE_FROM"			=> $date_from,
			"DATE_TO"			=> $date_to
		);

		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListPropertyPrices_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_prices = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListPropertyPrices_RQ' , $xml_str , true );

		$output = array(
			"AUTHENTICATION"	=> $auth,
			"PROPERTY_ID"		=> $changelog_item->remote_property_id,
			"DATE_FROM"			=> $date_from,
			"DATE_TO"			=> $date_to
		);

		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListPropertyAvailabilityCalendar_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_availability = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListPropertyAvailabilityCalendar_RQ' , $xml_str , true );

		$output = array(
			"AUTHENTICATION" => $auth,
			"PROPERTY_ID" => $changelog_item->remote_property_id,
		);

		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListSpecProp_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_property = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListSpecProp_RQ' , $xml_str );

		$property_room_types = array();
		if (!empty($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'])){
			$property_room_types =get_property_room_types_rentalsunited( $mapped_dictionary_items , $remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'] , $remote_property['Property']['StandardGuests'] );
		}

		if (empty($property_room_types)) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_ROOM_TYPES_NOT_FOUND','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_ROOM_TYPES_NOT_FOUND',false) );
		}

		$delete_rooms_result = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'DELETE' , 'cmf/property/rooms/'.$changelog_item->local_property_id , [] );
		// DELETE property rooms will fail if there are bookings for the property
		// Note, it checks the room_bookings table, not the contracts table

		if ($delete_rooms_result->data->response == true) {
			foreach ($property_room_types as $room_type ) {
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
		}


		$delete_tariffs_result = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'DELETE' , 'cmf/property/tariffs/'.$changelog_item->local_property_id , [] );

		$atts = '@attributes';
		$CalDays = array();

		if ( isset($remote_availability['PropertyCalendar']['CalDay']) ) {
			foreach ($remote_availability['PropertyCalendar']['CalDay'] as $CalDay ) {
				$date =  strtotime(str_replace ( "-" , "/" , $CalDay[$atts]['Date']));
				$CalDays[$date] = array ( "MinStay" => $CalDay['MinStay']);
			}
		}

		$primary_price_set	= array();
		$extra_price_set	= array();

		if ( !empty($remote_prices['Prices']['Season'])) {

			foreach ($remote_prices['Prices']['Season'] as $season ) {
				if ( !isset($season[$atts]) ) {
					if (isset($season['DateFrom'])){
						$season[$atts]['DateFrom'] = $season['DateFrom'];
						$season['Price'] = $remote_prices['Prices']['Season']['Price'];
						$season['Extra'] = $remote_prices['Prices']['Season']['Extra'];
					} else {
						throw new Exception( "DateFrom in season not set" );
					}
					if (isset($season['DateTo'])){
						$season[$atts]['DateTo'] = $season['DateTo'];
					} else {
						throw new Exception( "DateTo in season not set" );
					}
				}

				$from_date = str_replace ( "-" , "/" , $season[$atts]['DateFrom']) ;
				$to_date = str_replace ( "-" , "/" , $season[$atts]['DateTo']) ;

				if (!DateTime::createFromFormat('Y/m/d', $from_date )) {
					throw new Exception( "DateFrom not valid" );
				}

				if (!DateTime::createFromFormat('Y/m/d', $to_date )) {
					throw new Exception( "DateTo not valid" );
				}

				$date_range_array = findDateRangeForDates( $from_date , $to_date);

				if (isset($season['Price'])) {
					$dates_and_prices = array();

					$price_per_person =  $season['Price']/2;
					$price_per_person_extra =  ($season['Price']/2)+$season['Extra'];

					foreach ($date_range_array as $date ) {
						$stt_date = strtotime($date);

						if (isset($CalDays[$stt_date])) {
							$minDays = $CalDays[$stt_date]['MinStay'];
						} else {
							$minDays = 1;
						}

						$primary_price_set[$stt_date] = array ( "price" => $price_per_person , "mindays" =>$minDays , "minpeople" => 1 , "maxpeople" => 2 ) ;

						if (isset($season['Extra'])) {  // We'll create an extra tariff for min-max 3 people too
							$extra_price_set[$stt_date] = array ( "price" => $price_per_person_extra , "mindays" =>$minDays , "minpeople" => 3 , "maxpeople" => $remote_property['Property']['CanSleepMax']  ) ;
						}
					}
				}

				foreach ($property_room_types as $room_type) {
					$basic_post_data = array (
						"property_uid"					=> $changelog_item->local_property_id ,
						"tarifftypeid"					=> 0 , // Create a new micromanage tariff
						"rate_title"					=> "Tariff" ,
						"rate_description"				=> "Tariff description" ,
						"maxdays"						=> 364 ,
						"roomclass_uid"					=> $room_type['amenity']->jomres_id ,
						"dayofweek"						=> 7 , // Every day
						"ignore_pppn"					=> 0 , // Ignore per person per night flag in property config set to No.
						"allow_we"						=> 1 , // Allow bookings to span weekends
						"weekendonly"					=> 0 , // Bookings for this tariff only allowed if all days in the booking are on the weekend = No
						"minrooms_alreadyselected"		=> 0 , // Specialised setting, do not change unless you understand the consequences
						"maxrooms_alreadyselected"		=> 1000 , // Specialised setting, do not change unless you understand the consequences
					);



					if (!empty($primary_price_set)) {

						$all_keys = array_keys($primary_price_set);
						$date_from = date( "Y-m-d" , array_shift($all_keys)) ;
						$date_to = date( "Y-m-d" , end($all_keys));


						$post_data = $basic_post_data;

						$post_data['rate_title'] = jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_TARIFF_PRIMARY_SET','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_TARIFF_PRIMARY_SET',false). " :: ".$date_from. " - ".$date_to ;
						$counter = 0;
						foreach ($primary_price_set as $key => $vals ) {
							$post_data["tariffinput"][$key] = $vals['price'];
							$post_data["mindaysinput"][$key] = $vals['mindays'];
							$post_data['minpeople'] = $vals['minpeople'];
							$post_data['maxpeople'] = $vals['maxpeople'];
							$counter++;
							if ($counter == 365 ) {
								break;
							}
						}

						$primary_tariff_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/tariff/' , $post_data );
						$responses[] = $primary_tariff_response;
					}

					if (!empty($extra_price_set)) {
						$post_data = $basic_post_data;
						$post_data['rate_title'] = jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_TARIFF_EXTRA_SET','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_TARIFF_EXTRA_SET',false). " :: ".$date_from. " - ".$date_to ;
						$counter = 0;
						foreach ($extra_price_set as $key => $vals ) {
							$post_data["tariffinput"][$key] = $vals['price'];
							$post_data["mindaysinput"][$key] = $vals['mindays'];
							$post_data['minpeople'] = $vals['minpeople'];
							$post_data['maxpeople'] = $vals['maxpeople'];
							$counter++;
							if ($counter == 10 ) {
								break;
							}
						}
						$secondary_tariff_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/tariff/' , $post_data );
						$responses[] = $secondary_tariff_response;
					}
					// var_dump($responses);exit;
				}
			}
		}

	}


}