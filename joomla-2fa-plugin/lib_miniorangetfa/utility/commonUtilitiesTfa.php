<?php

/**
 * @package     Joomla.Library	
 * @subpackage  lib_miniorangetfa
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
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
class commonUtilitiesTfa
{

    static function mo_logs($log_msg)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT']."/log/log.log";
        $sizeInBytes = filesize($filePath);

        // Convert byte to kb upto 2 decimal
        $sizeInKb = number_format($sizeInBytes / 1024, 2);


        if($sizeInKb >= 256)
        {
            //Clean the file if the size is greater than or equal to 256kb
            file_put_contents($filePath, "");
        }

        $log_filename = $_SERVER['DOCUMENT_ROOT']."/log";
        if (!file_exists($log_filename))
        {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($filePath, var_export($log_msg, true). "\n", FILE_APPEND);
    }

    public static function __getDBValuesUsingColumns($col1_Name, $tableName,$condition=TRUE,$method="loadResult")
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($col1_Name);
        $query->from($db->quoteName($tableName));
        if ($condition !== TRUE){
            foreach ($condition as $key=>$value)
            {
                $query->where($db->quoteName($key) . " = " . $db->quote($value));
            }
        }
        $db->setQuery($query);
        switch ($method) {
            case 'loadColumn':
                return $db->loadColumn();
              break;
            case 'loadAssocList':
                return $db->loadAssocList();
                break;
            case 'loadObjectList':
                return $db->loadObjectList();
                break;
            case 'loadAssoc':
                return $db->loadAssoc();
                break;
            case 'loadObject':
                return $db->loadObject();
                break;
            case 'loadRow':
                return $db->loadRow();
                break;
            case 'loadRowList':
                return $db->loadRowList();
                break;
            default:
                return $db->loadResult();
            }
    }

    public static function __getDBProfileValues($uid,$profile_key)
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true);

        $conditions = array(
            $db->quoteName('profile_key') . ' = ' . $db->quote($profile_key),
            $db->quoteName('user_id') . ' = ' . $db->quote($uid)
        );
        

        $query->select('profile_value')->from($db->quoteName('#__user_profiles'))->where($conditions);
        $db->setQuery($query);

        $results = $db->loadResult();
        return $results;
    }

    public static function _getMaskedEmail($email)
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];
        
        $maskedUsername = substr($username, 0, 3) . '*****';
        $maskedDomain = substr($domain, strpos($domain, '.'));
        
        $email = $maskedUsername . $maskedDomain;
        return $email;
    }

    public static function checkIsLicenseExpired()
    {
        $content = self::getCustomerDetails();
        $licenseExpiry = isset($content['licenseExpiry']) ? $content['licenseExpiry'] : '0000-00-00 00:00:00';
        $days = intval((strtotime($licenseExpiry) - time()) / (60 * 60 * 24));
        
        $isLicenseExpired = array();
        $isLicenseExpired['LicenseExpiry'] = $days > 0 && $days < 31 ? TRUE : FALSE;
        $isLicenseExpired['LicenseExpired'] = $days > -365 && $days < 0 ? TRUE : FALSE;
        
        return $isLicenseExpired;
    }

    public static function _genericGetDBValues($table)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select(array('*'));
        $query->from($db->quoteName($table));
        $query->where($db->quoteName('id') . " = 1");
        $db->setQuery($query);
        $result = $db->loadAssoc();
        return $result;
    }

    public static function licenseExpiryDay()
    {
        $content = self::getCustomerDetails();
        $days = intval((strtotime($content['licenseExpiry']) - time()) / (60 * 60 * 24));
        return $days;
    }

    public static function licensevalidity($expire)
    {
        require_once JPATH_SITE . '/administrator/components/com_miniorange_twofa/helpers/Mo_tfa_customer_setup.php';

        $customer = new Mo_tfa_Customer();
        $licenseContent = json_decode($customer->fetchLicense(), true);
        $license_exp = $licenseContent['licenseExpiry'];
    

        if ($license_exp > $expire) {
            $db_table = '#__miniorange_tfa_customer_details';
            $db_coloums = array('licenseExpiry' => $license_exp);
            self::__genDBUpdate($db_table, $db_coloums);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public static function __genDBUpdate($db_table, $db_coloums)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        foreach ($db_coloums as $key => $value) {
            $database_values[] = $db->quoteName($key) . ' = ' . $db->quote($value);
        }

        $query->update($db->quoteName($db_table))->set($database_values)->where($db->quoteName('id') . " = 1");
        $db->setQuery($query);
        $db->execute();
    }

    public static function _update_lid($val)
    {

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
            $db->quoteName($val) . ' = ' . $db->quote(1)
        );

        $conditions = array(
            $db->quoteName('id') . ' = '. $db->quote(1)
        );

        $query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }


    public static function _cuc()
    {
        $content = self::_genericGetDBValues('#__miniorange_tfa_customer_details');

        $licenseExp = strtotime($content['licenseExpiry']);
        $licenseExp = $licenseExp === FALSE || $licenseExp <= -62169987208 ? "-" : date("F j, Y, g:i a", $licenseExp);

        //difference between expiry date and current time in days

        $day_diff = self::licenseExpiryDay();
        
        /*
         * Deactivate the plugin and remove the license key after 5 days of grace period
         */
        
        $config = Factory::getConfig();
        $site_name = $config->get('sitename');
        $plan_name = "Joomla 2FA";
        $subject = "License Expire of Joomla 2FA Plugin |" . $site_name ;

        $message_before_lexp = "Hello,<br /><br />Your license for <b>".$plan_name."</b> plan is going to expire on ".$licenseExp." for your website: <b>". $site_name ."</b>.<br /> <br /> Please renew your license as soon as possible to receive plugin updates providing security patches, bug fixes, new features, or even compatibility adjustments. If you want to renew your license please reach out to us at <b>joomlasupport@xecurify.com</b><br /><br />Thanks,<br />miniOrange Team";
        $message_after_lexp = "Hello,<br /><br />Your license for <b>".$plan_name."</b> plan has expired on ".$licenseExp." for your website: <b>". $site_name ."</b>.<br /> <br /> Please renew your license as soon as possible to receive plugin updates providing security patches, bug fixes, new features, or even compatibility adjustments. If you want to renew your license please reach out to us at <b>joomlasupport@xecurify.com</b><br /><br />Thanks,<br />miniOrange Team";

        if ($day_diff <=15  && $day_diff  > 5 &&  !$content['miniorange_fifteen_days_before_lexp'])            //15 days remaining    1296000: 15 days in seconds
        {
            if (!self::licensevalidity($licenseExp)) {
                self::_update_lid('miniorange_fifteen_days_before_lexp');
                json_decode(self::send_email_alert($subject, $message_before_lexp), true);
            
            }
        } else if ($day_diff <=5  && $day_diff  > 0 && !$content['miniorange_five_days_before_lexp'])            //5 days remaining    432000: 5 days in seconds
        {
            if (!self::licensevalidity($licenseExp)) {
                self::_update_lid('miniorange_five_days_before_lexp');
                json_decode(self::send_email_alert($subject, $message_before_lexp), true);
            }
        } else if ($day_diff <=0  && $day_diff  > -5 && !$content['miniorange_after_lexp'])            //on or after license expiry
        { 
            if (!self::licensevalidity($licenseExp)) {
                self::_update_lid('miniorange_after_lexp');
                json_decode(self::send_email_alert($subject, $message_after_lexp), true);
            }
        } else if ( $day_diff  == -5  && !$content['miniorange_after_five_days_lexp'])          // 5 days after expiry
        {
            if (!self::licensevalidity($licenseExp)) {
                self::_update_lid('miniorange_after_five_days_lexp');
                json_decode(self::send_email_alert($subject, $message_after_lexp), true);
            }
        }
     
    }

    public static function send_email_alert($subject, $message_content)
    {
        $hostname = self::getHostname();
        $url = $hostname . '/moas/api/notify/send';
        $ch = curl_init($url);
        $customer_details = self::_genericGetDBValues('#__miniorange_tfa_customer_details');

        $customerKey = $customer_details['customer_key'];
        $apiKey = $customer_details['api_key'];

        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $customerKey;
        $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader = "Authorization: " . $hashValue;

        $toEmail = $customer_details['email'];
        $fromEmail = 'joomlasupport@xecurify.com';

        $fields = array
        (
            'customerKey'   => $customerKey,
            'sendEmail'     => true,
            'email'         => array
            (
                'customerKey'   => $customerKey,
                'fromEmail'     => $fromEmail,
                'fromName'      => 'miniOrange',
                'toEmail'       => $toEmail,
                'toName'        => $toEmail,
                'bccEmail'      => $fromEmail,
                'subject'       => $subject,
                'content'       => $message_content
            ),
        );

        $field_string = json_encode($fields);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls

        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, $timestampHeader, $authorizationHeader));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        $content = json_decode(curl_exec($ch));

        if($content->status == 'SUCCESS'){
            self::_update_lid('miniorange_lexp_notification_sent');
        }

        if (curl_errno($ch)) {
            return json_encode(array("status" => 'ERROR', 'statusMessage' => curl_error($ch)));
        }
        curl_close($ch);
        
        return json_encode(array("status" => 'SUCCESS', 'statusMessage' => 'SUCCESS'));

    }

    public static function update_users_on_server($username,$new_user_email)
    {
        $hostname = self::getHostname();
        $url = $hostname . '/moas/api/admin/users/update';
        
        $customer_details = self::_genericGetDBValues('#__miniorange_tfa_customer_details');

        $customerKey = $customer_details['customer_key'];
        $apiKey = $customer_details['api_key'];

        $fields = array(
            'customerKey' => $customerKey,
            'username' => $username,
            'email' => $new_user_email,
            'transactionName' => 'Joomla 2FA Plugin'
        );
        $json_fields = json_encode($fields);

        $api = new MoTfa_api();
        $header= $api->get_http_header_array();
        return $api->make_curl_call($url, $json_fields, $header);

    }

    public static function get_user_on_server($username)
    {
        $hostname = self::getHostname();
        $url = $hostname . '/moas/api/admin/users/get';
        
        $customer_details = self::_genericGetDBValues('#__miniorange_tfa_customer_details');

        $customerKey = $customer_details['customer_key'];
        $apiKey = $customer_details['api_key'];

        $fields = array(
            'customerKey' => $customerKey,
            'username' => $username,
            'email' => $username,
            'transactionName' => 'Joomla 2FA Plugin'
        );
        $json_fields = json_encode($fields);

        $api = new MoTfa_api();
        $header= $api->get_http_header_array();
        return $api->make_curl_call($url, $json_fields, $header);

    }
    public static function check_active_tfa_method($email)
    {
        $db = Factory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('status_of_motfa')
            ->from($db->quoteName('#__miniorange_tfa_users'))
            ->where($db->quoteName('email') . " = " . $db->quote($email));
        $db->setQuery($query);
        $row=$db->loadAssoc();
        return $row;
    }

    public static function get_user_details($id){
        $db = Factory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('email')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . " = " . $db->quote($id));
        $db->setQuery($query);
        $row=$db->loadAssoc();
        return $row;
    }

    public static function delete_user_from_server($email)
    {
        $customerKeys = commonUtilitiesTfa::getCustomerKeys();
        $customerKey  = $customerKeys['customer_key'];
        
        $fields       = array
        (
            'customerKey' => $customerKey,
            'username'    => $email,
        );

        $api_urls = commonUtilitiesTfa::getApiUrls();
        $mo2fApi= new MoTfa_api();
        $http_header_array = $mo2fApi->get_http_header_array();
        return $mo2fApi->make_curl_call($api_urls['deleteUser'], $fields, $http_header_array);
    }

    public static function delete_user_from_joomla_database($email){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('email') . ' = ' . $db->quote($email)
        );
        $query->delete($db->quoteName('#__miniorange_tfa_users'));
        $query->where($conditions);
        $db->setQuery($query);
        return $db->execute();
    }
    public static function delete_rba_settings_from_database($user_id){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('user_id') . ' = ' . $db->quote($user_id)
        );
        $query->delete($db->quoteName('#__miniorange_rba_device'));
        $query->where($conditions);
        $db->setQuery($query);
        return $db->execute();
    }


    public static function is_curl_installed() 
    {
        if  (in_array  ('curl', get_loaded_extensions())) {
            return 1;
        } else
            return 0;
    }

    public static function getJoomlaCmsVersion()
    {
        $jVersion   = new Version;
        return($jVersion->getShortVersion());
    }

    public static function GetPluginVersion()
    {
        $db = Factory::getDbo();
        $dbQuery = $db->getQuery(true)
            ->select('manifest_cache')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . " = " . $db->quote('com_miniorange_twofa'));
        $db->setQuery($dbQuery);
        $manifest = json_decode($db->loadResult());
        return ($manifest->version);
    }

    public static function checkIsCurlInstalled()
    {
        if (!self::is_curl_installed()) { ?>
            <div id="help_curl_warning_title">
                <p><a target="_blank" style="cursor: pointer;"><font color="#FF0000"><?php Text::_('LIB_MINIORANGETFA_MSG_CURL');?>
                             <span style="color:blue"><?php Text::_('LIB_MINIORANGETFA_MSG_CLICK');?></span> <?php Text::_('LIB_MINIORANGETFA_MSG_CURL1');?>
                            </font></a></p>
            </div>
            <div hidden="" id="help_curl_warning_desc" class="mo_help_desc">
            <?php Text::_('LIB_MINIORANGETFA_MSG_BACKUP_DESC3');?>
            <?php Text::_('LIB_MINIORANGETFA_MSG_QUERY');?> <a href="mailto:joomlasupport@xecurify.com"><?php Text::_('LIB_MINIORANGETFA_MSG_CONTACT');?></a>.
            </div>
            <style>
                .mo_help_desc {
                    font-size:13px;
                    border-left:solid 2px rgba(128, 128, 128, 0.65);
                    margin-top:10px;
                    padding-left:10px;
                }
            </style>
            <?php
        }
    }
    public static function getCurrentUserID($current_user)
    { 
        
        $session = Factory::getSession();
        if(empty($current_user) || $current_user == '') {

            return $current_user_id = $session->get('juserId');
        } else{
            return $current_user_id = $current_user->id;
        }
        
    }

    public static function isIdPInstalled()
    {
        $arr = array('miniorangejoomlaidp', 'joomlaidplogin');

        foreach ($arr as $key)
        {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select('enabled');
            $query->from('#__extensions');
            $query->where($db->quoteName('element') . " = " . $db->quote($key));
            $query->where($db->quoteName('type') . " = " . $db->quote('plugin'));
            $db->setQuery($query);
            return($db->loadAssoc());
        }
    }

    public static function saveInFile($backup_codes)
    {
        $content_backup_code ='Two Factor Backup Codes:
        These are the codes that can be used in case you lose your phone or cannot access your email. Please reconfigure your authentication method after login.
        Please use this carefully as each code can only be used once. Please do not share these codes with anyone..'.PHP_EOL;
        $content_backup_code = $content_backup_code.PHP_EOL;
        $content_backup_code = $content_backup_code.$backup_codes.PHP_EOL;
        $filename = self::getFilePath('moBackupCode.txt');
        $file     = fopen($filename, 'w');
        fwrite($file, $content_backup_code);
        fclose($file);
    }
 

    public static function readBackupCodesFromFile()
    {
        $byteOffset = 303;
        $readLength = 256;

        $filename = self::getFilePath('moBackupCode.txt');
        if(file_exists($filename)){
            $fp = fopen($filename, "r");        
            fseek($fp, $byteOffset);
            $bytes = fread($fp, $readLength);
            return $bytes;
        }
        return '';
    }

    public static function getFilePath($bkpCodeFile){
        $fpath = JPATH_ADMINISTRATOR;
        $filep = substr($fpath, 0, strrpos($fpath, 'administrator'));
        $filepath = $filep.'libraries'.DIRECTORY_SEPARATOR.'miniorangetfa'.DIRECTORY_SEPARATOR.'utility'.DIRECTORY_SEPARATOR.$bkpCodeFile;       
        return 'Backup_Codes';
    }

    public static function downloadTxtFile()
    {
        $file_path = self::getFilePath('moBackupCode.txt');

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Expires: 0");
        header('Content-Disposition: attachment; filename='.basename($file_path));
        header('Content-Length: ' . (filesize($file_path)));
        header('Pragma: public');

        flush();
        ob_clean();
        readfile($file_path);
        unlink($file_path);
        die();
    }

    public static function generateBackupCodes()
    {
        $string = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($string);
        $randomStringArr = array();

        for($j = 1; $j <= 10; $j++){
            $randomString = '';
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $string[rand(0, $stringLength - 1)];
            }
            array_push($randomStringArr, $randomString);
        }

        return $randomStringArr;
    }

    public static function getMoTfaSettings()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_tfa_settings'));
        $db->setQuery($query);
        return $db->loadAssoc();
    }


    public static function get_user_from_joomla($username)
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true)
                    ->select('id')
                    ->from('#__users')
                    ->where('username=' . $db->quote($username));

        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;

    }

	public static function  is_extension_installed($extension_name){
		return in_array($extension_name, get_loaded_extensions());
	}

	public static function loadGroups(){
		$db = Factory::getDbo();
        $db->setQuery($db->getQuery(true)
            ->select('*')
            ->from("#__usergroups")
        );
        return  $db->loadAssocList();
	}

	public static function encrypt($str) {
		if(!self::is_extension_installed('openssl')) {
			return self::base64url_encode($str);
		}
		$key= self::getEncryptKey();
		$string= openssl_encrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
		return self::base64url_encode($string);
	}

	public static function isValidUid($id){
		$db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select('*')
    		->from($db->quoteName('#__users'))
    		->where($db->quoteName('id') . " = " . $db->quote($id));
		$db->setQuery($query);
		$row=$db->loadAssoc();

		if(is_null($row)){
			return FALSE;
		}
		return TRUE;
	}

	static function base64url_encode($data) {
  		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	static function base64url_decode($data) {
  		return base64_decode(strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 ));
	}

	public static function decrypt($value)
	{
		$value=self::base64url_decode($value);
		if(!self::is_extension_installed('openssl')) {
			return self::base64url_decode($value);
		}
		$key= self::getEncryptKey();
		$string = rtrim( openssl_decrypt($value, 'aes-128-ecb', $key, OPENSSL_RAW_DATA), "\0");
		return trim($string,"\0..\32");
	}

	public static function getEncryptKey(){
		$details = self::getCustomerDetails();
		$apiKey = empty($details['api_key'])?'j2faplufjebyu':$details['api_key'];
		$customerId = empty($details['customer_key'])?'j2faplufjebyu':$details['customer_key'];
		return $apiKey.$customerId;
	}

	public static function isCustomerRegistered(){
		$details = self::getCustomerDetails();
       
		return !(!isset($details['email']) || !isset($details['customer_key']) || !isset($details['api_key']) || !isset($details['customer_token']) || empty($details['email']) || empty($details['customer_key']) || empty($details['api_key']) || empty($details['customer_token']));

	}

	public static function isFirstUser($id){
		$details = self::getCustomerDetails();

		return $details['jid']==$id;
	} 

	public static function getCustomerDetails(){
		$db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select('*')
    		->from($db->quoteName('#__miniorange_tfa_customer_details'))
    		->where($db->quoteName('id') . " = " . $db->quote(1));
		$db->setQuery($query);
		$row=$db->loadAssoc();
		return $row;
	}

	public static function updateLicenseDetails($response,$email=NULL){
  
        if(!isset($response->licensePlan))
            return;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
        if($email!=NULL && $email == "demo2fauser1@xecurify.com")
        {
            $fields = array(
                $db->quoteName('license_type') . ' = '.$db->quote(isset($response->licenseType) ? $response->licenseType : ''),
                $db->quoteName('license_plan').' ='.$db->quote(isset($response->licensePlan) ? $response->licensePlan : ''),
                $db->quoteName('no_of_users').' ='.$db->quote(isset($response->noOfUsers) ? 5 : ''),
                $db->quoteName('smsRemaining') . ' = '.$db->quote(isset($response->smsRemaining) ? $response->smsRemaining : 0),
                $db->quoteName('emailRemaining') . ' = '.$db->quote(isset($response->emailRemaining) ? $response->emailRemaining : 0),
                $db->quoteName('supportExpiry') . ' = '.$db->quote(isset($response->supportExpiry) ? date('Y-M-d H:i:s', strtotime($response->supportExpiry)) : ''),
                $db->quoteName('licenseExpiry') . ' = '.$db->quote(isset($response->licenseExpiry) ? date('Y-M-d H:i:s', strtotime($response->licenseExpiry)) : ''),
            );
        }
        else
        {
            $fields = array(
                $db->quoteName('license_type') . ' = '.$db->quote(isset($response->licenseType) ? $response->licenseType : ''),
                $db->quoteName('license_plan').' ='.$db->quote(isset($response->licensePlan) ? $response->licensePlan : ''),
                $db->quoteName('no_of_users').' ='.$db->quote(isset($response->noOfUsers) ? $response->noOfUsers : ''),
                $db->quoteName('smsRemaining') . ' = '.$db->quote(isset($response->smsRemaining) ? $response->smsRemaining : 0),
                $db->quoteName('emailRemaining') . ' = '.$db->quote(isset($response->emailRemaining) ? $response->emailRemaining : 0),
                $db->quoteName('supportExpiry') . ' = '.$db->quote(isset($response->supportExpiry) ? date('Y-M-d H:i:s', strtotime($response->supportExpiry)) : '<span style="color:#FF0000;">Upgrade to licensed version</span>'),
                $db->quoteName('licenseExpiry') . ' = '.$db->quote(isset($response->licenseExpiry) ? date('Y-M-d H:i:s', strtotime($response->licenseExpiry)) : '<span style="color:#FF0000;">Upgrade to licensed version</span>'),
            );
        }

		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);

		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$db->execute();
	}

	public static function customerTfaDetails(){
		$details = self::getCustomerDetails();
		return self::getMoTfaUserDetails($details['jid']);
	}
 
	public static function getMoTfaUserDetails($id){
		$db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select('*')
    		->from($db->quoteName('#__miniorange_tfa_users'))
    		->where($db->quoteName('id') . " = " . $db->quote($id));
		$db->setQuery($query);
		$row=$db->loadAssoc();
		return $row;
	}
    public static function checkMoTfaUserDetails(){
		$db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select('*')
    		->from($db->quoteName('#__miniorange_tfa_users'));
		$db->setQuery($query);
		$row=$db->loadAssoc();
		return $row;
	}

    public static function checkMoTfaUsers(){
        $db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select($db->quoteName('id'))
    		->from($db->quoteName('#__miniorange_tfa_users'));
		$db->setQuery($query);
        $db->execute();
        $num_rows = $db->getNumRows();
		return $num_rows;
    }


    public static function insertMoTfaUser($jUsername,$id,$username,$email='',$phone=''){

        $c_user = Factory::getUser($id);
        $usergroup_id = $c_user->groups;
        $user_id = 1;
        foreach($usergroup_id as $key=>$value)
        {
            $user_id=$value;
        }
        
        $user_role = self::__getDBValuesUsingColumns('title', '#__usergroups',array('id' => $user_id,),$method="loadResult");
     
        $groups= self::loadGroups();
		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array('id','jUsername','username', 'email', 'phone','user_group');

		// Insert values.
		$values = array($id, $db->quote($jUsername),$db->quote($username), $db->quote($email), $db->quote($phone),$db->quote($user_role));

		// Prepare the insert query.
		$query
    		->insert($db->quoteName('#__miniorange_tfa_users'))
    		->columns($db->quoteName($columns))
    		->values(implode(',', $values));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();
	}

	public static function updateMoTfaUser($id,$username,$email,$phone=''){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('username') . ' = '.$db->quote($username),
			$db->quoteName('email') . ' = '.$db->quote($email),
			$db->quoteName('phone') . ' = '.$db->quote($phone),
		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' ='.$id
		);

		$query->update($db->quoteName('#__miniorange_tfa_users'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
	}

	public static function getCustomerKeys($isMiniorange=false){
		$keys=array();
		if($isMiniorange){
			$keys['customer_key']= "16555";
    		$keys['apiKey']      = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
		}
		else{
			$details=self::getCustomerDetails();
			$keys['customer_key']= $details['customer_key'];
    		$keys['apiKey']      = $details['api_key'];
		}
		return $keys;
	}

	static function saveCustomerDetailsAfterLogin($email,$password,$phone,$id, $apiKey, $token,$appSecret,$jid) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('email') . ' = '.$db->quote($email),
			$db->quoteName('password').' ='.$db->quote(base64_encode($password)),
			$db->quoteName('admin_phone').' ='.$db->quote($phone),
			$db->quoteName('customer_key') . ' = '.$db->quote($id),
			$db->quoteName('api_key') . ' = '.$db->quote($apiKey),
			$db->quoteName('customer_token') . ' = '.$db->quote($token),
			$db->quoteName('app_secret') . ' = '.$db->quote($appSecret),
			$db->quoteName('login_status') . ' = '.$db->quote(1),
			$db->quoteName('new_registration') .' = '.$db->quote(0),
			$db->quoteName('jid') .' = '.$db->quote($jid),
		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = 1'
		);
		$query->update($db->quoteName('#__miniorange_tfa_customer_details'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
	}



	static function getTransactionName(){
		return 'Joomla 2FA Plugin';
	}

    public static function insertOptionOfUser($jUsername,$id,$ActiveMethod,$StatusMotfa, $username, $email){

        $c_user = Factory::getUser($id);
        $usergroup_id = $c_user->groups;
        $user_id = 1;
        foreach($usergroup_id as $key=>$value)
        {
            $user_id=$value;
        }
        
        $user_role = self::__getDBValuesUsingColumns('title', '#__usergroups',array('id' => $user_id,),$method="loadResult");

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
            $db->quoteName('id')                . ' = ' . $db->quote($id),
            $db->quoteName('active_method')     . ' = ' . $db->quote($ActiveMethod),
            $db->quoteName('status_of_motfa')   . ' = ' . $db->quote($StatusMotfa),
            $db->quoteName('username')          . ' = ' . $db->quote($username),
            $db->quoteName('jUsername')          . ' = ' . $db->quote($jUsername),
            $db->quoteName('email')             . ' = ' . $db->quote($email),
            $db->quoteName('user_group'). ' = ' . $db->quote($user_role),
        );
        $query->insert($db->quoteName('#__miniorange_tfa_users'))->set($fields);

        $db->setQuery($query);
        $db->execute();
    }

	public static function updateOptionOfUser($id,$columnName,$value){
		$db = Factory::getDbo(); 
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName($columnName) . ' = '.$db->quote($value),
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' ='.$id
		);
		$query->update($db->quoteName('#__miniorange_tfa_users'))->set($fields)->where($conditions);
		$db->setQuery($query);

		$result = $db->execute();
	}

	static function getHostName(){
		$brandingName=self::getTfaSettings()['branding_name'];
		$brandingName=empty(trim($brandingName))?'login':$brandingName;
		return 'https://'.$brandingName.'.xecurify.com';
    }

    static function getApiUrls()
    {
	    $hostName = self::getHostName();
		return array(
			'challange'         =>  $hostName.'/moas/api/auth/challenge',
			'update'            =>  $hostName.'/moas/api/admin/users/update',
			'validate'          =>  $hostName.'/moas/api/auth/validate',
			'googleAuthService' =>  $hostName.'/moas/api/auth/google-auth-secret',
			'googlevalidate'    =>  $hostName.'/moas/api/auth/validate-google-auth-secret',
			'createUser'        =>  $hostName.'/moas/api/admin/users/create',
			'kbaRegister'       =>  $hostName.'/moas/api/auth/register',
			'getUserInfo'       =>  $hostName.'/moas/api/admin/users/get',
            'feedback'          =>  $hostName.'/moas/api/notify/send',
            'deleteUser'        =>  $hostName.'/moas/api/admin/users/delete'
	    );
	}

	public static function addToConfiguredMethod($id,$method)
    {
		$row=self::getMoTfaUserDetails($id);
		$methods=$row['configured_methods'];
		if(is_null($methods) || empty($methods)){
			$methods.=$method;
		}
		else{

			if(array_search($method, explode(';',$methods))===FALSE)
			$methods=$methods.';'.$method;
		}
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		 // Fields to update.
		$fields = array(
			$db->quoteName('configured_methods') . ' = '.$db->quote($methods),

		);
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' ='.$id
		);
		$query->update($db->quoteName('#__miniorange_tfa_users'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$db->execute();
	}

	public static function getKbaQuestions(){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('tfa_kba_set1', 'tfa_kba_set2')));
		$query->from($db->quoteName('#__miniorange_tfa_settings'));
		$db->setQuery($query);
		$results = $db->loadRow();

		$options_one = explode('?',$results[0]);
		$options_two = explode('?',$results[1]);

        $arr = array();
        $option_string_1 = '';
        foreach ($options_one as $key => $value) {
            if(!empty($options_one[$key]))
            {
                $option_string_1 = $option_string_1.'<option name=question1 value="'.$value.'" >'.$value.'</option>';
            }
        }
        $option_string_2='';
        foreach ($options_two as $key => $value) {
            if(!empty($options_two[$key])){
                $option_string_2 = $option_string_2.'<option name=question1 value="'.$value.'" >'.$value.'</option>';
            }
        }

        $arr['0'] = $option_string_1;
        $arr['1'] = $option_string_2;
        return $arr;
	}

	public static function getTfaSettings(){
		$db = Factory::getDbo();
		$query = $db
    		->getQuery(true)
    		->select('*')
    		->from($db->quoteName('#__miniorange_tfa_settings'))
    		->where($db->quoteName('id') . " = " . $db->quote(1));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);
		$row=$db->loadAssoc();
		return $row;
	}

	public static function  getActive2FAMethods(){
	    $tfaSetting = self::getTfaSettings();
	    $tfaSetting = json_decode($tfaSetting['activeMethods'],true);
        $configuration = self::getAllTfaMethods();

        if(in_array('ALL', $tfaSetting)){
            $tfaSetting = array_keys($configuration);
        }
        
        foreach ($configuration as $key => $value){
            if(!in_array($key, $tfaSetting))
                $configuration[$key]['active'] = false;
            else
                $configuration[$key]['active'] = true;
        }

        return $configuration;
    }

    public static function getAllTfaMethods(){
        return array(
            'OOS'=>array('name'=>'OTP over SMS'),
            'OOE'=>array('name'=>'OTP over Email'),
            'OOSE'=>array('name'=>'OTP over SMS or Email'),
            'OOC'=>array('name'=>'OTP over Phone Call'),
            'YK'=>array('name'=>'Yubikey Hardware Token'),
            'google'=>array('name'=>'Google Authenticator'),
            'MA'=>array('name'=>'Microsoft Authenticator'),
            'AA'=>array('name'=>'Authy Authenticator'),
			'LPA'=>array('name'=>'LastPass Authenticator'),
			'DUO'=>array('name'=>'Duo Authenticator'),
			'DUON'=>array('name'=>'Duo Push Notification')
        );
    }

    public static function validateIpsInput($IPs){
	    // explode with ;
        if(!array($IPs)){
            $IPs = trim($IPs);
        }
        $invalidIps = array();
        $validIps   = array();
        if(!empty($IPs)){
            $ipArray = explode(";", $IPs);
            foreach ($ipArray as $key => $value){
                // check if we have a range here
                $value = str_replace(" ","", $value);
                if(empty($value))
                    continue;
                $ipOrRangeArr = explode('-', $value);
                // validate ip
                $invalid  = count($ipOrRangeArr) > 2 ||
                    (count($ipOrRangeArr) == 2 && (ip2long($ipOrRangeArr[0]) === FALSE || ip2long($ipOrRangeArr[1]) === FALSE))
                    || (count($ipOrRangeArr) == 1 && ip2long($ipOrRangeArr[0]) === FALSE);

                $invalid ? array_push($invalidIps, $value) : array_push($validIps, $value);
            }
        }

        return array($validIps,$invalidIps);
    }

    static function get_client_ip() {
        $ipaddress = 'UNKNOWN';
	    $environments = array('HTTP_CLIENT_IP','REMOTE_ADDR','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED');
	    foreach ($environments as $key=>$value)
        {
            if(getenv($value))
            {
                $ipaddress = getenv($value);
                break;
            }
        }
	    return $ipaddress;
    }

    

    static function doWeHaveAwhiteIp($current_IP_address,$settings=false){
	    $settings = $settings== false ? self::getTfaSettings():$settings;
        $mo_ip_found = FALSE;
        if($settings['enableIpWhiteList']==0)
            return false;
        $whitelisted_IP_array=json_decode($settings['whiteListedIps'],true);
        foreach( $whitelisted_IP_array as $key => $value ) {
            if( stristr( $value, '-' ) )
            {
                /** Search in range of IP address **/
                list($lower, $upper) = explode('-', $value, 2);
                $lower_range = ip2long( $lower );
                $upper_range = ip2long( $upper );
                $current_IP  = ip2long( $current_IP_address );
                if( $lower_range !== FALSE && $upper_range !== FALSE && $current_IP !== FALSE && ( ( $current_IP >= $lower_range ) && ( $current_IP <= $upper_range ) ) ){
                    $mo_ip_found = TRUE;
                    break;
                }
            }
            else 
            {
                /** Compare with single IP address **/
                if( $current_IP_address == $value )
                {
                    $mo_ip_found = TRUE;
                    break;
                }
            }
        }

        return $mo_ip_found;
    }

    static function isCurrentIPBlackListed($current_IP_address, $settings = false)
    {
        $settings = $settings == false ? self::getTfaSettings():$settings;
        $mo_ip_found = FALSE;
        if($settings['enableIpBlackList']==0)
            return false;
        $blackListed_IP_array = json_decode($settings['blackListedIPs'],true);
        foreach( $blackListed_IP_array as $key => $value ) {
            if( stristr( $value, '-' ) ){
                /** Search in range of IP address **/
                list($lower, $upper) = explode('-', $value, 2);
                $lower_range = ip2long( $lower );
                $upper_range = ip2long( $upper );
                $current_IP  = ip2long( $current_IP_address );
                if( $lower_range !== FALSE && $upper_range !== FALSE && $current_IP !== FALSE && ( ( $current_IP >= $lower_range ) && ( $current_IP <= $upper_range ) ) ){
                    $mo_ip_found = TRUE;
                    break;
                }
            }else {
                /** Compare with single IP address **/
                if( $current_IP_address == $value ){
                    $mo_ip_found = TRUE;
                    break;
                }
            }
        }

        return $mo_ip_found;
    }

    public static function plugin_error_log($txt)
    {
        // $txt = $txt.PHP_EOL;
        // $file_path = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logs'.DIRECTORY_SEPARATOR . 'error_log.txt';

        // $myfile = fopen($file_path, "a") or die("Unable to open file!");
        // fwrite($myfile, $txt);
        // fclose($myfile);
    }

    public static function license_efficiency_check()
    {
        $userDetail = self::getCustomerDetails();
        $licenseAttributes = array();
        $currentUsers = self::checkMoTfaUsers();
        if( ("JOOMLA_2FA_PLUGIN" == $userDetail['license_type']) && ("joomla_2fa_premium_plan" == $userDetail['license_plan']))
        {
            $licenseAttributes['plan_fetched'] = 1;
            if((TRUE == self::check_status_time($userDetail['licenseExpiry'])) && (TRUE == self::check_status_time($userDetail['supportExpiry'])))
            {
                $licenseAttributes['license_expiry'] = 0;
                $licenseAttributes['support_expiry'] = 0;
            }
            else if((TRUE == self::check_status_time($userDetail['licenseExpiry'])) && (FALSE == self::check_status_time($userDetail['supportExpiry'])))
            {
                $licenseAttributes['license_expiry'] = 0;
                $licenseAttributes['support_expiry'] = 1;
            }
            else if((FALSE == self::check_status_time($userDetail['licenseExpiry'])) && (TRUE == self::check_status_time($userDetail['supportExpiry'])))
            {
                $licenseAttributes['license_expiry'] = 1;
                $licenseAttributes['support_expiry'] = 0;
            }
            else
            {
                $licenseAttributes['license_expiry'] = 1;
                $licenseAttributes['support_expiry'] = 1;
            }

            if($userDetail['email']!="demo2fauser1@xecurify.com" && ($userDetail['no_of_users']!=0) && ($userDetail['no_of_users']>$currentUsers))
            {
                $licenseAttributes['users'] = 0;
            }
            else if($userDetail['email']=="demo2fauser1@xecurify.com" && ($userDetail['no_of_users']!=0) && (5>=$currentUsers))
            {
                $licenseAttributes['users'] = 0;
            }
            else
            {
                $licenseAttributes['users'] = 1;
            }
            
        }
        else if(("JOOMLA_2FA_PLUGIN" != $userDetail['license_type']) ||("joomla_2fa_premium_plan" != $userDetail['license_plan']))
        {
            $licenseAttributes['plan_fetched'] = 0;
        }
        return $licenseAttributes;
    }

    static function check_status_time($timestamp)
    {
        $convertedTime = new DateTime($timestamp);
        $currentTime = new DateTime();

        $differenceSeconds = $convertedTime->format('U') - $currentTime->format('U');
        if($differenceSeconds>0)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    public static function _get_all_login_attempts_count()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__miniorange_tfa_users'));
        $db->setQuery($query);
        return $db->loadResult();;
    }

    public static function _get_login_transaction_reports()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_tfa_users'));
        $db->setQuery($query);
        return $db->loadAssoc();
    }

    public static function _get_login_attempts_count($low_id, $upper_id,$order="down")
    {
        $db = Factory::getDbo();
        $temp = array();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_tfa_users'));
        
        $query->order('jUsername ASC');
        $db->setQuery($query,$low_id,$upper_id);
        $temp[] = $db->loadAssocList();
        return $temp;
    }

    public static function __getDBValuesWOArray($table)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName($table));
        $query->where($db->quoteName('id') . " = 1");
        $db->setQuery($query);
        return ($db->loadAssoc());
    }

     public static function user_email_phone_check($post, $get){
        $columnName    = array('rs_form_field_configuration','reg_restriction','white_or_black','mo_otp_allowed_email_domains');
        $result        = self::getCustomerDetails($columnName, '#__miniorange_otp_customer', 'loadObjectList', array('id' => 1,));
        $requested_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $rs_form_configuration=isset($result[0]->rs_form_field_configuration)?json_decode($result[0]->rs_form_field_configuration):array();

      if ($result['reg_restriction'] == 1) {
            Log::add("reg_registration value is 1", Log::INFO, 'tfa');
        
            if ($result['white_or_black'] == 1) {
                Log::add("white_or_black value is 1", Log::INFO, 'tfa');
                $allowed_domains = $result['mo_otp_allowed_email_domains'] ?? 0;
            } else if ($result['white_or_black'] == 2) {
                Log::add("white_or_black value is 2", Log::INFO, 'tfa');
                $blocked_domains = $result['mo_otp_allowed_email_domains'] ?? 0;
            }
            if( isset($post['task']) && $post['task'] =='saveUser' ) { //Virtuemart Form
                isset ($post['email']) ? $domain = explode('@', $post['email'])[1] : '';
            }
            elseif ( isset($post['cf']['hnpt']) && $get['task'] == 'submit') { //Convert Form
                isset($post['cf']['email']) ? $domain = explode('@', $post['cf']['email'])[1] : '';
            }
            else if ( isset($post['__cf_token']) && $get['gpage'] == 'start_page') { //Chrono Form
                isset($post['email']) ? $domain = explode('@', $post['email'])[1] : '';
            }
            else if ( isset($post['form']) ){ //RS Form
                foreach ($rs_form_configuration as $key=>$value){
                    if ($post['form']['formid'] == $key) {
                        isset($post['form'][$value[0]]) ? $domain = explode('@', $post['form'][$value[0]])[1] : '';
                    }
                }
            }
            else if( isset($post['gid']) && isset($post['emailpass']) && strpos($requested_uri, 'cb-profile') ){ //Community Builder Form
                isset($post['email']) ? $domain = explode('@', $post['email'])[1] : '';
            }
            else { // Joomla default Registration Form
                isset ($post['jform']['email1']) ? $domain = explode('@', $post['jform']['email1'])[1] : '';
            }
        }
        $blocked_domains = isset($blocked_domains) ? explode(';', $blocked_domains) : [0];
        $allowed_domains = isset($allowed_domains) ? explode(';', $allowed_domains) : [0];

        $phone_number = '';
        //Check for blocked Country codes.
        if( isset($post['task']) && $post['task'] =='saveUser') { //Virtuemart Form
            $phone_number= $post['phone_1'] ?? '';
        }
        else if ( isset($post['cf']['hnpt']) && isset($get['task']) && $get['task'] == 'submit' ) { //Convert Form
            $phone_number = $post['cf']['phone'] ?? '';
        }
        else if ( isset($post['__cf_token']) && isset($get['gpage']) && $get['gpage'] == 'start_page' ) { //Chrono Form
            $phone_number = $post['phone'] ?? '';
        }
        else if ( isset($post['form']['formId']) ) { //RS Form
            foreach ($rs_form_configuration as $key=>$value){
                if ($post['form']['formId'] == $key) {
                    $phone_number = $post['form'][$value[1]] ?? '';
                }
            }
        }
        else if ( isset($post['gid']) && isset($post['emailpass']) ) { //Community Builder Form
            $phone_number = isset($post);
        }
        else { // Joomla default Registration Form
            $phone_number = $post['jform']['profile']['phone'] ?? '';
        }

        $is_blocked = self::_check_country_code_blocked($phone_number);
        $is_email = false;
        $is_phone = false;
        if ($is_blocked) {
            $is_phone = true;
        }
        if (isset($domain)) {
            if (!((!in_array($domain, $blocked_domains) || empty($blocked_domains[0])) && ((in_array($domain, $allowed_domains)) || empty($allowed_domains[0])))) {
                $is_email = true;
            }
        }
        self::_show_blocked_message($is_phone, $is_email);
    }

    public static function _check_country_code_blocked($phone_number)
    {
        $result = self::__getDBValuesWOArray('#__miniorange_otp_custom_message');
        $blocked_list = isset($result['mo_block_country_code']) ? $result['mo_block_country_code'] : '';

        if (!empty($blocked_list) && $blocked_list != '') {
            $blocked_list = unserialize($blocked_list);

            for ($i = 0; $i < count($blocked_list); $i++) {
                if (isset($blocked_list[$i]) && !empty($blocked_list[$i])) {
                    $val = $blocked_list[$i];
                    if (strpos($phone_number, $val) !== false ) {
                        return 1;
                    }
                }
            }
        }
        return 0;
    }
    
    public static function _show_blocked_message($is_phone, $is_email)
    {
       
        $result = self::__getDBValuesWOArray('#__miniorange_otp_custom_message');

        if ($is_email && $is_phone){
            $custom_blocked_email_and_phone_message = 'You are not allowed to register. Your country code and email domain are blocked. Please contact your administrator.';
            self::_redirect_url($custom_blocked_email_and_phone_message);
        }
        else if ($is_phone) {
            $custom_blocked_phone_message = $result['mo_custom_phone_blocked_message'] ?? '';
            if (empty($custom_blocked_phone_message) || $custom_blocked_phone_message == ''){
                $custom_blocked_phone_message = 'You are not allowed to register. Your country may be blocked. Please contact your administrator.';
            }
            self::_redirect_url($custom_blocked_phone_message);
        } else if ($is_email) {
            $custom_blocked_email_message = $result['mo_custom_email_blocked_message'] ?? '';
            if (empty($custom_blocked_email_message) || $custom_blocked_email_message == 'You are not allowed to register. Your Domain may be blocked. Please contact your administrator.'){
                $custom_blocked_email_message = 'You are not allowed to register. Your domain may be blocked. Please contact your administrator.';
            }
            self::_redirect_url($custom_blocked_email_message);
        }
    }

    public static function _redirect_url($message)
    {
        $app = Factory::getApplication();
        $app->enqueueMessage($message, 'error');
        $app->redirect(Route::_('index.php'));
    }

    
    public static function get_userid_from_username($username)
    {
        //Check if username exist in database
        $db = Factory::getDBO();

        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__users')
            ->where('username=' . $db->quote($username));

        $db->setQuery($query);
        return $db->loadColumn();
    }

    
    public static function get_userid_from_email($email)
    {
        //Check if email exist in database
        $db = Factory::getDBO();

        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__users')
            ->where('email=' . $db->quote($email));

        $db->setQuery($query);
        return $db->loadColumn();
    }

    
    public static function _get_feedback_form($post)
    {        
        $radio = isset($post['deactivate_plugin']) ? $post['deactivate_plugin'] : '';
        $data = isset($post['query_feedback']) ? $post['query_feedback'] : '';
        $db_table = '#__miniorange_tfa_customer_details';
        $db_coloums = array('uninstall_feedback' => 1,);// use cookie instead of db query.------------------------------

        self::__genDBUpdate($db_table, $db_coloums);
        $customerResult = self::getCustomerDetails();

            $radio = isset($post['deactivate_plugin']) ? $post['deactivate_plugin'] : '';
            $data = isset($post['query_feedback']) ? $post['query_feedback'] : '';

            $current_user = Factory::getUser();
            $admin_email_default = isset($current_user->email) ? $current_user->email : '';

            $admin_email = isset($post['query_email']) ? $post['query_email'] : '';
            $admin_phone = isset($customerResult['admin_phone']) ? $customerResult['admin_phone'] : '';
            $data1 = !isset($post['skip_feedback']) ? $radio . ' : ' . $data : 'Skipped the Feedback form.';

            require_once JPATH_BASE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_twofa' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_otp_customer_setup.php';
            Mo_tfa_Customer::submit_feedback_form($admin_email,$admin_email_default,$admin_phone, $data1);
        
        require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer' . DIRECTORY_SEPARATOR . 'Installer.php';

        if (isset($post['result'])) {
            foreach ($post['result'] as $fbkey) {
                $result = self::__getDBValuesUsingColumns('type', '#__extensions', $fbkey);
                $identifier = $fbkey;
                $type = 0;

                foreach ($result as $results) {
                    $type = $results;
                }
                if ($type) {
                    $cid = 0;
                    $installer = new JInstaller();
                    $installer->uninstall($type, $identifier, $cid);
                }
            }
        }
    }

    public static function add_otp_transaction($method, $user_email, $user_phone_number, $otp_sent, $otp_verified, $timestamp) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        $tableName = $db->quoteName('#__miniorange_otp_transactions_report'); // Replace with correct table name
    
        $fields = array(
            $db->quoteName('verification_method') . ' = ' . $db->quote($method),
            $db->quoteName('user_email') . ' = ' . $db->quote($user_email),
            $db->quoteName('user_phone') . ' = ' . $db->quote($user_phone_number),
            $db->quoteName('otp_sent') . ' = ' . $db->quote($otp_sent),
            $db->quoteName('otp_verified') . ' = ' . $db->quote($otp_verified),
            $db->quoteName('timestamp') . ' = ' . $db->quote($timestamp),
        );
    
        // Debugging log to ensure the correct table name is being used
        Log::add("Inserting into table: " . $tableName, Log::INFO, 'tfa');
    
        $query->insert($tableName)->set($fields);
        
        $db->setQuery($query);
        $db->execute();
    }
    
    public static function _is_country_code_blocked($country_code)
    {
        $result = self::__getDBValuesWOArray('#__miniorange_otp_custom_message');
        $blocked_list = isset($result['mo_block_country_code']) ? $result['mo_block_country_code'] : '';

        if (empty($blocked_list)) {
            return 0;
        }
        $blocked_list = unserialize($blocked_list);
        if (in_array($country_code, $blocked_list))
        {
            return 1;
        } else
        {
            return 0;
        }
    }

    public static function _is_default_selected($post)
    {
        $country_code = isset($post['mo_block_country_code']) ? $post['mo_block_country_code'] : '';
        $country_code = trim($country_code);
    
        $results = self::getCustomerDetails();
        
        $default_country_code = isset($results['mo_default_country_code']) && !empty($results['mo_default_country_code'])
            ? $results['mo_default_country_code']
            : '';
    
        $default_country_code = '+' . $default_country_code;
        $country_code = explode(';', $country_code);
    
        return in_array($default_country_code, $country_code);
    }
    
    public static function _block_country_code($post)
    {
        $country_code = isset($post['mo_block_country_code']) ? $post['mo_block_country_code'] : '';
        $country_code = trim($country_code);
        $country_code = explode(';', $country_code);
        $country_code = serialize($country_code);

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $fields = array(
            $db->quoteName('mo_block_country_code') . ' = ' . $db->quote($country_code),
        );
        $conditions = array(
            $db->quoteName('id') . ' = 1'
        );
        $query->update($db->quoteName('#__miniorange_otp_custom_message'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    public static function _save_custom_message($post)
    {
        $message         = isset($post['mo_custom_email_success_message']) ? $post['mo_custom_email_success_message'] : '';
        $error_otp       = isset($post['mo_custom_email_error_message']) ? $post['mo_custom_email_error_message'] : '';
        $blocked_email   = isset($post['mo_custom_email_blocked_message']) ? $post['mo_custom_email_blocked_message'] : '';
        $email_success   = isset($post['mo_custom_email_success_message_send']) ? $post['mo_custom_email_success_message_send'] : $message;
        $email_fail      = isset($post['mo_custom_email_error_message']) ? $post['mo_custom_email_error_message'] : $error_otp;
        $invalid_email   = isset($post['mo_custom_email_invalid_format_message']) ? $post['mo_custom_email_invalid_format_message'] : '';
        $blocked_message = isset($post['mo_custom_email_blocked_message']) ? $post['mo_custom_email_blocked_message'] : $blocked_email;

        $email_success   = trim($email_success);
        $email_fail      = trim($email_fail);
        $invalid_email   = trim($invalid_email);
        $blocked_message = trim($blocked_message);

        $db = Factory::getDbo();

        $query = $db->getQuery(true);
        // Fields to update.

        $fields = array(
            $db->quoteName('mo_custom_email_success_message') . ' = ' . $db->quote($email_success),
            $db->quoteName('mo_custom_email_error_message') . ' = ' . $db->quote($email_fail),
            $db->quoteName('mo_custom_email_invalid_format_message') . ' = ' . $db->quote($invalid_email),
            $db->quoteName('mo_custom_email_blocked_message') . ' = ' . $db->quote($blocked_message),
        );

        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = 1'
        );

        $query->update($db->quoteName('#__miniorange_otp_custom_message'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    public static function _save_custom_phone_message($post)
    {
        $success_message = "An OTP (One Time Passcode) has been sent to ##phone##. Please enter the OTP in the field below to verify your phone.";
        $error_message = "There was an error in sending the OTP to the given Phone Number. Please Try Again or contact site Admin.";

        $phone_success = isset($post['mo_custom_phone_success_message']) ? $post['mo_custom_phone_success_message'] : $success_message;
        $phone_error = isset($post['mo_custom_phone_error_message']) ? $post['mo_custom_phone_error_message'] : $error_message;
        $invalid_format = isset($post['mo_custom_phone_invalid_format_message']) ? $post['mo_custom_phone_invalid_format_message'] : '';
        $phone_blocked = isset($post['mo_custom_phone_blocked_message']) ? $post['mo_custom_phone_blocked_message'] : '';

        $phone_success = trim($phone_success);
        $phone_error = trim($phone_error);
        $invalid_format = trim($invalid_format);
        $phone_blocked = trim($phone_blocked);

        $db = Factory::getDbo();

        $query = $db->getQuery(true);
        // Fields to update.

        $fields = array(
            $db->quoteName('mo_custom_phone_success_message') . ' = ' . $db->quote($phone_success),
            $db->quoteName('mo_custom_phone_error_message') . ' = ' . $db->quote($phone_error),
            $db->quoteName('mo_custom_phone_invalid_format_message') . ' = ' . $db->quote($invalid_format),
            $db->quoteName('mo_custom_phone_blocked_message') . ' = ' . $db->quote($phone_blocked),
        );

        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = 1'
        );

        $query->update($db->quoteName('#__miniorange_otp_custom_message'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    
    public static function _save_com_message($post)
    {
        $invalid_otp = isset($post['mo_custom_invalid_otp_message']) ? $post['mo_custom_invalid_otp_message'] : '';

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $fields = array(
            $db->quoteName('mo_custom_invalid_otp_message') . ' = ' . $db->quote($invalid_otp),
        );
        $conditions = array(
            $db->quoteName('id') . ' = 1'
        );
        $query->update($db->quoteName('#__miniorange_otp_custom_message'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }

    public static function _get_all_otp_transaction_count(){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__miniorange_otp_transactions_report'));
        $db->setQuery($query);
        $config = $db->loadResult();
        return $config;
    }

    public static function _get_otp_transaction_reports_val()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_otp_transactions_report'));
        $db->setQuery($query);
        $attributes = $db->loadAssoc();
        return $attributes;
    }

    public static function _download_reports()
    {
        $data = self::_get_otp_transaction_report_download();
        $reports = Text::_('LIB_TNX_REPORT_COLUMNS');

        $i = 1;
        foreach ($data as $key => $value) {
            $timestamp = $value['timestamp'];
            $date = date('d-m-Y H:i:s', $timestamp);
            $reports .= $i . ',' . $value['verification_method'] . ',' . $value['user_email'] . ','
                . $value['user_phone'] . ',' . $value['otp_sent'] . ',' . $value['otp_verified'] .',' . $date . "\n";
            $i++;
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="OTP Transaction Report.csv"');
        print_r($reports);
        exit();
    }

    public static function _get_otp_transaction_report_download()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_otp_transactions_report'));
        $db->setQuery($query);
        $config = $db->loadAssocList();
        return $config;
    }

    public static function _get_otp_transaction_report($limit, $offset, $order="down")
    {
        $db = Factory::getDbo();
        $temp = array();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__miniorange_otp_transactions_report'));
        if($order=="down")
            $query->order('timestamp DESC');
        $query->setLimit($limit, $offset);
        $db->setQuery($query);
        $temp[] = $db->loadAssocList();
        return $temp;
    }
    public static function get_user_details_by_username($username) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('email')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('username') . ' = ' . $db->quote($username));
        $db->setQuery($query);
        return $db->loadAssoc(); 
    }
}