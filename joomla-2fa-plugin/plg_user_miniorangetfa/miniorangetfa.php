<?php
/**
 * @package     Joomla.User	
 * @subpackage  plg_user_miniorangetfa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.helper');
defined('_JEXEC') or die('Restricted access');
jimport('miniorangetfa.utility.commonUtilitiesTfa');
jimport('miniorangetfa.utility.miniOrangeUser');
require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_twofa/helpers/Mo_tfa_utility.php';
require_once 'common-elements.php';
require_once 'messages.php';
require_once 'miniorange_logic_interface.php';
require_once 'miniorange_form_handler.php';
require_once 'miniorange_email_logic.php';
require_once 'miniorange_phone_logic.php';
require_once 'miniorange_email_or_phone_logic.php';
require_once 'constants.php';
require_once 'moutility.php';
require_once 'curl.php';  
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;

Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);
/**
 * miniOrange 2FA Plugin plugin
 */
class PlgUserMiniorangetfa extends CMSPlugin
{
    /*2FA verification or Inline registration During Login */

    public function onUserAfterSave($user, $isnew, $success, $msg): void
    {

        $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
        $user_id=$user['id'];
        $updated_email    = $user['email'];
        $details_of_motfa = commonUtilitiesTfa::getMoTfaUserDetails($user_id);

        if($isCustomerRegistered && !empty($details_of_motfa) && !empty($details_of_motfa['email']) && $updated_email !==  $details_of_motfa['email'])
        {
            $user = new miniOrangeUser();
            $response = json_decode($user->updateEmail($details_of_motfa['email'],$updated_email));    

            if( $response->status=='SUCCESS'){ 
                commonUtilitiesTfa::updateOptionOfUser($user_id,'email',$updated_email);
                commonUtilitiesTfa::updateOptionOfUser($user_id,'username',$updated_email);
            }
        }
        if ($isnew && $success && !$app->isClient('administrator')) 
        {
            Log::add('Initiating 2FA setup after registration for user ID: ' . $user_id, Log::INFO, 'tfa');
            $this->triggerTwoFaSetup($user['id']);
        }
                 
    }

    public function onUserBeforeSave($oldUser, $isnew, $newuser)
    {
        Log::add('user before save function  ', 'tfa');

        Log::add('Fetching customer details before processing.', 'tfa');

        $customer_details = commonUtilitiesTfa::getCustomerDetails();
        $registration_otp_type      = isset($customer_details['registration_otp_type']) ? $customer_details['registration_otp_type'] : 0;
        $enable_during_registration = isset($customer_details['enable_during_registration']) ? $customer_details['enable_during_registration'] : 0;
        $default_country_code       = isset($customer_details['mo_default_country_code']) ? $customer_details['mo_default_country_code'] : 0;
        
        Log::add('Customer Details - Registration OTP Type: ' . $registration_otp_type . ', Enable During Registration: ' . $enable_during_registration . ', Default Country Code: ' . $default_country_code, 'tfa');
        
        // If OTP is not enabled during registration, exit the function
        if ($enable_during_registration != '1') {
            Log::add('OTP verification is disabled during registration. Exiting function.', Log::INFO, 'tfa');
            return;
        }

        // Continue with OTP verification process
         Log::add('Proceeding with OTP verification during user registration.', Log::INFO, 'tfa');

        if (commonUtilitiesTfa::isCustomerRegistered()) {
            Log::add('Customer is registered.', Log::INFO, 'tfa');

            $errors = NULL;
            if ($this->checkIfVerificationIsComplete()) return $errors;
            $phone_number = NULL;
            foreach ($newuser as $key => $value) {
                if ($key == "username")
                    $username = $value;
                elseif ($key == "email1") 
                    $email = $value;
                elseif ($key == "password1")
                    $password = $value;
                elseif ($key == "profile")
                    $phone_number = $value['phone'];
                else
                    $extra_data[$key] = $value;
            }

            $app   = Factory::getApplication();
            $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
            $tab   = $input->get('task', '', 'CMD');
            
            if (isset($tab) && $tab == 'registration.register') {
                $phone_number = str_replace(" ", "", $phone_number);
                $phno = strlen($phone_number);
                $phbr = substr($phone_number, 0, 1);

                if ($phbr != '+') {
                    if (!empty($default_country_code)) {
                        $phone_number = '+'.$default_country_code.$phone_number;
                        $phbr = '+';
                    }
                }

                if ($phone_number != '') {
                    if ($phno <= 4 || $phno >= 18 || $phbr != '+') {
                        $result= commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
                        $invalid_format = isset($result['mo_custom_phone_invalid_format_message']) ? $result['mo_custom_phone_invalid_format_message'] : '';
                        $app = Factory::getApplication();
                        if(!empty($invalid_format)){
                            $invalid_format = str_replace("##phone##",$phone_number,$invalid_format);
                            $app->enqueueMessage($invalid_format, 'error');
                        }else{
                            $app->enqueueMessage(Text::_('PLG_USER_MINIORANGESENDOTP_PHONE_ERROR_MSG'), 'error');
                        }
                        $app->redirect(Route::_('index.php/component/users/?view=registration&Itemid=101'));
                    }
                }
                $this->startVerificationProcess($registration_otp_type, $username, $email, $errors, $phone_number, $password, $extra_data);
            }
        }
        Log::add('Customer is NOT registered.', Log::WARNING, 'tfa');

    }
    function checkIfVerificationIsComplete()
    {
        $session = Factory::getSession();
        $formvalidation = $session->get('formvalidation');
        if (isset($formvalidation) && $formvalidation == 'success') {
            $this->unsetOTPSessionVariables();
            return TRUE;
        }
        return FALSE;
    }

    public static function unsetOTPSessionVariables()
    {
        $session = Factory::getSession();
        $formvalidation = $session->get('formvalidation');
        $test = $session->get('test');
        $form = $session->set('formvalidation', 'Done');
        unset($test);
        unset($form);
    }

    public static function startVerificationProcess($_otp_type, $username, $email, $errors, $phone_number, $password, $extra_data ,$resend=0)
    {
        $default_country_code = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
        $default_country_code = $default_country_code['mo_default_country_code'] ?? null;
        /**
         * $_otp_type = 1 => OTP over Email method
         * $_otp_type = 2 => OTP over SMS method
         * $_otp_type = 3 => OTP over Email or SMS method
         * $_otp_type = 4 => OTP over Email and SMS method
         */
        if (empty($phone_number) && ($_otp_type == 2 || $_otp_type == 3 || $_otp_type == 4))
        {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('PLG_USER_MINIORANGESENDOTP_PHONE_REQUIRED'), 'error');
            $app->redirect(Route::_('index.php/component/users/?view=registration&Itemid=101'));
        } 
        else if (!empty($phone_number) && ($_otp_type == 2 || $_otp_type == 3 || $_otp_type == 4))
        {
            $phone_number = str_replace(" ", "", $phone_number);
            $phno = strlen($phone_number);
            $phbr = substr($phone_number, 0, 1);
            if ($phbr != '+') 
            {
                if (!empty($default_country_code))
                {
                    $phone_number = '+'.$default_country_code.$phone_number;
                    $phbr = '+';
                }
            }

            if ($phone_number == '' || $phno <= 4 || $phno >= 18 || $phbr != '+' )
            {
                $result= commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
                $invalid_format = isset($result['mo_custom_phone_invalid_format_message']) ? $result['mo_custom_phone_invalid_format_message'] : '';
                $app = Factory::getApplication();
                if (!empty($invalid_format))
                {
                    $msg = str_replace("##phone##",$phone_number,$invalid_format);

                } else
                {
                    $msg = Text::_('PLG_USER_MINIORANGESENDOTP_PHONE_ERROR_MSG');
                }
                $app->enqueueMessage($msg, 'error');
                $app->redirect(Route::_('index.php/component/users/?view=registration&Itemid=101'));
            }
        }

        $session = Factory::getSession();
        switch ($_otp_type){
            case '1':
                $otp_method = 'email';
                $session->set('otp_method',$otp_method);
                Log::add('This is message: ' . $otp_method, Log::INFO, 'tfa');
                
                miniorange_site_challenge_otp($username, $email, $errors,$otp_method,  $phone_number, $password, $extra_data,false,$resend);
                break;
            case '2':
                $otp_method = 'phone';
                $session->set('otp_method',$otp_method);
                miniorange_site_challenge_otp($username, $email, $errors,$otp_method,  $phone_number, $password, $extra_data,false,$resend);
                break;
            case '3':
                $otp_method = 'otp_over_email_or_sms';
                $session->set('otp_method',$otp_method);
                miniorange_site_challenge_otp($username, $email, $errors,$otp_method,  $phone_number, $password, $extra_data,false,$resend);
                break;
            case '4':
                $otp_method = 'otp_over_email_and_sms';
                $session->set('otp_method',$otp_method);
                miniorange_site_challenge_otp($username, $email, $errors,$otp_method,  $phone_number, $password, $extra_data,false,$resend);
                break;
        }
    }
    private function triggerTwoFaSetup($user_id): void
    { 
        Log::add('Initiated 2FA setup for registration flow, user ID: ' . $user_id, Log::INFO, 'tfa');
        $session = Factory::getSession();

        $settings = commonUtilitiesTfa::getTfaSettings();
        $app = Factory::getApplication();
        $c_user = Factory::getUser($user_id);
        if ($session->get('flow_type') === 'login') {
            $session->clear('user');  
            Log::add('Login flow session data cleared for user ID: ' . $user_id, Log::INFO, 'tfa');
        }
        session_regenerate_id(true); 

        $session->set('flow_type', 'registration'); 
        Log::add('Flow type set to registration for user ID: ' . $user_id, Log::INFO, 'tfa');

        $session->set('tfa_registration_in_progress', true); 
        Log::add('New session started for 2FA registration: ' . session_id(), Log::INFO, 'tfa');
        Log::add('New session ID created: ' . session_id(), Log::INFO, 'tfa');
    
       
        if (!isset($settings['enableTfaRegistration']) || $settings['enableTfaRegistration'] == 0) {
            Log::add('2FA after registration is disable.', Log::INFO, 'tfa');
            return;
        }

        $isAdminSite = strpos($app->get('uri')->current, 'administrator') !== false;
        $bypass_tfa_for_users = isset($settings['tfa_enabled_type']) && $settings['tfa_enabled_type'] == 'admin';
        
        if ($bypass_tfa_for_users && !$isAdminSite) {
            Log::add('2FA disabled for Enduser. Skipping 2FA setup for user ID: ' . $user_id, Log::INFO, 'tfa');
            return;
        }

        $userDetails = commonUtilitiesTfa::getMoTfaUserDetails($user_id);
        if (!empty($userDetails) && $userDetails['status_of_motfa'] == 'active') {
            Log::add('User already has active 2FA. Skipping setup for user ID: ' . $user_id, Log::INFO, 'tfa');
            return;
        }

        $session = Factory::getSession();

        $session->clear('motfa'); 
        $session->clear('steps'); 
        $session->clear('mo_tfa_message'); 
        $session->clear('mo_tfa_message_type'); 
        
        Log::add('Previous session data cleared before redirect.', Log::INFO, 'tfa');
        
        $redirectUrl = Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($user_id);
        
        if ($app->isClient('administrator')) {
            $redirectUrl .= '&admin=1';
        }

        Log::add('Redirecting user to 2FA registration setup: ' . $redirectUrl, Log::INFO, 'tfa');
        
        $app->redirect($redirectUrl);
        exit();
    }
 
     public function onUserLogin($user, $options = array())
    {
        $app = Factory::getApplication();
        $settings = commonUtilitiesTfa::getTfaSettings();
        $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $username = is_array($user) ? $user['username'] : $user->username;
        $tfa_uid = UserHelper::getUserId($username);
        $c_user = Factory::getUser($tfa_uid);
        $details = commonUtilitiesTfa::getCustomerDetails();
        
        $details_of_motfa = commonUtilitiesTfa::getMoTfaUserDetails($tfa_uid);
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $device_details=isset($post['miniorange_rba_attributes'])?($post['miniorange_rba_attributes']):'';
        Log::add('User login initiated for username: ' . $username, Log::INFO, 'tfa');
        Log::add('post data: ' . print_r($post, true), Log::INFO, 'tfa');

        // Set the flow type to login
        $session = Factory::getSession();
        $session->set('flow_type', 'login');
        Log::add('Flow type set to login for user: ' . $username, Log::INFO, 'tfa');

        // Blocked or not activated user check (must be before any TFA logic)
        if ($c_user->block == 1 || ($c_user->activation && $c_user->activation != '')) {
            Log::add('Login denied for blocked/inactive user: ' . $username, Log::WARNING, 'tfa');
            return false;
        }

        // Check if TFA login is being attempted and if user's role is allowed
        $enablePasswordlessLogin = isset($settings['enable_tfa_passwordless_login']) ? (bool) $settings['enable_tfa_passwordless_login'] : false;
        
         if (isset($post['tfa_login']) && $post['tfa_login'] == '1' && $enablePasswordlessLogin) {
            $allowedRoles = isset($settings['mo_tfa_for_roles']) ? $settings['mo_tfa_for_roles'] : '';
            
            if (!empty($allowedRoles)) {
                // Get all user's groups
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('DISTINCT g.id, g.title')
                    ->from($db->quoteName('#__user_usergroup_map', 'm'))
                    ->join('INNER', $db->quoteName('#__usergroups', 'g') . ' ON g.id = m.group_id')
                    ->where($db->quoteName('m.user_id') . ' = ' . $db->quote($tfa_uid));
                $db->setQuery($query);
                $userGroups = $db->loadObjectList();

                if (empty($userGroups)) {
                    Log::add('No groups found for user: ' . $username, Log::WARNING, 'tfa');
                    return false;
                }

                // Convert allowed roles to array
                $allowedRoleArray = is_string($allowedRoles) 
                    ? (json_decode($allowedRoles, true) ?: array_map('trim', explode(',', $allowedRoles)))
                    : (array)$allowedRoles;

                // Log user groups and allowed roles
                $groupTitles = array_map(function($group) { return $group->title; }, $userGroups);
                Log::add('User groups: ' . implode(', ', $groupTitles), Log::INFO, 'tfa');
                Log::add('Allowed roles: ' . print_r($allowedRoleArray, true), Log::INFO, 'tfa');

                // Check if any of user's groups are allowed
                $userHasAllowedRole = in_array('ALL', $allowedRoleArray);
                if (!$userHasAllowedRole) {
                    foreach ($userGroups as $group) {
                        if (in_array($group->title, $allowedRoleArray)) {
                            $userHasAllowedRole = true;
                            Log::add('TFA access granted due to group: ' . $group->title, Log::INFO, 'tfa');
                            break;
                        }
                    }
                }

                if (!$userHasAllowedRole) {
                    Log::add('TFA login blocked for user: ' . $username . ' - No allowed roles found. User groups: ' . implode(', ', $groupTitles) . ', Allowed roles: ' . print_r($allowedRoleArray, true), Log::WARNING, 'tfa');
                    $app->enqueueMessage('Your role is blocked. Please use your username and password.', 'error');
                    // Redirect to the login form instead of returning false
                    $app->redirect(Uri::root() . 'index.php?option=com_users&view=login');
                    return;
                }
            }
            
            Log::add('TFA login allowed for user: ' . $username . ' - Role check passed', Log::INFO, 'tfa');
        } else {
            // Normal login or passwordless login disabled - allow all users regardless of role
            if ($enablePasswordlessLogin) {
                Log::add('Normal login detected for user: ' . $username . ' - No role restrictions applied', Log::INFO, 'tfa');
            } else {
                Log::add('Passwordless login disabled - No role restrictions applied for user: ' . $username, Log::INFO, 'tfa');
            }
        }

        $rba_var='';
        $session = Factory::getSession();
        $tfaInfo = $session->get('tfa_verified');
        $enableTfaDomain = isset($settings['enableTfaDomain']) ? (bool) $settings['enableTfaDomain'] : false;
        
        // Check if TFA should be disabled based on role first
        if (!$isCustomerRegistered || !isset($settings['tfa_enabled']) || $settings['tfa_enabled'] == 0 || $this->roleBaseTfaDisabled($c_user->groups) || commonUtilitiesTfa::doWeHaveAwhiteIp(commonUtilitiesTfa::get_client_ip(), $settings))
        { 
            Log::add('TFA not enabled for user: ' . $username, Log::INFO, 'tfa');
            // Clear any existing TFA session data
            $session->clear('steps');
            $session->clear('motfa');
            $session->clear('mo_tfa_message');
            $session->clear('mo_tfa_message_type');
            $session->clear('current_user_id');
            $session->clear('current_userid');
            $session->clear('tfa_verified');
            $session->clear('challenge_response');
            $session->clear('kba_response');
            $session->clear('googleInfo');
            $session->clear('selected_2fa_method');
            $session->clear('ooe_for_change_2fa');
            return TRUE;
        }

        // Check if user is in the middle of TFA setup
        $currentSteps = $session->get('steps');
        $motfa = $session->get('motfa');
        
        // If user is in the middle of TFA setup, force them to complete it
        if (!empty($currentSteps) && !empty($motfa) && $tfaInfo != 'yes') {
            Log::add('User is in the middle of TFA setup (step: ' . $currentSteps . '). Clearing session and restarting from step 1.', Log::INFO, 'tfa');
            
            // Clear all TFA session variables to restart from beginning
            $session->clear('steps');
            $session->clear('motfa');
            $session->clear('mo_tfa_message');
            $session->clear('mo_tfa_message_type');
            $session->clear('current_user_id');
            $session->clear('current_userid');
            $session->clear('tfa_verified');
            $session->clear('challenge_response');
            $session->clear('kba_response');
            $session->clear('googleInfo');
            $session->clear('selected_2fa_method');
            $session->clear('ooe_for_change_2fa');
            
            // Redirect to TFA setup to start from step 1
            if ($app->isClient('site')) { 
                header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($tfa_uid));
            } else {
                header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($tfa_uid) . '&admin=1');
            }
            exit();
        }
        
        if ($session->get('pending_login')) {
            return false; // Wait for OTP verification
        }

        if ($enableTfaDomain) {
            $email = $c_user->email;  
            $userDomain = strtolower(trim(substr(strrchr($email, "@"), 1))); 
            Log::add('User Domain on Login: ' . $userDomain, Log::INFO, 'TFA');

            $settings = Mo_tfa_utilities::getSettings();
            $tfaDomainList = isset($settings['tfaDomainList']) ? json_decode($settings['tfaDomainList'], true) : [];
            
            if (!is_array($tfaDomainList)) {
                $tfaDomainList = explode(';', $settings['tfaDomainList']);
            }
            
            Log::add('Fetched tfaDomainList: ' . print_r($tfaDomainList, true), Log::INFO, 'TFA');
            
            $textareaContent = implode(';', $tfaDomainList);
            echo '<textarea name="domain_list">' . htmlspecialchars($textareaContent) . '</textarea>';
            

            if (!empty($tfaDomainList)) {
                $allowedDomains = array_map('trim', $tfaDomainList);  

                if (!in_array($userDomain, $allowedDomains, true)) {
                    Log::add('Domain not allowed: ' . $userDomain, Log::WARNING, 'TFA');
                    return true; 
                }
            } else {
                Log::add('No tfaDomainList set or empty, allowing login.', Log::INFO, 'TFA');
            }
        } else {
            Log::add('Domain-based 2FA is disabled, allowing login.', Log::INFO, 'TFA');
        }

        // Skip TFA if passwordless login is enabled and user logs in with a password
        if (isset($settings['enable_tfa_passwordless_login']) && $settings['enable_tfa_passwordless_login'] && !empty($post['password'])) {
            Log::add('Passwordless login is enabled, user logged in with password. Checking role-based TFA for user: ' . $username, Log::INFO, 'tfa');
        }

        $app  = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $inputCookie = $input->cookie;        
        $rba_Verifier = $inputCookie->get($name = 'rba', $defaultValue = null);
        
        if(!is_null($details_of_motfa))
        {
            $db = Factory::getDbo();
            $db->setQuery($db->getQuery(true)
                ->select('mo_rba_device')
                ->from($db->quoteName('#__miniorange_rba_device'))
                ->where($db->quoteName('user_id') . ' = '.$db->quote($tfa_uid))
            );
            $result= $db->loadColumn();
            
            if(!empty($result) && isset($post['Submit']) && ($post['Submit']!='passless_login') && $post['password']!='password')
            {
                foreach($result as $key=>$val)
                    {
    
                        if($device_details == $val)
                        {
                            if($rba_Verifier !== NULL)
                            {
                                setcookie('rba', $rba_Verifier, time() + (86400 * 30), "/"); 
                                $rba_var=true;
                                break;
                            }
                           else
                           {
                                $db = Factory::getDbo();
                                $query = $db->getQuery(true);
                                $conditions = array(
                                    $db->quoteName('user_id') . ' = ' . $db->quote($tfa_uid)
                                );
                                $query->delete($db->quoteName('#__miniorange_rba_device'));
                                $query->where($conditions);
                                $db->setQuery($query);
                                $db->execute();
                                break;
                           }
                        }
                    }
            }
        }
        Log::add('TFA settings for user ' . $username . ': ' . json_encode($settings), Log::DEBUG, 'tfa');

        $session = Factory::getSession();
        $tfaInfo = $session->get('tfa_verified');

        $userId = UserHelper::getUserId($username);
        $skip = commonUtilitiesTfa::getMoTfaUserDetails($userId);
        $no_of_users=commonUtilitiesTfa::checkMoTfaUsers();

        $jLanguage = $app->getLanguage();
        $jLanguage->load('plg_user_miniorangetfa', JPATH_ADMINISTRATOR, 'en-GB', true, true);
        $jLanguage->load('plg_system_updatenotification', JPATH_ADMINISTRATOR, null, true, false);
        
        // Check user limit for all cases
        if (empty($details['license_type']) || $details['license_type'] == '') {
            // Demo license - allow up to 1 user
            if ($no_of_users >= 1) {
                if(is_null($details_of_motfa) || empty($details_of_motfa) || $details_of_motfa['status_of_motfa'] != 'active') {
                    Log::add('Demo license user limit (1) exceeded. Current users: ' . $no_of_users, Log::WARNING, 'tfa');
                    $app->enqueueMessage(Text::_('PLG_USER_MINIORANGETFA_MSG'), 'warning');
                    return TRUE;
                }
            }
        } else if($no_of_users >= $details['no_of_users'] && $details['no_of_users'] != 0) {
            // Regular license user limit check
            if(is_null($details_of_motfa) || empty($details_of_motfa) || $details_of_motfa['status_of_motfa'] != 'active') {
                Log::add('User limit exceeded. Current users: ' . $no_of_users . ', Limit: ' . $details['no_of_users'], Log::WARNING, 'tfa');
                $app->enqueueMessage(Text::_('PLG_USER_MINIORANGETFA_MSG'), 'warning');
                return TRUE;
            }
        }

        $session->set('current_userid', $tfa_uid);
        
        $isroot = $c_user->authorise('core.login.admin');

        $c_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $isAdminSite = strpos($c_url, 'administrator') == true ? 'Yes' : 'No';

        $bypass_tfa_for_admin = isset($settings['tfa_enabled_type']) && ($settings['tfa_enabled_type'] === 'site');
        $bypass_tfa_for_users = isset($settings['tfa_enabled_type']) && ($settings['tfa_enabled_type'] === 'admin');
        $admin_superuser_only = isset($settings['tfa_enabled_type']) && ($settings['tfa_enabled_type'] === 'admin_superuser');

        if(is_null($details_of_motfa) && $settings['tfa_halt']==1 )
        {
            Log::add('2FA for New users is disabled. Skipping setup for user ID: ' . $userId, Log::INFO, 'tfa');
            return TRUE;
        }

        $licenseUtility = commonUtilitiesTfa::license_efficiency_check();
        if (!empty($licenseUtility['license_expiry'])) {
            Log::add('skip tfa because license expired: ' . $userId, Log::INFO, 'tfa');
            return true;
        }


         // If admin option is selected, bypass TFA for admin and superuser on frontend
        if ($bypass_tfa_for_users && !$app->isClient('administrator') && ($isroot || isset($c_user->groups['7']) || isset($c_user->groups['8']))) {
            Log::add('Admin/Superuser bypassed TFA on frontend as per admin settings: ' . $username, Log::INFO, 'tfa');
            return TRUE;
        }

        // If site option is selected, enforce TFA for admin and superuser only on frontend
        if ($bypass_tfa_for_admin && ($isroot || isset($c_user->groups['7']) || isset($c_user->groups['8']))) {
            if (!$app->isClient('administrator')) {
                Log::add('TFA enforced for Admin/Superuser on frontend as per site settings: ' . $username, Log::INFO, 'tfa');
                // Continue with TFA verification for frontend
            } else {
                Log::add('TFA bypassed for Admin/Superuser on backend as per site settings: ' . $username, Log::INFO, 'tfa');
                return TRUE;
            }
        } else if ($bypass_tfa_for_users && !$isroot && $isAdminSite == 'No') {
            Log::add('2FA disabled for Enduser.', Log::INFO, 'tfa');
            return TRUE;
        }
        

        if (!$isCustomerRegistered || !isset($settings['tfa_enabled']) || $settings['tfa_enabled'] == 0 || commonUtilitiesTfa::doWeHaveAwhiteIp(commonUtilitiesTfa::get_client_ip(), $settings))
        { 
            Log::add('TFA not enabled for user: ' . $username, Log::INFO, 'tfa');
            // dont invoke 2FA for this case
            return TRUE;
        }
        
        // Remove license check to allow demo accounts
        // if( $details['license_type'] =='' && $details['license_plan']==''){
        //     Log::add('User license validation failed: ' . $username, Log::WARNING, 'tfa');
        //     return TRUE;
        // }
        
        if(isset($rba_var) && $rba_var==TRUE)
        {
            Log::add('RBA bypass applied for user: ' . $username, Log::INFO, 'tfa');
            return TRUE;
        }

        $no_of_users = commonUtilitiesTfa::checkMoTfaUsers();
        $config = Factory::getConfig();
        $userGroups = $c_user->groups;
        $adminEmail = $config->get('mailfrom');
        $to = $c_user->email; 
        $subject = "Welcome to 2FA Registration"; 
        $session = Factory::getSession();
        $method = $session->get('selected_2fa_method'); 

        if (!empty($method)) {
            $msg = "<p>Welcome <strong>{$c_user->username}</strong>!</p>";
            $msg .= "<p>Your registration is complete, and you have opted for the following 2FA method:</p>";
            $msg .= "<p><strong>2FA Method:</strong> {$method}</p>";

            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select([
                    'email_method',
                    'smtp_host',
                    'smtp_port',
                    'smtp_username',
                    'smtp_password',
                    'recipients'    
                ])
                ->from($db->quoteName('#__miniorange_email_settings'))
                ->where($db->quoteName('id') . ' = 1'); 
               Log::add('Executing query to fetch email settings: ' . $query->__toString(), Log::INFO, 'email_settings');

            $db->setQuery($query);
            $settings = $db->loadObject();

            if (!$settings) {
                return;
            }
            
            if (is_object($settings)) {
                $email_method = $settings->email_method;
                $recipient = $settings->recipients ?? 'both';   
                $sendToAdmin = in_array($recipient, ['admin', 'both']);
                $sendToUser = in_array($recipient, ['user', 'both']);
            }
            
      
            
            if ($sendToAdmin) {
                if ($email_method == 'smtp') {
                    $emailStatusAdmin = $this->smtp_mailer($adminEmail, $subject, $msg, $settings->smtp_host, $settings->smtp_port, $settings->smtp_username, $settings->smtp_password);
                } else if ($email_method == 'api') {
                    $emailStatusAdmin = $this->send_email_via_api($adminEmail, $subject, $msg);
                }
            }

            if ($sendToUser) {
                if ($email_method == 'smtp') {
                    $emailStatusUser = $this->smtp_mailer($to, $subject, $msg, $settings->smtp_host, $settings->smtp_port, $settings->smtp_username, $settings->smtp_password);
                } else if ($email_method == 'api') {
                    $emailStatusUser = $this->send_email_via_api($to, $subject, $msg);
                }
            }

            $session->set('selected_2fa_method', null);
        }
        
  
        if($skip=='null'||is_array($skip)?($skip['active_method']!= 'NONE'):TRUE)
        {   
            
            if ($tfaInfo == 'yes') 
            {  
                Log::add('TFA already verified for user: ' . $username, Log::INFO, 'tfa');
                $session->clear('tfa_verified');
                $session->clear('steps');
                $session->clear('motfa');
                $session->clear('current_user_id');
                $session->clear('challenge_response');
                $session->clear('mo_tfa_message');
                $session->clear('mo_tfa_message_type');
                $session->clear('kba_response');
                $session->clear('motfa_initiated');  // Still clear it if exists      
                $session->clear('motfa_initiated');
                return TRUE;
            } 
            else 
            {  
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__users'))
                    ->where($db->quoteName('username') . ' = ' . $db->quote($username));
                $db->setQuery($query);
                $userId = $db->loadResult();
                Log::add('Manual DB userId lookup for username [' . $username . ']: ' . $userId, Log::DEBUG, 'tfa');

                // Fallback to current user if not found
                if (empty($userId) && !empty($c_user->id)) {
                    $userId = $c_user->id;
                    Log::add('Fallback to current user ID: ' . $userId, Log::DEBUG, 'tfa');
                }
                Log::add('Resolved userId from username: ' . $userId, Log::DEBUG, 'tfa');

                if (isset($settings['tfa_enabled']) && $settings['tfa_enabled'] == 0) {
                    $row = commonUtilitiesTfa::getMoTfaUserDetails($userId);
                 
                    if (is_null($row) || count($row) == 0 || !isset($row['status_of_motfa']) || $row['status_of_motfa'] != 'active') {
                        $session->clear('motfa_initiated');
                        return TRUE;
                    }
                }
                
                $session->clear('tfa_verified');
                $session->clear('steps');
                $session->clear('motfa');
                $session->clear('current_user_id');
                $session->clear('challenge_response');
                $session->clear('mo_tfa_message');
                $session->clear('mo_tfa_message_type');
                $session->clear('kba_response');
                $tfa_arr = $session->get('motfa_initiated');
                
                if (is_null($tfa_arr) || !isset($tfa_arr)) {
                    $tfa_arr = array();
                }
                array_push($tfa_arr, $userId);
                Log::add("User ID fetched: $userId", Log::INFO, 'motfa');

                $session->set('motfa_initiated', $tfa_arr);
              
                if ($app->isClient('site')) { 
                    header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($userId));
                    Log::add('Fetched tfainList: ', Log::INFO);

                } else {
                    header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($userId) . '&admin=1');
                }
              exit();
            }
        }
    }
    function smtp_mailer($to, $subject, $msg, $from)
    {
        Log::add('Debug: Inside smtp_mailer...', Log::INFO, 'tfa');
        Log::add('From: ' . $from, Log::INFO, 'tfa');
        Log::add('Recipient Email: ' . $to, Log::INFO, 'tfa');
    
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__miniorange_email_settings'))
                    ->setLimit(1);
        $db->setQuery($query);
    
        $smtp_settings = $db->loadObject(); 
    
        if ($smtp_settings) {
            $smtpSettingsDebug = json_encode($smtp_settings, JSON_PRETTY_PRINT);
            Log::add("SMTP Settings Debug: " . $smtpSettingsDebug, Log::INFO, 'smtp_mailer');
        } else {
            $errorMessage = 'SMTP settings not found in the database.';
            Log::add($errorMessage, Log::ERROR, 'smtp_mailer');
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
            return $mail->ErrorInfo;
        } else {
            return 'Sent'; 
        }
    }

    public function roleBaseTfaDisabled($userRolesId)
    {
        $userRoles = array();
        $groups = commonUtilitiesTfa::loadGroups();
        $settings = commonUtilitiesTfa::getTfaSettings();
        $tfa_for_roles = isset($settings['mo_tfa_for_roles']) && !empty($settings['mo_tfa_for_roles']) ? json_decode($settings['mo_tfa_for_roles']) : array();
        foreach ($groups as $key => $value) {
            if (in_array($value['id'], $userRolesId)) {
                array_push($userRoles, $value['title']);
            }
        }

        if (count($tfa_for_roles) == 0)
        {
            return TRUE;
        }
        else if (in_array('ALL', $tfa_for_roles)) 
        {
            return FALSE;
        }

        foreach ($userRoles as $key => $value) 
        {
            if (in_array($value, $tfa_for_roles)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function onUserAfterDelete($user, $success, $msg)
    {
        $userId = is_object($user) && isset($user->id) ? (int) $user->id :
                  (is_array($user) && isset($user['id']) ? (int) $user['id'] : 0);
    
        if ($success && $userId) {
            Log::add("User ID found: {$userId}", Log::INFO, 'user');
    
            try {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__miniorange_tfa_users'))
                    ->where($db->quoteName('id') . ' = ' . $db->quote($userId));
    
                $db->setQuery($query);
                $db->execute();
    
                Log::add("Successfully deleted 2FA data for User ID: {$userId}", Log::INFO, 'user');
            } catch (Exception $e) {
                Log::add("Error deleting 2FA data for User ID: {$userId}. Error: " . $e->getMessage(), Log::ERROR, 'user');
            }
        } else {
            Log::add("User deletion failed or User ID not available.", Log::WARNING, 'user');
        }
    }
    
    
}