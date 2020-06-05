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

class channelmanagement_rentalsunited_changelog_item_update_description
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
		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');

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

			// Descriptive texts
			$data_array = array (
				"property_uid"			=> $changelog_item->local_property_id,
				"description"			=> $remote_property['Property']['Descriptions']['Description']['Text'],
				"checkin_times" 		=> jr_gettext('_JOMRES_ACTION_CHECKIN','_JOMRES_ACTION_CHECKIN',false,false)." ".
					$remote_property['Property']['CheckInOut']['CheckInFrom']." - ". $remote_property['Property']['CheckInOut']['CheckInTo']." ".
					jr_gettext('_JOMRES_ACTION_CHECKOUT','_JOMRES_ACTION_CHECKOUT',false,false)." ".
					$remote_property['Property']['CheckInOut']['CheckOutUntil'],
				"area_activities" 		=> '',
				"driving_directions"	=> '',
				"airports" 				=> '',
				"othertransport" 		=> '',
				"terms" 				=> '',
				"fax" 					=> '',
				"permit_number" 		=> $remote_property['Property']['LicenseNumber']
			);

			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/text/' , $data_array );

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