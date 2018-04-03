<?php
/*
 *  Author:  daddy
 *  Company: pskovlift
 */
//$ipath=  \Yii::getAlias('@app');
// Get pararameters that are passed in through $_GET or set to the default value
include '/home/prog/www/nw/components/phpqrcode-master/qrlib.php';
$text = (isset($_GET["text"])?$_GET["text"]:"No text presented");
QRcode::png($text, null, 'M', 2);
?>
