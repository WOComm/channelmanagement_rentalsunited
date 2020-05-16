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

    if ( $property_managers_id === 0 ) { // GTFO
        throw new Exception('Error: Manager id not set');
    }

    jr_import('channelmanagement_framework_user_accounts');
    if (!class_exists('channelmanagement_framework_user_accounts')) {
        throw new Exception('Error: Channel management framework plugin not installed');
    }

    $channelmanagement_framework_user_accounts = new channelmanagement_framework_user_accounts();



    if ( $property_managers_id != "system" ) {
        $user_accounts = $channelmanagement_framework_user_accounts->get_accounts_for_user($property_managers_id);

		if ( !isset( $user_accounts['rentalsunited']['channel_management_rentals_united_username']) || !isset( $user_accounts['rentalsunited']['channel_management_rentals_united_password']) ) {
			return ;
		}

        $username = $user_accounts['rentalsunited']['channel_management_rentals_united_username'];
        $password = $user_accounts['rentalsunited']['channel_management_rentals_united_password'];
    } else {
		$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
		$jrConfig = $siteConfig->get();

        $username = $jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_username"];
        $password = $jrConfig['channel_manager_framework_user_accounts']['rentalsunited']["channel_management_rentals_united_password"];
    }

    $output = array();
    $pageoutput = array();

    $output['USERNAME'] = $username;
    $output['PASSWORD'] = $password;

    $pageoutput[] = $output;
    $tmpl = new patTemplate();
    $tmpl->addRows( 'pageoutput', $pageoutput );
    $tmpl->setRoot( RENTALS_UNITED_PLUGIN_ROOT.'templates'.JRDS."xml" );
    $tmpl->readTemplatesFromInput( 'authentication.xml' );
    return $tmpl->getParsedTemplate();
}