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
	 * When a webhook is passed to the channel management framework the cmf webhook authmethod then hands the webhook to this script to allow this script to perform any actions required
	 * 
	 */

class j27330channelmanagement_rentalsunited_handle_webhook
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
		
		$this_channel = 'rentalsunited';

        logging::log_message("Starting RU 27330 webhook handling" , 'CHANNEL_MANAGEMENT_FRAMEWORK', 'DEBUG' , '' );

		// This script will collate and send information to the remote site using the authentication information provided in the componentArgs variable.
		$ePointFilepath=get_showtime('ePointFilepath');

		jr_import('channelmanagement_rentalsunited_push_event_trigger_crossref');
		$event_trigger_crossref = new channelmanagement_rentalsunited_push_event_trigger_crossref();

		$push_events = $event_trigger_crossref->events;
		$webhook_event = $componentArgs['webhook_notification']->webhook_event;
		$channel_data = $componentArgs['channel_data'];
		$managers = $componentArgs['managers'];

		if ( isset($push_events[$webhook_event]) ) {
			$push_tasks = $push_events[$webhook_event];
			if (!empty($push_tasks) ) {
				foreach ( $push_tasks as $task ) {
					$file_name = $ePointFilepath.'xml_'.$task.'.php';

                    try {
                        if (file_exists($file_name)) {
                            $class_name = $task;
                            logging::log_message("About to run class : " .$class_name, 'CHANNEL_MANAGEMENT_FRAMEWORK', 'DEBUG' , '' );
                            require_once($file_name);
                            $new_class = new $class_name();
                            $new_class->trigger_event($webhook_event , $componentArgs['webhook_notification']->data , $channel_data , $managers , $this_channel );
                            unset($new_class);
                        }
                    } catch (Exception $e) {
                        logging::log_message("Failed to send notification to remote channel, failed with message ".$e->getMessage() , 'CHANNEL_MANAGEMENT_FRAMEWORK', 'ERROR' , '' );
                    }
				}
			}
		}
		logging::log_message("Completed RU 27330 webhook handling" , 'CHANNEL_MANAGEMENT_FRAMEWORK', 'DEBUG' , '' );
	}

	public function getRetVals()
	{
		return null;
	}
}
	