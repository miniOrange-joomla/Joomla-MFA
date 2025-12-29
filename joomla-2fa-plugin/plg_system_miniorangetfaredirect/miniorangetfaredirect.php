<?php

/**
 * @package     Joomla.Plugin	
 * @subpackage  plg_system_miniorangetfaredirect
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */



defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
Use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Filesystem\File;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Installer\Installer;


jimport('miniorangetfa.utility.commonUtilitiesTfa');
require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Mo_tfa_utility.php';

Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);
/**
 * Plugin class for miniorangetfa redirect handling.
 *
 * @since  1.6
 */

 $app = Factory::getApplication();
 $doc = $app->getDocument();
 
 if ($doc instanceof HtmlDocument) {
     $wa = $doc->getWebAssetManager();
     $wa->useScript('jquery');
 }    

class PlgSystemMiniorangetfaredirect extends CMSPlugin 
{
    public function onAfterRender()
    {
        $app = Factory::getApplication();
        $body = $app->getBody();
        $user = Factory::getUser();  
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $get = ($input && $input->get) ? $input->get->getArray() : [];
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
        $userId = $user->id;

        $encryptedUserId = commonUtilitiesTfa::encrypt($userId);
        $settings = commonUtilitiesTfa::getMoTfaSettings();
        $enable_otp_login = isset($settings['enable_tfa_passwordless_login']) ? (int)$settings['enable_tfa_passwordless_login'] : 0;
    
       
        if ($enable_otp_login === 1 && $app->isClient('site')) {
            $buffer = $app->getBody();
        
            $customStyle = '<style>
                .mo-hide {
                    display: none !important;
                }
            </style>';
            $buffer = str_replace('</head>', $customStyle . '</head>', $buffer);
            $customScript = '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.querySelectorAll(".mod-login").forEach(function(form) {
                        form.querySelector(".mod-login__password")?.classList.add("mo-hide");
                        form.querySelector("#form-login-password")?.classList.add("mo-hide");
                        // Hide all submit buttons inside the form
                        form.querySelectorAll("button[type=submit], input[type=submit]").forEach(function(btn){
                            btn.classList.add("mo-hide");
                        });
                    });
                });
            </script>';

            $buffer = str_replace('</body>', $customScript . '</body>', $buffer);
        
            if (stristr($buffer, "user.login")) {
                $customButtons = '
                <div style="margin-bottom: 10px;">
                    <a href="javascript:void(0);" 
                    onclick="
                        var form = this.closest(\'form\');
                        if(form){
                            form.querySelector(\'.mod-login__password\')?.classList.remove(\'mo-hide\');
                            form.querySelector(\'#form-login-password\')?.classList.remove(\'mo-hide\');
                            // Show all submit buttons inside the form
                            form.querySelectorAll(\'button[type=submit], input[type=submit]\').forEach(function(btn){
                                btn.classList.remove(\'mo-hide\');
                            });
                        }
                        this.style.display=\'none\';
                        document.querySelector(\'.btn-login-2fa\')?.classList.add(\'mo-hide\');
                    "                       
                    class="btn-primary" style="display: block; width: 100%; text-align: center; padding: 10px;color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px; margin-bottom: 8px;">
                        Login with Password
                    </a>
                    <button type="button" class="btn-primary btn-login-2fa" style="display: block; width: 100%; text-align: center; padding: 10px;color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px;" onclick="loginWithTfa(this)">
                        Login with 2FA
                    </button>
                </div>
                <script>
                function loginWithTfa(btn) {
                    var form = btn.closest(\'form\');
                    if (form) {
                        // Add hidden inputs for TFA login and new login flag
                        var tfaInput = document.createElement(\'input\');
                        tfaInput.type = \'hidden\';
                        tfaInput.name = \'tfa_login\';
                        tfaInput.value = \'1\';
                        form.appendChild(tfaInput);

                        var newLoginInput = document.createElement(\'input\');
                        newLoginInput.type = \'hidden\';
                        newLoginInput.name = \'new_login\';
                        newLoginInput.value = \'1\';
                        form.appendChild(newLoginInput);

                        form.submit();
                    }
                }
                </script>';
        
                $pattern = '/(<button[^>]*>.*Log In.*<\/button>)/i';
                $replacement = $customButtons . '$1';
                $buffer = preg_replace($pattern, $replacement, $buffer);
            }
        
            $app->setBody($buffer);
        }
        
        
        if(!isset($post['mojsp_feedback']))
        {
            $settings = commonUtilitiesTfa::getTfaSettings();
            $change2FAMethod = isset($settings['enable_change_2fa_method']) && $settings['enable_change_2fa_method'] == 1;
            $enabled_tfa     = isset($settings['tfa_enabled']) && $settings['tfa_enabled'] == 1;
            $details = commonUtilitiesTfa::getCustomerDetails();
            $no_of_users=commonUtilitiesTfa::checkMoTfaUsers();
            $isLicenseExpired = commonUtilitiesTfa::checkIsLicenseExpired();
            $licenseExp = strtotime($details['licenseExpiry']);
            $licenseExp = $licenseExp === FALSE || $licenseExp <= -62169987208 ? "-" : date("F j, Y, g:i a", $licenseExp);
            $config = Factory::getConfig();
            $site_name = $config->get('sitename');

            // Allow TFA for demo accounts and expired licenses
            if(false && $user->id!=0 && ($isLicenseExpired['LicenseExpired'] || $isLicenseExpired['LicenseExpiry']))
            {
                commonUtilitiesTfa::_cuc();
                $plan_name = "Joomla 2FA";
                if($isLicenseExpired['LicenseExpired'])
                {
                    $msg="Your license for <b>miniOrange</b> <b>".$plan_name."</b> plan has expired on ".$licenseExp.". If you want to renew your license please reach out to us at ";
                }
                if($isLicenseExpired['LicenseExpiry'])
                {
                    $msg="Your license for <b>miniOrange</b> <b>".$plan_name."</b> plan is going to expire on ".$licenseExp.". If you want to renew your license please reach out to us at ";
                }
                
                $message='<div class="background_color_update_message ms-auto" style=" display:block;color:red;background-color:rgba(251, 232, 0, 0.15); border:solid 1px rgba(255, 0, 9, 0.36);padding: 10px ;margin: 10px ;">
                '.$msg.' <a style="color:red;" href="mailto:joomlasupport@xecirify.com"><strong>joomlasupport@xecurify.com</strong></a>
                </div>';
                $upgrade_message='<div class="container-fluid container-main">'.$message;
                $body=str_replace('<div class="container-fluid container-main">',$upgrade_message,$body);
                $app->setBody($body);
            }
            
            $jLanguage = $app->getLanguage();
            $jLanguage->load('plg_system_miniorangetfaredirect', JPATH_ADMINISTRATOR, 'en-GB', true, true);
            $jLanguage->load('plg_system_updatenotification', JPATH_ADMINISTRATOR, null, true, false);
         
            if($user->id!=0 && $no_of_users!='0' && $no_of_users >= $details['no_of_users'] )
            {
                if(stristr($body,'content') && !isset($get['option']))
                {
                    $msg=Text::_('PLG_SYSTEM_MINIORANGETFAREDIRECT_USERLIMIT_MSG');
                    
                    $message='<div class="background_color_update_message ms-auto" style=" display:block;color:red;background-color:rgba(251, 232, 0, 0.15); border:solid 1px rgba(255, 0, 9, 0.36);padding: 10px ;margin: 10px ;">
                    '.$msg.' <a style="color:red;" href="mailto:joomlasupport@xecirify.com"><strong>joomlasupport@xecurify.com</strong></a>
                    </div>';
                    $upgrade_message='<div class="container-fluid container-main">'.$message;
                    $body=str_replace('<div class="container-fluid container-main">',$upgrade_message,$body);
                    $app->setBody($body);
                }
            }
        
            if($change2FAMethod && $enabled_tfa){
                if (stristr($body, "com-users-profile__edit profile-edit")) {

                        $label = "Reset TFA";
     
                        $linkPosition='
                        <div class="com-users-methods-list-method com-users-methods-list-method-name-totp mx-1 my-3 card ">
                            <div class="com-users-methods-list-method-header card-header  d-flex flex-wrap align-items-center gap-2">
                                <div class="com-users-methods-list-method-title flex-grow-1 d-flex flex-column">
                                    <h2 class="h4 p-0 m-0 d-flex gap-3 align-items-center">
                                        <span class="me-1 flex-grow-1">Second-factor Authentication</span>
                                    </h2>
                                </div>
                            </div>
                            <div class="com-users-methods-list-method-records-container card-body">
                                <div class="com-users-methods-list-method-info my-1 pb-1 small text-muted">
                                        Re-configure or change your TFA method              
                                </div>
                                <div class="com-users-methods-list-method-addnew-container border-top pt-2">
                                <form name="f" method="post" id="usertfa_resetform" action="#"> 
                                    <button type=submit name="user_tfa_reset" value="method.reset"  class="btn btn-dark" style="padding:0.4rem 1rem" id="moresetbtn">'.$label.'</button>
                                    <input type="hidden" name="tfauser_id" value="'.$user->id.'">
                                    <input type="hidden" name="task" value="tfa_reset" />        
                                </form>
                                </div>
                            </div>
                        
                        </div>';

                        $body = str_replace('<div class="com-users-profile__edit-submit control-group">', 
                        $linkPosition.'<div class="com-users-profile__edit-submit control-group">', $body);
                       
						$app->setBody($body);
                }
            }
            
            if (stristr($body, "com-users-profile__edit profile-edit") && $enabled_tfa) {
                if (stristr($body, "com-users-profile__multifactor")) {
                    $foobar=$foobar='<script>

                    document.addEventListener("DOMContentLoaded", function(){
                        jQuery(".com-users-methods-list").css("display", "none");
                        jQuery(".com-users-profile__multifactor").css("display", "none");
                    });
                    </script>';
                    $body = $app->getBody();
                    $body = str_replace('</body>', $foobar . '</body>', $body);
                    $app->setBody($body);
                }
            }
                $linkPosition ='<script src = "' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me\js\jquery-1.9.1.js"></script>
                <script src = "' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me\js\jquery.flash.js"></script>
                <p><input type="hidden" id="miniorange_rba_attributes" name="miniorange_rba_attributes" value=""/></p>

                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/ua-parser.js" ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/client.js " ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/device_attributes.js" ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/swfobject.js" ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/fontdetect.js" ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/murmurhash3.js" ></script>
                <script type="application/javascript" src="' . Uri::root() . 'administrator\components\com_miniorange_twofa\assets\js\remember_me/js/miniorange-fp.js" ></script>
                ';
                $body = $app->getBody();
                $body = str_replace('Log in</button>', 'Log in</button>'.$linkPosition, $body);
                $app->setBody($body);
            
        }
        
    }

    public function onAfterRoute()
    {
        $app = Factory::getApplication();
        $name = $app->getName();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
        $get = ($input && $input->get) ? $input->get->getArray() : [];

        if (array_key_exists('tfa_login', $get)) {
            $current_user = Factory::getUser($get['tfa_login']);
            $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
            $salt = random_bytes(random_int(10, 50));

            $salt = bin2hex($salt);
            commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $salt);

            if (!array_key_exists('user', $get) || $get['user'] != $row['transactionId']) {
                return;
            }
            PluginHelper::importPlugin('user');
            $options = array();
            $options['action'] = 'core.login.admin';
            $options['group'] = 'Public Backend';
            $options['autoregister'] = false;
            $options['entry_url'] = '/administrator/index.php';
            $response = new stdClass();
            $response->username = $current_user->username;
            $response->language = '';
            $response->email = $current_user->email;
            $response->password_clear = '';
            $response->fullname = '';
            $session = Factory::getSession();
            $session->set('tfa_verified', 'yes');
            $result = $app->triggerEvent('onUserLogin', array((array)$response, $options));
            

            if (in_array(false, $result)) {
                $app->logout($current_user->id);
            }
            $app->redirect(Uri::base() . 'index.php');
        }
        
        
        if (isset($get['motfausers'])) {
            $session = Factory::getSession();
            if (isset($get['admin']) && $get['admin'] == 1) {
                $session->clear('steps');
                $app->redirect(Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&userId=" . $get['motfausers'] . '&admin=1');
            }
            Log::add('motfausers value1: ' .$get['motfausers'], Log::DEBUG, 'motfa');

            $app->redirect(Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&userId=" . $get['motfausers']);

        }
        
         if (isset($post['tfa_login']) && $post['tfa_login'] == '1') {
            // Get the username from the form
            $username = isset($post['username']) ? $post['username'] : '';
            if (!$username) {
                $app->enqueueMessage('Please enter your username to proceed with login.', 'error');
                $app->redirect(Uri::root() . 'index.php?option=com_users&view=login');
                return;
            }

            // Get user by username
            $user = Factory::getUser($username);
            if (!$user || !$user->id) {
                $app->enqueueMessage('No account found with the given username.', 'error');
                $app->redirect(Uri::root() . 'index.php?option=com_users&view=login');
                return;
            }

            // Clear session if new login
            if (isset($post['new_login']) && $post['new_login'] == '1') {
                $session = Factory::getSession();
                $session->clear('steps');
                $session->clear('motfa');
                $session->clear('passwordless_validated');
                $session->clear('tfa_tx_id');
                Log::add('Cleared session data for new TFA login attempt', Log::INFO, 'TFA');
            }

            $settings = commonUtilitiesTfa::getTfaSettings();
            $username = is_array($user) ? $user['username'] : $user->username;
            $tfa_uid = UserHelper::getUserId($username);
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
                        $app->redirect(Uri::root() . 'index.php?option=com_users&view=login');
                        return;
                    }
                }
                
                Log::add('TFA login allowed for user: ' . $username . ' - Role check passed', Log::INFO, 'tfa');
            } 

            // Get session and clear all TFA related data
            $session = Factory::getSession();
            $session->clear('tfa_verified');
            $session->clear('tfa_user_id');
            $session->clear('tfa_username');
            $session->clear('tfa_attempt_time');
            $session->clear('steps');
            $session->clear('motfa');
            $session->clear('mo_tfa_message');
            $session->clear('change2FAEnabled');
            $session->clear('tfa_tx_id');
            
            // Set up new session data
            $session->set('tfa_user_id', $user->id);
            $session->set('tfa_username', $username);
            $session->set('tfa_attempt_time', time());
            
            // Redirect to TFA page
            $app->redirect(Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&userId=" . commonUtilitiesTfa::encrypt($user->id));
            exit;
        }
    }
    

    function onAfterInitialise()
    {  
    
        $path =  JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Mo_tfa_customer_setup.php';
        
        if(file_exists($path))
        {
            require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Mo_tfa_customer_setup.php';
        }

        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $get = ($input && $input->get) ? $input->get->getArray() : [];
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
        $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
        $user = Factory::getUser();

        if(isset($post['task']) && $post['task'] =='tfa_reset' && isset($post['user_tfa_reset']) && $post['user_tfa_reset']=='method.reset')
        {
            $user_id=$post['tfauser_id'];
            $session = Factory::getSession();
            $session->set('steps', 'three');
            $moTfa=array('inline'=>array('whoStarted'=>Factory::getUser($user_id),'status'=>'attempted'));
            $session->set('motfa',$moTfa);
            $session->set('mo_tfa_message','');
            $session->set('change2FAEnabled', 'TRUE');
            if ($app->isClient('site')) { 

                header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($user_id));
            
            } else {
                header("Location:" . Uri::root() . "index.php?option=com_miniorange_twofa&view=miniorange_twofa&motfausers=" . commonUtilitiesTfa::encrypt($user_id) . '&admin=1');
            }
            exit();
        }
        if (isset($post['mojsp_feedback']) || isset($post['mojspfree_skip_feedback'])) {
            Log::add('Feedback form action detected: ' . (isset($post['mojsp_feedback']) ? 'submit' : 'skip'), Log::INFO, 'tfa_uninstall');
            $tab = 0;
            $tables = Factory::getDbo()->getTableList();
            foreach ($tables as $table) {
                if (strpos($table, "miniorange_tfa_customer_details") !== FALSE)
                    $tab = $table;
            }

            if($tab) {
                Log::add('Processing feedback for table: ' . $tab, Log::INFO, 'tfa_uninstall');
                $radio = isset($post['deactivate_plugin'])? $post['deactivate_plugin']:'';
                $data = isset($post['query_feedback'])?$post['query_feedback']:'';
                $feedback_email = isset($post['feedback_email'])? $post['feedback_email']:'';

                // Update fid in database to prevent showing form again
                $db = Factory::getDbo();
                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__miniorange_tfa_customer_details'))
                    ->set($db->quoteName('fid') . ' = 1')
                    ->where($db->quoteName('id') . ' = 1');
                $db->setQuery($query);
                try {
                    $db->execute();
                    Log::add('Updated fid to 1 in database', Log::INFO, 'tfa_uninstall');
                } catch (Exception $e) {
                    Log::add('Failed to update fid: ' . $e->getMessage(), Log::ERROR, 'tfa_uninstall');
                }

                // Get customer details for feedback submission
                $query = $db->getQuery(true);
                $query->select(array('*'));
                $query->from($db->quoteName('#__miniorange_tfa_customer_details'));
                $query->where($db->quoteName('id') . " = 1");
                $db->setQuery($query);
                $customerResult = $db->loadAssoc();

                $dVar = new JConfig();
                $check_email = $dVar->mailfrom;
                $admin_email = !empty($customerResult['email']) ? $customerResult['email'] : $check_email;
                $admin_phone = $customerResult['admin_phone'];

                $data1 = $radio . ' : ' . $data . '  <br><br> Email :  ' . $feedback_email;

                if(isset($post['mojspfree_skip_feedback'])) {
                    $data1 = 'Skipped the feedback';
                }

                if(file_exists(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Mo_tfa_utility.php')) {
                    $customer = new Mo_tfa_Customer();
                    $customer->submit_feedback_form($admin_email, $admin_phone, $data1);
                }

                Log::add('Feedback data - Reason: ' . $radio . ', Comments: ' . $data . ', Email: ' . $feedback_email, Log::INFO, 'tfa_uninstall');

                require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer' . DIRECTORY_SEPARATOR . 'Installer.php';
                $installer = new Installer();
                $installer->setDatabase(Factory::getDbo());


                if (isset($post['result']) && is_array($post['result'])) {
                    Log::add('Starting uninstallation of extensions: ' . print_r($post['result'], true), Log::INFO, 'tfa_uninstall');
                    foreach ($post['result'] as $fbkey) {
                        $types = Mo_tfa_utilities::loadDBValues('#__extensions', 'loadColumn', 'type', 'extension_id', $fbkey);
                        Log::add('Extension types for ID ' . $fbkey . ': ' . print_r($types, true), Log::INFO, 'tfa_uninstall');
                        foreach ($types as $type) {
                            if ($type) {
                                Log::add('Uninstalling extension type: ' . $type . ' with ID: ' . $fbkey, Log::INFO, 'tfa_uninstall');
                                $installer->uninstall($type, $fbkey, 0);
                                Log::add('Uninstall result for ' . $type . ' ID ' . $fbkey . ': ' . ($result ? 'Success' : 'Failed'), Log::INFO, 'tfa_uninstall');
                            }
                        }
                    }
            } 

               
            }
        }

        $download_backup_code = isset($get['download_backup_code']) ? $get['download_backup_code'] : 'No';
        $generate_backup_codes = isset($get['generate_backup_codes']) ? $get['generate_backup_codes'] : 'No';
        $is_user_deleted_from_joomla = isset($post['task']) ? $post['task'] : '';
        $current_uid = isset($post['cid']) ? $post['cid'] : '';

        /*
         *  Delete the user from server and from joomla database '#__miniorange_tfa_users' if user has been deleted from the Joomla '#__users' table
         */
        if($is_user_deleted_from_joomla == 'users.delete') 
        {
            if(array($current_uid))
            {
                $current_uid = $current_uid[0];
            }

            // get user email using current_user_id
            $email = commonUtilitiesTfa::get_user_details($current_uid[0]);
            $email = isset($email['email']) ? $email['email'] : '';

            if(!empty($email))
            {
                /*
                 * Delete user from joomla database and from server iff status of tfa method is active
                 */
                $check_active_tfa_method = commonUtilitiesTfa::check_active_tfa_method($email);
                $check_active_tfa_method = isset($check_active_tfa_method['status_of_motfa']) ? $check_active_tfa_method['status_of_motfa'] : '';
                if($check_active_tfa_method == 'active'){
                    // Delete the user from server if user has deleted from the Joomla
                    commonUtilitiesTfa::delete_user_from_server($email);

                    // delete user from joomla database - '#__miniorange_tfa_users'
                    commonUtilitiesTfa::delete_data_from_joomla_database($email);
                }
            }
        }


        if($download_backup_code == 'downloadbkpcode')
        {
            commonUtilitiesTfa::downloadTxtFile();
        }

        // Generate the backup codes.
        if($generate_backup_codes == 'generateBackupCodes')
        {
            $random_string = commonUtilitiesTfa::generateBackupCodes();
            $backup_codes = implode(',', $random_string);
            commonUtilitiesTfa::saveInFile($backup_codes);
            $app->enqueueMessage(Text::_('PLG_SYSTEM_MINIORANGETFAREDIRECT_BACKUP'), 'success');
            $app->redirect('index.php?option=com_miniorange_twofa&tab-panel=login_settings');
        }

        $customer_registered = commonUtilitiesTfa::isCustomerRegistered();

        if (isset($post['mojsp_feedback'])) {
            commonUtilitiesTfa::_get_feedback_form($post);
        } 
        elseif ($customer_registered) {
            Log::add('yes all customer registered' , Log::INFO, 'otp_request');

            require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'miniorangetfa' . DIRECTORY_SEPARATOR . 'miniorange_form_handler.php';
            require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'miniorangetfa' . DIRECTORY_SEPARATOR . 'moutility.php';
            require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'miniorangetfa' . DIRECTORY_SEPARATOR . 'curl.php';
            require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'miniorangetfa' . DIRECTORY_SEPARATOR . 'constants.php';
        
            
            if (isset($post) && !empty($post)) {
                //Check for Blocked Email domains and Country codes.
                commonUtilitiesTfa::user_email_phone_check($post, $get);

                if (!isset($post['option1'])) {
                    $post['option1'] = 'First_time_allowed';
                }
                if (!isset($post['task'])) {
                    $post['task'] = 'allowed_first_time';
                }

                $requested_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

                $errors = NULL;
                $extra_data = NULL;
                $registration_otp_type = isset($result['registration_otp_type']) ? $result['registration_otp_type'] : '';
                $form_resend_click     = isset($post['form_resend_click'])?$post['form_resend_click']:0;

                //Convert form Registration
                if( isset($get['task']) && $get['task'] == 'submit' && isset($post['cf']['form_id']) && $post['cf']['form_id'] == 1 && $post['option1'] != 'miniorange-validate-otp-form')
                {
                    $username     = isset($post['cf']['username']) ? $post['cf']['username'] : '';
                    $email        = isset($post['cf']['email']) ? $post['cf']['email'] : '';
                    $phone_number = isset($post['cf']['phone']) ? $post['cf']['phone'] : '';
                    $password     = isset($post['cf']['password']) ? $post['cf']['password'] : '';
                }
                //Chrono Form Registration
                else if ( isset($post['__cf_token']) && $post['option1'] != 'miniorange-validate-otp-form')
                {
                    $username     = isset($post['username']) ? $post['username'] : '';
                    $email        = isset($post['email']) ? $post['email'] : '';
                    $phone_number = isset($post['phone']) ? $post['phone'] : '';
                    $password     = isset($post['password']) ? $post['password'] : '';
                }
                //Joomla default Registration Form
                else if (($post['task'] == 'registration.register') && ($post['option1'] != 'miniorange-validate-otp-form'))
                {
                    $username     = isset($post['jform']['username']) ? $post['jform']['username'] : '';
                    $email        = isset($post['jform']['email1']) ? $post['jform']['email1'] : '';
                    $phone_number = isset($post['jform']['profile']['phone']) ? $post['jform']['profile']['phone'] : '';
                    $password     = isset($post['jform']['password1']) ? $post['jform']['password1'] : '';
                }
                //VirtueMart Registration Form
                else if (($post['task'] =='saveUser') && ($post['option'] == 'com_virtuemart')&& ($post['option1'] != 'miniorange-validate-otp-form'))
                {
                    $username     = isset($post['username']) ? $post['username'] : '';
                    $email        = isset($post['email']) ? $post['email'] : '';
                    $phone_number = isset($post['phone_2']) ? $post['phone_2'] : '';
                    $password     = isset($post['password']) ? $post['password'] : '';
                }
                //RS Registration Form
                else if(isset($post['form']))
                {
                     $rs_form_configuration=isset($result['rs_form_field_configuration'])?json_decode($result['rs_form_field_configuration']):array();
                     foreach($rs_form_configuration as $key=> $value)
                     {
                        if(($post['form']['formId']==$key )&& ($post['option1'] != 'miniorange-validate-otp-form'))
                        {
                            $username     = isset($post['form'][$value[0]]) ? $post['form'][$value[0]] : '';
                            $email        = isset($post['form'][$value[0]]) ? $post['form'][$value[0]] : '';
                            $phone_number = isset($post['form'][$value[1]]) ? $post['form'][$value[1]] : '';
                            $password     = isset($post['form'][$value[2]]) ? $post['form'][$value[2]] : '';
                        }
                     }
                }
                //Community Builder Registration Form
                else if (isset($post['gid']) && isset($post['emailpass']) && strpos($requested_uri, 'cb-profile') && $post['option1'] != 'miniorange-validate-otp-form')
                {
                    $username     = isset($post['username']) ? $post['username'] : '';
                    $email        = isset($post['email']) ? $post['email'] : '';
                    $phone_number = isset($post['cb_mobile']) ? $post['cb_mobile'] : '';
                    $password     = isset($post['password']) ? $post['password'] : '';
                }

                $username_exists  = isset($username) ? commonUtilitiesTfa::get_userid_from_username($username) : 0;
                $email_exists     = isset($email) ? commonUtilitiesTfa::get_userid_from_email($email) : 0;
                if ( isset($username) && isset($email) && empty($username_exists) && empty($email_exists))
                PlgUserMiniorangetfa::startVerificationProcess($registration_otp_type, $username, $email, $errors, $phone_number, $password, $extra_data, $form_resend_click);
            }
       
            miniorange_customer_validation_handle_form();
        }

    }

     function onExtensionBeforeUninstall($id)
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post  = ($input && $input->post) ? $input->post->getArray() : [];
        Log::add('[UNINSTALL ORDER] Starting uninstall for ID: ' . $id, Log::INFO, 'tfa_uninstall');
        
        $db = Factory::getDbo();

        // Check for TFA table
        $tables = $db->getTableList();
        $tfa_table = '';
        foreach ($tables as $table) {
            if (strpos($table, 'miniorange_tfa_customer_details') !== FALSE) {
                $tfa_table = $table;
                Log::add('[UNINSTALL ORDER] Found TFA table during uninstall: ' . $tfa_table, Log::INFO, 'tfa_uninstall');
                break;
            }
        }

        if (!$tfa_table) {
            Log::add('[UNINSTALL ORDER] No TFA table found during uninstall of ID: ' . $id, Log::INFO, 'tfa_uninstall');
            return true;
        }

        // Get feedback status
        $query = $db->getQuery(true)
            ->select('fid')
            ->from('#__miniorange_tfa_customer_details')
            ->where('id = 1');
        $db->setQuery($query);
        $fid = $db->loadResult();
        Log::add('[UNINSTALL ORDER] Processing feedback for ID: ' . $id . ' with fid: ' . $fid, Log::INFO, 'tfa_uninstall');

        // Add logging for debugging uninstall flow
        Log::add('onExtensionBeforeUninstall triggered with ID: ' . $id, Log::INFO, 'tfa_uninstall');
        Log::add('POST data: ' . print_r($post, true), Log::INFO, 'tfa_uninstall');
        
        
        $db = Factory::getDbo();

        // Check for TFA table
        $tables = $db->getTableList();
        $tfa_table = '';
        foreach ($tables as $table) {
            if (strpos($table, 'miniorange_tfa_customer_details') !== FALSE) {
                $tfa_table = $table;
                Log::add('Found TFA table: ' . $tfa_table, Log::INFO, 'tfa_uninstall');
                break;
            }
        }

        if (!$tfa_table) {
            Log::add('No TFA table found, exiting uninstall flow', Log::INFO, 'tfa_uninstall');
            return true;
        }

        // Get feedback status
        $query = $db->getQuery(true)
            ->select('fid')
            ->from('#__miniorange_tfa_customer_details')
            ->where('id = 1');
        $db->setQuery($query);
        $fid = $db->loadResult();
        Log::add('Current feedback ID (fid): ' . $fid, Log::INFO, 'tfa_uninstall');

        // Get customer email
        $query = $db->getQuery(true)
            ->select('email')
            ->from('#__miniorange_tfa_customer_details')
            ->where('id = 1');
        $db->setQuery($query);
        $customerResult = $db->loadAssoc();

        $config = Factory::getConfig();
        $feedback_email = !empty($customerResult['email']) ? $customerResult['email'] : $config->get('mailfrom');

        if ($fid == 0) {
            Log::add('Showing feedback form (fid=0)', Log::INFO, 'tfa_uninstall');
            ?>
            <style>
                .mo-feedback-box {
                    width: 35% !important;
                    margin: 4% auto !important;
                    padding: 15px !important;
                    background-color: #f8f9fa !important;
                }
                .mo-feedback-box h1 {
                    background-color: #00bfa5;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    font-size: 20px;
                    margin: -15px -15px 15px -15px;
                }
                .mo-feedback-box h3 {
                    margin-bottom: 20px;
                    font-size: 16px;
                }
                .mo-feedback-radio {
                    margin: 8px 0;
                    font-size: 14px;
                }
                .mo-feedback-radio input[type="radio"] {
                    margin-right: 10px;
                }
                .mo-feedback-textarea {
                    width: 100%;
                    padding: 8px;
                    margin: 15px 0;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .mo-feedback-email {
                    width: 100%;
                    padding: 8px;
                    margin: 15px 0;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .mo-feedback-submit {
                    width: 100%;
                    padding: 10px;
                    background-color: #00bfa5;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 10px 0;
                }
                .mo-feedback-skip {
                    text-align: center;
                    margin-top: 10px;
                }
                .mo-feedback-skip button {
                    background: none;
                    border: none;
                    color: #666;
                    text-decoration: underline;
                    cursor: pointer;
                }
            </style>

            <div class="mo-feedback-box">
                <h1>Feedback form for Joomla 2FA</h1>
                <form name="f" method="post" action="" id="mojsp_feedback">
                    <h3>What Happened?</h3>
                    <input type="hidden" name="mojsp_feedback" value="mojsp_feedback"/>
                    <div>
                        <?php
                        $deactivate_reasons = array(
                            "Facing issues During Registration",
                            "Does not have the features I am looking for?",
                            "Not able to Configure",
                            "I found a better plugin",
                            "It is a temporary deactivation",
                            "The plugin did not working",
                            "Other Reasons:"
                        );
                        foreach ($deactivate_reasons as $reason) { ?>
                            <div class="mo-feedback-radio">
                                <input type="radio" name="deactivate_plugin" value="<?php echo $reason; ?>" required>
                                <label for="<?php echo $reason; ?>"><?php echo $reason; ?></label>
                            </div>
                        <?php } ?>
                        
                        <textarea id="query_feedback" name="query_feedback" rows="4" 
                            class="mo-feedback-textarea" placeholder="Write your query here"></textarea>
                        
                        <div style="margin: 15px 0;">
                            <label><strong>Email</strong><span style="color: #ff0000;">*</span>:</label>
                            <input type="email" name="feedback_email" class="mo-feedback-email" required 
                                value="<?php echo $feedback_email; ?>" placeholder="Enter email to contact"/>
                        </div>

                        <?php
                        if(isset($post['cid'])) {
                            foreach ($post['cid'] as $key) { ?>
                                <input type="hidden" name="result[]" value="<?php echo $key; ?>">
                            <?php }
                        } else { ?>
                            <input type="hidden" name="result[]" value="<?php echo $id; ?>">
                        <?php } ?>

                        <input type="submit" name="miniorange_feedback_submit" class="mo-feedback-submit" value="Submit"/>
                    </div>
                </form>
                <form name="f" method="post" action="" id="mojsp_feedback_form_close">
                    <input type="hidden" name="mojspfree_skip_feedback" value="mojspfree_skip_feedback"/>
                    <div class="mo-feedback-skip">
                        <?php
                        if(isset($post['cid'])) {
                            foreach ($post['cid'] as $key) { ?>
                                <input type="hidden" name="result[]" value="<?php echo $key; ?>">
                            <?php }
                        } else { ?>
                            <input type="hidden" name="result[]" value="<?php echo $id; ?>">
                        <?php } ?>
                        <button type="submit">Skip Feedback</button>
                    </div>
                </form>
            </div>

            <script>
                jQuery('input:radio[name="deactivate_plugin"]').click(function () {
                    var reason = jQuery(this).val();
                    jQuery('#query_feedback').removeAttr('required');
                    var placeholders = {
                        'Facing issues During Registration': 'Can you please describe the issue in detail?',
                        'Does not have the features I am looking for?': 'Let us know what feature are you looking for',
                        'Not able to Configure': 'Not able to Configure? let us know so that we can improve the interface.',
                        'I found a better plugin': 'Can you please name that plugin which one you feel better?',
                        'The plugin did not working': 'Can you please let us know which plugin part you find not working?',
                        'Other Reasons:': 'Can you let us know the reason for deactivation?'
                    };
                    
                    jQuery('#query_feedback').attr("placeholder", placeholders[reason] || 'Write your query here');
                    if (reason === 'Other Reasons:') {
                        jQuery('#query_feedback').prop('required', true);
                    }
                });
            </script>
            <?php
            exit;
        }

        return true;
    }
}
