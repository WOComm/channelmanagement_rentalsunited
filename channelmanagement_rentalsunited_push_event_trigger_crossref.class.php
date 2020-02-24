<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( 'Direct Access to this file is not allowed.' );
// ################################################################

/**
*
* @package Jomres\CMF
*
* Returns an array of Jomres webhooks and the RU scripts that will be called when those webhooks have been triggered
* For completion I'll record all webhook events as empty RU scripts, then this file can be used as a template for other thin plugins
*
*/

class channelmanagement_rentalsunited_push_event_trigger_crossref
{
    function __construct()
	{
		$this->events = array (
			'blackbooking_added'		=> [ 'Push_PutAvb_RQ' , 'Push_PutPropertyOnHold_RQ'  , 'Push_PutPropertiesOnHold_RQ'  ],        
			'blackbooking_deleted'		=> [  ],
			'booking_added'				=> [ 'Push_PutConfirmedReservationMulti_RQ' ],
			'booking_cancelled'			=> [ 'Push_CancelReservation_RQ'  , 'Push_ArchiveReservation_RQ' ], 
			'booking_marked_noshow'		=> [  ],
			'booking_modified'			=> [ 'Push_ModifyStay_RQ' ],
			'booking_note_deleted'		=> [  ],
			'booking_note_saved'		=> [  ],
			'changeover_day_enabled'	=> [ 'Push_PutChangeoverDays_RQ' ], 
			'changeover_day_disabled'	=> [ 'Push_PutChangeoverDays_RQ' ],
			'coupon_saved'				=> [  ],
			'coupon_updated'			=> [  ],
			'coupon_deleted'			=> [  ],
			'deposit_saved'				=> [  ],
			'extra_deleted'				=> [  ],
			'extra_saved'				=> [  ],
			'guest_checkedin'			=> [  ],
			'guest_checkedin_undone'	=> [  ],
			'guest_checkedout'			=> [  ],
			'guest_checkedout_undone'	=> [  ],
			'guest_deleted'				=> [  ],
			'guest_saved'				=> [  ],
			'guest_type_deleted'		=> [  ],
			'guest_type_saved'			=> [  ],
			'image_added'				=> [  ],
			'image_deleted'				=> [  ],
			'invoice_created'			=> [  ],
			'invoice_cancelled'			=> [  ],
			'invoice_saved'				=> [  ],
			'property_added'			=> [  ],   
			'property_deleted'			=> [ 'Push_SetPropertiesStatus_RQ' ],
			'property_published'		=> [ 'Push_SetPropertiesStatus_RQ' ],
			'property_settings_updated'	=> [  ],
			'property_unpublished'		=> [ 'Push_SetPropertiesStatus_RQ' ],
			'property_approved'			=> [  ],
			'property_unapproved'		=> [  ],
			'property_updated'			=> [  ],
			'property_completed'		=> [ 'Push_PutProperty_RQ' , 'Push_PutPropertyExternalListing_RQ' ],
			'property_incompleted'		=> [  ],
			'review_deleted'			=> [ 'Push_PutPropertyReviews_RQ'  ], 
			'review_published'			=> [ 'Push_PutPropertyReviews_RQ' , 'Push_PutPropertyReviews_RQ' ], 
			'review_saved'				=> [  ], 
			'review_unpublished'		=> [ 'Push_PutPropertyReviews_RQ' , 'Push_PutPropertyReviews_RQ' ], 
			'room_added'				=> [  ],
			'room_deleted'				=> [  ],
			'room_updated'				=> [  ],
			'rooms_multiple_added'		=> [  ],
			'tariff_cloned'				=> [  ],
			'tariffs_updated'			=> [ 'Push_PutMinstay_RQ' , 'Push_PutPropertyBasePrice_RQ' , 'Push_PutPrices_RQ' , 'Push_PutLongStayDiscounts_RQ' ]       
			
			// Not sure if we have webhooks for these yet
			// Push_StandardNumberOfGuests_RQ
			// Push_PutLastMinuteDiscounts_RQ
			
			
		);
	}

}




