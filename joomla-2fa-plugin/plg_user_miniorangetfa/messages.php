<?php

/**
 * @package     Joomla.User	
 * @subpackage  plg_user_miniorangetfa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
$lang = Factory::getLanguage();
$lang->load('plg_user_miniorangetfa', JPATH_ADMINISTRATOR);


/**
 * This is the constant class which lists all the messages
 * to be shown in the plugin.
 */
class MoMessages
{
    function __construct()
    {
        //created an array instead of messages instead of constant variables for Translation reasons.
        define("MO_MESSAGES", serialize( array(
            //General Messages
            "OTP_SENT_PHONE" 		 => Text::_('PLG_USER_CONSTANT_OTP_SENT_PHONE') ,
            "OTP_SENT_EMAIL"         => Text::_('PLG_USER_CONSTANT_OTP_SENT_EMAIL') ,
            "OTP_SENT_EMAIL_OR_PHONE"=> Text::_('PLG_USER_CONSTANT_OTP_SENT_EMAIL_OR_PHONE') ,
            "ERROR_OTP_EMAIL" 		 => Text::_('PLG_USER_CONSTANT_ERROR_OTP_EMAIL') ,
            "ERROR_OTP_PHONE" 		 => Text::_('PLG_USER_CONSTANT_ERROR_OTP_PHONE') ,
            "ERROR_OTP_EMAIL_OR_PHONE"=>Text::_('PLG_USER_CONSTANT_ERROR_OTP_EMAIL_OR_PHONE'),
            "ERROR_PHONE_FORMAT" 	 => Text::_('PLG_USER_CONSTANT_ERROR_PHONE_FORMAT') ,
            "CHOOSE_METHOD" 		 => Text::_('PLG_USER_CONSTANT_CHOOSE_METHOD') ,
            "ERROR_PHONE_BLOCKED"	 => Text::_('PLG_USER_CONSTANT_ERROR_PHONE_BLOCKED') ,
            "ERROR_EMAIL_BLOCKED"	 => Text::_('PLG_USER_CONSTANT_ERROR_EMAIL_BLOCKED') ,
            "EMAIL_FORMAT"	         => Text::_('PLG_USER_CONSTANT_EMAIL_FORMAT') ,
            "COMMON_MESSAGES"        => Text::_('PLG_USER_CONSTANT_COMMON_MSG'),

            //License Messages
            "UPGRADE_MSG" 			 => Text::_('PLG_USER_UPGRADE_MSG') ,
            "FREE_PLAN_MSG" 		 => Text::_('PLG_USER_FREE_PLAN_MSG') ,
        )));
    }

    /**
     * This function is used to fetch and process the Messages to
     * be shown to the user. It was created to mostly show dynamic
     * messages to the user.
     */
    public static function showMessage($messageKeys , $data=array())
    {
        $displayMessage = "";
        $messageKeys = explode(" ",$messageKeys);
        $messages = unserialize(MO_MESSAGES);
        foreach ($messageKeys as $messageKey)
        {
            if(MoUtility::isBlank($messageKey)) return $displayMessage;
            $formatMessage = $messages[$messageKey];
            foreach($data as $key => $value)
            {
                $formatMessage = str_replace("{{" . $key . "}}", $value ,$formatMessage);
            }
            $displayMessage.=$formatMessage;
        }
        return $displayMessage;
    }
}
new MoMessages;