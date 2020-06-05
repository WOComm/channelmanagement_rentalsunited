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

class Push_PutConfirmedReservationMulti_RQ
{	
	public function __construct()
	{
		
	}
	
	public function trigger_event( $webhook_event , $data , $channel_data , $managers , $this_channel ) 
	{
		if ( isset($channel_data['channel_name']) && $channel_data['channel_name'] != '' ) {
			if ($this_channel == $channel_data['channel_name']) {
                throw new Exception ( "Webhook triggered by this channel, will not process further");
			}
		}

        $ePointFilepath=get_showtime('ePointFilepath');

        $target_xml_file = 'Push_PutConfirmedReservationMulti_RQ.xml';

        //var_dump($data);exit;
		// var_dump($channel_data);exit;
		// var_dump($managers);exit;
        //var_dump($managers);exit;
		// We need the manager's id, if we can't find it we'll back out

		reset($managers);
		$first_key = key($managers);

        if ( !isset($managers[$first_key]['user_id']) ||  $managers[$first_key]['user_id'] == 0 ) {
            throw new Exception ( "Cannot identify property manager's id");
        } else {
            set_showtime("property_managers_id" , $managers[$first_key]['user_id'] );
        }

        if ( !isset($data->property_uid) || $data->property_uid == 0 ) {
            throw new Exception ( "Property uid not set");
        }

        if ( !isset($data->contract_uid) || $data->contract_uid == 0 ) {
            throw new Exception ( "Contract uid not set");
        }

        $channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');
        $response = $channelmanagement_framework_singleton->rest_api_communicate( $this_channel , 'GET' , 'cmf/properties/ids');

        if (!isset($response->data->response)) {
            throw new Exception ( "Channel not associated with any properties or api failed to connect");
        }

        $remote_property_uid = 0;
        foreach ($response->data->response as $property ) {
            if ($property->local_property_uid == $data->property_uid ) {
                $remote_property_uid = $property->remote_property_uid;
                break;
            }
        }

        if ($remote_property_uid == 0) {
            throw new Exception ( "Could not identify the remote property id");
        }

        // Was going to use this, but the get property booking cmf endpoint includes guest numbers so we'll use that instead of reinventing the wheel
        // $current_contract_details = jomres_singleton_abstract::getInstance('basic_contract_details');
        // $current_contract_details->gather_data($data->contract_uid, $data->property_uid);

        $endpoint = 'cmf/property/booking/'.$data->property_uid.'/'.$data->contract_uid;
        $booking_data_response = $channelmanagement_framework_singleton->rest_api_communicate( $this_channel , 'GET' ,  $endpoint);

        if (!isset($booking_data_response->data->response[0])) {
            throw new Exception ( "Could not get booking data");
        }

        jr_import('channelmanagement_rentalsunited_communication');
        $this->channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

         $auth = get_auth();

        $output = array(
            "AUTHENTICATION" => $auth,
            "LOCATION" => $booking_data_response->data->response[0]->guest_data->country
        );


        $tmpl = new patTemplate();
        $tmpl->addRows('pageoutput', array($output));
        $tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
        $tmpl->readTemplatesFromInput('Pull_GetLocationByName_RQ.xml');
        $xml_str = $tmpl->getParsedTemplate();

        $location_data = $this->channelmanagement_rentalsunited_communication->communicate( 'Pull_GetLocationByName_RQ' , $xml_str );

        if ( !isset($location_data["LocationID"]) || $location_data["LocationID"] == 0 ) {
            throw new Exception ( "Could not identify the property location");
        }



/*        $availability_request = array ( 'PropertyID' => $remote_property_uid, 'DateFrom' =>  $booking_data_response->data->response[0]->date_from, 'DateTo' =>  $booking_data_response->data->response[0]->date_to);

        $availability_request_response = $this->channelmanagement_rentalsunited_communication->communicate(  $availability_request , 'Pull_ListPropertyAvailabilityCalendar_RQ');

        var_dump( $availability_request_response);exit;*/

		$output = array();
		$pageoutput = array();

        $output['AUTHENTICATION'] = get_auth();

        $output['NAME'] = $booking_data_response->data->response[0]->guest_data->enc_firstname;
        $output['SURNAME'] = $booking_data_response->data->response[0]->guest_data->enc_surname;
        $output['EMAIL'] = $booking_data_response->data->response[0]->guest_data->enc_email;
        $output['PHONE'] = $booking_data_response->data->response[0]->guest_data->enc_tel_mobile;
        $output['ADDRESS'] =
            $booking_data_response->data->response[0]->guest_data->enc_house.', '.
            $booking_data_response->data->response[0]->guest_data->enc_street.', '.
            $booking_data_response->data->response[0]->guest_data->enc_city.', '.
            $booking_data_response->data->response[0]->guest_data->enc_region.', '.
            $booking_data_response->data->response[0]->guest_data->country;

        $output['POSTCODE'] = $booking_data_response->data->response[0]->guest_data->enc_postcode;
        $output['COUNTRY_ID'] = $location_data["LocationID"];
        $output['COMMENTS'] = ''; // Not supported yet

        // Not needed right now, but might need to make it so that this can work with multiple bookings in the future. The xml template will support it, but right now this code doesn't
        $r = array();
        $rows = array();

        $r['REMOTE_PROPERTY_UID'] = $remote_property_uid;
        $r['DATE_FROM'] = $booking_data_response->data->response[0]->date_from;
        $r['DATE_TO'] = date ("Y-m-d" ,strtotime( $booking_data_response->data->response[0]->date_to . " +1 day" ));
        $r['GUEST_NUMBER'] = $booking_data_response->data->response[0]->guest_numbers->number_of_guests;
        if (  $r['GUEST_NUMBER'] == 0 ) { // Xml will not be parsed properly if guest numbers aren't set so we'll default to 2 as a fallback
            $r['GUEST_NUMBER'] = 2;
        }
        if ( $booking_data_response->data->response[0]->deposit_paid ) {
            $r['ALREADYPAID'] = $booking_data_response->data->response[0]->deposit_amount;
        } ELSE {
            $r['ALREADYPAID'] = 0.00;
        }

        $r['RUPRICE'] = $booking_data_response->data->response[0]->booking_total;
        $r['CLIENTPRICE'] = $booking_data_response->data->response[0]->booking_total;

        $rows[] = $r;


        $pageoutput[] = $output;
        $tmpl = new patTemplate();
        $tmpl->addRows( 'pageoutput', $pageoutput );
        $tmpl->addRows( 'rows', $rows );
        $tmpl->setRoot( $ePointFilepath.'templates'.JRDS."xml" );
        $tmpl->readTemplatesFromInput( $target_xml_file );
        $xml = $tmpl->getParsedTemplate();
        $xml_str = trim(preg_replace('/^\h*\v+/m', '', $xml));

        $notification = $this->channelmanagement_rentalsunited_communication->communicate( 'Push_PutConfirmedReservationMulti_RQ' , $xml_str );

        if ( isset($notification['ReservationID']) && $notification['ReservationID'] > 0) {

			$data_array = array (
                "property_uid"			=> $data->property_uid,
                "remote_booking_id"		=> $notification['ReservationID'],
                "local_booking_id"		=> $data->contract_uid
            );

            $response = $channelmanagement_framework_singleton->rest_api_communicate( $this_channel , 'PUT' , 'cmf/property/booking/link' , $data_array );

            $message = "Forwarded booking to channel : ".serialize($notification);
            logging::log_message($message, 'RENTALS_UNITED', 'INFO');



        } else {
            $message = "Failed to forward booking to channel, response from channel : ".serialize($notification);
            logging::log_message($message, 'RENTALS_UNITED', 'ERROR');
        }

        return $xml;
	}
}

/*
{
    "data": {
        "response": [
            {
                "booking_id": "19",
                "booking_number": "82590841",
                "invoice_id": "5",
                "invoice_number": "1001",
                "status": {
                    "status_code": 3,
                    "status_text": "Approved "
                },
                "property_uid": "26",
                "booking_created": "2020-02-23 10:51:04",
                "last_modified": "2020-02-23 10:51:05",
                "date_from": "2020-02-24",
                "date_to": "2020-02-24",
                "booking_total": "144",
                "deposit_amount": "14.4",
                "deposit_paid": false,
                "currency_code": "EUR",
                "referrer": "Jomres",
                "mapping": [],
                "guest_numbers": {
                    "adults": 0,
                    "children": 0,
                    "number_of_guests": 0
                },
                "guest_data": {
                    "guest_id": "3",
                    "guest_system_id": "48",
                    "enc_firstname": "test",
                    "enc_surname": "test",
                    "enc_house": "test",
                    "enc_street": "test",
                    "enc_city": "test",
                    "enc_region": "Kyrenia",
                    "country": "Cyprus",
                    "enc_postcode": "test",
                    "enc_preferences": "",
                    "enc_tel_landline": "test",
                    "enc_tel_mobile": "test",
                    "enc_email": "test@test.com"
                }
            }
        ]
    },
    "meta": {
        "code": 200
    }
}
 *

 object(basic_contract_details)#589 (2) {
  ["contract"]=>
  array(1) {
    [18]=>
    array(5) {
      ["contractdeets"]=>
      array(43) {
        ["contract_uid"]=>
        string(2) "18"
        ["arrival"]=>
        string(10) "2020/02/23"
        ["departure"]=>
        string(10) "2020/02/24"
        ["guest_uid"]=>
        string(1) "2"
        ["rate_rules"]=>
        string(0) ""
        ["rooms_tariffs"]=>
        string(5) "57^39"
        ["deposit_paid"]=>
        string(1) "0"
        ["contract_total"]=>
        string(3) "144"
        ["deposit_ref"]=>
        NULL
        ["payment_ref"]=>
        NULL
        ["special_reqs"]=>
        string(0) ""
        ["deposit_required"]=>
        string(4) "28.8"
        ["date_range_string"]=>
        string(10) "2020/02/23"
        ["booked_in"]=>
        string(1) "0"
        ["true_arrival"]=>
        NULL
        ["single_person_suppliment"]=>
        string(1) "0"
        ["extras"]=>
        string(0) ""
        ["extrasquantities"]=>
        bool(false)
        ["extrasvalue"]=>
        string(1) "0"
        ["tax"]=>
        string(5) "24.00"
        ["tag"]=>
        string(8) "22028627"
        ["timestamp"]=>
        string(19) "2020-02-21 12:36:41"
        ["room_total"]=>
        string(3) "120"
        ["discount"]=>
        string(1) "0"
        ["currency_code"]=>
        string(3) "EUR"
        ["cancelled"]=>
        string(1) "0"
        ["cancelled_timestamp"]=>
        NULL
        ["cancelled_reason"]=>
        NULL
        ["discount_details"]=>
        string(0) ""
        ["username"]=>
        string(6) "jomres"
        ["coupon_id"]=>
        string(1) "0"
        ["bookedout"]=>
        string(1) "0"
        ["bookedout_timestamp"]=>
        NULL
        ["invoice_uid"]=>
        string(1) "4"
        ["channel_manager_booking"]=>
        string(1) "0"
        ["approved"]=>
        string(1) "1"
        ["secret_key"]=>
        string(50) "JdSBTOogWwSrcGbCmVuGqfctHCWgxHQQHZItCcPDuaDlOudhmF"
        ["booking_language"]=>
        string(5) "en-GB"
        ["last_changed"]=>
        string(19) "2020-02-21 12:36:41"
        ["noshow_flag"]=>
        string(1) "0"
        ["network_stats"]=>
        string(2) "[]"
        ["referrer"]=>
        string(6) "Jomres"
        ["booking_data_archive_id"]=>
        string(1) "4"
      }
      ["guestdeets"]=>
      array(17) {
        ["image"]=>
        string(33) "/jomres/assets/images/noimage.gif"
        ["firstname"]=>
        string(4) "test"
        ["surname"]=>
        string(4) "test"
        ["house"]=>
        string(4) "test"
        ["street"]=>
        string(4) "test"
        ["town"]=>
        string(4) "test"
        ["county"]=>
        string(7) "Kyrenia"
        ["country"]=>
        string(6) "Cyprus"
        ["country_code"]=>
        string(2) "CY"
        ["postcode"]=>
        string(4) "test"
        ["tel_landline"]=>
        string(4) "test"
        ["tel_mobile"]=>
        string(4) "test"
        ["email"]=>
        string(13) "test@test.com"
        ["discount"]=>
        string(1) "0"
        ["vat_number"]=>
        string(0) ""
        ["vat_number_validated"]=>
        string(1) "0"
        ["vat_number_validation_response"]=>
        NULL
      }
      ["roomdeets"]=>
      array(1) {
        [57]=>
        array(11) {
          ["room_uid"]=>
          string(2) "57"
          ["black_booking"]=>
          NULL
          ["reception_booking"]=>
          string(1) "1"
          ["internet_booking"]=>
          string(1) "0"
          ["room_classes_uid"]=>
          string(1) "5"
          ["room_name"]=>
          NULL
          ["room_number"]=>
          NULL
          ["room_floor"]=>
          NULL
          ["max_people"]=>
          string(2) "10"
          ["singleperson_suppliment"]=>
          string(1) "0"
          ["rate_title"]=>
          string(6) "Tariff"
        }
      }
      ["guesttype"]=>
      array(0) {
      }
      ["notedeets"]=>
      array(1) {
        [4]=>
        array(3) {
          ["id"]=>
          string(1) "4"
          ["timestamp"]=>
          string(19) "2020-02-21 12:36:41"
          ["note"]=>
          string(32) " Wiseprice discount not applied "
        }
      }
    }
  }
  ["jomres_encryption"]=>
  object(jomres_encryption)#595 (2) {
    ["encryption_key":"jomres_encryption":private]=>
    object(Defuse\Crypto\Key)#532 (1) {
      ["key_bytes":"Defuse\Crypto\Key":private]=>
      string(32) "�4lBWꚴ$�$�"���9�����&�}���"
    }
    ["key_location"]=>
    string(50) "/home/vince/html/jomres.development/public/jomres/"
  }
}

*/
