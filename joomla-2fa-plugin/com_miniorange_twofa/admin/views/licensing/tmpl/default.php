<?php

/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('jquery.framework', false);
jimport('miniorangetfa.utility.commonUtilitiesTfa');

$document = Factory::getApplication()->getDocument();
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_twofa/assets/css/miniorange_boot.css');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_twofa/assets/css/mo_two_fa_style_sheet.css');
$document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');



$mfa_active_tab = "license";
/*
* Check is curl installed or not, if not show the instructions for installation.
*/
commonUtilitiesTfa::checkIsCurlInstalled();
?>

<div id="account" class="container-fluid mo_tfa_license_page">
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <?php licensingtab(); ?>
        </div>
    </div>
</div>
<?php
function licensingtab()
{
?>
    <div class="mo_boot_row mo_tfa_license_tab">
        <div class="mo_boot_col-sm-12">
            <div class="mo_boot_row mo_boot_mt-2">
                <div class="mo_boot_col-sm-12 mo_boot_mt-4 lead mo_boot_text-center mo_tfa_license_head">
                    <h2><?php echo Text::_('COM_MINIORANGE_LICENSING'); ?></h2>
                    <a href="<?php echo Uri::base() . 'index.php?option=com_miniorange_twofa&tab-panel=account_setup'; ?>" class="mo_boot_btn mo_boot_btn-danger mo_tfa_back_btn"><?php echo Text::_('COM_MINIORANGE_BACK'); ?></a>
                </div>
            </div>

            <!-- Cards Container -->
            <div class="mo_boot_row mo_boot_justify-content-center mo_boot_mt-4 mo_boot_mb-4">
                <!-- Free Card -->
                <div class="mo_boot_col-sm-4">
                    <div class="mo_boot_card">
                        <div class="mo_boot_card-header mo_boot_text-center mo_boot_bg-light">
                            <h4 class="mo_boot_my-0"><?php echo Text::_('COM_MINIORANGE_FREE_PLAN_TITLE'); ?></h4>
                        </div>
                        <div class="mo_boot_card-body">
                            <!-- Pricing -->
                            <div class="mo_boot_text-center mo_boot_mb-4">
                                <h2 class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_FREE_PLAN_PRICE'); ?></h2>
                                <small class="mo_boot_text-muted"><?php echo Text::_('COM_MINIORANGE_FREE_PLAN_SUBTITLE'); ?></small>
                            </div>
                            <!-- Features -->
                            <ul class="mo_boot_list-unstyled">
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_TOTP_ONLY'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_SINGLE_USER'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_FEATURE_PASSWORDLESS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_ROLE_BASED_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_IP_SPECIFIC_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_SPECIFIC_TFA_METHOD'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PASSWORD_RESET'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PROFILE_UPDATE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_EMAIL_REMEMBER_DEVICE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_TFA_ON_REGISTER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_QUESTIONS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_NUMBER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKUP_CODES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CHANGE_APP_NAME'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_TEMPLATES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_OTP_LENGTH'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKDOOR_URL'); ?></li>
                            </ul>
                            <!-- Button -->
                            <div class="mo_boot_text-center mo_boot_mt-4">
                                <button class="mo_boot_btn mo_boot_btn-outline-primary mo_boot_btn-lg mo_boot_px-4" disabled><?php echo Text::_('COM_MINIORANGE_FREE_PLAN_CURRENT'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Plan Card -->
                <div class="mo_boot_col-sm-4">
                    <div class="mo_boot_card mo_boot_border-primary">
                        <div class="mo_boot_card-header mo_boot_text-center mo_boot_bg-primary mo_boot_text-white">
                            <h4 class="mo_boot_my-0"><?php echo Text::_('COM_MINIORANGE_PREMIUM_PLAN_TITLE'); ?></h4>
                        </div>
                        <div class="mo_boot_card-body">
                            <!-- Pricing -->
                            <div class="mo_boot_text-center mo_boot_mb-4">
                                <div class="mo_tfa_year_fee">
                                    <select class="mo_tfa_lic_users mo_boot_form-control" required id="user-select">
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 10 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $10 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 20 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $20 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 30 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $30 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 40 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $40 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 50 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $50 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 60 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $60 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 70 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $70 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 80 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $80 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 90 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $90 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 100 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $100 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 150 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $150 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 200 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $200 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 250 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $250 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 300 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $275 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 350 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $300 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 400 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $325 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 450 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $348 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 500 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $370 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 600 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $395 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 700 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $420 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 800 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $445 / year</option>
                                        <option><?php echo Text::_('COM_MINIORANGE_UPTO'); ?> 900 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - $470 / year</option>
                                        <option id="contact-us-option"><?php echo Text::_('COM_MINIORANGE_MORE_THAN'); ?> 1000 <?php echo Text::_('COM_MINIORANGE_USERS'); ?> - <?php echo Text::_('COM_MINIORANGE_CONTACTUS'); ?> </option>
                                    </select>
                                </div>
                                <small class="mo_boot_text-muted"><?php echo Text::_('COM_MINIORANGE_PREMIUM_PLAN_TRANSACTION'); ?></small>
                            </div>
                            <!-- Features -->
                            <ul class="mo_boot_list-unstyled">
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_ALL_AUTH'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_USER_BASED'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_FEATURE_PASSWORDLESS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_ROLE_BASED_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_IP_SPECIFIC_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_SPECIFIC_TFA_METHOD'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PASSWORD_RESET'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PROFILE_UPDATE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_EMAIL_REMEMBER_DEVICE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_TFA_ON_REGISTER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_QUESTIONS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_NUMBER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKUP_CODES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CHANGE_APP_NAME'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_TEMPLATES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_OTP_LENGTH'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKDOOR_URL'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_USER_VERIFY'); ?></strong></li>
                            </ul>
                            <!-- Button -->
                            <div class="mo_boot_text-center mo_boot_mt-4">
                                <button class="mo_boot_btn mo_boot_btn-primary mo_boot_btn-lg mo_boot_px-4" onclick="window.location.href='https://portal.miniorange.com/initializePayment?requestOrigin=joomla_2fa_premium_plan'"><?php echo Text::_('COM_MINIORANGE_PREMIUM_PLAN_UPGRADE'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- On-Premise Card -->
                <div class="mo_boot_col-sm-4">
                    <div class="mo_boot_card">
                        <div class="mo_boot_card-header mo_boot_text-center mo_boot_bg-primary mo_boot_text-white">
                            <h4 class="mo_boot_my-0"><?php echo Text::_('COM_MINIORANGE_ONPREMISE_PLAN_TITLE'); ?></h4>
                        </div>
                        <div class="mo_boot_card-body">
                            <!-- Pricing -->
                            <div class="mo_boot_text-center mo_boot_mb-4">
                                <h2 class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_ONPREMISE_PLAN_PRICE'); ?><small class="mo_boot_text-muted">*</small></h2>
                                <small class="mo_boot_text-muted"><?php echo Text::_('COM_MINIORANGE_ONPREMISE_PLAN_INSTANCE'); ?> + <?php echo Text::_('COM_MINIORANGE_PREMIUM_PLAN_TRANSACTION'); ?></small>
                            </div>
                            <!-- Features -->
                            <ul class="mo_boot_list-unstyled">
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_ALL_AUTH'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_UNLIMITED_USERS'); ?></strong></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_FEATURE_PASSWORDLESS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_ROLE_BASED_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_IP_SPECIFIC_TFA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_SPECIFIC_TFA_METHOD'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PASSWORD_RESET'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_PROFILE_UPDATE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_EMAIL_REMEMBER_DEVICE'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_TFA_ON_REGISTER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_QUESTIONS'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_KBA_NUMBER'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKUP_CODES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CHANGE_APP_NAME'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_TEMPLATES'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_CUSTOM_OTP_LENGTH'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <?php echo Text::_('COM_MINIORANGE_2FA_BACKDOOR_URL'); ?></li>
                                <li class="mo_boot_mb-2"><i class="fas fa-check mo_boot_text-success"></i> <strong><?php echo Text::_('COM_MINIORANGE_FEATURE_USER_VERIFY'); ?></strong></li>
                            </ul>
                            <!-- Button -->
                            <div class="mo_boot_text-center mo_boot_mt-4">
                                <button class="mo_boot_btn mo_boot_btn-primary mo_boot_btn-lg mo_boot_px-4" onclick="window.location.href='https://www.miniorange.com/contact'"><?php echo Text::_('COM_MINIORANGE_ONPREMISE_PLAN_CONTACT'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mo_boot_card {
            border-radius: 10px;
            transition: transform 0.2s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .mo_boot_card:hover {
            transform: translateY(-5px);
        }
        .mo_boot_card-header {
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem;
        }
        .mo_boot_card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .mo_boot_list-unstyled {
            flex: 1;
        }
        .mo_boot_list-unstyled li {
            position: relative;
            padding-left: 25px;
            font-size: 0.9rem;
        }
        .mo_boot_list-unstyled li i {
            position: absolute;
            left: 0;
            top: 4px;
        }
        .mo_tfa_lic_users {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .mo_boot_btn-lg {
            min-width: 160px;
        }
        .mo_boot_mt-4 {
            margin-top: auto !important;
            padding-top: 1.5rem;
        }
    </style>
<?php
}
?>