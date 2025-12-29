<?php
defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin	
 * @subpackage  plg_authentication_miniorangeauthtfa
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;

$app = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('plg_authentication_miniorangeauthtfa', JPATH_ADMINISTRATOR, null, true);

class plgauthenticationminiorangeauthtfa extends CMSPlugin
{
    function onUserAuthenticate($credentials, $options, &$response)
    {
        $app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
        $licenseUtility = commonUtilitiesTfa::license_efficiency_check();
        $settings = commonUtilitiesTfa::getTfaSettings();
        $username = isset($post['username']) ? $post['username'] : '';
        $app = Factory::getApplication();

        Log::add('License check - licenseUtility: ' . json_encode($licenseUtility), Log::INFO, 'tfa');
        Log::add('License check - users condition: ' . (isset($licenseUtility['users']) ? 'true' : 'false') . ', value: ' . (isset($licenseUtility['users']) ? $licenseUtility['users'] : 'not set'), Log::INFO, 'tfa');
        Log::add('License check - plan_fetched condition: ' . (isset($licenseAttributes['plan_fetched']) ? 'true' : 'false') . ', value: ' . (isset($licenseAttributes['plan_fetched']) ? $licenseAttributes['plan_fetched'] : 'not set'), Log::INFO, 'tfa');

        if (
            (isset($licenseUtility['users']) && $licenseUtility['users'] == 1) ||
            (isset($licenseAttributes['plan_fetched']) && $licenseAttributes['plan_fetched'] == 1)
        ) {
            Log::add('License check failed - Condition triggered: ' . 
                ((isset($licenseUtility['users']) && $licenseUtility['users'] == 1) ? 'users limit exceeded' : 'plan fetch issue'), 
                Log::INFO, 'tfa');
            $app = Factory::getApplication();
            $response->status = Authentication::STATUS_FAILURE;
            $app->enqueueMessage(Text::_('PLG_AUTHENTICATION_MINIORANGETFA_CONFIGURE_TFA_ERROR'), 'warning');
            $app->redirect(Uri::root());
            return false;
        }
        Log::add('License check passed - proceeding with authentication', Log::INFO, 'tfa');

        $settings = commonUtilitiesTfa::getMoTfaSettings();
        $enable_otp_login = isset($settings['enable_tfa_passwordless_login']) ? (int)$settings['enable_tfa_passwordless_login'] : 0;
        
        if ($enable_otp_login === 1 && $app->isClient('site') && !empty($username)) {
            $user = Factory::getUser($username);
            
            if (!$user->id) {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_MINIORANGETFA_ERROR_MSG');
                return false;
            }
        
            if ($this->checkUserTfaConfigured($user->id)) {
                $response->status = Authentication::STATUS_SUCCESS;
                $response->error_message = '';
                return true;
            }
        }
        
        

        $login_with_second_factor_only = isset($settings['login_with_second_factor_only']) ? (int)$settings['login_with_second_factor_only'] : 0;

        if (!empty($post['Submit']) && trim($post['password'] ?? '') === 'password' && !empty(trim($post['username']))) {
            $result = commonUtilitiesTfa::get_user_from_joomla(trim($post['username']));
            
            if ($result) {
                $response->status = Authentication::STATUS_SUCCESS;
                $response->error_message = '';
                return true;
            }
            
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('PLG_AUTHENTICATION_MINIORANGETFA_ERROR_MSG');
            return false;
        }
        

        if ($login_with_second_factor_only) {
            $username = isset($credentials['username']) ? $credentials['username'] : '';
            if (!empty($username)) {
                $result = commonUtilitiesTfa::get_user_from_joomla($username);
                if ($result) {
                    $response->status = Authentication::STATUS_SUCCESS;
                    $response->error_message = '';
                    return true;
                }
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_MINIORANGETFA_ERROR_MSG');
                return false;
            }
        }
    }
    private function checkUserTfaConfigured($userId)
    {
        $settings = commonUtilitiesTfa::getTfaSettings();
        $enable_otp_login = isset($settings['enable_tfa_passwordless_login']) ? (int)$settings['enable_tfa_passwordless_login'] : 0;

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('active_method'))
            ->from($db->quoteName('#__miniorange_tfa_users'))
            ->where($db->quoteName('id') . ' = ' . (int) $userId); 
        $db->setQuery($query);
        
        $tfaConfigured = $db->loadResult();
        return !empty($tfaConfigured);
    }

}

