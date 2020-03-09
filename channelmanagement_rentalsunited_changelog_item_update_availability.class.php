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
		if (is_null($item)) {
			throw new Exception('Item object is empty');
		}

		/* Last modification of the property's data (living space, address, coordinates, amenities, composition, etc.) */
		jr_import('channelmanagement_rentalsunited_communication');
		$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

		$changelog_item = unserialize($item->item);

		$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
		$manager_accounts = $channelmanagement_framework_user_accounts->find_channel_owners_for_property($item->property_uid);
		$first_manager_id = (int)array_key_first ($manager_accounts);
		if (!isset($first_manager_id) ||  $first_manager_id == 0 ) {
			return;
		}

		set_showtime("property_managers_id" , $first_manager_id );
		$auth = get_auth();


		// We need to find the "since" date, so we'll go through the previous queue items, find any that refer to availability then find the last two items. The very last should be this item, the one before that will be the last time availability was checked. If that doesn't exist then we'll use "this" item's last updated date



		$all_queue_items = channelmanagement_framework_utilities:: get_all_queue_items_for_property($item->property_uid);

		ksort ( $all_queue_items , SORT_REGULAR  );
		// Now we will get all of the items that refer to availability

		$completed_items = array();
		$uncompleted_items = array();  // In case there are no completed items yet
		if (!empty($all_queue_items)) {
			foreach ( $all_queue_items as $queue_item) {
				$queue_thing = unserialize($queue_item->item);
				if ($queue_thing->thing == 'Availability') {
					if ($queue_item->completed == 0 ) {
						$uncompleted_items[$queue_item->id ] = $queue_item;
					} else {
						$completed_items[$queue_item->id ] = $queue_item;
					}
				}
			}
		}


		if (empty($completed_items)) { // None of the queue items have been completed, we'll find the earliest date
			$since_item = reset($uncompleted_items);
		} else {
			$since_item = reset($completed_items);
		}

		$thing = unserialize($since_item->item);
		$since_date = $thing->last_updated;

		$output = array(
			"AUTHENTICATION" => $auth,
			"PROPERTY_ID" => $changelog_item->remote_property_id,
			"SINCE_DATE" => $since_date
		);

		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListPropertyAvbChanges_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		$remote_property = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListPropertyAvbChanges_RQ' , $xml_str , $clear_cache = true );

		var_dump($remote_property);exit;



	}


}