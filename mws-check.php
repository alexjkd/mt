<?php
include_once(dirname(__FILE__) . "/lib/functions.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define("SEVEN_DAYS",604800);
// Load Composer's autoloader
require 'vendor/autoload.php';

// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);
$to = 'john@ispringfilter.com';
$cc = 'melon.bao@outlook.com';
$subject = 'Fetch Data Error Alert';
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
    $mail->Host       = 'smtpout.secureserver.net';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'cs6@ispringfilter.com';                     // SMTP username
    $mail->Password   = 'cs6cs620190905';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $mail->Port       = 587;                                    // TCP port to connect to

    //MWS Data Fetch <offermon@ispringfilter.com>
    //Recipients
    $mail->setFrom('offermon@ispringfilter.com', 'MWS Data Fetch');
    $mail->addAddress($to, 'John');     // Add a recipient
    $mail->addAddress($cc, 'Melon');  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/mt/model/GraphData.php?mws-check=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    //var_dump($output);
    $data = json_decode($output, true);
    if(empty($data)) {
        echo "no mws data is missed.";
        exit();
    }
    /*Initializing temp variable to design table dynamically*/
    $html_table = "<table>";

    /*Defining table Column headers depending upon JSON records*/
    $html_table .= "<tr><th>ASIN</th>";
    $html_table .= "<th>SKU</th>";
    $html_table .= "<th>Number of Records</th></tr>";

    /*Dynamically generating rows & columns*/
    for ($i = 0; $i < count($data); $i++) {
        $html_table .= "<tr>";
        $html_table .= "<td align=\"center\">" . $data[$i]["asin"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["sku_names"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["24hours-records"] . "</td>";
        $html_table .= "</tr>";
    }
    // Content
    $content = file_get_contents(dirname(__FILE__) .'/email-template.html');
    $content = str_replace('{{mail-content}}',$html_table, $content);
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $content;
    $mail->AltBody = 'MWS Data Mising Alert';
    $mail->send();
    
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
