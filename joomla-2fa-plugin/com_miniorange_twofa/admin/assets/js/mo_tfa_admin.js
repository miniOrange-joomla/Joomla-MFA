function mo_show_tab(tab_id)
{

    if(tab_id=='2fa_tab_1')
    {
        jQuery("#mo_navbar").show(); 
        jQuery(".mo_nav_tab_active ").removeClass("mo_nav_tab_active").removeClass("active");
        jQuery("#nav_item2").addClass("mo_nav_tab_active");

    }
    else
    {
        jQuery("#mo_navbar").hide();
    }
    jQuery(".mini_2fa_tab").css("background",'none');
    jQuery(".mini_2fa_tab").css("color",'white');
    jQuery(".mo_2fa_tab").css('display','none');
    jQuery("#"+tab_id).css('display','block');
    jQuery("#mo_"+tab_id).css("background",'white');
    jQuery("#mo_"+tab_id).css("color",'black');
    
}

jQuery(document).on('change', '#enable_2fa_backup_method', function enable_backup_change() {

  if (!jQuery("#enable_2fa_backup_method").is(':checked')) {
    jQuery("#setup_kba_questions").css("display", "none");

  } else {
    if (jQuery("#enable_2fa_backup_type").val() == 'securityQuestion') {
      jQuery("#setup_kba_questions").css("display", "block");
    } else {
      jQuery("#setup_kba_questions").css("display", "none");
    }
  }

});
jQuery(document).ready(function () {

  enable_tfa_change();
  var methods_checkbox = document.getElementsByClassName('methods_checkbox');
  var enable_2fa_users = document.getElementById('enable_2fa_users');
  var checkboxes = document.querySelectorAll('input.methods_checkbox');

  if (!jQuery("#enable_2fa_users").attr('disabled')) {
    if (jQuery("#enable_2fa_users").is(':checked')) {
      jQuery("#enable_2fa_user_type").removeAttr("disabled");
      jQuery("#select_methods").prop('disabled', 'false');
    } else {
      jQuery("#enable_2fa_user_type").attr("disabled", "true");
      jQuery("#select_methods").prop('disabled', 'true');
    }
  }
  if (!jQuery("#enable_2fa_backup_method").attr('disabled')) {
    if (jQuery("#enable_2fa_backup_method").is(':checked')) {
      jQuery("#enable_2fa_backup_type").removeAttr("disabled");
      if (jQuery("#enable_2fa_backup_type").val() == 'securityQuestion') {
        jQuery("#setup_kba_questions").css("display", "block");
      } else {
        jQuery("#setup_kba_questions").css("display", "none");
      }
    } else {
      jQuery("#enable_2fa_backup_type").attr("disabled", "true");
      if (jQuery("#enable_2fa_backup_type").val() == 'securityQuestion') {
        jQuery("#setup_kba_questions").css("display", "none");
      }
    }

  } else {
    jQuery("#setup_kba_questions").css("display", "none");
    jQuery("#enable_2fa_backup_type").attr("disabled", "true");
  }
});



function enable_tfa_change() {
  var checkboxes = document.querySelectorAll('input.methods_checkbox');

  if (jQuery("#enable_2fa_users").is(':checked')) {
    if (!jQuery("#enable_2fa_users").attr('disabled')) {
      jQuery("#enable_2fa_user_type").removeAttr("disabled");
    }

  } else {
    jQuery("#enable_2fa_user_type").attr("disabled", "true");
    jQuery("#select_methods").prop('disabled', 'true');
  }
}

function enable_backup_change() {
  if (jQuery("#enable_2fa_backup_method").is(':checked')) {
    jQuery("#enable_2fa_backup_type").removeAttr("disabled");
  } else {
    jQuery("#enable_2fa_backup_type").attr("disabled", "true");
  }
}

function show_kba_question() {
  if (jQuery("#enable_2fa_backup_type").val() == 'securityQuestion') {
    jQuery("#setup_kba_questions").css("display", "block");
  } else {
    jQuery("#setup_kba_questions").css("display", "none");
  }
}



var no_of_entry = "10";
jQuery(document).ready(function () {
  next_or_prev_page('next');
});

function getValue(num) {
  let text = "The user must go through the inline process if TFA is reset. Do you really wish to revert this user's TFA?";

  var value_email = jQuery("#reset_email" + num).val();
  var value_name = jQuery("#reset_username" + num).val();

  if (confirm(text) == true) {
    jQuery("#form_user" + num).submit();
  } else {
    return false;
  }

}

function refreshFilters() {
  location.reload();
}

function list_of_entry() {
  no_of_entry = jQuery("#select_number").val();
  // Hide pagination buttons initially
  document.getElementById('pagination_buttons').style.display = "none";
  next_or_prev_page('on');
}

function sort(button) {
  var order = "";
  if (button === 'up') {
      order = 'up';
  } else {
      order = 'down';
  }
  next_or_prev_page(button, order);
}

function searchFilter() {
  var name_flag = 0;
  var value_name = jQuery("#search_name").val().toLowerCase();
  var role_flag = 0;
  var value_role = jQuery("#search_role").val().toLowerCase();
  var status_flag = 0;
  var value_status = jQuery("#search_status").val().toLowerCase().trim();

  jQuery("#Tfa_table tbody tr").filter(function () {
    jQuery(this).toggle(
      (value_name === '' || jQuery(this).text().toLowerCase().indexOf(value_name) > -1) &&
      (value_role === 'any' || jQuery(this).text().toLowerCase().indexOf(value_role) > -1) &&
      (value_status === 'any' || jQuery(this).find('.mo_btn-status').text().toLowerCase().trim() === value_status)
    );
    document.getElementById("tfa_entries").style.display = "none";
  });
}


function resetFilters() {
  document.querySelector('input[name="search_name"]').value = '';
  document.querySelector('select[name="search_role"]').value = 'any';
  document.querySelector('select[name="search_status"]').value = 'any';

  var value_name = jQuery("#search_name").val().toLowerCase();
  var value_role = jQuery("#search_role").val().toLowerCase();
  var value_status = jQuery("#search_status").val().toLowerCase();

  $("#Tfa_table tbody tr").filter(function () {

    $(this).toggle(
      (value_name == '' || $(this).text().toLowerCase().indexOf(value_name) > -1) &&
      (value_role == 'any' || $(this).text().toLowerCase().indexOf(value_role) > -1) &&
      (value_status == 'any' || $(this).text().toLowerCase().indexOf(value_status) > -1))

  });
  document.getElementById("tfa_entries").style.display = "block";
}

//POP UP Tab

jQuery(document).change(function(){
  CustomCss();
});

jQuery(document).ready(function(){
CustomCss();
});

function CustomCss(){
var radius=jQuery('#radius').val();
var margin = jQuery('#margin').val();
var bgcolor = jQuery('#bgcolor').val();
var bordertop = jQuery('#bordertop').val();
var borderbottom = jQuery('#borderbottom').val();
var primarybtn = jQuery('#primarybtn').val();
var height = jQuery('#height').val();
var customcss="";
var custombtn="";

custombtn +="background-color:"+primarybtn;
customcss += "border-radius:"+radius+"px;background-color:"+bgcolor+";border-top:"+margin+"px solid "+bordertop+";border-bottom:"+margin+"px "+"solid "+bordertop+";min-height:"+height+"px;"+"width:90% !important;";


jQuery("#previewform").attr("style",customcss);
jQuery("#previewbutton").attr("style",custombtn);
jQuery("#previewbutton1").attr("style",custombtn);
}
jQuery(document).ready(function(){
jQuery('#css_reset').click(function(){
document.getElementById("margin").value = 5;
document.getElementById("radius").value = 8;
document.getElementById("bgcolor").value = "#FFFFFF";
document.getElementById("bordertop").value = "#20b2aa";
document.getElementById("primarybtn").value = "#2384d3";
document.getElementById("height").value = 200;
jQuery("#previewCSS").submit();
});
});

//Support Tab

jQuery(document).ready(function(){
  var dtToday = new Date();
  var month = dtToday.getMonth() + 1;
  var day = dtToday.getDate();
  var year = dtToday.getFullYear();
  if(month < 10)
      month = '0' + month.toString();
  if(day < 10)
      day = '0' + day.toString();
  var maxDate = year + '-' + month + '-' + day;

  jQuery('#setTodaysDate').attr('min', maxDate);
  }
);

function show_setup_call() {
  jQuery("#support_form").hide();
  jQuery("#request_quote_form").hide();
  jQuery("#setup_call_form").show();
}
function hide_setup_call() {
  jQuery("#support_form").show();
  jQuery("#setup_call_form").hide();
  jQuery("#request_quote_form").hide();
}
function show_quote(){
  jQuery("#support_form").hide();
  jQuery("#setup_call_form").hide();
  jQuery("#request_quote_form").show();
}
jQuery('#type_service').change(function(){
  jQuery('#select_type_country').css('display','none');
  if(jQuery(this).val()==="SMS")
  {
      jQuery('#type_country').css('display','');
      jQuery('#no_of_otp').css('display','');
  }
 else if(jQuery(this).val()==="Email")
  {
      jQuery('#type_country').css('display','none');
      jQuery('#no_of_otp').css('display','');
  }
  else if(jQuery(this).val()==="OOSE")
  {
      jQuery('#type_country').css('display','');
      jQuery('#no_of_otp').css('display','');
  }
  else {
      jQuery('#type_country').css('display','none');
      jQuery('#no_of_otp').css('display','none');
      jQuery('#singlecountry').prop('selected',false);
   }

});
jQuery('select').change(function(){
  if(jQuery(this).val()==="singlecountry")
  {
      jQuery('#select_type_country').css('display','');
  }

});

//Advance Setting Tab
jQuery(document).ready(function (){
  enable_ip_whitelist_field();
});
function enable_ip_whitelist_field() {
  var enable_ip_whitelist = document.getElementById('enable_ip_whitelist');
  var ip_whitelist_field = document.getElementsByClassName('ip_whitelist_field')[0];
  ip_whitelist_field.disabled = enable_ip_whitelist.checked !== true;
}

function displayFileName() {
  var fileInput = document.getElementById('fileInput');
  var file = fileInput.files[0];

  if (file && file.name.endsWith('.json')) {
      document.getElementById('fileName').textContent = file.name; 
  } else {
      document.getElementById('fileName').textContent = "Please select a .json file.";
  }
}
jQuery(document).ready(function () {
  toggleTfaDomainField();
});


function toggleTfaDomainField() {
  var enable_tfa_domain = document.getElementById('enable_tfa_domain');
  var domain_field = document.querySelector('.domain_field'); 
  if (domain_field) {
      domain_field.disabled = !enable_tfa_domain.checked;
  }
}

function toggleSmtpFields(value) {
  var smtpFields = document.getElementById('smtp_fields');
  smtpFields.style.display = 'block';
}

function toggleEmailFields() {
  var emailEnabled = document.getElementById('enable_email_functionality').checked;
  var emailRecipients = document.getElementById('email_recipients');
  var emailMethod = document.getElementById('email_method');

  emailRecipients.disabled = !emailEnabled;
  emailMethod.disabled = !emailEnabled;

  if (!emailEnabled) {
      emailRecipients.value = 'both'; 
      emailMethod.value = 'smtp'; 
  }

  toggleSmtpFields(); 
}


function toggleCheckboxes(checkbox, enableId, disableId) {
  const enableCheckbox = document.getElementById(enableId);
  const disableCheckbox = document.getElementById(disableId);
  const otpMessageDiv = document.getElementById("enqueueMessage1");
  const skip2faMessageDiv = document.getElementById("enqueueMessage2");

  if (checkbox.checked) {
      disableCheckbox.checked = false;  
      displayMessage(enableId, 'enabled');
  }

  otpMessageDiv.style.display = (enableId === 'enable_otp_login' && checkbox.checked) ? "block" : "none";
  skip2faMessageDiv.style.display = (enableId === 'skip_tfa_for_users' && checkbox.checked) ? "block" : "none";
}

function displayMessage(checkboxId, action) {
  const messages = {
      'enable_otp_login': document.getElementById("enable_otp_login_message").value,
      'skip_tfa_for_users': document.getElementById("skip_tfa_for_users_message").value
  };

  const messageDivs = {
      'enable_otp_login': document.getElementById("enqueueMessage1"),
      'skip_tfa_for_users': document.getElementById("enqueueMessage2")
  };

  Object.values(messageDivs).forEach(div => {
      div.innerHTML = '';
      div.style.display = "none";
  });

  if (action === 'enabled' && messages[checkboxId]) {
      messageDivs[checkboxId].innerHTML = messages[checkboxId];
      messageDivs[checkboxId].style.display = "block";
  }
}


document.addEventListener("DOMContentLoaded", function() {
    var input = document.querySelector("#mo_country_code");
    var iti = window.intlTelInput(input, {
        initialCountry: "auto",
        separateDialCode: true,
        preferredCountries: ["us", "gb", "in"], // Adjust as needed
        geoIpLookup: function(callback) {
            fetch("https://ipapi.co/json")
                .then(response => response.json())
                .then(data => callback(data.country_code.toLowerCase()))
                .catch(() => callback("us"));
        }
    });

    // Ensure selected country code is stored properly
    input.addEventListener("countrychange", function() {
        var countryData = iti.getSelectedCountryData();
        input.value = countryData.dialCode + "," + countryData.name;
    });
});


jQuery(document).ready(function (){
  next_or_prev_page_otp('next');
});

function list_of_entry_Otp(){
  no_of_entry=jQuery("#select_number").val();
  next_or_prev_page_otp('on');
}
function sort(button){
  var order ="";
  if(clock)
  {
      clock = 0;
      order = 'up';
  }
  else
  {
      clock = 1;
      order = 'down';
  }
  next_or_prev_page_otp(button,order);
}
