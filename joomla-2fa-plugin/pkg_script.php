<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;   
use Joomla\CMS\Factory;

/**
 * @package     Joomla.Package
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
class pkg_MINIORANGETFAInstallerScript
{
    /**
     * This method is called after a component is installed.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function install($parent) 
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_twofa/helpers/Mo_tfa_utility.php';
        $siteName = $_SERVER['SERVER_NAME'];
        $currentUser = Factory::getUser();
        $currentUserEmail = $currentUser->email;         
        $moPluginVersion = commonUtilitiesTfa::GetPluginVersion();
        $jCmsVersion = commonUtilitiesTfa::getJoomlaCmsVersion();
        $phpVersion = phpversion();
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        $webServer = !empty($serverSoftware) ? trim(explode('/', $serverSoftware)[0]) : 'Unknown';
        $query1 = '[Plugin ' . $moPluginVersion . ' | PHP ' . $phpVersion .' | Joomla Version '. $jCmsVersion .' | Web Server '. $webServer .']';
        $content = '<div>
            Hello,<br><br>
            Plugin has been successfully installed on the following site.<br><br>
            <strong>Company:</strong> <a href="http://' . $siteName . '" target="_blank">' . $siteName . '</a><br>
            <strong>Admin Email:</strong> <a href="mailto:' . $currentUserEmail . '">' . $currentUserEmail . '</a><br>
            <strong>System Information:</strong> ' . $query1 . '<br><br>
        </div>';
        Mo_tfa_utilities::send_tfa_test_mail($currentUserEmail, $content);
    }

    /**
     * This method is called after a component is uninstalled.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function uninstall($parent) 
    {
        //echo '<p>' . Text::_('COM_HELLOWORLD_UNINSTALL_TEXT') . '</p>';
    }

    /**
     * This method is called after a component is updated.
     *
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function update($parent) 
    {
        //echo '<p>' . Text::sprintf('COM_HELLOWORLD_UPDATE_TEXT', $parent->get('manifest')->version) . '</p>';
    }

    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param  string    $type   - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function preflight($type, $parent) 
    {
        //echo '<p>' . Text::_('COM_HELLOWORLD_PREFLIGHT_' . $type . '_TEXT') . '</p>';
    }

    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param  string    $type   - Type of PostFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    function postflight($type, $parent) 
    {
       // echo '<p>' . Text::_('COM_HELLOWORLD_POSTFLIGHT_' . $type . '_TEXT') . '</p>';
       if ($type == 'uninstall') {
        return true;
        }
       $this->showInstallMessage('');
    }

    protected function showInstallMessage($messages=array()) {
        ?>
        <style>
        
	.mo-row {
		width: 100%;
		display: block;
		margin-bottom: 2%;
	}

	.mo-row:after {
		clear: both;
		display: block;
		content: "";
	}

	.mo-column-2 {
		width: 19%;
		margin-right: 1%;
		float: left;
	}

	.mo-column-10 {
		width: 80%;
		float: left;
	}

    .btn {
    display: inline-block;
    font-weight: 300;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: 4px 12px;
    font-size: 0.85rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .btn-cstm {
    background-color: #3D618F;
    border: 1px solid #3D618F;
    border-radius: var(--border-radius-sm, 0.25rem);
    font-size: 1.1rem;
    padding: 0.3rem 1.5rem;
    cursor: pointer;
    transition: all var(--transition-duration, 0.15s) ease-in-out;
    color: var(--white, #ffffff) !important;
    }

    .btn-cstm:hover {
        background: #3D618F;
        color: var(--white, #ffffff) !important;
        border: 1px solid #3D618F;
    }


    /* Dark background button styles */
    :root[data-color-scheme=dark] {
        .btn-cstm {
            color: white;
            background-color: #007DB0;
            border-color: 1px solid #ffffff;
        }

        .btn-cstm:hover {
            background-color: #007DB0;
            border-color: #ffffff;
        }
    }

    a[target=_blank]:before {
        display: none;
    }
    </style>
    	<div class="mo-row">
            <p>Plugin package for <strong>Two Factor Authentication 2FA</strong> for Joomla</p>
            <h3>What does this plugin do?</h3>
            <p>miniOrange Joomla Two Factor Authentication (TFA/MFA) is a security feature that requires users to provide further proof of identity when accessing a Joomla website.</p>
            <h3>Steps to use the Joomla Two Factor Authentication plugin.</h3>
            <ul>
            <li>Go to <b>Components</b> menu.</li>
            <li>Click on <b>miniOrange Multi-Factor Authentication MFA</b> and select <b>Account Setup </b>tab.</li>
            <li>You can login with miniOrange account credentials to activate the plugin.</li>
            <li>Now you can start configuring.</li>
            </ul>
            <a class="btn btn-cstm"  href="index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=account_setup">Start Using miniOrange 2FA plugin</a>
            <a class="btn btn-cstm"  href="https://plugins.miniorange.com/joomla-two-factor-authentication-2fa" target="_blank">Read the miniOrange documents</a>
            <a class="btn btn-cstm"  href="https://plugins.miniorange.com/joomla-sso-ldap-mfa-solutions?section=mfa" target="_blank">Setup Guides</a>
		    <a class="btn btn-cstm"  href="https://www.miniorange.com/contact" target="_blank">Get Support!</a>
        </div>
        <?php
    }
  
}