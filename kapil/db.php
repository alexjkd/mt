<?php
$zip = new ZipArchive;
$res = $zip->open('MWSProducts.zip');
if ($res === TRUE) {
  $zip->extractTo('/mwsproducts/');
  $zip->close();
}
//database connection setting
$dbName = "mws";
$dbHost = "localhost";
$dbUser = "mws";
$dbPwd = "mws9lBl88G2uvVtcHw$";

/*
Seller account identifiers for iSpring Water Systems
Seller ID:	A2AB4EJKHRN74A
Marketplace ID:	ATVPDKIKX0DER (Amazon.com)
Developer id: 141965792485
AWS Access Key ID: AKIAIQ3AR7PLQUFLUD5A
client securet: ZkOWStjEmO6kMUM1wH+4yR7OzcJe2mLh5ex60AlI
*/
?>