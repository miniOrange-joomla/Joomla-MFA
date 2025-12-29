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
	/**
	 * Interface class that's extended by the email nd phone logic classes.
	 * It defines some of the common actions and functions for each of those 
	 * classes. 
	 */
	abstract class LogicInterface
	{
		// Some abstract functions that needs to implemented by each logic class
		abstract public function _handle_logic($user_login,$user_email,$phone_number,$otp_type,$from_both);
		
		abstract public function _handle_otp_sent($user_login,$user_email,$phone_number,$otp_type,$from_both,$content);
		abstract public function _handle_otp_sent_failed($user_login,$user_email,$phone_number,$otp_type,$from_both,$content);
		abstract public function _get_otp_sent_message();
		abstract public function _get_otp_sent_failed_message();
		abstract public function _get_otp_invalid_format_message();
		abstract public function _get_is_blocked_message();
		abstract public function _handle_matched($user_login,$user_email,$phone_number,$otp_type,$from_both);
		abstract public function _handle_not_matched($phone_number,$otp_type,$from_both);
		abstract public function _start_otp_verification($user_login,$user_email,$phone_number,$otp_type,$from_both);
	}