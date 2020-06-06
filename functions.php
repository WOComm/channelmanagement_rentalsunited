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

function get_remote_admin_uri_rentalsunited( $remote_uid = 0 ) 
{
	return "https://new.rentalsunited.com/MyProperties/Edit/".$remote_uid."#step-1";
}

function get_property_room_types_rentalsunited( $mapped_dictionary_items, $CompositionRoomAmenities , $StandardGuests )
{
	$atts = '@attributes';
	$property_room_types = array();
	if (!empty($CompositionRoomAmenities)){
		foreach ($CompositionRoomAmenities as $amenity) {
			$amenity_id = $amenity[$atts]['CompositionRoomID'];
			if ($amenity_id == 257 ) {  // Will this change?
				if ( isset($mapped_dictionary_items['Pull_ListCompositionRooms_RQ']) && array_key_exists ( $amenity_id , $mapped_dictionary_items['Pull_ListCompositionRooms_RQ'] ) ) {
					$arr = $mapped_dictionary_items['Pull_ListCompositionRooms_RQ'][$amenity_id];
					unset($arr->item);

					$count = 0;
					foreach ($CompositionRoomAmenities as $a) {
						$a_id = $a[$atts]['CompositionRoomID'];
						if ( $a_id == $amenity_id ) {
							$count++;
						}
					}
					$property_room_types[] = array ( "amenity" => $arr , "count" => $count , "max_guests" => $StandardGuests ) ;
				}
			}
		}
		$property_room_types = array_unique($property_room_types, SORT_REGULAR);
	}
	return $property_room_types;
}

function get_property_type_rentalsunited( $mapped_dictionary_items , $ObjectTypeID )
{
	if (isset($ObjectTypeID)){
		foreach ($mapped_dictionary_items['Pull_ListOTAPropTypes_RQ'] as $mapped_property_type) {
			if ($ObjectTypeID == $mapped_property_type->remote_item_id) {
				$local_property_type = $mapped_property_type->jomres_id;
				$mrp_srp_flag = channelmanagement_rentalsunited_import_property::get_property_type_booking_model( $local_property_type ); // Is this an MRP or SRP?
			}
		}
	}
	return array( "local_property_type" => $local_property_type , "mrp_srp_flag" => $mrp_srp_flag );
}