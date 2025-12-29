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
jimport('miniorangetfa.utility.commonUtilitiesTfa');
jimport('miniorangetfa.utility.miniOrangeUser');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
Use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Input\Input;

HTMLHelper::_('jquery.framework');
Log::addLogger(
	array(
		 'text_file' => 'tfa_site_logs.php',
		 'text_entry_format' => '{DATETIME}   {PRIORITY}   {CATEGORY}   {MESSAGE}'
	),
	Log::ALL
);

// Function to handle passwordless login validation
function handlePasswordlessLoginValidation() {
    $app = Factory::getApplication();
    $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
    $session = Factory::getSession();
    $db = Factory::getDbo();
    $username = $session->get('tfa_username');
    
    // Clear session data if starting a new login attempt
    if ($input->get('new_login', 0)) {
        $session->clear('steps');
        $session->clear('motfa');
        $session->clear('passwordless_validated');
        $session->clear('tfa_tx_id');
        Log::add('Cleared session data for new login attempt', Log::INFO, 'TFA');
        return false;
    }
    
    // Check if already validated
    if ($session->get('passwordless_validated', false)) {
        Log::add('Passwordless validation already completed for user: ' . $username, Log::INFO, 'TFA');
        return false;
    }
    
    if (empty($username)) {
        return false; // No username in session, skip validation
    }
    
    Log::add('Checking passwordless login validation for user: ' . $username, Log::INFO, 'TFA');
    
    // Check if passwordless login is enabled
    $query = $db->getQuery(true)
        ->select($db->quoteName('enable_tfa_passwordless_login'))
        ->from($db->quoteName('#__miniorange_tfa_settings'));
    $db->setQuery($query);
    $passwordlessEnabled = (int) $db->loadResult();
    
    if ($passwordlessEnabled !== 1) {
        Log::add('Passwordless login not enabled, skipping validation for user: ' . $username, Log::INFO, 'TFA');
        return false;
    }
    
    // Check if TFA is already active
    $query = $db->getQuery(true)
        ->select($db->quoteName('status_of_motfa'))
        ->from($db->quoteName('#__miniorange_tfa_users'))
        ->where($db->quoteName('username') . ' = ' . $db->quote($username));
    $db->setQuery($query);
    $status = $db->loadResult();
    
    if ($status === 'active') {
        Log::add('TFA is already active for user: ' . $username . ', skipping validation.', Log::INFO, 'TFA');
        return false;
    }
    
    // Get user email
    $userDetails = commonUtilitiesTfa::get_user_details_by_username($username);
    $email = isset($userDetails['email']) ? $userDetails['email'] : '';
    
    if (empty($email)) {
        Log::add('No email address found for user: ' . $username, Log::WARNING, 'TFA');
        return false;
    }
    
    $otpMessage = '';
    
    // Handle OTP verification
    if ($input->get('verify_otp', null, 'cmd')) {
        $otpToken = $input->getString('otp');
        $transactionId = $session->get('tfa_tx_id');
        Log::add('Attempting to verify OTP for user: ' . $username . ' (Transaction ID: ' . $transactionId . ', OTP: ' . $otpToken . ')', Log::INFO, 'TFA');

        if (!$transactionId || !$otpToken) {
            Log::add('Invalid OTP request for user: ' . $username, Log::WARNING, 'TFA');
            $app->enqueueMessage('Invalid OTP request.', 'error');
            return true; // Show the form again
        }

        require_once JPATH_ROOT . '/plugins/user/miniorangetfa/curl.php';
        $response = MocURLOTP::validatee_otp_token($transactionId, $otpToken);
        Log::add('OTP verify response: ' . $response, Log::INFO, 'TFA');
        $data = json_decode($response, true);

        if (!empty($data['status']) && $data['status'] === 'SUCCESS') {
            $session->clear('tfa_tx_id');
            $session->set('passwordless_validated', true); // Set validation flag
            Log::add('OTP verified successfully for user: ' . $username, Log::INFO, 'TFA');
            return false; // Validation successful, continue with normal flow
        } else {
            $otpMessage = '<p class="mo_boot_alert-danger mo_tfa_text" style="color:red;">Invalid OTP. Please try again.</p>';
        }
    }
    
    // Handle OTP sending
    if ($input->get('send_otp', null, 'cmd')) {
        Log::add('Attempting to send OTP to email: ' . $email, Log::INFO, 'TFA');
        require_once JPATH_ROOT . '/plugins/user/miniorangetfa/curl.php';
        $response = MocURLOTP::mo_send_otp_token('EMAIL', $email);
        Log::add('OTP send response: ' . $response, Log::INFO, 'TFA');
        $data = json_decode($response, true);

        if (!empty($data['status']) && $data['status'] === 'SUCCESS') {
            $session->set('tfa_tx_id', $data['txId']);
            $otpMessage = '<p class="mo_boot_alert-success mo_tfa_text" style="color:green;">OTP sent to <strong>' . htmlspecialchars($email) . '</strong>.</p>';
            Log::add('OTP sent successfully to: ' . $email, Log::INFO, 'TFA');
        } else {
            $otpMessage = '<p class="mo_boot_alert-danger mo_tfa_text" style="color:red;">Failed to send OTP. Please try again.</p>';
            Log::add('Failed to send OTP to: ' . $email, Log::ERROR, 'TFA');
        }
    }
    
    // Show OTP validation form
    echo '<div class="container-fluid mo_boot_text-center mo_tfa_container">';
    echo '<div class="mo_boot_col-sm-5 mo_boot_offset-sm-3">';
    echo '<div class="mo_boot_row mo_tfa_main mo_tfa_custom">';
    echo '<div class="mo_boot_col-sm-12 mo_tfa_title"><center>' . Text::_('COM_MINIORANGE_VERIFY_YOUR_EMAIL') . '</center></div>';

    echo '<div class="mo_boot_col-sm-12 mo_boot_mt-2">';
    if (!empty($otpMessage)) {
        echo '<div class="mo_boot_col-sm-12">' . $otpMessage . '</div>';
    }

    echo '<form method="post">';

    if (!$session->get('tfa_tx_id')) {
        $emailSafe = htmlspecialchars($email);
        echo '<p>' . Text::_('COM_MINIORANGE_SEND_OTP_TO_EMAIL') . ' <strong>' . $emailSafe . '</strong></p>'
        . '<input type="submit" name="send_otp" value="' . Text::_('COM_MINIORANGE_SEND_OTP') . '" class="mo_boot_btn mo_boot_btn-primary mo_btn_custom mo_tfa_text mo_tfa_dark_site" />';
    } else {
        echo '<div class="mo_boot_row">
            <div class="mo_boot_col-sm-10 mo_boot_text-left mo_boot_ms-5">
                <label class="mo_tfa_text">' . Text::_('COM_MINIORANGE_ENTER_OTP') . '</label>
            </div>
        </div>
        <div class="mo_boot_row">
            <div class="mo_boot_col-sm-10 mo_boot_m-2">
                <input type="text" name="otp" required class="input mo_boot_form-control mo_tfa_text mo_boot_ms-5" placeholder="' . Text::_('COM_MINIORANGE_ENTER_OTP_PLACEHOLDER') . '" />
            </div>
        </div>
        <input type="submit" name="verify_otp" value="' . Text::_('COM_MINIORANGE_VERIFY_OTP') . '" class="mo_boot_btn mo_boot_btn-primary mo_btn_custom mo_tfa_text mo_tfa_dark_site mo_boot_mt-2" />';
    }
    echo '</form></div></div></div></div>';
    
    return true; // Show the form
}
?>
<html>
        <head>
		<script src="<?php echo Uri::base() . 'components/com_miniorange_twofa/assets/mo_tfa_view.js'; ?>"></script>

	    	<link rel="stylesheet" type="text/css" href="<?php echo Uri::base() . 'components/com_miniorange_twofa/media/css/mo_tfa_inline.css';?>">
	    	<link rel="stylesheet" type="text/css" href="<?php echo Uri::base() . 'administrator/components/com_miniorange_twofa/assets/css/mo_tfa_phone.css';?>">
			<link rel="stylesheet" type="text/css" href="<?php echo URI::base() . 'administrator/components/com_miniorange_twofa/assets/css/miniorange_boot.css';?>">
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	    	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>	
			<script src = "administrator\components\com_miniorange_twofa\assets\js\remember_me\js\jquery-1.9.1.js"></script>
  			<script src = "administrator\components\com_miniorange_twofa\assets\js\remember_me\js\jquery.flash.js"></script>
		   	<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<script>
				(function($) {
				$(document).ready(
					function() {
						jQuery(".mo_tfa_query_phone").intlTelInput();
						var mo_2fa_phone = document.getElementsByClassName('mo_tfa_query_phone');
						for(var i=0;i<mo_2fa_phone.length;i++){
						mo_2fa_phone[i].value=mo_2fa_phone[i].value.replace(' ',"")
						}  
						Array.from(mo_2fa_phone).forEach(v => v.addEventListener('keyup', function() {
							if(this.value=='' || this.value[0]!='+' )
							{
							this.value='+'+this.value;
							jQuery(".mo_tfa_query_phone").keyup();
							}
						}));

					});
				}(jQuery));


				function resize_phone_field (){
						var phone_field_arr=document.getElementsByClassName('mo_tfa_query_phone');
						for (let i=0;i<phone_field_arr.length;i++){
							phone_field_arr[i].size=55;
						}

				}

				!function(a,b,c){function d(b,c){this.element=b,this.options=a.extend({},f,c),this._defaults=f,this._name=e,this.init()}var e="intlTelInput",f={preferredCountries:["IN","US"],americaMode:false};d.prototype={init:function(){var b=this,d=[];a.each(this.options.preferredCountries,function(b,c){var e=a.grep(intlTelInput.countries,function(a){return a.cca2==c});e.length&&d.push(e[0])});var e=a(this.element);""!==e.val()||this.options.americaMode||e.val("+1 "),e.wrap(a("<div>",{"class":"intl-number-input"}));var f=a("<div>",{"class":"flag-dropdown f16 mo_boot_mt-2"}).insertBefore(e),g=a("<div>",{"class":"selected-flag"}).appendTo(f),h=d[0].cca2.toLowerCase(),i=a("<div>",{"class":"flag "+h}).appendTo(g);a("<div>",{"class":"down-arrow"}).appendTo(i);var j=a("<ul>",{"class":"country-list hide"}).appendTo(f);this.appendListItems(d,j),a("<li>",{"class":"divider"}).appendTo(j),this.appendListItems(intlTelInput.countries,j);var k=j.children(".country");k.first().addClass("active"),e.keyup(function(){var c=b.getDialCode(e.val())||"1",d=intlTelInput.countryCodes[c],f=!1;if(a.each(d,function(a,b){i.hasClass(b.toLowerCase())&&(f=!0)}),!f){var g=intlTelInput.countryCodes[c][0].toLowerCase();i.attr("class","flag "+g),k.removeClass("active"),k.children(".flag."+g).parent().addClass("active")}}),e.keyup(),g.click(function(d){if(d.stopPropagation(),j.hasClass("hide")){k.removeClass("highlight");var f=j.children(".active").addClass("highlight");b.scrollTo(f,j),j.removeClass("hide"),a(c).bind("keydown.intlTelInput",function(c){if(38==c.which||40==c.which){var d=j.children(".highlight").first(),f=38==c.which?d.prev():d.next();f&&(f.hasClass("divider")&&(f=38==c.which?f.prev():f.next()),k.removeClass("highlight"),f.addClass("highlight"),b.scrollTo(f,j))}else if(13==c.which){var h=j.children(".highlight").first();h.length&&b.selectCountry(h,g,e,j)}else if(9==c.which||27==c.which)b.closeDropdown(j);else if(c.which>=97&&c.which<=122||c.which>=65&&c.which<=90){var i=String.fromCharCode(c.which),l=k.filter(function(){return a(this).text().charAt(0)==i});if(l.length){var m,n=l.filter(".highlight").first();m=n&&n.next()&&n.next().text().charAt(0)==i?n.next():l.first(),k.removeClass("highlight"),m.addClass("highlight"),b.scrollTo(m,j)}}})}else b.closeDropdown(j)}),k.mouseover(function(){k.removeClass("highlight"),a(this).addClass("highlight")}),k.click(function(c){var d=a(c.currentTarget);b.selectCountry(d,g,e,j)}),a("html").click(function(c){a(c.target).closest(".country-list").length||b.closeDropdown(j)})},selectCountry:function(a,b,c,d){var e=a.attr("data-country-code").toLowerCase();b.find(".flag").attr("class","flag "+e);var f=this.updateNumber(c.val(),a.attr("data-dial-code"));c.val(f),this.closeDropdown(d),c.focus(),d.children(".country").removeClass("active highlight"),a.addClass("active")},closeDropdown:function(b){b.addClass("hide"),a(c).unbind("keydown.intlTelInput")},scrollTo:function(a,b){var c=b.height(),d=b.offset().top,e=d+c,f=a.outerHeight(),g=a.offset().top,h=g+f,i=g-d+b.scrollTop();if(d>g)b.scrollTop(i);else if(h>e){var j=c-f;b.scrollTop(i-j)}},updateNumber:function(a,b){var c,d="+"+this.getDialCode(a),e="+"+b;return d.length>1?(c=a.replace(d,e),a==d&&(c+=" ")):c=a.length&&"+"!=a.substr(0,1)?e+" "+a.trim():e+" ",this.options.americaMode&&"+1 "==c.substring(0,3)&&(c=c.substring(3)),c},getDialCode:function(a){var b=a.trim().split(" ")[0];if("+"==b.substring(0,1))for(var c=b.replace(/\D/g,"").substring(0,4),d=c.length;d>0;d--)if(c=c.substring(0,d),intlTelInput.countryCodes[c])return c;return""},appendListItems:function(b,c){var d="";a.each(b,function(a,b){d+="<li class='country' data-dial-code='"+b["calling-code"]+"' data-country-code='"+b.cca2+"'>",d+="<div class='flag "+b.cca2.toLowerCase()+"'></div>",d+="<span class='country-name'>"+b.name+"</span>",d+="<span class='dial-code'>+"+b["calling-code"]+"</span>",d+="</li>"}),c.append(d)}},a.fn[e]=function(b){return this.each(function(){a.data(this,"plugin_"+e)||a.data(this,"plugin_"+e,new d(this,b))})}}(jQuery,window,document);var intlTelInput={countries:[{name:"Afghanistan",cca2:"AF","calling-code":"93"},{name:"Albania",cca2:"AL","calling-code":"355"},{name:"Algeria",cca2:"DZ","calling-code":"213"},{name:"American Samoa",cca2:"AS","calling-code":"1684"},{name:"Andorra",cca2:"AD","calling-code":"376"},{name:"Angola",cca2:"AO","calling-code":"244"},{name:"Anguilla",cca2:"AI","calling-code":"1264"},{name:"Antigua and Barbuda",cca2:"AG","calling-code":"1268"},{name:"Argentina",cca2:"AR","calling-code":"54"},{name:"Armenia",cca2:"AM","calling-code":"374"},{name:"Aruba",cca2:"AW","calling-code":"297"},{name:"Australia",cca2:"AU","calling-code":"61"},{name:"Austria",cca2:"AT","calling-code":"43"},{name:"Azerbaijan",cca2:"AZ","calling-code":"994"},{name:"Bahamas",cca2:"BS","calling-code":"1242"},{name:"Bahrain",cca2:"BH","calling-code":"973"},{name:"Bangladesh",cca2:"BD","calling-code":"880"},{name:"Barbados",cca2:"BB","calling-code":"1246"},{name:"Belarus",cca2:"BY","calling-code":"375"},{name:"Belgium",cca2:"BE","calling-code":"32"},{name:"Belize",cca2:"BZ","calling-code":"501"},{name:"Benin",cca2:"BJ","calling-code":"229"},{name:"Bermuda",cca2:"BM","calling-code":"1441"},{name:"Bhutan",cca2:"BT","calling-code":"975"},{name:"Bolivia",cca2:"BO","calling-code":"591"},{name:"Bosnia and Herzegovina",cca2:"BA","calling-code":"387"},{name:"Botswana",cca2:"BW","calling-code":"267"},{name:"Brazil",cca2:"BR","calling-code":"55"},{name:"Brunei Darussalam",cca2:"BN","calling-code":"673"},{name:"Bulgaria",cca2:"BG","calling-code":"359"},{name:"Burkina Faso",cca2:"BF","calling-code":"226"},{name:"Burundi",cca2:"BI","calling-code":"257"},{name:"Cambodia",cca2:"KH","calling-code":"855"},{name:"Cameroon",cca2:"CM","calling-code":"237"},{name:"Canada",cca2:"CA","calling-code":"1"},{name:"Cape Verde",cca2:"CV","calling-code":"238"},{name:"Cayman Islands",cca2:"KY","calling-code":"1345"},{name:"Central African Republic",cca2:"CF","calling-code":"236"},{name:"Chad",cca2:"TD","calling-code":"235"},{name:"Chile",cca2:"CL","calling-code":"56"},{name:"China",cca2:"CN","calling-code":"86"},{name:"Colombia",cca2:"CO","calling-code":"57"},{name:"Comoros",cca2:"KM","calling-code":"269"},{name:"Congo (DRC)",cca2:"CD","calling-code":"243"},{name:"Congo (Republic)",cca2:"CG","calling-code":"242"},{name:"Cook Islands",cca2:"CK","calling-code":"682"},{name:"Costa Rica",cca2:"CR","calling-code":"506"},{name:"Côte d'Ivoire",cca2:"CI","calling-code":"225"},{name:"Croatia",cca2:"HR","calling-code":"385"},{name:"Cuba",cca2:"CU","calling-code":"53"},{name:"Cyprus",cca2:"CY","calling-code":"357"},{name:"Czech Republic",cca2:"CZ","calling-code":"420"},{name:"Denmark",cca2:"DK","calling-code":"45"},{name:"Djibouti",cca2:"DJ","calling-code":"253"},{name:"Dominica",cca2:"DM","calling-code":"1767"},{name:"Dominican Republic",cca2:"DO","calling-code":"1809"},{name:"Ecuador",cca2:"EC","calling-code":"593"},{name:"Egypt",cca2:"EG","calling-code":"20"},{name:"El Salvador",cca2:"SV","calling-code":"503"},{name:"Equatorial Guinea",cca2:"GQ","calling-code":"240"},{name:"Eritrea",cca2:"ER","calling-code":"291"},{name:"Estonia",cca2:"EE","calling-code":"372"},{name:"Ethiopia",cca2:"ET","calling-code":"251"},{name:"Faroe Islands",cca2:"FO","calling-code":"298"},{name:"Fiji",cca2:"FJ","calling-code":"679"},{name:"Finland",cca2:"FI","calling-code":"358"},{name:"France",cca2:"FR","calling-code":"33"},{name:"French Polynesia",cca2:"PF","calling-code":"689"},{name:"Gabon",cca2:"GA","calling-code":"241"},{name:"Gambia",cca2:"GM","calling-code":"220"},{name:"Georgia",cca2:"GE","calling-code":"995"},{name:"Germany",cca2:"DE","calling-code":"49"},{name:"Ghana",cca2:"GH","calling-code":"233"},{name:"Gibraltar",cca2:"GI","calling-code":"350"},{name:"Greece",cca2:"GR","calling-code":"30"},{name:"Greenland",cca2:"GL","calling-code":"299"},{name:"Grenada",cca2:"GD","calling-code":"1473"},{name:"Guadeloupe",cca2:"GP","calling-code":"590"},{name:"Guam",cca2:"GU","calling-code":"1671"},{name:"Guatemala",cca2:"GT","calling-code":"502"},{name:"Guernsey",cca2:"GG","calling-code":"44"},{name:"Guinea",cca2:"GN","calling-code":"224"},{name:"Guinea-Bissau",cca2:"GW","calling-code":"245"},{name:"Guyana",cca2:"GY","calling-code":"592"},{name:"Haiti",cca2:"HT","calling-code":"509"},{name:"Honduras",cca2:"HN","calling-code":"504"},{name:"Hong Kong",cca2:"HK","calling-code":"852"},{name:"Hungary",cca2:"HU","calling-code":"36"},{name:"Iceland",cca2:"IS","calling-code":"354"},{name:"India",cca2:"IN","calling-code":"91"},{name:"Indonesia",cca2:"ID","calling-code":"62"},{name:"Iran",cca2:"IR","calling-code":"98"},{name:"Iraq",cca2:"IQ","calling-code":"964"},{name:"Ireland",cca2:"IE","calling-code":"353"},{name:"Isle of Man",cca2:"IM","calling-code":"44"},{name:"Israel",cca2:"IL","calling-code":"972"},{name:"Italy",cca2:"IT","calling-code":"39"},{name:"Jamaica",cca2:"JM","calling-code":"1876"},{name:"Japan",cca2:"JP","calling-code":"81"},{name:"Jersey",cca2:"JE","calling-code":"44"},{name:"Jordan",cca2:"JO","calling-code":"962"},{name:"Kazakhstan",cca2:"KZ","calling-code":"7"},{name:"Kenya",cca2:"KE","calling-code":"254"},{name:"Kiribati",cca2:"KI","calling-code":"686"},{name:"Kuwait",cca2:"KW","calling-code":"965"},{name:"Kyrgyzstan",cca2:"KG","calling-code":"996"},{name:"Laos",cca2:"LA","calling-code":"856"},{name:"Latvia",cca2:"LV","calling-code":"371"},{name:"Lebanon",cca2:"LB","calling-code":"961"},{name:"Lesotho",cca2:"LS","calling-code":"266"},{name:"Liberia",cca2:"LR","calling-code":"231"},{name:"Libya",cca2:"LY","calling-code":"218"},{name:"Liechtenstein",cca2:"LI","calling-code":"423"},{name:"Lithuania",cca2:"LT","calling-code":"370"},{name:"Luxembourg",cca2:"LU","calling-code":"352"},{name:"Macao",cca2:"MO","calling-code":"853"},{name:"Macedonia",cca2:"MK","calling-code":"389"},{name:"Madagascar",cca2:"MG","calling-code":"261"},{name:"Malawi",cca2:"MW","calling-code":"265"},{name:"Malaysia",cca2:"MY","calling-code":"60"},{name:"Maldives",cca2:"MV","calling-code":"960"},{name:"Mali",cca2:"ML","calling-code":"223"},{name:"Malta",cca2:"MT","calling-code":"356"},{name:"Marshall Islands",cca2:"MH","calling-code":"692"},{name:"Martinique",cca2:"MQ","calling-code":"596"},{name:"Mauritania",cca2:"MR","calling-code":"222"},{name:"Mauritius",cca2:"MU","calling-code":"230"},{name:"Mexico",cca2:"MX","calling-code":"52"},{name:"Micronesia",cca2:"FM","calling-code":"691"},{name:"Moldova",cca2:"MD","calling-code":"373"},{name:"Monaco",cca2:"MC","calling-code":"377"},{name:"Mongolia",cca2:"MN","calling-code":"976"},{name:"Montenegro",cca2:"ME","calling-code":"382"},{name:"Montserrat",cca2:"MS","calling-code":"1664"},{name:"Morocco",cca2:"MA","calling-code":"212"},{name:"Mozambique",cca2:"MZ","calling-code":"258"},{name:"Myanmar (Burma)",cca2:"MM","calling-code":"95"},{name:"Namibia",cca2:"NA","calling-code":"264"},{name:"Nauru",cca2:"NR","calling-code":"674"},{name:"Nepal",cca2:"NP","calling-code":"977"},{name:"Netherlands",cca2:"NL","calling-code":"31"},{name:"New Caledonia",cca2:"NC","calling-code":"687"},{name:"New Zealand",cca2:"NZ","calling-code":"64"},{name:"Nicaragua",cca2:"NI","calling-code":"505"},{name:"Niger",cca2:"NE","calling-code":"227"},{name:"Nigeria",cca2:"NG","calling-code":"234"},{name:"North Korea",cca2:"KP","calling-code":"850"},{name:"Norway",cca2:"NO","calling-code":"47"},{name:"Oman",cca2:"OM","calling-code":"968"},{name:"Pakistan",cca2:"PK","calling-code":"92"},{name:"Palau",cca2:"PW","calling-code":"680"},{name:"Palestinian Territory",cca2:"PS","calling-code":"970"},{name:"Panama",cca2:"PA","calling-code":"507"},{name:"Papua New Guinea",cca2:"PG","calling-code":"675"},{name:"Paraguay",cca2:"PY","calling-code":"595"},{name:"Peru",cca2:"PE","calling-code":"51"},{name:"Philippines",cca2:"PH","calling-code":"63"},{name:"Poland",cca2:"PL","calling-code":"48"},{name:"Portugal",cca2:"PT","calling-code":"351"},{name:"Puerto Rico",cca2:"PR","calling-code":"1787"},{name:"Qatar",cca2:"QA","calling-code":"974"},{name:"Réunion",cca2:"RE","calling-code":"262"},{name:"Romania",cca2:"RO","calling-code":"40"},{name:"Russian Federation",cca2:"RU","calling-code":"7"},{name:"Rwanda",cca2:"RW","calling-code":"250"},{name:"Saint Kitts and Nevis",cca2:"KN","calling-code":"1869"},{name:"Saint Lucia",cca2:"LC","calling-code":"1758"},{name:"Saint Vincent and the Grenadines",cca2:"VC","calling-code":"1784"},{name:"Samoa",cca2:"WS","calling-code":"685"},{name:"San Marino",cca2:"SM","calling-code":"378"},{name:"São Tomé and Príncipe",cca2:"ST","calling-code":"239"},{name:"Saudi Arabia",cca2:"SA","calling-code":"966"},{name:"Senegal",cca2:"SN","calling-code":"221"},{name:"Serbia",cca2:"RS","calling-code":"381"},{name:"Seychelles",cca2:"SC","calling-code":"248"},{name:"Sierra Leone",cca2:"SL","calling-code":"232"},{name:"Singapore",cca2:"SG","calling-code":"65"},{name:"Slovakia",cca2:"SK","calling-code":"421"},{name:"Slovenia",cca2:"SI","calling-code":"386"},{name:"Solomon Islands",cca2:"SB","calling-code":"677"},{name:"Somalia",cca2:"SO","calling-code":"252"},{name:"South Africa",cca2:"ZA","calling-code":"27"},{name:"South Korea",cca2:"KR","calling-code":"82"},{name:"Spain",cca2:"ES","calling-code":"34"},{name:"Sri Lanka",cca2:"LK","calling-code":"94"},{name:"Sudan",cca2:"SD","calling-code":"249"},{name:"Suriname",cca2:"SR","calling-code":"597"},{name:"Swaziland",cca2:"SZ","calling-code":"268"},{name:"Sweden",cca2:"SE","calling-code":"46"},{name:"Switzerland",cca2:"CH","calling-code":"41"},{name:"Syrian Arab Republic",cca2:"SY","calling-code":"963"},{name:"Taiwan, Province of China",cca2:"TW","calling-code":"886"},{name:"Tajikistan",cca2:"TJ","calling-code":"992"},{name:"Tanzania",cca2:"TZ","calling-code":"255"},{name:"Thailand",cca2:"TH","calling-code":"66"},{name:"Timor-Leste",cca2:"TL","calling-code":"670"},{name:"Togo",cca2:"TG","calling-code":"228"},{name:"Tonga",cca2:"TO","calling-code":"676"},{name:"Trinidad and Tobago",cca2:"TT","calling-code":"1868"},{name:"Tunisia",cca2:"TN","calling-code":"216"},{name:"Turkey",cca2:"TR","calling-code":"90"},{name:"Turkmenistan",cca2:"TM","calling-code":"993"},{name:"Turks and Caicos Islands",cca2:"TC","calling-code":"1649"},{name:"Tuvalu",cca2:"TV","calling-code":"688"},{name:"Uganda",cca2:"UG","calling-code":"256"},{name:"Ukraine",cca2:"UA","calling-code":"380"},{name:"United Arab Emirates",cca2:"AE","calling-code":"971"},{name:"United Kingdom",cca2:"GB","calling-code":"44"},{name:"United States",cca2:"US","calling-code":"1"},{name:"Uruguay",cca2:"UY","calling-code":"598"},{name:"Uzbekistan",cca2:"UZ","calling-code":"998"},{name:"Vanuatu",cca2:"VU","calling-code":"678"},{name:"Vatican City",cca2:"VA","calling-code":"379"},{name:"Venezuela",cca2:"VE","calling-code":"58"},{name:"Viet Nam",cca2:"VN","calling-code":"84"},{name:"Virgin Islands (British)",cca2:"VG","calling-code":"1284"},{name:"Virgin Islands (U.S.)",cca2:"VI","calling-code":"1340"},{name:"Western Sahara",cca2:"EH","calling-code":"212"},{name:"Yemen",cca2:"YE","calling-code":"967"},{name:"Zambia",cca2:"ZM","calling-code":"260"},{name:"Zimbabwe",cca2:"ZW","calling-code":"263"}],countryCodes:{1:["US"],7:["RU","KZ"],20:["EG"],27:["ZA"],30:["GR"],31:["NL"],32:["BE"],33:["FR"],34:["ES"],36:["HU"],39:["IT"],40:["RO"],41:["CH"],43:["AT"],44:["GB","GG","IM","JE"],45:["DK"],46:["SE"],47:["NO","SJ"],48:["PL"],49:["DE"],51:["PE"],52:["MX"],53:["CU"],54:["AR"],55:["BR"],56:["CL"],57:["CO"],58:["VE"],60:["MY"],61:["AU","CC","CX"],62:["ID"],63:["PH"],64:["NZ"],65:["SG"],66:["TH"],81:["JP"],82:["KR"],84:["VN"],86:["CN"],90:["TR"],91:["IN"],92:["PK"],93:["AF"],94:["LK"],95:["MM"],98:["IR"],211:["SS"],212:["MA","EH"],213:["DZ"],216:["TN"],218:["LY"],220:["GM"],221:["SN"],222:["MR"],223:["ML"],224:["GN"],225:["CI"],226:["BF"],227:["NE"],228:["TG"],229:["BJ"],230:["MU"],231:["LR"],232:["SL"],233:["GH"],234:["NG"],235:["TD"],236:["CF"],237:["CM"],238:["CV"],239:["ST"],240:["GQ"],241:["GA"],242:["CG"],243:["CD"],244:["AO"],245:["GW"],246:["IO"],247:["AC"],248:["SC"],249:["SD"],250:["RW"],251:["ET"],252:["SO"],253:["DJ"],254:["KE"],255:["TZ"],256:["UG"],257:["BI"],258:["MZ"],260:["ZM"],261:["MG"],262:["RE","YT"],263:["ZW"],264:["NA"],265:["MW"],266:["LS"],267:["BW"],268:["SZ"],269:["KM"],290:["SH"],291:["ER"],297:["AW"],298:["FO"],299:["GL"],350:["GI"],351:["PT"],352:["LU"],353:["IE"],354:["IS"],355:["AL"],356:["MT"],357:["CY"],358:["FI","AX"],359:["BG"],370:["LT"],371:["LV"],372:["EE"],373:["MD"],374:["AM"],375:["BY"],376:["AD"],377:["MC"],378:["SM"],379:["VA"],380:["UA"],381:["RS"],382:["ME"],385:["HR"],386:["SI"],387:["BA"],389:["MK"],420:["CZ"],421:["SK"],423:["LI"],500:["FK"],501:["BZ"],502:["GT"],503:["SV"],504:["HN"],505:["NI"],506:["CR"],507:["PA"],508:["PM"],509:["HT"],590:["GP","BL","MF"],591:["BO"],592:["GY"],593:["EC"],594:["GF"],595:["PY"],596:["MQ"],597:["SR"],598:["UY"],599:["CW","BQ"],670:["TL"],672:["NF"],673:["BN"],674:["NR"],675:["PG"],676:["TO"],677:["SB"],678:["VU"],679:["FJ"],680:["PW"],681:["WF"],682:["CK"],683:["NU"],685:["WS"],686:["KI"],687:["NC"],688:["TV"],689:["PF"],690:["TK"],691:["FM"],692:["MH"],850:["KP"],852:["HK"],853:["MO"],855:["KH"],856:["LA"],880:["BD"],886:["TW"],960:["MV"],961:["LB"],962:["JO"],963:["SY"],964:["IQ"],965:["KW"],966:["SA"],967:["YE"],968:["OM"],970:["PS"],971:["AE"],972:["IL"],973:["BH"],974:["QA"],975:["BT"],976:["MN"],977:["NP"],992:["TJ"],993:["TM"],994:["AZ"],995:["GE"],996:["KG"],998:["UZ"],1242:["BS"],1246:["BB"],1264:["AI"],1268:["AG"],1284:["VG"],1340:["VI"],1345:["KY"],1441:["BM"],1473:["GD"],1649:["TC"],1664:["MS"],1671:["GU"],1684:["AS"],1758:["LC"],1767:["DM"],1784:["VC"],1787:["PR"],1809:["DO"],1868:["TT"],1869:["KN"],1876:["JM"]}};
					function mo2f_valid(f) {
					!(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
					}

					function search(element) {
					var arr=[];

					for(let i=0;i<intlTelInput.countries.length;i++){
						if(element==intlTelInput.countries[i]["calling-code"]){
						return 1;
						} }return 0;
					}
			</script>
		
		</head>
		<body onLoad="noBack();" onpageshow="if (event.persisted) noBack();" onUnload="">
		
		<?php
				$settings = commonUtilitiesTfa::getTfaSettings();
				$CustomCssSaved  = isset($settings['customFormCss']) && !empty($settings['customFormCss'])
				? $settings['customFormCss']
				: "" ;

				$CustomButtonSaved = isset($settings['primarybtnCss']) && !empty($settings['primarybtnCss'])
				? $settings['primarybtnCss']
				: "#337ab7";

				$customCssSaved =explode(";",$CustomCssSaved);
				$fields = array();
				foreach($customCssSaved as $key=>$value)
				{
					$breakCss =explode(":",$value);
					$fields[$breakCss[0]] = isset($breakCss[1])?$breakCss[1]:"";
				}

				$border = isset($fields['border-top'])?explode(" ",$fields['border-top']):array( 0 => '2px',
				1 => 'solid',
				2 => '#20b2aa');

				if(is_array($border))
				{
						$border0= $border['0'];
						$border1= $border['2'];
				}
				else{
					$border0 = "";
					$border1 = "";
				}

				if(isset($fields)&&!empty($fields))
				{
					
					$radius = isset($fields['border-radius'])?(int)$fields['border-radius']:'8px';
					$bgcolor = isset($fields['background-color'])?strtok($fields['background-color'],'!'):"#FFFFFF";
					$bordertop = isset($border1)?strtok($border1,'!'):'#20b2aa';
					$margin = (int)$border0;
					$height = isset($fields['min-height'])?(int)$fields['min-height']:'200px';
					$primarymo_boot_btn = strtok($CustomButtonSaved,'!');
					?>
					<?php
				}
				?>
				<style>
				.mo_tfa_custom{
					border-radius:  <?php echo $radius; ?>px;
					box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
					background-color: <?php echo $bgcolor; ?>;
					border: 1px solid #CCCCCC;
					min-height: <?php echo $height; ?>;
					border-top: <?php echo $margin;?>px solid <?php echo $bordertop; ?> ;
					border-bottom: <?php echo $margin;?>px solid <?php echo $bordertop; ?>;
					margin: 5%;

				}
				@media (prefers-color-scheme: dark) {
					.mo_tfa_custom, .mo_tfa_title {
						color: var(--bs-dark);
					}
				}
				.mo_btn_custom{
					color: #fff;
					background-color: <?php echo $primarybtn; ?>;
					border-color: <?php echo $primarybtn; ?>;
				}
				.mo_btn_custom:hover {
					color: #fff;
					background-color: <?php echo $primarybtn; ?>;
					border-color: <?php echo $primarybtn; ?>;
					top: 310px;
					left: 1500px;
					outline: 0px;
					z-index: 2;
					border: 0;
					}

					.mo_btn_custom:focus, .mo_btn_custom.mo_boot_focus {
					box-shadow: none;
					}
					.mo_boot_btn-primary :not(:disabled):not(.mo_boot_disabled):active, .mo_boot_btn-primary :not(:disabled):not(.mo_boot_disabled).mo_boot_active,
					.mo_boot_show > .mo_boot_btn-primary .mo_boot_dropdown-toggle {
					color: #fff;
					background-color: <?php echo $primarybtn; ?>;
					border-color: <?php echo $primarybtn; ?>;
					}

					.mo_boot_btn-primary :not(:disabled):not(.mo_boot_disabled):active:focus, .mo_boot_btn-primary :not(:disabled):not(.mo_boot_disabled).mo_boot_active:focus,
					.mo_boot_show > .mo_boot_btn-primary .mo_boot_dropdown-toggle:focus {
					box-shadow: none;
					}
			</style>

			<?php
				$app = Factory::getApplication();
				$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
				$get = ($input && $input->get) ? $input->get->getArray() : [];
				$session = Factory::getSession();
				$step = $session->get('steps');

                if(!isset($step) || is_null($step) ||(array_key_exists('admin',$get) && $get['admin']=='1' && $session->get('started_at_admin')=='no')){
				
                	if(array_key_exists('admin',$get) && $get['admin']=='1'){
                		$session->set('started_at_admin','yes');
                	}
                	else{
                		$session->set('started_at_admin','no');
                	}
                	
                	$user= new miniOrangeUser();
                	if(isset($get['userId']) && !empty($get['userId'])){
						
                		$user->challanger($get['userId']);
                	}
                }
                $info = $session->get('motfa');
				$step = $session->get('steps');
				$msg = $session->get('mo_tfa_message');
                $msgType = $session->get('mo_tfa_message_type');

                $download_backup_code = isset($get['download_backup_code']) ? $get['download_backup_code'] : 'No';

				if($download_backup_code == 'downloadbkpcode')
                {
                    commonUtilitiesTfa::downloadTxtFile();
                }
					switch($step){
						case 'one':
							showStep1($info['inline'], $msg, $msgType);
							break;
						case 'two':
							showStep2($msg, $msgType);
							break;
						case 'three':
							// Check passwordless login validation first
							if (handlePasswordlessLoginValidation()) {
								break; // Show OTP form, don't continue to showStep3
							}
							showStep3($msg, $msgType);
							break;
						case 'four':
							// Check passwordless login validation first
							if (handlePasswordlessLoginValidation()) {
								break; // Show OTP form, don't continue to configureApp
							}
							
							if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']=='google'){
								Log::add('single totp methods started google'  ,Log::INFO, 'TFA');
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']=='MA')
							{
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']=='AA'){
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']=='LPA'){
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']== 'DUO'){
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else if(isset($info['stepThreeMethod']) && $info['stepThreeMethod']== 'YK'){
								configureApp($info['stepThreeMethod'],$msg,$msgType);
							}
							else{
									// Check passwordless login validation first
							if (handlePasswordlessLoginValidation()) {
								break; // Show OTP form, don't continue to configureApp
							}
								showStep4($msg,$msgType);
							}
							break;
						case 'five':
							$tfaSettings   = commonUtilitiesTfa::getTfaSettings();
							$backup_method_type = isset($tfaSettings['enable_backup_method_type'])?$tfaSettings['enable_backup_method_type'] : '';
							if($backup_method_type == 'backupCodes'){
								renderBackupCode();
							}
							else{
								showStep5();
							}
							break;
						case 'active':  
							$challangeInfo=$session->get('challenge_response');

							if(is_object($challangeInfo) && $challangeInfo->authType=='KBA'){
								moTfaBackupForm($msg,$msgType);
							}
							else
							{
								moTfaChallangeForm($msg,$msgType);
							}
							break;
						case 'invokeOOE':
							invokeOOE($info['inline'], $msg, $msgType);
							break;
						case 'validateEmail':
							if (handlePasswordlessLoginValidation()) {
								break; // Show OTP form, don't continue to showStep3
							}
							validateEmail($msg, $msgType);
							break;
						case 'backup':
							validateBackupCode($msg, $msgType);
							break;
						case 'KBA':
							moTfaBackupForm($msg,$msgType);
							break;
						case 'invalid':
							moTfaInvalid($msg,$msgType);
							break;
						case 'skip':
							moSkipemail($info['inline'],$msg,$msgType);
							break;
						case 'userLimit':
							$app->enqueueMessage('Not able to login. User limit exceeded for the Two Factor Authentication. Please contact your administrator', 'error');
							$app->redirect('index.php');
				}
			?>
		</body>
</html>

<?php
function moSkipemail($info,$msg='',$msgType='')
{
	$app = Factory::getApplication();
	$app = Factory::getApplication();
        $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
        $post = ($input && $input->post) ? $input->post->getArray() : [];
	$user = new miniOrangeUser();
	$session = Factory::getSession();
	$info = $session->get('motfa');
	$current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
	$tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
	$active_tfa_methods=trim($tfaSettings['activeMethods'],'[]');
	$method= str_replace(['"'],"",$active_tfa_methods);

	commonUtilitiesTfa::delete_user_from_server($current_user->email);
	// inserting user details in 2FA users table
	$row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
    $email = $current_user->email;
    $username = $current_user->username;

	if ((!is_array($row)) || (!isset($row['id'])) || ($row['id'] != $current_user->id)) 
	{
		commonUtilitiesTfa::insertMoTfaUser($username,$current_user->id, $email, $email, '');
	}

	$enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
	$backup_method_type = isset($tfaSettings['enable_backup_method_type']) ? $tfaSettings['enable_backup_method_type'] : '';

	$row = commonUtilitiesTfa::getMoTfaUserDetails($current_user->id);
	$customer_details = commonUtilitiesTfa::getCustomerDetails();
	$email = $current_user->email;
	
	//check for customer email and user email
	if($customer_details['email']!=$email)
	{
		$user_create_response = json_decode($user->mo_create_user($current_user->id,$current_user->name));	
	}
	
	$user_tfamethod_update_reponse=json_decode($user->mo2f_update_userinfo($row['email'],'OOE'));

			if($user_tfamethod_update_reponse->status=='SUCCESS')
			{
				if(isset($method) && !empty($method))
                    {
                        $info['stepThreeMethod']=$method;
                        $session->set('motfa',$info);
                        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'backup_method', $backup_method_type);
                        commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'active_method', $method);
                        if($method=='OOE')
                        {
                            if(!commonUtilitiesTfa::isValidUid($current_user->id)){
								$session->set('steps','invalid');
								$msg = 'Invalid User';
                            	$msg_type = 'mo2f-message-error';
								moTfaInvalid($msg,$msgType);
                            }
                            else
                            {                    
                                $user = new miniOrangeUser();
                                $response = json_decode($user->challenge($current_user->id, 'OOE', true));
								
                                if ($response->status == 'SUCCESS'){
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'transactionId', $response->txId);
                                    commonUtilitiesTfa::updateOptionOfUser($current_user->id, 'status_of_motfa', 'three');
								
                                    $mo2f_update_userinfo = json_decode($user->mo2f_update_userinfo($row['email'], 'OOE'));
									
									$email=commonUtilitiesTfa::_getMaskedEmail($email);
									
                                    $session->set('steps','validateEmail');
                                    $msg = Text::_('COM_MINIORANGE_OTP_SUCCESS_MSG') . $email . Text::_('COM_MINIORANGE_MSG_ENTER_OTP');
                                    $msg_type = 'mo2f-message-status';
									if (handlePasswordlessLoginValidation()) {
									Log::add('Showing OTP form validateEmail block', Log::INFO, 'TFA');
									return;
								    }
									validateEmail($msg,$msg_type);
									return;
                            
                                }
                            }
                        }
					
                        else
                        {
							$msg = 'Your 2FA account is created successfully. Please complete the setup';
                            $msg_type = 'mo2f-message-status';
                            $session->set('steps','four');
							if($method == 'OOS' || $method == 'OOSE' || $method=='OOC')
							{
								Log::add('single otp methods started'  ,Log::INFO, 'TFA');
								if (handlePasswordlessLoginValidation()) {
									Log::add('Showing OTP form in else block', Log::INFO, 'TFA');
									return;
								}
								showStep4($msg,$msg_type);
								return;
							}
							else
							{
								Log::add('single totp methods started'  ,Log::INFO, 'TFA');
								if (handlePasswordlessLoginValidation()) {
									Log::add('Showing OTP form in else block', Log::INFO, 'TFA');
									return;
								}
								Log::add('Configuring app for method: ' . $method, Log::INFO, 'TFA');
								configureApp($method,$msg,$msg_type);
								return;
							}
                        }	
        			}
			}
			else
			{
				$msg = $user_tfamethod_update_reponse->message;
				$session->set('steps','three');
				$session->set('mo_tfa_message',$msg);
				$session->set('mo_tfa_message_type','mo2f-message-error');
				moTfaInvalid($msg,'mo2f-message-error');
				return;
			}
}


function invokeOOE($info, $msg = '', $msgType = ''){
	
	$session = Factory::getSession();
	$info = $session->get('motfa');
    $juserId = $session->get('juserId');
	$uid = isset($info['inline']['whoStarted']->id) ? $info['inline']['whoStarted']->id : $juserId;
    $row = commonUtilitiesTfa::getMoTfaUserDetails($uid);
    $email = "";
    if(is_array($row) && isset($row['email'])){
        $email = $row['email'];
    }
	
    ?>
    <div class="container-fluid mo_boot_text-center mo_tfa_container">
   
                <div class="mo_boot_row mo_tfa_main mo_tfa_custom mo_boot_mo_boot_col-sm-4 mo_boot_offset-sm-4 ">
                    <div class="mo_boot_col-sm-12 mo_tfa_title">
                        <center><?php echo Text::_('COM_MINIORANGE_INVOKEOOE');?></center>
                    </div>
                    <div class="mo_boot_col-sm-12">
                        <?php
                        if(!empty($msg)){
                            ?>
                            <div class="mo2f-message <?php echo $msgType; ?>"><center><?php echo $msg;?></center></div>
                            <?php
                        }
                        ?>
                    </div>

                    <div class="mo_boot_col-sm-12">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.validateEmail'); ?>">
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-lg-4 mo_boot_col-sm-12">
                                    <label><strong><?php echo Text::_('COM_MINIORANGE_EMAIL');?></strong></label>
                                </div>
                                <div class="mo_boot_col-lg-8 mo_boot_col-sm-12">
                                    <input type="email" class="input mo_boot_form-control mo_tfa_cursor" name="miniorange_registered_email" value="<?php echo $email; ?>" placeholder="<?php echo Text::_('COM_MINIORANGE_EMAIL');?>" required readonly/>
                                </div>
                            </div>
                            <div class="mo_boot_row mo_boot_mt-3">
                                <div class="mo_boot_col-sm-12">
									<input type="button" onclick="previousStep()" name="Start_registration" class="mo_boot_btn mo_boot_btn-reset" value="<?php echo Text::_('COM_MINIORANGE_BACK');?>"  />
                                    <input type="submit"  name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VAL_NEXT');?>" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
           
    </div>
    <form name="fback" method="post" id="stepValidateBackupCodeBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.backValidateBackup'); ?>">
    </form>
    <form name="fback" method="post" id="stepThreeBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.gotoPreviousStep'); ?>">
    </form>
	<script type="text/javascript">
        function previousStep(){
            document.getElementById('stepThreeBack').submit();
        }
    </script>  
    <?php
}

function validateEmail($msg, $msgType)
{
	$settings = commonUtilitiesTfa::getTfaSettings();
	$active_tfa_methods = $settings['activeMethods'];
	$active_tfa_methods = json_decode($active_tfa_methods, TRUE);
	$count= count($active_tfa_methods);
	$tfa_ooe=in_array( "OOE" ,$active_tfa_methods );
    ?>
    <div class="container-fluid mo_boot_text-center mo_tfa_container">
        <div class="mo_boot_col-sm-5 mo_boot_offset-sm-3 ">
                <div class="mo_boot_row mo_tfa_main mo_tfa_custom ">
                    <div class="mo_boot_col-sm-12 mo_tfa_title">
                        <center><?php echo Text::_('COM_MINIORANGE_EMAIL_VALIDATE');?></center>
                    </div>
                    <div class="mo_boot_col-sm-12">
                        <?php
                        if(!empty($msg)){
                            ?>
                            <div class=" mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><center class="mo_tfa_red"><?php echo $msg;?></center></div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="mo_boot_col-sm-12 mo_boot_mt-2">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.validateOOE'); ?>">
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-10  mo_boot_text-left mo_boot_ms-5">
                                    <label class="mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_PASSCODE1');?></label>
                                </div>
							</div>
							<div class="mo_boot_row">
                                <div class="mo_boot_col-sm-10 mo_boot_m-2">
                                    <input type="text" class="input mo_boot_form-control mo_tfa_text mo_boot_ms-5" name="Passcode" value="" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PASSCODE');?>" autofocus required />
                                </div>
                            </div>
                            <div class="mo_boot_row mo_boot_mt-3">
                                <div class="mo_boot_col-sm-12 mo_boot_text-right">
                                    <input type="button" onclick="previousStep()" name="Start_registration" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_BACK');?>" formnovalidate  />
                                    <input type="submit"  name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_text mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VERIFY_NEXT');?>"  />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
        </div>
    </div>
    <form name="fback" method="post" id="navigateToBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.gotoPreviousStep'); ?>">
    </form>
	<form name="fback" method="post" id="navigateToBack1" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">
    </form>
    <script type="text/javascript">
        function previousStep(){
			var x = "<?php echo"$tfa_ooe"?>";
			var count = "<?php echo "$count"?>";
			if(x==true && count==1){
				document.getElementById('navigateToBack1').submit();
			}
			else{
				document.getElementById('navigateToBack').submit();
			}
        }
    </script>
    <?php
}

function moTfaInvalid($msg,$msgType){
	echo '<html><body bgcolor="#FFFFFF"></body></html>';
	exit();
}

function renderBackupCode()
{
    $random_string = commonUtilitiesTfa::generateBackupCodes();
    renderGenerateBackupCodeUI($random_string);
}

function renderGenerateBackupCodeUI($random_string)
{
    $backup_codes = implode(',', $random_string);
    commonUtilitiesTfa::saveInFile($backup_codes);

    ?>

    <div class="container-fluid mo_boot_text-center mo_tfa_container ">
        <div class="mo_boot_row">
            <div class="mo_boot_col-sm-5 mo_boot_offset-sm-3">
                <div class="mo_boot_row mo_tfa_main mo_tfa_custom">
                    <div class="mo_boot_col-sm-12 mo_tfa_title">
                        <?php echo Text::_('COM_MINIORANGE_BACKUP_DOWNLOAD');?><hr>
                    </div>
					
                    <div class="mo_boot_col-sm-11  mo_boot_alert-secondary mo_boot_mx-4 mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_BACKUP_DESC');?></div>

                    <div class="mo_boot_col-sm-12">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.stepFiveSubmitForBackupCode'); ?>">
                            <div class="mo_boot_row mo_boot_mt-3">
                                <div class="mo_boot_col-sm-12">
                                    <div class="mo_boot_row mo_boot_mt-3">
									
										
											<?php
                                            foreach ($random_string as $key => $value){
                                                ?>
                                                <tr>
                                                    <div class="mo_boot_col-sm-6 mo_boot_text-center">
                                                    <p class="mo_tfa_text"><i class="fas fa-key"></i>&nbsp;&nbsp;<?php echo $value ?></p>
											</div>
                                                </tr>
                                            <?php }
                                            ?>
                                    </div>
                                    <div class="mo_boot_row mo_boot_mt-3">
                                        <div class="mo_boot_col-sm-12 ">
                                            <input type="hidden" id="postId" name="backup_codes_values" value="<?php echo $backup_codes ?>" />
											<a class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_text"
                                               href="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&download_backup_code=downloadbkpcode'); ?>" >
											   <?php echo Text::_('COM_MINIORANGE_DOWNLOAD_CODES');?></a>
                                            <button type="submit" name="Start_registration"  class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_FINISH');?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
     
    </style>
    
    <?php
}

function validateBackupCode($msg, $msgType)
{
    if($msgType != 'mo2f-message-error'){
        $msg = Text::_('COM_MINIORANGE_MSG');
    }
    ?>
    <div class="container-fluid mo_boot_text-center mo_tfa_container">
        <div class="mo_boot_row">
            <div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
                <div class="mo_boot_row  mo_tfa_main mo_tfa_custom">
                    <div class="mo_boot_col-sm-12 mo_tfa_title mo_tfa_border" >
                        <span><center><?php echo Text::_('COM_MINIORANGE_VALIDATE_CODES');?></center></span>
                    </div>
                  
                    <div class="mo_boot_col-sm-12  mo_boot_mt-3">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.validateBackupCodes'); ?>">
                            <div class="mo_boot_row mo_boot_mb-3">
								<div class="mo_boot_col-sm-12">
									<?php
									if(!empty($msg)){
										Log::add(' validateBackupCode error', Log::INFO, 'tfa');
										?>
										<div class=" mo_boot_p-1 mo_tfa_text <?php echo ($msgType=='mo2f-message-status') ? ' mo_boot_alert-secondary' : 'alert-danger mo_tfa_red'; ?>" ><center><?php echo $msg;?></center></div>
										<?php
									}
									?>
								</div>
							</div>
							<div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12">
                                    <input type="text" class="input mo_boot_form-control mo_tfa_text"  name="backup_code_value" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_CODE');?>" required/>
                                </div>
                                <div class="mo_boot_col-sm-12 mo_boot_mt-3">
                                    <input type="submit"  name="validate_backup_code" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_text"  value="<?php echo Text::_('COM_MINIORANGE_VALIDATE');?>"  />
                                    <input type="button" onclick="previousStep()" name="step_Back" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text"  value="<?php echo Text::_('COM_MINIORANGE_CANCEL');?>" formnovalidate  />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">
    </form>
	<script type="text/javascript">
        function previousStep(){
            document.getElementById('stepTwoBack').submit();
        }
    </script> 
    <?php
}


function showStep1($info,$msg='',$msgType=''){
	
	$uid = $info['whoStarted']->id;
	$row = commonUtilitiesTfa::getMoTfaUserDetails($uid);
	$email = $info['whoStarted']->email;
	if(is_array($row) && isset($row['email'])){
		$email=$row['email'];
	}
	$settings = commonUtilitiesTfa::getTfaSettings();
	
    ?>
	<div class="container-fluid mo_boot_text-center mo_tfa_container">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_STEP1');?></center>
					</div>
					<div class="mo_boot_col-sm-12">
						<?php 
							if(!empty($msg)){
								?>
								<div class="mo2f-message <?php echo $msgType; ?>"><center><?php echo $msg;?></center></div>
								<?php
							}
						?>
					</div>
					<div class="mo_boot_col-sm-12">
						<p>
						<?php echo Text::_('COM_MINIORANGE_STEP1_DESC');?>
						</p>
					</div> 
					<div class="mo_boot_col-sm-12">
						<form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.testing'); ?>"> 
							<div class="mo_boot_row">
								<div class="mo_boot_col-lg-4 mo_boot_col-sm-12">
									<label><strong><?php echo Text::_('COM_MINIORANGE_EMAIL');?>:</strong></label>
								</div>
								<div class="mo_boot_col-lg-8 mo_boot_col-sm-12">

									<input id="edit_email" type="email" class="input mo_boot_form-control mo_tfa_cursor" name="miniorange_registered_email" value="<?php echo $email; ?>" placeholder="<?php echo Text::_('COM_MINIORANGE_EMAIL');?>" readonly/>
								</div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3">
								<div class="mo_boot_col-sm-12">
									<input type="submit"  name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_REGISTER');?>"  />
									<input type="button" onclick="previousStep()" name="cancel_registration" class="mo_boot_btn btn-danger" value="<?php echo Text::_('COM_MINIORANGE_CANCEL');?>"  />
                                    <?php if (is_null($row) && isset($settings['skip_tfa_for_users']) && $settings['skip_tfa_for_users'] == 1 ) {
                                        ?>
                                        <input type="button" onclick="skip_tfa_inline_registration()" name="mo_skip_tfa" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_dark_site" id="SkipTfaButton" value="<?php echo Text::_('COM_MINIORANGE_SKIP2FA');?>"  />
										<?php
                                    }
                                    ?>
                                </div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
    <form name="fback" method="post" id="stepValidateBackupCodeBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.backValidateBackup'); ?>">
    </form>
	<form name="fback" method="post" id="stepOneBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">
	</form>
	<script type="text/javascript">
		function previousStep(){
			document.getElementById('stepOneBack').submit();
		}
	</script>


    <form name="skip_tfa_button" method="post" id="skipTfa" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.skipTwoFactor'); ?>">
    </form>
	<script type="text/javascript">
        function skip_tfa_inline_registration(){
            jQuery('#2fa_skip').attr('value','yes');
            document.getElementById('skipTfa').submit();
        }

    </script>

	<?php
}

function showStep2($msg='',$msgType=''){
	?>
	<div class="container-fluid mo_boot_text-center mo_tfa_container">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_STEP2');?></center>
						</div>
					<div class="mo_boot_col-sm-12">
						<?php 
							if(!empty($msg)){
								?>
								<div class="mo2f-message <?php echo $msgType; ?>"><center><?php echo $msg;?></center></div>
								<?php
							}
						?> 
					</div>
					<div class="mo_boot_col-sm-12">
					<form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.pageTwoSubmit'); ?>">
							<div class="mo_boot_row">
								<div class=" mo_boot_col-lg-5 mo_boot_col-sm-12">
									<label><?php echo Text::_('COM_MINIORANGE_PASSCODE1');?></label>
								</div>
								<div class=" mo_boot_col-lg-7 mo_boot_col-sm-12">
									<input type="text" class="input mo_boot_form-control" name="Passcode" value="" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PASSCODE');?>" autofocus required />		  
								</div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3">
								<div class="mo_boot_col-sm-12">
									<input type="button" onclick="previousStep()" name="Start_registration" class="mo_boot_btn mo_tfa_text mo_boot_btn-secondary" value="<?php echo Text::_('COM_MINIORANGE_BACK');?>" formnovalidate  />
									<input type="submit"  name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom " value="<?php echo Text::_('COM_MINIORANGE_VERIFY_NEXT');?>"  />
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInlineTwo'); ?>">			
	</form>
	<script type="text/javascript">
		function previousStep(){
			document.getElementById('stepTwoBack').submit();
		}
	</script>
	<?php
}

function showStep3($msg='',$msgType=''){

	$settings = commonUtilitiesTfa::getTfaSettings();
	$session = Factory::getSession();
    $info = $session->get('motfa');
	$uid = $info['inline']['whoStarted']->id;
	$row = commonUtilitiesTfa::getMoTfaUserDetails($uid);
	$status_motfa=isset($row['status_of_motfa'])?$row['status_of_motfa']:"";

    ?>
	<div class="container-fluid mo_boot_text-center mo_tfa_container mo_tfa_dark_site">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4" >
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_STEP3');?></center>
					</div>
					<div class="mo_boot_col-sm-12">
						<?php 
							if(!empty($msg)){
								Log::add(' showstep3 error', Log::INFO, 'tfa');
							?>
							<div class=" mo_boot_p-1 mo_tfa_text <?php echo($msgType=='mo2f-message-error') ? 'alert-danger mo_tfa_red':' alert-info' ?>"><center><?php echo $msg;?></center></div>
							<?php
							}
							else
							{
								?>
									<div class=" mo_boot_p-1 mo_tfa_text alert-info"><center>Select a method to setup as your Second Factor Authentication </center></div>
								<?php
							}
							$active2FAs = commonUtilitiesTfa::getActive2FAMethods();
						?>
					</div>
					<div class="mo_boot_col-sm-12">
						<form name="f" method="post" id="2fa_methods" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.thirdStepSubmit'); ?>">
							<div class="mo_boot_row  mo_boot_text-left mo_tfa_methods">
								<div class="mo_boot_col-sm-11 mo_boot_text-center mo_boot_mt-3 mo_boot_ml-2">
									<span class="mo_tfa_text"><strong><?php echo Text::_('COM_MINIORANGE_METHOD');?></strong></span>
								</div>
								<div class='mo_boot_col-sm-8 mo_boot_m-2  '>
									<select class ="mo_boot_form-control mo_tfa_text mo_tfa_dark_site" name="miniorangetfa_method" id="miniorangetfa_method">
									<?php
                                        $authenticatorMethods = ['google', 'MA', 'AA', 'LPA', 'DUO'];
                                        $details = commonUtilitiesTfa::getCustomerDetails();
                                        
                                        foreach ($active2FAs as $key=>$value) {
                                            if($value['active']) {
                                                if(empty($details['license_type'])) {
                                                    // For demo account, only show authenticator methods
                                                    if(in_array($key, $authenticatorMethods)) {
                                                        echo "<option class='mo_tfa_text mo_tfa_dark_site' value='".$key."'>".$value['name']."</option>";
                                                    }
                                                } else {
                                                    // For premium accounts, show all methods
                                                    echo "<option class='mo_tfa_text mo_tfa_dark_site' value='".$key."'>".$value['name']."</option>";
                                                }
                                            }
                                        }
	                                ?>
									</select>
								</div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3">
								<div class="mo_boot_col-sm-12 mo_boot_text-right">
									<input type ="button" onclick="backToStep1()" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text mr-1"value="<?php echo Text::_('COM_MINIORANGE_BACK');?>" />
					  				<input type="submit" name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom mr-3 mo_tfa_dark_site"value="<?php echo Text::_('COM_MINIORANGE_SELECT_METHOD');?>"  />
									<?php if ( isset($settings['skip_tfa_for_users']) && $settings['skip_tfa_for_users'] == 1  ) {
                                    ?> 
                                    <button type="button" onclick="skip_tfa_inline_registration()" name="mo_skip_tfa" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom mo_tfa_dark_site" id="SkipTfaButton" ><?php echo Text::_('COM_MINIORANGE_SKIP2FA');?> </button>
										<?php
                                    }?>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

	<form name="fback" method="post" id="mo_skip_email" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>"></form>
  
	<form name="skip_tfa_button" method="post" id="skipTfa" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.skipTwoFactor'); ?>">
    </form>
    <script type="text/javascript">
		
		function backToStep1() {

				document.getElementById('mo_skip_email').submit();
		
        }
        function skip_tfa_inline_registration(){
			jQuery('#2fa_skip').attr('value','yes');
            document.getElementById('skipTfa').submit();
        }

    </script>

	<?php
}

function showStep4($msg='',$msgType=''){

	$session = Factory::getSession();

	$info = $session->get('motfa');
	$tfa_method=$info['stepThreeMethod'];
	
    $juserId = $session->get('juserId');

	$uid = isset($info['inline']['whoStarted']->id) ? $info['inline']['whoStarted']->id : $juserId;
	$row = commonUtilitiesTfa::getMoTfaUserDetails($uid);
	$settings = commonUtilitiesTfa::getTfaSettings();

	$active_tfa_methods = $settings['activeMethods'];
	$active_tfa_methods = trim($active_tfa_methods, '[]');
	
	$methods= str_replace(['"'],"",$active_tfa_methods);
	$count = count(explode(",",$methods));
	
	$phone='';
	
	if(is_array($row) && isset($row['phone']) && !empty($row['phone'])){
		$phone=$row['phone'];
	}
	else
	{
		$Phone = commonUtilitiesTfa:: __getDBProfileValues($uid,'profile.phone');
		$Phone = isset($Phone)?$Phone:'';
		$phone = str_replace(['"'],"",$Phone);
	}

	?>
	<div class="container-fluid mo_boot_text-center mo_tfa_container">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_STEP4');?></center>
					</div>
					<div class="mo_boot_col-sm-12">
						<?php
						if(!empty($msg))
						{
							?>
							<div class=" mo_boot_p-1  mo_tfa_text <?php echo ($msgType=='mo2f-message-status') ? ' mo_boot_alert-secondary' : 'alert-danger mo_tfa_red'; ?>" ><center><?php echo $msg;?></center></div>
							<?php
						}
						else
						{
							?>
							<div class=" mo_boot_p-1 alert-info mo_tfa_text"><center><?php echo ($tfa_method=='OOS')? Text::_('COM_MINIORANGE_TFA_PHONE') : Text::_('COM_MINIORANGE_TFA_MSG_SMS_EMAIL'); ?></center></div>
							<?php
						}
						?>
						</div> 
					<div class="mo_boot_col-sm-12">
                        <input type="hidden" id="next_page" value="0">
                        <form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.pageFourAndHAlf'); ?>"> 
							<div class="mo_boot_row mo_boot_mt-3  mo_boot_text-left mo_tfa_methods">
								<div class="mo_boot_col-sm-12">
									<label class="mo_tfa_text "><?php echo Text::_('COM_MINIORANGE_PHONE');?>:</label>
								</div>
							
							
								<div class=" mo_boot_col-sm-7">
									<input type="tel" required class="input mo_tfa_query_phone mo_boot_form-control mo_tfa_text mo_boot_mt-2" id="mo_tfa_query_phone" name="phone" pattern="[\+]\d{11,14}|[\+]\d{1,4}([\s]{0,1})(\d{0}|\d{9,10})" value="<?php echo $phone;?>" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PHONE');?>" autofocus />
								</div>
							
							
								<div class="mo_boot_col-sm-4">
									<input type="submit"  name="sendOtpBtn" class="mo_boot_btn mo_boot_btn-primary   mo_btn_custom mo_tfa_text mo_tfa_otp_btn mo_boot_mt-2 mo_tfa_dark_site"id="send_otp_btn" value="<?php echo Text::_('COM_MINIORANGE_SEND_OTP');?>"  />
                                </div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3  mo_boot_text-left mo_tfa_methods">
								<div class="mo_boot_col-sm-12">
									<label class="mo_tfa_text mo_tfa_font"><?php echo Text::_('COM_MINIORANGE_PASSCODE1');?></label>
								</div>
								<div class=" mo_boot_col-sm-7">
									<input type="text" id="moPass" class="input mo_boot_form-control mo_tfa_text mo_boot_mt-2"  name="Passcode1" value="" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PASSCODE');?>" autofocus />
								</div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3">
								<div class="mo_boot_col-sm-12">
									<input type="button" onclick="backToStep2()" name="Start_registration" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text" value=" <?php echo Text::_('COM_MINIORANGE_BACK');?>"  />
									<input type="button" onclick="submitNextForm()" name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_text mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VERIFY_NEXT');?>"  />
								</div>
							</div>
						</form>
						<form name="f" method="post" id="moStepFourNextForm" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.pageFourValidatePasscode'); ?>">
					  		<input type="hidden" id="moHiddenPasscode" name="Passcode" value="" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PASSCODE');?>" autofocus />
					  	</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInlineFour'); ?>">			
	</form>
	<form name="fback" method="post" id="stepTwoBack1" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">			
	</form>

	<script type="text/javascript">
		function backToStep2(){
			var count = "<?php echo "$count"?>";
			var method = "<?php echo "$methods"?>";
			if( count==1  && method!= 'ALL'){
				document.getElementById('stepTwoBack1').submit();
			}
			else{
				document.getElementById('stepTwoBack').submit();
			}
		}
		function submitNextForm(){
			var moPass= document.getElementById('moPass');
			document.getElementById('moHiddenPasscode').value=moPass.value;
			document.getElementById('moStepFourNextForm').submit();
		}
	</script>
	<?php
}

function showStep5(){
	$options=commonUtilitiesTfa::getKbaQuestions();
    $session = Factory::getSession();
    $isChange2FAEnabled = $session->get('change2FAEnabled');
    $tfaSettings = commonUtilitiesTfa::getMoTfaSettings();
    
    $invokeOOE = $session->get('ooe_for_change_2fa');
        $step = Text::_('COM_MINIORANGE_STEP ');
  

    ?>
	<div class="container-fluid mo_boot_text-center mo_tfa_container mo_tfa_google_auth">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-6 mo_boot_offset-sm-3">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_STEP_BACKUP');?> </center>
					</div>
					<div class="mo_boot_col-sm-12">
						<form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.stepFiveSubmit'); ?>">
							<div class="mo_boot_row mo_boot_mt-3  mo_boot_text-left">
								<div class="mo_boot_col-sm-11  mo_boot_alert-secondary mo_boot_mx-4 mo_tfa_text">
								<?php echo Text::_('COM_MINIORANGE_BACKUP_QUESTIONS');?>
								</div>
							</div>
							<div class="mo_boot_row mo_boot_mt-3">
								<div class="mo_boot_col-sm-12">
									<div class="mo_boot_row mo_boot_mt-3">
										<div class="mo_boot_col-sm-8 mo_tfa_text">
											<strong><?php echo Text::_('COM_MINIORANGE_QUESTION');?></strong>
										</div>
										<div class="mo_boot_col-sm-4 mo_tfa_text">
											<strong><?php echo Text::_('COM_MINIORANGE_ANSWER');?></strong>
										</div>
									</div>
									<div class="mo_boot_row mo_boot_mt-3">
										<div class="mo_boot_col-sm-8">
											<select class=" mo_tfa_text mo_tfa_backup_que " name="mo_tfa_ques_1">
												<?php echo $options['0']; ?>
											</select>
										</div>
										<div class="mo_boot_col-sm-4">
											<input class="mo_tfa_input mo_boot_form-control mo_tfa_text" type="text" name="mo_tfa_ans_1" placeholder="<?php echo Text::_("COM_MINIORANGE_VAL_ANSWER")?>" required />
										</div>
									</div>
									<div class="mo_boot_row mo_boot_mt-3">
										<div class="mo_boot_col-sm-8">
											<select class="mo_tfa_input_kba mo_tfa_backup_que mo_tfa_text" name="mo_tfa_ques_2">
												<?php echo $options['1']; ?>
											</select>
										</div>
										<div class="mo_boot_col-sm-4">
											<input class="mo_tfa_input mo_boot_form-control mo_tfa_text" type="text" name="mo_tfa_ans_2" placeholder="<?php echo Text::_("COM_MINIORANGE_VAL_ANSWER")?>" required />
										</div>
									</div>
									<div class="mo_boot_row mo_boot_mt-3">
										<div class="mo_boot_col-sm-8">
											<input class="mo_tfa_input_kba mo_boot_form-control mo_tfa_text" placeholder="<?php echo Text::_('COM_MINIORANGE_SECURITY_QUESTION');?>" name="mo_tfa_ques_3" required />
										</div>
										<div class="mo_boot_col-sm-4">
											<input class="mo_tfa_input mo_boot_form-control mo_tfa_text" type="text" name="mo_tfa_ans_3" placeholder="<?php echo Text::_("COM_MINIORANGE_VAL_ANSWER")?>" required/>
										</div>
									</div>
									<div class="mo_boot_row mo_boot_mt-3">
										<div class="mo_boot_col-sm-12">
											
											<input type="submit"  name="Start_registration" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VERIFY_NEXT');?>"  />
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInlineFive'); ?>">
	</form>
	
	<?php
}

function configureApp($name,$msg='',$msgType='')
{   
	$session=Factory::getSession();
	$info = $session->get('motfa');
	$current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
	
	// Clear any old Google Authenticator data
	$session->clear('googleInfo');
	
	$email = $current_user->email;
	Log::add('Configuring app for user: ' . $email, Log::INFO, 'TFA');

	$settings = commonUtilitiesTfa::getTfaSettings();
	$active_tfa_methods = $settings['activeMethods'];
	$active_tfa_methods = trim($active_tfa_methods, '[]');
	$methods= str_replace(['"'],"",$active_tfa_methods);
	$count = count(explode(",",$methods));
	
	if(!isset($googleInfo)||is_null($googleInfo))
	{
		$response=Mo_tfa_utilities::getAppQR($name,TRUE);
		if ($response) {
			$session->set('googleInfo',$response);
			Log::add('Generated new QR code for user: ' . $email, Log::INFO, 'TFA');
		} else {
			Log::add('Failed to generate QR code for user: ' . $email, Log::ERROR, 'TFA');
		}
	}
	else
	{
		$response=$session->get('googleInfo');
	}
	
	$appName=array(
			"google"=>"Google",
			"MA"=>"Microsoft",
			"AA"=>"Authy",
			"LPA"=>"LastPass",
			"DUO"=>"Duo",
			"YK" => "Hardware",
		);
		
    ?>
    <div class="container-fluid mo_boot_text-center mo_tfa_container mo_tfa_google_auth">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom  mo_boot_mt-4">
					<div class="mo_boot_col-sm-12 mo_tfa_title">
						<center><?php echo Text::_('COM_MINIORANGE_CONFIGURE');?></center>
					</div>
					
					<?php 
					if($appName[$name] == 'Hardware'){
					?>
					<div class="mo_boot_col-sm-12">
						<?php 
						if(!empty($msg)){
							Log::add(' configure app error', Log::INFO, 'tfa');
							?>
							<div class="mo_boot_col-sm-12">
								<div class=" mo_boot_p-1 mo_tfa_text <?php echo($msgType=='mo2f-message-status') ? ' alert-info':' alert-danger mo_tfa_red' ?>"><center><?php echo $msg ?></center></div>
							</div>
							<?php
						}
					  	?>
					</div>
					<div class="mo_boot_col-sm-12  mo_boot_text-left my-2">
						<form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.pageFourValidatePasscode'); ?>">
                            <div class="mo_boot_row">
                                <div class="mo_boot_col-sm-12">
                                    <label class="mo_tfa_text"><strong>Hardware Token One time passcode :</strong></label>
                                </div>
                                <div class="mo_boot_col-sm-12">
                                    <input type="text" class="input mo_boot_form-control mo_tfa_text mo_tfa_passcode"  name="mo_auth_token_textfield" value="" placeholder="Your passcode" autofocus required />
                                </div>
                            </div>
                            <div class="mo_boot_row mo_boot_mt-3">
                                <div class="mo_boot_col-sm-12 mo_tfa_back_btns" >
                                    <input type="button" onclick="backToStep2()" name="Backto_registration" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text" value="<?php echo Text::_('COM_MINIORANGE_BACK');?>" formnovalidate  />
                                    <input type="submit"  name="auth_token_submit" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VERIFY');?>"  />
                                </div>
                            </div>
                        </form>
					</div>
				</div>
			</div>
			<?php 
			}
			else{
			?>
			<div class="mo_boot_col-sm-12">
				<?php 
				if(!empty($msg)){
					Log::add(' configure app second error', Log::INFO, 'tfa');
					?>
					<div class="mo_boot_col-sm-12">
							<div class=" mo_boot_p-1 mo_tfa_text <?php echo($msgType=='mo2f-message-status') ? ' alert-info':' alert-danger mo_tfa_red' ?>"><center><?php echo ($msgType=='mo2f-message-error') ? "The passcode you entered is incorrect" : $msg ;?></center></div>
						</div>
					<?php
				}
				?>
			</div>
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_row ">
					<div class="mo_boot_col-sm-12  mo_boot_text-left my-2">
						<details>
							<summary><span class="mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_CONFIG_STEP1');?><strong> <?php echo $appName[$name];?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR_APP');?></strong></span></summary>

							<table width="100%;" id="mo2f_inline_table">
							<tr id="mo2f_inline_table">
								<td class="p2">
									<h3 class="mo_h3 mo_tfa_text" id="user_phone_id"><?php echo Text::_('COM_MINIORANGE_IPHONE');?></h3>
									<hr>
									<ol class="mo_tfa_text">
										<li><?php echo Text::_('COM_MINIORANGE_IPHONE_STEP1');?></li>
										<li><?php echo Text::_('COM_MINIORANGE_SEARCH');?> <?php echo $appName[$name] ?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR');?></li>
										<li><?php echo Text::_('COM_MINIORANGE_INSTALL');?></li>
									</ol>
									
								</td>
								<td class="p2">
									<h3 class="mo_h3 mo_tfa_text" id="user_phone_id"><?php echo Text::_('COM_MINIORANGE_ANDROID');?></h3>
									<hr>
									<ol class="mo_tfa_text">
										<li><?php echo Text::_('COM_MINIORANGE_ANDROID_STEP1');?></li>
										<li><?php echo Text::_('COM_MINIORANGE_SEARCH');?> <?php echo $appName[$name];?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR');?></li>
										<li><?php echo Text::_('COM_MINIORANGE_INSTALL');?></li>
									</ol>
									
								</td>
							</tr>
							</table>
						</details>
					</div>
					<div class="mo_boot_col-sm-12 mo_boot_mt-3  mo_boot_text-left">
						<details open>
							<summary><span class="mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_CONFIG_STEP2');?> <strong><?php echo $appName[$name] ;?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR_APP');?></strong> </span></summary>
						
							</details>
					</div>
					<div class="mo_boot_col-sm-12">
						<div class="mo_boot_row">
							<div class="mo_boot_col-sm-12 mo_boot_m-2">
								<?php echo '<img src="data:image/jpg;base64,' .$response['QR']. '" />'; ?>
							</div>
							<div class="mo_boot_col-sm-12  mo_boot_mt-4">
								<div class="googleauth-secret  mo_tfa_text" >
									<p><?php echo Text::_('COM_MINIORANGE_CONFIG_SECRET');?></p>
									<span class=" mo_boot_alert-secondary mo_tfa_padding"><strong><?php echo $response['code'];?></strong></span>
									<p>(<?php echo Text::_('COM_MINIORANGE_SPACES');?>)</p>
								</div>
							</div>
						</div>			
					</div> 
					<div class="mo_boot_col-sm-12 mo_boot_mt-3  mo_boot_text-left">
						<details open>
							<summary>
								<span class="mo_tfa_text"><?php echo Text::_('COM_MINIORANGE_CONFIG_STEP3');?> <strong><?php echo $appName[$name];?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR_APP');?></span>
							</summary>
						
						</details>
						<form name="f" id="step_two" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.pageFourValidatePasscode'); ?>">
							<div class="mo_boot_row text-sm-left">
								
								<div class="mo_boot_col-sm-12 mo_boot_mt-2">
									<input type="text" class="mo_tfa_input mo_boot_form-control mo_tfa_text mo_tfa_passcode" name="Passcode" placeholder="<?php echo Text::_('COM_MINIORANGE_ENTER_PASSCODE');?>" required="true" autofocus="true" />
								</div>
								<div class="mo_boot_col-sm-12 mo_boot_mt-3 mo_tfa_back_btns" >
									<input type="button" onclick="backToStep2()" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text mr-3"name="google_passcode_submit" value="<?php echo Text::_('COM_MINIORANGE_BACK');?>" />
				                    <input type="submit" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom mo_tfa_dark_site" name="google_passcode_submit" value="<?php echo Text::_('COM_MINIORANGE_VERIFY');?>"/>
								</div>
							</div>
						</form>
					</div>
					<?php
					}
					?>
				
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInlineFour'); ?>">			 
	</form>
	<form name="fback" method="post" id="navigateToBack1" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">
    </form>
	<script type="text/javascript">
		function backToStep2(){
			
			var count = "<?php echo "$count"?>";
			var method = "<?php echo "$methods"?>";
			if( count==1  && method!= 'ALL'){
				document.getElementById('navigateToBack1').submit();
			}
			else{
				document.getElementById('stepTwoBack').submit();
			}
		}
	</script>
	<?php
}

function moTfaChallangeForm($msg='',$msgType=''){ 
 	$session = Factory::getSession();
    $challangeInfo = $session->get('challenge_response');
	$userId = $session->get('current_user_id');
	$details = commonUtilitiesTfa::getMoTfaUserDetails($userId);
	$username = $details['username'];
	$tfaSettings = commonUtilitiesTfa::getMoTfaSettings();

	$active_tfa_methods = $tfaSettings['activeMethods'];
	$active_tfa_methods = json_decode($active_tfa_methods, TRUE);
	$count= count($active_tfa_methods);

	$appName=array(
		"google"=>"Google",
		"MA"=>"Microsoft",
		"AA"=>"Authy",
		"LPA"=>"LastPass",
		"DUO"=>"Duo",
	);
	$name = isset($details["active_method"]) ? $details["active_method"] : '';
    $enable_backup_method = isset($tfaSettings['enable_backup_method']) ? $tfaSettings['enable_backup_method'] : 0;
	$backup_method = isset($details['backup_method']) ? $details['backup_method'] : '';
    $session->set('juserId', $userId);
    
	$remember_device = isset($tfaSettings['remember_device']) && $tfaSettings['remember_device'] == 1;
	
	$enable_password_less_login=0;
	$enable_tfa_password_less_login = $tfaSettings['enable_tfa_passwordless_login'] ? $tfaSettings['enable_tfa_passwordless_login'] : 0;
	$isSystemEnabled = PluginHelper::isEnabled('system','miniorangepasswordlesslogin');
	if($isSystemEnabled)
	{
		require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_passwordlesslogin'. DIRECTORY_SEPARATOR .'helpers'. DIRECTORY_SEPARATOR . 'mo_passwordless_addon_utility.php';
		$passless_settings = MoPasswordLessAddonUtility::getCustomerDetails();
		
		$enable_password_less_login = $passless_settings['enable_password_less_login'] ? $passless_settings['enable_password_less_login'] : 0;
	}

  ?>
  
  <div class="container-fluid mo_boot_text-center mo_tfa_container mo_tfa_google_auth">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-4 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom ">
					<div class="mo_boot_col-sm-12 mo_tfa_title mo_tfa_border">
						<center> <span><?php echo Text::_('COM_MINIORANGE_AUTHENTICATE');?></span></center>
					</div>
					<div class="mo_boot_col-sm-12 mo_boot_mt-3">
						<form name="f" method="post" id="morba_loginform" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.validateTfaChallange'); ?>"> 
								<div class="mo_boot_row mo_boot_mb-3">
									<div class="mo_boot_col-sm-11 mo_boot_mx-4">
										<?php 
											if(!empty($msg))
											{
												if(!empty($challangeInfo->authType) && $msgType=='mo2f-message-status'){
													if($challangeInfo->authType=='GOOGLE AUTHENTICATOR')
													{
														?>
														<div class=" mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><span><?php echo Text::_('COM_MINIORANGE_AUTHENTICATE_PASSCODE');?> <strong><?php echo $appName[$name];?> <?php echo Text::_('COM_MINIORANGE_AUTHENTICATOR_APP');?> </span></div>
														<?php
													}
													elseif($challangeInfo->authType=='EMAIL')
													{
														?>
														<div class="  mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><span ><center><?php echo $msg; ?></center></span></div>
														<?php
													}
													elseif($challangeInfo->authType=='HARDWARE TOKEN')
													{
														?>
														<div class="  mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><span ><center><?php echo $msg; ?></center></span></div>
														<?php
													}
													else
													{
														?>
														<div class=" mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><span ><center><?php echo $msg; ?></center></span></div>
														<?php
													}
												}
												elseif($msgType=='mo2f-message-error')
												{
													?>
														<div class=" mo_boot_p-1 alert-danger mo_tfa_text"><span ><center><?php echo $msg; ?></center></span></div>
													<?php
												}
												else
												{
													?>
														<div class=" mo_boot_p-1  mo_boot_alert-secondary mo_tfa_text"><span ><center><?php echo $msg; ?></center></span></div>
													<?php
												}
											}
										
										?>
									</div>
								</div>
								<div class="mo_boot_row">
									
									<div class="mo_boot_col-sm-11 mo_boot_mx-4">
										<input type="text" class="input mo_boot_form-control mo_tfa_text" name="passcode" placeholder="<?php echo Text::_('COM_MINIORANGE_PASSCODE');?>" required/>
									</div>
								</div>
						
								<div class="mo_boot_row">
									<div class=" mo_boot_col-sm-6  mo_boot_text-left  mo_boot_mt-1  mo_boot_ml-4">
									<?php
									
								    if($remember_device  && $enable_password_less_login!='1' && $enable_tfa_password_less_login!=1 ){ ?>
										
											<input class=" mo_tfa_login_options" type="checkbox" name="remember_device" />&nbsp;<span class="mo_tfa_remeb_device"><?php echo Text::_('COM_MINIORANGE_REMEMBER_DEVICE');?></span><br><br>
										
									<?php } ?>
									</div>
									<br>

									<p><input type="hidden" id="miniorange_rba_attributes" name="miniorange_rba_attributes" value=""/></p>
									<?php

									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/ua-parser.js" ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/client.js " ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/device_attributes.js" ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/swfobject.js" ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/fontdetect.js" ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/murmurhash3.js" ></script>';
									echo '<script type="application/javascript" src="administrator\components\com_miniorange_twofa\assets\js\remember_me/js/miniorange-fp.js" ></script>';
									?>
									<input type="hidden" name="username" value="<?php echo $username ?>"/>
								</div>
								<div class="mo_boot_row">
									<div class="mo_boot_col-sm-5 mo_boot_mt-2  mo_boot_ml-4 ">
										<?php
										if(!empty($backup_method) && $backup_method!= 'none'){?>
										
											<a class="forgot_phn mo_tfa_forgot_phn" href="<?php echo Route::_('index.php?option=com_miniorange_twofa&task=miniorange_inline.handleForgotForm'); ?>" ><?php echo Text::_('COM_MINIORANGE_FORGOT_PHONE');?></a>
										<?php }
										?>
									</div>
                                	<div class="mo_boot_col-sm-6 mo_boot_mt-2 mo_boot_text-right">
										<input type="submit"  name="validate_passcode" class="mo_boot_btn mo_boot_btn-primary  mo_tfa_text mo_btn_custom mr-1 mo_tfa_dark_site"value="<?php echo Text::_('COM_MINIORANGE_VALIDATE');?>"  />
                                    	<input type="button" onclick="previousStep()" name="Start_registration" class="mo_boot_btn mo_boot_btn-secondary mo_tfa_text" value="<?php echo Text::_('COM_MINIORANGE_CANCEL');?>" />
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepTwoBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInlineFour'); ?>">			 
	</form>
	<form name="fback" method="post" id="stepOneBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">			
	</form>
    <form name="fphone" method="post" id="forgotPhoneForm" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleForgotForm'); ?>">
    </form>
	<form name="fback" method="post" id="ResendOtpForm" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.resendTfaChallanger'); ?>">			
	</form>
	<script type="text/javascript">
		function previousStep(){
			document.getElementById('stepOneBack').submit();
		}
		
		function submitForgotPhone(){
			document.getElementById('forgotPhoneForm').submit();
		}
		function submitResendOtp(){
			document.getElementById('ResendOtpForm').submit();
		}
	</script>
	<?php
}

function moTfaBackupForm($msg='',$msgType=''){
 	$session=Factory::getSession(); 
    $challangeInfo=$session->get('kba_response');
    if(!isset($challangeInfo) || is_null($challangeInfo)){
    	$challangeInfo=$session->get('challenge_response');
    	$session->set('kba_response',$challangeInfo);
    }
   
    $questions = $challangeInfo->questions;
  ?>
  <div class="container-fluid mo_boot_text-center mo_tfa_container mo_tfa_google_auth">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-5 mo_boot_offset-sm-4">
				<div class="mo_boot_row mo_tfa_main mo_tfa_custom">
					<div class="mo_boot_col-sm-12 mo_tfa_title mo_tfa_border">
						<center><?php echo Text::_('COM_MINIORANGE_AUTHENTICATE');?></center>
					</div>
					<?php
					if(!empty($msg))
					{
						Log::add(' motfa backup form error', Log::INFO, 'tfa');
						?>
						<div class="mo_boot_col-sm-12">
							<div class=" mo_boot_p-1 mo_tfa_text <?php echo($msgType=='mo2f-message-status') ? '  mo_boot_alert-secondary':' alert-danger mo_tfa_red' ?>"><center><?php echo $msg ?></center></div>
						</div>
						<?php
					}?>
					<div class="mo_boot_col-sm-12  mo_boot_mt-3">
						<form name="f" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.SubmitKBAForm'); ?>">
							
								<?php 
					  				$q_str='';	
						  			foreach($questions as $key=>$value)
									{
						  				$q_str=$q_str.'<div class="mo_boot_row mo_boot_mt-3  mo_boot_text-left"><div class="mo_boot_col-sm-6"><label class="mo_tfa_text">'.$questions[$key]->question.'</label></div>
										  	<div class="mo_boot_col-sm-6 ">
					  							<input type="text" class="input mo_boot_form-control mo_tfa_text" name="answer'.$key.'" placeholder="Your answer" required/>
						  					</div></div>';
						  			}			  			
						  			echo $q_str;
					  			?>
							
							<div class="mo_boot_row">
								<div class="mo_boot_col-sm-12 mo_boot_mt-3">
									<input type="submit"  name="validate_kba" class="mo_boot_btn mo_boot_btn-primary  mo_btn_custom mo_tfa_dark_site" value="<?php echo Text::_('COM_MINIORANGE_VALIDATE');?>"  />
									<input type="button" onclick="previousStep1()" name="Start_registration" class="mo_boot_btn mo_boot_btn-secondary" value="<?php echo Text::_('COM_MINIORANGE_CANCEL');?>"  />
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<form name="fback" method="post" id="stepOneBack" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&view=miniorange_twoFA&task=miniorange_inline.handleBackOfInline'); ?>">
	</form>
	<script type="text/javascript">
		function previousStep1(){
			document.getElementById('stepOneBack').submit();
		}
	</script>
	<?php
}

exit(); 