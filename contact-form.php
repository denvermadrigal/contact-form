<?php
/*
Plugin Name: Contact Form
Description: A custom form integrated with PHPMailer and Re-Captcha
Version:  1.0
Author: Denver Madrigal
Author URI: mailto:denvermadrigal@gmail.com
License: n/a
*/

if(!defined('ABSPATH')) return;

define('CF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF_PLUGIN_URI', dirname(__FILE__));
// replace below with reCAPTCHA v3 keys
define('CF_RECAPTCHA_SITE_KEY', '6Le34TEhAAAAAPbGjV7hdXz4oJwM8ObdqoSYnboz');
define('CF_RECAPTCHA_SECRET_KEY', '6Le34TEhAAAAAPwVnJryNFuMFlv1iC82lTCHOxcO');

// import required css & js
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
function enqueue_custom_scripts(){
    wp_register_style('cf-css', CF_PLUGIN_URL.'style.css');
    wp_enqueue_style('cf-css');

    wp_register_script('jquery-lib', 'https://code.jquery.com/jquery-3.6.0.min.js');
    wp_enqueue_script('jquery-lib');

    wp_register_script('cf-js', CF_PLUGIN_URL.'script.js');
    wp_enqueue_script('cf-js');
}

// i did this because the external js link was not being imported
add_action('wp_head', 'insert_recaptcha_api');
function insert_recaptcha_api(){
    ?>
    <script type="text/javascript">var cf_recaptcha_site_key = '<?php echo CF_RECAPTCHA_SITE_KEY; ?>';</script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo CF_RECAPTCHA_SITE_KEY; ?>"></script>
    <?php
}

// [cf_display_form]
add_shortcode('cf_display_form', 'show_contact_form');
function show_contact_form($atts){
    ob_start();
    ?>
    <div id="cf-result">
        <?php
        $result = (isset($_GET['result']))?$_GET['result']:'';
        echo ($result == 'success')?'Thank you for your message. It has been sent.':'';
        echo ($result == 'error')?'There was an error trying to send your message. Please try again later.':'';
        ?>
    </div>
    <form id="cf-renewal" action="" method="POST">
        <ul class="form-multi-columns">
            <li>
                <input type="text" name="cf_first-name" id="cf_first-name" placeholder="First Name" />
            </li>
            <li>
                <input type="text" name="cf_middle-name" id="cf_middle-name" placeholder="Middle Name" />
            </li>
            <li>
                <input type="text" name="cf_last-name" id="cf_last-name" placeholder="Last Name" />
            </li>
        </ul>
        <ul class="form-two-columns">
            <li>
                <input type="email" name="cf_email" id="cf_email" placeholder="Email Address" />
            </li>
            <li>
                <input type="email" name="cf_confirm-email" id="cf_confirm-email" placeholder="Confirm Email Address" />
            </li>
        </ul>
        <ul class="form-two-columns">
            <li>
                <input type="text" name="cf_policy-number" id="cf_policy-number" placeholder="Policy Number" />
            </li>
            <li>
                <input type="text" name="cf_phone-number" id="cf_phone-number" placeholder="Phone Number" />
            </li>
        </ul>
        <input type="submit" name="cf-submit" id="cf-submit" value="SUBMIT" />
        <input type="hidden" name="cf-process" value="cf-submit" />
    </form>
    <?php
    return ob_get_clean();
}

if(isset($_POST['cf-process']) && $_POST['cf-process'] == 'cf-submit'){
    require_once CF_PLUGIN_URI.'/recaptcha/src/autoload.php';
    
    $token = (isset($_POST['token']))?$_POST['token']:'';
    $action = (isset($_POST['action']))?$_POST['action']:'send_email';
    
    $recaptcha = new \ReCaptcha\ReCaptcha(CF_RECAPTCHA_SECRET_KEY);
    $resp = $recaptcha->setExpectedAction($action)
                ->setScoreThreshold(0.5)
                ->verify($token, $_SERVER['REMOTE_ADDR']);

    // verify the response
    if ($resp->isSuccess()) {
        if(isset($_POST['cf_email']) && $_POST['cf_email']) {
            // create post and send mails
            require_once 'send-email.php';
        } else {
            header('location: ?result=error&s=2');
            exit;
        }
    } else {
        /*
        $errors = $resp->getErrorCodes();
        echo '<pre>';
        print_r($errors);
        echo '</pre>';
        */
        header('location: ?result=error&t=rc');
        exit;
    }
}