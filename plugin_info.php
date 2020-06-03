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
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

class plugin_info_channelmanagement_rentalsunited
	{
	function __construct()
		{
		$this->data=array(
			"name"=>"channelmanagement_rentalsunited",
			"category"=>"Integration",
			"marketing"=>"Plugin that integrates with the Rentals United Channel Manager, imports properties, pricing and availability.",
			"version"=>(float)"0.2",
			"description"=> "Plugin that integrates with the Rentals United Channel Manager, imports properties, pricing and availability.",
			"lastupdate"=>"2019/06/02",
			"min_jomres_ver"=>"9.16.1",
			"manual_link"=>'',
			'change_log'=>' v0.2  Proxy header changed to use hyphens ',
			'highlight'=>'',
			'image'=>'',
			'demo_url'=>''
			);
		}
	}
