<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'alex.ngigi@strathmore.edu';
    $mail->Password = 'your_app_password_here'; // Use a Gmail app password!
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('alex.ngigi@strathmore.edu', 'Alex Test');
    $mail->addAddress('alex.ngigi@strathmore.edu');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = '✅ PHPMailer test message sent successfully.';

    $mail->send();
    echo "Message sent successfully.";
} catch (Exception $e) {
    echo "Message could not be sent. Error: {$mail->ErrorInfo}";
}
?>
