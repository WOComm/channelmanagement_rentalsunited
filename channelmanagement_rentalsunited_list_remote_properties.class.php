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


class channelmanagement_rentalsunited_list_remote_properties
{
    function __construct()
	{

	}
    
	function get_remote_properties()
	{
        jr_import('channelmanagement_rentalsunited_communication');
        $channelmanagement_rentalsunited_communication = new channelmanagement_rentalsunited_communication();

		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );

        set_showtime("property_managers_id" ,  $JRUser->id );
        $auth = get_auth();

        $output = array(
            "AUTHENTICATION" => $auth
        );


        $tmpl = new patTemplate();
        $tmpl->addRows('pageoutput', array($output));
        $tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
        $tmpl->readTemplatesFromInput('Pull_ListProp_RQ.xml');
        $xml_str = $tmpl->getParsedTemplate();
		
		$property_data = $channelmanagement_rentalsunited_communication->communicate(  'Pull_ListProp_RQ' , $xml_str );

		$remote_property_ids = array();
		if ($property_data['Status']["value"] == "Success" ) {
			foreach ($property_data["Properties"]["Property"] as $property) {
				$remote_property_ids[] = array ( "remote_property_id" => $property['ID']["value"] , "remote_property_name" => $property['Name'] );
				}
			}

		return $remote_property_ids;
	}


}
