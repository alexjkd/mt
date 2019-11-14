<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);
$to = 'melon.bao@outlook.com';
$subject = 'Price Changing Alert';
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=iso 8859-1" . "\r\n";
$headers .= 'From: 123filter' . "\r\n";
$message = "<html>
            <head>
            <title>HTML email</title>
            </head>
            <body>
            <img src='https://www.123filter.com/ac/resources/image/18/73/3.png' alt='Illegal seller' style='width:300px;' />
            <table>
            <tr>
            <td>
            <strong>This is a Illegal Seller</strong>
            </td>
            <td>Seller Name</td>
            </tr>
            </table>
            </body>
            </html>";
try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.live.com';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'cit1.ispring@hotmail.com';                     // SMTP username
    $mail->Password   = 'PwdCit1.20191011';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $mail->Port       = 587;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('cit1.ispring@hotmail.com', '123filter');
    $mail->addAddress($to, 'Joe User');     // Add a recipient

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/mt/model/GraphData.php?alerts=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    //var_dump($output);
    $data = json_decode($output, true);
    //var_dump($data);
    /*Initializing temp variable to design table dynamically*/
    $temp = "<table>";

    /*Defining table Column headers depending upon JSON records*/
    $temp .= "<tr><th>ASIN</th>";
    $temp .= "<th>SKU</th>";
    $temp .= "<th>Dtime</th>";
    $temp .= "<th>Percentage</th></tr>";

    /*Dynamically generating rows & columns*/
    $sendmail = false;
    for ($i = 0; $i < count($data); $i++) {
        if (abs($data[$i]["Percentage"] >= 20)) {
            $sendmail = true;
        }
        $temp .= "<tr>";
        $temp .= "<td align=\"center\">" . $data[$i]["ASIN"] . "</td>";
        $temp .= "<td align=\"center\">" . $data[$i]["SKU"] . "</td>";
        $temp .= "<td align=\"center\">" . $data[$i]["Dtime"] . "</td>";
        $temp .= "<td align=\"center\">" . $data[$i]["Percentage"] . "</td>";
        $temp .= "</tr>";
    }
    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $temp;
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    if ($sendmail) {
        $mail->send();
        echo 'Message has been sent';
    }
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
