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
use Joomla\CMS\Factory;
require_once('commonUtilitiesTfa.php');
require_once('MoTfa_api.php');

class miniOrangeUser{
    public function challengeTest($authType=NULL,$isConfigure=false,$email="",$phone="") 
    {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL','KBA'=>'KBA');
        if($isConfigure)
        {
            if($authType=='OOS')
            {
                $fields = array (
                    'customerKey' => $customerKey,
                    'username' => '',
                    'phone' => str_replace(" ","",$phone),
                    'authType' => $authCodes[$authType],
                    'transactionName' => commonUtilitiesTfa::getTransactionName()
                  );
            }
            else
            {
                $fields = array (
                    'customerKey' => $customerKey,
                    'username' => '',
                    'email' => str_replace(" ","",$email),
                    'phone' => str_replace(" ","",$phone),
                    'authType' => $authCodes[$authType],
                    'transactionName' => commonUtilitiesTfa::getTransactionName()
                  );
            }
            
        }
        else{
            $fields = array (
              'customerKey' => $customerKey,
              'username' => $row['username'],
              'transactionName' => commonUtilitiesTfa::getTransactionName(),
              'authType'=>$authType
            );
        }


        $urls = commonUtilitiesTfa::getApiUrls(); 
        $url=$urls['challange'];
        $api = new MoTfa_api();
        $header= $api->get_http_header_array();
        return $api->make_curl_call($url, $fields, $header);
    }


    public function challenge($id,$authType=NULL,$isConfigure=false) {
        
        if($authType=="MICROSOFT AUTHENTICATOR" || $authType=="AUTHY AUTHENTICATOR" || $authType=="LASTPASS AUTHENTICATOR"|| $authType=="DUO AUTHENTICATOR")
        {
            $authType="GOOGLE AUTHENTICATOR";
        }

        $row=commonUtilitiesTfa::getMoTfaUserDetails($id);
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL','OOC'=>'PHONE VERIFICATION','KBA'=>'KBA','YK' => 'HARDWARE TOKEN');
        $phone = '';
        if(!empty($row) && $authType == NULL )
        {
            $email = $row['email'];
            $phone = $row['phone'];
            $get_user= json_decode(commonUtilitiesTfa::get_user_on_server($email));
            $authType = $get_user->authType;

            $authType = array_key_exists($authType, $authCodes)?$authCodes[$authType]:$authType;
        }

       if($isConfigure)
        {
            if($authType=='OOS')
            {
                $fields = array (
                    'customerKey' => $customerKey,
                    'username' => '',
                    'phone' => str_replace(" ","",$row['phone']),
                    'authType' => $authCodes[$authType],
                    'transactionName' => commonUtilitiesTfa::getTransactionName()
                  );
            }
            elseif($authType=='OOC') {
                $fields = array(
                    'customerKey' => $customerKey,
                    'username' => '',
                    'phone' => str_replace(" ","",$row['phone']),
                    'authType' => $authCodes[$authType],
                    'transactionName' => commonUtilitiesTfa::getTransactionName()
                );
            }
            else
            {
                $fields = array (
                    'customerKey' => $customerKey,
                    'username' => '',
                    'email' => str_replace(" ","",$row['email']),
                    'phone' =>  str_replace(" ","",$row['phone']),
                    'authType' => $authCodes[$authType],
                    'transactionName' => commonUtilitiesTfa::getTransactionName()
                  );
            }

            
        }
        else{
            $fields = array (
              'customerKey' => $customerKey,
              'username' => $row['username'],
              'phone'    => $phone,
              'transactionName' => commonUtilitiesTfa::getTransactionName(),
              'authType'=> $authType
            );
        }

        $urls = commonUtilitiesTfa::getApiUrls();  
        $url=$urls['challange'];
        $api = new MoTfa_api();
        $header= $api->get_http_header_array();
        return $api->make_curl_call($url, $fields, $header);
    }


    public function challanger($userId){
        
        $session = Factory::getSession();
        $uid=commonUtilitiesTfa::decrypt($userId);
        $row=commonUtilitiesTfa::getMoTfaUserDetails($uid);
        $settings=commonUtilitiesTfa::getTfaSettings();
        $active_tfa_methods=$settings['activeMethods'];
        $active_tfa_methods = trim($active_tfa_methods, '[]');

        $methods= str_replace(['"'],"",$active_tfa_methods);
        $count = count(explode(",",$methods));

        if( !commonUtilitiesTfa::isValidUid($uid) ){
           
            $session->set('steps','invalid');
        }
    
        else if(empty($row['status_of_motfa']) && $count==1 && $methods!= 'ALL')
        {
            $session->set('steps','skip');
            $moTfa=array('inline'=>array('whoStarted'=>Factory::getUser($uid),'status'=>'attempted'));
            $session->set('motfa',$moTfa);
            $session->set('mo_tfa_message','');

        }
        else if(empty($row['status_of_motfa']))
        {
            $session->set('steps','three');
            $moTfa=array('inline'=>array('whoStarted'=>Factory::getUser($uid),'status'=>'attempted'));
            $session->set('motfa',$moTfa);
            $session->set('mo_tfa_message','');

        }
        else if($row['status_of_motfa']=='active'){

            $session->set('steps',$row['status_of_motfa']);
            $response=json_decode($this->challenge($uid,NULL,FALSE));
            
            if($response->status=='SUCCESS'){
                
                commonUtilitiesTfa::updateOptionOfUser($uid,'transactionId',$response->txId);
                $session->set('current_user_id',$uid);
                $session->set('challenge_response',$response);

                if($response->authType=='EMAIL'){
                    
                    $session->set('mo_tfa_message','A passcode (OTP) has been sent to your <strong>email</strong> to verify your identity');
                }
                else if($response->authType=='SMS'){
                    
                    $session->set('mo_tfa_message','A passcode (OTP) has been sent to your <strong>phone</strong> to verify your identity');
                }
                else if($response->authType=='SMS AND EMAIL'){
                    
                    $session->set('mo_tfa_message','A passcode (OTP) has been sent to your <strong>email</strong> and <strong>phone</strong> to verify your identity');
                }
                else if($response->authType=='KBA'){
                    
                    $session->set('mo_tfa_message','Answer these questions to login');
                }
                else if($response->authType=='MICROSOFT AUTHENTICATOR'){
                    
                    $session->set('mo_tfa_message','Enter passcode displayed on your <strong>Microsoft Authenticator</strong> App to verify your identity');
                }
                else if($response->authType=='AUTHY AUTHENTICATOR'){
                    
                    $session->set('mo_tfa_message','Enter passcode displayed on your <strong>Authy Authenticator</strong> App to verify your identity');
                }
                else if($response->authType=='LASTPASS AUTHENTICATOR'){
                    
                    $session->set('mo_tfa_message',"Enter passcode displayed on your <strong>LastPass Authenticator</strong> App to verify your identity");
                }
                else if($response->authType=='DUO AUTHENTICATOR'){
                    
                    $session->set('mo_tfa_message',"Enter passcode displayed on your <strong>Duo Authenticator</strong> App to verify your identity");
                }
                else if($response->authType=='HARDWARE TOKEN'){
                
                    $session->set('mo_tfa_message','Enter the code by taping on the <strong>hardware token</strong> to verify your identity');
                }
                else{
                    
                    $session->set('mo_tfa_message',"Enter passcode displayed on your <strong>Google Authenticator</strong> App to verify your identity");
                }
                 $session->set('mo_tfa_message_type','mo2f-message-status');

            }
            else{
                $session->set('mo_tfa_message',"Facing issues please try after some time");
                $session->set('mo_tfa_message_type','mo2f-message-error');

            }
           
        }
        else{
            $session->set('mo_tfa_message','');
             $session->set('steps',$row['status_of_motfa']);
             $moTfa=array('inline'=>array('whoStarted'=>Factory::getUser($uid),'status'=>'attempted'));
             $session->set('motfa',$moTfa);
        }
                         
    }

    function mo2f_update_userinfo( $email,$authType, $phone='') {

        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        
        $authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL','KBA'=>'KBA',
                                'google'=>'GOOGLE AUTHENTICATOR','MA'=>'MICROSOFT AUTHENTICATOR',
                                'AA'=>'AUTHY AUTHENTICATOR','LPA'=>'LASTPASS AUTHENTICATOR',
                                'DUO'=>'Duo AUTHENTICATOR', 'YK'=>'HARDWARE TOKEN');
        $fields            = array(
            'customerKey'            => $customerKey,
            'username'               => $email,
            'phone'                  => $phone,
            'transactionName'        => commonUtilitiesTfa::getTransactionName(),
        );
        if($authType!=''){
            $fields['authType']=$authCodes[$authType];
        }
      

        $urls = commonUtilitiesTfa::getApiUrls();
        $url=$urls['update'];
        $api= new MoTfa_api();
        $header = $api->get_http_header_array();
        return $api->make_curl_call($url, $fields, $header);
        
    }

    public function validate($id,$token,$authType, $answers = NULL,$isConfiguring=false) {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $row=commonUtilitiesTfa::getMoTfaUserDetails($id);
        $authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL');

        if($isConfiguring){
            $fields = array (
                'customerKey' => $customerKey,
                'txId' => $row['transactionId'],
                'token' => str_replace(" ","",$token),
            );
        } 
        else{
            $fields = array (
                'customerKey' => $customerKey,
                'username' => $row['username'],
                'txId' => $row['transactionId'],
                'token' => str_replace(" ","",$token),
                'authType' =>array_key_exists($authType, $authCodes)?$authCodes[$authType]:$authType ,
                'answers' => $answers
            );
        } 

        $urls = commonUtilitiesTfa::getApiUrls();
        $url=$urls['validate'];
        $api= new MoTfa_api();
        $header = $api->get_http_header_array();
        return $api->make_curl_call($url,$fields,$header);
    }

    public function validateTest($token,$authType, $answers = NULL,$isConfiguring=false,$secret="") {
        $details = commonUtilitiesTfa::getCustomerDetails();
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $apiKey      = $customerKeys['apiKey'];
        $authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL');

        if($isConfiguring){
            $fields = array (
                'customerKey' => $customerKey,
                'txId' => $secret,
                'token' => str_replace(" ","",$token),
            );
        }
        else{
            $fields = array (
                'customerKey' => $customerKey,
                'username' => $detail['email'],
                'txId' => $secret,
                'token' => str_replace(" ","",$token),
                'authType' =>array_key_exists($authType, $authCodes)?$authCodes[$authType]:$authType ,
                'answers' => $answers
            );
        } 

        $urls = commonUtilitiesTfa::getApiUrls();
        $url=$urls['validate'];
        $api= new MoTfa_api();
        $header = $api->get_http_header_array();
        return $api->make_curl_call($url,$fields,$header);
    }

    public function validateGoogleToken($username,$secret,$token,$method)
    {
        $authenticatorType = NULL;
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key']; 

        $urls = commonUtilitiesTfa::getApiUrls();

        if($method == 'YK')
        {
            $authenticatorType = 'HARDWARE TOKEN';
            $registrationType = 'YUBIKEY_TOKEN';
            $quesAnsList = NULL;
            $url=$urls['kbaRegister'];

            $fields = array(
                'customerKey' => $customerKey,
                'username' => $username,
                'registrationType' => $registrationType,
                'secret' => $secret,
                'otpToken' => !empty($token) ? str_replace(" ", "", $token) : '',
                'authenticatorType' => $authenticatorType,
                'questionAnswerList' => $quesAnsList
            );
        }
        else
        {
            $url=$urls['googlevalidate'];
            $fields = array(
                'customerKey' => $customerKey,
                'username'    => $username, 
                'secret'      => $secret,
                'otpToken'    => str_replace(" ","",$token)
            );
        }
        
        $api= new MoTfa_api();
        $header = $api->get_http_header_array();
        return $api->make_curl_call($url,$fields,$header);
    }

    function mo_create_user($id,$name) {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $row=commonUtilitiesTfa::getMoTfaUserDetails($id);
        $fields      = array(
            'customerKey' => $customerKey,
            'username'    => $row['email'],
            'firstName'   => $name,
            'lastName'    => $row['email'],
        );
        $urls = commonUtilitiesTfa::getApiUrls();
        $url=$urls['createUser']; 

        $mo2fApi= new MoTfa_api();
        $http_header_array = $mo2fApi->get_http_header_array();
        return $mo2fApi->make_curl_call( $url, $fields, $http_header_array );

    }
    function mo2f_get_userinfo( $email ) {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $fields      = array(
            'customerKey' => $customerKey,
            'username'    => $email,
        );
        $urls = commonUtilitiesTfa::getApiUrls();
        $url = $urls['getUserInfo'];
        $api = new MoTfa_api();
        $http_header_array = $api->get_http_header_array();

        return $api->make_curl_call( $url, $fields, $http_header_array );
    }
    
    function register_kba_details( $email, $question1, $answer1, $question2, $answer2, $question3, $answer3 ) 
    {
        $customerKeys= commonUtilitiesTfa::getCustomerKeys();
        $customerKey = $customerKeys['customer_key'];
        $q_and_a_list = "[{\"question\":\"" . $question1 . "\",\"answer\":\"" . $answer1 . "\" },{\"question\":\"" . $question2 . "\",\"answer\":\"" . $answer2 . "\" },{\"question\":\"" . $question3 . "\",\"answer\":\"" . $answer3 . "\" }]";
        $field_string = "{\"customerKey\":\"" . $customerKey . "\",\"username\":\"" . $email . "\",\"questionAnswerList\":" . $q_and_a_list . "}";
        $urls = commonUtilitiesTfa::getApiUrls();
        $url=$urls['kbaRegister'];
        $mo2fApi= new MoTfa_api();
        $http_header_array = $mo2fApi->get_http_header_array();
        return $mo2fApi->make_curl_call( $url, $field_string, $http_header_array );
    }

    public function updateEmail($username,$email)
    {
        $hostname = commonUtilitiesTfa::getHostname();
        $url = $hostname . '/moas/api/admin/users/update';

        
        $customer_details = commonUtilitiesTfa::_genericGetDBValues('#__miniorange_tfa_customer_details');

        $customerKey = $customer_details['customer_key'];
        $apiKey = $customer_details['api_key'];


        $ch = curl_init($url);
        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $customerKey;
        $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader = "Authorization: " . $hashValue;

        $fields = array(
            'customerKey' => $customerKey,
            'username' => $username,
            'email' => $email,
            'transactionName' => 'Joomla 2FA Plugin'
        );
        

        $field_string = json_encode($fields);

        $api = new MoTfa_api();
        $header= $api->get_http_header_array();
        return $api->make_curl_call($url, $field_string, $header);
    
    }
}
?>