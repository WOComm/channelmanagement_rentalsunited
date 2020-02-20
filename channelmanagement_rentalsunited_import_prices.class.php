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


class channelmanagement_rentalsunited_import_prices
{
	
	public static function import_prices( $channel , $remote_property_id = 0 , $property_uid = 0 , $sleeps = 0 , $room_type_id = 0  )
	{
		if ( (int)$remote_property_id == 0 ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYID_NOTSET','CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYID_NOTSET',false) );
		}
		
		if ( (int)$property_uid == 0 ) {
			throw new Exception( "Property uid is not set " );
		}
		
		if ( (int)$sleeps == 0 ) {
			throw new Exception( "Number of persons property sleeps is not set " );
		}
		
		if ( (int)$room_type_id == 0 ) {
			throw new Exception( "Room type id is not set " );
		}
		
		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );
		
		jr_import('channelmanagement_framework_user_accounts');
		$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
		$user_accounts = $channelmanagement_framework_user_accounts->get_accounts_for_user($JRUser->id);

		jr_import('channelmanagement_rentalsunited_communication');
		$channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();
		$channelmanagement_rentalsunited_communication->set_username($user_accounts['rentalsunited']['channel_management_rentals_united_username']);
		$channelmanagement_rentalsunited_communication->set_password($user_accounts['rentalsunited']['channel_management_rentals_united_username']);
		
		$DateFrom	=  date ( "Y-m-d" , strtotime("now") ); 
		$DateTo		= date ( "Y-m-d" , strtotime(" +1 year ") ) ;
		
		$remote_prices = $channelmanagement_rentalsunited_communication->communicate( array( "PropertyID" => $remote_property_id , "DateFrom" => $DateFrom , "DateTo"  => $DateTo ) , 'Pull_ListPropertyPrices_RQ' );
		
		$remote_availability = $channelmanagement_rentalsunited_communication->communicate( array( "PropertyID" => $remote_property_id , "DateFrom" => $DateFrom , "DateTo"  =>$DateTo ) , 'Pull_ListPropertyAvailabilityCalendar_RQ' );
		
		
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

				// Dates should be in Y/m/d to find the date range so we need to str_replace "-" with "/"
				if ( !isset($season[$atts]['DateFrom']) ) {
					if (isset($season['DateFrom'])){
						$season[$atts]['DateFrom'] = $season['DateFrom'];
						$season['Price'] = $remote_prices['Prices']['Season']['Price'];
						$season['Extra'] = $remote_prices['Prices']['Season']['Extra'];
					} else {
						throw new Exception( "DateFrom in season not set" );
					}
				}
				if ( !isset($season[$atts]['DateTo']) ) {
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
							$extra_price_set[$stt_date] = array ( "price" => $price_per_person_extra , "mindays" =>$minDays , "minpeople" => 3 , "maxpeople" => $sleeps  ) ;
						}
					}
				}

			}
			
			
			$basic_post_data = array (
				"property_uid"					=> $property_uid ,
				"tarifftypeid"					=> 0 , // Create a new micromanage tariff
				"rate_title"					=> "Tariff" , 
				"rate_description"				=> "Tariff description" , 
				"maxdays"						=> 364 , 
				"roomclass_uid"					=> $room_type_id , 
				"dayofweek"						=> 7 , // Every day
				"ignore_pppn"					=> 0 , // Ignore per person per night flag in property config set to No. 
				"allow_we"						=> 1 , // Allow bookings to span weekends
				"weekendonly"					=> 0 , // Bookings for this tariff only allowed if all days in the booking are on the weekend = No
				"minrooms_alreadyselected"		=> 0 , // Specialised setting, do not change unless you understand the consequences
				"maxrooms_alreadyselected"		=> 1000 , // Specialised setting, do not change unless you understand the consequences 
				
				
			);
			
			$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton'); 

			if (!empty($primary_price_set)) {
				$post_data =$basic_post_data;
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

			}
			
			if (!empty($extra_price_set)) {
				$post_data =$basic_post_data;
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
			}
			
		}
	}
	
	

}

