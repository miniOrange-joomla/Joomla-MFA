<?php
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die;
jimport('miniorangetfa.utility.commonUtilitiesTfa');
jimport('miniorangetfa.utility.miniOrangeUser');
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
Use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Input\Input;
Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);

class miniorange_twofaControllerminiorange_inline extends FormController
{
    public function skipTwoFactor()
    {
        $session = Factory::getSession();
        $info    = $session->get('motfa');
        $current_user=$info['inline']['whoStarted'];
        Log::add('Processing skipping two factor for user ID: ' . $current_user->id, Log::INFO, 'TFA');
        $details=commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
        $activeMethod = is_null($details)?'NONE':$details['active_method'];
        $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
        if ( $activeMethod != "NONE") {
            Log::add('Deactivating active 2FA method for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id,'active_method', 'NONE');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'inactive');
            $this->performLogin(true);
        } else {
            Log::add('No active 2FA method detected for user ID: ' . $current_user->id . '. Proceeding with default setup.', Log::INFO, 'TFA');
            commonUtilitiesTfa::insertOptionOfUser($current_user->username,$current_user->id, 'NONE', 'active', $current_user->email, $current_user->email );
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'inactive');
            $this->performLogin(true);
        }
    }
 
    function testing()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $email = $post['miniorange_registered_email'];
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = $info['inline']['whoStarted'];
        // Log the start of the function and email being processed
        Log::add('Testing initiated for email: ' . $email, Log::INFO, 'TFA');
        Log::add('Processing user ID: ' . $current_user->id, Log::INFO, 'TFA');

        $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
         
        if (is_array($row) && isset($row['id']) && $row['id'] == $current_user->id) {
            Log::add('Updating existing user details for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateMoTfaUser($current_user->id, $email, $email, '');
        } else {
            Log::add('Inserting new user details for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::insertMoTfaUser($current_user->username,$current_user->id, $email, $email, '');
        }
         $user = new miniOrangeUser();
                $response = json_decode($user->challenge($current_user->id, 'OOE', true));

                if ($response->status == 'SUCCESS') 
                {
                    Log::add('Updating transaction ID and status for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $response->txId);
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'one');
                    $mo_2fa_user_details = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
                    $session->set('steps', 'two');
                    $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_OTP_SUCCESS_MSG') . $email . Text::_('COM_MINIORANGE_MSG_ENTER_OTP'));
                    $session->set('mo_tfa_message_type', 'mo2f-message-status');
                    Log::add('Redirecting user ID: ' . $current_user->id . ' to OTP page.', Log::INFO, 'TFA');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                    return;
                } else {
                    Log::add('Challenge failed for user ID: ' . $current_user->id . ' - Message: ' . $response->message, Log::ERROR, 'TFA');
                    $session->set('mo_tfa_message', $response->message);
                    $session->set('mo_tfa_message_type', 'mo2f-message-error');
                    Log::add('Redirecting user ID: ' . $current_user->id . ' to retry OTP page.', Log::INFO, 'TFA');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                    return;
                }
    }

    public function pageTwoSubmit(){
		$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
		$user = new miniOrangeUser();
		$session=Factory::getSession();
		$info=$session->get('motfa');
		$current_user=$info['inline']['whoStarted'];
        Log::add('Starting page two submit for user ID ' . $current_user->id, Log::INFO, 'TFA');
		$row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
        
		$response= $user->validate($current_user->id,$post['Passcode'],NULL,NULL,true);
		$response=json_decode($response);
        Log::add('Passcode validation response: ' . json_encode($response), Log::INFO, 'TFA');
        
		if($response->status=='SUCCESS') 
        {
            $customer_details = commonUtilitiesTfa::getCustomerDetails();
            $email = $current_user->email;
            Log::add('Comparing customer email: ' . $customer_details['email'] . ' with current user email: ' . $email, Log::INFO, 'TFA');
            if($customer_details['email']!=$email)
            {
                $user_create_response = json_decode($user->mo_create_user($current_user->id,$current_user->name));
                Log::add('User creation response: ' . json_encode($user_create_response), Log::INFO, 'TFA');
            }
            
            if($user_create_response->status=='SUCCESS'){
				$mo_2fa_user_details=commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
			    $user_tfamethod_update_reponse=json_decode($user->mo2f_update_userinfo($mo_2fa_user_details['email'],'OOE'));
                Log::add('TFA method update response: ' . json_encode($user_tfamethod_update_reponse), Log::INFO, 'TFA');

                if($user_tfamethod_update_reponse->status=='SUCCESS'){
                    Log::add('TFA method update successful for user ID ' . $current_user->id, Log::INFO, 'TFA');
                    $session->set('steps','three');
                    $session->set('mo_tfa_message','Your 2FA account is created successfully. Please complete the setup.');
                    $session->set('mo_tfa_message_type','mo2f-message-status');
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id,'status_of_motfa','three');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                    return;
                }
			    else{
                    $session->set('steps','three');
                    $session->set('mo_tfa_message',$user_tfamethod_update_reponse->message);
                    $session->set('mo_tfa_message_type','mo2f-message-error');
                    Log::add('TFA method update failed: ' . $user_tfamethod_update_reponse->message, Log::ERROR, 'TFA');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                    return;
                }
			}

		}
		else
        {
            Log::add('Passcode validation failed: ' . $response->message, Log::ERROR, 'TFA');
			$session->set('mo_tfa_message',$response->message);
            $session->set('mo_tfa_message_type','mo2f-message-error');
			$this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
			return;
		}
	} 

    public function thirdStepSubmit()
    {        
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
        $method = $post['miniorangetfa_method'];
        Log::add('Redirect to next step after selecting method: ' . $method . ', For user ID: '. ($current_user->id ?? 'Unknown') , Log::INFO, 'TFA');
        Log::add('Session Data: ' . print_r($info, true), Log::INFO, 'TFA');
        $session = Factory::getSession();
        $session->set('selected_2fa_method', $method);
        Log::add('Method selected: ' . $method, Log::INFO, '2fa_method');
        $saved_method = $session->get('selected_2fa_method');
        Log::add('Method saved in session: ' . $saved_method, Log::INFO, '2fa_method');
        $get_response=commonUtilitiesTfa::get_user_on_server($current_user->email);

        if(empty($post))
        {
            Log::add('No post data received, redirecting to the main TFA page.', Log::WARNING, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
        $tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
        $enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
        $backup_method_type = isset($tfaSettings['enable_backup_method_type']) ? $tfaSettings['enable_backup_method_type'] : '';
        $change2FAMethod = isset($tfaSettings['enable_change_2fa_method']) && $tfaSettings['enable_change_2fa_method'] == 1;
        $isChange2FAEnabled = $session->get('change2FAEnabled');

            $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
            $email = $current_user->email;
            $username = $current_user->username;
            
            if ((!is_array($row)) || (!isset($row['id'])) || ($row['id'] != $current_user->id)) 
            {
                commonUtilitiesTfa::insertMoTfaUser($username,$current_user->id, $email, $email, '');
            }

            if($isChange2FAEnabled && $change2FAMethod)
            {
                if(isset($method) && !empty($method))
                {
                    Log::add('2FA method change enabled. Updating method for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                    $info['stepThreeMethod']=$post['miniorangetfa_method'];
                    $session->set('motfa',$info);
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'backup_method', $backup_method_type);
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'active_method', $method);
                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'three');
                    commonUtilitiesTfa::delete_rba_settings_from_database($current_user->id);
                    if($method=='OOE'){

                            if(!commonUtilitiesTfa::isValidUid($current_user->id)){
                                Log::add('Invalid user ID during 2FA method update: ' . $current_user->id, Log::ERROR, 'TFA');
                                $session->set('steps','invalid');
                            }
                            else
                            {                    
                                $user = new miniOrangeUser();
                                $response = json_decode($user->challenge($current_user->id, 'OOE', true));
                                if ($response->status == 'SUCCESS'){
                                    Log::add('Updating transaction ID and status for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $response->txId);
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'three');
                                    $mo_2fa_user_details = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
                                    json_decode($user->mo2f_update_userinfo($mo_2fa_user_details['email'], 'OOE'));
                                    $email=commonUtilitiesTfa::_getMaskedEmail($email);
                                    Log::add('Redirecting to OTP validation for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                                    $session->set('steps','validateEmail');
                                    $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_OTP_SUCCESS_MSG') . $hiddenEmail . Text::_('COM_MINIORANGE_MSG_ENTER_OTP'));
                                    $session->set('mo_tfa_message_type', 'mo2f-message-status');
                                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                                    return;
                                }
                            }
                    }
                    else
                    {
                        $session->set('mo_tfa_message','');
                        $session->set('steps','four');
                        Log::add('Redirecting to step four for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                        return;	
                    }	 
                }
            }
            else
            { 
                $response=commonUtilitiesTfa::delete_user_from_server($current_user->email);
                $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);

                $customer_details = commonUtilitiesTfa::getCustomerDetails();
                $email = $current_user->email;
                
                if($customer_details['email']!=$email)
                {
                    $user_create_response = json_decode($user->mo_create_user($current_user->id,$current_user->name));
                    
                    if(strtolower($user_create_response->status)=='error' && $user_create_response->message=='An error occurred. Please contact miniOrange administrator.')
                    {
                        commonUtilitiesTfa::delete_user_from_joomla_database($email);
                        $session->set('steps','three');
                        $session->set('mo_tfa_message',$user_create_response->message);
                        $session->set('mo_tfa_message_type','mo2f-message-error');
                        Log::add('Failed to create user for email: ' . $email . '. Error: ' . $user_create_response->message, Log::ERROR, 'TFA');
                        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                        return;
                    }
                }
                
                $mo_2fa_user_details=commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
                Log::add('Preparing to send API request to update 2FA method for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                Log::add('User Email: ' . $mo_2fa_user_details['email'], Log::INFO, 'TFA');
                Log::add('API Request Payload: ' . json_encode($mo_2fa_user_details), Log::INFO, 'TFA');
                $user_tfamethod_update_reponse=json_decode($user->mo2f_update_userinfo($mo_2fa_user_details['email'],'OOE'));
                Log::add('API Response: ' . json_encode($user_tfamethod_update_reponse), Log::INFO, 'TFA');
                Log::add('Updating TFA method for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                if($user_tfamethod_update_reponse->status=='SUCCESS')
                {
                    Log::add('user tfa method update response successful ', Log::INFO, 'TFA');

                    if(isset($method) && !empty($method))
                    {
                        $info['stepThreeMethod']=$post['miniorangetfa_method'];
                        $session->set('motfa',$info);
                        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'backup_method', $backup_method_type);
                        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'active_method', $method);
                        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'three');
                        Log::add('TFA method updated successfully for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                        if($method=='OOE')
                        {
                            if(!commonUtilitiesTfa::isValidUid($current_user->id)){
                                Log::add('Invalid UID for user ID: ' . $current_user->id, Log::ERROR, 'TFA');
                                $session->set('steps','invalid');
                            }
                            else
                            {                   
                                $user = new miniOrangeUser();
                                $response = json_decode($user->challenge($current_user->id, 'OOE', true));
                                
                                if ($response->status == 'SUCCESS'){
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $response->txId);
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'three');
                                    $mo_2fa_user_details = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
                                    if (empty($mo_2fa_user_details)) {
                                        Log::add('User details not found for ID: ' . $current_user->id, Log::ERROR, 'TFA');
                                    }
                                    json_decode($user->mo2f_update_userinfo($mo_2fa_user_details['email'], 'OOE'));
                                    Log::add('Updated user info on server for email: ' . $mo_2fa_user_details['email'], Log::INFO, 'TFA');
                                    $email=commonUtilitiesTfa::_getMaskedEmail($email);
                                    $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
                                    $email_notify = !empty($result['mo_custom_email_success_message'])? $result['mo_custom_email_success_message']: Text::_('PLG_USER_CONSTANT_OTP_SENT_EMAIL');
                                    $msg = str_replace('##email##', $email, $email_notify);
                                    $session->set('steps','validateEmail');
                                    $session->set('mo_tfa_message', $msg);
                                    $session->set('mo_tfa_message_type', 'mo2f-message-status');
                                    Log::add('Initiated the user to validate their email address by entering the OTP sent to them.', Log::INFO, 'TFA');
                                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                                    return;
                                }
                            }
                        }
                        else
                        {
                            $session->set('steps','four');
                            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                            return;	
                        }	
                    }
                }
                else
                {
                    Log::add('user tfa method update response not successful ', Log::INFO, 'TFA');
                    $session->set('steps','three');
                    $session->set('mo_tfa_message',$user_tfamethod_update_reponse->message);
                    $session->set('mo_tfa_message_type','mo2f-message-error');
                    $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                    return;
                }
               
            }   
        
    }

    function pageFourAndHAlf()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
        $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
        $method = $info['stepThreeMethod'];
        $phone = $post['phone'];
        Log::add('Method: ' . $method, Log::INFO, 'TFA');    
        commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'phone', $phone);
        $tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
        $enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
        Log::add('2FA Settings: ' . json_encode($tfaSettings), Log::INFO, 'TFA');

        $mo_user = new miniOrangeUser();
   
         
        $send_otp_response = json_decode($mo_user->challenge($current_user_id, $method, true));
        Log::add('OTP Response: ' . json_encode($send_otp_response), Log::INFO, 'TFA');

        if ($send_otp_response->status == 'SUCCESS') {
           $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
            $success_msg= !empty($result['mo_custom_phone_success_message'])? $result['mo_custom_phone_success_message']: Text::_('PLG_USER_CONSTANT_OTP_SENT_PHONE');
            $msg = str_replace('##phone##', $phone, $success_msg);
            commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'transactionId', $send_otp_response->txId);
            $session->set('mo_tfa_message',$msg);
            commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'transactionId', $send_otp_response->txId);
            $session->set('mo_tfa_message',$msg);
            $session->set('mo_tfa_message_type', 'mo2f-message-status');
            Log::add('TFA OTP sent successfully to user ID: ' . $current_user_id, Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        
        } else {
            Log::add('Error sending OTP for user ID: ' . $current_user_id . ' - ' . $send_otp_response->message, Log::ERROR, 'TFA');
            $session->set('mo_tfa_message', $send_otp_response->message);
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }

    } 

    public function pageFourValidatePasscode()
    {
        
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser(); 
        $session = Factory::getSession();
        $info = $session->get('motfa'); 
        $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
        $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
        $method = $info['stepThreeMethod'];
        $email = $current_user->email;
        Log::add('Validating passcode for user ID: ' . $current_user_id . ' with method: ' . $method, Log::INFO, 'TFA');

        $tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
        $enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
        $backup_method = isset($tfaSettings['enable_backup_method_type']) ? $tfaSettings['enable_backup_method_type'] : "";
        Log::add('Passcode validation Backup Method: ' . $backup_method, Log::INFO, 'TFA');
    
        if ($method == 'google' || $method == 'MA' || $method == 'AA' || $method == 'LPA' || $method == 'DUO') {
            
            $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
            $response = json_decode($user->validateGoogleToken($row['username'], $row['transactionId'], trim($post['Passcode']), $method));
        } 
        elseif($method == 'YK')
        {
            $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
            $response = $user->validateGoogleToken($row['username'], $row['transactionId'], $post['mo_auth_token_textfield'], $method);
            $response = json_decode($response);
        }
        else {
            $response = $user->validate($current_user_id, trim($post['Passcode']), $method, NULL, true);
            $response = json_decode($response);
        }
        
        Log::add('Passcode validation Response: ' . json_encode($response), Log::INFO, 'TFA');

        if ($response->status == 'SUCCESS') {
            Log::add('Passcode validation successful for user ID: ' . $current_user_id . ' using method: ' . $method, Log::INFO, 'TFA');
            if($enable_backup_method==1){

                if ($method != 'google' && $method != 'MA' && $method != 'AA' && $method != 'LPA' && $method != 'DUO') {
                    $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
                    $user->mo2f_update_userinfo($row['email'], $method, '');
                }
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'status_of_motfa', 'five');
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'active_method', $method);
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'backup_method', $backup_method);
                Log::add('TFA method updated successfully for user ID: ' . $current_user_id, Log::INFO, 'TFA');
                $session->set('mo_tfa_message', '');
                $session->set('steps', 'five'); 
                $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&step=five');
                return;
            } 
            else{
                if ($method != 'google' && $method != 'MA' && $method != 'AA' && $method != 'LPA' && $method != 'DUO') {
                    $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
                    $user->mo2f_update_userinfo($row['email'], $method,'');
                }
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'active_method', $method);
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'status_of_motfa', 'active');
                commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'backup_method', $backup_method);
                Log::add('TFA method updated successfully for user ID: ' . $current_user_id, Log::INFO, 'TFA');
                $session->set('mo_tfa_message', '');
                $this->performLogin(true);
            }
        } 
        else {
            Log::add('Error validating passcode for user ID: ' . $current_user_id . ' - ' . $response->message, Log::ERROR, 'TFA');
            $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
            $msg = !empty($result['mo_custom_invalid_otp_message'])? $result['mo_custom_invalid_otp_message']: Text::_('PLG_USER_CONSTANT_COMMON_MSG');
            $session->set('mo_tfa_message',  $msg );
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
    }

    // public function stepFiveSubmit()
    // {
    //     $app = Factory::getApplication();
    //     $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
    //     $post = ($input && $input->post) ? $input->post->getArray() : [];
    //     $session = Factory::getSession();
    //     $question1 = $post['mo_tfa_ques_1'];
    //     $answer1 = $post['mo_tfa_ans_1'];
    //     $question2 = $post['mo_tfa_ques_2'];
    //     $answer2 = $post['mo_tfa_ans_2'];
    //     $question3 = $post['mo_tfa_ques_3'];
    //     $answer3 = $post['mo_tfa_ans_3'];
    //     Log::add('Received KBA questions and answers for submission.', Log::INFO, 'TFA');
    //     if ($question1 == $question2 || $question1 == $question3 || $question2 == $question3) {
    //         Log::add('KBA questions are not unique, rejecting submission.', Log::WARNING, 'TFA');
    //         $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_KBA'));
    //         $session->set('mo_tfa_message_type', 'mo2f-message-error');
    //         $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
    //         return;
    //     }
    //     $user = new miniOrangeUser();

    //     $info = $session->get('motfa');
    //     $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
    //     $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
    //     Log::add('Fetching user details for ID: ' . $current_user_id, Log::INFO, 'TFA');
    //     $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
    //     $user = new miniOrangeUser();
    //     Log::add('Registering KBA details for user email: ' . $row['email'], Log::INFO, 'TFA');
    //     $kba_response = json_decode($user->register_kba_details($row['email'], $question1, $answer1, $question2, $answer2, $question3, $answer3));
    
    //     if ($kba_response->status == 'SUCCESS') {
    //         Log::add('KBA registration successful for user ID: ' . $current_user_id, Log::INFO, 'TFA');
    //         commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'status_of_motfa', 'active');
    //         $this->performLogin(true);
    //     } else {
    //         Log::add('KBA registration failed for user ID: ' . $current_user_id . '. Message: ' . $kba_response->message, Log::ERROR, 'TFA');
    //         $this->performLogin(true);
    //     }
    // }
    public function stepFiveSubmit()
{
    $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
    $session = Factory::getSession();
    $question1 = trim($post['mo_tfa_ques_1']);
    $answer1 = trim(strtolower($post['mo_tfa_ans_1']));
    $question2 = trim($post['mo_tfa_ques_2']);
    $answer2 = trim(strtolower($post['mo_tfa_ans_2']));
    $question3 = trim($post['mo_tfa_ques_3']);
    $answer3 = trim(strtolower($post['mo_tfa_ans_3']));

    Log::add('Received KBA questions and answers for submission.', Log::INFO, 'TFA');
    
    // Check for unique questions
    if ($question1 == $question2 || $question1 == $question3 || $question2 == $question3) {
        Log::add('KBA questions are not unique, rejecting submission.', Log::WARNING, 'TFA');
        $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_KBA'));
        $session->set('mo_tfa_message_type', 'mo2f-message-error');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    $user = new miniOrangeUser();
    $info = $session->get('motfa');
    $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
    $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
    
    Log::add('Fetching user details for ID: ' . $current_user_id, Log::INFO, 'TFA');
    $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
    
    Log::add('Registering KBA details for user email: ' . $row['email'], Log::INFO, 'TFA');
    $kba_response = json_decode($user->register_kba_details($row['email'], $question1, $answer1, $question2, $answer2, $question3, $answer3));

    if ($kba_response->status == 'SUCCESS') {
        Log::add('KBA registration successful for user ID: ' . $current_user_id, Log::INFO, 'TFA');
        commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'status_of_motfa', 'active');
        $this->performLogin(true);
    } else {
        Log::add('KBA registration failed for user ID: ' . $current_user_id . '. Message: ' . $kba_response->message, Log::ERROR, 'TFA');
        $session->set('mo_tfa_message', $kba_response->message);
        $session->set('mo_tfa_message_type', 'mo2f-message-error');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }
}


    public function stepFiveSubmitForBackupCode()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
        $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
        $backup_codes = isset($post['backup_codes_values']) ? $post['backup_codes_values'] : '';
        $encoded_backup_codes = base64_encode($backup_codes);
        $tfaSettings   = commonUtilitiesTfa::getTfaSettings();
        $backup_method_type = isset($tfaSettings['enable_backup_method_type'])?$tfaSettings['enable_backup_method_type'] : '';
        Log::add('Received backup codes for user ID ' . $current_user_id, Log::INFO, 'TFA');
        Log::add('Backup method type set to: ' . $backup_method_type, Log::INFO, 'TFA');
        commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'mo_backup_codes', $encoded_backup_codes);
        commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'status_of_motfa', 'active');
        commonUtilitiesTfa::updateOptionOfUser($current_user_id, 'backup_method', $backup_method_type);
        Log::add('Backup codes stored for user ID ' . $current_user_id, Log::INFO, 'TFA');
        $this->performLogin(true);
        Log::add('User successfully logged in after submitting backup code', Log::INFO, 'TFA');

    }

    public function handleBackOfInline()
    {
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
        $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
        if(!empty($row))
        {
            Log::add('User tfa status reset for user ID ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', '');
        }
        
        if ($session->get('started_at_admin') == 'yes') {
            Log::add('Redirecting back to admin panel', Log::INFO, 'TFA');
            $this->setRedirect(Uri::base() . 'administrator/index.php');
        } else {
            Log::add('Redirecting back to the front-end', Log::INFO, 'TFA');
            $this->setRedirect('index.php');

        }
        return;
    }

    public function backValidateBackup(){
        $session = Factory::getSession();
        $session->set('steps', 'active');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        Log::add('Redirecting to active step after validating backup', Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function gotoPreviousStep(){
        $session = Factory::getSession();
        $session->set('steps', 'three');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function navigateToBack()
    {
        $session = Factory::getSession();
        $session->set('steps', 'invokeOOE');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        Log::add('Navigating back to invoke OOE step', Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleBackOfInlineTwo()
    {
        $session = Factory::getSession();
        $session->set('steps', 'one');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        Log::add('Redirecting back to step one of inline process', Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleBackOfInlineThree()
    {
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = $info['inline']['whoStarted'];
        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'one');
        $session->set('steps', 'one');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        Log::add('Updated TFA status for user to "one" and redirecting to step one', Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleBackOnChange2FA(){
        $session = Factory::getSession();
        $session->set('steps', 'active');
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        Log::add('Redirecting to active step for change 2FA process', Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleBackOfInlineFour()
    {
        $session = Factory::getSession();
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        $session->set('steps', 'three');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleBackOfInlineFive()
    {
        $session = Factory::getSession();
        $tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
        $isChange2FAEnabled = $session->get('change2FAEnabled');
        $change2FAMethod = isset($tfaSettings['enable_change_2fa_method']) && $tfaSettings['enable_change_2fa_method'] == 1;

        if($isChange2FAEnabled && $change2FAMethod) {
            $session->set('steps', 'invokeOOE');
            Log::add('Redirecting to invoke OOE step because change 2FA is enabled', Log::INFO, 'TFA');
        }
        else{
            $session->set('steps', 'four');
            Log::add('Redirecting back to step four as change 2FA is not enabled', Log::INFO, 'TFA');
        }
        $session->set('mo_tfa_message', '');
        $session->set('mo_tfa_message_type', '');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function validateTfaChallange()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $userId = $session->get('current_user_id');
        $challenge_response = $session->get('challenge_response');
        Log::add('Starting TFA validation process for user ID: ' . $userId, Log::INFO, 'TFA');
        $response = $user->validate($userId, $post['passcode'], $challenge_response->authType, NULL);
        $response = json_decode($response);
        $username = $post['username'];
        $attributes="";

        
        $reconfigure2FA = isset($post['reconfigure_2fa']) ? $post['reconfigure_2fa'] : '';
        $remember_device = isset($post['remember_device']) ? $post['remember_device'] : '';
        
        if (isset($post['miniorange_rba_attributes'])) {
            $attributes = $post['miniorange_rba_attributes'];
          }
    
        if($reconfigure2FA == 'on'){
            Log::add('Reconfigure 2FA flag detected for user ID: ' . $userId, Log::INFO, 'TFA');
            if ($response->status == 'SUCCESS') {
                $session = Factory::getSession();
                $session->set('steps', 'three');
                $moTfa=array('inline'=>array('whoStarted'=>Factory::getUser($userId),'status'=>'attempted'));
                $session->set('motfa',$moTfa);
                $session->set('mo_tfa_message','');
                $session->set('change2FAEnabled', 'TRUE');
                Log::add('Redirecting to TFA dashboard for user ID: ' . $userId, Log::INFO, 'TFA');
                $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                return;
            } else {
                Log::add('Reconfigure 2FA failed for user ID: ' . $userId . ' - Error: ' . $response->message, Log::ERROR, 'TFA');
               $this->showInvalidMessage($response);
            }
        }
    
        elseif($remember_device == 'on'){
            Log::add('Enabled remeber device for user ID: ' . $userId, Log::INFO, 'TFA');
            if($response->status == 'SUCCESS'){
                Log::add('Device validation successful for user ID: ' . $userId, Log::INFO, 'TFA');
                $db = Factory::getDbo();
                $query = $db->getQuery(true);

                $columns = array('user_id', 'mo_rba_device');
                
                $values = array($db->quote($userId), $db->quote($attributes));
                Log::add('Updating device information in the database for user ID: ' . $userId, Log::INFO, 'TFA');
                $query
                ->insert($db->quoteName('#__miniorange_rba_device'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));
                
                $db->setQuery($query);
                $db->execute();
                Log::add('Device information successfully stored in the database for user ID: ' . $userId, Log::INFO, 'TFA');
                $cookie_value    =  self::getRandomString(8);
                $cookie_name = "rba";
                Log::add('Setting RBA cookie for user ID: ' . $userId, Log::INFO, 'TFA');
                setcookie($cookie_name, $cookie_value, time() + (86400 * 1), "/");
                Log::add('Performing login for user ID: ' . $userId, Log::INFO, 'TFA');
                $this->performLogin();
            }
            else {
                Log::add('Remember device validation failed for user ID: ' . $userId . ' - Error: ' . $response->message, Log::ERROR, 'TFA');
                $this->showInvalidMessage($response);
            }
        }
        else{
            Log::add('Processing standard TFA validation for user ID: ' . $userId, Log::INFO, 'TFA');
            if ($response->status == 'SUCCESS') {
                Log::add('Standard TFA validation successful. Logging in user ID: ' . $userId, Log::INFO, 'TFA');
                $this->performLogin();
            } else {
                Log::add('Standard TFA validation failed for user ID: ' . $userId . ' - Error: ' . $response->message, Log::ERROR, 'TFA');
                $this->showInvalidMessage($response);
            }
        }
    }

    function getRandomString($n) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        Log::add('Generated random string: ' . $randomString, Log::INFO, 'TFA');

        return $randomString;
    }

    public function showInvalidMessage($response){
        $session = Factory::getSession();
        $session->set('mo_tfa_message', $response->message);
        $session->set('mo_tfa_message_type', 'mo2f-message-error');
        Log::add('Displaying invalid message for response: ' . json_encode($response), Log::INFO, 'TFA');
        $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
        return;
    }

    public function handleForgotForm()
    {
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $userId = $session->get('current_user_id');
        Log::add('Handling forgot form for user ID: ' . $userId, Log::INFO, 'TFA');
        $get_user_details = commonUtilitiesTfa::getMoTfaUserDetails($userId);
        if($get_user_details['backup_method']=='backupCodes')
        {
            Log::add('Backup method is backupCodes for user ID: ' . $userId, Log::INFO, 'TFA');
            $session->set('steps', 'backup');
            $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG'));
            $session->set('mo_tfa_message_type', 'mo2f-message-status');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
        else if($get_user_details['backup_method']=='securityQuestion')
        
        {
            $response = json_decode($user->challenge($userId, 'KBA'));
            
            if ($response->status == 'SUCCESS') {
                Log::add('Security question challenge successful for user ID: ' . $userId, Log::INFO, 'TFA');
                $session->set('kba_response', $response);
                commonUtilitiesTfa::updateOptionOfUser($userId, 'transactionId', $response->txId);
                $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_KBA1'));
                $session->set('mo_tfa_message_type', 'mo2f-message-status');
                $session->set('steps', 'KBA');
                $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                return;
            } 
            else {
                $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_KBA2'));
                $session->set('mo_tfa_message_type', 'mo2f-message-error');
                Log::add('Security question challenge failed for user ID: ' . $userId, Log::WARNING, 'TFA');
                $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                return;
            }
        }
        
    }

    public function validateBackupCodes()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];

        $session = Factory::getSession();
        $current_userid = $session->get('current_userid');
        Log::add('Starting backup code validation for user ID: ' . $current_userid, Log::INFO, 'TFA');
        $entered_backup_code_value = trim(isset($post['backup_code_value']) ? $post['backup_code_value'] : '');
        Log::add('Backup code entered: ' . $entered_backup_code_value, Log::DEBUG, 'TFA');
        $get_user_details = commonUtilitiesTfa::getMoTfaUserDetails($current_userid);
        $stored_backup_codes = isset($get_user_details['mo_backup_codes']) ? $get_user_details['mo_backup_codes'] : '';
        $stored_backup_codes = base64_decode($stored_backup_codes);
        $stored_backup_codes = explode(',', $stored_backup_codes);

        if(in_array($entered_backup_code_value, $stored_backup_codes)){
            Log::add('Backup code matched for user ID: ' . $current_userid, Log::INFO, 'TFA');

            foreach (array_keys($stored_backup_codes, $entered_backup_code_value) as $key) {
                unset($stored_backup_codes[$key]);
            }

            $stored_backup_codes = implode(',', $stored_backup_codes);
            $altered_backup_codes = base64_encode($stored_backup_codes);
            commonUtilitiesTfa::updateOptionOfUser($current_userid, 'mo_backup_codes', $altered_backup_codes);
            Log::add('Backup codes updated for user ID: ' . $current_userid, Log::INFO, 'TFA');
            self::performLogin();
        }
        else{
            Log::add('Invalid backup code entered for user ID: ' . $current_userid, Log::WARNING, 'TFA');
            $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_INVALID_CODE'));
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            $this->setRedirect(Uri::root().'index.php?option=com_miniorange_twofa&view=miniorange_twoFA&taskbcode=generatebackupcode');
        }

    }

        public function SubmitKBAForm()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $userId = $session->get('current_user_id');
        $challengeInfo = $session->get('kba_response');

        if (empty($challengeInfo) || empty($challengeInfo->questions)) {
            Log::add('KBA challenge info is missing or invalid.', Log::ERROR, 'TFA');
            $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_INVALID_KBA_CHALLENGE'));
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }

        $questions = $challengeInfo->questions;
        $answers = array();

        foreach ($questions as $key => $value) {
            $answer_key = 'answer' . $key;
            if (isset($post[$answer_key])) {
                $temp_arr = array(
                    "question" => trim($value->question),
                    "answer" => trim(strtolower($post[$answer_key])),
                );
                array_push($answers, $temp_arr);
            } else {
                Log::add('Answer key missing for question ' . $key, Log::ERROR, 'TFA');
                $session->set('mo_tfa_message', Text::_('COM_MINIORANGE_MSG_MISSING_ANSWERS'));
                $session->set('mo_tfa_message_type', 'mo2f-message-error');
                $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
                return;
            }
        }

        Log::add('Collected KBA answers for validation', Log::INFO, 'TFA');
        $response = json_decode($user->validate($userId, NULL, 'KBA', $answers));

        if ($response->status == 'SUCCESS') {
            Log::add('KBA validation successful for user ID: ' . $userId, Log::INFO, 'TFA');
            $this->performLogin();
        } else {
            Log::add('KBA validation failed for user ID: ' . $userId . '. Message: ' . $response->message, Log::ERROR, 'TFA');
            $session->set('mo_tfa_message', $response->message);
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
    }


    public function performLogin($inline = false)
    {
        $session = Factory::getSession();
        Log::add('Started to perform login. Inline mode: ' . ($inline ? 'Yes' : 'No'), Log::INFO, 'TFA');

        // Check if this is a registration flow TFA setup
        $flowType = $session->get('flow_type');
        $tfaRegistrationInProgress = $session->get('tfa_registration_in_progress');
        
        if ($flowType === 'registration' && $tfaRegistrationInProgress === true) {
            Log::add('Registration flow detected with enableTfaRegistration enabled. Clearing session for user ID: ' . ($inline ? 'inline user' : $session->get('current_user_id')), Log::INFO, 'TFA');
            
            // Clear registration-related session variables
            $session->clear('flow_type');
            $session->clear('tfa_registration_in_progress');
            $session->clear('motfa');
            $session->clear('steps');
            $session->clear('mo_tfa_message');
            $session->clear('mo_tfa_message_type');
            $session->clear('current_user_id');
            $session->clear('current_userid');
            $session->clear('tfa_verified');
            
            // Redirect to normal Joomla registration completion page
            $this->setRedirect('index.php/component/users/registration?Itemid=101', Text::_('COM_MINIORANGE_MSG_TFA_SETUP_COMPLETE'));
            return;
        }

        if ($inline) {
            $info = $session->get('motfa');
            $userId = isset($info['inline']['whoStarted']->id) && !empty($info['inline']['whoStarted']->id) ? $info['inline']['whoStarted']->id : '';
            if(empty($userId) || $userId == '') {
                $userId = $session->get('juserId');
            }
        } else {
            $userId = $session->get('current_user_id');
        }
        $current_user = Factory::getUser($userId);
        $session->set('tfa_verified', 'yes');

        $app = Factory::getApplication();
        $isroot = $current_user->authorise('core.login.admin');

        $isChange2FAEnabled = $session->get('change2FAEnabled');
        
        if ($isroot && $session->get('started_at_admin') == 'yes') {
            Log::add('Admin login detected for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            $session->clear('tfa_verified');
            $session->clear('steps');
            $session->clear('motfa');
            $session->clear('current_user_id');
            $session->clear('challenge_response');
            $session->clear('mo_tfa_message');
            $session->clear('mo_tfa_message_type');
            $session->clear('kba_response');
            $salt = bin2hex(random_bytes(random_int(10, 50)));
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $salt);
            Log::add('Generated transaction ID: ' . $salt, Log::INFO, 'TFA');
            $this->setRedirect(Uri::base() . 'administrator/index.php?tfa_login=' . $userId . '&user=' . $salt);
            return;
        } else {
            
            PluginHelper::importPlugin('user');
            $options = array();
            $options['action'] = 'core.login.site';
            $response = new stdClass();
            $response->username = $current_user->username;
            $response->language = '';
            $response->email = $current_user->email;
            $response->password_clear = '';
            $response->fullname = '';
            Log::add('Logging in user: ' . $response->username, Log::INFO, 'TFA');
            $result = $app->triggerEvent('onUserLogin', array((array)$response, $options));

            if ($isChange2FAEnabled) {
                Log::add('2FA reset enabled for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                $this->setRedirect('index.php',"TFA reset successful");
    
            }
            else if(in_array(false, $result)) {
                Log::add('Login event failed for user ID: ' . $current_user->id, Log::ERROR, 'TFA');
                $app->logout($current_user->id);
                $this->setRedirect('index.php');
                return;
            }

            $is_idp_installed = commonUtilitiesTfa::isIdPInstalled();

            if(isset($is_idp_installed['enabled']) && $is_idp_installed['enabled'] == 1)
            {
                Log::add('IdP detected. Handling request for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                setcookie('2faInvokedSuccessfully', TRUE, time() + 3600, '/');
                $saml_request = isset($_COOKIE['SAMLRequest']) ? $_COOKIE['SAMLRequest'] : '';
                if(!empty($saml_request)){
                    Log::add('Redirecting to SAML request URL: ' . $saml_request, Log::INFO, 'TFA');
                    $this->setRedirect($saml_request);
                    setcookie('SAMLRequest', '', time() - 3600, '/'); 
                    return;
                }
            }

            $redirectUrl = commonUtilitiesTfa::getTfaSettings()['afterLoginRedirectUrl'];
            $redirectUrl = empty($redirectUrl) ? 'index.php' : $redirectUrl;
            Log::add('Redirecting user ID: ' . $current_user->id . ' to URL: ' . $redirectUrl, Log::INFO, 'TFA');
            $this->setRedirect($redirectUrl, Text::_('COM_MINIORANGE_MSG_SUCCESS'));
            return;
        }
    }

    public function validateEmail()
    {

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $email = $post['miniorange_registered_email'];
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $session->set('ooe_for_change_2fa', 'TRUE');
        $current_user = $info['inline']['whoStarted'];
        Log::add('Starting email validation process for user ID: ' . $current_user->id, Log::INFO, 'TFA');
        Log::add('Received email for validation: ' . $email, Log::INFO, 'TFA');

        $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
        if (is_array($row) && isset($row['id']) && $row['id'] == $current_user->id) 
        {
            Log::add('User already exists in TFA database. Updating email for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateMoTfaUser($current_user->id, $email, $email, '');
        } 
        else 
        {        
            Log::add('User not found in TFA database. Inserting new record for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::insertMoTfaUser($current_user->$username,$current_user->id, $email, $email, '');
        }

        $user = new miniOrangeUser();

        $response = json_decode($user->challenge($current_user->id, 'OOE', true));
        if ($response->status == 'SUCCESS') {
            Log::add('OOE challenge initiated successfully for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            Log::add('Transaction ID: ' . $response->txId, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $response->txId);
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'one');
            $mo_2fa_user_details = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
            json_decode($user->mo2f_update_userinfo($mo_2fa_user_details['email'], 'OOE'));
            Log::add('Redirecting user ID: ' . $current_user->id . ' to the TFA dashboard after successful validation.', Log::INFO, 'TFA');
            $session->set('steps', 'validateEmail');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        } else {
            $session->set('mo_tfa_message', $response->message);
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            Log::add('Redirecting user ID: ' . $current_user->id . ' to the TFA dashboard due to validation failure.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
    }

    public function validateOOE()
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $user = new miniOrangeUser();
        $session = Factory::getSession();
        $info = $session->get('motfa');
        $current_user = $info['inline']['whoStarted'];
        $tfaSettings = commonUtilitiesTfa::getTfaSettings();
        $backup_method_type = isset($tfaSettings['enable_backup_method_type'])?$tfaSettings['enable_backup_method_type'] : '';
        $enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
        Log::add('Starting OOE validation process for user ID: ' . $current_user->id, Log::INFO, 'TFA');
        $response = $user->validate($current_user->id, $post['Passcode'], NULL, NULL, true);
        $response = json_decode($response);
    
        if ($response->status == 'SUCCESS') {
            Log::add('OOE validation successful for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            if($enable_backup_method==1){
            Log::add('Backup method is enabled. Updating user settings for user ID: ' . $current_user->id, Log::INFO, 'TFA');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'five');
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'backup_method', $backup_method_type);
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'active_method', 'OOE');
            Log::add('Redirecting user ID: ' . $current_user->id . ' to the TFA dashboard after updating settings.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            $session->set('steps', 'five');
            return;
            }
            else{
                Log::add('Backup method is disabled. Setting OOE as active method for user ID: ' . $current_user->id, Log::INFO, 'TFA');
                commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'active_method', 'OOE');
                commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'active');
                Log::add('Logging in user ID: ' . $current_user->id . ' after successful OOE validation.', Log::INFO, 'TFA');    
                $this->performLogin(true);
                return;
            }
        }
        else {
            $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
            $msg = !empty($result['mo_custom_invalid_otp_message'])? $result['mo_custom_invalid_otp_message']: Text::_('PLG_USER_CONSTANT_COMMON_MSG');
            $session->set('mo_tfa_message', $msg);
            $session->set('mo_tfa_message_type', 'mo2f-message-error');
            Log::add('Redirecting user ID: ' . $current_user->id . ' to the TFA dashboard due to validation failure.', Log::INFO, 'TFA');
            $this->setRedirect('index.php?option=com_miniorange_twofa&view=miniorange_twoFA');
            return;
        }
    }
}

