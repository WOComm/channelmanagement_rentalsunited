<?php
/**
 * Core file.
 *
 * @author Vince Wooll <sales@jomres.net>
 *
 * @version Jomres 9.8.21
 *
 * @copyright	2005-2017 Vince Wooll
 * Jomres (tm) PHP, CSS & Javascript files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project, and use it accordingly
 **/

// ################################################################
defined('_JOMRES_INITCHECK') or die('');
// ################################################################

/**
 * @package Jomres\Core\Minicomponents
 *
 * This collects a list of property uids for this channel, and for each property it pulls the changelog items
 *
 */

class j27400channelmanagement_rentalsunited_get_changelog_items
{
    /**
     *
     * Constructor
     *
     * Main functionality of the Minicomponent
     *
     */

    public function __construct($componentArgs)
    {
        // Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
        $MiniComponents = jomres_singleton_abstract::getInstance('mcHandler');
        if ($MiniComponents->template_touch) {
            $this->template_touchable = false;
            return;
        }

        $channel_name = 'rentalsunited';

		jr_import('channelmanagement_rentalsunited_communication');

        // We need to get the changelog for each manager from RU so the first job is to get all manager IDs

		$managers = channelmanagement_framework_utilities::get_manager_ids_by_channel_name( $channel_name );

		if (empty($managers)) { // There aren't any managers with channels, nothing to do
			return;
		}

		jr_import('channelmanagement_framework_queue_handling');
		$channelmanagement_framework_queue_handling = new channelmanagement_framework_queue_handling();


		foreach ($managers as $manager) {

			$manager_id = $manager['cms_user_id'];
			$channel_id = $manager['channel_id'];

			set_showtime("property_managers_id" , $manager_id );
			$auth = get_auth();

			if (!is_null($auth)) { // RU might be installed but not yet configured, let's back out for now

				$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

				$rows = array();

				$local_properties = channelmanagement_framework_properties::get_local_property_ids_for_channel($channel_id);

				if (!empty($local_properties)) {
					foreach ($local_properties as $local_property) {
						$r = array();
						$r['PROPERTY_ID'] = $local_property["remote_property_uid"];
						$rows[] = $r;
					}

					$output = array(
						"AUTHENTICATION" => $auth
					);

					$tmpl = new patTemplate();
					$tmpl->addRows('pageoutput', array($output));
					$tmpl->addRows('rows', $rows);
					$tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
					$tmpl->readTemplatesFromInput('Pull_ListPropertiesChangeLog_RQ.xml');
					$xml_str = $tmpl->getParsedTemplate();

					$changelog_items = $channelmanagement_rentalsunited_communication->communicate( 'Pull_ListPropertiesChangeLog_RQ' , $xml_str );

					$atts = '@attributes';

					if ( isset ($changelog_items['ChangeLogs']['ChangeLog']) && !empty($changelog_items['ChangeLogs']['ChangeLog']) ) {

						if ( isset($changelog_items['ChangeLogs']["ChangeLog"][$atts]) ) { // RU returns an array of changelogs if we request changelogs for more than one property, but just a changelog if we request changes for just one property so here we will rejig the array to make it parsable by the same foreach loop
							$tmp_arr = array($changelog_items['ChangeLogs']["ChangeLog"]);
							$changelog_items['ChangeLogs']["ChangeLog"] = $tmp_arr;
						}

						foreach ($changelog_items['ChangeLogs']["ChangeLog"] as $property_changelog ) {

							if ($property_changelog[$atts]['IsActive'] == "true" ) {
								$remote_property_id = $property_changelog[$atts]['PropertyID'];

								// The queuing system allows for a very broad set of data to be stored for processing by this thin plugin's 27410 script, so you can store pretty much anything you want in it's "item" field. This allows individual channels a lot of freedom as to what they want to store for later processing. The data is serialized before storage so pass an array or object or whatever you need
								// Each thing to be stored should be saved as an individual "thing" to be checked/updated as only one thing will be processed at a time later on to ensure that each "thing" has time to be processed successfully
								// Completed flag is provided so that you can mark a job completed or not completed as required

								$local_property_id = 0;
								foreach ($local_properties as $local_property ) {
									if ($remote_property_id == $local_property['remote_property_uid'] ) {
										$local_property_id = $local_property['local_property_uid'];
									}
								}

								// We will not set the completed flag here, as any item added to the table for that property with an identical unique id is updated
								if ( $local_property_id > 0 ) {
									$items = array();

									$item = new stdClass();

									$item->remote_property_id	= $remote_property_id;
									$item->local_property_id	= $local_property_id;
									$item->thing				= 'StaticData';
									$item->last_updated			= $property_changelog['StaticData'];
									$item->manager_id			= $manager_id;

									$items[] = array(
										"channel_name"		=> $channel_name,
										"local_property_id"	=> $local_property_id,
										"unique_id"			=> $channel_name." StaticData ".$property_changelog['StaticData'],
										"item"				=> $item

									);

									$item = new stdClass();
									$item->remote_property_id	= $remote_property_id;
									$item->local_property_id	= $local_property_id;
									$item->thing				= 'Pricing';
									$item->last_updated			= $property_changelog['Pricing'];
									$item->manager_id			= $manager_id;

									$items[] = array(
										"channel_name"		=> $channel_name,
										"local_property_id"	=> $local_property_id,
										"unique_id"			=> $channel_name." Pricing ".$property_changelog['Pricing'],
										"item"				=> $item
									);

									$item = new stdClass();
									$item->remote_property_id	= $remote_property_id;
									$item->local_property_id	= $local_property_id;
									$item->thing				= 'Availability';
									$item->last_updated			= $property_changelog['Availability'];
									$item->manager_id			= $manager_id;

									$items[] = array(
										"channel_name"		=> $channel_name,
										"local_property_id"	=> $local_property_id,
										"unique_id"			=> $channel_name." Availability ".$property_changelog['Availability'],
										"item"				=> $item
									);

									$item = new stdClass();
									$item->remote_property_id	= $remote_property_id;
									$item->local_property_id	= $local_property_id;
									$item->thing				= 'Image';
									$item->last_updated			= $property_changelog['Image'];
									$item->manager_id			= $manager_id;

									$items[] = array(
										"channel_name"		=> $channel_name,
										"local_property_id"	=> $local_property_id,
										"unique_id"			=> $channel_name." Image ".$property_changelog['Image'],
										"item"				=> $item
									);

									$item = new stdClass();
									$item->remote_property_id	= $remote_property_id;
									$item->local_property_id	= $local_property_id;
									$item->thing				= 'Description';
									$item->last_updated			= $property_changelog['Description'];
									$item->manager_id			= $manager_id;

									$items[] = array(
										"channel_name"		=> $channel_name,
										"local_property_id"	=> $local_property_id,
										"unique_id"			=> $channel_name." Description ".$property_changelog['Description'],
										"item"				=> $item
									);

									foreach ($items as $item) {
										try {
											$id = $channelmanagement_framework_queue_handling->store_queue_item($item);
										} catch (Exception $e) {
											logging::log_message("Failed to get store queue item for channel " . $channel_name . ". Message " . $e->getMessage(), 'RENTALS_UNITED', 'ERROR', serialize($item));
										}
									}
								}
							}
						}
					}
				}
			}
		}
    }

    public function getRetVals()
    {
        return null;
    }
}
