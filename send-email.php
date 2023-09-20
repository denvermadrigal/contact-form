<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require CF_PLUGIN_URI.'/phpmailer/src/Exception.php';
require CF_PLUGIN_URI.'/phpmailer/src/SMTP.php';
require CF_PLUGIN_URI.'/phpmailer/src/PHPMailer.php';

// stmp / mail settings
define('CF_SMTP_HOST', '');
define('CF_SMTP_PORT', 465);
define('CF_SENDER_EMAIL', '');
define('CF_SENDER_PASSWORD', '');
define('CF_ADMIN_EMAIL', '');

$email = filter_var($_POST['cf_email'], FILTER_SANITIZE_STRING);
$first_name = $_POST['cf_first-name'];
$middle_name = $_POST['cf_middle-name'];
$last_name = $_POST['cf_last-name'];
$policy_number = $_POST['cf_policy-number'];
$phone_number = $_POST['cf_phone-number'];

try{
    // create post & update fields
    global $wpdb;
    $post_title = $post_title = '#'.$policy_number.' - '.$first_name.' '.$middle_name[0].'. '.$last_name;
    $new_slug = 'irenewals_'.str_replace(array(' ', '-', ':'), '', current_time('mysql'));
    $insert_sql = '
    INSERT INTO `wp_posts` (
            `post_title`,
            `post_status`,
            `post_type`,
            `post_content`,
            `post_author`,
            `post_name`
        ) VALUES (
            %s,%s,%s,%s,%d,%s
        );
    ';

    $query = $wpdb->prepare($insert_sql, $post_title, 'publish', 'insurance_renewals', '', 1, $new_slug);
    $wpdb->query($query);
    $new_renewal_id = $wpdb->insert_id;

    if($new_renewal_id){
        // update the custom fields of the new renewal post
        update_post_meta($new_renewal_id, 'first_name', $first_name);
        update_post_meta($new_renewal_id, 'middle_name', $middle_name);
        update_post_meta($new_renewal_id, 'last_name', $last_name);
        update_post_meta($new_renewal_id, 'email', $email);
        update_post_meta($new_renewal_id, 'policy_number', $policy_number);
        update_post_meta($new_renewal_id, 'phone_number', $phone_number);

        // send email notifications
        $mail_admin = new PHPMailer(true);
        $mail_sender = new PHPMailer(true);

        // set smtp config
        //$mail_admin->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail_admin->isSMTP();
        $mail_admin->Host = CF_SMTP_HOST;
        $mail_admin->SMTPAuth = true;
        $mail_admin->Username = CF_SENDER_EMAIL;
        $mail_admin->Password = CF_SENDER_PASSWORD;
        $mail_admin->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail_admin->Port = CF_SMTP_PORT;

        //$mail_sender->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail_sender->isSMTP();
        $mail_sender->Host = CF_SMTP_HOST;
        $mail_sender->SMTPAuth = true;
        $mail_sender->Username = CF_SENDER_EMAIL;
        $mail_sender->Password = CF_SENDER_PASSWORD;
        $mail_sender->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail_sender->Port = CF_SMTP_PORT;

        // mail to admin
        $mail_admin->setFrom(CF_SENDER_EMAIL, 'Do Not Reply');
        $mail_admin->addAddress(CF_ADMIN_EMAIL);// admin's email

        $mail_admin->isHTML(true);
        $mail_admin->Subject = 'Insurance Policy Renewal Form';
        $mail_admin->Body = '
        <p>A new insurance policy renewal request has been received, please check details below:</p>
        <p>
        Name: '.$first_name.' '.$middle_name.' '.$last_name.'<br />
        Email : '.$email.'<br />
        Policy Number: '.$policy_number.'<br />
        Phone Number: '.$phone_number.'
        </p>
        ';
        $mail_admin->send();

        // mail to sender
        $mail_sender->setFrom(CF_SENDER_EMAIL, 'Do Not Reply');
        $mail_sender->addAddress($email, $first_name.' '.$middle_name.' '.$last_name);

        $mail_sender->isHTML(true);
        $mail_sender->Subject = 'Insurance Policy Renewal Form';
        $mail_sender->Body = '
        <p>Thank you for submitting an insurance policy renewal request. Below are the details you\'ve sent us.</p>
        <p>
        Name: '.$first_name.' '.$middle_name.' '.$last_name.'<br />
        Email : '.$email.'<br />
        Policy Number: '.$policy_number.'<br />
        Phone Number: '.$phone_number.'
        </p>
        <p>-- This is a system generated email, please do not reply.</p>
        ';
        $mail_sender->send();

        header('location: ?result=success');
        exit;
    }else{
        header('location: ?result=failed');
        exit;
    }
}catch(Exception $e){
    header('location: ?result=failed');
    exit;
}