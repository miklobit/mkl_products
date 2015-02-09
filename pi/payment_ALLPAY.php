<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Milosz Klosowicz (typo3@miklobit.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * payment_ALLPAY.php
 *
 * This script handles payment via the polish payment gateway, ALLPAY.
 * Available channels: 
 * 0: Credit Card (Support: VISA,Master-Card,Dinners Club,JCB)
 * 1: mTransfer ( direct money order from MBank )
 * 212: PayPal
 * 
 *
 *
 * ALLPAY:	http://www.allpay.pl
 *
 * @author	Milosz Klosowicz <typo3@miklobit.com>
 */


if (!is_object($this) || !is_object($this->cObj))	die('$this and $this->cObj must be objects!');


// $lConf = $this->basketExtra["payment."]["handleScript."];		// Loads the handleScript TypoScript into $lConf.
$lConf = $conf;
// debug($lConf,"skrypt ALLPAY:lconf");//MKL
$localTemplateCode = $this->cObj->fileResource($lConf[templateFile] ? $lConf[templateFile] : "EXT:mkl_products/pi/payment_ALLPAY_template_en.tmpl");		// Fetches the ALLPAY template file
$localTemplateCode = $this->cObj->substituteMarkerArrayCached($localTemplateCode, $this->globalMarkerArray);

$orderUid = $this->getBlankOrderUid();		// Gets an order number, creates a new order if no order is associated with the current session

switch(t3lib_div::GPvar("status"))	{
	default:
		$errorMsg = '';	
		if ($lConf["returnURL"])	{	
			$tSubpart = "###ALLPAY_CARD_TEMPLATE###";
			$content=$this->getBasket($tSubpart,$localTemplateCode);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
			
			$markerArray=array();
			
			$markerArray["###ALLPAY_URL###"] = 'https://ssl.dotpay.pl';

			$errorMsg = '';
			$paymentCurrency = $this->currency ;
			if( ! in_array( $paymentCurrency, explode(",", $lConf["currency"] ) ) )   {
				$errorMsg = '<b style="color: red;">Error: currency '.$paymentCurrency.' not allowed in this payment channel !</b>';	
				$paymentCurrency = '';
			}	 
			$markerArray["###HIDDEN_FIELDS###"] = '                 
<input type=hidden name=id value="'.$lConf["merchant"].'">
<input type=hidden name=p_info value="'.$lConf["merchantName"].'">
<input type=hidden name=lang value="'.$this->langKey.'">
<input type=hidden name=as value="yes">
<input type=hidden name=kwota value="'.$this->getCurrencyAmount($this->calculatedSums_tax["total"]).'">
<input type=hidden name=waluta value="'.$paymentCurrency.'">		<!--PLN,USD,EUR-->
<input type=hidden name=opis value="'.$this->getOrderNumber($orderUid).'">		<!-- order number -->
<input type=hidden name=URL value="'.$lConf["returnURL"]."/".$this->getLinkUrl($this->conf["PIDbasket"],"","").'&products_finalize=1">
<input type=hidden name=type value="0">
<input type=hidden name=channel value="'.$lConf["channel"].'">
<input type=hidden name=ch_lock value="0">
<input type=hidden name=forename value="'.$this->personInfo["forename"].'">
<input type=hidden name=surname value="'.$this->personInfo["name"].'">
<input type=hidden name=street value="'.$this->personInfo["street"].'">
<input type=hidden name=street_n1 value="'.$this->personInfo["street_n1"].'">
<input type=hidden name=street_n2 value="'.$this->personInfo["street_n2"].'">
<input type=hidden name=city value="'.$this->personInfo["city"].'">
<input type=hidden name=postcode value="'.$this->personInfo["zip"].'">
<input type=hidden name=country value="'.$this->personInfo["country_code"].'">
<input type=hidden name=email value="'.$this->personInfo["email"].'">
<input type=hidden name=phone value="'.$this->personInfo["telephone"].'">
';

			  
			$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray);
		} else {
			$errorMsg = '<b style="color: red;">Error: NO .returnURL given!!</b>';
		}	
		if( $errorMsg != '')  {
			$content = $errorMsg ;
		}
	break;		
	case "FAIL":
	    // debug(t3lib_div::GPvar("status"),"status=");//MKL
		$markerArray=array();
		$content=$this->getBasket("###ALLPAY_DECLINE_TEMPLATE###",$localTemplateCode, $markerArray);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		$markerArray["###DECLINE_URL###"] = $this->getLinkUrl($this->conf["PIDbasket"],"","");
		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray);
	break;
	case "OK":
		$content=$this->getBasket("###ALLPAY_ACCEPT_TEMPLATE###",$localTemplateCode);		// This is just done to calculate stuff
		$content=$this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###","",$markerArray);
		$this->finalizeOrder($orderUid,$markerArray);	// Important: finalizeOrder MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
	break;
}
?>