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
// import common utilities from library
jimport('miniorangetfa.utility.commonUtilitiesTfa');
jimport('miniorangetfa.utility.miniOrangeUser');

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

Log::addLogger(
	array(
		 'text_file' => 'tfa_admin_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);

class miniorange_twofaControllersetup_two_factor  extends FormController
{
	public  function configure(){
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		Log::add('Customer registration status: ' . ($isCustomerRegistered ? 'Registered' : 'Not Registered'), Log::INFO, 'TFA');
		if(!$isCustomerRegistered){
			Log::add('Redirecting to account setup due to unregistered customer.', Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$current_user =  Factory::getUser();
		$username='';
		$email='';
		$phone='';
		
		$authType=$post['authType'];
		Log::add('Received authType: ' . $authType, Log::INFO, 'TFA');
		if(isset($post['authType'])&&$post['authType']=='OOSE'){
			$username=isset($post['Email'])?$post['Email']:'';
		    $email=isset($post['Email'])?$post['Email']:'';
		    $phone=$post['Phone'];
			Log::add('Set username: ' . $username . ', email: ' . $email . ', phone: ' . $phone, Log::INFO, 'TFA');
		}
		else if(isset($post['authType'])&&$post['authType']=='OOS'){
			$authType='OOS';
			$username=$current_user->email;
			$phone=$post['Phone'];
			$email="";
			Log::add('Using current user email for OOS: ' . $username . ', phone: ' . $phone, Log::INFO, 'TFA');
		}
		else{
			$authType='OOE';
			$username=isset($post['Email'])?$post['Email']:'';
		    $email   =isset($post['Email'])?$post['Email']:'';
			$phone="";
			Log::add('Set username: ' . $username . ', email: ' . $email . ', phone is empty for OO2.', Log::INFO, 'TFA');
		}
		$user = new miniOrangeUser();	
		$response=json_decode($user->challengeTest($authType,true,$email,$phone));
		setcookie("step_two_txID",$response->txId);
		Log::add('Challenge test response: ' . json_encode($response), Log::INFO, 'TFA');
		if($response->status=='SUCCESS'){
			Log::add('OTP generated successful.', Log::INFO, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&configure='.$authType.'&step=2',Text::_('COM_MINIORANGE_SETUP2FA_OTP_MSG'));
			return;
		}
		else{
			Log::add('Challenge test failed: ' . $response->message, Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&configure='.$authType,$response->message,'error');
			return;
		}
	}
	public function configure_step_two()
	{
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered){
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$user = new miniOrangeUser();
		$current_user = Factory::getUser();
		$secret = Factory::getApplication()->input->cookie->get('step_two_txID');
		setcookie("step_two_txID", "", time()-3600);
		Log::add('Received OTP: ' . $post['Otp_token'] . ' and configuration: ' . $post['configuring'], Log::INFO, 'TFA');
		$response= $user->validateTest($post['Otp_token'],$post['configuring'],NULL,true,$secret);
		$response=json_decode($response);
		Log::add('validateTest response: ' . json_encode($response), Log::INFO, 'TFA');
		if($response->status=='SUCCESS'){
			Log::add('OTP validation successful, redirecting to setup_two_factor with success message.', Log::INFO, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor', Text::_('COM_MINIORANGE_SETUP2FA_TESTED'),'success');
			return;
		}
		else{
			Log::add('OTP validation failed: ' . $response->message, Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&configure='.$post['configuring'].'&step=2',$response->message,'error');
				return;
		}
	}

	public function validateAppPasscode(){
		$c_time =date("Y-m-d",time());
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $get = ($input && $input->get) ? $input->get->getArray() : [];
		
		$name=$get['AppName'];
		$appName=array(
			"google"=>"Google",
			"AA"=>"Authy",
			"MA"=>"Microsoft",
			"LPA"=>"LastPass", 
			"DUO"=>"DUO",
		);
		$passcode=$post['google_passcode'];
		$user = new miniOrangeUser();
		
		$details=commonUtilitiesTfa::getCustomerDetails();
		Log::add('Customer details retrieved for email: ' . $details['email'], Log::INFO, 'TFA');
		Log::add('Received passcode for app: ' . $appName[$name], Log::INFO, 'TFA');
		$response=json_decode($user->validateGoogleToken($details['email'],$post['txID'],$passcode,$get['AppName']));
		Log::add('Google token validation response: ' . json_encode($response), Log::INFO, 'TFA');
		if($response->status=='SUCCESS')
		{
			Log::add('Google token validation successful, redirecting with success message.', Log::INFO, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor', Text::_('COM_MINIORANGE_SETUP2FA_TESTED').$appName[$name].Text::_(' COM_MINIORANGE_SETUP2FA_AUTH_METHOD'));
			return;
		}
		else if($response->status=='FAILED'){
			Log::add('Google token validation failed: Invalid passcode, redirecting with error message.', Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&configure='.$get['AppName'].'&step=3',Text::_('COM_MINIORANGE_SETUP2FA_INVALID'),'error');
			return;
		} 
 
	}
	
	public  function support()
	{
		$c_time =date("Y-m-d",time());
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$obj = new Mo_tfa_Customer();
        $response = $obj->submit_contact_us($post['email'],$post['phone'],$post['query']);
		Log::add('Support query submission response: ' . $response, Log::INFO, 'TFA');

	    if ( $response!= 'Query submitted.' ) 
		{
			Log::add('Support query submission failed, error message: ' . $response, Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG'),'error');
			return;
	    } 
	    else 
		{
			Log::add('Support query submitted successfully, email: ' . $email, Log::INFO, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG1'));
			return;
	    }
	}
	public  function trialsupport()
	{
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$c_time =date("Y-m-d",time());
		$obj = new Mo_tfa_Customer();
		Log::add('Trial support request received. Data: ' . json_encode($post), Log::INFO, 'TFA');
        $response = $obj->submit_trial_request($post['trial_or_demo'],$post['trial_email_id'],$post['trial_mobile_number'],$post['trial_plan'],$post['trial_description']);
	    if(json_last_error() == JSON_ERROR_NONE){
			if(is_array($response) && array_key_exists('status', $response) && $response['status'] == 'ERROR'){
				Log::add('Trial support query submission failed. Error message: ' . $response['message'], Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', $response['message'], 'error');
				return;
			}else{
				if ( $response == false ) {
					Log::add('Trial support query submission failed. General failure.', Log::ERROR, 'TFA');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG'),'error');
					return;
				} else {
					Log::add('Trial support query successfully submitted.', Log::INFO, 'TFA');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG1'));
					return;
				}
			}
		}
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG2'),'error');
		return;
	}

	public function callSupport() {
		$c_time =date("Y-m-d",time());
	    $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		Log::add('Call support request received. Data: ' . json_encode($post), Log::INFO, 'TFA');

	    if(count($post) == 0) {
			Log::add('No data received for call support request.', Log::ERROR, 'TFA');
	        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support',Text::_('COM_MINIORANGE_SETUP2FA_FILL_DETAILS'),'error');
	        return;
        }
        $query_email = $post['mo_sp_setup_call_email'];
	    $query = $post['mo_sp_setup_call_issue'];
	    $description = $post['mo_sp_setup_call_desc'];
	    $callDate = $post['mo_sp_setup_call_date'];
        $timeZone    =$post['mo_sp_setup_call_timezone'];

        if(empty($query_email) || empty($query) || empty($description) || empty($callDate) || empty($timeZone)) {
			Log::add('Mandatory fields missing in call support request: email, query, description, callDate, or timeZone.', Log::ERROR, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support',Text::_('COM_MINIORANGE_SETUP2FA_FIELDS_MANDATORY'),'error');
            return;
        } else {
            $contact = new Mo_tfa_Customer();
            $submitted = json_decode($contact->request_setup_call($query_email, $query, $description, $callDate, $timeZone), true);
		
            if(json_last_error() == JSON_ERROR_NONE){
                if(is_array($submitted) && array_key_exists('status', $submitted) && $submitted['status'] == 'ERROR'){
					Log::add('Call support query submission failed. Error message: ' . $submitted['message'], Log::ERROR, 'TFA');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', $submitted['message'], 'error');
                    return;
                }else{
                    if ( $submitted == false ) {
						Log::add('Call support query submission failed. General failure.', Log::ERROR, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG'),'error');
                        return;
                    } else {
						Log::add('Call support query successfully submitted.', Log::INFO, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG1'));
                        return;
                    }
                }
            }
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG2'),'error');
            return;
        }

    }

    public function requestQuote() {
		$c_time =date("Y-m-d",time());
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $array = ($input && $input->post) ? $input->post->getArray() : [];
        $type_service = isset($array['type_service'])?$array['type_service']:"";
        Log::add('Received type_service: ' . $type_service, Log::INFO, 'TFA');

        $email = isset($array['email'])?$array['email']:"";
        $user_count = isset($array['no_of_users'])?$array['no_of_users']:0;
        $number_otp = isset($array['no_of_otp'])?$array['no_of_otp']:0;
        if($type_service=='SMS'||$type_service=='OOSE')
        {

            $select_country=$array['select_country'];
            if($select_country=="singlecountry")
            {
                $which_country=$array['select_country'];
            }
        }
        $query = isset($array['user_extra_requirement'])?$array['user_extra_requirement']:"";
        if(empty($email)){
			Log::add('Email is missing from the request', Log::ERROR, 'TFA');

            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support',Text::_('COM_MINIORANGE_SETUP2FA_EMAIL'),'error');
            return;
        }else{
            $select_country=isset($array['select_country'])?$array['select_country']:"";
            $which_country=isset($array['which_country'])?$array['which_country']:"";
			Log::add('Submitting quote request with parameters: ' . json_encode(compact('type_service', 'email', 'user_count', 'number_otp', 'select_country', 'which_country', 'query')), Log::INFO, 'TFA');
            $quote_Request=new Mo_tfa_Customer();
            $submitted = json_decode($quote_Request->submit_request_quote($type_service, $email,$user_count, $number_otp,$select_country,$which_country,$query), true);
        
			if (json_last_error() == JSON_ERROR_NONE){
                if (is_array($submitted) && array_key_exists('status', $submitted) && $submitted['status'] == 'ERROR') {
					Log::add('Quote request failed with message: ' . $submitted['message'], Log::ERROR, 'TFA');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', $submitted['message'], 'error');
                    return;
                } else {
                    if($submitted == false){
						Log::add('Quote request submission failed', Log::ERROR, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support',Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG'),'error');
                        return;
                    }else {
						Log::add('Quote request submitted successfully', Log::INFO, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG1'));
                        return;
                    }
                }
            }
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG2'),'error');
            return;
        }

	}
	public  function register_login_customer(){
		$c_time =date("Y-m-d",time());
		// extract all posted data
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		Log::add('POST data count: ' . count($post), Log::INFO , 'TFA');
		// decide if it is registration or login
		$isRegistering = isset($post['register_or_login'])&&$post['register_or_login']=='Register'?TRUE:FALSE;
		if($isRegistering){
			Log::add('Registering a new customer.', Log::INFO, 'TFA');
			// check if password and confirm password matches
			if(isset($post['password']) && isset($post['confirm_password']))
			{
				if($post['password']!=$post['confirm_password'])
				{  
					Log::add('Password and confirm password do not match.', Log::WARNING, 'TFA');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup', Text::_('COM_MINIORANGE_SETUP2FA_PASSWORD_MSG') ,'error');
            		return;
				}
				else if(strlen($post['password'])<6)
				{   
					Log::add('Password length is less than 6 characters.', Log::WARNING, 'TFA');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup', Text::_('COM_MINIORANGE_SETUP2FA_PASSWORD_MSG1') ,'error');
            		return;
				}
			}

			//check if customer already exist
			$customer = new Mo_tfa_Customer();
        	$check_customer_response  = json_decode($customer->check_customer(trim($post['email'])));
        	if(!is_object( $check_customer_response ) || !isset( $check_customer_response->status ) || empty($check_customer_response->status))
			{   
				Log::add('Error checking customer existence or invalid response.', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG'), 'error');
                return;
       		}
        	elseif ($check_customer_response->status == 'TRANSACTION_LIMIT_EXCEEDED') 
			{   
				Log::add('Transaction limit exceeded for customer.', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_TESTING'), 'error');
            	return;
        	}
			elseif ($check_customer_response->status == 'CURL_ERROR') {
				Log::add('CURL error occurred during customer check.', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CURL_MSG'), 'error');
				return;
        	}
        	elseif ($check_customer_response->status == 'CUSTOMER_NOT_FOUND') 
			{
				Log::add('Customer not found during registration process.', Log::WARNING, 'TFA');
            	$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',"User Not Found", 'error');
				return;
        	} 
        	else {
				Log::add('Customer already has an account.', Log::INFO, 'TFA');
        		// already have an acount
        		$this->customerLogin(trim($post['email']),$post['password']);
        	}
		}
		else{
			Log::add('Starting login process for existing customer.', Log::INFO, 'TFA');
			// start login process
			$this->customerLogin(trim($post['email']),$post['password']);
		}
	}
	
	public  function customerRegister($email,$password,$phone='')
	{
		Log::add('Entering customerRegister function for email: ' . $email, Log::INFO, 'tfa');
		$c_time =date("Y-m-d",time());
		// save email,password,phone and change registration status to OTP
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('email') . ' = '.$db->quote($email),
			$db->quoteName('password').' ='.$db->quote(base64_encode($password)),
			$db->quoteName('admin_phone').' ='.$db->quote($phone),
		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		$db->setQuery($query);
		Log::add('Database query: ' . $query->__toString(), Log::INFO, 'TFA');
		$result = $db->execute();
		Log::add('Customer details updated successfully for email: ' . $email, Log::INFO, 'tfa');
		$this->sendOtp('sent');
		// send an otp to the email and save Transaction Id on successfull otp transfer
	}
	
	public  function customerLogin($email,$password){
		$c_time =date("Y-m-d",time());
		Log::add('Initiating customer login process for user with email: ' . $email, Log::INFO, 'tfa');
        $customer= new Mo_tfa_Customer();
		$customer_keys_response = json_decode($customer->getCustomerKeys($email,$password));
		
        if (json_last_error() == JSON_ERROR_NONE) {
            if(!is_object( $customer_keys_response ) || !isset( $customer_keys_response->id ) || empty($customer_keys_response->id)){
				Log::add('Customer login failed. Response: ' . json_encode($customer_keys_response), Log::ERROR, 'tfa');
                $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup&tab=login',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG'), 'error');
                return;
            }
            else{
            	// save customer details
            	if($customer_keys_response->status=='SUCCESS')
            	{
					Log::add('Customer login successful for email: ' . $email, Log::INFO, 'tfa');
            		$current_user = Factory::getUser();
            		commonUtilitiesTfa::saveCustomerDetailsAfterLogin($email,$password,$customer_keys_response->phone,$customer_keys_response->id,$customer_keys_response->apiKey,$customer_keys_response->token,$customer_keys_response->appSecret,$current_user->id);
            		 
            		$moUser = new miniOrangeUser();
            		//$userApiResponse = json_decode($moUser->mo2f_update_userinfo($email,'OOE'));
            		$customer = new Mo_tfa_Customer();
					$response = json_decode($customer->fetchLicense());
					
					commonUtilitiesTfa::updateLicenseDetails($response,$email); 
            		$erMsg = Text::_('COM_MINIORANGE_SETUP2FA_ACCOUNT_RETRIEVED');
					Log::add('License details updated for email: ' . $email, Log::INFO, 'tfa');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',$erMsg);
            	}
            	else{
					Log::add('Customer login failed with status: ' . $customer_keys_response->status, Log::ERROR, 'tfa');
					$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup&tab=login',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG'), 'error');
					return;
				}
			}
		}
		else {
			Log::add('JSON decode error for getCustomerKeys response. Error: ' . json_last_error_msg(), Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup&tab=login',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG1'), 'error');
			return;
		}

	}

	public  function backToRegisterLogin(){
		Log::add('Resetting customer registration details', Log::INFO, 'tfa');
		$c_time =date("Y-m-d",time());
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('email') . ' = '.$db->quote(''),
			$db->quoteName('password').' ='.$db->quote(''),
			$db->quoteName('admin_phone').' ='.$db->quote(''),
			$db->quoteName('registration_status') .' = '.$db->quote('not-started'),
			$db->quoteName('transaction_id') . ' = '.$db->quote('')
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);

		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup');
		Log::add('Customer registration details reset successfully', Log::INFO, 'tfa');
            return;

	}
	public  function resendOtp(){
		$c_time =date("Y-m-d",time());
		Log::add('Resending OTP', Log::INFO, 'tfa');
		$this->sendOtp('resent');
	}
	public function sendOtp($sentOrResent){
		$c_time =date("Y-m-d",time());
		$details  = commonUtilitiesTfa::getCustomerDetails();
		$customer = new Mo_tfa_Customer();
		$response = $customer->send_otp_token('EMAIL',$details['email'],'',true);
		$response = json_decode($response);

		if($response->status == 'SUCCESS' ){
			$this->updateTransactionId($response->txId);
			Log::add('OTP sent successfully to ' . $details['email'], Log::INFO, 'tfa');
 			$application = Factory::getApplication();
			$msg_otp=Text::_('COM_MINIORANGE_SETUP2FA_OTP_SENT') .$sentOrResent. Text::_('COM_MINIORANGE_SETUP2FA_OTP_TO') .$details['email'].Text::_('COM_MINIORANGE_SETUP2FA_ENTER_OTP');
             $application->enqueueMessage($msg_otp,'success');
             $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup');

			return;
		}
		else{
			Log::add('Error sending OTP: ' . json_encode($response), Log::ERROR, 'tfa');
			commonUtilitiesTfa::plugin_error_log($c_time." : administrator/component/com_miniorange_twofa/controller/setup_two_factor - In a function sendOtp().".json_encode($response));
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_OTP_ERROR').$details['email'],'error');
			return;
		}
	}
	public  function validateOTP()
	{
		$c_time =date("Y-m-d",time());
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		Log::add('POST data received: ' . json_encode($post), Log::INFO, 'TFA');
		$details  = commonUtilitiesTfa::getCustomerDetails();
		Log::add('Customer details fetched: ' . json_encode($details), Log::INFO, 'TFA');
		$customer = new Mo_tfa_Customer();
		$response = $customer->validate_otp_token($details['transaction_id'],$post['OTP_token'],true);
		$response = json_decode($response);
		Log::add('OTP validation response: ' . json_encode($response), Log::INFO, 'TFA');
		
		if($response->status=='SUCCESS')
		{
			Log::add('OTP validation successful', Log::INFO, 'TFA');
			// create customer
			$apiResponse=$customer->create_customer($details['email'],$details['admin_phone'],base64_decode($details['password']));
			$apiResponse = json_decode($apiResponse);
			Log::add('Customer creation response: ' . json_encode($apiResponse), Log::INFO, 'TFA');

			if($apiResponse->status == 'SUCCESS'){
				Log::add('Customer created successfully', Log::INFO, 'TFA');

				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$current_user = Factory::getUser();
		 		// Fields to update.
				$fields = array(
					$db->quoteName('email') . ' = '.$db->quote($apiResponse->email),
					$db->quoteName('customer_key') . ' = '.$db->quote($apiResponse->id),
					$db->quoteName('api_key') . ' = '.$db->quote($apiResponse->apiKey),
					$db->quoteName('customer_token') . ' = '.$db->quote($apiResponse->token),
					$db->quoteName('app_secret') . ' = '.$db->quote($apiResponse->appSecret),
					$db->quoteName('login_status') . ' = '.$db->quote(1),
					$db->quoteName('new_registration') .' = '.$db->quote(1),
					$db->quoteName('jid') .' = '.$db->quote($current_user->id),
				);
			 
				// Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('id') . ' = 1'
				);
			 
				$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
				$db->setQuery($query);
				$result = $db->execute();
				Log::add('Database updated successfully', Log::INFO, 'TFA');
				$current_user = Factory::getUser();
            
            	$customer = new Mo_tfa_Customer();
				$response = json_decode($customer->fetchLicense());
				Log::add('License fetched: ' . json_encode($response), Log::INFO, 'TFA');

				commonUtilitiesTfa::updateLicenseDetails($response,$details['email']);
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_OOE_MSG'));
				Log::add('Redirecting to setup with success message', Log::INFO, 'TFA');
				return;
			}
			else if(strcasecmp($apiResponse->status,'INVALID_EMAIL_QUICK_EMAIL')==0 || strcasecmp($apiResponse->status,'INVALID_EMAIL')==0 ){
				Log::add('Invalid email during customer creation', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG2'),'error');
				return;
			}
			else if($apiResponse->status == 'FAILED'){
				Log::add('Customer creation failed while processing request', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG3'),'error');
				return;
			}
			else{
				Log::add('Customer creation failed while processing request', Log::ERROR, 'TFA');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG3'),'error');
				return;
			}
		}
		else if($response->status=='FAILED')
		{
			Log::add('OTP validation failed', Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_INVALID_OTP'),'error');
			return;
		}
		else
		{
			Log::add('Customer creation failed while processing request', Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ERROR_MSG3'),'error');
			return;
		}
	}
	static function  updateTransactionId($transactionId) 
	{
		$c_time =date("Y-m-d",time());
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('registration_status') .' = '.$db->quote('OTP'),
			$db->quoteName('transaction_id') . ' = '.$db->quote($transactionId),
			
		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		Log::add('Query to update transaction ID: ' . $query->__toString(), Log::INFO, 'tfa');
		$db->setQuery($query);
		$result = $db->execute();
		Log::add('Transaction ID updated successfully. Result: ' . json_encode($result), Log::INFO, 'tfa');
	}

	public function domain()
	{
		$app   = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
		$domains = $input->get('domains', '', 'STRING');
		$db = Factory::getDbo();
		$db->transactionStart(); 
		$app = Factory::getApplication();
		$app->enqueueMessage('Domains received: ' . $domains, 'message');
			
		try {
			$domainArray = explode("\n", trim($domains));
			$cleanedDomains = [];
			foreach ($domainArray as $domain) {
				$domain = trim($domain);
				if (!empty($domain)) {
					$cleanedDomains[] = $db->quote($domain);
				}
			}
	
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__miniorange_allowed_domains'));
			$db->setQuery($query);
			$db->execute();
	
	
			if (!empty($cleanedDomains)) {
				$query = $db->getQuery(true);
				$query
					->insert($db->quoteName('#__miniorange_allowed_domains'))
					->columns($db->quoteName('domain'))
					->values(implode('), (', $cleanedDomains));
				$db->setQuery($query);
				$db->execute();
	
			}
			$db->transactionCommit();
			$this->setMessage(Text::_('COM_MINIORANGE_DOMAINS_SAVED_SUCCESS'));
		} catch (Exception $e) {
			$db->transactionRollback(); 

			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
	
			$this->setMessage(Text::_('COM_MINIORANGE_DOMAINS_SAVE_FAILED'), 'error');
		}
	
		$this->setRedirect(Route::_('index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=advance_settings', false));
	}
	
	public function saveTfaSettings()
	{
		Log::add('Started process to save login settings.', Log::INFO, 'TFA');
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		Log::add('Customer registration check: ' . ($isCustomerRegistered ? 'Yes' : 'No'), Log::INFO, 'TFA');

		if(!$isCustomerRegistered){
			Log::add('Redirecting due to unregistered customer.', Log::INFO, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app=Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
 
		if(count($post)==0){
			Log::add('No post data received, redirecting.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup');
            return;
        }

		$active2FAMethods=array();
		$allTfaMethods = commonUtilitiesTfa::getAllTfaMethods();

		Log::add('All available TFA methods: ' . json_encode($allTfaMethods), Log::INFO, 'TFA');

		foreach ($allTfaMethods as $key=>$value){
		    if(isset($post['tfa_method_allowed_'.$key]) && $post['tfa_method_allowed_'.$key]=='on' )
		        array_push($active2FAMethods,$key);
        }
        $validator = function ($var) {
            if($var == 1 || $var == TRUE || $var == 'on'){
                return TRUE;
            }
            return FALSE;
		};
		
		$kbasetofques1             = Text::_('COM_MINIORANGE_SETUP2FA_KBA1');
		$kbasetofques2             = Text::_('COM_MINIORANGE_SETUP2FA_KBA2');
		$enabled_tfa               = isset($post['enable_mo_tfa']) ? $validator($post['enable_mo_tfa']) : 0;
		$enable_2fa_user_type      = isset($post['enable_2fa_user_type']) ? $post['enable_2fa_user_type'] : 'none';
		$inline                    = isset($post['enable_mo_tfa_inline']) ? $validator($post['enable_mo_tfa_inline']) : 0;
        $skip_tfa_for_users        = isset($post['skip_tfa_for_users']) ? $post['skip_tfa_for_users'] : 0;
		$enable_otp_login = isset($post['enable_tfa_passwordless_login']) ? (int) $validator(var: $post['enable_tfa_passwordless_login']) : 0;
		$enable_change_2fa_method  = isset($post['enable_change_2fa_method']) ? $validator($post['enable_change_2fa_method']) : 0;
		$enable_remember_device    = isset($post['enable_remember_device']) ? $validator($post['enable_remember_device']) : 0;
		$enable_2fa_backup_method  = isset($post['enable_2fa_backup_method']) ? $validator($post['enable_2fa_backup_method']) : 0;
		$enable_2fa_backup_type    = isset($post['enable_2fa_backup_type']) ? $post['enable_2fa_backup_type'] : 'none'; 
		$KBA_set1                  = isset($post['KBA_set_ques1']) ? trim($post['KBA_set_ques1']) : $kbasetofques1;
		$KBA_set2                  = isset($post['KBA_set_ques2']) ? trim($post['KBA_set_ques2']) : $kbasetofques2;
	
		Log::add('Extracted settings: ' . json_encode(compact('enabled_tfa', 'enable_2fa_user_type', 'inline', 'skip_tfa_for_users', 'enable_otp_login', 'enable_change_2fa_method', 'enable_remember_device', 'enable_2fa_backup_method', 'enable_2fa_backup_type', 'KBA_set1', 'KBA_set2')), Log::INFO, 'TFA');

		
		$details= commonUtilitiesTfa::getCustomerDetails();
        $inlineDisabled='';

		Log::add('Customer details fetched: ' . json_encode($details), Log::INFO, 'TFA');
	
        if( $inline==true &&(is_null($details['license_type']) || empty($details['license_type']))){
			// Allow inline TFA for demo accounts
			$inline=TRUE;
			Log::add('Allowing inline TFA for demo account', Log::INFO, 'TFA');
		}
		
		Log::add('Active TFA methods after license check: ' . json_encode($active2FAMethods), Log::INFO, 'TFA');

        if(count($active2FAMethods)==0 && $enabled_tfa==TRUE){
			Log::add('No TFA method selected, aborting save process.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=login_settings',Text::_('COM_MINIORANGE_SETUP2FA_DISABLE_ONSITE'),'warning:');
			return;
        }
        if(count($active2FAMethods)==1 && $enable_change_2fa_method==true){
			Log::add('Attempting to enable method change with only one TFA method selected, aborting.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=login_settings','You cannot enable <strong>Allow users to change TFA method</strong> if only one TFA method is selected.','warning:');
			return;
        }
	
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('tfa_enabled') .' = '.$db->quote($enabled_tfa),
			$db->quoteName('tfa_enabled_type') .' = '.$db->quote($enable_2fa_user_type),
			$db->quoteName('tfa_halt') .' = '.$db->quote($inline),
            $db->quoteName('skip_tfa_for_users') . '=' . $db->quote($skip_tfa_for_users),
            $db->quoteName('enable_tfa_passwordless_login') . '=' . $db->quote($enable_otp_login),
			$db->quoteName('enable_change_2fa_method') . '=' . $db->quote($enable_change_2fa_method),
			$db->quoteName('remember_device') . '=' . $db->quote($enable_remember_device),
            $db->quoteName('enable_backup_method') . '=' . $db->quote($enable_2fa_backup_method),
			$db->quoteName('enable_backup_method_type') . '=' . $db->quote($enable_2fa_backup_type),
			$db->quoteName('tfa_kba_set1').' = '.$db->quote($KBA_set1),
			$db->quoteName('tfa_kba_set2').' = '.$db->quote($KBA_set2),
			$db->quoteName('activeMethods').' = '.$db->quote(json_encode($active2FAMethods)),
		);

		Log::add('Fields to be updated: ' . json_encode($fields), Log::INFO, 'TFA');
	
		
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_settings'))->set($fields)->where($conditions);
		Log::add('Query to be executed: ' . $query->__toString(), Log::INFO, 'TFA');
	
		$db->setQuery($query);
	
		$db->execute();
		$msg= Text::_('COM_MINIORANGE_LOGIN_SETTINGS_SAVES_SUCCESSFULLY');
        $msgType = 'success';
		Log::add('Redirecting after successful save.', Log::INFO, 'TFA');
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=login_settings',$msg,$msgType);
		return;
	} 

	public function saveTfaAdvanceSettings()
	{
		Log::add('Started process to save advance settings',Log::INFO,'TFA');
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		Log::add('Customer registered check: ' . ($isCustomerRegistered ? 'Yes' : 'No'),Log::INFO,'TFA');

		if(!$isCustomerRegistered){
			Log::add('Redirecting to account setup due to unregistered customer.', Log::ERROR, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}

		$app=Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];		$settings = commonUtilitiesTfa::getTfaSettings();
		$passwordlessEnabled = (isset($settings['enable_tfa_passwordless_login']) && $settings['enable_tfa_passwordless_login'] == 1);

			if ($passwordlessEnabled) {
				Log::add('Passwordless Login enabled - skipping Role-Based TFA update.', Log::INFO, 'TFA');
				$msg = Text::_('COM_MINIORANGE_ADV_BY_PASSWORDLESS_LOGIN');
				$msgType = 'warning';
				$tfa_enabled_for_roles = json_decode($settings['mo_tfa_for_roles'], true) ?: [];
			} else {
				$tfa_enabled_for_roles = [];
				$groups = commonUtilitiesTfa::loadGroups();
				foreach ($groups as $group) {
					$key = 'role_based_tfa_' . str_replace(' ', '_', $group['title']);
					if (!empty($post[$key]) && ($post[$key] == 1 || $post[$key] === 'on' || $post[$key] === true)) {
						$tfa_enabled_for_roles[] = $group['title'];
						Log::add('Enabling TFA for role: ' . $group['title'], Log::INFO, 'TFA');
					}
				}
			}	

		if(count($post)==0){
			Log::add('No post data received.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup');
            return;
        }

		$tfa_enabled_for_roles = array();
		$groups = commonUtilitiesTfa::loadGroups();
		Log::add('Loaded groups: ' . print_r($groups, true),Log::INFO, 'TFA');
		
		foreach ($groups as $key => $value) {
			if(array_key_exists('role_based_tfa_'.str_replace(' ', '_', $value['title']),$post))
			{
				$en = $post['role_based_tfa_'.str_replace(' ', '_', $value['title'])];
				Log::add("Checking 2FA for role: " . $value['title'] . " - " . ($en == 1 || $en == 'on' || $en == TRUE ? 'Enabled' : 'Disabled'),Log::INFO, 'TFA');
				if($en==1 || $en=='on' || $en=TRUE){
					array_push($tfa_enabled_for_roles,$value['title'] );
					Log::add('Enabling TFA for role: ' . $value['title'], Log::INFO, 'TFA');
				}
			}
		}
		if(count($tfa_enabled_for_roles)==0){
			Log::add('No roles selected for TFA, showing warning message.', Log::WARNING, 'TFA');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=advance_settings',Text::_('COM_MINIORANGE_SETUP2FA_ROLEBASED_MSG'),'warning:');
			return;
		}

        $validator = function ($var) {
            if($var == 1 || $var == TRUE || $var == 'on'){
                return TRUE;
            }
            return FALSE;
		};

		
        $ipWhiteList = isset($post['enableIpWhiteListing'])
            ? $validator($post['enableIpWhiteListing'])
            : 0;
        $ipBlackList    = isset($post['enableIpBlackListing'])
            ? $validator($post['enableIpBlackListing'])
            : 0;
		$redirectUrl    = isset($post['mo_tfa_user_after_login'])
            ? $post['mo_tfa_user_after_login']
            : '';
		$googleAppName  = isset($post['mo_tfa_google_app_name']) || empty($post['mo_tfa_google_app_name'])
            ? $post['mo_tfa_google_app_name']
            : 'miniOrangeAuth';
		$brandingName   = isset($post['branding_name']) && !empty($post['branding_name'])
            ? $post['branding_name']
            : 'login';
		
        $login_with_second_factor_only = isset($post['login_with_second_factor_only'])
            ? $validator($post['login_with_second_factor_only'])
            : 0;
		
		$enableTfaReg = isset($post['enableTfaRegistration'])
		? $validator($post['enableTfaRegistration'])
		: 0;
		$domainList = isset($post['enableTfaDomain'])
			? $validator($post['enableTfaDomain'])
			: 0;
		$enableEmail = isset($post['enableEmailFunctionality'])
		? $validator($post['enableEmailFunctionality'])
		: 0;

		$domainFieldValue = isset($post['mo_tfa_domain']) ? $post['mo_tfa_domain'] : '';

		// Clean domain field: remove spaces around semicolons
		$domainFieldValue = preg_replace('/\s*;\s*/', ';', $domainFieldValue);
		$domainFieldValue = trim($domainFieldValue);
		
		$settings['tfaDomainList'] = !empty($domainFieldValue) ? json_encode(explode(";", $domainFieldValue)) : '';
		
		list($validIps, $invalidIps) = isset($post['mo_tfa_whitelist_ips'])
            ? commonUtilitiesTfa::validateIpsInput($post['mo_tfa_whitelist_ips'])
            : commonUtilitiesTfa::validateIpsInput(array());

		list($blackListIPs, $invalidIps) = isset($post['mo_tfa_blacklist_ips'])
            ? commonUtilitiesTfa::validateIpsInput($post['mo_tfa_blacklist_ips'])
            : commonUtilitiesTfa::validateIpsInput(array());

		$details= commonUtilitiesTfa::getCustomerDetails();
        $inlineDisabled='';

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('mo_tfa_for_roles').' = '.$db->quote(json_encode($tfa_enabled_for_roles)),
            $db->quoteName('enableIpWhiteList').' = '.$db->quote($ipWhiteList),
            $db->quoteName('enableIpBlackList').' = '.$db->quote($ipBlackList),
            $db->quoteName('whiteListedIps').' = '.$db->quote(json_encode($validIps)),
			$db->quoteName('enableTfaRegistration').' = '.$db->quote($enableTfaReg),
			$db->quoteName('enableTfaDomain').' = '.$db->quote($domainList),
			$db->quoteName('tfaDomainList') . ' = ' . $db->quote($settings['tfaDomainList']),
			$db->quoteName('enableEmailFunctionality').' = '.$db->quote($enableEmail),
            $db->quoteName('blackListedIPs').' = '.$db->quote(json_encode($blackListIPs)),
            $db->quoteName('afterLoginRedirectUrl').' = '.$db->quote($redirectUrl),
            $db->quoteName('googleAuthAppName').' = '.$db->quote(urlencode($googleAppName)),
			$db->quoteName('branding_name').' = '.$db->quote($brandingName),
			$db->quoteName('login_with_second_factor_only').' = '.$db->quote($login_with_second_factor_only),
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_settings'))->set($fields)->where($conditions);
		$db->setQuery($query);
		Log::add('Database query for tfa: ' . $query->__toString(), Log::INFO, 'TFA');
		$db->execute();

		$app   = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
		$email_method = $input->get('email_method', '', 'STRING'); // No default value here
		$smtp_host = $input->get('smtp_host', '', 'string');
		$smtp_port = $input->get('smtp_port', '', 'int');
		$smtp_username = $input->get('smtp_username', '', 'string');
		$smtp_password = $input->get('smtp_password', '', 'string');
		$email_recipients = $input->getString('email_recipients', 'both'); // Default to 'both'
	
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
	
		$query->select('id')
			  ->from($db->quoteName('#__miniorange_email_settings'));
		$db->setQuery($query);
		$existingId = $db->loadResult();
	
		if ($existingId) {
			$query->clear()
				  ->update($db->quoteName('#__miniorange_email_settings'))
				  ->set($db->quoteName('email_method') . ' = ' . $db->quote($email_method))
				  ->set($db->quoteName('smtp_host') . ' = ' . $db->quote($smtp_host))
				  ->set($db->quoteName('smtp_port') . ' = ' . $db->quote($smtp_port))
				  ->set($db->quoteName('smtp_username') . ' = ' . $db->quote($smtp_username))
				  ->set($db->quoteName('smtp_password') . ' = ' . $db->quote($smtp_password))
				  ->set($db->quoteName('recipients') . ' = ' . $db->quote($email_recipients))
				  ->where($db->quoteName('id') . ' = ' . (int)$existingId);
		} else {
			$query->clear()
				  ->insert($db->quoteName('#__miniorange_email_settings'))
				  ->columns($db->quoteName(['email_method', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'recipients']))
				  ->values(implode(',', [
					  $db->quote($email_method),
					  $db->quote($smtp_host),
					  $db->quote($smtp_port),
					  $db->quote($smtp_username),
					  $db->quote($smtp_password),
					  $db->quote($email_recipients)
				  ]));
		}
	
		$db->setQuery($query);
		try {
			$db->execute();
			Log::add('Email settings saved successfully!', Log::INFO, 'tfa');
		} catch (Exception $e) {
			Log::add('Error saving email settings: ' . $e->getMessage(), Log::ERROR, 'tfa');
		}

		$msg= Text::_('COM_MINIORANGE_ADV_SETTINGS_SAVES_SUCCESSFULLY');
        $msgType = 'success';
		if(count($invalidIps)>0){
		    $msg=Text::_('COM_MINIORANGE_SETUP2FA_INVALID_IPS');
		    foreach ($invalidIps as $value)
		        $msg=$msg.'<li>'.$value.'</li>';
		    $msg=$msg.'</ul>';
		    $msgType = 'error';
			Log::add('Invalid IPs: ' . print_r($invalidIps, true), Log::ERROR, 'TFA');
        }
	
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=advance_settings',$msg,$msgType);
		Log::add('Redirectig with message type: ' . $msgType,   Log::INFO,'TFA');
		return;
	}

	public function configureKBADetails()
	{
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered){
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app  = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];		Log::add('Posted data for KBA: ' . json_encode($post), Log::INFO, 'tfa');
		$question1 = trim($post['mo_tfa_ques_1']);
		$answer1   = trim($post['mo_tfa_ans_1']);
		$question2 = trim($post['mo_tfa_ques_2']);
		$answer2   = trim($post['mo_tfa_ans_2']);
		$question3 = trim($post['mo_tfa_ques_3']);
		$answer3   = trim($post['mo_tfa_ans_3']);
		Log::add('KBA Questions and Answers: Q1: ' . $question1 . ' A1: ' . $answer1 . ', Q2: ' . $question2 . ' A2: ' . $answer2 . ', Q3: ' . $question3 . ' A3: ' . $answer3, Log::INFO, 'com_miniorange_twofa');
		if($question1==$question2 || $question1==$question3 || $question2==$question3){
			Log::add('Duplicate questions found. Redirecting with error message.', Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',Text::_('COM_MINIORANGE_SETUP2FA_KBA_MSG'),'error');
		    return;
		}
		else
		{
			Log::add('KBA details configured successfully. Redirecting with success message.', Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',Text::_('COM_MINIORANGE_SETUP2FA_KBA_MSG1'),'success');
		    return;
		}
	}

	public function testKBADetails(){
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered){
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app  = Factory::getApplication();
		$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
		Log::add('Posted data for KBA test: ' . json_encode($post), Log::INFO, 'tfa');
		$current_user = Factory::getUser(); 
		$user      = new miniOrangeUser();
		$row       = commonUtilitiesTfa::getCustomerDetails();
		$answers   = array();
		for ($i=1;$i<3;$i++) {
    		$temp_arr=array("question" =>$post['question'.$i],
                            "answer" => $post['answer'.$i],
                            );
    		array_push($answers, $temp_arr);
    	}
		Log::add('KBA Questions and Answers for validation: ' . json_encode($answers), Log::INFO, 'tfa');
    	$response=json_decode($user->validate($current_user->id,NULL,'KBA', $answers));
		Log::add('Response from KBA validation: ' . json_encode($response), Log::INFO, 'tfa');

    	if($response->status=='SUCCESS'){
			Log::add('KBA test passed successfully.', Log::INFO, 'tfa');
    		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',Text::_('COM_MINIORANGE_SETUP2FA_TEST_SUCCESS'));
    		return;
    	}
    	else{
    	    $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',$response->message,'error');
			Log::add('KBA test failed. Error message: ' . $response->message, Log::ERROR, 'tfa');
    		return;
    	}
	}

	public function setTfaMethod($tfaMethod){
		$c_time =date("Y-m-d",time());
		Log::add('Checking if customer is registered for setting TFA method.', Log::INFO, 'tfa');
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered)
		{
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app=Factory::getApplication();
		$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
		$current_user = Factory::getUser();
		$user = new miniOrangeUser();
		$details = commonUtilitiesTfa::getCustomerDetails();
		Log::add('User selected TFA method: ' . $tfaMethod, Log::INFO, 'tfa');
		$response = json_decode($user->mo2f_update_userinfo($details['email'],$tfaMethod));
		$tfaArray = Mo_tfa_utilities::tfaMethodArray();
		Log::add('Response from TFA method update: ' . json_encode($response), Log::INFO, 'tfa');
		if( $response->status=='SUCCESS'){ 
			commonUtilitiesTfa::updateOptionOfUser($current_user->id,'active_method',$tfaMethod);
			Log::add('TFA method set successfully for user: ' . $current_user->id, Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',$tfaArray[$tfaMethod]['name'].Text::_('COM_MINIORANGE_SETUP2FA_ACTIVE_METHOD'));
    		return;
		} 
		else{
			Log::add('Failed to set TFA method. Error message: ' . $response->message, Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor',$response->message,'error');
    		return;
		}
		
	}

	public function testTfaMethod(){
		$c_time =date("Y-m-d",time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered){
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$app=Factory::getApplication();
		$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
		$current_user = Factory::getUser();
		$tfaMethod=str_replace('mo_tfa_test', '', $post['tfaMethodToTest']);
		$user = new miniOrangeUser();
		Log::add('Testing TFA method: ' . $tfaMethod, Log::INFO, 'tfa');

		$authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL','KBA'=>'KBA','google'=>'GOOGLE AUTHENTICATOR','MA'=>'MICROSOFT AUTHENTICATOR','AA'=>'AUTHY AUTHENTICATOR','LPA'=>'LASTPASS AUTHENTICATOR');
		Log::add('Sending challenge request to validate TFA method: ' . json_encode($authCodes[$tfaMethod]), Log::INFO, 'tfa');
		$response = json_decode($user->challenge($current_user->id,$authCodes[$tfaMethod]));
		Log::add('Response from TFA method test: ' . json_encode($response), Log::INFO, 'tfa');
		if($response->status=='SUCCESS'){
			commonUtilitiesTfa::updateOptionOfUser($current_user->id,'transactionId',$response->txId);
			Log::add('TFA test successful for user: ' . $current_user->id . '. Transaction ID: ' . $response->txId, Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&test='.$tfaMethod);
    		return;

		}
		else{
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&configuring',$response->message,'error');
			Log::add('Failed to test TFA method. Error message: ' . $response->message, Log::ERROR, 'tfa');

    		return;
		}
	}
	public function resendOtpWhileTest(){
		$c_time =date("Y-m-d",time());
		Log::add('Checking if customer is registered for resending OTP while testing TFA method.', Log::INFO, 'tfa');
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if(!$isCustomerRegistered){
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
				return;
		}
		$user = new miniOrangeUser();
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$current_user = Factory::getUser();
		Log::add('Testing TFA method for OTP resend: ' . $post['testing'], Log::INFO, 'tfa');
		$user = new miniOrangeUser();
		$authCodes   = array('OOE'=>'EMAIL','OOS'=>'SMS','OOSE'=>'SMS AND EMAIL','KBA'=>'KBA');
		Log::add('Sending challenge request for OTP resend: ' . json_encode($authCodes[$post['testing']]), Log::INFO, 'tfa');
		$response=json_decode($user->challenge($current_user->id,$authCodes[$post['testing']]));
		Log::add('Response from OTP resend challenge: ' . json_encode($response), Log::INFO, 'tfa');
		if($response->status=='SUCCESS'){
			commonUtilitiesTfa::updateOptionOfUser($current_user->id,'transactionId',$response->txId);
			Log::add('OTP resent successfully for user: ' . $current_user->id . '. Transaction ID: ' . $response->txId, Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&test='.$post['testing'],Text::_('COM_MINIORANGE_SETUP2FA_OTP_RESEND'));
    		return;
		}
		else{
			Log::add('Failed to resend OTP. Error message: ' . $response->message, Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&test='.$post['testing'],$response->message,'error');
			return;
		}
	}

	public function testTfaMethodValidate() {
		$c_time = date("Y-m-d", time());
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		if (!$isCustomerRegistered) {
			Log::add("Customer is not registered at: " . $c_time, Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup', Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'), 'error');
			return;
		}
		$user = new miniOrangeUser();
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$current_user = Factory::getUser();
		Log::add("Starting OTP validation for user ID: " . $current_user->id . " at: " . $c_time, Log::INFO, 'tfa');
		$response = json_decode($user->validate($current_user->id, $post['Otp_token'], $post['testing']));
		if ($response->status == 'SUCCESS') {
			Log::add("OTP validation successful for user ID: " . $current_user->id, Log::INFO, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor', Text::_('COM_MINIORANGE_SETUP2FA_TEST_SUCCESS'));
		} else {
			Log::add("OTP validation failed for user ID: " . $current_user->id . ". Response: " . json_encode($response), Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&test=' . $post['testing'], $response->message, 'error');
		}
	}

	public function checkLicense() {
		$c_time = date("Y-m-d", time());
		$customer = new Mo_tfa_Customer();
		$details = commonUtilitiesTfa::getCustomerDetails();
		Log::add("Fetching customer license details at: " . $c_time, Log::INFO, 'tfa');
		$response = json_decode($customer->fetchLicense());
		$miniorange_lexp_notification_sent = isset($details['miniorange_lexp_notification_sent'])
			? $details['miniorange_lexp_notification_sent']
			: 0;
		Log::add("License fetch response: " . json_encode($response), Log::INFO, 'tfa');
		$licenseExpiryDate = strtotime($details['licenseExpiry']);
		$licenseExpiryFromServer = strtotime($response->licenseExpiry);
		Log::add("License expiry date (local): " . $licenseExpiryDate, Log::INFO, 'tfa');
		Log::add("License expiry date (server): " . $licenseExpiryFromServer, Log::INFO, 'tfa');

		if ($response->status == 'SUCCESS') {
			commonUtilitiesTfa::updateLicenseDetails($response, $details['email']);
	
			if ($licenseExpiryDate < $licenseExpiryFromServer) {
				if ($miniorange_lexp_notification_sent) {
					$db_table = '#__miniorange_tfa_customer_details';
					$db_columns = array(
						'miniorange_fifteen_days_before_lexp' => 0,
						'miniorange_five_days_before_lexp' => 0,
						'miniorange_after_lexp' => 0,
						'miniorange_after_five_days_lexp' => 0,
						'miniorange_lexp_notification_sent' => 0,
					);
					commonUtilitiesTfa::__genDBUpdate($db_table, $db_columns);
				}
			}
			Log::add("License updated successfully.", Log::INFO, 'tfa');
			$this->triggerNotificationCheck();
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup', Text::_('COM_MINIORANGE_SETUP2FA_LICENSE_UPDATE'), 'success');
		} else {
			Log::add("License update failed. Server response: " . json_encode($response), Log::ERROR, 'tfa');
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup', $response->message, 'error');
		}
	}
	
	private function triggerNotificationCheck() {
		$threshold_email = 10; 
		$threshold_sms = 10;  
		$details = commonUtilitiesTfa::getCustomerDetails();
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
					->select($db->quoteName(['emailRemaining', 'smsRemaining']))
					->from($db->quoteName('#__miniorange_tfa_customer_details'))
					->where($db->quoteName('id') . ' = 1');
	
		$db->setQuery($query);
		$counts = $db->loadAssoc();
	
		$config = Factory::getConfig(); 
		$adminEmail = $config->get('mailfrom');
	
		Log::add('Checking notification threshold...', Log::INFO, 'tfa');
	
		$transctionUrl = "<a href='" . Mo_tfa_utilities::getHostname() . "/moas/login?username=" . $details['email'] . "&redirectUrl=" . Mo_tfa_utilities::getHostname() . "/moas/viewtransactions' target='_blank'>" . Text::_("COM_MINIORANGE_VAL_CHECK") . "</a>";

		if ($counts['emailRemaining'] < $threshold_email || $counts['smsRemaining'] < $threshold_sms) {
			$subject = "Admin Notification: Low Email/SMS Remaining";
			$message = "Your email and SMS remaining counts are low.\n\n" .
					   "Email Remaining: {$counts['emailRemaining']}\n" .
					   "SMS Remaining: {$counts['smsRemaining']}\n\n" .
					   "To view your transactions, please click the following link:\n" . $transctionUrl;
				   		
			$this->smtp_mailer($adminEmail, $subject, $message, $adminEmail);
			Factory::getApplication()->enqueueMessage('Notification email sent to admin.', 'message');
			return true; 
		}
		return false; 
	}
	
	function smtp_mailer($to, $subject, $msg, $from)
    {
        Log::add('Debug: Inside smtp_mailer...', Log::INFO, 'smtp_mailer');
		Log::add('From: ' . $from, Log::INFO, 'smtp_mailer');
		Log::add('Recipient Email: ' . $to, Log::INFO, 'smtp_mailer');

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__miniorange_email_settings'))
                    ->setLimit(1);
        $db->setQuery($query);
        $smtp_settings = $db->loadObject(); 
    
		if ($smtp_settings) {
		Log::add('SMTP Settings Debug: ' . json_encode($smtp_settings), Log::INFO, 'smtp_settings');
		} else {
		Log::add('SMTP settings not found in the database.', Log::ERROR, 'smtp_settings');
		return 'Error: SMTP settings not found';
		}
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host = $smtp_settings->smtp_host; 
        $mail->Port = $smtp_settings->smtp_port; 
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Username = $smtp_settings->smtp_username; 
        $mail->Password = $smtp_settings->smtp_password; 
        $mail->SetFrom($from);
        $mail->Subject = $subject;
        $mail->Body = $msg;
        $mail->AddAddress($to);
    
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    
        $mail->SMTPDebug = 2; 
        $mail->Debugoutput = 'html'; 

		if (!$mail->Send()) {
			Log::add('PHPMailer Error: ' . $mail->ErrorInfo, Log::ERROR, 'email_sending');
			return $mail->ErrorInfo; 
		} else {
			Log::add('Email sent successfully!', Log::INFO, 'email_sending');
			return 'Sent'; 
		}

    }
	
    public function resetUser2FA()
    {
		Log::add('Initiated process to reset user.', Log::INFO, 'tfa');
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		
		$c_time =date("Y-m-d",time());
        $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        if(!$isCustomerRegistered){
			Log::add('Customer is not registered. Redirecting to account setup.', Log::ERROR, 'tfa');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_REGISTER'),'error');
            return;
        }
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];        $email = $post['reset_email'];
        $username =  $post['reset_username'];
        $id = UserHelper::getUserId($username);
		Log::add('Fetched user ID for ' . $username . ': ' . $id, Log::INFO, 'tfa');
        if($id != null)
        {
            $db = Factory::getDbo();
            $query = $db
                ->getQuery(true)
                ->select('email')
                ->from($db->quoteName('#__miniorange_tfa_users'))
                ->where($db->quoteName('id') . " = " . $id);
            $db->setQuery($query);
            $email = $db->loadResult();
			Log::add('Fetched email for user ' . $username . ': ' . $email, Log::INFO, 'tfa');
        }
        else{
			Log::add('User ' . $username . ' does not exist in the system.', Log::ERROR, 'tfa');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management',Text::_('COM_MINIORANGE_SETUP2FA_USER').$username.Text::_('COM_MINIORANGE_SETUP2FA_NOT_EXIST'), 'error');
            return;
        }
		$get_user= json_decode(commonUtilitiesTfa::get_user_on_server($email),true);
		if($get_user['status'] == 'SUCCESS')
		{
			Log::add('2FA reset successful for ' . $username . ' on the server. Deleting user from the server...', Log::INFO, 'tfa');
			// Delete the user from server if reset the 2FA successfully
			$response = json_decode(commonUtilitiesTfa::delete_user_from_server($email),true);
			if($response['status'] == 'SUCCESS')
			{
				Log::add('User ' . $username . ' successfully deleted from the server.', Log::INFO, 'tfa');
				// Delete the user from '#__miniorange_tfa_users' tables.
				commonUtilitiesTfa::delete_user_from_joomla_database($email);
				commonUtilitiesTfa::delete_rba_settings_from_database($id);
				Log::add('2FA reset for ' . $username . ' is successful in Joomla database.', Log::INFO, 'tfa');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management',Text::_('COM_MINIORANGE_SETUP2FA_METHOD').$username.Text::_('COM_MINIORANGE_SETUP2FA_RESET_SUCCESS'));
				return;
			}
			else
			{
				Log::add('Error deleting user ' . $username . ' from the server. Response: ' . print_r($response, true), Log::ERROR, 'tfa');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management',Text::_('COM_MINIORANGE_SETUP2FA_RESET_ERROR').$username.'</strong>.','error');
				return;
			}
		}
        else if($get_user['status'] == 'FAILED' && $get_user['message'] == 'Invalid username or email.')
		{
			Log::add('Invalid username or email for ' . $username . ' on the server.', Log::ERROR, 'tfa');
			$row = commonUtilitiesTfa::getMoTfaUserDetails($id);
			if(!empty($row))
			{
				commonUtilitiesTfa::delete_user_from_joomla_database($email);
				commonUtilitiesTfa::delete_rba_settings_from_database($id);
				Log::add('2FA reset successful for ' . $username . ' (user exists in Joomla database).', Log::INFO, 'tfa');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management','2FA reset for <strong>'.$username.'</strong> is successful.');
				return;
			}
	
			else
			{
				Log::add('Error resetting 2FA for ' . $username . '. User not found in Joomla database.', Log::ERROR, 'tfa');
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management','There occured some error resetting the 2FA for <strong>'.$username.'</strong>.','error');
				return;
			}
			
		}
		commonUtilitiesTfa::delete_user_from_joomla_database($email);
		commonUtilitiesTfa::delete_rba_settings_from_database($id);
		Log::add('2FA reset successful for ' . $username . ' (user exists in Joomla database).', Log::INFO, 'tfa');
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=user_management','2FA reset for <strong>'.$username.'</strong> is successful.');
		return;
    }

	public function removeAccount()
	{
		Log::add('Initiating remove account process.', Log::INFO, 'tfa');
		$c_time =date("Y-m-d",time());
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('email') . ' = '.$db->quote(""),
			$db->quoteName('password') . ' = '.$db->quote(""),
			$db->quoteName('admin_phone') . ' = '.$db->quote(""),
			$db->quoteName('customer_key') . ' = '.$db->quote(""),
			$db->quoteName('customer_token') . ' = '.$db->quote(""),
			$db->quoteName('api_key') . ' = '.$db->quote(""),
			$db->quoteName('app_secret') . ' = '.$db->quote(""),
			$db->quoteName('login_status') . ' = '.$db->quote(0),
			$db->quoteName('registration_status') . ' = '.$db->quote("not-started"),
			$db->quoteName('new_registration') . ' = '.$db->quote(0),
			$db->quoteName('transaction_id') . ' = '.$db->quote(""),
			$db->quoteName('license_type') . ' = '.$db->quote(""),
			$db->quoteName('license_plan').' ='.$db->quote(""),
			$db->quoteName('no_of_users').' ='.$db->quote(0),
			$db->quoteName('jid') . ' = '.$db->quote(0),
			$db->quoteName('smsRemaining') . ' = '.$db->quote(0),
			$db->quoteName('emailRemaining') . ' = '.$db->quote(0),
			$db->quoteName('supportExpiry') . ' = '.$db->quote('0000-00-00 00:00:00'),
			$db->quoteName('licenseExpiry') . ' = '.$db->quote('0000-00-00 00:00:00'),
			$db->quoteName('fid') . ' = '.$db->quote(0),
		);
		Log::add('Fields to be updated: ' . print_r($fields, true), Log::INFO, 'tfa');

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		$db->setQuery($query);
		if ($db->execute()) {
			Log::add('Customer account details have been reset successfully in the database.', Log::INFO, 'tfa');
		} else {
			Log::add('Failed to reset customer account details in the database.', Log::ERROR, 'tfa');
		}
		$db1 = Factory::getDbo();
		$query1 = $db1->getQuery(true);
		 // Fields to update.
		$query1->delete($db1->quoteName('#__miniorange_tfa_users'));
		Log::add('Executing deletion query on tfa_users table: ' . $query1->__toString(), Log::INFO, 'tfa');
		$db1->setQuery($query1);
		if ($db1->execute()) {
			Log::add('User records have been successfully removed from tfa_users table.', Log::INFO, 'tfa');
		} else {
			Log::add('Failed to remove user records from tfa_users table.', Log::ERROR, 'tfa');
		}		
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=account_setup',Text::_('COM_MINIORANGE_SETUP2FA_ACCOUNT_REMOVE'),'success');
	} 

	function updateCssConfig(){
		Log::add('updateCssConfig function started', Log::INFO, 'tfa');
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		Log::add('Received POST data: ' . json_encode($post), Log::INFO, 'tfa');

		if(count($post)==0){
			Log::add('No POST data received, redirecting', Log::INFO, 'tfa');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=popup_design');
            return;
        } 
		$Newcss ="";
		$radius= isset($post['radius'])?$post['radius']:8;
        $margin = (isset($post['margin'])&&$post['margin']!='0')?$post['margin']:5;
        $bgcolor =isset($post['bgcolor'])?$post['bgcolor']:"#FFFFFF";
        $bordertop = isset($post['bordertop'])?$post['bordertop']:"#20b2aa";
        $borderbottom = isset($post['borderbottom'])?$post['borderbottom']:"#20b2aa";
        $primarybtn = isset($post['primarybtn'])?$post['primarybtn']:"#fb9a9a";
        $height = isset($post['height'])?$post['height']:"200px";
		Log::add('CSS Parameters: radius=' . $radius . ', margin=' . $margin . ', bgcolor=' . $bgcolor . ', bordertop=' . $bordertop . ', borderbottom=' . $borderbottom . ', primarybtn=' . $primarybtn . ', height=' . $height, Log::INFO, 'tfa');
		$Newcss .="border-radius:".$radius."px;background-color:".$bgcolor.";border-top:".$margin."px "."solid ".$bordertop.";border-bottom:".$margin."px "."solid ".$borderbottom.";min-height:".$height."px;";
		
		Log::add('Generated CSS: ' . $Newcss, Log::INFO, 'tfa');

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.

		
		$fields = array(
			$db->quoteName('customFormCss').' = '.$db->quote($Newcss),
            $db->quoteName('primarybtnCss').' = '.$db->quote($primarybtn),
           
		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		
		$query->update($db->quoteName('#__miniorange_tfa_settings'))->set($fields)->where($conditions);
		Log::add('Executing query: ' . (string) $query, Log::INFO, 'tfa');
		$db->setQuery($query);
		if ($db->execute()) {
			Log::add('CSS configuration successfully updated in the database', Log::INFO, 'tfa');
		} else {
			Log::add('Failed to update CSS configuration in the database', Log::ERROR, 'tfa');
		}		
		$message =  Text::_('COM_MINIORANGE_SETUP2FA_CONFIG_MSG');
		Log::add($message, Log::INFO, 'tfa');
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=popup_design',$message ,'success');
	}

	public function joomlapagination()
    {
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        Log::add('Received POST data for pagination: ' . json_encode($post), Log::INFO, 'tfa');
        $total_entries = commonUtilitiesTfa::_get_all_login_attempts_count();
		Log::add('Total login attempts count: ' . $total_entries, Log::INFO, 'tfa');
        $start      = isset($post['page']) ? $post['page'] : '';
        $order      = isset($post['orderBY']) ? $post['orderBY'] : 'down';
        $no_of_entry= isset($post['no_of_entry']) ? $post['no_of_entry'] : 10;

        $first_val  = (int)$start * $no_of_entry;
        $first_val  = ($total_entries == 0) ? -1 : $first_val;
        $last_val   = $no_of_entry + $first_val;

        if($last_val >= $total_entries)
        {
            $last_val = $total_entries;
        }

        $low_id    = (int)$start * $no_of_entry;
        $upper_id  =  $no_of_entry;
        $first_val = $first_val + 1;
		Log::add('Pagination calculated: first_val = ' . $first_val . ', last_val = ' . $last_val, Log::INFO, 'tfa');
        $data = commonUtilitiesTfa::_get_login_transaction_reports();

        if($last_val == $total_entries)
        {
            echo '<script>
                document.getElementById("next_btn").style.display = "none";              
            </script>';
        }

        $list_of_login_trans = commonUtilitiesTfa::_get_login_attempts_count($low_id, $upper_id,$order);

        $icnt = count($list_of_login_trans[0]);
		$active2FA = commonUtilitiesTfa::getAllTfaMethods();

        $result = '';
        $result .= '<div class="mo_boot_row" ><div class="mo_boot_col-sm-12" style="overflow:auto;">
            <table class="mo_boot_table mo_otp_trans_table" id="Tfa_table">
            <thead class=" mo_boot_table-hover ">
                    <tr class="header">
                    <th>'. Text::_("COM_MINIORANGE_EMAIL_USERNAME").'</th>
                    <th>'. Text::_("COM_MINIORANGE_USER_EMAIL").'</th>
                    <th class="mo_boot_text-center">'. Text::_("COM_MINIORANGE_USER_PHONE").'</th>
                    <th class="mo_boot_text-center">'. Text::_("COM_MINIORANGE_USER_ROLE").'</th>
                    <th class="mo_boot_text-center">'. Text::_("COM_MINIORANGE_USER_METHOD").'</th>
                    <th class="mo_boot_text-center">'. Text::_("COM_MINIORANGE_USER_STATUS").'</th>
                    <th class="mo_boot_text-center">'. Text::_("COM_MINIORANGE_USER_ACTION").'</th>
                </tr>
            </thead>
                <tbody style="font-size: 13px;color:black;">';
        foreach ($list_of_login_trans as $list2)
        {
            foreach ($list2 as $key=>$list)
            {
                if (!empty($list['username']) && (!empty($list['email'])|| !empty($list['status_of_motfa'])))
                {
					$serialNum = (int)($key) + 1;
					Log::add('Rendering row for user: ' . $list['email'] . ' with status: ' . $list['status_of_motfa'], Log::INFO, 'tfa');
					$result .='<tr style="line-height: 25px;"><td>'.$list['jUsername'].'</td>'.'<td>'.$list['email'].'</td>'.'<td class="mo_boot_text-center">'.$list['phone'].'</td>';

                    $result .= '<td class="mo_boot_text-center" >'.$list['user_group'].'</td>';
					if($list['active_method'] != 'NONE' && !empty($list['active_method']))
					{
						$result .='<td class="mo_boot_text-center"><strong>'.$active2FA[$list['active_method']]['name'].'</strong></td>';
					}
					else
					{
						$result .='<td class="mo_boot_text-center"><strong>None</strong></td>';
					}

					if($list['status_of_motfa']=='active' || $list['status_of_motfa']=='five')
					{
						$result .= '<td class="mo_boot_text-center"><label class=" mo_btn-status mo_btn-tfa-enabled">'.Text::_("COM_MINIORANGE_USER_ACTIVE").'</label></td>';
					}
					else
					{
						$result .= '<td class="mo_boot_text-center"><label class="mo_btn-status mo_btn-tfa-disabled">'.Text::_("COM_MINIORANGE_USER_INACTIVE").'</label></td>';
					}
                    $result.='<td>
					<form class="mo_boot_text-center" id="form_user'.$serialNum.'" method="post" action="index.php?option=com_miniorange_twofa&task=setup_two_factor.resetUser2FA">
					<input type="button" name="reset_user" value="Reset" id="reset_user" onclick="getValue('.$serialNum.')" class="mo_boot_btn mo_boot_btn-dark" style="height: 30px;padding: 2px;width:66px;">
					<input type="hidden" id="reset_username'.$serialNum.'" name="reset_username" value="'.$list['jUsername'].'">
					<input type="hidden" id="reset_email'.$serialNum.'" name="reset_email" value="'.$list['email'].'">
					</form></td></tr>';

                }			
            }
	
        }

        $result .= '</tr>
                    </tbody>
                    </table>
                    </div></div></div><br>
                    <div class="mo_boot_col-sm-6" id="tfa_entries">Showing '.$first_val .' - '. $last_val .' of '. "<span id='total_entries'>$total_entries".'</span> entries</div>
					<script>
					
					jQuery(document).ready(function(){
                        var table = document.getElementById("Tfa_table");
                        var tbody = table ? table.getElementsByTagName("tbody")[0] : null;
                        var rowCount = Array.from(document.getElementById("Tfa_table").children[1].children).filter(child => child.style.display !== "none").length;
                        
                    });
					</script>';
        $entries = commonUtilitiesTfa::_get_all_login_attempts_count();
        if ($entries == 0){
            $result .= '<br><br><br><br><br><br><br><br>';
        }
        else if ($entries == 1){
            $result .= '<br><br><br><br><br><br>';
        }
        else if ($entries == 2){
            $result .= '<br><br><br><br>';
        }
        else if ($entries == 3){
            $result .= '<br><br><br>';
        }
        else if ($entries == 4){
            $result .= '<br>';
        }
        echo $result;
        exit;
	}

	public function exportConfiguration()
    {
        $tableNames = [
            '#__miniorange_tfa_customer_details',
            '#__miniorange_tfa_settings',
			'#__miniorange_tfa_users',
			'#__miniorange_rba_device',
			'#__miniorange_email_settings',
			'#__miniorange_otp_transactions_report',
			'#__miniorange_otp_custom_message',	
        ];
		Log::add('Exporting data for tables: ' . implode(', ', $tableNames), Log::INFO, 'tfa');
        // Include the helper file
        require_once JPATH_COMPONENT . '/helpers/Mo_tfa_utility.php';

        Mo_tfa_utilities::exportData($tableNames);
    }



	public function saveOTP()
	{
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();

		$otp_method = $post['login_otp_type'] ?? '';
		$enable_otp = $post['otp_during_registration'] ?? '';

		if (isset($enable_otp) && !empty($enable_otp) && $otp_method  == null) {
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_ACCOUNT_SELECT_VERIFICATION_METHOD'), 'warning');
			return;
		}

		$customer_details = commonUtilitiesTfa::getCustomerDetails(); 
		$reg_status = isset($customer_details['registration_status']) ? $customer_details['registration_status'] : 'FALSE';
		if($isCustomerRegistered){

		$tab = $post['login_otp_type'];
		$tab2 = $post['otp_during_registration'];
		$resend = $post['resend_count'];
		
        $enableTfaRegistration = isset($post['enableTfaRegistration']) && $post['enableTfaRegistration'] == 'on' ? 1 : 0;

		if (!isset($tab2)) $tab = 0;

		if (!isset($tab3)) $tab1 = 0;

		$tab_va = isset($tab) ? $tab : 0;
		$tab_val = isset($tab2) ? $tab2 : 0;

		$db_table = '#__miniorange_tfa_customer_details';
		$db_coloums = array(
			'registration_otp_type' => $tab_va,
			'enable_during_registration' => $tab_val,
			'resend_otp_count' => $resend,

		);

		$db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__miniorange_tfa_settings'))
            ->set($db->quoteName('enableTfaRegistration') . ' = ' . (int) $enableTfaRegistration);
        $db->setQuery($query)->execute();

		commonUtilitiesTfa::__genDBUpdate($db_table, $db_coloums);
		$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REG_SETTINGS_SAVES_SUCCESSFULLY'));
		}
		else
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
	}

	function saveDomainBlocks()
	{
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';
		
		if ($isCustomerRegistered) {
	
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
			
			// Get the allowed domains from the post
			$allow_domains = isset($post['mo_otp_allowed_email_domains']) ? $post['mo_otp_allowed_email_domains'] : '';
			$white_or_black = isset($post['white_or_black']) ? $post['white_or_black'] : 0;
			$reg_restriction = isset($post['reg_restriction']) ? $post['reg_restriction'] : 0;
	
			// Remove spaces around semicolons, leaving only semicolons to separate domains
			$allow_domains = preg_replace('/\s*;\s*/', ';', $allow_domains); // Removes spaces around semicolons
			$allow_domains = trim($allow_domains); // Remove any leading or trailing spaces
	
			// Prepare the database columns
			$db_table = '#__miniorange_tfa_customer_details';
			
			if ($reg_restriction == 1) {
				$db_coloums = array(
					'reg_restriction' => $reg_restriction,
					'white_or_black' => $white_or_black,
					'mo_otp_allowed_email_domains' => $allow_domains,
				);
			} else {
				$db_coloums = array(
					'reg_restriction' => 0,
					'white_or_black' => 0,
					'mo_otp_allowed_email_domains' => '',
				);
			}
	
			// Update the database
			commonUtilitiesTfa::__genDBUpdate($db_table, $db_coloums);
	
			// Redirect with a success message
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REG_SETTINGS_SAVES_SUCCESSFULLY'));
		} else {
			// Redirect with an error message if the user is not registered
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
		}
	}
	

	function saveCustomSettings()
    {

		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';


		if($isCustomerRegistered){

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $default_co_values = $post['default_country_code'] ?? '';
        if (empty($default_co_values) || $default_co_values == Text::_('COM_MINIORANGE_SELECT_COUNTRY_CODE_PLACEHOLDER')) {
            $default_co_code = 0; 
            $default_co_name = 'Not Selected'; 
        } else {
            $country_val = explode(',', $default_co_values);
            $default_co_code = isset($country_val[0]) && is_numeric($country_val[0]) ? (int) $country_val[0] : 0;
            $default_co_name = isset($country_val[1]) ? $country_val[1] : 'Not Selected';
        }

    
        $is_blocked = false;
        if (!empty($default_co_code) && $default_co_code != '') {
            $is_blocked = commonUtilitiesTfa::_is_country_code_blocked($default_co_code);
        }
      
        if ($is_blocked) {
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_ALREADY_BLOCKED_COUNTRY_CODE'), 'warning');
            return;
        }

        $db_table = '#__miniorange_tfa_customer_details';

        $db_coloums = array(
            'mo_default_country_code' => $default_co_code,
            'mo_default_country' => $default_co_name,
        );

        commonUtilitiesTfa::__genDBUpdate($db_table, $db_coloums);
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REG_SETTINGS_SAVES_SUCCESSFULLY'));
        }
        else
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
    }

	function block_country_codes()
	{
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
		
		$columnName = array('registration_status');
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';

		if ($isCustomerRegistered) {
			$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
			
			if (isset($post['mo_block_country_code'])) {
				$post['mo_block_country_code'] = str_replace(' ', '', $post['mo_block_country_code']);
			}

			$check = commonUtilitiesTfa::_is_default_selected($post);

			if ($check) {
				$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_COUNTRY_CODE_BLOCKING'), 'warning');
				return;
			}

			commonUtilitiesTfa::_block_country_code($post);
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REG_SETTINGS_SAVES_SUCCESSFULLY'));
		}
		else {
			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=register_settings', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
		}
	}


	function saveCustomMessage()
    {
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $columnName = array('registration_status');
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';

        if ($isCustomerRegistered) {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        commonUtilitiesTfa::_save_custom_message($post);
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_SETTINGS_SAVES_SUCCESSFULLY'));
        }
        else
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
    }

	function saveCustomPhoneMessage()
    {
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $columnName = array('registration_status');
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';

        if ($isCustomerRegistered) {

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        commonUtilitiesTfa::_save_custom_phone_message($post);
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_SETTINGS_SAVES_SUCCESSFULLY'));
        }
        else
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
    }

	function saveComOTPMessages()
    {
		$isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $columnName = array('registration_status');
		$result = commonUtilitiesTfa::getCustomerDetails();
		$reg_status = isset($result['registration_status']) ? $result['registration_status'] : 'FALSE';

        if ($isCustomerRegistered) {

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        commonUtilitiesTfa::_save_com_message($post);
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_SETTINGS_SAVES_SUCCESSFULLY'));
        }
        else
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=customise_options', Text::_('COM_MINIORANGE_REGISTER_OR_LOGIN_ERROR'), 'error');
    }

	function otp_reports(){
		
		Log::add("otp_reports () started", Log::DEBUG, 'TFA');
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $login_report = commonUtilitiesTfa::_get_all_otp_transaction_count();
        $login_report_count = commonUtilitiesTfa::_get_otp_transaction_reports_val();

        $refresh = $post['refresh_page'] ?? '';
        if ($refresh == Text::_('COM_MINIORANGE_REFRESH_BUTTON'))
        {        Log::add("refresh_page started", Log::DEBUG, 'TFA');

			$this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=otp_report',Text::_('COM_MINIORANGE_REFRESH_MSG'));
            return;
        }

        $download = $post['download_reports'] ?? '';
        if ($download == Text::_('COM_MINIORANGE_DOWNLOAD_BUTTON') && $login_report_count['verification_method'] != '' && $login_report != 0) {
			Log::add("download_reports started", Log::DEBUG, 'TFA');

            commonUtilitiesTfa::_download_reports();
        }
        else if ($download == Text::_('COM_MINIORANGE_DOWNLOAD_BUTTON')){

            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=otp_report', Text::_('COM_MINIORANGE_DOWNLOAD_ERROR_MSG'),'error');
            return;
        }

        $clear_reports = $post['clear_val'] ?? '';
		Log::add("clear_reports Value: " . $clear_reports, Log::DEBUG, 'TFA');

if (!isset($login_report_count['verification_method'])) {
    Log::add("login_report_count['verification_method'] is not set", Log::WARNING, 'TFA');
}

if (!isset($login_report)) {
    Log::add("login_report is not set", Log::WARNING, 'TFA');
}
        if ($login_report_count['verification_method'] != '' && $login_report != 0) {
            if ($clear_reports == Text::_('COM_MINIORANGE_CLEAR_BUTTON')) {
				Log::add("clear_reports started", Log::DEBUG, 'TFA');

                $db = Factory::getDbo();
                $db->truncateTable('#__miniorange_otp_transactions_report');
                $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=otp_report', Text::_('COM_MINIORANGE_CLEAR_SUCCESS_MSG'),'success');
                return;
            } else {
                $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=otp_report', Text::_('COM_MINIORANGE_CLEAR_ERROR_MSG'), 'warning');
                return;
            }
        }
        else {
            if ($clear_reports == Text::_('COM_MINIORANGE_CLEAR_BUTTON')){
                $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=otp_report', Text::_('COM_MINIORANGE_CLEAR_WARNING_MSG'), 'error');
                return;
            }
        }
    }
  function joomlapagination_otp() {
        Log::addLogger(array('text_file' => 'joomla_debug.log'), Log::ALL, array('TFA'));
    
        Log::add("Function joomlapagination() started", Log::DEBUG, 'TFA');
    
        $total_entries = commonUtilitiesTfa::_get_all_otp_transaction_count();
        Log::add("Total OTP transactions: $total_entries", Log::DEBUG, 'TFA');
    
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        Log::add("POST Data: " . json_encode($post), Log::DEBUG, 'TFA');
    
        $is_customer_registered = commonUtilitiesTfa::isCustomerRegistered();
        Log::add("Customer Registered Status: " . ($is_customer_registered ? "Yes" : "No"), Log::DEBUG, 'TFA');
    
   
        $start = $post['page'] ?? 0;
        $order = $post['orderBY'] ?? 'down';
        $no_of_entry = $post['no_of_entry'] ?? 10;
        
        Log::add("Pagination - Start: $start, Order: $order, Entries per page: $no_of_entry", Log::DEBUG, 'TFA');
    
        $low_id = $start * $no_of_entry;
        $first_val = $low_id + 1;
        $last_val = min($low_id + $no_of_entry, $total_entries);
        
        if ($total_entries == 0) {
            $first_val = 0;
            $last_val = 0;
        }
    
        Log::add("Pagination calculated - First Val: $first_val, Last Val: $last_val, Low ID: $low_id", Log::DEBUG, 'TFA');
    
        if ($last_val == $total_entries) {
            echo '<script>document.getElementById("next_btn").style.display = "none";</script>';
        }
    
        $list_of_otp_trnas = commonUtilitiesTfa::_get_otp_transaction_report($no_of_entry, $low_id, $order);
        Log::add("OTP Transactions Retrieved: " . json_encode($list_of_otp_trnas), Log::DEBUG, 'TFA');
    
        if (empty($list_of_otp_trnas)) {
            Log::add("No transactions retrieved", Log::WARNING, 'TFA');
        }
    
        $result = '<div class="table-responsive" id="mo_otp_transaction_table">
                    <table id="myTable" class=" mo_boot_table  mo_otp_trans_table">
                    <thead>
                        <tr class="header">
                            <th>Verification Method</th>
                            <th>User Email</th>
                            <th>User Phone</th>
                            <th>OTP Sent</th>
                            <th>OTP Verified</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>';
    
        foreach ($list_of_otp_trnas as $list2) {
            Log::add("Processing list2: " . json_encode($list2), Log::DEBUG, 'TFA');
    
            foreach ($list2 as $list) {
                Log::add("Processing list item: " . json_encode($list), Log::DEBUG, 'TFA');
    
                if (empty($list['user_phone'])) {
                    $list['user_phone'] = '-';
                }
    
                if ($is_customer_registered) {
                    Log::add("Appending row: " . json_encode($list), Log::DEBUG, 'TFA');
    
                    $result .= '<tr>
                                    <td>' . $list['verification_method'] . '</td>
                                    <td>' . $list['user_email'] . '</td>
                                    <td>' . $list['user_phone'] . '</td>
                                    <td>' . $list['otp_sent'] . '</td>
                                    <td>' . $list['otp_verified'] . '</td>
                                    <td>' . date("M j, Y, g:i:s a", $list['timestamp']) . '</td>
                                </tr>';		
                } else {
                    Log::add("User not registered, truncating table.", Log::WARNING, 'TFA');
    
                    $db = Factory::getDbo();
                    $db->truncateTable('#__miniorange_otp_transactions_report');
                    $first_val = 0;
                    $last_val = 0;
                    $total_entries = 0;
                }
            }
        }
    
        $result .= '</tbody></table></div><br>
                    <div>' . Text::sprintf('COM_SHOWING_NO_OF_ENTRIES', $first_val, $last_val, $total_entries) . '</div>';
    
        Log::add("Final HTML output: " . substr($result, 0, 500) . "...", Log::DEBUG, 'TFA');
    
        echo $result;
        exit;
    }


	public function importConfiguration()
	{
		$app = Factory::getApplication();
		if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
			$uploadedFile = $_FILES['file']['tmp_name'];
			$this->processImportFile($uploadedFile);
		} else {
			$app->enqueueMessage('File upload failed or no file was selected.', 'error');
			$this->setRedirect('index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=exportConfiguration',false);
		}

	}

	public function processImportFile($uploadedFile)
	{
		$jsonContent = file_get_contents($uploadedFile);

		$jsonData = json_decode($jsonContent, true);
	
		if (json_last_error() !== JSON_ERROR_NONE) {
			echo json_encode(['error' => 'Invalid JSON file.']);
			Factory::getApplication()->close();
		}
	
		foreach ($jsonData as $tableName => $data) {
			// Skip the table if it contains the message "This table is empty."
			if (isset($data['message']) && $data['message'] === 'This table is empty.') {
				continue;  // Skip this table
			}
	
			// Proceed with importing data for the non-empty tables
			$this->importTableData($tableName, $data);
		}
	
		$this->setRedirect('index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=exportConfiguration', Text::_('COM_MINIORANGE_DATA_IMPORTED_SUCCESSFULLY'), 'message');
	}

	public function importTableData($tableName, $data)
	{
		$db = Factory::getDbo();
		$integerFields = ['email_count', 'sms_count', 'registration_otp_type','login_otp_type','enable_during_registration'];

		foreach ($data as $row) {
			if (!is_array($row)) {
				echo json_encode(['error' => 'Invalid data format for row in table ' . $tableName]);
				Factory::getApplication()->close();
			}

			$columns = array_keys($row);
			$values = [];

			foreach ($columns as $column) {
				$value = $row[$column];

				// If it's an integer field and empty, default to 0
				if (in_array($column, $integerFields)) {
					if ($value === '' || !is_numeric($value)) {
						$value = 0;
					}
					$values[] = (int)$value; // Unquoted integer
				} else {
					// Quote other fields
					if ($value === '') {
						$values[] = $db->quote(''); // empty string
					} else {
						$values[] = $db->quote($value);
					}
				}
			}

			$insert = $db->getQuery(true)
						->insert($db->quoteName($tableName))
						->columns($db->quoteName($columns))
						->values(implode(',', $values));

			$updates = [];
			foreach ($columns as $column) {
				if ($column === 'id') continue;
				$updates[] = $db->quoteName($column) . ' = VALUES(' . $db->quoteName($column) . ')';
			}

			$query = $insert . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);

			$db->setQuery($query);

			try {
				$db->execute();
			} catch (Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
				Factory::getApplication()->close();
			}
		}
	}

    public function requestTrial()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        Log::add('Trial/Demo request received. Data: ' . json_encode($post), Log::INFO, 'TFA');

        // Check if required fields are present
        if (empty($post['email']) || empty($post['description']) || empty($post['request_type'])) {
            Log::add('Missing required fields in trial/demo request', Log::ERROR, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_FILL_DETAILS'), 'error');
            return;
        }

        $obj = new Mo_tfa_Customer();
        $response = $obj->submit_trial_request(
            $post['request_type'],           // demo or trial
            $post['email'],                  // email
            '',                              // mobile number (empty as not collected)
            'Joomla Two Factor Authentication', // plan
            $post['description']             // description
        );

        Log::add('Trial/Demo request submission response: ' . $response, Log::INFO, 'TFA');
        $response = json_decode($response, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            if (is_array($response) && array_key_exists('status', $response) && $response['status'] == 'ERROR') {
                Log::add('Trial/Demo request submission failed: ' . $response['message'], Log::ERROR, 'TFA');
                $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', $response['message'], 'error');
                return;
            }
        }

        if ($response == false) {
            Log::add('Trial/Demo request submission failed', Log::ERROR, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG'), 'error');
            return;
        }

        Log::add('Trial/Demo request submitted successfully for email: ' . $post['email'], Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&tab-panel=support', Text::_('COM_MINIORANGE_SETUP2FA_QUERY_MSG1'));
        return;
    }

}

