<?php
$Url = ObtenerVariable('URLCrystalReportAPI') . 'FormatosSAPB1';
//echo $Url."<br>";
$Client = new SoapClient($Url, array('trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => 0));
//$functions = $Client->__getFunctions ();
//print_r($functions);
?>