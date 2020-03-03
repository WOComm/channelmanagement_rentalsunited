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

class j27400channelmanagement_rentalsunited_process_changelog_queue_item
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

   /*     $auth = get_auth();

        $output = array(
            "AUTHENTICATION" => $auth,
            "LOCATION" => $booking_data_response->data->response[0]->guest_data->country
        );


        $tmpl = new patTemplate();
        $tmpl->addRows('pageoutput', array($output));
        $tmpl->setRoot(RENTALS_UNITED_PLUGIN_ROOT . 'templates' . JRDS . "xml");
        $tmpl->readTemplatesFromInput('Pull_GetLocationByName_RQ.xml');
        $xml_str = $tmpl->getParsedTemplate();

        $location_data = $this->channelmanagement_rentalsunited_communication->communicate( 'Pull_GetLocationByName_RQ' , $xml_str );*/
    }

    public function getRetVals()
    {
        return null;
    }
}
