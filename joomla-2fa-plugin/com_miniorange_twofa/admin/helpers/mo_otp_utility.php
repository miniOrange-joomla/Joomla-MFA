<?php
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
jimport('miniorangetfa.utility.commonUtilitiesTfa');

class MoOtpUtility
{
    public static function getCustomerKeys($isMiniorange=false){
        $keys=array();
        if($isMiniorange){
            $keys['customer_key']= "16555";
            $keys['apiKey']      = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
        }
        else{
            $customer_details = commonUtilitiesTfa::getCustomerDetails(); 
            $keys['customer_key'] = $customer_details['customer_key'] ?? null;
            $keys['apiKey']       = $customer_details['api_key'] ?? null;            
        }
        return $keys;
    }

    public static function fetchLicense() {
        if(!self::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.'));
        }
        $hostname = commonUtilitiesTfa::getHostname();
        $url = $hostname . '/moas/rest/customer/license';

        $customerKeys= self::getCustomerKeys(false);
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $fields = array (
            'customerId' => $customerKey,
            'applicationName' => 'otp_recharge_plan'
        );
        $api = new MoOtpUtility();
        return $api->make_curl_call($url,$fields,$api->get_http_header_array()) ;

    }

    function get_http_header_array($isMiniOrange=false) {
        $customerKeys= self::getCustomerKeys($isMiniOrange);
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];

        /* Current time in milliseconds since midnight, January 1, 1970 UTC. */
       $currentTimeInMillis = MoTfa_api::get_timestamp();

        /* Creating the Hash using SHA-512 algorithm */
        $stringToHash = $customerKey . $apiKey;;
        $hashValue = hash( "sha512", $stringToHash );

        return array(
            "Content-Type: application/json",
            "Customer-Key: ".$customerKey,
            "Timestamp: ".$currentTimeInMillis,
            "Authorization: ".$hashValue
        );
    }

    function make_curl_call( $url, $fields, $http_header_array =array( 'Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Basic' ) ) {

        if ( gettype( $fields ) !== 'string' ) {
            $fields = json_encode( $fields );
        }

        return ($this->mo_otp_curl($url, $fields,$http_header_array));
    }

    public function mo_otp_curl($url,$fields,$http_header_array){
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
        if (curl_errno($ch)) {
            $error_message = 'Request Error: ' . curl_error($ch);
            curl_close($ch);
            return $error_message;
        }
        curl_close( $ch );
        return $content;
    }

    public static function updateLicenseDetails($sample){
        if(!isset($sample->licensePlan))
            return;
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        // Fields to update.
        $fields = array(
            $db->quoteName('license_plan').' ='.$db->quote($sample->licensePlan),
            $db->quoteName('sms_count') . ' = '.$db->quote($sample->smsRemaining),
            $db->quoteName('email_count') . ' = '.$db->quote($sample->emailRemaining),
        );
        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = 1'
        );
        $query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    public static function is_curl_installed()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        } else
            return 0;
    }

    public static function exportData($tableNames)
    {
        $db = Factory::getDbo();
        $jsonData = [];

        if (empty($tableNames)) {
            $jsonData['error'] = 'No table names provided.';
        } else {
            foreach ($tableNames as $tableName) {
                $query = $db->getQuery(true);
                $query->select('*')
                      ->from($db->quoteName($tableName));

                $db->setQuery($query);
                try {
                    $data = $db->loadObjectList();
                    
                    if (empty($data)) {
                        $jsonData[$tableName] = ['message' => 'This table is empty.'];
                    } else {
                        $jsonData[$tableName] = $data;
                    }
                } catch (Exception $e) {
                    $jsonData[$tableName] = ['error' => $e->getMessage()];
                }
            }
        }

        header('Content-disposition: attachment; filename=exported_data.json');
        header('Content-type: application/json');
        echo json_encode($jsonData, JSON_PRETTY_PRINT);

        Factory::getApplication()->close();
    }

    public static function extractEmailDomain($email) {
        if (!empty($email) && strpos($email, '@') !== false) {
            return explode('@', $email)[1];
        }
        return '';
    }

}