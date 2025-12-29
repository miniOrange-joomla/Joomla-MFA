<?php
/**
 * @package     Joomla.User	
 * @subpackage  plg_user_miniorangetfa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

defined('_JEXEC') or die('Restricted access');
jimport('miniorangetfa.utility.commonUtilitiesTfa');
use Joomla\CMS\Log\Log;

Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);
class MocURLOTP
{
    public static function mo_send_otp_token($auth_type, $email = '', $phone = '')
    {
        Log::add('Initiating OTP token request. Auth Type: ' . $auth_type . ', Email: ' . $email . ', Phone: ' . $phone, Log::INFO, 'otp_request');
    
        require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR .
            'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_otp_utility.php';
    
        $url = MoOTPConstants::HOSTNAME . '/moas/api/auth/challenge';
    
        Log::add('Fetching customer details from database.', Log::INFO, 'otp_request');
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = isset($customerKeys['customer_key']) ? $customerKeys['customer_key'] : "";
        $apiKey      = isset($customerKeys['apiKey']) ? $customerKeys['apiKey'] : "";
        Log::add('Customer Key: ' . $customerKey, Log::INFO, 'otp_auth');
        Log::add('API Key: ' . $apiKey, Log::INFO, 'otp_auth');
        
        if ($customerKey == null || $apiKey == null) {
            Log::add('Customer credentials not found. OTP request failed.', Log::ERROR, 'otp_request');
            return json_encode(array('status' => 'FAILED'));
        }
    
        Log::add('Customer credentials retrieved successfully.', Log::INFO, 'otp_request');
        $fields = array(
            'customerKey' => $customerKey,
            'email' => $email,
            'phone' => $phone,
            'authType' => $auth_type,
            'transactionName' => MoOTPConstants::AREA_OF_INTEREST
        );
        
        $json = json_encode($fields);
        Log::add('Sending OTP request to API. Payload: ' . $json, Log::INFO, 'otp_request');
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $json, $authHeader);
        Log::add('OTP API Response: ' . $response, Log::INFO, 'otp_request');
    
        return $response;
    }
    
  

    public static function validatee_otp_token($transactionId, $otpToken)
    {
        require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR .
            'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_otp_utility.php';
            Log::add('check customer in',Log::INFO, 'otp_request');

        $url = MoOTPConstants::HOSTNAME . '/moas/api/auth/validate';
        $customerKeys = commonUtilitiesTfa::getCustomerKeys();

        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        Log::add('Customer Key: ' . $customerKey . ', API Key: ' . $apiKey, 'tfa');

        $fields = array(
            'txId' => $transactionId,
            'token' => $otpToken,
        );
        $json = json_encode($fields);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $json, $authHeader);
        return $response;
    }


    public static function check_customer_ln($customerKey, $apiKey)
    {

        $url = MoOTPConstants::HOSTNAME . '/moas/rest/customer/license';
        $fields = array(
            'customerId' => $customerKey,
            'applicationName' => MoOTPConstants::APPLICATION_NAME,
            'licenseType' => !MoUtility::micr() ? 'DEMO' : 'PREMIUM',
        );

        $json = json_encode($fields);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $json, $authHeader);
        return $response;
    }

    private static function createAuthHeader($customerKey, $apiKey)
    {
        Log::add('Generating authentication header.', Log::INFO, 'otp_auth');
    
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');
    
        Log::add('Current timestamp: ' . $currentTimestampInMillis, Log::INFO, 'otp_auth');
    
        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash("sha512", $stringToHash);
    
        Log::add('Authentication hash generated successfully.', Log::INFO, 'otp_auth');
    
        $header = array(
            "Content-Type: application/json",
            "Customer-Key: $customerKey",
            "Timestamp: $currentTimestampInMillis",
            "Authorization: $authHeader"
        );
    
        Log::add('Auth header created.', Log::INFO, 'otp_auth');
    
        return $header;
    }
    

    private static function callAPI($url, $json_string, $headers = array("Content-Type: application/json"))
    {
        Log::add('Initiating API call to: ' . $url, Log::INFO, 'api_request');
    
        $ch = curl_init($url);
    
        if (defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT')
            && defined('WP_PROXY_USERNAME') && defined('WP_PROXY_PASSWORD')) {
            Log::add('Using proxy settings for API request.', Log::INFO, 'api_request');
            curl_setopt($ch, CURLOPT_PROXY, WP_PROXY_HOST);
            curl_setopt($ch, CURLOPT_PROXYPORT, WP_PROXY_PORT);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD);
        }
    
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        
        if (!is_null($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            Log::add('Request headers set: ' . json_encode($headers), Log::INFO, 'api_request');
        }
    
        curl_setopt($ch, CURLOPT_POST, true);
    
        if (!is_null($json_string)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
            Log::add('Request payload: ' . $json_string, Log::INFO, 'api_request');
        }
    
        $content = curl_exec($ch);
    
        if (curl_errno($ch)) {
            $error_msg = 'Request Error: ' . curl_error($ch);
            Log::add($error_msg, Log::ERROR, 'api_request');
            curl_close($ch);
            return json_encode(array('status' => 'FAILED', 'message' => $error_msg));
        }
    
        curl_close($ch);
        
        Log::add('API Response: ' . $content, Log::INFO, 'api_request');
    
        return $content;
    }

}