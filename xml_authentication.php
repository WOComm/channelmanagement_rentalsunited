<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2020 Woollyinwales IT
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( 'Direct Access to this file is not allowed.' );
// ################################################################


function get_auth() {
	
	$property_managers_id = get_showtime("property_managers_id");
	
	if ( (int)$property_managers_id == 0  ) { // GTFO
		die();
	}
	
	jr_import('channelmanagement_framework_user_accounts');
	if (!class_exists('channelmanagement_framework_user_accounts')) {
		throw new Exception('Error: Channel management framework plugin not installed');
	}

	$channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();
	$user_accounts = $channelmanagement_framework_user_accounts->get_accounts_for_user($property_managers_id);

	$output = array();
	$pageoutput = array();
	
	$output['USERNAME'] = $user_accounts['rentalsunited']['channel_management_rentals_united_username'];
	$output['PASSWORD'] = $user_accounts['rentalsunited']['channel_management_rentals_united_password'];
	
	$pageoutput[] = $output;
	$tmpl = new patTemplate();
	$tmpl->addRows( 'pageoutput', $pageoutput );
	$tmpl->setRoot( $ePointFilepath.'templates'.JRDS."xml" );
	$tmpl->readTemplatesFromInput( 'authentication.xml' );
	return $tmpl->getParsedTemplate();
}

