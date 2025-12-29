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
jimport('miniorangetfa.utility.commonUtilitiesTfa');

/**
 * This class handles the logic for  OTP over Email or SMS Verification
 * Process the email address or contact number and starts the verification process.
 */
class EmailOrPhoneLogic extends LogicInterface
{
    /**
     * This function is called to handle Email or phone Verification request. Processes
     * the request and starts the OTP Verification process.
     *
     * @param $user_login 	- username of the user
     * @param $user_email 	- email of the user
     * @param $phone_number - phone number of the user
     * @param $otp_type 	- email or sms verification
     * @param $from_both 	- has user enabled from both
     */
    public function _handle_logic($user_login,$user_email,$phone_number,$otp_type,$from_both,$resend_otp=0)
    {
        $this->_start_otp_verification($user_login,$user_email,$phone_number,$otp_type,$from_both,$resend_otp);
    }

    /**
     * This function is called to handle Email or Phone Verification request. Processes
     * the request and starts the OTP Verification process to send OTP to user's
     * email address.
     *
     * @param $user_login 	- username of the user
     * @param $user_email 	- email of the user
     * @param $phone_number - phone number of the user
     * @param $otp_type 	- email or sms verification
     * @param $from_both 	- has user enabled from both
     */
    public function _start_otp_verification($user_login,$user_email,$phone_number,$otp_type,$from_both,$resend_otp=0)
    {
        $session = Factory::getSession();
        $counter=$session->get('both-refresh');
        $temp =$session->get('reset-refresh');
        $temp=($temp==NULL)?0:$temp;

        if($counter==NULL)
        {
            $session->set('both-refresh',1);
            $content =  MoOTPConstants::MO_TEST_MODE ? array('status'=>'SUCCESS','txId'=> MoUtility::rand())
                : json_decode(MocURLOTP::mo_send_otp_token('BOTH',$user_email,$phone_number), true);
            $session->set('transaction_id_email',$content['txId']);
        }
        else if($resend_otp!=0&&($temp==NULL||$resend_otp!=$temp))
        {
            $session->set('reset-refresh',$temp+1);
            $content =  MoOTPConstants::MO_TEST_MODE ? array('status'=>'SUCCESS','txId'=> MoUtility::rand())
                : json_decode(MocURLOTP::mo_send_otp_token('BOTH',$user_email,$phone_number), true);
            $session->set('transaction_id_email',$content['txId']);
        }
        else
        {
            $content=array('status'=>'SUCCESS','txId'=> $session->get('transaction_id_email'));
        }

        $otp_status = $content['status'] ?? 'FAILED';
        $session->set('otp_status', $otp_status);
        switch ($content['status'])
        {
            case 'SUCCESS':
                $this->_handle_otp_sent($user_login,$user_email,$phone_number,$otp_type,$from_both,$content);
                break;

            default:
                $this->_handle_otp_sent_failed($user_login,$user_email,$phone_number,$otp_type,$from_both,$content);
                break;
        }
    }

    /**
     * This function is called to handle what needs to be done when OTP sending is successful.
     * Checks if the current form is an AJAX form and decides what message has to be
     * shown to the user.
     *
     * @param $user_login 	- username of the user
     * @param $user_email 	- email of the user
     * @param $phone_number - phone number of the user
     * @param $otp_type 	- email or sms verification
     * @param $from_both 	- has user enabled from both
     * @param $content 		- the json decoded response from server
     */
    public function _handle_otp_sent($user_login,$user_email,$phone_number,$otp_type,$from_both,$content)
    {
        MoUtility::checkSession();
        $session = Factory::getSession();
        $session->set('test', $content['txId']);
        $target = ["##phone##", "##email##"];
        $replace = [$phone_number, $user_email];
        $message = str_replace( $target, $replace, $this->_get_otp_sent_message());
        miniorange_site_otp_validation_form($user_login, $user_email,$phone_number,$message,$otp_type,$from_both);
    }

    /**
     * This function is called to handle what needs to be done when OTP sending fails.
     * Checks if the current form is an AJAX form and decides what message has to be
     * shown to the user.
     *
     * @param $user_login 	- username of the user
     * @param $user_email 	- email of the user
     * @param $phone_number - phone number of the user
     * @param $otp_type 	- email or sms verification
     * @param $from_both 	- has user enabled from both
     * @param $content 		- the json decoded response from server
     */
    public function _handle_otp_sent_failed($user_login,$user_email,$phone_number,$otp_type,$from_both,$content)
    {
        $target = ["##phone##", "##email##"];
        $replace = [$phone_number, $user_email];
        $message = str_replace( $target, $replace, $this->_get_otp_sent_failed_message());
        miniorange_site_otp_validation_form(null,null,null,$message,$otp_type,$from_both);
    }

    /**
     * Get the success message to be shown to the user when OTP was sent
     * successfully. If admin has set his own unique message then
     * show that to the user instead of the default one.
     */
    public function _get_otp_sent_message()
    {
        return MoMessages::showMessage('OTP_SENT_EMAIL_OR_PHONE');
    }

    /**
     * Get the error message to be shown to the user when there was an
     * error sending OTP. If admin has set his own unique message then
     * show that to the user instead of the default one.
     */
    public function _get_otp_sent_failed_message()
    {
        return MoMessages::showMessage('ERROR_OTP_EMAIL_OR_PHONE');
    }

    /**
     * Function decides what message needs to be shown to the user when he enteres a
     * blocked email domain. It checks if the admin has set any message in the
     * plugin settings and returns that instead of the default one.
     */
    public function _get_is_blocked_message()
    {
        return MoMessages::showMessage('ERROR_EMAIL_BLOCKED');
    }

    /**
     * Get OTP Invalid email format. This is not required in context
     * to the email address and email verification. Can be extended
     * and used in the future.
     */
    public function _get_otp_invalid_format_message() { return; }


    /**
     * Function should handle what needs to be done if email/phone number
     * don't match the required format match the required format. This is not
     * required in context to the email address and email verification.
     * Can be extended and used in the future.
     */
    public function _handle_not_matched($phone_number,$otp_type,$from_both){ return; }


    /**
     * Function should handle what needs to be done if email/phone number
     * does match the required format. This is not required in context to
     * the email address and email verification. Can be extended and used in the future.
     */
    public function _handle_matched($user_login,$user_email,$phone_number,$otp_type,$from_both){ return; }
}
global $EmailOrPhoneLogic;
$EmailOrPhoneLogic = new EmailOrPhoneLogic();