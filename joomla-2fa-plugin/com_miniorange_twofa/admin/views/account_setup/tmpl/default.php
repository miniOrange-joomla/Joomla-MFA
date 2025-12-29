<?php

/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

require_once JPATH_PLUGINS . '/user/miniorangetfa/messages.php';

HTMLHelper::_('jquery.framework');
jimport('miniorangetfa.utility.commonUtilitiesTfa');
Log::addLogger(
    array(
        'text_file' => 'tfa_admin_logs.php',
        'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
    ),
    Log::ALL
);
$document = Factory::getApplication()->getDocument();
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_twofa/assets/css/miniorange_boot.css');
$document->addScript(Uri::base() . 'components/com_miniorange_twofa/assets/js/mo_tfa_admin.js');
$document->addScript(Uri::base() . 'components/com_miniorange_twofa/assets/js/mo_tfa_phone.js');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_twofa/assets/css/mo_two_fa_style_sheet.css');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_twofa/assets/css/mo_tfa_phone.css');
$document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
$document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css');

$document->addScript('https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js');

/*
 * Check is curl installed or not, if not show the instructions for installation.
*/
commonUtilitiesTfa::checkIsCurlInstalled();
$app = Factory::getApplication();
$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
$get = ($input && $input->get) ? $input->get->getArray() : [];

$this->mfa_active_tab = !empty($get['tab-panel']) ? $get['tab-panel'] : 'account_setup';
$mfa_active_tab = $this->mfa_active_tab ?? ''; 

?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">

<!-- Include JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

<div class="container-fluid 2fa-container mo_2fa_border mo_boot_col-sm-12">
    <div class="mo_boot_row mo_boot_p-2 mo_2fa_heading">
        <div class="mo_boot_col-sm-6 mo_boot_text-light text-left">
            <h4><?php echo Text::_('COM_MINIORANGE_TWO_FACTOR_AUTHENTICATION'); ?></h4>
        </div>

        <div class="mo_boot_col-sm-6 mo_2fa_btns">
            <a href="<?php echo Uri::base() . 'index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=support' ?>" class="mo_boot_btn  mo_tfa_sup_btns">
                <i class="fas fa-life-ring mo_btn_tfa_icon"></i>
                <strong class="mo_btn_tfa_text"><?php echo Text::_('COM_MINIORANGE_SUPPORT_FEATURE'); ?></strong>
            </a>
            &emsp;<a href="<?php echo Uri::base() . 'index.php?option=com_miniorange_twofa&view=Licensing' ?>" class="mo_boot_btn mo_tfa_sup_btns">
                <i class="fas fa-crown mo_btn_tfa_icon"></i>
                <strong class="mo_btn_tfa_text"><?php echo Text::_('COM_MINIORANGE_UPGRADE_PLAN'); ?></strong>
            </a>
            &emsp;<a class="mo_boot_btn mo_tfa_sup_btns" href="<?php echo Uri::base() . 'index.php?option=com_miniorange_twofa&view=account_setup&tab-panel=exportConfiguration' ?>">
                <i class="fas fa-download mo_btn_tfa_icon"></i>
                <strong class="mo_btn_tfa_text"><?php echo Text::_('COM_MINIORANGE_EXPORT_IMPORT'); ?></strong>
            </a>
        </div>
    </div>


    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-2 mo_2fa_row ">
            <div class="mo_boot_row">
                <?php
                $tabs = [
                    [
                        'id' => '2fa_tab_1',
                        'label' => Text::_('COM_MINIORANGE_TFA_ACCOUNT'),
                        'active' => ($mfa_active_tab == 'account_setup'),
                        'icon' => 'fa fa-user-plus'
                    ],
                    [
                        'id' => '2fa_tab_7',
                        'label' => Text::_('COM_MINIORANGE_OTP_REGISTRATION_METHOD_TITLE'),
                        'active' => ($mfa_active_tab == 'register_settings'),
                        'icon' => 'fa fa-sliders'
                    ],
                    [
                        'id' => '2fa_tab_2',
                        'label' => Text::_('COM_MINIORANGE_LOGIN_SETTING'),
                        'active' => ($mfa_active_tab == 'login_settings'),
                        'icon' => 'fa fa-user-shield'
                    ],
                    [
                        'id' => '2fa_tab_3',
                        'label' => Text::_('COM_MINIORANGE_ADVANCE_SETTINGS'),
                        'active' => ($mfa_active_tab == 'advance_settings'),
                        'icon' => 'fa fa-gear'
                    ],
                    [
                        'id' => '2fa_tab_4',
                        'label' => Text::_('COM_MINIORANGE_USER_MANAGEMENT'),
                        'active' => ($mfa_active_tab == 'user_management'),
                        'icon' => 'fa fa-user-gear'
                    ],
                    [
                        'id' => '2fa_tab_10',
                        'label' => Text::_('COM_MINIORANGE_REPORT'),
                        'active' => ($mfa_active_tab == 'otp_report'),
                        'icon' => 'fa fas fa-palette'
                    ],
                    [
                        'id' => '2fa_tab_9',
                        'label' => Text::_('COM_MINIORANGE_CUSTOMISATION'),
                        'active' => ($mfa_active_tab == 'customise_options'),
                        'icon' => 'fa fa-solid fa-pen-to-square'
                    ],
                    [
                        'id' => '2fa_tab_5',
                        'label' => Text::_('COM_MINIORANGE_LOGIN_FORMS'),
                        'active' => ($mfa_active_tab == 'login_forms'),
                        'icon' => 'fa fa-file-waveform'
                    ],
                    [
                        'id' => '2fa_tab_6',
                        'label' => Text::_('COM_MINIORANGE_POPUPS'),
                        'active' => ($mfa_active_tab == 'popup_design'),
                        'icon' => 'fa fas fa-palette'
                    ]
                ];


                foreach ($tabs as $tab) {
                    $activeClass = $tab['active'] ? 'mo_tfa_tab-active' : 'mo_tfa_tab-none';
                ?>
                    <div onclick="mo_show_tab('<?php echo $tab['id']; ?>')" id="mo_<?php echo $tab['id']; ?>" class="mo_tfa_tabs mo_boot_col-sm-12 mo_boot_p-3  mini_2fa_tab  <?php echo $activeClass; ?>">
                        <i class="fa-solid <?php echo $tab['icon']; ?>"></i>
                        <span class="mo_boot_px-2 tab-label"><?php echo $tab['label']; ?></span>
                    </div>
                <?php

                }


                ?>

            </div>
        </div>
        <?php
        $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
        $details = commonUtilitiesTfa::getCustomerDetails();
        ?>
        <div class="mo_boot_col-sm-10 mo_tfa_login_section">
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_1" style="<?php echo (($mfa_active_tab == 'account_setup') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                $details = commonUtilitiesTfa::getCustomerDetails();
                 $app = Factory::getApplication();
                $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
                $get = ($input && $input->get) ? $input->get->getArray() : [];
                if ($details['login_status'] == 1) {
                    welcomeCustomer($details);
                } else if ($details['registration_status'] == 'OTP') {
                    validateOtpTab();
                } else {
                    if (array_key_exists('tab', $get)) {
                        loginTab($get['tab']);
                    } else {
                        loginTab();
                    }
                }
                ?>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_7" style="<?php echo (($mfa_active_tab == 'register_settings') ? 'display:block;' : 'display:none;'); ?>">
                <div class="mo_boot_row mo_boot_p-3">
                    <div class="mo_boot_col-sm-12">
                        <?php
                        if (!commonUtilitiesTfa::isCustomerRegistered()) { ?>
                            <div class="mo_register_message"><?php echo Text::_("COM_MINIORANGE_SETUP_TFA_MSG"); ?><a href="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup'); ?>"><?php echo Text::_("COM_MINIORANGE_REGISTER_MSG"); ?></a><?php echo Text::_("COM_MINIORANGE_SETUP_TFA_MSG1"); ?></div>
                            <div class="mo_boot_col-sm-12"></div>
                        <?php
                        }
                        ?>
                        <div class="mo_boot_row  mo_boot_text-center">
                            <div class="mo_boot_col-sm-12"><br>
                                <h3><?php echo Text::_('COM_MINIORANGE_OTP_REGISTRATION_METHOD_TITLE'); ?></h3>
                                <hr>
                            </div>
                        </div>
                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-12">
                                <?php
                                $customer_details = commonUtilitiesTfa::getCustomerDetails();
                                $login_status = $customer_details['login_status'] ?? null;
                                $registration_status = $customer_details['registration_status'] ?? null;
                                registerSettingsTab();
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="mo_boot_col-sm-12">
                        <?php
                            $details = commonUtilitiesTfa::getCustomerDetails();
                            $licenseExpired = false;
                            // Check if license has expired based on 'supportExpiry'
                            if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
                                $licenseExpired = true;
                            }
                        ?>
                        <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveDomainBlocks'); ?>" method="post" name="adminForm" id="otp_form">
                            <input id="mo_otp_blocked_email_domains" type="hidden" name="option9" value="mo_domain_block" />
                            <input id="mo_otp_allowed_email_domains" type="hidden" name="option9" value="mo_domain_allow" />
                            <input id="reg_restriction" type="hidden" name="option9" value="mo_domain_allow" />
                            <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                            <?php
                            $result         = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
                            $white_or_black = isset($result['white_or_black']) ? $result['white_or_black'] : 0;
                            $allowed_emails = isset($result['mo_otp_allowed_email_domains']) ? $result['mo_otp_allowed_email_domains'] : 0;
                            $reg_restr      = isset($result['reg_restriction']) ? $result['reg_restriction'] : 0;
                            ?>
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12">
                                    <h3 class="mo_tfa_summary">
                                        <?php echo Text::_('COM_MINIORANGE_DOMAIN_RESTRICTION_TITLE'); ?>
                                        <?php if (empty($details['license_type'])): ?>
                                            <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                            </div>

                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12">
                                    <?php if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = true;
                                    else $disabled = false; 
                                    $isRegistered = commonUtilitiesTfa::isCustomerRegistered();
                                    $textareaAndRadiosEnabled = $isRegistered; 
                                    ?>
                                    <strong>
                                    <input id="reg_restriction_for_email" name="reg_restriction"
                                        class="reg_restriction_for_email mo_boot_mr-2 mo_boot_m-0"
                                        type="checkbox" value="1"
                                        <?php if ($reg_restr == 1) echo 'checked'; ?>
                                        <?php if (empty($details['license_type'])) echo 'disabled'; ?>>
                                    <?php echo Text::_('COM_MINIORANGE_DOMAIN_RESTRICTION_INFO'); ?>
                                    </strong>
                                </div>
                            </div>


                            <div class="mo_boot_row mo_boot_mt-3" id="otp_settings">
                                <div class="mo_boot_col-sm-4">
                                    <strong><?php echo Text::_('COM_MINIORANGE_EMAIL_DOMAINS'); ?></strong>
                                </div>
                                <div class="mo_boot_col-sm-6 ps-1">
                                <textarea rows="3" cols="55" id="mo_otp_allowed_email_domains" name="mo_otp_allowed_email_domains"
                                class="mo_boot_form-control mo_otp_allowed_email_domains textarea-control mo_otp_email_domain"
                                placeholder="<?php echo Text::_('COM_MINIORANGE_EMAIL_DOMAINS_PLACEHOLDER'); ?>"
                                onkeyup="nospaces(this);"
                                <?php if (!$textareaAndRadiosEnabled) echo 'disabled'; ?> <?php if (empty($details['license_type'])) echo 'disabled'; ?>><?php echo trim($allowed_emails); ?></textarea>
                                </div>
                            </div>
                            <div class="mo_boot_row mo_boot_mt-2">
                            <div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
                                <?php 
                                if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = true;
                                else $disabled = false; 
                                ?>
                                <strong>
                               <input type="radio" id="white_list_radio" name="white_or_black" value="1"
                            class="mo_boot_m-0 white_or_black mo_otp_radiobox_style"
                            <?php if ($white_or_black == 1) echo 'checked'; ?>
                            <?php if (!$textareaAndRadiosEnabled) echo 'disabled'; ?>>
                                <?php echo Text::_('COM_MINIORANGE_ALLOWED_EMAIL_DOMAINS'); ?>
                                </strong>
                            </div>

                            <div class="mo_boot_col-sm-4">
                                <strong>                     
                                <input type="radio" id="black_list_radio" name="white_or_black" value="2"
                                    class="mo_boot_m-0 white_or_black mo_otp_radiobox_style"
                                    <?php if ($white_or_black == 2) echo 'checked'; ?>
                                    <?php if (!$textareaAndRadiosEnabled) echo 'disabled'; ?>>
                                <?php echo Text::_('COM_MINIORANGE_BLOCKED_EMAIL_DOMAINS'); ?>
                                </strong>
                            </div>

                            </div>
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12  mo_boot_text-center mo_boot_mt-3">
                                    <input type="submit" <?php if ($disabled) echo "enabled";
                                    else echo "disabled"; ?> name="save" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>">
                                </div>
                            </div><br>
                            </fieldset>
                        </form>
                    </div>
                    <div class="mo_boot_col-sm-12">
                        <?php get_country_code_dropdown(); ?>
                    </div>
                    <div class="mo_boot_col-sm-12">
                        <?php __block_country_code(); ?>
                    </div>

                </div>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_2" style="<?php echo (($mfa_active_tab == 'login_settings') ? 'display:block;' : 'display:none;'); ?>">
                <?php echo loginSettingsTab(); ?>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_3" style="<?php echo (($mfa_active_tab == 'advance_settings') ? 'display:block;' : 'display:none;'); ?>">
                <?php echo advanceSettingsTab(); ?>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_4" style="<?php echo (($mfa_active_tab == 'user_management') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                UserManagement();
                ?>
            </div>

            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_9" style="<?php echo (($mfa_active_tab == 'customise_options') ? 'display:block;' : 'display:none;'); ?>">
                <div class="mo_boot_col-sm-12 mo_boot_p-3">

                    <div class="mo_boot_col-sm-12"><?php
                        if (!commonUtilitiesTfa::isCustomerRegistered()) {
                            echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
                        }
                        ?>
                    </div>

                    <div class="mo_boot_row mo_boot_mt-3  mo_boot_text-center">
                        <div class="mo_boot_col-sm-12">
                            <h2><?php echo Text::_('COM_MINIORANGE_CUSTOMISATION'); ?></h2>
                        </div>
                    </div>

                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-12">
                            <hr>
                        </div>
                    </div>
                    <div class="mo_boot_row  mo_boot_mt-4">
                        <div class="mo_boot_col-sm-12">
                            <?php _custom_email_messages(); ?>
                        </div>
                        <div class="mo_boot_col-sm-12">
                            <?php _custom_phone_messages(); ?>
                        </div>
                        <div class="mo_boot_col-sm-12">
                            <?php _custom_common_otp_messages(); ?>
                        </div>

                        <div class="mo_boot_col-sm-12">
                            <?php _custom_common_otp_messagess(); ?>
                        </div>

                    </div>
                </div>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_5" style="<?php echo (($mfa_active_tab == 'login_forms') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                CustomLoginForms();
                ?>
            </div>

            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_8" style="<?php echo (($mfa_active_tab == 'support') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                support();
                ?>
            </div>

            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_7" style="<?php echo (($mfa_active_tab == 'exportConfiguration') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                exportConfiguration();
                ?>
            </div>
            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_10" style="<?php echo (($mfa_active_tab == 'otp_report') ? 'display:block;' : 'display:none;'); ?>">
                <div class="mo_boot_row mo_boot_p-3">
                    <?php
                   $disabled = "disabled";
                    $details = commonUtilitiesTfa::getCustomerDetails();

                    $licenseExpired = false;
                    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
                        $licenseExpired = true;
                    }

                    if (commonUtilitiesTfa::isCustomerRegistered() && !$licenseExpired) {
                        $disabled = "";
                    }
                    $base_url_otp = Uri::base() . 'index.php?option=com_miniorange_twofa&task=setup_two_factor.joomlapagination_otp';
                    $otp_transaction_count = commonUtilitiesTfa::_get_all_otp_transaction_count();
                    ?>
                    <div class="mo_boot_col-sm-12">
                        <div class="mo_boot_col-sm-12"><?php
                            if (!commonUtilitiesTfa::isCustomerRegistered()) {
                                echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
                            }
                            ?>
                        </div>
                        <h3 class=" mo_boot_mt-3"><?php echo Text::_('COM_MINIORANGE_OTP_TNX_REPORT_TITLE'); ?></h3>
                        <hr>
                        <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-8 mo_boot_col-lg-5 mo_otp_trans_report ">
                                <select class=" mo_boot_form-control mo_boot_m-1 mo_otp_report_no mo_boot_p-1" id="select_number" onchange="list_of_entry_Otp()" <?php echo $disabled; ?>>
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <input type="text" id="search_text" class=" mo_boot_form-control mo_otp_search mo_boot_m-1" onkeyup="search()" placeholder="<?php echo Text::_('COM_MINIORANGE_OTP_SEARCH'); ?>" <?php echo $disabled; ?> />
                            </div>
                            <div class="mo_boot_col-sm-12  mo_boot_col-lg-7 text-right mo_otp_report_desc mo_boot_p-1">
                                <form name="mo_ip_login" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.otp_reports'); ?>" id="jnsp_clear_values">

                                    <input type="submit" name="refresh_page" class="mo_boot_btn mo_boot_btn-primary mo_otp_search mo_otp_btns mo_boot_mr-3 <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="<?php echo Text::_('COM_MINIORANGE_REFRESH_BUTTON'); ?>" <?php echo $disabled; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                                    <input type="submit" name="clear_val" class="mo_boot_btn mo_boot_btn-danger mo_boot_mr-3 <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="<?php echo Text::_('COM_MINIORANGE_CLEAR_BUTTON'); ?>" onclick="ClearReports();" <?php echo $disabled; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                                    <input type="submit" name="download_reports" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="<?php echo Text::_('COM_MINIORANGE_DOWNLOAD_BUTTON'); ?>" <?php echo $disabled; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                                </form>

                            </div>
                        </div><br>
                        </fieldset>
                        <script>
                        function ClearReports() {
                            if (confirm("<?php echo Text::_('COM_MINIORANGE_CLEAR_POPUP'); ?>")) {
                                jQuery('input[name="clear_val"]').val('<?php echo Text::_('COM_MINIORANGE_CLEAR_BUTTON'); ?>');
                            } else {
                                jQuery('#jnsp_clear_values').submit(function() {
                                    return false;
                                });
                            }
                        }
                        </script>
                        <div id="show_paginations_2"></div>
                        <input type="hidden" id="next_page_otp" value="0"><br>
                        <?php 
                        // Only show pagination buttons if there are more entries than can fit on one page
                        $entries_per_page = 10; // Default entries per page
                        if ($otp_transaction_count > 0 && $otp_transaction_count > $entries_per_page): 
                        ?>
                        <div>
                            <div id="next_btn">
                                <input type="button" name="mo_next" class="mo_boot_btn mo_boot_btn-primary mo_otp_report_btn mo_otp_btns mo_boot_m-1"
                                    onclick="next_or_prev_page_otp('next','preserve');" value="Next">
                            </div>
                            <div id="pre_btn">
                                <input type="button" name="mo_next" class="mo_boot_btn mo_boot_btn-primary mo_boot_m-1 mo_otp_report_btn mo_otp_btns"
                                    onclick="next_or_prev_page_otp('pre','preserve');" value="Prev">
                            </div>
                        </div>
                        <?php endif; ?>
                        <script>
                            var clock = 1; // Initialize clock variable
                            var no_of_entry = 10; // Default number of entries
                            
                            jQuery(document).ready(function (){
                                next_or_prev_page_otp('next');
                            });

                            function list_of_entry_Otp(){
                                no_of_entry = jQuery("#select_number").val();
                                next_or_prev_page_otp('on');
                            }
                            
                            function sort(button){
                                var order = "";
                                if(clock) {
                                    clock = 0;
                                    order = 'up';
                                } else {
                                    clock = 1;
                                    order = 'down';
                                }
                                next_or_prev_page_otp(button, order);
                            }
                            
                            function next_or_prev_page_otp(button, order = 'down') {
                                var page = parseInt(document.getElementById('next_page_otp').value) || 0;
                                var orderBY = 'down';
                                var totalEntries = <?php echo $otp_transaction_count; ?>;
                                
                                // Calculate max possible page
                                var maxPage = Math.ceil(totalEntries / no_of_entry) - 1;
                                
                                // Update page number before AJAX call
                                if (button == 'on') {
                                    page = 0;
                                } else if (button == 'pre') {
                                    page = Math.max(0, page - 1);
                                } else if (button == 'next' && page < maxPage) {
                                    page = page + 1;
                                }
                                
                                if (order == 'up') {
                                    orderBY = 'up';
                                } else if (order == 'preserve') {
                                    orderBY = clock === 1 ? 'down' : 'up';
                                }

                                // Update pagination buttons visibility
                                document.getElementById('pre_btn').style.display = page === 0 ? "none" : "inline";
                                document.getElementById('next_btn').style.display = page >= maxPage ? "none" : "inline";
                                
                                // Update page value immediately
                                document.getElementById('next_page_otp').value = page;

                                jQuery.ajax({
                                    url: '<?php echo $base_url_otp; ?>',
                                    dataType: "text",
                                    method: "POST",
                                    data: {
                                        'page': page,
                                        'orderBY': orderBY,
                                        'no_of_entry': no_of_entry
                                    },
                                    success: function(data) {
                                        var arr = data.split("separator_for_count");
                                        jQuery("#show_paginations_2").html(arr[0]);
                                        
                                        if (arr[1] === "0") {
                                            // If no results, go back one page
                                            page = Math.max(0, page - 1);
                                            document.getElementById('next_page_otp').value = page;
                                            document.getElementById('next_btn').style.display = "none";
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        // On error, revert the page number
                                        page = Math.max(0, page - 1);
                                        document.getElementById('next_page_otp').value = page;
                                    }
                                });
                            }

                            function search() {
                                var value = jQuery("#search_text").val().toLowerCase();
                                jQuery("#myTable tbody tr").filter(function() {
                                    jQuery(this).toggle(jQuery(this).text().toLowerCase().indexOf(value) > -1)
                                });
                            }
                        </script>
                    </div>
                </div>
            </div>

            <div class="mo_boot_col-sm-12 mo_2fa_tab" id="2fa_tab_6" style="<?php echo (($mfa_active_tab == 'popup_design') ? 'display:block;' : 'display:none;'); ?>">
                <?php
                popup();
                ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php

function loginTab($tab = 'register')
{
    $user = Factory::getUser();
?>
    <div class="mo_boot_row mo_boot_m-1">
        <div class="container-fluid mo_boot_py-4 mo_boot_col-sm-12 ">
            <div class="mo_boot_card mo_boot_my-2">
                <div class="mo_boot_card-body">
                    <div class="mo_boot_col-sm-11 mo_boot_mt-3 mo_boot_mb-3">
                        <div class="mo_boot_alert mo_tfa_note" role="alert">
                            <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_LOGIN_NOTE'); ?> <strong><?php echo Text::_('COM_MINIORANGE_100_FREE'); ?></strong>, <?php echo Text::_('COM_MINIORANGE_LOGIN_NOTE_2'); ?>
                        </div>
                    </div>

                    <div class="mo_boot_col-sm-11 mo_boot_mt-3  mo_boot_text-center ">
                        <h3 id="mo_tfa_register_login"><?php echo Text::_('COM_MINIORANGE_LOGIN_MINI'); ?><span class="icon-mo_tfa_icon mo_tfa_mini_logo" aria-hidden="true"></span>range</h3>

                    </div>

                    <div class="mo_boot_col-sm-12 mo_boot_offset-2 mo_tfa_Login_panel">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.register_login_customer'); ?>">

                            <div class="mo_boot_row mo_boot_mt-3 mo_boot_mx-4">
                                <div class="mo_boot_col-sm-12 mo_tfa_fw">
                                    <?php echo Text::_('COM_MINIORANGE_EMAIL'); ?>
                                </div>
                                <div class="mo_boot_col-sm-12 mo_boot_mt-1">
                                    <input class=" mo_boot_form-control mo_tfa_email" type="email" name="email" required placeholder="<?php echo Text::_('COM_MINIORANGE_EXAMPLE_EMAIL'); ?>" />

                                </div>
                            </div>
                            <div class="mo_boot_row mo_tfa_fw mo_boot_mt-3 mo_boot_mx-4">
                                <div class="mo_boot_col-sm-12 ">
                                    <?php echo Text::_('COM_MINIORANGE_PASSWORD'); ?>

                                </div>
                                <div class="mo_boot_col-sm-12 mo_boot_mt-1">
                                    <input class=" mo_boot_form-control " required type="password" name="password" placeholder="<?php echo Text::_('COM_MINIORANGE_PASSWORD_MSG'); ?>" />
                                </div>
                            </div>
                            <div class="mo_boot_row mo_boot_my-4 mo_tfa_login_desc justify-content-center">
                                <div class="mo_boot_col-sm-12 text-center">
                                                                        <input type="submit" name="register_or_login" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns mo_boot_mr-3 " value="<?php echo Text::_('COM_MINIORANGE_VALLOGIN1'); ?>" />
                                    <a class="mo_boot_btn mo_boot_btn-primary mo_otp_btns mo_boot_mr-3 "href="<?php echo commonUtilitiesTfa::getHostName(); ?>/moas/idp/resetpassword"target="_blank"><?php echo Text::_('COM_MINIORANGE_FORGOT_PASSWORD'); ?></a>
                               </div>
                            </div>
                             <div class="mo_boot_row mo_boot_my-4 text-center mo_boot_p-0 mo_boot_m-0 mo_boot_d-flex mo_boot_flex-column align-items-center justify-content-center ">
                                <span class="forgot_phn mo_tfa_acc_option mo_tfa_white_dark">
                                    <?php echo Text::_('COM_MINIORANGE_REGISTER_MINI'); ?>
                                </span>
                                <a class="mo_boot_btn mo_boot_btn-primary mo_otp_btns mo_boot_mr-3 "
                                href="https://www.miniorange.com/businessfreetrial"
                                target="_blank">
                                <u><?php echo Text::_('COM_MINIORANGE_CREATE_ACCOUNT'); ?></u>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

function validateOtpTab()
{
?>
    <div class="mo_boot_row mo_boot_m-1">
        <div class="mo_boot_col-sm-12 mo_boot_mt-3  mo_boot_text-center">
            <h3 id="mo_tfa_register_login"><?php echo Text::_('COM_MINIORANGE_REGISTER_MINI'); ?><span style="color:orange;"><strong>O</strong></span>range</h3>
            <hr>
        </div>
        <div class="mo_boot_col-sm-12">
            <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.validateOTP'); ?>">
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-2  mo_boot_text-center offset-sm-3">
                        <strong><?php echo Text::_('COM_MINIORANGE_OTP'); ?> <span style="color:#FF0000;">*</span></strong>
                    </div>
                    <div class="mo_boot_col-sm-4">
                        <input class=" mo_boot_form-control" required type="text" name="OTP_token" placeholder="" />
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-3">
                    <div class="mo_boot_col-sm-12  mo_boot_text-center" style="margin-bottom:15px;">
                        <input type="submit" name="Validate" class="mo_boot_btn btn-success" style="margin-right: 10px;" value="<?php echo Text::_('COM_MINIORANGE_VAL_VALIDATE'); ?>" />
                        <input type="button" name="Resend OTP" class="mo_boot_btn btn-success" style="margin-right: 10px;" value="<?php echo Text::_('COM_MINIORANGE_VAL_OTP2'); ?>" onclick="document.getElementById('mo_tfa_resend_otp_form').submit();" />
                        <input type="button" name="Back" class="mo_boot_btn mo_boot_btn-danger" value="<?php echo Text::_('COM_MINIORANGE_VALBACK'); ?>" onclick="document.getElementById('mo_tfa_back_to_registerlogin').submit();" />
                    </div>
                </div>
            </form>
        </div>
        <form id="mo_tfa_resend_otp_form" name="Resend_otp_form" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.resendOtp'); ?>">
        </form>
        <form id="mo_tfa_back_to_registerlogin" name="Back_from_otp" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.backToRegisterLogin'); ?>">
        </form>
    </div>
<?php
}
function welcomeCustomer($details)
{
    $tfaDetails = commonUtilitiesTfa::customerTfaDetails();
    $tfaMethodArray = Mo_tfa_utilities::tfaMethodArray();
    $transctionUrl = "<a href='" . Mo_tfa_utilities::getHostname() . "/moas/login?username=" . $details['email'] . "&redirectUrl=" . Mo_tfa_utilities::getHostname() . "/moas/viewtransactions' target='_blank'>" . Text::_("COM_MINIORANGE_VAL_CHECK") . "</a>";

    $licenseExpiry = strtotime($details['licenseExpiry']);
    $supportExpiry = strtotime($details['supportExpiry']);
    $customCSS = '';
    $joomla_version = commonUtilitiesTfa::getJoomlaCmsVersion();
    $phpVersion = phpversion();
    $PluginVersion = commonUtilitiesTfa::GetPluginVersion();

    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $current_user     = Factory::getUser();
    $isFirstUser          = commonUtilitiesTfa::isFirstUser($current_user->id);
    $c_time = date("Y-m-d", time());
    $licenseType = !empty($details['license_type']) ? $details['license_type'] : 'Demo';

    $email = commonUtilitiesTfa::_getMaskedEmail($details['email']);



?>

    <div class="mo_boot_row mo_boot_m-1 mo_tfa_account_Set">
        <div class="mo_boot_col-sm-12 mo_boot_p-3">
            <?php ?>
            <h5 class=" mo_boot_text-center mo_tfa_log_head "><?php echo ($licenseType == 'Demo Account') ? 'You have successfully logged in with Demo Account' : Text::_('COM_MINIORANGE_THANKS'); ?></h5>
            <hr>

            <details class="mo_details">
                <summary class="mo_tfa_bg_grey">
                    <b><?php echo Text::_('COM_MINIORANGE_ACCINFO'); ?></b>
                </summary>
                <div class="mo_boot_row mo_boot_m-2">
                    <div class="mo_boot_col-sm-12 mo_boot_table-responsive  ">
                        <table class="mo_boot_table mo_boot_table-bordered mo_boot_table-striped">
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_2FA_REGISTERED'); ?></td>
                                <td><?php echo $email; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_XECURIFY_REG'); ?></td>
                                <td><?php echo $email; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_CUSTOMER_ID'); ?></td>
                                <td><?php echo $details['customer_key']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_JOOMLA_VERSION'); ?></td>
                                <td><?php echo $joomla_version; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_PHP_VERSION'); ?></td>
                                <td><?php echo $phpVersion ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_PLUGIN_VERSION'); ?></td>
                                <td><?php echo $PluginVersion; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </details>
            <details class="mo_details" open>
                <summary class="mo_tfa_bg_grey">
                    <strong class="mo_tfa_licinfo"><?php echo Text::_('COM_MINIORANGE_LIC_INFO'); ?></strong>
                </summary>
                <div class="mo_boot_row mo_boot_m-2">
                    <div class="mo_boot_col-sm-12 mo_boot_table-responsive  ">
                        <table class="mo_boot_table mo_boot_table-bordered mo_boot_table-striped">
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_LIC_TYPE'); ?></td>
                                <td><?php echo $licenseType; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_USERS_NO'); ?></td>      
                                <td><?php echo empty($details['license_plan']) ? 1 : $details['no_of_users']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_EMAIL_REMAIN'); ?></td>
                                <td><?php echo empty($details['license_plan']) ? $details['emailRemaining'] : $details['emailRemaining']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo Text::_('COM_MINIORANGE_SMS_REMAIN'); ?></td>
                                <td><?php echo empty($details['license_plan']) ? $details['smsRemaining'] : $details['smsRemaining']; ?></td>
                            </tr>
                            <tr <?php echo $customCSS; ?>>
                                <td><?php echo Text::_('COM_MINIORANGE_LIC_EXP'); ?></td>
                                <td><?php if (isset($details['licenseExpiry']) && strtotime($c_time) > ($licenseExpiry)) {
                                    ?>
                                        <span><?php echo $details['licenseExpiry']; ?></span>
                                    <?php } else {
                                        echo empty($details['licenseExpiry']) ? $transctionUrl : $details['licenseExpiry'];
                                    } ?>
                                </td>
                            </tr>
                            <tr <?php echo $customCSS; ?>>
                                <td><?php echo Text::_('COM_MINIORANGE_SUPPORT_EXP'); ?></td>
                                <td>
                                    <?php if (isset($details['supportExpiry']) && strtotime($c_time) > ($supportExpiry)) {
                                    ?>
                                        <span class="mo_tfa_red mo_tfa_bold"> <?php echo $details['supportExpiry']; ?></span>
                                    <?php } else {
                                        echo empty($details['supportExpiry']) ? $transctionUrl : $details['supportExpiry'];
                                    } ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </details>
        </div>
        <div class="mo_boot_col-sm-12 mo_boot_my-3  mo_boot_text-center mo_tfa_wel_btns">
            <a onclick="document.getElementById('mo_tfa_fetch_ccl').submit();" class="mo_boot_btn  mo_boot_btn-primary mo_otp_btns mo_tfa_btns_dark mo_tfa_btn_bg mo_boot_mr-3 "><?php echo Text::_('COM_MINIORANGE_REFETCH_LIC'); ?></a>
            <a class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark mo_tfa_upgrade_now mo_boot_mr-3 mo_otp_btns " target="_blank" href="https://portal.miniorange.com/initializePayment?requestOrigin=joomla_2fa_premium_plan"><?php echo Text::_('COM_MINIORANGE_UPGRADE'); ?></a>
            <?php
            if ($isCustomerRegistered && $isFirstUser) { ?>
                <a onclick="document.getElementById('mo_remove_account').submit();" class="mo_boot_btn  mo_boot_btn-danger"><?php echo Text::_('COM_MINIORANGE_REMOVE_ACC'); ?></a>
            <?php }
            ?>
        </div>
    </div>
    <form name="f" method="post" id="mo_tfa_fetch_ccl" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.checkLicense'); ?>"></form>
    <form name="f" method="post" id="mo_remove_account" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.removeAccount'); ?>"></form>
<?php
}

function registerSettingsTab()
{
    $settings   = commonUtilitiesTfa::getTfaSettings();
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $details = commonUtilitiesTfa::getCustomerDetails();
    $inlineDisabled = '';

    $featureDisable = '';
    if (!$isCustomerRegistered) {
        $featureDisable = 'disabled';
    }

    $licenseExpired = false;
    // Check if license has expired based on 'supportExpiry'
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }

    // Read backup codes from file
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $enableTfaRegistration = isset($settings['enableTfaRegistration']) && $settings['enableTfaRegistration'] == 1
        ? 'checked'
        : '';
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->select('*')
        ->from($db->quoteName('#__miniorange_email_settings'))
        ->setLimit(1);
    $db->setQuery($query);

    $result = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
    $enable_tfa = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_settings');

    $enable_otp = $result['registration_otp_type'];
    $enable_during_registration = $result['enable_during_registration'];
    $resend = $result['resend_otp_count'];
    $enableTfaRegistration = $enable_tfa['enableTfaRegistration'];

    if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = true;
    else $disabled = false;
?>
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveOTP'); ?>"
            method="post" name="adminForm" id="otp_form">
            <input id="mo_otp_form_action" type="hidden" name="option9" value="mo_otp" />
            <fieldset <?php echo ($licenseExpired ) ? 'disabled' : ''; ?>>

                <summary class="mo_tfa_summary"><?php echo Text::_('COM_MINIORANGE_OTP_REGISTRATION'); ?><?php if (empty($details['license_type']) || $details['license_type'] === 'demo'): ?><sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup><?php endif; ?></summary>

                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <strong> <input type="checkbox" name="otp_during_registration" id="otp_during_registration" class="otp_during_registration mo_otp_registration mo_boot_m-0"
                                value="1" <?php if ($enable_during_registration == 1) echo "checked"; ?> <?php echo " " . $featureDisable; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                            <?php echo Text::_('COM_MINIORANGE_ENABLE_DURING_REGISTRATION'); ?>
                        </strong>
                    </div>
                </div><br>

                <div class="mo_boot_row align-items-center">
                    <div class="mo_boot_col-sm-2">
                        <strong><?php echo Text::_('COM_MINIORANGE_VERIFICATION_METHOD'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-4">
                        <select class="otp_reg_dropdown  mo_boot_form-control" id="failure_response" name="login_otp_type" <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                            <option value="" disabled="" selected="selected"><?php echo Text::_('COM_MINIORANGE_SELECT_DEFAULT_VALUE'); ?></option>
                            <option value="1" <?php if ($enable_otp == 1) echo "selected"; ?>>
                                <?php echo Text::_('COM_MINIORANGE_SELECT_EMAIL'); ?>
                            </option>
                            <option value="2" <?php if ($enable_otp == 2) echo "selected"; ?>>
                                <?php echo Text::_('COM_MINIORANGE_SELECT_SMS'); ?>
                            </option>
                            <option value="3" <?php if ($enable_otp == 3) echo "selected"; ?>>
                                <?php echo Text::_('COM_MINIORANGE_SELECT_EMAIL_OR_SMS'); ?>
                            </option>
                            <option value="4" <?php if ($enable_otp == 4) echo "selected"; ?>>
                                <?php echo Text::_('COM_MINIORANGE_SELECT_EMAIL_AND_SMS'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="mo_boot_col-sm-2">
                        <strong><?php echo Text::_('COM_MINIORANGE_RESEND_TITLE'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-4">
                        <strong>
                            <select class="mo_resend_otp_dropdown  mo_boot_form-control mo_otp_country_code" name="resend_count" <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                                <option value="default" selected="selected" <?php if ($resend == "default") echo "selected"; ?>><?php echo Text::_('COM_MINIORANGE_RESEND_DEFAULT'); ?></option>
                                <option value="1" <?php if ($resend == 1) echo "selected"; ?>>1</option>
                                <option value="2" <?php if ($resend == 2) echo "selected"; ?>>2</option>
                                <option value="3" <?php if ($resend == 3) echo "selected"; ?>>3</option>
                                <option value="4" <?php if ($resend == 4) echo "selected"; ?>>4</option>
                            </select>
                        </strong>
                    </div>
                </div><br>

                <div class="mo_boot_row">

                </div>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <?php echo Text::_('COM_MINIORANGE_RESEND_OTP_NOTE'); ?>
                    </div>
                </div><br><br>
                <summary class="mo_tfa_summary"><?php echo Text::_('COM_MINIORANGE_TFA_REGISTRATION'); ?></summary>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <div class=" mo_dark_bg">
                            <input type="checkbox" class="mo_boot_mr-2 mo_boot_m-0" id="enable_tfa_registration" onclick="toggleTfaRegistrationField();" name="enableTfaRegistration" <?php echo $inlineDisabled; ?> <?php echo $enableTfaRegistration ? 'checked' : ''; ?> <?php echo " " . $featureDisable; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?> <?php if (empty($details['license_type'])) echo 'disabled'; ?>><strong><?php echo Text::_('COM_MINIORANGE_ENABLE_DURING_REGISTRATION'); ?></strong><br><br>
                            <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong>
                            <?php echo Text::_('COM_MINIORANGE_TFA_REGISTRATION_DESC'); ?><br>
                        </div>
                    </div>
                </div>

                <div class="mo_boot_row  mo_boot_mt-4">
                    <div class="mo_boot_col-sm-12  mo_boot_text-center">
                    <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>"
                        value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php if ($disabled) echo "enabled";
                        else echo "disabled"; ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                    </div>
                </div><br>

    </fieldset>
</form>
            <script>
document.addEventListener("DOMContentLoaded", function() {
    // Check if OTP is enabled
    var otpEnabled = <?php echo $otpEnabled; ?>;

    // Get the phone field and its label
    var phoneLabel = document.getElementById('jform_profile_phone-lbl');
    var phoneInput = document.getElementById('jform_profile_phone');

    // If OTP is enabled, make the phone field mandatory
    if (otpEnabled === 1) {
        if (phoneLabel) {
            // Add the asterisk (*) to the label
            phoneLabel.innerHTML = phoneLabel.innerHTML.replace('*', '') + ' *';
        }
        if (phoneInput) {
            // Add the required attribute to the input field
            phoneInput.setAttribute('required', 'required');
        }
    }
});
</script>
        </div>
    </div>
<?php
}

function get_country_code_dropdown()
{
    $result            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
    $default_cont_code = isset($result['mo_default_country_code']) ? $result['mo_default_country_code'] : '';
    $enable_during_registration = $result['enable_during_registration'];
    $final_disabled = ($enable_during_registration != 1) ? 'disabled' : '';

    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select($db->quoteName(['mo_default_country_code', 'mo_default_country']))
        ->from($db->quoteName('#__miniorange_tfa_customer_details'))
        ->setLimit(1);
    $db->setQuery($query);
    $savedCountry = $db->loadAssoc();
    $defaultCountryCode = $savedCountry['mo_default_country_code'] ?? '';
    $defaultCountryName = $savedCountry['mo_default_country'] ?? '';
    $savedCountryValue = (!empty($defaultCountryCode) && !empty($defaultCountryName)) ? "+" . $defaultCountryCode . " " . $defaultCountryName : '';

    if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = true;
    else $disabled = false;
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    // Check if license has expired based on 'supportExpiry'
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }

?> <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12 mo_boot_p-3">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveCustomSettings'); ?>" method="post" name="custom_set" id="otp_form">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <h3 class="mo_tfa_summary">
                            <?php echo Text::_('COM_MINIORANGE_COUNTRY_CODE_TITLE'); ?>
                            <?php if (empty($details['license_type'])): ?>
                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="mo_boot_col-sm-12">
                        <?php echo Text::_('COM_MINIORANGE_COUNTRY_CODE_DETAILS'); ?>

                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-4">
                                <p><strong><?php echo Text::_('COM_MINIORANGE_SELECT_COUNTRY_CODE'); ?></strong></p>
                            </div>
                            <div class="mo_boot_col-sm-6">
                                
                                <input type="tel" id="mo_country_code" name="default_country_code" class=" mo_boot_form-control pl-2 mo_otp_country_select"
                                    value="<?php echo htmlspecialchars($savedCountryValue, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $final_disabled; ?> <?php echo $disabled ? "enabled" : "disabled"; ?>>
                            </div>
                        </div>

                    </div>
                    <div class="mo_boot_col-sm-12"><br></div>
                    <div class="mo_boot_col-sm-12  mo_boot_text-center">
                        <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php if ($disabled) echo "enabled";
                         else echo "disabled"; ?> />
                    </div>
                </div>
                </fieldset>
            </form>
        </div>
    </div>
<?php

}

function __block_country_code()
{
    $country            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
    $country_code = isset($country['mo_block_country_code']) ? $country['mo_block_country_code'] : '';
    $country_code = unserialize($country_code);
    $result            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_tfa_customer_details');
    $enable_during_registration = $result['enable_during_registration'];
    $enable_during_registration = $result['enable_during_registration'];
    $final_disabled = ($enable_during_registration != 1) ? 'disabled' : '';

    if (!empty($country_code)) {
        $country_code = implode(';', $country_code);
    } else {
        $country_code = '';
    }
    if (commonUtilitiesTfa::isCustomerRegistered()) {
        $disabled = true;
    } else {
        $disabled = false;
    }
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    // Check if license has expired based on 'supportExpiry'
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }

?>
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12 mo_boot_p-3">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.block_country_codes'); ?>" method="post" name="block_country_code">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <h3 class="mo_tfa_summary">
                            <?php echo Text::_('COM_MINIORANGE_BLOCKED_COUNTRY_CODE_TITLE'); ?>
                            <?php if (empty($details['license_type'])): ?>
                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="mo_boot_col-sm-12">
                        <?php echo Text::_('COM_MINIORANGE_BLOCKED_COUNTRY_CODE_DETAILS'); ?>

                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-4">
                                <p><strong><?php echo Text::_('COM_MINIORANGE_BLOCKED_COUNTRY_CODE'); ?></strong></p>
                            </div>
                            <div class="mo_boot_col-sm-6">
                                <textarea name="mo_block_country_code" class=" mo_boot_form-control mo_boot_textarea-control mo_otp_email_domain"
                                    onkeyup="nospaces(this)" cols="55" rows="3"
                                    placeholder="<?php echo Text::_('COM_MINIORANGE_BLOCKED_COUNTRY_CODE_PLACEHOLDER'); ?>"
                                    <?php echo $final_disabled; ?> <?php if ($disabled) echo "enabled";
                                    else echo "disabled"; ?> <?php if (empty($details['license_type'])) echo 'disabled'; ?>><?php echo $country_code; ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="mo_boot_col-sm-12"><br></div>
                    <div class="mo_boot_col-sm-12  mo_boot_text-center">
                        <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php if ($disabled) echo "enabled";
                        else echo "disabled"; ?> />
                    </div>
                </div>
                </fieldset>
            </form>
        </div>
    </div>
<?php

}


function _custom_email_messages()
{
    $messages = unserialize(MO_MESSAGES);
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
    $otp_sent      = isset($messages['OTP_SENT_EMAIL']) ? $messages['OTP_SENT_EMAIL'] : '';
    $otp_error     = isset($messages['ERROR_OTP_EMAIL']) ? $messages['ERROR_OTP_EMAIL'] : '';
    $email_blocked = isset($messages['ERROR_EMAIL_BLOCKED']) ? $messages['ERROR_EMAIL_BLOCKED'] : '';
    $email_format  = isset($messages['EMAIL_FORMAT']) ? $messages['EMAIL_FORMAT'] : '';
    $result            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
    $custom_success_email_message = isset($result['mo_custom_email_success_message']) && !empty($result['mo_custom_email_success_message']) ? $result['mo_custom_email_success_message'] : $otp_sent;
    $error_otp_message            = isset($result['mo_custom_email_error_message']) && !empty($result['mo_custom_email_error_message']) ? $result['mo_custom_email_error_message'] : $otp_error;
    $invalid_format               = isset($result['mo_custom_email_invalid_format_message']) && !empty($result['mo_custom_email_invalid_format_message']) ? $result['mo_custom_email_invalid_format_message'] : $email_format;
    $blocked_email_message        = isset($result['mo_custom_email_blocked_message']) && !empty($result['mo_custom_email_blocked_message']) ? $result['mo_custom_email_blocked_message'] : $email_blocked;

    if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = "enabled";
    else $disabled = "disabled";
?>
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveCustomMessage'); ?>" method="post" name="custom_message">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <h3 class="mo_tfa_summary"><?php echo Text::_('COM_JOOMLAOTP_TAB4_MESSAGES'); ?></h3>
                    </div>
                </div>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <details class="mo_otp_bg_white mo_details">
                            <div class="mo_boot_row  mo_boot_mx-1">
                                <div class="mo_boot_col-sm-12 mo_boot_mt-3">
                                    <details open class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <p class="mo_otp_msg_note"><?php echo Text::_('COM_MINIORANGE_EMAIL_MESSAGES_NOTE'); ?></p>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_email_success_message_send" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $custom_success_email_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_SUCCESS_OTP_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>

                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_email_error_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $error_otp_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_ERROR_OTP_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>

                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <p class="mo_otp_msg_note"><?php echo Text::_('COM_MINIORANGE_EMAIL_MESSAGES_NOTE'); ?></p>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_email_invalid_format_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $invalid_format; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_INVALID_FORMAT_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>

                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_email_blocked_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $blocked_email_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_BLOCKED_EMAIL_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                            </div>
                            <div class="mo_boot_col-sm-12  mo_boot_text-center mo_boot_mt-2">
                                <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns mo_boot_mb-3" name="custom_email_messages" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php echo $disabled; ?> />
                            </div>
                            <summary>
                                <?php echo Text::_('COM_MINIORANGE_EMAIL_MESSAGES'); ?>
                            </summary>
                        </details>
                    </div>
                </div>
        </div>
        </form>
    </div>
<?php
}


function _custom_phone_messages()
{
    $messages = unserialize(MO_MESSAGES);
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }  
    $ph_otp_sent   = isset($messages['OTP_SENT_PHONE']) ? $messages['OTP_SENT_PHONE'] : '';
    $ph_otp_error  = isset($messages['ERROR_OTP_PHONE']) ? $messages['ERROR_OTP_PHONE'] : '';
    $phone_blocked = isset($messages['ERROR_PHONE_BLOCKED']) ? $messages['ERROR_PHONE_BLOCKED'] : '';
    $phone_format  = isset($messages['ERROR_PHONE_FORMAT']) ? $messages['ERROR_PHONE_FORMAT'] : '';
    $result            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
    $success_phone_message   = isset($result['mo_custom_phone_success_message']) && !empty($result['mo_custom_phone_success_message']) ? $result['mo_custom_phone_success_message'] : $ph_otp_sent;
    $error_phone_otp_message = isset($result['mo_custom_phone_error_message']) && !empty($result['mo_custom_phone_error_message']) ? $result['mo_custom_phone_error_message'] : $ph_otp_error;
    $invalid_phone_format    = isset($result['mo_custom_phone_invalid_format_message']) && !empty($result['mo_custom_phone_invalid_format_message']) ? $result['mo_custom_phone_invalid_format_message'] : $phone_format;
    $blocked_phone_message   = isset($result['mo_custom_phone_blocked_message']) && !empty($result['mo_custom_phone_blocked_message']) ? $result['mo_custom_phone_blocked_message'] : $phone_blocked;

    if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = "enabled";
    else $disabled = "disabled";
?>
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveCustomPhoneMessage'); ?>" method="post" name="custom_phone_message">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <details class="mo_otp_bg_white mo_details">
                            <div class="mo_boot_row  mo_boot_mx-1">
                                <div class="mo_boot_col-sm-12 mo_boot_mt-3">
                                    <details open class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <p class="mo_otp_msg_note"><?php echo Text::_('COM_MINIORANGE_PHONE_MESSAGE_NOTE'); ?></p>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_phone_success_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $success_phone_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_SUCCESS_OTP_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_phone_error_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $error_phone_otp_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_ERROR_OTP_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <p class="mo_otp_msg_note"><?php echo Text::_('COM_MINIORANGE_PHONE_MESSAGE_NOTE'); ?></p>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_phone_invalid_format_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $invalid_phone_format; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_INVALID_FORMAT_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                                <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                    <details class="mo_boot_py-2 mo_boot_px-3">
                                        <br>
                                        <div class="mo_boot_col-sm-12">
                                            <textarea name="mo_custom_phone_blocked_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $blocked_phone_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_BLOCKED_COUNTRY_CODE_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                            </div>
                            <div class="mo_boot_col-sm-12  mo_boot_text-center mo_boot_mt-2 mo_boot_mb-3">
                                <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" name="custom_phone_messages" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php echo $disabled ?> />
                            </div>
                            <summary>
                                <?php echo Text::_('COM_MINIORANGE_SMS_MESSAGES'); ?>
                            </summary>
                        </details>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php
}

function _custom_common_otp_messages(): void
{
    $messages = unserialize(MO_MESSAGES);
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
    $com_messages = isset($messages['COMMON_MESSAGES']) ? $messages['COMMON_MESSAGES'] : '';
    $result            = commonUtilitiesTfa::__getDBValuesWOArray('#__miniorange_otp_custom_message');
    $invalid_otp_message = isset($result['mo_custom_invalid_otp_message']) && !empty($result['mo_custom_invalid_otp_message']) ? $result['mo_custom_invalid_otp_message'] : $com_messages;
    if (commonUtilitiesTfa::isCustomerRegistered()) $disabled = "enabled";
    else $disabled = "disabled";
?>
    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveComOTPMessages'); ?>" method="post" name="block_country_codes">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                        <details class="mo_otp_bg_white mo_details">
                            <div class="mo_boot_row  mo_boot_mx-1">
                                <div class="mo_boot_col-sm-12 mo_boot_mt-3">
                                    <details open class="mo_boot_py-2 mo_boot_px-3">
                                        <div class="mo_boot_col-sm-12  mo_boot_mt-4">
                                            <textarea name="mo_custom_invalid_otp_message" class="mo_textarea_css mo_otp_custom_msg" cols="52" rows="5" <?php echo $disabled; ?>><?php echo $invalid_otp_message; ?></textarea>
                                        </div>
                                        <summary>
                                            <?php echo Text::_('COM_MINIORANGE_INVALID_OTP_MESSAGE'); ?>
                                        </summary>
                                    </details>
                                </div>
                            </div>
                            <div class="mo_boot_col-sm-12  mo_boot_text-center mo_boot_mt-2 mo_boot_mb-3">
                                <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" name="custom_otp_messages" value="<?php echo Text::_('COM_MINIORANGE_SAVE_SETTINGS_BUTTON'); ?>" <?php echo $disabled; ?> />
                            </div>
                            <summary>
                                <?php echo Text::_('COM_MINIORANGE_COMMON_MESSAGES'); ?>
                            </summary>
                        </details>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php
}

function _custom_common_otp_messagess()
{
    $details = commonUtilitiesTfa::getCustomerDetails();
    $customerEmail = $details['email'];
    $hostName = commonUtilitiesTfa::getHostName();
?>

    <div class="mo_boot_row">
        <div class="mo_boot_col-sm-12">
            <div class="mo_tfa_template_box" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3 class="mo_tfa_summary"><?php echo Text::_('COM_MINIORANGE_CUSTOMISE_TMPL'); ?></h3>
                <a href="<?php echo $hostName; ?>/moas/login?username=<?php echo $customerEmail; ?>&redirectUrl=<?php echo $hostName; ?>/moas/admin/customer/staticwelcomeemailtemplate" target="_blank" rel="noopener noreferrer" style="display: block; margin-bottom: 15px; text-decoration: none; color: #007bff;">
                    <?php echo Text::_('COM_MINIORANGE_EMAIL_TEMPLATE'); ?>
                </a>
                <a href="<?php echo $hostName; ?>/moas/login?username=<?php echo $customerEmail; ?>&redirectUrl=<?php echo $hostName; ?>/moas/admin/customer/showsmstemplate" target="_blank" rel="noopener noreferrer" style="display: block; text-decoration: none; color: #007bff;">
                    <?php echo Text::_('COM_MINIORANGE_SMS_TEMPLATE'); ?>
                </a>
            </div>
        </div>
    </div>

<?php
}
function loginSettingsTab()
{
    $settings = commonUtilitiesTfa::getTfaSettings();
    $enabled_tfa = isset($settings['tfa_enabled']) && $settings['tfa_enabled'] == 1 ? 'checked' : '';
    $enable_2fa_user_type = isset($settings['tfa_enabled_type']) ? $settings['tfa_enabled_type'] : 'none';
    $inline = isset($settings['tfa_halt']) && $settings['tfa_halt'] == 1 ? 'checked' : '';
    $skip_tfa_for_users = isset($settings['skip_tfa_for_users']) && $settings['skip_tfa_for_users'] == 1 ? 'checked' : '';
    $enable_otp_login = isset($settings['enable_tfa_passwordless_login']) && $settings['enable_tfa_passwordless_login'] == 1 ? 'checked' : '';
    
    // Add disabled attribute if enforce TFA is not checked
    $skip_tfa_disabled = !$enabled_tfa ? 'disabled' : '';
    $passwordless_disabled = !$enabled_tfa ? 'disabled' : '';
    
    $enable_change_2fa_method  = isset($settings['enable_change_2fa_method']) && $settings['enable_change_2fa_method'] == 1 ? 'checked' : '';
    $enable_remember_device    = isset($settings['remember_device']) &&  $settings['remember_device'] == 1 ? 'checked' : '';
    $enable_2fa_backup_method  = isset($settings['enable_backup_method']) && $settings['enable_backup_method'] == 1 ? 'checked' : '';
    $enable_2fa_backup_type    = isset($settings['enable_backup_method_type']) ? $settings['enable_backup_method_type'] : 'none';
    $kbaSet1                  = isset($settings['tfa_kba_set1']) ? $settings['tfa_kba_set1'] : $kbasetofques1;
    $kbaSet2                  = isset($settings['tfa_kba_set2']) ? $settings['tfa_kba_set2'] : $kbasetofques2;
    $kbaSet1 = $settings['tfa_kba_set1'] ? $settings['tfa_kba_set1'] : '';
    $kbaSet2 = $settings['tfa_kba_set2'] ? $settings['tfa_kba_set2'] : '';
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $details = commonUtilitiesTfa::getCustomerDetails();
    $customerEmail = $details['email'];

    $inlineDisabled = '';
    // Allow TFA for demo accounts
    // if (is_null($details['license_type']) || empty($details['license_type'])) {
    //     $inlineDisabled = 'disabled';
    // }

    $featureDisable = '';
    if (!$isCustomerRegistered) {
        $featureDisable = 'disabled';
    }

    $licenseExpired = false;
    // Skip license expiry check for demo accounts
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
    $disabled = 'disabled';
    $active2FA = commonUtilitiesTfa::getActive2FAMethods();
    $current_user     = Factory::getUser();
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $isFirstUser          = commonUtilitiesTfa::isFirstUser($current_user->id);


?>

    <div class="mo_boot_row mo_boot_m-1">
        <div class="mo_boot_col-sm-12"><?php
            if (!commonUtilitiesTfa::isCustomerRegistered()) {
                echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
            }
            ?>
        </div>
        <div class="mo_boot_col-sm-12">
            <div class="mo_boot_row  mo_boot_text-center">
                <div class="mo_boot_col-sm-12"><br>
                    <h3><?php echo Text::_('COM_MINIORANGE_LOGIN_SETTINGS'); ?></h3>
                    <hr>
                </div>
            </div>
            <div class="mo_boot_col-sm-12 mo_boot_mt-2">
                <form name="f" class="miniorange_tfa_settings_form" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveTfaSettings'); ?>">
                    <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-12">
                            <input type="checkbox" id="enable_2fa_users" class=" mo_boot_m-0" onchange="enable_tfa_change()" name="enable_mo_tfa" <?php echo $enabled_tfa; ?> <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_ENABLE2FA'); ?></strong>
                            <br><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?>&nbsp;</strong><?php echo Text::_('COM_MINIORANGE_ENABLE2FA_NOTE'); ?></span><br>
                        </div>
                    </div>
                    <div class="mo_boot_row">
                        <div class=" mo_boot_col-sm-3  mo_boot_col-lg-2  mo_boot_offset-1 mo_boot_mt-2">
                            <strong><?php echo Text::_('COM_MINIORANGE_ENABLE2FA_FOR'); ?> </strong>
                        </div>
                       <div class="mo_boot_col-sm-8  mo_boot_col-lg-4 mo_boot_mt-2">
                            <select class=" mo_boot_form-control mo_tfa_select_user" name="enable_2fa_user_type" id="enable_2fa_user_type" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?>>
                                <option value="both" <?php echo $enable_2fa_user_type == 'both' ? "selected" : ""; ?>> <?php echo Text::_('COM_MINIORANGE_2FA_BOTH'); ?> </option>
                                <option value="admin" <?php echo $enable_2fa_user_type == 'admin' ? "selected" : ""; ?>> <?php echo Text::_('COM_MINIORANGE_2FA_ADMIN_SUPERUSERS_BACKEND'); ?> </option>
                                <option value="site" <?php echo $enable_2fa_user_type == 'site' ? "selected" : ""; ?>> <?php echo Text::_('COM_MINIORANGE_2FA_ENDUSERS_FRONTEND'); ?> </option>
                            </select>
                        </div>
                    </div>
                    <p class=" mo_boot_my-4">
                        <input type="checkbox" name="enable_mo_tfa_inline" id="enable_mo_tfa_inline" class=" mo_boot_m-0"  onchange="change_tfaInline()" <?php echo $inline . ' ' . $inlineDisabled; ?> />
                        &emsp;<strong><?php echo Text::_('COM_MINIORANGE_DISABLE2FA'); ?></strong>
                        <br><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?>&nbsp;</strong> <?php echo Text::_('COM_MINIORANGE_DISABLE2FA_DESC'); ?><br>
                    </p>
                    <p class="mo_boot_my-4">
                        <input type="checkbox" class="mo_boot_m-0" name="skip_tfa_for_users" id="skip_tfa_for_users" onchange="toggleCheckboxes(this, 'skip_tfa_for_users', 'enable_otp_login')" value="1" <?php echo $skip_tfa_for_users; ?> />
                        &emsp;<strong><?php echo Text::_('COM_MINIORANGE_SKIP2FA'); ?></strong>
                        <br><strong>&emsp;&emsp;<?php echo Text::_('COM_MINIORANGE_NOTE'); ?>&nbsp;</strong><?php echo Text::_('COM_MINIORANGE_SKIP2FA_DESC'); ?><br>
                    <div id="enqueueMessage2" style="display:none; color: #ff0000;"></div>
                    </p>
                    <p class=" mo_boot_my-4 ">
                        <input type="checkbox" class="mo_boot_m-0" name="enable_change_2fa_method" id="change_tfa_method" onchange="change_2fa_method()" <?php echo $inlineDisabled; ?> <?php echo $enable_change_2fa_method; ?> <?php echo " " . $featureDisable ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_CHANGE2FA'); ?></strong><br>
                        <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?> </strong> <?php echo Text::_('COM_MINIORANGE_CHANGE2FA_DESC'); ?><br>
                        <span class="mo_tfa_red"><?php echo Text::_('COM_MINIORANGE_CHANGETFA_NOTE_MENU'); ?>
                        </span>
                    </p>
                    <p class=" mo_boot_my-4 ">
                        <input type="checkbox" class="mo_boot_m-0"name="enable_remember_device" id="enable_remember_device" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?><?php echo $enable_remember_device ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_REMEMBER_DEVICE_ENABLE'); ?></strong>
                        <br><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?> </strong> <?php echo Text::_('COM_MINIORANGE_REMEMBER_DEVICE_DESC'); ?><br>
                    </p>
                    <hr><br>
                    <div class="mo_boot_row mo_boot_my-3">
                        <div class="mo_boot_col-lg-5 mo_boot_col-sm-12">
                            <input type="checkbox" class=" mo_boot_m-0" id="enable_2fa_backup_method" onchange="enable_backup_change()" name="enable_2fa_backup_method" <?php echo $enable_2fa_backup_method; ?> <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_BACKUP_METHODS'); ?></strong>
                        </div> 
                        <div class="mo_boot_col-sm-12  mo_boot_col-lg-6">
                            <select class=" mo_boot_form-control mo_tfa_select_user" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> onchange="show_kba_question()" name="enable_2fa_backup_type" id="enable_2fa_backup_type">
                                <option value="securityQuestion" <?php echo $enable_2fa_backup_type == 'securityQuestion' ? "selected" : ""; ?>><?php echo Text::_('COM_MINIORANGE_SECURITYQUES'); ?></option>
                                <option value="backupCodes" <?php echo $enable_2fa_backup_type == 'backupCodes' ? "selected" : ""; ?>><?php echo Text::_('COM_MINIORANGE_BACKUP_CODES'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="mo_boot_row mo_boot_my-3" id="setup_kba_questions">
                        <div class="mo_boot_col-sm-12 mo_boot_py-4 mo_tfa_border_que">
                            <ul>
                                <i>
                                    <p><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?> </strong><?php echo Text::_('COM_MINIORANGE_KBAQ'); ?></p>
                                </i>
                                <li>
                                    <label for="KBA_set_ques1"><strong><?php echo Text::_('COM_MINIORANGE_KBAQ1'); ?></strong></label>
                                    <textarea class="mo_tfa_kba_qes" name="KBA_set_ques1" id="KBA_set_ques1" <?php echo $featureDisable ?> cols="30" rows="5"><?php echo $kbaSet1; ?> </textarea>
                                </li>
                                <hr>
                                <li>
                                    <label for="KBA_set_ques2"><strong><?php echo Text::_('COM_MINIORANGE_KBAQ2'); ?></strong></label>
                                    <textarea class="mo_tfa_kba_qes" name="KBA_set_ques2" id="KBA_set_ques2" <?php echo $featureDisable ?> cols="30" rows="5"><?php echo $kbaSet2; ?></textarea>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <br>
                    <div class="mo_boot_row mo_boot_my-3">
                        <div class="mo_boot_col-sm-12 mo_boot_py-4 mo_tfa_border_que">
                            <h3><?php echo Text::_('COM_MINIORANGE_PASSLESS_LOGIN'); ?></h3>
                            <hr>
                            <p class="alert-info mo_boot_p-2">
                                <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_OTP_LOGIN_DESCRIPTION'); ?><br>
                            </p>
                            <p>
                                <input type="checkbox" class="mo_boot_m-0" name="enable_tfa_passwordless_login" id="enable_otp_login" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?><?php echo $enable_otp_login ?> onchange="toggleCheckboxes(this, 'enable_otp_login', 'skip_tfa_for_users')" /> &emsp;<strong><?php echo Text::_('COM_MINIORANGE_OTP_LOGIN'); ?></strong>
                            </p>
                            <div id="enqueueMessage1"></div>
                            <div id="enqueueMessage2"></div>
                        </div>
                    </div>
                    <div class="mo_boot_row mo_boot_my-3">
                        <div class="mo_boot_col-sm-12 mo_boot_py-4 mo_tfa_border_que">
                            <h3><?php echo Text::_('COM_MINIORANGE_2FA_METHODS'); ?></h3>
                            <hr>
                            <p>
                                <i><?php echo Text::_('COM_MINIORANGE_2FA_METHODS_DESC'); ?></i></br>
                            </p>
                            <p>
                                <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_2FA_METHODS_DESC_NOTE'); ?>
                            </p>


                            <table class="table-responsive  mo_boot_table mo_boot_table-hover">
                                <thead class="mo_boot_table-active">
                                    <tr>
                                         <th scope="col"><?php echo Text::_('COM_MINIORANGE_TOTP_METHODS'); ?></th>
                                         <th scope="col">
                                            <?php echo Text::_('COM_MINIORANGE_OTP_METHODS'); ?>
                                         </th>
                                         <th scope="col">
                                            <?php echo Text::_('COM_MINIORANGE_OTHER_METHODS'); ?>
                                         </th>

                                    </tr>
                                </thead>
                                                                    <tbody>
                                    <tr>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_google" <?php echo ($active2FA['google']['active']) ? 'checked=true' : '' ?> <?php echo $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_GA'); ?></td>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_OOS" <?php echo ($active2FA['OOS']['active']) ? 'checked=true' : '' ?> <?php echo (empty($details['license_type']) || $details['license_type'] === 'demo') ? 'disabled' : $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_OOS'); ?>   <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_YK" <?php echo ($active2FA['YK']['active']) ? 'checked=true' : '' ?> <?php echo (empty($details['license_type']) || $details['license_type'] === 'demo') ? 'disabled' : $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_TFA_METHODS_YUBIKEY'); ?>   <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                    </tr>
                                    <tr>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_MA" <?php echo ($active2FA['MA']['active']) ? 'checked=true' : '' ?> <?php echo $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_MA'); ?></td>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_OOE" <?php echo ($active2FA['OOE']['active']) ? 'checked=true' : '' ?> <?php echo (empty($details['license_type']) || $details['license_type'] === 'demo') ? 'disabled' : $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_OOE'); ?>   <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                        <td><input type="checkbox" name="tfa_method_allowed_DUON" disabled />&emsp;<?php echo Text::_('COM_MINIORANGE_DUO_PUSH_NOTIFY'); ?> <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                    </tr>
                                    <tr>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_AA" <?php echo ($active2FA['AA']['active']) ? 'checked=true' : '' ?> <?php echo $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_AA'); ?></td>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_OOSE" <?php echo ($active2FA['OOSE']['active']) ? 'checked=true' : '' ?> <?php echo (empty($details['license_type']) || $details['license_type'] === 'demo') ? 'disabled' : $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_OOSOE'); ?>   <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_LPA" <?php echo ($active2FA['LPA']['active']) ? 'checked=true' : '' ?> <?php echo $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_LA'); ?></td>
                                        <td><input class="methods_checkbox" type="checkbox" name="tfa_method_allowed_OOC" <?php echo ($active2FA['OOC']['active']) ? 'checked=true' : '' ?> <?php echo (empty($details['license_type']) || $details['license_type'] === 'demo') ? 'disabled' : $inlineDisabled ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_OOC'); ?>   <?php if (empty($details['license_type'])): ?>
                                                <sup><img class="crown_img_small mo_boot_ml-2" src="<?php echo Uri::base();?>components/com_miniorange_twofa/assets/images/crown.webp" title="Premium Feature" onclick="window.location.href='<?php echo Route::_('index.php?option=com_miniorange_twofa&view=Licensing'); ?>'"></sup>
                                            <?php endif; ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><input class="methods_checkbox " type="checkbox" name="tfa_method_allowed_DUO" <?php echo ($active2FA['DUO']['active']) ? 'checked=true' : '' ?> />&emsp;<?php echo Text::_('COM_MINIORANGE_DA'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="mo_boot_col-sm-12 mo_boot_my-3  mo_boot_text-center">
                        <?php
                        if ($isCustomerRegistered && $isFirstUser) { ?>
                            <input type="submit" name="submit_login_settings" value="<?php echo Text::_('COM_MINIORANGE_VAL_SAVE'); ?>" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns" <?php echo $featureDisable ?>>
                        <?php }
                        ?>
                    </div>
                    <script>
                        function displayMessage(checkboxId, action) {
                            let message = '';
                            const messageDivs = {
                                'enable_otp_login': document.getElementById("enqueueMessage1"),
                                'skip_tfa_for_users': document.getElementById("enqueueMessage2")
                            };

                            Object.values(messageDivs).forEach(div => div.innerHTML = '');

                            if (checkboxId === 'enable_otp_login' && action === 'enabled') {
                                message = "<?php echo Text::_('COM_MINIORANGE_ENABLE_OTP_LOGIN'); ?>";
                                messageDivs['enable_otp_login'].innerHTML = message;
                                messageDivs['enable_otp_login'].style.display = "block";
                            } else if (checkboxId === 'skip_tfa_for_users' && action === 'enabled') {
                                message = "<?php echo Text::_('COM_MINIORANGE_SKIP_TFA_SETUP'); ?>";
                                messageDivs['skip_tfa_for_users'].innerHTML = message;
                                messageDivs['skip_tfa_for_users'].style.display = "block";
                            }
                        }
                    </script>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
<?php
}

function UserManagement()
{
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $featureDisable = '';
    if (!$isCustomerRegistered) {
        $featureDisable = 'disabled';
    }

    $details = commonUtilitiesTfa::getCustomerDetails();
    $groups = commonUtilitiesTfa::loadGroups();

    $inlineDisabled = '';
    $licenseExpired = false;

    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }

    $base_url = Uri::base() . 'index.php?option=com_miniorange_twofa&task=setup_two_factor.joomlapagination';

?>

    <div class="mo_boot_row mo_boot_m-1">

        <div class="mo_boot_col-sm-12"><?php
            if (!commonUtilitiesTfa::isCustomerRegistered()) {
                echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
            }
            ?>
        </div>
        <div class="mo_boot_col-sm-12 mo_boot_mt-2">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12   mo_boot_text-center"><br>
                    <h3><?php echo Text::_('COM_MINIORANGE_USER_MANAGE'); ?></h3>
                    <hr>
                </div>
                <div class="mo_boot_col-sm-12 ">
                    <div class="alert alert-info mo_boot_p-2 mo_tfa_user_note">

                        <p>
                            <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong><?php echo Text::_('COM_MINIORANGE_RESET2FA'); ?><strong><?php echo Text::_('COM_MINIORANGE_RESET_DESC'); ?></strong>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mo_boot_card w-100">
                <div class="mo_boot_card-body mo_tfa_user_mo_boot_card">
                    <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                    <div class="mo_boot_row mo_boot_mt-2">
                        <div class="  mo_boot_col-lg-1  mo_boot_col-sm-5 mo_boot_px-2">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_SHOW_PAGES'); ?><br></strong>
                                <select id="select_number" class=" mo_boot_filter-input mo_tfa_user_list mo_boot_p-0" min="10" onchange="list_of_entry()" <?php echo $inlineDisabled ?>>
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </label>
                        </div>
                        <div class=" mo_boot_col-sm-7  mo_boot_col-lg-3  mo_boot_p-0">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_FILTER_USERNAME_EMAIL'); ?></strong>
                                <input type="text" id="search_name" name="search_name" class=" mo_tfa_user_email mo_boot_filter-input  mo_boot_col-lg-12" placeholder="<?php echo Text::_('COM_MINIORANGE_SEARCH'); ?>" <?php echo $inlineDisabled ?>>
                            </label>
                        </div>
                        <div class=" mo_boot_col-sm-5   mo_boot_col-lg-2 mo_tfa_roles">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_FILTER_ROLES'); ?></strong>
                                <select type="text" id="search_role" name="search_role" class="w-100 mo_boot_filter-input" placeholder="Search" <?php echo $inlineDisabled ?>>
                                    <option value="any" selected><?php echo Text::_('COM_MINIORANGE_VAL_ANY'); ?></option>
                                    <?php
                                    foreach ($groups as $key => $value) {
                                        echo '<option value="' . $value['title'] . '">' . $value['title'] . '</option>';
                                    }
                                    ?>

                                </select>
                            </label>
                        </div>
                        <div class=" mo_boot_col-sm-5  mo_boot_col-lg-2 mo_boot_px-2">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_FILTER_STATUS'); ?></strong>
                                <select type="text" id="search_status" name="search_status" class="w-100 mo_boot_filter-input" placeholder="Search" <?php echo $inlineDisabled ?>>
                                    <option value="any" selected><?php echo Text::_('COM_MINIORANGE_VAL_ANY'); ?></option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </label>
                        </div>
                        <div class="mo_boot_filter mo_boot_p-0  mo_boot_col-lg-4 mo_boot_col-sm-12 mo_boot_mt-2">

                            <input type="button" name="filter_search" class="mo_boot_btn btn-reset mo_boot_btn-primary mo_tfa_btns_dark mo_boot_mr-3 mo_otp_btns <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="<?php echo Text::_('COM_MINIORANGE_VAL_FILTER'); ?>" onclick="searchFilter()" <?php echo $inlineDisabled ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                            <input type="button" name="reset_filters" class="mo_boot_btn btn-reset mo_boot_btn-primary mo_tfa_btns_dark mo_boot_mr-3 mo_otp_btns <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="<?php echo Text::_('COM_MINIORANGE_RESET_FILTER'); ?>" onclick="resetFilters()" <?php echo $inlineDisabled ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                            <button name="refresh_page" class="mo_boot_btn btn-reset mo_boot_btn-primary mo_tfa_btns_dark mo_otp_btns <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" value="" onclick="refreshFilters()" <?php echo $inlineDisabled ?> <?php echo $licenseExpired ? 'disabled' : ''; ?>><i class="fa fa-refresh" aria-hidden="true"></i></button>
                        </div>
                    </div><br>
                    </fieldset>
                </div>
            </div>
            <div id="show_paginations1" class="mo_boot_mt-2">
            </div>
            <input class="pager" type="hidden" id="next_page_user" value="0"><br>
            <div class="mo_boot_col-sm-12  mo_boot_mb-2" id="pagination_buttons" style="display: none;">
                <div id="next_btn">
                    <a href="#" onclick="next_or_prev_page('next', 'preserve');" aria-label="Next" class="mo_tfa_nxt_pg mo_tfa_white_dark">
                        <span aria-hidden="true"><u><?php echo Text::_('COM_MINIORANGE_NEXT_PAGE'); ?></u>&nbsp;<i class="fa fa-angle-double-right" aria-hidden="true"></i></span>
                    </a>

                </div>
                <div id="pre_btn">
                    <a href="#" onclick="next_or_prev_page('pre', 'preserve');" aria-label="Next" class="mo_tfa_prev_pg mo_tfa_white_dark">
                        <span aria-hidden="true"><i class="fa fa-angle-double-left" aria-hidden="true"></i>&nbsp;<u><?php echo Text::_('COM_MINIORANGE_PREVIOUS_PAGE'); ?></u></span>
                    </a>
                </div>
            </div>
        </div><br>
        <script>
            function refreshFilters() {
                $.ajax({
                    url: '<?php echo $base_url; ?>',
                    method: 'GET',
                    success: function(response) {
                        // Update the content on the page with the new data
                        $('#show_paginations1').html(response);
                    }
                });
            }

            function next_or_prev_page(button, order = 'down') {
                var page = document.getElementById('next_page_user').value;
                var orderBY = 'down';
                no_of_entry = jQuery("#select_number").val();
                if (button == 'on')
                    page = 0;
                if (order == 'up')
                    orderBY = 'up';
                if (order == 'preserve')
                    orderBY = 'down';
                page = parseInt(page);
                if (button == 'pre' && page != 0) {
                    page -= 2;
                    document.getElementById('next_page_user').value = page;
                    document.getElementById('next_btn').style.display = "inline";
                }
                if (page == 0) {
                    document.getElementById('pre_btn').style.display = "none";
                    document.getElementById('next_btn').style.display = "inline";
                } else
                    document.getElementById('pre_btn').style.display = "inline";
                var correct_url = "<?php echo Uri::base(); ?>index.php?option=com_miniorange_twofa&task=setup_two_factor.joomlapagination";
                jQuery.ajax({
                    url: correct_url,
                    dataType: "text",
                    method: "POST",
                    data: {
                        'page': page,
                        'orderBY': orderBY,
                        'no_of_entry': no_of_entry
                    },
                    success: function(data) {
                        if (data.search('form-login') !== -1 || data.search('com_login') !== -1) {
                            window.location.reload();
                        }
                        var arr = data.split("separator_for_count");
                        jQuery("#show_paginations1").html(arr[0]);
                        
                        // Show/hide pagination buttons based on number of entries
                        var totalEntries = parseInt(arr[1]) || 0;
                        var entriesPerPage = parseInt(no_of_entry) || 10;
                        
                        if (totalEntries > entriesPerPage) {
                            document.getElementById('pagination_buttons').style.display = "block";
                        } else {
                            document.getElementById('pagination_buttons').style.display = "none";
                        }
                        
                        if (arr[1] == 0) {
                            document.getElementById('next_page_user').value = 0;
                            next_or_prev_page('next', 'preserve');
                        }
                    }
                });
                page += 1;
                document.getElementById('next_page_user').value = page;
            }
        </script>
    </div>
<?php
}

function popup()
{
    $settings = commonUtilitiesTfa::getTfaSettings();
    $current_user     = Factory::getUser();
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $isFirstUser          = commonUtilitiesTfa::isFirstUser($current_user->id);
    $details = commonUtilitiesTfa::getCustomerDetails();

    $inlineDisabled = '';

    $featureDisable = '';
    if (!$isCustomerRegistered) {
        $featureDisable = 'disabled';
    }
    $licenseExpired = false;
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
    $CustomCssSaved  = isset($settings['customFormCss']) && !empty($settings['customFormCss'])
        ? $settings['customFormCss']
        : "";

    $CustomButtonSaved = isset($settings['primarybtnCss']) && !empty($settings['primarybtnCss'])
        ? $settings['primarybtnCss']
        : array();

    $customCssSaved = explode(";", $CustomCssSaved);
    $fields = array();
    foreach ($customCssSaved as $key => $value) {
        $breakCss = explode(":", $value);
        $fields[$breakCss[0]] = isset($breakCss[1]) ? $breakCss[1] : "";
    }

    $border = isset($fields['border-top']) ? explode(" ", $fields['border-top']) : "";

    if (is_array($border)) {
        $border0 = $border['0'];
        $border1 = $border['2'];
    } else {
        $border0 = "";
        $border1 = "";
    }

?>

    <div class="mo_boot_row mo_boot_m-1">
        <div class="mo_boot_col-sm-12"><?php
            if (!commonUtilitiesTfa::isCustomerRegistered()) {
                echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
            } ?>
        </div>
        <div class="mo_boot_col-sm-12 mo_boot_mt-3  mo_boot_text-center ">
            <h3><?php echo Text::_('COM_MINIORANGE_POPUPS'); ?></h3>
            <hr>
        </div>

        <div class="mo_boot_col-sm-12  mo_boot_col-lg-6  mo_boot_mb-3 mo_tfa_cust_popup">
            <form name="" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.updateCssConfig'); ?>" id="previewCSS">
                <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong><?php echo Text::_('COM_MINIORANGE_POPUP_MARGIN'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="number" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="margin" id="margin" min="0" value="<?php echo !empty($border0) ? (int)$border0 : 5; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong><?php echo Text::_('COM_MINIORANGE_POPUP_RADIUS'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="number" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="radius" id="radius" min="0" value="<?php echo isset($fields['border-radius']) ? (int)$fields['border-radius'] : 8; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong><?php echo Text::_('COM_MINIORANGE_POPUP_BGCOLOR'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="color" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="bgcolor" id="bgcolor" value="<?php echo isset($fields['background-color']) ? strtok($fields['background-color'], '!') : "#FFFFFF"; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong> <?php echo Text::_('COM_MINIORANGE_POPUP_BORDERCOLOR'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="color" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="bordertop" id="bordertop" value="<?php echo !empty($border1) ? (strtok($border1, '!')) : "#20b2aa"; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong><?php echo Text::_('COM_MINIORANGE_POPUP_BUTTONCOLOR'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="color" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="primarybtn" id="primarybtn" value="<?php echo !empty($CustomButtonSaved) ? strtok($CustomButtonSaved, '!') : "#2384d3"; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2">
                    <div class="mo_boot_col-sm-4">
                        <strong><?php echo Text::_('COM_MINIORANGE_POPUP_HEIGHT'); ?></strong>
                    </div>
                    <div class="mo_boot_col-sm-6">
                        <input type="number" <?php echo $featureDisable ?> <?php echo $inlineDisabled ?> class=" mo_boot_form-control" name="height" id="height" value="<?php echo isset($fields['min-height']) ? (int)$fields['min-height'] : "200"; ?>">
                    </div>
                </div>
                <div class="mo_boot_row mo_boot_mt-2  mo_boot_text-center">
                    <div class="mo_boot_col-sm-12 ">
                        <?php if ($isCustomerRegistered && $isFirstUser) {
                        ?>
                            <input id="css_submit" <?php echo $inlineDisabled; ?> type="submit" class="mo_boot_btn mo_boot_btn-primary mo_otp_btns mo_boot_m-2" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                            <input type="button" id="css_reset" <?php echo $inlineDisabled; ?> class="mo_boot_btn mo_boot_btn-danger mo_boot_m-2" value="<?php echo Text::_('COM_MINIORANGE_VAL_RESET_CSS'); ?>">
                            <?php } 
                        ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="mo_boot_col-sm-12  mo_boot_col-lg-6  mo_boot_mb-3 mo_tfa_bg_none_dark">
            <div class="container-fluid  mo_boot_text-center mo_tfa_auth_factor" s>
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12 offset-sm-12 mo_tfa_bg_none_dark">
                        <?php if ($featureDisable == 'disabled') { ?>
                            <p><?php echo Text::_('COM_MINIORANGE_PREVIEW'); ?></p>
                        <?php
                        } elseif ($featureDisable != 'disabled') {
                        ?>
                            <div class="mo_boot_row mo_tfa_main " id="previewform">
                                <div class="mo_boot_col-sm-12 mo_tfa_title">
                                    <center> <span><?php echo Text::_('COM_MINIORANGE_AUTHENTICATE'); ?></span></center>
                                </div>

                                <div class="mo_boot_col-sm-12 mo_boot_mt-3">
                                    <form name="f" method="post" action="">
                                        <div class="mo_boot_row  mo_boot_mb-3">
                                            <div class="mo_boot_col-sm-10 mo_boot_mx-4">
                                                <div class="p-1 alert-info mo_tfa_text"><span><?php echo Text::_('COM_MINIORANGE_AUTHENTICATE_PASSCODE'); ?> <strong>Google <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR_APP'); ?></span></div>

                                            </div>
                                        </div>
                                        <div class="mo_boot_row">
                                            <div class="mo_boot_col-sm-10 mo_boot_mx-4">
                                                <input type="text" class="input  mo_boot_form-control mo_tfa_text" name="passcode" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_PASSCODE'); ?>" disabled />
                                            </div>
                                        </div>
                                        <div class="mo_boot_row mx-2">
                                            <div class="mo_boot_col-sm-12 text-left mo_boot_mt-1">
                                                <input class="mini_2fa_tab" type="checkbox" name="reconfigure_2fa" />&nbsp;<span class="mo_tfa_remeber_choice"><?php echo Text::_('COM_MINIORANGE_REMEMBER_DEVICE'); ?></span><br><br>
                                            </div>
                                        </div>
                                        <div class="mo_boot_row mo_boot_m-2  mo_boot_text-center">
                                            <div class=" mo_boot_col-12">
                                                <a class="forgot_phn mo_tfa_forgot_phne"><?php echo Text::_('COM_MINIORANGE_FORGOT_PHONE'); ?></a>
                                            </div>
                                            <div class=" mo_boot_col-12 mo_boot_mt-3">
                                                <input type="button" id="previewbutton1" name="validate_passcode" class="mo_boot_btn mo_boot_btn-primary mo_tfa_validate_btn  mo_boot_mx-1" value="<?php echo Text::_('COM_MINIORANGE_VALIDATE'); ?>" />
                                                <input type="button" name="Start_registration" class="mo_boot_btn mo_boot_btn-danger mo_boot_mr-3" value="<?php echo Text::_('COM_MINIORANGE_CANCEL'); ?>" />
                                            </div>
                                        </div>

                                </div>
                            </div>
                        <?php
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}


function support()
{
    $user = Factory::getUser();
    $admin_email = $user->email;
?>
    <div class="mo_boot_row mo_boot_m-1">
        <div class="mo_boot_col-sm-12 mo_boot_mt-3  mo_boot_text-center">
            <h3>
                <?php echo Text::_('COM_MINIORANGE_SUPPORT_FEATURE'); ?>
                <span id="mini-icons">
                    <a href="https://faq.miniorange.com/" target="_blank" class="mo_boot_btn mo_boot_btn-success  mo_tfa_faq_btn  mo_boot_py-1">FAQ's</a>
                </span>
            </h3>
            <hr>
        </div>
        <div class="mo_boot_col-sm-12 mo_boot_mt-2">
            <details class="mo_details" open>
                <summary><?php echo Text::_('COM_MINIORANGE_SUPPORT'); ?></summary>
                <br>
                <div class="mo_boot_row mo_boot_m-2">
                    <?php
                    $arrContextOptions = array(
                        "ssl" => array(
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        )
                    );
                    $context = stream_context_create($arrContextOptions);

                    $details = commonUtilitiesTfa::getCustomerDetails();
                    $strJsonTime = file_get_contents(Uri::root() . "/administrator/components/com_miniorange_twofa/assets/json/timezones.json", false, $context);
                    $timezoneJsonArray = json_decode($strJsonTime, true);
                    ?>
                    <div class="mo_boot_col-sm-12">
                        <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.support'); ?>">
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12  mo_boot_text-center  mo_boot_mb-2">
                                    <p><?php echo Text::_('COM_MINIORANGE_NEED_HELP'); ?></p>
                                </div>
                            </div>
                            <div class="mo_boot_row pb-4">
                                <div class="mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_EMAIL'); ?><span class="mo_tfa_red">*</span>&nbsp;</strong>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <input type="email" name="email" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_ENTER_EMAIL'); ?>" class="  mo_boot_form-control " value="<?php echo $admin_email ?>" required="true" />
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class="mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_PHONE'); ?>&nbsp;</strong>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <input type="tel" name="phone" pattern="[\+]\d{11,14}|[\+]\d{1,4}([\s]{0,1})(\d{0}|\d{9,10})" class="mo_boot_form-control "  placeholder="<?php echo Text::_('COM_MINIORANGE_PHONE_MSG'); ?>" value="" />
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class="mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_QUERY'); ?><span class="mo_tfa_red">*</span>&nbsp;</strong>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <textarea cols="52" rows="4" name="query" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_QUERY'); ?>" required="true" class="mo_tfa_req_query mo_boot_form-control"></textarea>
                                </div>
                            </div>
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12  mo_boot_text-center">
                                    <input type="submit" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT_QUERY'); ?>" name="submit_query" class="mo_boot_btn mo_tfa_btns_dark mo_tfa_support_btns">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </details>
             <details class="mo_details">
                <summary><?php echo Text::_('COM_MINIORANGE_TRIAL_DEMO_REQUEST'); ?></summary>
                <br>
                <div class="mo_boot_row mo_boot_m-2">
                    <div class="mo_boot_col-sm-12">
                        <div class="mo_boot_text-center mo_boot_mb-4">
                            <h4><?php echo Text::_('COM_MINIORANGE_TRIAL_DEMO_HEADING'); ?></h4>
                        </div>
                        <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.requestTrial'); ?>">
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_REQUEST_TYPE'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <select id="request_type" name="request_type" class="mo_boot_form-control mo_boot_text-center mo_tfa_select_user" required>
                                        <option disabled selected><?php echo Text::_('COM_MINIORANGE_SELECT_REQUEST_TYPE'); ?></option>
                                        <option value="trial"><?php echo Text::_('COM_MINIORANGE_FREE_TRIAL_OPTION'); ?></option>
                                        <option value="demo"><?php echo Text::_('COM_MINIORANGE_REQUEST_DEMO_OPTION'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_EMAIL'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <input type="email" name="email" class="mo_boot_form-control" value="<?php echo $admin_email; ?>" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_EMAIL_PLACEHOLDER'); ?>" required>
                                </div>
                            </div>
                            <div class="mo_boot_row">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_DESCRIPTION'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <textarea cols="52" rows="4" name="description" placeholder="<?php echo Text::_('COM_MINIORANGE_DESCRIPTION_PLACEHOLDER'); ?>" class="mo_tfa_req_query mo_boot_form-control" required></textarea>
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_text-center">
                                <div class="mo_boot_col-sm-12">
                                    <input type="submit" value="<?php echo Text::_('COM_MINIORANGE_SUBMIT_REQUEST'); ?>" class="mo_boot_btn mo_tfa_btns_dark mo_tfa_support_btns mo_boot_mt-3">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </details>
            <details class="mo_details">
                <summary><?php echo Text::_('COM_MINIORANGE_QUOTE'); ?></summary>
                <br>
                <div class="mo_boot_row mo_boot_m-2">
                    <?php
                    $arrContextOptions = array(
                        "ssl" => array(
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        )
                    );
                    $context = stream_context_create($arrContextOptions);
                    $details = commonUtilitiesTfa::getCustomerDetails();
                    $strJsonTime = file_get_contents(Uri::root() . "/administrator/components/com_miniorange_twofa/assets/json/timezones.json", false, $context);
                    $timezoneJsonArray = json_decode($strJsonTime, true);
                    ?>
                    <div class="mo_boot_col-sm-12">
                        <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.requestQuote'); ?>">
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_METHODS'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <select id="type_service" name="type_service" class=" mo_boot_form-control  mo_boot_text-center mo_tfa_select_user" required>
                                        <option disabled selected>--------<?php echo Text::_('COM_MINIORANGE_METHODSELECT'); ?>--------</option>
                                        <option id="google_auth" value="google_auth"><?php echo Text::_('COM_MINIORANGE_GA'); ?></option>
                                        <option id="microsoft_auth" value="microsoft_auth"><?php echo Text::_('COM_MINIORANGE_MA'); ?></option>
                                        <option id="LPA" value="LPA"><?php echo Text::_('COM_MINIORANGE_LA'); ?></option>
                                        <option id="AA" value="AA"><?php echo Text::_('COM_MINIORANGE_AA'); ?></option>
                                        <option id="duo_auth" value="duo_auth"><?php echo Text::_('COM_MINIORANGE_DA'); ?></option>
                                        <option id="sms" value="SMS"><?php echo Text::_('COM_MINIORANGE_OOS'); ?></option>
                                        <option id="email" value="Email"><?php echo Text::_('COM_MINIORANGE_OOE'); ?>l</option>
                                        <option id="OOSE" value="OOSE"><?php echo Text::_('COM_MINIORANGE_OOSOE'); ?></option>
                                        <option id="kba" value="kba"><?php echo Text::_('COM_MINIORANGE_SECURITY_QUES'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_EMAIL'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <input type="email" name="email" class=" mo_boot_form-control" value="<?php echo $admin_email; ?>" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_ENTER_EMAIL'); ?>">
                                </div>
                            </div>

                            <div class="mo_boot_row  mo_boot_mb-3">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_USERS_NO'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                    <input type="number" name="no_of_users" class=" mo_boot_form-control" value="" min="10" placeholder="10" required>
                                </div>
                            </div>

                            <div class="mo_boot_row mo_tfa_disply_none " id="no_of_otp">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_OTP_NO'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="col-sm-  mo_boot_pb-2">
                                    <input type="number" name="no_of_otp" class=" mo_boot_form-control" pattern="^[1-9][0-9]*$" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_OTP_NO'); ?>">
                                </div>
                            </div>

                            <div class="mo_boot_row mo_tfa_disply_none" id="type_country">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_COUNTRY'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8  mo_boot_pb-2">
                                    <select name="select_country" id="select_country" class=" mo_boot_form-control">
                                        <option disabled selected>--------<?php echo Text::_('COM_MINIORANGE_TYPESELECT'); ?>--------</option>
                                        <option value="allcountry"><?php echo Text::_('COM_MINIORANGE_ALLCOUNTRIES'); ?></option>
                                        <option value="singlecountry"><?php echo Text::_('COM_MINIORANGE_SINGLECOUNTRIES'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mo_boot_row mo_tfa_disply_none" id="select_type_country">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_SELECT_COUNTRY'); ?></strong><span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8  mo_boot_mb-2">
                                    <select class=" mo_boot_form-control" data-size="8" name="which_country" id="which_country" data-live-search="true">
                                        <option value="default" disabled selected><?php echo Text::_('COM_MINIORANGE_SELECT_COUNTRY1'); ?></option>
                                        <?php
                                        $countries = countryList();
                                        foreach ($countries as $data) {
                                            if ($data['name'] != "All Countries")
                                                echo "<option value='" . $data['name'] . "'>" . $data['name'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mo_boot_row">
                                <div class=" mo_boot_col-sm-3">
                                    <strong><?php echo Text::_('COM_MINIORANGE_QUERY'); ?></strong> <span class="mo_tfa_red">*</span>
                                </div>
                                <div class="mo_boot_col-sm-8">
                                <textarea cols="52" rows="4" name="user_extra_requirement" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_REQUIREMENT'); ?>" required="true" class="mo_tfa_req_query mo_boot_form-control"></textarea>
                                </div>
                            </div>
                            <div class="mo_boot_row  mo_boot_text-center">
                                <div class="mo_boot_col-sm-12">
                                    <input type="submit" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>" class="mo_boot_btn mo_tfa_btns_dark mo_tfa_support_btns mo_boot_mt-3">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </details>
           
        </div>
    </div>
<?php
}

function countryList()
{
    $countries = array(
        array(
            'name' => 'All Countries',
            'alphacode' => '',
            'countryCode' => ''
        ),
        array(
            'name' => 'Afghanistan (‫افغانستان‬‎)',
            'alphacode' => 'af',
            'countryCode' => '+93'
        ),
        array(
            'name' => 'Albania (Shqipëri)',
            'alphacode' => 'al',
            'countryCode' => '+355'
        ),
        array(
            'name' => 'Algeria (‫الجزائر‬‎)',
            'alphacode' => 'dz',
            'countryCode' => '+213'
        ),
        array(
            'name' => 'American Samoa',
            'alphacode' => 'as',
            'countryCode' => '+1684'
        ),
        array(
            'name' => 'Andorra',
            'alphacode' => 'ad',
            'countryCode' => '+376'
        ),
        array(
            'name' => 'Angola',
            'alphacode' => 'ao',
            'countryCode' => '+244'
        ),
        array(
            'name' => 'Anguilla',
            'alphacode' => 'ai',
            'countryCode' => '+1264'
        ),
        array(
            'name' => 'Antigua and Barbuda',
            'alphacode' => 'ag',
            'countryCode' => '+1268'
        ),
        array(
            'name' => 'Argentina',
            'alphacode' => 'ar',
            'countryCode' => '+54'
        ),
        array(
            'name' => 'Armenia (Հայաստան)',
            'alphacode' => 'am',
            'countryCode' => '+374'
        ),
        array(
            'name' => 'Aruba',
            'alphacode' => 'aw',
            'countryCode' => '+297'
        ),
        array(
            'name' => 'Australia',
            'alphacode' => 'au',
            'countryCode' => '+61'
        ),
        array(
            'name' => 'Austria (Österreich)',
            'alphacode' => 'at',
            'countryCode' => '+43'
        ),
        array(
            'name' => 'Azerbaijan (Azərbaycan)',
            'alphacode' => 'az',
            'countryCode' => '+994'
        ),
        array(
            'name' => 'Bahamas',
            'alphacode' => 'bs',
            'countryCode' => '+1242'
        ),
        array(
            'name' => 'Bahrain (‫البحرين‬‎)',
            'alphacode' => 'bh',
            'countryCode' => '+973'
        ),
        array(
            'name' => 'Bangladesh (বাংলাদেশ)',
            'alphacode' => 'bd',
            'countryCode' => '+880'
        ),
        array(
            'name' => 'Barbados',
            'alphacode' => 'bb',
            'countryCode' => '+1246'
        ),
        array(
            'name' => 'Belarus (Беларусь)',
            'alphacode' => 'by',
            'countryCode' => '+375'
        ),
        array(
            'name' => 'Belgium (België)',
            'alphacode' => 'be',
            'countryCode' => '+32'
        ),
        array(
            'name' => 'Belize',
            'alphacode' => 'bz',
            'countryCode' => '+501'
        ),
        array(
            'name' => 'Benin (Bénin)',
            'alphacode' => 'bj',
            'countryCode' => '+229'
        ),
        array(
            'name' => 'Bermuda',
            'alphacode' => 'bm',
            'countryCode' => '+1441'
        ),
        array(
            'name' => 'Bhutan (འབྲུག)',
            'alphacode' => 'bt',
            'countryCode' => '+975'
        ),
        array(
            'name' => 'Bolivia',
            'alphacode' => 'bo',
            'countryCode' => '+591'
        ),
        array(
            'name' => 'Bosnia and Herzegovina (Босна и Херцеговина)',
            'alphacode' => 'ba',
            'countryCode' => '+387'
        ),
        array(
            'name' => 'Botswana',
            'alphacode' => 'bw',
            'countryCode' => '+267'
        ),
        array(
            'name' => 'Brazil (Brasil)',
            'alphacode' => 'br',
            'countryCode' => '+55'
        ),
        array(
            'name' => 'British Indian Ocean Territory',
            'alphacode' => 'io',
            'countryCode' => '+246'
        ),
        array(
            'name' => 'British Virgin Islands',
            'alphacode' => 'vg',
            'countryCode' => '+1284'
        ),
        array(
            'name' => 'Brunei',
            'alphacode' => 'bn',
            'countryCode' => '+673'
        ),
        array(
            'name' => 'Bulgaria (България)',
            'alphacode' => 'bg',
            'countryCode' => '+359'
        ),
        array(
            'name' => 'Burkina Faso',
            'alphacode' => 'bf',
            'countryCode' => '+226'
        ),
        array(
            'name' => 'Burundi (Uburundi)',
            'alphacode' => 'bi',
            'countryCode' => '+257'
        ),
        array(
            'name' => 'Cambodia (កម្ពុជា)',
            'alphacode' => 'kh',
            'countryCode' => '+855'
        ),
        array(
            'name' => 'Cameroon (Cameroun)',
            'alphacode' => 'cm',
            'countryCode' => '+237'
        ),
        array(
            'name' => 'Canada',
            'alphacode' => 'ca',
            'countryCode' => '+1'
        ),
        array(
            'name' => 'Cape Verde (Kabu Verdi)',
            'alphacode' => 'cv',
            'countryCode' => '+238'
        ),
        array(
            'name' => 'Caribbean Netherlands',
            'alphacode' => 'bq',
            'countryCode' => '+599'
        ),
        array(
            'name' => 'Cayman Islands',
            'alphacode' => 'ky',
            'countryCode' => '+1345'
        ),
        array(
            'name' => 'Central African Republic (République centrafricaine)',
            'alphacode' => 'cf',
            'countryCode' => '+236'
        ),
        array(
            'name' => 'Chad (Tchad)',
            'alphacode' => 'td',
            'countryCode' => '+235'
        ),
        array(
            'name' => 'Chile',
            'alphacode' => 'cl',
            'countryCode' => '+56'
        ),
        array(
            'name' => 'China (中国)',
            'alphacode' => 'cn',
            'countryCode' => '+86'
        ),
        array(
            'name' => 'Christmas Island',
            'alphacode' => 'cx',
            'countryCode' => '+61'
        ),
        array(
            'name' => 'Cocos (Keeling) Islands',
            'alphacode' => 'cc',
            'countryCode' => '+61'
        ),
        array(
            'name' => 'Colombia',
            'alphacode' => 'co',
            'countryCode' => '+57'
        ),
        array(
            'name' => 'Comoros (‫جزر القمر‬‎)',
            'alphacode' => 'km',
            'countryCode' => '+269'
        ),
        array(
            'name' => 'Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo)',
            'alphacode' => 'cd',
            'countryCode' => '+243'
        ),
        array(
            'name' => 'Congo (Republic) (Congo-Brazzaville)',
            'alphacode' => 'cg',
            'countryCode' => '+242'
        ),
        array(
            'name' => 'Cook Islands',
            'alphacode' => 'ck',
            'countryCode' => '+682'
        ),
        array(
            'name' => 'Costa Rica',
            'alphacode' => 'cr',
            'countryCode' => '+506'
        ),
        array(
            'name' => 'Côte d\'Ivoire',
            'alphacode' => 'ci',
            'countryCode' => '+225'
        ),
        array(
            'name' => 'Croatia (Hrvatska)',
            'alphacode' => 'hr',
            'countryCode' => '+385'
        ),
        array(
            'name' => 'Cuba',
            'alphacode' => 'cu',
            'countryCode' => '+53'
        ),
        array(
            'name' => 'Curaçao',
            'alphacode' => 'cw',
            'countryCode' => '+599'
        ),
        array(
            'name' => 'Cyprus (Κύπρος)',
            'alphacode' => 'cy',
            'countryCode' => '+357'
        ),
        array(
            'name' => 'Czech Republic (Česká republika)',
            'alphacode' => 'cz',
            'countryCode' => '+420'
        ),
        array(
            'name' => 'Denmark (Danmark)',
            'alphacode' => 'dk',
            'countryCode' => '+45'
        ),
        array(
            'name' => 'Djibouti',
            'alphacode' => 'dj',
            'countryCode' => '+253'
        ),
        array(
            'name' => 'Dominica',
            'alphacode' => 'dm',
            'countryCode' => '+1767'
        ),
        array(
            'name' => 'Dominican Republic (República Dominicana)',
            'alphacode' => 'do',
            'countryCode' => '+1'
        ),
        array(
            'name' => 'Ecuador',
            'alphacode' => 'ec',
            'countryCode' => '+593'
        ),
        array(
            'name' => 'Egypt (‫مصر‬‎)',
            'alphacode' => 'eg',
            'countryCode' => '+20'
        ),
        array(
            'name' => 'El Salvador',
            'alphacode' => 'sv',
            'countryCode' => '+503'
        ),
        array(
            'name' => 'Equatorial Guinea (Guinea Ecuatorial)',
            'alphacode' => 'gq',
            'countryCode' => '+240'
        ),
        array(
            'name' => 'Eritrea',
            'alphacode' => 'er',
            'countryCode' => '+291'
        ),
        array(
            'name' => 'Estonia (Eesti)',
            'alphacode' => 'ee',
            'countryCode' => '+372'
        ),
        array(
            'name' => 'Ethiopia',
            'alphacode' => 'et',
            'countryCode' => '+251'
        ),
        array(
            'name' => 'Falkland Islands (Islas Malvinas)',
            'alphacode' => 'fk',
            'countryCode' => '+500'
        ),
        array(
            'name' => 'Faroe Islands (Føroyar)',
            'alphacode' => 'fo',
            'countryCode' => '+298'
        ),
        array(
            'name' => 'Fiji',
            'alphacode' => 'fj',
            'countryCode' => '+679'
        ),
        array(
            'name' => 'Finland (Suomi)',
            'alphacode' => 'fi',
            'countryCode' => '+358'
        ),
        array(
            'name' => 'France',
            'alphacode' => 'fr',
            'countryCode' => '+33'
        ),
        array(
            'name' => 'French Guiana (Guyane française)',
            'alphacode' => 'gf',
            'countryCode' => '+594'
        ),
        array(
            'name' => 'French Polynesia (Polynésie française)',
            'alphacode' => 'pf',
            'countryCode' => '+689'
        ),
        array(
            'name' => 'Gabon',
            'alphacode' => 'ga',
            'countryCode' => '+241'
        ),
        array(
            'name' => 'Gambia',
            'alphacode' => 'gm',
            'countryCode' => '+220'
        ),
        array(
            'name' => 'Georgia (საქართველო)',
            'alphacode' => 'ge',
            'countryCode' => '+995'
        ),
        array(
            'name' => 'Germany (Deutschland)',
            'alphacode' => 'de',
            'countryCode' => '+49'
        ),
        array(
            'name' => 'Ghana (Gaana)',
            'alphacode' => 'gh',
            'countryCode' => '+233'
        ),
        array(
            'name' => 'Gibraltar',
            'alphacode' => 'gi',
            'countryCode' => '+350'
        ),
        array(
            'name' => 'Greece (Ελλάδα)',
            'alphacode' => 'gr',
            'countryCode' => '+30'
        ),
        array(
            'name' => 'Greenland (Kalaallit Nunaat)',
            'alphacode' => 'gl',
            'countryCode' => '+299'
        ),
        array(
            'name' => 'Grenada',
            'alphacode' => 'gd',
            'countryCode' => '+1473'
        ),
        array(
            'name' => 'Guadeloupe',
            'alphacode' => 'gp',
            'countryCode' => '+590'
        ),
        array(
            'name' => 'Guam',
            'alphacode' => 'gu',
            'countryCode' => '+1671'
        ),
        array(
            'name' => 'Guatemala',
            'alphacode' => 'gt',
            'countryCode' => '+502'
        ),
        array(
            'name' => 'Guernsey',
            'alphacode' => 'gg',
            'countryCode' => '+44'
        ),
        array(
            'name' => 'Guinea (Guinée)',
            'alphacode' => 'gn',
            'countryCode' => '+224'
        ),
        array(
            'name' => 'Guinea-Bissau (Guiné Bissau)',
            'alphacode' => 'gw',
            'countryCode' => '+245'
        ),
        array(
            'name' => 'Guyana',
            'alphacode' => 'gy',
            'countryCode' => '+592'
        ),
        array(
            'name' => 'Haiti',
            'alphacode' => 'ht',
            'countryCode' => '+509'
        ),
        array(
            'name' => 'Honduras',
            'alphacode' => 'hn',
            'countryCode' => '+504'
        ),
        array(
            'name' => 'Hong Kong (香港)',
            'alphacode' => 'hk',
            'countryCode' => '+852'
        ),
        array(
            'name' => 'Hungary (Magyarország)',
            'alphacode' => 'hu',
            'countryCode' => '+36'
        ),
        array(
            'name' => 'Iceland (Ísland)',
            'alphacode' => 'is',
            'countryCode' => '+354'
        ),
        array(
            'name' => 'India (भारत)',
            'alphacode' => 'in',
            'countryCode' => '+91'
        ),
        array(
            'name' => 'Indonesia',
            'alphacode' => 'id',
            'countryCode' => '+62'
        ),
        array(
            'name' => 'Iran (‫ایران‬‎)',
            'alphacode' => 'ir',
            'countryCode' => '+98'
        ),
        array(
            'name' => 'Iraq (‫العراق‬‎)',
            'alphacode' => 'iq',
            'countryCode' => '+964'
        ),
        array(
            'name' => 'Ireland',
            'alphacode' => 'ie',
            'countryCode' => '+353'
        ),
        array(
            'name' => 'Isle of Man',
            'alphacode' => 'im',
            'countryCode' => '+44'
        ),
        array(
            'name' => 'Israel (‫ישראל‬‎)',
            'alphacode' => 'il',
            'countryCode' => '+972'
        ),
        array(
            'name' => 'Italy (Italia)',
            'alphacode' => 'it',
            'countryCode' => '+39'
        ),
        array(
            'name' => 'Jamaica',
            'alphacode' => 'jm',
            'countryCode' => '+1876'
        ),
        array(
            'name' => 'Japan (日本)',
            'alphacode' => 'jp',
            'countryCode' => '+81'
        ),
        array(
            'name' => 'Jersey',
            'alphacode' => 'je',
            'countryCode' => '+44'
        ),
        array(
            'name' => 'Jordan (‫الأردن‬‎)',
            'alphacode' => 'jo',
            'countryCode' => '+962'
        ),
        array(
            'name' => 'Kazakhstan (Казахстан)',
            'alphacode' => 'kz',
            'countryCode' => '+7'
        ),
        array(
            'name' => 'Kenya',
            'alphacode' => 'ke',
            'countryCode' => '+254'
        ),
        array(
            'name' => 'Kiribati',
            'alphacode' => 'ki',
            'countryCode' => '+686'
        ),
        array(
            'name' => 'Kosovo',
            'alphacode' => 'xk',
            'countryCode' => '+383'
        ),
        array(
            'name' => 'Kuwait (‫الكويت‬‎)',
            'alphacode' => 'kw',
            'countryCode' => '+965'
        ),
        array(
            'name' => 'Kyrgyzstan (Кыргызстан)',
            'alphacode' => 'kg',
            'countryCode' => '+996'
        ),
        array(
            'name' => 'Laos (ລາວ)',
            'alphacode' => 'la',
            'countryCode' => '+856'
        ),
        array(
            'name' => 'Latvia (Latvija)',
            'alphacode' => 'lv',
            'countryCode' => '+371'
        ),
        array(
            'name' => 'Lebanon (‫لبنان‬‎)',
            'alphacode' => 'lb',
            'countryCode' => '+961'
        ),
        array(
            'name' => 'Lesotho',
            'alphacode' => 'ls',
            'countryCode' => '+266'
        ),
        array(
            'name' => 'Liberia',
            'alphacode' => 'lr',
            'countryCode' => '+231'
        ),
        array(
            'name' => 'Libya (‫ليبيا‬‎)',
            'alphacode' => 'ly',
            'countryCode' => '+218'
        ),
        array(
            'name' => 'Liechtenstein',
            'alphacode' => 'li',
            'countryCode' => '+423'
        ),
        array(
            'name' => 'Lithuania (Lietuva)',
            'alphacode' => 'lt',
            'countryCode' => '+370'
        ),
        array(
            'name' => 'Luxembourg',
            'alphacode' => 'lu',
            'countryCode' => '+352'
        ),
        array(
            'name' => 'Macau (澳門)',
            'alphacode' => 'mo',
            'countryCode' => '+853'
        ),
        array(
            'name' => 'Macedonia (FYROM) (Македонија)',
            'alphacode' => 'mk',
            'countryCode' => '+389'
        ),
        array(
            'name' => 'Madagascar (Madagasikara)',
            'alphacode' => 'mg',
            'countryCode' => '+261'
        ),
        array(
            'name' => 'Malawi',
            'alphacode' => 'mw',
            'countryCode' => '+265'
        ),
        array(
            'name' => 'Malaysia',
            'alphacode' => 'my',
            'countryCode' => '+60'
        ),
        array(
            'name' => 'Maldives',
            'alphacode' => 'mv',
            'countryCode' => '+960'
        ),
        array(
            'name' => 'Mali',
            'alphacode' => 'ml',
            'countryCode' => '+223'
        ),
        array(
            'name' => 'Malta',
            'alphacode' => 'mt',
            'countryCode' => '+356'
        ),
        array(
            'name' => 'Marshall Islands',
            'alphacode' => 'mh',
            'countryCode' => '+692'
        ),
        array(
            'name' => 'Martinique',
            'alphacode' => 'mq',
            'countryCode' => '+596'
        ),
        array(
            'name' => 'Mauritania (‫موريتانيا‬‎)',
            'alphacode' => 'mr',
            'countryCode' => '+222'
        ),
        array(
            'name' => 'Mauritius (Moris)',
            'alphacode' => 'mu',
            'countryCode' => '+230'
        ),
        array(
            'name' => 'Mayotte',
            'alphacode' => 'yt',
            'countryCode' => '+262'
        ),
        array(
            'name' => 'Mexico (México)',
            'alphacode' => 'mx',
            'countryCode' => '+52'
        ),
        array(
            'name' => 'Micronesia',
            'alphacode' => 'fm',
            'countryCode' => '+691'
        ),
        array(
            'name' => 'Moldova (Republica Moldova)',
            'alphacode' => 'md',
            'countryCode' => '+373'
        ),
        array(
            'name' => 'Monaco',
            'alphacode' => 'mc',
            'countryCode' => '+377'
        ),
        array(
            'name' => 'Mongolia (Монгол)',
            'alphacode' => 'mn',
            'countryCode' => '+976'
        ),
        array(
            'name' => 'Montenegro (Crna Gora)',
            'alphacode' => 'me',
            'countryCode' => '+382'
        ),
        array(
            'name' => 'Montserrat',
            'alphacode' => 'ms',
            'countryCode' => '+1664'
        ),
        array(
            'name' => 'Morocco (‫المغرب‬‎)',
            'alphacode' => 'ma',
            'countryCode' => '+212'
        ),
        array(
            'name' => 'Mozambique (Moçambique)',
            'alphacode' => 'mz',
            'countryCode' => '+258'
        ),
        array(
            'name' => 'Myanmar (Burma) (မြန်မာ)',
            'alphacode' => 'mm',
            'countryCode' => '+95'
        ),
        array(
            'name' => 'Namibia (Namibië)',
            'alphacode' => 'na',
            'countryCode' => '+264'
        ),
        array(
            'name' => 'Nauru',
            'alphacode' => 'nr',
            'countryCode' => '+674'
        ),
        array(
            'name' => 'Nepal (नेपाल)',
            'alphacode' => 'np',
            'countryCode' => '+977'
        ),
        array(
            'name' => 'Netherlands (Nederland)',
            'alphacode' => 'nl',
            'countryCode' => '+31'
        ),
        array(
            'name' => 'New Caledonia (Nouvelle-Calédonie)',
            'alphacode' => 'nc',
            'countryCode' => '+687'
        ),
        array(
            'name' => 'New Zealand',
            'alphacode' => 'nz',
            'countryCode' => '+64'
        ),
        array(
            'name' => 'Nicaragua',
            'alphacode' => 'ni',
            'countryCode' => '+505'
        ),
        array(
            'name' => 'Niger (Nijar)',
            'alphacode' => 'ne',
            'countryCode' => '+227'
        ),
        array(
            'name' => 'Nigeria',
            'alphacode' => 'ng',
            'countryCode' => '+234'
        ),
        array(
            'name' => 'Niue',
            'alphacode' => 'nu',
            'countryCode' => '+683'
        ),
        array(
            'name' => 'Norfolk Island',
            'alphacode' => 'nf',
            'countryCode' => '+672'
        ),
        array(
            'name' => 'North Korea (조선 민주주의 인민 공화국)',
            'alphacode' => 'kp',
            'countryCode' => '+850'
        ),
        array(
            'name' => 'Northern Mariana Islands',
            'alphacode' => 'mp',
            'countryCode' => '+1670'
        ),
        array(
            'name' => 'Norway (Norge)',
            'alphacode' => 'no',
            'countryCode' => '+47'
        ),
        array(
            'name' => 'Oman (‫عُمان‬‎)',
            'alphacode' => 'om',
            'countryCode' => '+968'
        ),
        array(
            'name' => 'Pakistan (‫پاکستان‬‎)',
            'alphacode' => 'pk',
            'countryCode' => '+92'
        ),
        array(
            'name' => 'Palau',
            'alphacode' => 'pw',
            'countryCode' => '+680'
        ),
        array(
            'name' => 'Palestine (‫فلسطين‬‎)',
            'alphacode' => 'ps',
            'countryCode' => '+970'
        ),
        array(
            'name' => 'Panama (Panamá)',
            'alphacode' => 'pa',
            'countryCode' => '+507'
        ),
        array(
            'name' => 'Papua New Guinea',
            'alphacode' => 'pg',
            'countryCode' => '+675'
        ),
        array(
            'name' => 'Paraguay',
            'alphacode' => 'py',
            'countryCode' => '+595'
        ),
        array(
            'name' => 'Peru (Perú)',
            'alphacode' => 'pe',
            'countryCode' => '+51'
        ),
        array(
            'name' => 'Philippines',
            'alphacode' => 'ph',
            'countryCode' => '+63'
        ),
        array(
            'name' => 'Poland (Polska)',
            'alphacode' => 'pl',
            'countryCode' => '+48'
        ),
        array(
            'name' => 'Portugal',
            'alphacode' => 'pt',
            'countryCode' => '+351'
        ),
        array(
            'name' => 'Puerto Rico',
            'alphacode' => 'pr',
            'countryCode' => '+1'
        ),
        array(
            'name' => 'Qatar (‫قطر‬‎)',
            'alphacode' => 'qa',
            'countryCode' => '+974'
        ),
        array(
            'name' => 'Réunion (La Réunion)',
            'alphacode' => 're',
            'countryCode' => '+262'
        ),
        array(
            'name' => 'Romania (România)',
            'alphacode' => 'ro',
            'countryCode' => '+40'
        ),
        array(
            'name' => 'Russia (Россия)',
            'alphacode' => 'ru',
            'countryCode' => '+7'
        ),
        array(
            'name' => 'Rwanda',
            'alphacode' => 'rw',
            'countryCode' => '+250'
        ),
        array(
            'name' => 'Saint Barthélemy',
            'alphacode' => 'bl',
            'countryCode' => '+590'
        ),
        array(
            'name' => 'Saint Helena',
            'alphacode' => 'sh',
            'countryCode' => '+290'
        ),
        array(
            'name' => 'Saint Kitts and Nevis',
            'alphacode' => 'kn',
            'countryCode' => '+1869'
        ),
        array(
            'name' => 'Saint Lucia',
            'alphacode' => 'lc',
            'countryCode' => '+1758'
        ),
        array(
            'name' => 'Saint Martin (Saint-Martin (partie française))',
            'alphacode' => 'mf',
            'countryCode' => '+590'
        ),
        array(
            'name' => 'Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon)',
            'alphacode' => 'pm',
            'countryCode' => '+508'
        ),
        array(
            'name' => 'Saint Vincent and the Grenadines',
            'alphacode' => 'vc',
            'countryCode' => '+1784'
        ),
        array(
            'name' => 'Samoa',
            'alphacode' => 'ws',
            'countryCode' => '+685'
        ),
        array(
            'name' => 'San Marino',
            'alphacode' => 'sm',
            'countryCode' => '+378'
        ),
        array(
            'name' => 'São Tomé and Príncipe (São Tomé e Príncipe)',
            'alphacode' => 'st',
            'countryCode' => '+239'
        ),
        array(
            'name' => 'Saudi Arabia (‫المملكة العربية السعودية‬‎)',
            'alphacode' => 'sa',
            'countryCode' => '+966'
        ),
        array(
            'name' => 'Senegal (Sénégal)',
            'alphacode' => 'sn',
            'countryCode' => '+221'
        ),
        array(
            'name' => 'Serbia (Србија)',
            'alphacode' => 'rs',
            'countryCode' => '+381'
        ),
        array(
            'name' => 'Seychelles',
            'alphacode' => 'sc',
            'countryCode' => '+248'
        ),
        array(
            'name' => 'Sierra Leone',
            'alphacode' => 'sl',
            'countryCode' => '+232'
        ),
        array(
            'name' => 'Singapore',
            'alphacode' => 'sg',
            'countryCode' => '+65'
        ),
        array(
            'name' => 'Sint Maarten',
            'alphacode' => 'sx',
            'countryCode' => '+1721'
        ),
        array(
            'name' => 'Slovakia (Slovensko)',
            'alphacode' => 'sk',
            'countryCode' => '+421'
        ),
        array(
            'name' => 'Slovenia (Slovenija)',
            'alphacode' => 'si',
            'countryCode' => '+386'
        ),
        array(
            'name' => 'Solomon Islands',
            'alphacode' => 'sb',
            'countryCode' => '+677'
        ),
        array(
            'name' => 'Somalia (Soomaaliya)',
            'alphacode' => 'so',
            'countryCode' => '+252'
        ),
        array(
            'name' => 'South Africa',
            'alphacode' => 'za',
            'countryCode' => '+27'
        ),
        array(
            'name' => 'South Korea (대한민국)',
            'alphacode' => 'kr',
            'countryCode' => '+82'
        ),
        array(
            'name' => 'South Sudan (‫جنوب السودان‬‎)',
            'alphacode' => 'ss',
            'countryCode' => '+211'
        ),
        array(
            'name' => 'Spain (España)',
            'alphacode' => 'es',
            'countryCode' => '+34'
        ),
        array(
            'name' => 'Sri Lanka (ශ්‍රී ලංකාව)',
            'alphacode' => 'lk',
            'countryCode' => '+94'
        ),
        array(
            'name' => 'Sudan (‫السودان‬‎)',
            'alphacode' => 'sd',
            'countryCode' => '+249'
        ),
        array(
            'name' => 'Suriname',
            'alphacode' => 'sr',
            'countryCode' => '+597'
        ),
        array(
            'name' => 'Svalbard and Jan Mayen',
            'alphacode' => 'sj',
            'countryCode' => '+47'
        ),
        array(
            'name' => 'Swaziland',
            'alphacode' => 'sz',
            'countryCode' => '+268'
        ),
        array(
            'name' => 'Sweden (Sverige)',
            'alphacode' => 'se',
            'countryCode' => '+46'
        ),
        array(
            'name' => 'Switzerland (Schweiz)',
            'alphacode' => 'ch',
            'countryCode' => '+41'
        ),
        array(
            'name' => 'Syria (‫سوريا‬‎)',
            'alphacode' => 'sy',
            'countryCode' => '+963'
        ),
        array(
            'name' => 'Taiwan (台灣)',
            'alphacode' => 'tw',
            'countryCode' => '+886'
        ),
        array(
            'name' => 'Tajikistan',
            'alphacode' => 'tj',
            'countryCode' => '+992'
        ),
        array(
            'name' => 'Tanzania',
            'alphacode' => 'tz',
            'countryCode' => '+255'
        ),
        array(
            'name' => 'Thailand (ไทย)',
            'alphacode' => 'th',
            'countryCode' => '+66'
        ),
        array(
            'name' => 'Timor-Leste',
            'alphacode' => 'tl',
            'countryCode' => '+670'
        ),
        array(
            'name' => 'Togo',
            'alphacode' => 'tg',
            'countryCode' => '+228'
        ),
        array(
            'name' => 'Tokelau',
            'alphacode' => 'tk',
            'countryCode' => '+690'
        ),
        array(
            'name' => 'Tonga',
            'alphacode' => 'to',
            'countryCode' => '+676'
        ),
        array(
            'name' => 'Trinidad and Tobago',
            'alphacode' => 'tt',
            'countryCode' => '+1868'
        ),
        array(
            'name' => 'Tunisia (‫تونس‬‎)',
            'alphacode' => 'tn',
            'countryCode' => '+216'
        ),
        array(
            'name' => 'Turkey (Türkiye)',
            'alphacode' => 'tr',
            'countryCode' => '+90'
        ),
        array(
            'name' => 'Turkmenistan',
            'alphacode' => 'tm',
            'countryCode' => '+993'
        ),
        array(
            'name' => 'Turks and Caicos Islands',
            'alphacode' => 'tc',
            'countryCode' => '+1649'
        ),
        array(
            'name' => 'Tuvalu',
            'alphacode' => 'tv',
            'countryCode' => '+688'
        ),
        array(
            'name' => 'U.S. Virgin Islands',
            'alphacode' => 'vi',
            'countryCode' => '+1340'
        ),
        array(
            'name' => 'Uganda',
            'alphacode' => 'ug',
            'countryCode' => '+256'
        ),
        array(
            'name' => 'Ukraine (Україна)',
            'alphacode' => 'ua',
            'countryCode' => '+380'
        ),
        array(
            'name' => 'United Arab Emirates (‫الإمارات العربية المتحدة‬‎)',
            'alphacode' => 'ae',
            'countryCode' => '+971'
        ),
        array(
            'name' => 'United Kingdom',
            'alphacode' => 'gb',
            'countryCode' => '+44'
        ),
        array(
            'name' => 'United States',
            'alphacode' => 'us',
            'countryCode' => '+1'
        ),
        array(
            'name' => 'Uruguay',
            'alphacode' => 'uy',
            'countryCode' => '+598'
        ),
        array(
            'name' => 'Uzbekistan (Oʻzbekiston)',
            'alphacode' => 'uz',
            'countryCode' => '+998'
        ),
        array(
            'name' => 'Vanuatu',
            'alphacode' => 'vu',
            'countryCode' => '+678'
        ),
        array(
            'name' => 'Vatican City (Città del Vaticano)',
            'alphacode' => 'va',
            'countryCode' => '+39'
        ),
        array(
            'name' => 'Venezuela',
            'alphacode' => 've',
            'countryCode' => '+58'
        ),
        array(
            'name' => 'Vietnam (Việt Nam)',
            'alphacode' => 'vn',
            'countryCode' => '+84'
        ),
        array(
            'name' => 'Wallis and Futuna (Wallis-et-Futuna)',
            'alphacode' => 'wf',
            'countryCode' => '+681'
        ),
        array(
            'name' => 'Western Sahara (‫الصحراء الغربية‬‎)',
            'alphacode' => 'eh',
            'countryCode' => '+212'
        ),
        array(
            'name' => 'Yemen (‫اليمن‬‎)',
            'alphacode' => 'ye',
            'countryCode' => '+967'
        ),
        array(
            'name' => 'Zambia',
            'alphacode' => 'zm',
            'countryCode' => '+260'
        ),
        array(
            'name' => 'Zimbabwe',
            'alphacode' => 'zw',
            'countryCode' => '+263'
        ),
        array(
            'name' => 'Åland Islands',
            'alphacode' => 'ax',
            'countryCode' => '+358'
        ),
    );
    return $countries;
}

function advanceSettingsTab()
{
    $settings   = commonUtilitiesTfa::getTfaSettings();
    $kbaSet1 = $settings['tfa_kba_set1']
        ? $settings['tfa_kba_set1']
        : '';
    $kbaSet2 = $settings['tfa_kba_set2']
        ? $settings['tfa_kba_set2']
        : '';
    $tfa_enabled = isset($settings['tfa_enabled']) && $settings['tfa_enabled'] == 1
        ? 'checked'
        : '';
    $inline  = isset($settings['inline_enabled']) && $settings['inline_enabled'] == 1
        ? 'checked'
        : '';
    $enableIpWhiteList = isset($settings['enableIpWhiteList']) && $settings['enableIpWhiteList'] == 1
        ? 'checked'
        : '';
    $enableIpBlackList = isset($settings['enableIpBlackList']) && $settings['enableIpBlackList'] == 1
        ? 'checked'
        : '';
    $whiteListedIps = isset($settings['whiteListedIps'])
        ? implode(";", json_decode($settings['whiteListedIps']))
        : '';
    $blackListedIps = isset($settings['blackListedIPs'])
        ? implode(";", json_decode($settings['blackListedIPs']))
        : '';
    $redirectUrl = empty($settings['afterLoginRedirectUrl'])
        ? Uri::root()
        : $settings['afterLoginRedirectUrl'];
    $brandingName   = isset($settings['branding_name']) && !empty($settings['branding_name'])
        ? ($settings['branding_name'])
        : 'login';
    $tfa_bypass_for_admin = isset($settings['bypass_tfa_for_admin']) && $settings['bypass_tfa_for_admin'] == 1
        ? 'checked'
        : '';
    $tfa_bypass_for_users = isset($settings['skip_tfa_for_users']) && $settings['skip_tfa_for_users'] == 1
        ? 'checked'
        : '';
    $tfa_for_roles = isset($settings['mo_tfa_for_roles']) && !empty($settings['mo_tfa_for_roles'])
        ? json_decode($settings['mo_tfa_for_roles'])
        : array();
    $login_with_second_factor_only = isset($settings['login_with_second_factor_only']) && $settings['login_with_second_factor_only'] == 1
        ? 'checked'
        : '';
    $resend_otp_control = isset($settings['resend_otp_control']) && $settings['resend_otp_control'] == 1
        ? 'checked'
        : '';
    $resend_otp_count = isset($settings['resend_otp_count'])
        ? $settings['resend_otp_count']
        : 3;
    $blocking_resend_otp_type = isset($settings['blocking_resend_otp_type'])
        ? $settings['blocking_resend_otp_type']
        : "days";
    $enable_backup_codes = isset($settings['enable_backup_codes']) && $settings['enable_backup_codes'] == 1
        ? 'checked'
        : '';


    $googleAuthAppName = urldecode($settings['googleAuthAppName']);

    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $details = commonUtilitiesTfa::getCustomerDetails();
    $licenseExpired = false;
    // Check if license has expired based on 'supportExpiry'
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
    $customerEmail = $details['email'];
    $inlineDisabled = '';

    $featureDisable = '';
    if (!$isCustomerRegistered) {
        $featureDisable = 'disabled';
    }
    $groups = commonUtilitiesTfa::loadGroups();
    $active2FA = commonUtilitiesTfa::getActive2FAMethods();
    $hostName = commonUtilitiesTfa::getHostName();

    // Read backup codes from file
    $random_string = trim(commonUtilitiesTfa::readBackupCodesFromFile());
    $backup_code_array = explode(',', $random_string);

    $current_user     = Factory::getUser();
    $isCustomerRegistered = commonUtilitiesTfa::isCustomerRegistered();
    $isFirstUser          = commonUtilitiesTfa::isFirstUser($current_user->id);

    $enableChange2FA = isset($settings['enable_change_2fa_method']) && $settings['enable_change_2fa_method'] == 1
        ? 'checked'
        : '';

    $enableTfaRegistration = isset($settings['enableTfaRegistration']) && $settings['enableTfaRegistration'] == 1
        ? 'checked'
        : '';

    $enableTfaDomain = isset($settings['enableTfaDomain']) && $settings['enableTfaDomain'] == 1
        ? 'checked'
        : '';

    $enableEmailFunctionality = isset($settings['enableEmailFunctionality']) && $settings['enableEmailFunctionality'] == 1
        ? 'checked'
        : '';

        $tfaDomainList = isset($settings['tfaDomainList']) ? json_decode($settings['tfaDomainList'], true) : [];
        $domainListStr = is_array($tfaDomainList) ? implode(';', array_map('trim', $tfaDomainList)) : '';
        $app = Factory::getApplication();
        

    $db = Factory::getDbo();
    $query = $db->getQuery(true);


    $query->select('*')
        ->from($db->quoteName('#__miniorange_email_settings'))
        ->setLimit(1);
    $db->setQuery($query);
    $emailSettings = $db->loadObject();
    $email_method = $emailSettings->email_method ?? 'smtp';
    $smtp_host = $emailSettings->smtp_host ?? '';
    $smtp_port = $emailSettings->smtp_port ?? '587';
    $smtp_username = $emailSettings->smtp_username ?? '';
    $smtp_password = $emailSettings->smtp_password ?? '';
    $email_recipients = $emailSettings->recipients ?? 'both';

    Log::add('Email method retrieved: ' . $email_method, Log::INFO, 'tfa');

?>
    <div class="mo_boot_row mo_boot_m-1">
        <div class="mo_boot_col-sm-12"><?php
            if (!commonUtilitiesTfa::isCustomerRegistered()) {
                echo  '<div class="mo_register_message">' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG") . ' <a href="' . Route::_('index.php?option=com_miniorange_twofa&tab-panel=account_setup') . '" >' . Text::_("COM_MINIORANGE_REGISTER_MSG") . '</a> ' . Text::_("COM_MINIORANGE_SETUP_TFA_MSG1") . '</div>';
            } ?>
        </div>
        <div class="mo_boot_col-sm-12">
            <div class="mo_boot_row  mo_boot_text-center">
                <div class="mo_boot_col-sm-12"><br>
                    <h3><?php echo Text::_('COM_MINIORANGE_ADVANCE_SETTINGS'); ?></h3>
                    <hr>
                </div>
            </div>
            <div class="mo_boot_col-sm-12 mo_boot_mt-2">
                <form name="f" class="miniorange_tfa_settings_form" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.saveTfaAdvanceSettings'); ?>">
                     <fieldset <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                    <details class="mo_details">
                            <summary class="mo_tfa_summary"><span class="mo_boot_mr-2">&#9662;</span><?php echo Text::_('COM_MINIORANGE_ROLE2FA'); ?></summary><div class="mo_boot_row mo_boot_mt-2">
                            <div class="mo_boot_col-sm-12 mo_boot_m-3">
                                <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <i><?php echo Text::_('COM_MINIORANGE_ROLE_DESC'); ?></i></br><?php
                                    foreach ($groups as $key => $value) {
                                        if ($value['title'] != 'Public' && $value['title'] != 'Guest') {
                                            if (in_array('ALL', $tfa_for_roles) || in_array($value['title'], $tfa_for_roles))
                                                echo '<br><input type="checkbox" name="role_based_tfa_' . str_replace(' ', '_', $value['title']) . '" checked="true" ' . $inlineDisabled . ' /> &emsp;' . $value['title'];
                                            else
                                                echo '<br><input type="checkbox" name="role_based_tfa_' . str_replace(' ', '_', $value['title']) . '"' . $inlineDisabled . '  />&emsp;' . $value['title'];
                                        }
                                    } ?>
                            </div>
                        </div>

                    </details>

                    <details class="mo_details">
                        <summary class="mo_tfa_summary"><span class="mo_boot_mr-2">&#9662;</span><?php echo Text::_('COM_MINIORANGE_IP_BASED2FA'); ?></summary>
                        <div class="mo_boot_row mo_boot_m-2">
                            <div class="mo_boot_col-sm-12 ">
                                <div class="mo_tfa_details_content mo_dark_bg">
                                    <input type="checkbox" id="enable_ip_whitelist" onclick="enable_ip_whitelist_field();" name="enableIpWhiteListing" <?php echo $inlineDisabled; ?> <?php echo $enableIpWhiteList; ?> <?php echo " " . $featureDisable ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_WHITELIST'); ?></strong><br>
                                    &emsp;&emsp;<strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?> </strong><?php echo Text::_('COM_MINIORANGE_WHITELIST_DESC'); ?><br>
                                    <textarea class="ip_whitelist_field mo_tfa_ip_domain" name="mo_tfa_whitelist_ips" rows="5" placeholder="<?php echo Text::_('COM_MINIORANGE_WHITELIST_IP'); ?>" <?php echo $inlineDisabled; ?> <?php echo $featureDisable ?>><?php echo $whiteListedIps; ?></textarea>
                                </div>
                            </div>
                        </div>

                    </details>


                    <details class="mo_details">
                        <summary class="mo_tfa_summary"><span class="mo_boot_mr-2">&#9662;</span><?php echo Text::_('COM_MINIORANGE_EXTRA_MODIFICATIONS'); ?></summary>
                        <div class="mo_boot_row mo_boot_m-2">
                            <div class="mo_boot_col-sm-12">
                                <div class="mo_tfa_details_content mo_dark_bg">
                                    <label>
                                        <strong><?php echo Text::_('COM_MINIORANGE_REDIRECT'); ?></strong>
                                    </label>
                                    <input type="url" name="mo_tfa_user_after_login" <?php echo $featureDisable ?> class=" mo_boot_form-control" value="<?php echo $redirectUrl; ?>" placeholder="Enter the redirect URL" />
                                    <label><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_REDIRECT_URL'); ?> <i><?php echo Text::_('COM_MINIORANGE_URL'); ?></i> <?php echo Text::_('COM_MINIORANGE_REDIRECT_AFTER_AUTH'); ?></label>
                                    <hr>
                                    <label>
                                        <strong><?php echo Text::_('COM_MINIORANGE_DOMAIN'); ?></strong>
                                    </label>
                                    <input type="text" name="branding_name" <?php echo $featureDisable ?> class=" mo_boot_form-control" value="<?php echo $brandingName; ?>" />
                                    <label><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong><?php echo Text::_('COM_MINIORANGE_BRANDING'); ?> <strong><?php echo Text::_('COM_MINIORANGE_LOGIN'); ?></strong></label>
                                    <hr>
                                    <label>
                                        <strong><?php echo Text::_('COM_MINIORANGE_CHANGE_ACCOUNT'); ?></strong>
                                    </label>
                                    <input type="text" name="mo_tfa_google_app_name" <?php echo $featureDisable ?> class=" mo_boot_form-control" value="<?php echo $googleAuthAppName; ?>" />
                                    <label><strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong><?php echo Text::_('COM_MINIORANGE_CHANGE_DESC'); ?></label>

                                </div>
                            </div>
                        </div>
                    </details>
                    <details class="mo_details">
                        <summary class="mo_tfa_summary"><span class="mo_boot_mr-2">&#9662;</span><?php echo Text::_('COM_MINIORANGE_DOMAIN_BASED2FA'); ?></summary>
                        <hr>
                        <div class="mo_boot_row mo_boot_m-2">
                            <div class="boot_mo_boot_col-sm-12">
                                <div class="mo_tfa_details_content mo_dark_bg">
                                    <input type="checkbox" id="enable_tfa_domain" onclick="toggleTfaDomainField();" name="enableTfaDomain" <?php echo $inlineDisabled; ?> <?php echo $enableTfaDomain ? 'checked' : ''; ?> <?php echo " " . $featureDisable; ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_DOMAIN_ENABLE'); ?></strong><br>
                                    &ensp;&ensp;&emsp;<strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_DOMAIN_DESC'); ?><br>
                                    <textarea class="domain_field mo_tfa_ip_domain" name="mo_tfa_domain" rows="5" placeholder="<?php echo Text::_('COM_MINIORANGE_VAL_DOMAIN'); ?>" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?>><?php echo $domainListStr; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </details>
                    <details class="mo_details">
                        <summary class="mo_tfa_summary"><span class="mo_boot_mr-2">&#9662;</span><?php echo Text::_('COM_MINIORANGE_EMAIL_NOTIFY'); ?></summary>
                        <div class="mo_boot_row mo_boot_m-2">
                            <div class="mo_boot_col-sm-12">
                                <div class="mo_tfa_details_content mo_dark_bg">
                                    <hr>
                                    <div><input type="checkbox" id="enable_email_functionality" onclick="toggleEmailFields();" name="enableEmailFunctionality" <?php echo $inlineDisabled; ?> <?php echo $enableEmailFunctionality ? 'checked' : ''; ?> <?php echo " " . $featureDisable; ?> />&emsp;<strong><?php echo Text::_('COM_MINIORANGE_ENFORCE_EMAIL'); ?></strong><br></div>
                                    <div class="email-functionality-section mo_dark_bg">
                                        <div class="mo_boot_row">
                                            <div class=" mo_boot_col-sm-3  mo_boot_col-lg-3 mo_boot_mt-3">
                                                <strong><?php echo Text::_('COM_MINIORANGE_SEND_MAIL'); ?> </strong>
                                            </div>
                                            <div class="mo_boot_col-sm-8  mo_boot_col-lg-8 mo_boot_mt-2">
                                                <select class=" mo_boot_form-control mo_tfa_select_user mo_email_method_select mo_dark_bg" name="email_recipients" id="email_recipients" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?>>
                                                    <option value="both" <?php echo $email_recipients == 'both' ? "selected" : ""; ?>><?php echo Text::_('COM_MINIORANGE_BOTH'); ?></option>
                                                    <option value="user" <?php echo $email_recipients == 'user' ? "selected" : ""; ?>><?php echo Text::_('COM_MINIORANGE_USER_ONLY'); ?></option>
                                                    <option value="admin" <?php echo $email_recipients == 'admin' ? "selected" : ""; ?>><?php echo Text::_('COM_MINIORANGE_ADMIN_ONLY'); ?></option>
                                                </select>

                                            </div>
                                            <div class=" mo_boot_col-sm-3  mo_boot_col-lg-3 mo_boot_mt-3">
                                                <strong><?php echo Text::_('COM_MINIORANGE_MAIL_METHOD'); ?> </strong>
                                            </div>
                                            <div class="mo_boot_col-sm-8  mo_boot_col-lg-8 mo_boot_mt-2">
                                                <select name="email_method" class=" mo_boot_form-control mo_email_method_select mo_dark_bg" id="email_method" required onchange="toggleSmtpFields(this.value)" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?>>
                                                    <option value="smtp"><?php echo Text::_('SMTP'); ?></option>
                                                </select>
                                                <div class="mo_dark_bg mo_smtp_fields" id="smtp_fields">
                                                    <div>
                                                        <label for="smtp_host"><?php echo Text::_('HOST'); ?>:</label>
                                                        <input type="text" name="smtp_host" class="mo_dark_bg mo_input_field" value="<?php echo htmlspecialchars($smtp_host); ?>" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?> />
                                                    </div>
                                                    <div>
                                                        <label for="smtp_port"><?php echo Text::_('PORT'); ?>:</label>
                                                        <input type="text" name="smtp_port" class="mo_dark_bg mo_input_field" value="<?php echo htmlspecialchars($smtp_port); ?>" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?> />
                                                    </div>
                                                    <div>
                                                        <label for="smtp_username"><?php echo Text::_('COM_MINIORANGE_USERNAME'); ?>:</label>
                                                        <input type="text" name="smtp_username" class="mo_dark_bg mo_input_field" value="<?php echo htmlspecialchars($smtp_username); ?>" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?> />
                                                    </div>
                                                    <div>
                                                        <label for="smtp_password"><?php echo Text::_('COM_MINIORANGE_PASS'); ?></label>
                                                        <input type="password" name="smtp_password" class="mo_dark_bg mo_input_field" value="<?php echo htmlspecialchars($smtp_password); ?>" <?php echo $featureDisable ?> <?php echo $inlineDisabled; ?> />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div class="mo_boot_col-sm-12 mo_boot_my-3 ;">
                        <?php
                        if ($isCustomerRegistered && $isFirstUser) { ?>
                            <input type="submit" name="submit_login_settings" value="<?php echo Text::_('COM_MINIORANGE_VAL_SAVE'); ?>" class="mo_tfa_input_submit mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark mo_tfa_btn_bg mo_otp_btns" <?php echo $featureDisable ?>>
                        <?php }
                        ?>
                    </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
<?php
}

function Customisation() {}

function CustomLoginForms()
{
?>
    <div class="mo_boot_row mo_boot_my-3">
        <div class="mo_boot_col-sm-12  mo_boot_text-center mo_boot_mt-3">
            <h3><?php echo Text::_('COM_MINIORANGE_LOGINFORMS'); ?><sup><span class="mo_tfa_red"><?php echo Text::_('COM_MINIORANGE_COMINGSOON'); ?></span></sup></h3>
            <hr>
            <p>
                <strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?></strong> <?php echo Text::_('COM_MINIORANGE_FORMS_DESC1'); ?> <?php echo Text::_('COM_MINIORANGE_FORMS_DESC2'); ?> <a href="mailto:joomlasupport@xecurify.com">joomlasupport@xecurify.com</a>
            </p>
        </div>
        <div class="mo_boot_col-sm-12 mo_boot_my-3">
            <div class="mo_boot_row mo_boot_m-2">
                <div class="mo_boot_col-sm-12">
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_CONVERTFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" class=" mo_boot_form-control" disabled name="convert_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" class=" mo_boot_form-control" disabled name="convert_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" disabled value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_BREZZINGFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="brazzing_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="brazzing_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_VIRTUEFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="virtuemart_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="virtue_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_AJAX'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="ajax_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="ajax_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_PROFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="pro_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="pro_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_RSFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="rs_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="rs_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_CHRONOFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="chrono_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="chrono_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </details>
                    <details class="mo_details">
                        <summary><?php echo Text::_('COM_MINIORANGE_SHACKFORMS'); ?></summary>
                        <form action="#">
                            <div class="mo_boot_row mo_boot_p-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_FORMID'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="shack_form_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-2">
                                        <div class="mo_boot_col-sm-4">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL_ATTRIBUTE_A'); ?>
                                        </div>
                                        <div class="mo_boot_col-sm-6">
                                            <input type="text" disabled class=" mo_boot_form-control" name="shack_email_id">
                                        </div>
                                    </div>
                                    <div class="mo_boot_row  mo_boot_mt-4  mo_boot_text-center">
                                        <div class="mo_boot_col-sm-12">
                                            <input type="submit" disabled class="mo_boot_btn mo_boot_btn-primary mo_tfa_btns_dark" value="<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </details>
                </div>
            </div>
        </div>
    </div>
<?php
}
function exportConfiguration()
{
      $details = commonUtilitiesTfa::getCustomerDetails();
      $licenseExpired = false;
    // Check if license has expired based on 'supportExpiry'
    if (!empty($details['license_type']) && !empty($details['supportExpiry']) && strtotime($details['supportExpiry']) < time()) {
        $licenseExpired = true;
    }
?>
    <div class="container-fluid  mo_boot_m-0  mo_boot_p-0">
        <div class="mo_boot_row mo_ldap_tab_theme  mo_boot_p-0">
            <div class="export-configuration">
                <h3 class="mo_export_heading  mo_boot_pt-4"><?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION'); ?></h3>
                <p>
                    <?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION_TEXT'); ?>
                </p>
                <form action="<?php echo Route::_('index.php?option=com_miniorange_twofa&task=setup_two_factor.exportConfiguration'); ?>" method="post">
                    <button type="submit" <?php echo $licenseExpired ? 'disabled' : ''; ?> class="mo_boot_btn mo_export_blue_buttons mo_export_white_color <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>"><?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIG'); ?></button>
                </form>
                <?php echo HTMLHelper::_('form.token'); ?>
                <hr>
                <h3 class="mo_export_heading  mo_boot_pt-4"><?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIGURATION'); ?></h3>
                <p>
                    <?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIGURATION_TEXT'); ?>
                </p>
                <form action="index.php?option=com_miniorange_twofa&task=setup_two_factor.importConfiguration" method="post" enctype="multipart/form-data">
                <input type="file" id="fileInput" name="file" accept=".json" style="display: none;" onchange="displayFileName()" <?php echo $licenseExpired ? 'disabled' : ''; ?>>
                
                <button type="button" class="mo_boot_btn mo_export_blue_buttons mo_export_white_color mo_boot_mr-3 mo_boot_mb-4 <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" onclick="<?php echo $licenseExpired ? 'return false;' : 'document.getElementById(\'fileInput\').click();'; ?>" <?php echo $licenseExpired ? 'disabled' : ''; ?> >
                    <i class="fas fa-upload mo_boot_p-1"></i>
                </button>
                <span class="file-name" id="fileName"></span>
                <button type="submit" class="mo_boot_btn mo_export_blue_buttons mo_export_white_color  mo_boot_mb-4 <?php echo $licenseExpired ? 'mo_boot_disabled' : ''; ?>" <?php echo $licenseExpired ? 'disabled' : ''; ?>><?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIG'); ?></button>
                </form>
            </div>
        </div>
    </div>
<?php
}
?>