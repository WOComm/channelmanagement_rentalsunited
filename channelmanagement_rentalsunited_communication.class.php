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

require_once('XMLParser.php');
use XMLParser\XMLParser;

class channelmanagement_rentalsunited_communication
	{
	/*
	The username
	*/
	private $username = '';
	
	/*
	The password
	*/
	private $password = '';
	

	
	function __construct()
	{
		$this->url = 'http://rm.rentalsunited.com/api/Handler.ashx';
	}

		
	public function set_username($username = '')
	{
		if ($username == '' ){
			throw new Exception("username not sent to communication class");
		}
		$this->username = $username;
	}
	
	public function set_password($password = '')
	{
		if ($password == '' ){
			throw new Exception("password not sent to communication class");
		}
		$this->password = $password;
	}
	
	/*
	
	
	*/
	
	public function communicate($data_array = array() , $method = 'Pull_ListProp_RQ')
	{
		if ($this->username == '' ){
			throw new Exception("username not available to communication class");
		}
		
		if ($this->password == '' ){
			throw new Exception("password not available to communication class");
		} 
		
		$user_hash = md5($this->username);
		$data_hash = md5(serialize($data_array));
		$filename = $user_hash."_".$method."_".$data_hash.".php";
		
		if (!is_dir(JOMRES_TEMP_ABSPATH."cm_ru_data_cache")) {
			mkdir(JOMRES_TEMP_ABSPATH."cm_ru_data_cache");
		}
		
		if (file_exists( JOMRES_TEMP_ABSPATH."cm_ru_data_cache".JRDS.$filename )) {
			require_once(JOMRES_TEMP_ABSPATH."cm_ru_data_cache".JRDS.$filename);
			$class_name = $user_hash."_".$method."_".$data_hash;
			$ru_data_cache = new $class_name();
			return unserialize($ru_data_cache->data);
		}
		
		$authentication = array (
			"Authentication" => array (
				"UserName" => $this->username,
				"Password" => $this->password,
			)
		);
		
		$data = array_merge($authentication , $data_array);

		$xml = XMLParser::encode($data , $method );
		$body = $xml->asXML();
		$body = str_replace(array('.', ' ', "\n", "\t", "\r"), "" , $body );
		
		try {
			$uri = $this->url;

			$client = new GuzzleHttp\Client(['timeout' => 6, 'connect_timeout' => 6]);

			logging::log_message('Starting guzzle call to '.$uri, 'Guzzle', 'DEBUG');
			
			$options = [
				'headers' => [
					'Content-Type' => 'text/xml; charset=UTF8',
				],
				'body' => str_replace('<?xml version="1.0" encoding="UTF-8"?>' , '' , $xml->asXML()),
			];

			$response = $client->request('POST', $uri, $options);
		}
		catch (Exception $e) {
			logging::log_message("Failed to get response from channel manager. Message ".$e->getMessage(), 'channel_management', 'ERROR' , "rentalsunited" );
			return false;
		}
		
		if (!isset($response)) {
			return false;
		}
		
		$raw_response = (string)$response->getBody();
		$response_body = new SimpleXMLElement($raw_response);
		
		$contents = $this->xmlToArray($response_body );


		reset($contents);
		$first_key = key($contents);

		if (!isset($contents[$first_key]['Status']) || $contents[$first_key]['Status']['value'] != "Success") {
			return false;
		}
		
		$cache_data = "<?php
defined(\"_JOMRES_INITCHECK\" ) or die( \"\" );
class ".$user_hash."_".$method."_".$data_hash." 
{
	public function __construct()
	{
		\$this->username = '".$this->username."';
		\$this->method =  '".$method."';
		\$this->data = '".serialize($contents[$first_key])."';
	}
	
}
			";

		file_put_contents(JOMRES_TEMP_ABSPATH."cm_ru_data_cache".JRDS.$filename , $cache_data );
		
		
		return $contents[$first_key];
		}
		
		
	function xmlToArray(SimpleXMLElement $xml)
		{
			$parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
				$nodes = $xml->children();
				$attributes = $xml->attributes();

				if (0 !== count($attributes)) {
					foreach ($attributes as $attrName => $attrValue) {
						$collection['@attributes'][$attrName] = strval($attrValue);
					}
				}

				if (0 === $nodes->count()) {
					if($xml->attributes())
					{
						$collection['value'] = strval($xml);
					}
					else
					{
						$collection = strval($xml);
					}
					return $collection;
				}

				foreach ($nodes as $nodeName => $nodeValue) {
					if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
						$collection[$nodeName] = $parser($nodeValue);
						continue;
					}

					$collection[$nodeName][] = $parser($nodeValue);
				}

				return $collection;
			};

			return [
				$xml->getName() => $parser($xml)
			];
		}

	}
