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


	function __construct()
	{
		$this->url = 'http://rm.rentalsunited.com/api/Handler.ashx';
	}

	/*
	
	
	*/
	
	public function communicate( $method = '' , $xml_str = '' , $clear_cache = false )
	{
		// Webhook events will use this method, but we don't (?) want to cache the messages so we'll not cache them
		$method_can_be_cached = true;

        jr_import('channelmanagement_rentalsunited_push_event_trigger_crossref');
        $event_trigger_crossref = new channelmanagement_rentalsunited_push_event_trigger_crossref();
		foreach ( $event_trigger_crossref->events as $event_type ) {
            if ( in_array( $method , $event_type )) {
                $method_can_be_cached = false;
            }
        }

		if ($method == 'Pull_ListPropertiesChangeLog_RQ' ) {
			$method_can_be_cached = false;
		}

		if ($clear_cache == true ) {
			$method_can_be_cached = false;
		}

		if ( $method_can_be_cached ) {
            $data_hash = md5(serialize($xml_str));
            $filename = $method."_".$data_hash.".php";

            if (!is_dir(JOMRES_TEMP_ABSPATH."cm_ru_data_cache")) {
                mkdir(JOMRES_TEMP_ABSPATH."cm_ru_data_cache");
            }

            if (file_exists( JOMRES_TEMP_ABSPATH."cm_ru_data_cache".JRDS.$filename )) {
                require_once(JOMRES_TEMP_ABSPATH."cm_ru_data_cache".JRDS.$filename);
                $class_name = $method."_".$data_hash;
                $ru_data_cache = new $class_name();
                return unserialize($ru_data_cache->data);
            }
        }

 		try {
			$uri = $this->url;

			$client = new GuzzleHttp\Client(['timeout' => 6, 'connect_timeout' => 6]);

			logging::log_message('Starting guzzle call to '.$uri, 'Guzzle', 'DEBUG');
			//var_dump($xml_str);exit;
			$options = [
				'headers' => [
					'Content-Type' => 'text/xml; charset=UTF8',
				],
				'body' => str_replace(array('.', ' ', "\n", "\t", "\r"), '',  $xml_str ),
			];

			$response = $client->request('POST', $uri, $options);

		}
		catch (Exception $e) {
			echo "Failed to get response from channel
			";

            var_dump($e->getMessage());exit;
			logging::log_message("Failed to get response from channel manager. Message ".$e->getMessage(), 'CHANNEL_MANAGEMENT_FRAMEWORK', 'ERROR' , "rentalsunited" );
			return false;
		}

		if (!isset($response)) {
			return false;
		}
		
		$raw_response = (string)$response->getBody();
var_dump($raw_response);exit;
		$response_body = new SimpleXMLElement($raw_response);

		$contents = $this->xmlToArray($response_body );

		// Check to see if the response contains _RS, which means that it's a response to a push method. If so we will process it differently
		$response_method = str_replace( "_RQ" , "_RS" , $method);

        // There's a typo in RU's responses, Pull_GetLocatinByName_RS is returned when it should say Pull_GetLocationByName_RS, so we'll make a special exception and hard-code a fix here
        if ($method == "Pull_GetLocationByName_RQ") {
            if (!isset($contents[$response_method])) { // In case they ever fix the typo
                $response_method = "Pull_GetLocatinByName_RS";
            }

        }

        if ( isset($contents[$response_method])) {
            if ( $method_can_be_cached ) {
                $sanitised_apostrophes = str_replace("'", "&#39;", $contents[$response_method]);
                $cache_data = "<?php
                    defined(\"_JOMRES_INITCHECK\" ) or die( \"\" );
                    class " . $method . "_" . $data_hash . " 
                    {
                        public function __construct()
                        {
                            \$this->method =  '" . $method . "';
                            \$this->data = '" . serialize($sanitised_apostrophes) . "';
                        }
                    }
                ";

                file_put_contents(JOMRES_TEMP_ABSPATH . "cm_ru_data_cache" . JRDS . $filename, $cache_data);
                return  $sanitised_apostrophes;
            } else {
                 return $contents[$response_method];
            }

        } else {

        }

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
                    set_time_limit(0);
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
