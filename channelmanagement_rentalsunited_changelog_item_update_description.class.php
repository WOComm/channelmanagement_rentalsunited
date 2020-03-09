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
		if (is_null($item)) {
			throw new Exception('Item object is empty');
		}

		/* Last modification of the property's data (living space, address, coordinates, amenities, composition, etc.) */
		jr_import('channelmanagement_rentalsunited_communication');
		$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

		$changelog_item = unserialize($item->item);
var_dump($changelog_item);exit;
		$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
		$manager_accounts = $channelmanagement_framework_user_accounts->find_channel_owners_for_property($item->property_uid);
		$first_manager_id = (int)array_key_first ($manager_accounts);
		if (!isset($first_manager_id) ||  $first_manager_id == 0 ) {
			return;
		}

		set_showtime("property_managers_id" , $first_manager_id );
		$auth = get_auth();

		$output = array(
			"AUTHENTICATION" => $auth,
			"PROPERTY_ID" => $changelog_item->remote_property_id
		);


		$tmpl = new patTemplate();
		$tmpl->addRows('pageoutput', array($output));
		$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
		$tmpl->readTemplatesFromInput('Pull_ListSpecProp_RQ.xml');
		$xml_str = $tmpl->getParsedTemplate();

		//$remote_property = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListSpecProp_RQ' , $xml_str , $clear_cache = true );

		//var_dump($remote_property);exit;


	}


}