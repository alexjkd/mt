<?php
$to = 'kapilkasera0801@gmail.com';
$subject = 'Illegal Seller Alert';
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
if(mail($to,$subject,$message,$headers)){
    echo "Sent";
}else{
    // echo "Not Sent";
}
?>