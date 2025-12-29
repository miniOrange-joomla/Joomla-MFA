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
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
Use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Log\Log;

Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);
require_once 'miniorangetfa.php';
jimport('miniorangetfa.utility.commonUtilitiesTfa');

/*
	add_action(	'init', 'miniorange_customer_validation_handle_form' , 1 );
	add_action( 'mo_validate_otp', '_handle_validation_form_action' , 1, 2);
	add_filter('mo_filter_phone_before_api_call','_filter_phone_before_api_call',1,1);
	*/

/**
 * This function is called from every form handler class to start the OTP
 * Verification process. Keeps certain variables in session and start the
 * OTP Verification process.
 *
 * @param $user_login - username submitted by the user
 * @param $user_email - email submitted by the user
 * @param $errors - error variable ( currently not being used )
 * @param $phone_number - phone number submitted by the user
 * @param $otp_type - email or sms verification
 * @param $password - password submitted by the user
 * @param $extra_data - an array containing all the extra data submitted by the user
 * @param $from_both - denotes if user has a choice between email and phone verification
 */

function miniorange_site_challenge_otp($user_login, $user_email, $errors,  $otp_type,$phone_number = null, $password = "", $extra_data = null, $from_both = false, $resend = 0)
{    
    $session = Factory::getSession();
    $session->set('user_email', $user_email);
    $session->set('user_login', $user_login);
    $session->set('user_password', $password);
    $session->set('phone_number_mo', $phone_number);
    $session->set('extra_data', $extra_data);
    _handle_otp_action($user_login, $user_email,$phone_number, $otp_type,  $from_both, $extra_data ,$resend);

}
function _handle_otp_action($user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data , $resend=0)
{
    global $phoneLogic, $emailLogic, $EmailOrPhoneLogic;
    $session = Factory::getSession();
   
    switch ($otp_type) {
        case 'phone':
            $phoneLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both,$resend);
            break;
        case 'email':
            $emailLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both,$resend);
            break;
        case 'otp_over_email_or_sms':
            $EmailOrPhoneLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both, $resend);
            break;
        case 'otp_over_email_and_sms':
            if( isset($_COOKIE['email_verified']) && $_COOKIE['email_verified'] == $user_email ){
                $phoneLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both, $resend);
            }
            else {
                $emailLogic->_handle_logic($user_login, $user_email, $phone_number, $otp_type, $from_both, $resend);
            }
            break;
        case 'both':
            miniorange_verification_user_choice($user_login, $user_email, $phone_number, MoMessages::showMessage('CHOOSE_METHOD'), $otp_type);
            break;
    }
}

function miniorange_customer_validation_handle_form()
{

    Log::add('validation handle form' ,Log::INFO, 'otp_request');


    if (array_key_exists('option1', $_REQUEST)) {

        Log::add('option1 found: ' . $_REQUEST['option1'], Log::INFO, 'otp_request');

        switch (trim($_REQUEST['option1'])) {
            case "validation_goBack":
                _handle_validation_goBack_action();
                break;
            case "miniorange-validate-otp-form":
                _handle_validation_form_action();
                break;
            case "verification_resend_otp_phone":
                _handle_verification_resend_otp_action("phone");
                break;
            case "verification_resend_otp_email":
                $from_both = Factory::getApplication()->input->get->post(['from_both']) == 'true' ? true : false;
                _handle_verification_resend_otp_action("email", $from_both);
                break;
            case "verification_resend_otp_both":
                $from_both = Factory::getApplication()->input->get->post(['from_both']) == 'true' ? true : false;
                _handle_verification_resend_otp_action("both", $from_both);
                break;
            case "miniorange-validate-otp-choice-form":
                _handle_validate_otp_choice_form(Factory::getApplication()->input->get->post);
                break;
            case "check_mo_ln":
                MoUtility::_handle_mo_check_ln(true,
                    "mo_customer_validation_admin_customer_key",
                    "mo_customer_validation_admin_api_key"
                );
                break;
        }
    }
}
    function _handle_validation_form_action($requestVariable = 'mo_customer_validation_otp_token', $otp_token = NULL)
{
    Log::add('handle validation form' ,Log::INFO, 'otp_request');

    global $phoneLogic;
    MoUtility::checkSession(); 
    $session = Factory::getSession();
    $app = Factory::getApplication();
    $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
    $post = ($input && $input->post) ? $input->post->getArray() : [];    
    $customer_details = commonUtilitiesTfa::getCustomerDetails();
    $otp_method = isset($customer_details['registration_otp_type']) ? $customer_details['registration_otp_type'] : 0;
    Log::add('Registration OTP Type: ' . $otp_method, 'tfa');
    if ($otp_method == 1)
        $otp_method_sub = 'Email';
    else if ($otp_method == 2)
        $otp_method_sub = 'SMS';
    else if ($otp_method == 3)
        $otp_method_sub = 'Email or SMS';
    else
        $otp_method_sub = 'Email and SMS';

    $user_login = $session->get('user_login');
    $user_email = $session->get('user_email');
    $phone_number_mo = $session->get('phone_number_mo');
    $user_password = $session->get('user_password');
    $extra_data = $session->get('extra_data');
    $user_login = !MoUtility::isBlank($user_login) ? $user_login : null;
    $user_email = !MoUtility::isBlank($user_email) ? $user_email : null;
    $phone_number = !MoUtility::isBlank($phone_number_mo) ? $phone_number_mo : null;
    $password = !MoUtility::isBlank($user_password) ? $user_password : null;
    $extra_data = !MoUtility::isBlank($extra_data) ? $extra_data : null;
    $session = Factory::getSession();
    $txID = $session->get('test');
    $otp_token = !is_null($requestVariable) && array_key_exists($requestVariable, $_REQUEST)
    && !MoUtility::isBlank($_REQUEST[$requestVariable]) ? $_REQUEST[$requestVariable] : $otp_token;

    if (!is_null(value: $otp_token)) {
        $response = MocURLOTP::validatee_otp_token($txID, $otp_token);
        $content = json_decode($response, true);
        if (is_array($content) && isset($content['status'])) { 
            switch ($content['status']) {
            case 'SUCCESS':
                $currentTime = time();
                if ($otp_method != 4){

                    Log::add('add transaction data' ,Log::INFO, 'otp_request');

                    commonUtilitiesTfa::add_otp_transaction($otp_method_sub, $user_email, $phone_number, 'Yes', 'Yes', $currentTime);
                }
                else{
                    if ($session->get('inPhone') == 'yes'){
                        commonUtilitiesTfa::add_otp_transaction($otp_method_sub, $user_email, $phone_number, 'Yes', 'Yes', $currentTime);
                    }
                }
                _handle_success_validated($user_login, $user_email, $password, $phone_number, $extra_data);
                $session->set('email_verification_in_step1', "yes");

                if ($session->get('otp_type') == "otp_over_email_and_sms"){
                    if ($session->get('inPhone') != 'yes') {
                        $session->set('inPhoneVerification','yes');
                        setcookie('email_verified', $user_email);
                        $phoneLogic->_handle_logic($user_login, $user_email, $phone_number, 'otp_over_email_and_sms', $from_both = false, $resend = 0);
                    }
                }
                $user_exist = UserHelper::getUserId($user_login);
                break;
            default:
                $currentTime = time();
                commonUtilitiesTfa::add_otp_transaction($otp_method_sub, $user_email, $phone_number, 'Yes', 'No', $currentTime);
                _handle_error_validated($user_login, $user_email, $phone_number);
                break;
        }
    } else {
        Log::add('Invalid OTP validation response: ' . $response, 'error'); // Optional logging
    }
    }
}

function _handle_success_validated($user_login, $user_email, $password, $phone_number, $extra_data)
{
    Log::add('handle success validated' ,Log::INFO, 'otp_request');

    if (isset($phone_number)) {
        $userId = UserHelper::getUserId($user_login);

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];

        if ( isset($post['__cf_token']) ) {
            $username = $post['username'];
            $name = isset($post['name']) ? $post['name'] : $username;
            $password = $post['password'];
            $email = $post['email'];
            $phone = $post['phone'];

            addUserToJoomla($username, $name,$email, $password, '2', true);
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $fields = array(
            $db->quoteName('profile_value') . ' = ' . $db->quote($phone_number),
            $db->quoteName('ordering') . ' = 2'
        );
        $conditions = array(
            $db->quoteName('user_id') . ' = ' . $db->quote($userId),
            $db->quoteName('profile_key') . ' = ' . $db->quote('profile.phone')
        );
        $query->update($db->quoteName('#__user_profiles'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    MoUtility::checkSession();
    $session = Factory::getSession();
    $session->set('formvalidation', 'success');
    $session->set('test-refresh',null);
    $session->set('phone-test-refresh',null);
    $session->set('both-refresh',null);
    if ($session->get('otp_method') == 'email' || $session->get('otp_method') == 'phone' || $session->get('otp_method') == 'otp_over_email_or_sms')
        $session->set('reset-refresh',null);
    if ($session->get('otp_type') == 'otp_over_email_and_sms' && $session->get('inEmail') == 'yes') {
        $session->set('reset-refresh',null);
        $session->set('inEmail',null);
    }

    if ($session->get('otp_type') == 'otp_over_email_and_sms' && ( $session->get('inPhoneVerification') == 'yes' || $session->get('inPhone') == 'yes' ) ){
        $session->set('reset-refresh',null);
        $session->set('inPhoneVerification',null);
        $session->set('otp_type',null);
        setcookie('email_verified', null);
    }
    $session->set('inPhone',null);
    $session->set('transaction_id_email',null);
}

function addUserToJoomla($username, $name, $email, $password, $groups, $activate = false)
{

    Log::add('add user joomla' ,Log::INFO, 'otp_request');

    jimport('joomla.user.helper');

    $groups = is_string($groups) ? explode(',', $groups) : (array) $groups;

    $data = [
        'name'   	 => $name,
        'username'	 => $username,
        'password'	 => $password,
        'email'		 => PunycodeHelper::emailToPunycode($email),
        'groups'	 => $groups
    ];

    if (!$activate)
    {
        $hash = JApplicationHelper::getHash(UserHelper::genRandomPassword());
        $data['activation'] = $hash;
        $data['block'] = 1;
    }

    // Load the user's plugin group.
    PluginHelper::importPlugin('user');
    $user = new User;

    if (!$user->bind($data))
    {
        throw new Exception($user->getError());
    }

    if (!$user->save())
    {
        throw new Exception($user->getError());
    }

    return $user;
}

function _handle_error_validated($user_login, $user_email, $phone_number)
{
    MoUtility::checkSession();
    $customer_details = commonUtilitiesTfa::getCustomerDetails();
    $otpVerType = isset($customer_details['login_otp_type']) ? $customer_details['login_otp_type'] : '';
    $fromBoth   = strcasecmp($otpVerType, "both") == 0 ? TRUE : FALSE;
    miniorange_site_otp_validation_form($user_login, $user_email, $phone_number, MoUtility::_get_invalid_otp_method(), $otpVerType, $fromBoth);
}

/**
 * This function handles which page to redirect the user to when he
 * clicks on the go back link on the OTP Verification pop up.
 */
function _handle_validation_goBack_action()
{

    MoUtility::checkSession();
    $session = Factory::getSession();
    $current_url = $session->get('current_url');
    $app = Factory::getApplication();
    $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
    $post = ($input && $input->post) ? $input->post->getArray() : [];
    $customer_details = commonUtilitiesTfa::getCustomerDetails();
    $otp_type          = isset($customer_details['registration_otp_type']) ? $customer_details['registration_otp_type'] : 0;
    $user_email        = isset($post['email']) ? $post['email'] : '';
    $user_phone_number = isset($post['phone']) ? $post['phone'] : '';
    Log::add('OTP Type: ' . $otp_type . ', User Email: ' . $user_email . ', User Phone: ' . $user_phone_number, 'tfa');
    $currentTime       = time();
    if ($otp_type == 1){
        $method = "Email";
    }
    else if ($otp_type == 2){
        $method = "SMS";
    }
    else if ($otp_type == 3){
        $method = "Email or SMS";
    }
    else {
        $method = "Email and SMS";
    }

    if ($session->get('otp_status') == 'SUCCESS')
        commonUtilitiesTfa::add_otp_transaction($method, $session->get('user_email'), $session->get('phone_number_mo'), 'Yes', 'No', $currentTime);
    else if ($session->get('otp_status') == 'FAILED')
        commonUtilitiesTfa::add_otp_transaction($method, $session->get('user_email'), $session->get('phone_number_mo'), 'No', 'No', $currentTime);

    $url = isset($current_url) ? $current_url : '';
    echo $url;
    PlgUserMiniorangetfa::unsetOTPSessionVariables();
    $session->set('test-refresh',null);
    $session->set('phone-test-refresh',null);
    $session->set('both-refresh',null);
    $session->set('reset-refresh',null);
    $session->set('transaction_id_email',null);
    $session->set('otp_type',null);
    $session->set('inPhone',null);
    $session->set('inPhoneVerification',null);
    $session->set('otp_status',null);

    header("location:" . $url);
}

/**
 * This function is called to handle the resend OTP Verification process.
 *
 * @param $otp_type - email or sms verification
 * @param $from_both - denotes if user has a choice between email and phone verification
 */
function _handle_verification_resend_otp_action($otp_type, $from_both = false)
{
    MoUtility::checkSession();
    $session = Factory::getSession();
    $user_email = $session->get('user_email');
    $user_login = $session->get('user_login');
    $password = $session->get('user_password');
    $phone_number = $session->get('phone_number_mo');
    $extra_data = $session->get('extra_data');
    _handle_otp_action($user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data);
}


/**
 * This function starts the OTP verification process based on user input.
 * starts Email or Phone Verification based on user input.
 *
 * @param $postdata - the data posted
 */
function _handle_validate_otp_choice_form($postdata)
{
    MoUtility::checkSession();
    $session = Factory::getSession();
    $user_login = $session->get('user_login');
    $user_email = $session->get('user_email');
    $phone_number_mo = $session->get('phone_number_mo');
    $user_password = $session->get('user_password');
    $extra_data = $session->get('extra_data');

    if (strcasecmp($postdata['mo_customer_validation_otp_choice'], 'user_email_verification') == 0)
        miniorange_site_challenge_otp($user_login, $user_email, null, "email", $phone_number_mo, $user_password, $extra_data, true);
    else
        miniorange_site_challenge_otp($user_login, $user_email, null, "phone",  $phone_number_mo, $user_password, $extra_data, true);
}
