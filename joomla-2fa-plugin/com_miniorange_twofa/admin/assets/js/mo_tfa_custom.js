document.addEventListener('DOMContentLoaded', function(){
    let password         = document.getElementById("mo_tfa_login_pass");
    let confirm_password = document.getElementById("mo_tfa_login_pass_confirm");
    console.log(password);
    console.log(confirm_password);
    if(password!=null && confirm_password!=null){
        password.addEventListener('keyup',validatePassword);
        password.addEventListener('input',validatePassword);
        confirm_password.addEventListener('keyup',validatePassword);
        confirm_password.addEventListener('input',validatePassword);
    }

    function validatePassword() {
            let indicator = document.getElementById("mo_tfa_login_pass_matcher");

            if(password.value==confirm_password.value){
                indicator.innerHTML="Password Match:<code style='color:green;'><strong>&#10004;</strong></code>";

            }
            else{
                indicator.innerHTML="Password Match:<code style='color:red;'><strong>&#10006;</strong></code>";
            }

    }
});

function show_tab_demo(id)
{
    jQuery(element).addClass("mo_nav_tab_active");  
} 

