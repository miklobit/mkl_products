<?php
/*

products_shipping_calcScript.inc



TypoScript properties example:

plugin.tt_products.shipping.20.calculationScript = EXT:mkl_products/pi/products_shipping_calcScript.inc

*/


$calcShipping_tax = 0;
$calcShipping_no_tax = 0;
$destCountry = $this->deliveryInfo["country_code"];
if( $destCountry == "" ) {
   $destCountry = $this->personInfo["country_code"];
}
// get post region from destination country

$postRegion = "L" ;  // by default local shipping
if( ($destCountry != "") && ($destCountry != $this->conf["countryCode"]) )    {
	$whereString =  'cn_iso_3 = "'.$destCountry.'"';
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','static_countries', $whereString);
	if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
		$postRegion = $row['cn_post_region'];
	}
	else {
		$postRegion = "D";   // if country not found, set max shipping cost  
	}
}

// get shipping cost ( with tax ) from config table 

$whereString =  'weight_limit >= '.$this->calculatedSums_weight;
$orderString =  'weight_limit';
$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_products_post_rates', $whereString, '', $orderString, '1');
if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
	switch(trim($postRegion)) {
		case "L": $calcShipping_tax = doubleval( $row["local_rate"] ) ; break ;	
		case "A1": $calcShipping_tax = doubleval( $row["a1_rate"] ) ; break ;		
		case "A2": $calcShipping_tax = doubleval( $row["a2_rate"] ) ; break ;	
		case "A3": $calcShipping_tax = doubleval( $row["a3_rate"] ); break ;	
		case "A4": $calcShipping_tax = doubleval( $row["a4_rate"] ); break ;	
		case "A5": $calcShipping_tax = doubleval( $row["a5_rate"] ); break ;	
		case "B": $calcShipping_tax = doubleval( $row["b_rate"] ); break ;	
		case "C": $calcShipping_tax = doubleval( $row["c_rate"] ); break ;	
		case "D": $calcShipping_tax = doubleval( $row["d_rate"] ) ; break ;																
	}
//debug($row);
}
$calcShipping_no_tax = $this->getPrice($calcShipping_tax,0);
$this->calculatedSums_tax["shipping"] = $calcShipping_tax ;
$this->calculatedSums_no_tax["shipping"] = $calcShipping_no_tax ;

//debug($destCountry,"destination country" );
//debug($postRegion,"postRegion");
//debug($calcShipping_tax,"shipping_tax");
//debug($calcShipping_no_tax,"shipping_no_tax");
//debug($this->calculatedSums_tax);
//debug($this->calculatedSums_no_tax);
//debug($this->calculatedSums_weight);

?>