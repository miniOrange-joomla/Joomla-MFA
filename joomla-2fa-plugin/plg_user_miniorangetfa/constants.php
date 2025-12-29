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

class MoOTPConstants
{
	const HOSTNAME				= "https://login.xecurify.com";
	const SUCCESS				= "SUCCESS";
	const AREA_OF_INTEREST		= "Joomla OTP Verification plugin";
	const APPLICATION_NAME		= "wp_otp_verification";
	const PATTERN_PHONE			= '/^[\+]\d{1,4}\d{7,12}$|^[\+]\d{1,4}[\s]\d{7,12}$/';
	const PATTERN_COUNTRY_CODE  = '/^[\+]\d{1,4}.*/';
	const PATTERN_SPACES_HYPEN 	= '/([\(\) -]+)/';
	const FORM_NONCE 			= "mo_form_settings";

	const MO_TEST_MODE          = False;
}
new MoOTPConstants;