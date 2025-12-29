<?php
/**
 * @package     Joomla.Library	
 * @subpackage  lib_miniorangetfa
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die;
require_once('commonUtilitiesTfa.php');
jimport('miniorangetfa.utility.MoTfa_api');
class MoTfa_api 
{

    static function get_timestamp() {
      
        $currentTimeInMillis = round( microtime( true ) * 1000 );
        $currentTimeInMillis = number_format( $currentTimeInMillis, 0, '', '' );
      
        return  $currentTimeInMillis ;
    }
    function make_curl_call( $url, $fields, $http_header_array =array( 'Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Basic' ) ) {
        if(!commonUtilitiesTfa::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.'));
        }

        if ( gettype( $fields ) !== 'string' ) {
            $fields = json_encode( $fields );
        }

        $response = $this->mo_tfa_post_curl($url, $fields, $http_header_array);
        return $response;

    }
    
    function get_http_header_array($isMiniOrange=false) {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys($isMiniOrange);
        $customerKey = isset($customerKeys['customer_key']) ? $customerKeys['customer_key'] : "";
        $apiKey      = isset($customerKeys['apiKey']) ? $customerKeys['apiKey'] : "";

        /* Current time in milliseconds since midnight, January 1, 1970 UTC. */
        $currentTimeInMillis = MoTfa_api::get_timestamp();

        /* Creating the Hash using SHA-512 algorithm */
        $stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
        $hashValue = hash( "sha512", $stringToHash );
        
        $headers = array(
            "Content-Type: application/json",
            "Customer-Key: ".$customerKey,
            "Timestamp: ".$currentTimeInMillis,
            "Authorization: ".$hashValue
        );

        return $headers;
    }
    public function mo_tfa_post_curl($url,$fields,$http_header_array){
        
        $ch     = curl_init( $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING, "" ); 
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); 
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $http_header_array );
        curl_setopt( $ch, CURLOPT_POST, true);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields);
        $content = curl_exec( $ch );
        if( curl_errno( $ch ) ){
            echo 'Request Error:' . curl_error( $ch );
           exit();
        }
        curl_close( $ch );
        return $content;
    }
}
?>