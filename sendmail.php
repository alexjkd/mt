<?php
//~ include "lib/functions.php";
$usage = "sendmail.php?file=FILEPATH&subject=SUBJECT&to=TO&from=FROM";
if (isset($_GET['subject']) == 0) {echo $usage; exit -1;}
if (isset($_GET['to']) == 0) {echo $usage; exit -1;}
if (isset($_GET['from']) == 0) {echo $usage; exit -1;}
foreach (explode(',',"file,subject,to,from") as $v) {
	$$v = $_GET[$v];
}
if (isset($_GET['file'])== 0) {$file='sendmail_msg.html';} else {
	$file = $_GET['file'];
	foreach(glob("*$file") as $f) {
		echo $f ."<br>";
		$body = '<table border=1>'. file_get_contents($f) .'</table>';
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: '. $from . "\r\n";
		if(mail($to,$subject,$body,$headers)) {
			echo "\n<li>Email sent: $subject\r";
			file_put_contents('sendmail.log',"\nfile=$file,subject=$subject,to=$to,from=$from<br>\r");
			unlink($f);
		}
	}
}
?>