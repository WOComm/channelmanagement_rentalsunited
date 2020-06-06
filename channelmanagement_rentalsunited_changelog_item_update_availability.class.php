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

class channelmanagement_rentalsunited_changelog_item_update_availability
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

		$property_settings = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'GET' , 'cmf/property/settings/'.$changelog_item->local_property_id , [] );


		set_showtime("property_managers_id" , $changelog_item->manager_id );
		$auth = get_auth();

		$date_from	=  date("Y-m-d");
		$date_to	= date('Y-m-d',strtotime(date("Y-m-d") .'+1 year'));

		$output = array(
			"AUTHENTICATION" => $auth,
			"PROPERTY_ID" => $changelog_item->remote_property_id,
			"LOCATION_ID" => $property_settings->data->response->RENTALSUNITED_SETTING_LocationID,
			"DATE_FROM"			=> $date_from,
			"DATE_TO"			=> $date_to
		);

		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListPropertiesBlocks_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_property_blocks = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListPropertiesBlocks_RQ' , $xml_str , $clear_cache = true );

		$atts = '@attributes';
		$blocks = $remote_property_blocks["Properties"]["PropertyBlock"];

		if (isset($blocks[$atts])){ // Only one property was returned, we'll put this one block set into an array and then parse the array
			$blocks = array ($blocks);
		}

		if (!empty($blocks)) {
			foreach ($blocks as $property_blocks) {
				 if ($property_blocks[$atts]["PropertyID"] == $changelog_item->remote_property_id) {
				 	if ( isset($property_blocks['Block']) && !empty($property_blocks['Block']) ) {
				 		foreach ($property_blocks['Block'] as $block ) {
							$data_array = array (
								"property_uid"			=> $changelog_item->local_property_id,
								"availability"			=> json_encode(array (
									"date_from"			=> $block["DateFrom"],
									"date_to"			=> $block["DateTo"]
								)),
								"room_ids" => '[]',
								"remote_booking_id" => ''
							);

							$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/blackbooking/' , $data_array );
				 		}
					}
				 }
			}
		}

	}


}