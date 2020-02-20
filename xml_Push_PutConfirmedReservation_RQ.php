<?php
/**
 * Core file.
 *
 * @author Vince Wooll <sales@jomres.net>
 *
 * @version Jomres 9.8.21
 *
 * @copyright	2005-2017 Vince Wooll
 * 
 **/

// ################################################################
defined('_JOMRES_INITCHECK') or die('');
// ################################################################
	
	/**
	 * @package Jomres\Core\Minicomponents
	 *
	 * Processes the webhook, refactor's the data to send the information to the channel
	 * 
	 */

class Push_PutConfirmedReservation_RQ
{	
	public function __construct()
	{
		
	}
	
	public function trigger_event( $webhook_event , $data , $channel_data , $managers , $this_channel ) 
	{
		if ( isset($channel_data['channel_name']) && $channel_data['channel_name'] != '' ) {
			if ($this_channel == $channel_data['channel_name']) {
				echo "nope";exit;
				return;
			}
		}
		
		var_dump($data);exit;
		var_dump($channel_data);exit;
		var_dump($managers);exit;
		
	}
}
