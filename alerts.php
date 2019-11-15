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
$to = 'offermon@ispringfilter.com';
//$to = 'melon.bao@outlook.com';
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
    $mail->Host       = 'smtpout.secureserver.net';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'cs6@ispringfilter.com';                     // SMTP username
    $mail->Password   = 'cs6cs620190905';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $mail->Port       = 587;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('offermon@ispringfilter.com', '123filter');
    $mail->addAddress($to, 'Joe User');     // Add a recipient

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/mt/model/GraphData.php?alerts=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    //var_dump($output);
    $data = json_decode($output, true);
    /*Initializing temp variable to design table dynamically*/
    $html_table = "<table>";

    /*Defining table Column headers depending upon JSON records*/
    $html_table .= "<tr><th>ASIN</th>";
    $html_table .= "<th>SKU</th>";
    $html_table .= "<th>Dtime</th>";
    $html_table .= "<th>Percentage</th></tr>";

    /*Dynamically generating rows & columns*/
    $sendmail = false;
    for ($i = 0; $i < count($data); $i++) {
        if (abs($data[$i]["Percentage"] >= 20)) {
            $sendmail = true;
        }
        $html_table .= "<tr>";
        $html_table .= "<td align=\"center\">" . $data[$i]["ASIN"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["SKU"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Dtime"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Percentage"] . "</td>";
        $html_table .= "</tr>";
    }
    // Content
    $content = file_get_contents(dirname(__FILE__) .'/email-template.html');
    $content = str_replace('{{mail-content}}',$html_table, $content);
    $md5sum = md5($html_table);
    $sql = sprintf("select md5 from `mail-content` where md5='" . $md5sum . "' and updated between %d and %d",time()-SEVEN_DAYS,time());
    //var_dump($sql);
    $records = sqlquery($sql);
    //$sendmail = true;
   // var_dump($records);
    if(!empty($records) && count($records)){
        echo 'This mail has been sent within 7 days';
    } else if($sendmail == false) {
        echo 'No Price changing up to 20% ';
    }else {
        $insert = sprintf("insert into `mail-content` (md5,updated) ". "values('$md5sum', %ld)",time());
        sqlquery($insert);
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $content;
        $mail->AltBody = 'Price Changing Alert';
        $mail->send();
    }
    
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
