(function($){
    $(document).ready(function(){
        $('#cf-renewal').submit(function(e) {
            e.preventDefault();
            var err_fields = 0;
            var err_email = 0;
            
            // simple validations for input fields
            if($('#cf_first-name').val() == '') err_fields++;
            if($('#cf_middle-name').val() == '') err_fields++;
            if($('#cf_last-name').val() == '') err_fields++;
            if($('#cf_policy-number').val() == '') err_fields++;
            if($('#cf_phone-number').val() == '') err_fields++;
            
            if($('#cf_email').val() == '' || $('#cf_confirm-email').val() == ''){
                err_email++;
            }else{
                if($('#cf_email').val() != $('#cf_confirm-email').val()){
                    err_email++;
                }
            }

            var pass = 0;
            var html_msg = '';

            if(err_fields > 0){
                html_msg = '<div class="error">Please fill out all fields.</div>';
                pass++;
            }

            if(err_email > 0){
                html_msg+= '<div class="error">Email address doesn\'t match or is not valid.</div>';
                pass++;
            }

            if(html_msg != ''){
                $('#cf-result').html(html_msg);
            }

            if(pass < 1){
                grecaptcha.ready(function() {
                    grecaptcha.execute(cf_recaptcha_site_key, {action: 'send_email'}).then(function(token) {
                        $('#cf-renewal').prepend('<input type="hidden" name="token" value="' + token + '">');
                        $('#cf-renewal').unbind('submit').submit();
                    });;
                });
            }
        });
    });
})(jQuery);