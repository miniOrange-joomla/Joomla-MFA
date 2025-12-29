<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
jimport('miniorangetfa.utility.MoTfa_api');
class Mo_tfa_Customer{

    function create_customer($email,$phone,$password){
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("statusCode"=>'ERROR','error'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR2")));
        }
        $hostname = Mo_tfa_utilities::getHostname();
        $url = $hostname.'/moas/rest/customer/add';
        $current_user =  Factory::getUser();

        $fields = array(
            'companyName' => $_SERVER['SERVER_NAME'],
            'areaOfInterest' => 'Joomla 2FA Plugin',
            'firstname' => $current_user->username,
            'email'    => $email,
            'phone'    => $phone,
            'password' =>$password
        );
        $api=new MoTfa_api();
        return $api->make_curl_call($url,$fields);
    }

    function getCustomerKeys($email,$password) {
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("apiKey"=>'CURL_ERROR','token'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }

        $hostname = Mo_tfa_utilities::getHostname();

        $url = $hostname. "/moas/rest/customer/key";

        $fields = array(
            'email' => $email,
            'password' => $password
        );
        $api = new MoTfa_api();
        return $api->make_curl_call($url,$fields);
    }

    public static function submit_feedback_form($email,$phone,$query)
    {
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("statusCode"=>'ERROR','error'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
        $url =  commonUtilitiesTfa::getApiUrls()['feedback'];
        
        $subject            = "MiniOrange Joomla Feedback for TFA Plugin";

        $query1 =" MiniOrange Joomla 2FA ";

        $fromEmail = $email;
        $moPluginVersion = commonUtilitiesTfa::GetPluginVersion();
        $jCmsVersion = commonUtilitiesTfa::getJoomlaCmsVersion();
        $phpVersion = phpversion();
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        $webServer = !empty($serverSoftware) ? trim(explode('/', $serverSoftware)[0]) : 'Unknown';
        $query2 = '[Plugin ' . $moPluginVersion . ' | PHP ' . $phpVersion .' | Joomla Version '. $jCmsVersion .' | Web Server '. $webServer .']';
        $content='<div >Hello, <br><br>Company :<a href="'.$_SERVER['SERVER_NAME'].'" target="_blank" >'.$_SERVER['SERVER_NAME'].'</a><br><br>Phone Number :'.$phone.'<br><br><b>Email :<a href="mailto:'.$fromEmail.'" target="_blank">'.$fromEmail.'</a></b><br><br><b>Plugin Deactivated: '.$query1.'</b><br><br><b>Reason: '.$query.'</b><br><br><b>System info: '.$query2.'</b></div>';
        $customerKeys= commonUtilitiesTfa::getCustomerKeys(true);
        $customerKey = $customerKeys['customer_key'];

        $fields = array(
            'customerKey'   => $customerKey,
            'sendEmail'     => true,
            'email'         => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => $fromEmail,
                'fromName'      => 'miniOrange',
                'toEmail'       => 'joomlasupport@xecurify.com',
                'toName'        => 'joomlasupport@xecurify.com',
                'subject'       => $subject,
                'content'       => $content
            ),
        );
        
        
        $api=new MoTfa_api();
        $header=$api->get_http_header_array(true);
        return $api->make_curl_call($url,$fields,$header);
    }

    function submit_contact_us( $q_email, $q_phone, $query ) {
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
        $hostname        = Mo_tfa_utilities::getHostname();
        $url             = $hostname . "/moas/rest/customer/contact-us";       
        $current_user    =  Factory::getUser();
        $phpVersion      = phpversion();
        $jVersion        = new Version;
        $jCmsVersion     = $jVersion->getShortVersion();
        $moPluginVersion = Mo_tfa_utilities::GetPluginVersion();
        $query           = '[Joomla '.$jCmsVersion. ' 2FA Plugin | '.$moPluginVersion.' | PHP '.$phpVersion.'] ' . $os_version.'] '. $query;
        
        $fields = array(
            'firstName'         => $current_user->username,
            'company'           => $_SERVER['SERVER_NAME'],
            'email'             => $q_email,
            'ccEmail'           => 'joomlasupport@xecurify.com',
            'phone'             => $q_phone,
            'query'             => $query,
            
        );
        $api=new MoTfa_api();
        return $api->make_curl_call($url,$fields);
    }

    function submit_trial_request($demo, $email,$mobile_number,$plan, $description){
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
        $hostname = Mo_tfa_utilities::getHostname();
          $url =  $hostname .'/moas/api/notify/send';
        $customerKeys= commonUtilitiesTfa::getCustomerKeys(false);
        $customerKey = $customerKeys['customer_key'];
        $subject = "MiniOrange Two Factor Authentication - Demo / Trial Request";
        $content='<div>Hello, 
                  <strong><br><br>Company :</strong><a href="'.$_SERVER['SERVER_NAME'].'" target="_blank" >'.$_SERVER['SERVER_NAME'].'</a>
                  <strong><br><br>Email :</strong><a href="mailto:'.$email.'" target="_blank">'.$email.'</a>
                  <strong><br><br>Demo or Trial :</strong>'.$demo.'
                  <strong><br><br>Plugin Info:</strong>[Joomla '.JVERSION.' 2FA Plugin | '.Mo_tfa_utilities::GetPluginVersion().' | PHP '.phpversion().']
                  <strong><br><br>Description:</strong> '.$description. '</div>';
        $fields = array(
            'customerKey'	=> $customerKey,
            'sendEmail' 	=> true,
            'email' 		=> array(
                'customerKey' 	=> $customerKey,
                'fromEmail' 	    => $email,
                'fromName' 		=> 'miniOrange',
                'toEmail' 		=> 'joomlasupport@xecurify.com',
                'toName' 		=> 'joomlasupport@xecurify.com',
                'subject' 		=> $subject,
                'content' 		=> $content
            ),
        );
        $api = new MoTfa_api();
        $http_header_array = $api->get_http_header_array();
        return $api->make_curl_call($url,$fields,$http_header_array) ;
    }

    function send_otp_token($auth_type,$email, $phone,$isMiniOrange=false){

        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }

        $hostname = Mo_tfa_utilities::getHostname();
        $url = $hostname . '/moas/api/auth/challenge';
        $customerKeys= commonUtilitiesTfa::getCustomerKeys($isMiniOrange);
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        if($auth_type=="EMAIL")
        {
            $fields = array(
                'customerKey' => $customerKey ,
                'email' => str_replace(" ","",$email),
                'authType' => $auth_type,
                'transactionName' => 'Joomla 2FA  Plugin'
            );
        }
        else{
            $fields = array(
                'customerKey' =>$customerKey,
                'phone' => str_replace(" ","",$phone),
                'authType' => $auth_type,
                'transactionName' => 'Joomla 2FA Plugin'
            );
        }
        
        $api=new MoTfa_api();
        $header=$api->get_http_header_array($isMiniOrange);
        return $api->make_curl_call($url,$fields,$header);
    }

    function validate_otp_token($transactionId,$otpToken,$isMiniOrange){
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
        $hostname = Mo_tfa_utilities::getHostname();
        $url = $hostname . '/moas/api/auth/validate';
       
        $customerKeys= commonUtilitiesTfa::getCustomerKeys($isMiniOrange);
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $fields = array(
            'txId' => $transactionId,
            'token' => str_replace(" ","",$otpToken),
        );
        $api    = new MoTfa_api();
        $header = $api->get_http_header_array($isMiniOrange);
        return $api->make_curl_call($url,$fields,$header);
    }

    function check_customer($email) {
        if(!Mo_tfa_utilities::is_curl_installed()) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
        $hostname = Mo_tfa_utilities::getHostname();
        $url = $hostname . "/moas/rest/customer/check-if-exists";
        
        $fields = array(
            'email'     => $email,
        );
        $api = new MoTfa_api();

       return $api->make_curl_call($url,$fields) ;
    }
    public function fetchLicense() {
        require_once JPATH_SITE . '/administrator/components/com_miniorange_twofa/helpers/Mo_tfa_utility.php';

        
        if(!in_array  ('curl', get_loaded_extensions())) {
            return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
        }
       
        $hostname = Mo_tfa_utilities::getHostname();
        $url = $hostname . '/moas/rest/customer/license';
       
        $customerKeys= commonUtilitiesTfa::getCustomerKeys(false);
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $fields = array (
            'customerId' => $customerKey,
            'applicationName' => 'joomla_2fa_premium_plan'
        );
        $api = new MoTfa_api();
        return $api->make_curl_call($url,$fields,$api->get_http_header_array());
        
  }
  
  function mo2f_google_auth_service( $email, $googleAuthenticatorType, $googleAuthenticatorName="") 
  {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();  
        $customerKey = $customerKeys['customer_key'];

        $fields = array(
            'customerKey' => $customerKey,
            'username'    => $email,
            'googleAuthenticatorName'   => $googleAuthenticatorName,
            'authenticatorName' => $googleAuthenticatorType,
        );
        $urls = commonUtilitiesTfa::getApiUrls();

        $url=$urls['googleAuthService'];
        $api= new MoTfa_api();
        $http_header_array = $api->get_http_header_array();
        return $api->make_curl_call( $url, $fields, $http_header_array );
   }

  function request_setup_call($email, $plan, $description,$callDate,$timeZone){
      if(!Mo_tfa_utilities::is_curl_installed()) {
          return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
      }

                $url =  'https://login.xecurify.com/moas/api/notify/send';
                $ch = curl_init($url);

                $customerKey = "16555";
                $apiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

                $currentTimeInMillis= round(microtime(true) * 1000);
                $stringToHash 		= $customerKey .  number_format($currentTimeInMillis, 0, '', '') . $apiKey;
                $hashValue 			= hash("sha512", $stringToHash);
                $customerKeyHeader 	= "Customer-Key: " . $customerKey;
                $timestampHeader 	= "Timestamp: " .  number_format($currentTimeInMillis, 0, '', '');
                $authorizationHeader= "Authorization: " . $hashValue;
                $fromEmail 			= $email;
                $currentUserEmail 	= Factory::getUser();
                $adminEmail         = $currentUserEmail->email;

                $subject = "MiniOrange Two Factor Authentication - Screen Share/Call Request";
                $mo_content='<div>Hello, 
                <strong><br><br>Company :</strong><a href="'.$_SERVER['SERVER_NAME'].'" target="_blank" >'.$_SERVER['SERVER_NAME'].'</a>
                <strong><br><br>Email :</strong><a href="mailto:'.$email.'" target="_blank">'.$email.'</a>
                <strong><br><br>Time Zone:</strong> '.$timeZone. '
                <strong><br><br>Date to set up call:</strong> ' .$callDate. '
                <strong><br><br>Issue :</strong> ' .$plan. '
                <strong><br><br>Description:</strong> '.$description. '</div>';

                $fields = array(
                    'customerKey'	=> $customerKey,
                    'sendEmail' 	=> true,
                    'email' 		=> array(
                        'customerKey' 	=> $customerKey,
                        'fromEmail' 	=> $fromEmail,                
                        'fromName' 		=> 'miniOrange',
                        'toEmail' 		=> 'joomlasupport@xecurify.com',
                        'toName' 		=> 'joomlasupport@xecurify.com',
                        'subject' 		=> $subject,
                        'content' 		=> $mo_content
                    ),
                );
                $field_string = json_encode($fields);


                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt( $ch, CURLOPT_ENCODING, "" );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

                curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader,
                    $timestampHeader, $authorizationHeader));
                curl_setopt( $ch, CURLOPT_POST, true);
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
                $content = curl_exec($ch);

                if(curl_errno($ch)){
                    return json_encode(array("status"=>'ERROR','statusMessage'=>curl_error($ch)));
                }
                curl_close($ch);

                return ($content);
    }

  function submit_request_quote($type_service, $email,$user_count,$number_otp, $select_country,$which_country=null,$query=null){
      if(!Mo_tfa_utilities::is_curl_installed()) {
          return json_encode(array("status"=>'CURL_ERROR','statusMessage'=>Text::_("COM_MINIORANGE_SETUP2FA_CURL_ERROR1")));
      }
        $url =  'https://login.xecurify.com/moas/api/notify/send';
        $ch = curl_init($url);

        $customerKey = "16555";
        $apiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

        $currentTimeInMillis= round(microtime(true) * 1000);
        $stringToHash 		= $customerKey .  number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue 			= hash("sha512", $stringToHash);
        $customerKeyHeader 	= "Customer-Key: " . $customerKey;
        $timestampHeader 	= "Timestamp: " .  number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader= "Authorization: " . $hashValue;
        $fromEmail 			= $email;
        $currentUserEmail 	= Factory::getUser();
        $adminEmail         = $currentUserEmail->email;
        $subject = "MiniOrange Two Factor Authentication - Request for Quote";
        $content = '
                    <strong>Hello, <br><br>
                    Company :</strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>
                    <strong>Email :</strong><a href="mailto:' . $email . '" target="_blank">' . $email . '</a><br><br>
                    <strong>Quote Requested for OTP through:</strong> ' . $type_service . '<br><br>
                    <strong>Total Number of Users :</strong>'.$user_count.'<br><br>
                    ';
        if($type_service == 'SMS' || $type_service == 'OOSE') {
            $content .= '
                    <strong>Total number of OTP requested:</strong> ' . $number_otp . '<br><br>
                    <strong>Service Requested for Country:</strong> ' . $select_country . '<br>
                    <strong><br>Requested for country:</strong> ' . $which_country . '<br><br>
                    ';
        }else if($type_service == 'Email') { 
          $content .= '
                    <strong>Total number of OTP requested:</strong> ' . $number_otp . '<br><br>
                    ';
      }
        $content .='<strong> Extra Query:</strong> ' . $query . '';

      $fields = array(
          'customerKey'	=> $customerKey,
          'sendEmail' 	=> true,
          'email' 		=> array(
              'customerKey' 	=> $customerKey,
              'fromEmail' 	    => $email,
              'fromName' 		=> 'miniOrange',
              'toEmail' 		=> 'joomlasupport@xecurify.com',
              'toName' 		    => 'joomlasupport@xecurify.com',
              'subject' 		=> $subject,
              'content' 		=> $content
          ),
      );
      $field_string = json_encode($fields);

            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader,
                $timestampHeader, $authorizationHeader));
            curl_setopt( $ch, CURLOPT_POST, true);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
            $content = curl_exec($ch);

            if(curl_errno($ch)){
                return json_encode(array("status"=>'ERROR','statusMessage'=>curl_error($ch)));
            }
            curl_close($ch);

      return ($content);
  }
}?>