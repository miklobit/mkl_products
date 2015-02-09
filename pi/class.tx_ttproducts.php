<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2003 Kasper Sk�rh�j (kasper@typo3.com)
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
 * productsLib.inc
 *
 * Creates a list of products for the shopping basket in Typo3.
 * Also controls basket, searching and payment.
 *
 * TypoScript config:
 * - See static_template "plugin.tt_products"
 * - See TS_ref.pdf
 *
 * @author	Kasper Sk�rh�j <kasper@typo3.com>
 * @coauthor Ren� Fritz <r.fritz@colorcube.de>
 * @coauthor Milosz Klosowicz <typo3@miklobit.com>
 */


/**
 * changes:
 *
 * 12.9.2001
 *
 * added 'page browsing': <- 1 2 3 ->
 * - see ###LINK_BROWSE### and ###BROWSE_LINKS###
 * added ###ITEMS_SELECT_COUNT### for displaying the amount of the current available items (in this category or search)
 *
 * 13.9.2001 Ren� Fritz
 *
 * added range check for $begin_at
 *
 * 14.9.2001 Ren� Fritz
 * bugfix: with adding of page browsing 'orderby' was damaged
 *
 * 19.9.2001 Ren� Fritz
 * changed counting select to 'select count(*)'
 *
 * 20.9.2001 Ren� Fritz
 * new TS value 'stdSearchFieldExt' extends the search fields. Example: 'stdSearchFieldExt = note2,year'
 *
 * Milosz Klosowicz
 * 05.09.2004 added multilanguage product and category record, static url, sortable products
 * 22.09.2004  added marker for detailed adress fields, TS value 'order_htmlcharset' for htmlmail
 * 22.02.2005  multicurrency, VAT for transaction inside EU
 * 04.09.2005  bugfix: default country was always shown in country selector
 * 15.12.2011  changes for  upgrade to typo3 4.5 
 * 18.01.2013  delete obsolete mysql calls
 */
                 

require_once(PATH_tslib."class.tslib_pibase.php");
require_once(PATH_t3lib."class.t3lib_parsehtml.php");
require_once(t3lib_extMgm::extPath('sr_static_info').'pi1/class.tx_srstaticinfo_pi1.php');
require_once(t3lib_extMgm::extPath('mkl_products').'pi/products_mail.inc');
require_once(t3lib_extMgm::extPath('mkl_currxrate').'pi1/class.tx_mklcurrxrate_pi1.php');

class tx_ttproducts extends tslib_pibase {
	var $cObj;		// The backReference to the mother cObj object set at call time

	var $searchFieldList="title,note,itemnumber";

		// Internal
	var $pid_list="";
	var $uid_list="";					// List of existing uid's from the basket, set by initBasket()
	var $categories=array();			// Is initialized with the categories of the shopping system
	var $pageArray=array();				// Is initialized with an array of the pages in the pid-list
	var $orderRecord = array();			// Will hold the order record if fetched.


		// Internal: init():

//	var $cObj = "";
	var $templateCode="";				// In init(), set to the content of the templateFile. Used by default in getBasket()

		// Internal: initBasket():
	var $basket=array();				// initBasket() sets this array based on the registered items
	var $basketExtra;					// initBasket() uses this for additional information like the current payment/shipping methods
	var $recs = Array(); 				// in initBasket this is set to the recs-array of fe_user.
	var $personInfo;					// Set by initBasket to the billing address
	var $deliveryInfo; 					// Set by initBasket to the delivery address

		// Internal: Arrays from getBasket() function
	var $calculatedBasket;				// - The basked elements, how many (quantity, count) and the price and total
	var $calculatedSums_tax;			// - Sums of goods, shipping, payment and total amount WITH TAX included ( in base currency )
	var $calculatedSums_no_tax;			// - Sums of goods, shipping, payment and total amount WITHOUT TAX
	var $calculatedSums_weight;			// - Sums of total weight of all goods	

	var $config=array();
	var $conf=array();
	var $tt_product_single="";
	var $globalMarkerArray=array();
	var $externalCObject="";

       // mkl - multilanguage support
    var $language = 0;
    var $langKey;
       // mkl - multicurrency support
	var $currency = "";					// currency iso code for selected currency
	var $baseCurrency = "";				// currency iso code for default shop currency
	var $allowedCurrency = "" ;         // list of allowed curency iso codes
	var $xrate = 1.0;						// currency exchange rate (currency/baseCurrency)

	var	$vatIncluded = 1;

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_products($content,$conf)	{
		$GLOBALS["TSFE"]->set_no_cache();


		// *************************************
		// *** getting configuration values:
		// *************************************

//debug( $GLOBALS["HTTP_GET_VARS"],"HTTP_GET_VARS");
//debug( $GLOBALS["HTTP_POST_VARS"],"HTTP_POST_VARS");

        // mkl - multilanguage support
        $this->language = $GLOBALS["TSFE"]->sys_language_uid;
        $this->langKey = $GLOBALS["TSFE"]->tmpl->setup["config."]["language"];
// debug($this->language,"language");//MKL
// debug($this->langKey,"langkey");//MKL

		// mkl - multicurrency support
		$this->baseCurrency = $GLOBALS["TSFE"]->tmpl->setup["plugin."]["tx_mklcurrxrate_pi1."]["currencyCode"];
		$this->currency = t3lib_div::GPvar("C") ? 	t3lib_div::GPvar("C") : $this->baseCurrency;
		
		// mkl - Initialise static info library
		$this->staticInfo = t3lib_div::makeInstance('tx_srstaticinfo_pi1');
		$this->staticInfo->init();

			// getting configuration values:
		$this->conf=$conf;
// debug($this->conf,"this->conf");//MKL
		// Converting flexform data into array:
		$this->pi_initPIflexForm();

		$this->allowedCurrency = $this->conf["allowedCurrency"]	? $this->conf["allowedCurrency"] : $this->baseCurrency ;	
		// check if selected currency is allowed, if not change to first allowed currency
		$allowedCurr = explode(",",$this->allowedCurrency );
		if (! in_array($this->currency, $allowedCurr)) {
    		$this->currency = $allowedCurr[0] ;
		}
// debug($this->currency,"currency");//MKL

		// mkl - Initialise exchange rate library and get

		$this->exchangeRate = t3lib_div::makeInstance('tx_mklcurrxrate_pi1');
		$this->exchangeRate->init();
		$result = $this->exchangeRate->getExchangeRate($this->baseCurrency, $this->currency) ;
		$this->xrate = floatval ( $result["rate"] );
		
		// set allowed/excluded country codes for selector
		$this->config["allowedCountry"] = $this->conf["allowedCountry"] ? $this->conf["allowedCountry"] : '' ;	
		$this->config["excludedCountry"] = $this->conf["excludedCountry"] ? $this->conf["excludedCountry"] : '' ;
		
//		$this->config["code"] = strtolower(trim($this->cObj->stdWrap($this->conf["code"],$this->conf["code."])));
		$this->config["code"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'display_mode');
		$this->config["product_records"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'product_records');
//debug($this->config["code"], "display_mode");
//debug($this->config["product_records"], "product_list");		 
		$this->config["limit"] = t3lib_div::intInRange($this->conf["limit"],0,1000);
		$this->config["limit"] = $this->config["limit"] ? $this->config["limit"] : 50;

		$this->config["pid_list"] = trim($this->cObj->stdWrap($this->conf["pid_list"],$this->conf["pid_list."]));
		$this->config["pid_list"] = $this->config["pid_list"] ? $this->config["pid_list"] : $GLOBALS["TSFE"]->id;

		$this->config["recursive"] = $this->cObj->stdWrap($this->conf["recursive"],$this->conf["recursive."]);
		$this->config["storeRootPid"] = $this->conf["PIDstoreRoot"] ? $this->conf["PIDstoreRoot"] : $GLOBALS["TSFE"]->tmpl->rootLine[0][uid];
		$this->config["orderTrackingPid"] = $this->conf["PIDorderTracking"] ? $this->conf["PIDorderTracking"] : $this->config["storeRootPid"];
		
			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf["stdSearchFieldExt"]) ? implode(",", array_unique(t3lib_div::trimExplode(",",$this->searchFieldList.",".trim($this->conf["stdSearchFieldExt"]),1))) : $this->searchFieldList;

        $this->config["displayListCatHeader"] = $this->conf["displayListCatHeader"];		
		
			// If the current record should be displayed.
		$this->config["displayCurrentRecord"] = $this->conf["displayCurrentRecord"];
		if ($this->config["displayCurrentRecord"])	{
			$this->config["code"]="SINGLE";
			$this->tt_product_single = true;
		} else {
			$this->tt_product_single = $GLOBALS["HTTP_GET_VARS"]["tt_products"];
		}

			// template file is fetched. The whole template file from which the various subpart are extracted.
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf["wrap2."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($this->conf["color1"],$this->conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($this->conf["color2"],$this->conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($this->conf["color3"],$this->conf["color3."]);
		$globalMarkerArray["###CURR###"] = $this->currency;

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);


			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject("externalProcessing");

			// Initializes object
		$this->setPidlist($this->config["pid_list"]);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$this->TAXpercentage = doubleval($this->conf["TAXpercentage"]);		// Set the TAX percentage.
		$this->globalMarkerArray = $globalMarkerArray;
		$this->initCategories();
		$this->initBasket($GLOBALS["TSFE"]->fe_user->getKey("ses","recs"));	// Must do this to initialize the basket...


		// *************************************
		// *** Listing items:
		// *************************************

		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (!count($codes))	$codes=array("");
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));

			//	debug($theCode);
			switch($theCode)	{
				case "TRACKING":
					$content.=$this->products_tracking($theCode);
				break;
				case "BASKET":
				case "PAYMENT":
				case "FINALIZE":
				case "INFO":
					$content.=$this->products_basket($theCode);
				break;
				case "SEARCH":
				case "SINGLE":
				case "LIST":
				case "RECORDS":	
 					$content.=$this->products_display($theCode);
				break;
				case "CURRENCY":
					$content.=$this->currency_selector($theCode);
				break;
				case "PRDCOUNTER":
					$content.=$this->product_counter($theCode);
				break;				
				
			}
		}
		return $content;
	}

	/**
	 * Get External CObjects
	 */
	function getExternalCObject($mConfKey)	{
		if ($this->conf[$mConfKey] && $this->conf[$mConfKey."."])	{
			$this->cObj->regObj = &$this;
			return $this->cObj->cObjGetSingle($this->conf[$mConfKey],$this->conf[$mConfKey."."],"/".$mConfKey."/")."";
		}
	}


	/**
	 * Products counter
	 */
	function product_counter($theCode)	{
		$total_counter  = 0;
		$new_counter  = 0;
		
		$last_week = time() - (7 * 24 * 60 * 60);
		
		$this->setPidlist($this->config["storeRootPid"]);
		$this->initRecursive(999);
		$this->generatePageArray();

		$query = "SELECT count(*) FROM tt_products WHERE tt_products.pid IN ($this->pid_list)";
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);				
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$total_counter = $row[0];		
		
		$query = "SELECT count(*) FROM tt_products WHERE tt_products.pid IN ($this->pid_list)";
		$query.= " AND tt_products.crdate > $last_week";
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);				
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$new_counter = $row[0];			
		

		$content = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###PRODUCT_COUNTER###"));
		$content = $this->cObj->substituteMarker( $content, "###TOTAL_COUNTER###", $total_counter );
		$content = $this->cObj->substituteMarker( $content, "###NEW_COUNTER###", $new_counter );

		return $content ;
	}

	/**
	 * Currency selector
	 */
	function currency_selector($theCode)	{
		$currList = $this->exchangeRate->initCurrencies($this->BaseCurrency, $this->allowedCurrency);
		$jScript =  '	var currlink = new Array(); '.chr(10);
		$index = 0;
		foreach( $currList as $key => $value)	{
			$url = $this->getLinkUrl('','',array("C" => 'C='.$key));
			$jScript .= '	currlink['.$index.'] = "'.$url.'"; '.chr(10) ;
			$index ++ ;
		}

		$content = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###CURRENCY_SELECTOR###"));
		$content = $this->cObj->substituteMarker( $content, "###CURRENCY_FORM_NAME###", 'mkl_products_currsel_form' );
		$onChange = "if (!document.mkl_products_currsel_form.C.options[document.mkl_products_currsel_form.C.selectedIndex].value) return; top.location.replace(currlink[document.mkl_products_currsel_form.C.selectedIndex] );";
		$selector = $this->exchangeRate->buildCurrSelector($this->BaseCurrency,$this->allowedCurrency,'C','',$this->currency, $onChange);
		$content = $this->cObj->substituteMarker( $content, "###SELECTOR###", $selector );

		// javascript to submit correct get parameters for each currency
		$GLOBALS['TSFE']->additionalHeaderData['tx_mklproducts'] = '<script type="text/javascript">'.chr(10).$jScript.'</script>';
		return $content ;
	}

	/**
	 * Order tracking
	 */
	function products_tracking($theCode)	{
		$admin = $this->shopAdmin();
		if (t3lib_div::GPvar("tracking") || $admin)	{		// Tracking number must be set
			$orderRow = $this->getOrderRecord("",t3lib_div::GPvar("tracking"));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow))	$orderRow=array("uid"=>0);
				$content = $this->getTrackingInformation($orderRow,$this->templateCode);
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_WRONG_NUMBER###"));
				if (!$GLOBALS["TSFE"]->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_ENTER_NUMBER###"));
			if (!$GLOBALS["TSFE"]->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
		}
		$markerArray=array();
		$addQueryString=array();
		$markerArray["###FORM_URL###"] = $this->getLinkUrl("","",$addQueryString);	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}

	/**
	 * Takes care of basket, address info, confirmation and gate to payment
	 */
	function products_basket($theCode)	{
		$this->setPidlist($this->config["storeRootPid"]);	// Set list of page id's to the storeRootPid.
		$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
		$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.

		if (count($this->basket))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$activity="";
			if (t3lib_div::GPvar("products_info"))	{
				$activity="products_info";
			} elseif (t3lib_div::GPvar("products_payment"))	{
				$activity="products_payment";
			} elseif (t3lib_div::GPvar("products_finalize"))	{
				$activity="products_finalize";
			}

			if ($theCode=="INFO")	{
				$activity="products_info";
			} elseif ($theCode=="PAYMENT")	{
				$activity="products_payment";
			} elseif ($theCode=="FINALIZE")	{
				$activity="products_finalize";
			}
			
// debug($activity,"activity"); //MKL
				// perform action
			switch($activity)	{
				case "products_info":
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket("###BASKET_INFO_TEMPLATE###");				
				break;
				case "products_payment":
					$this->load_noLinkExtCobj();
					if ($this->checkRequired())	{
						$this->mapPersonIntoToDelivery();
						$content=$this->getBasket("###BASKET_PAYMENT_TEMPLATE###");
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_REQUIRED_INFO_MISSING###"));
						$content = $this->cObj->substituteMarkerArray($content, $this->addURLMarkers(array()));
					}
				break;
				case "products_finalize":
					if ($this->checkRequired())	{
						$this->load_noLinkExtCobj();
						$this->mapPersonIntoToDelivery();
//                        debug($this->basketExtra["payment."],"basketExtra[payment.]"); //MKL
						$handleScript = $GLOBALS["TSFE"]->tmpl->getFileName($this->basketExtra["payment."]["handleScript"]);
						if ($handleScript)	{
// 							debug($this->basketExtra["payment."]["handleScript."],"basketExtra([payment.][handleScript.])"); //MKL
							$content = $this->includeHandleScript($handleScript,$this->basketExtra["payment."]["handleScript."]);
						} else {
							$orderUid = $this->getBlankOrderUid();
							$content=$this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###");
							$this->finalizeOrder($orderUid);	// Important: finalizeOrder MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
						}
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_REQUIRED_INFO_MISSING###"));
						$content = $this->cObj->substituteMarkerArray($content, $this->addURLMarkers(array()));
					}
				break;
				default:
					$content.=$this->getBasket();					
				break;
			}
		} else {
			$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_TEMPLATE_EMPTY###"));
		}
		$markerArray=array();
		$markerArray["###EXTERNAL_COBJECT###"] = $this->externalCObject;	// adding extra preprocessing CObject
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);
// debug($this->basketExtra["payment."],"basketExtra[payment.]"); //MKL	
		return $content;
	}
	function load_noLinkExtCobj()	{
		if ($this->conf["externalProcessing_final"] || is_array($this->conf["externalProcessing_final."]))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject("externalProcessing_final");
		}
	}

	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = "";
		if (isset($this->conf["altMainMarkers."]))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf["altMainMarkers."][$sPBody],$this->conf["altMainMarkers."][$sPBody."."]));
			$GLOBALS["TT"]->setTSlogMessage("Using alternative subpart marker for '".$subpartMarker."': ".$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	}

	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode)	{
		$addQueryString=array();
		$formUrl = $this->getLinkUrl($this->conf["PIDbasket"],"",$addQueryString);
		if ($this->tt_product_single)	{
	// List single product:
				// performing query:
			$this->setPidlist($this->config["storeRootPid"]);
			$this->initRecursive(999);
			$this->generatePageArray();

 

 			$query = "select tt_products.uid,tt_products.pid";
 			$query .= ",tt_products.title,tt_products.note";
 			$query .= ",tt_products.price,tt_products.price2,tt_products.price2_qty,tt_products.price_factor";
 			$query .= ",tt_products.unit,tt_products.unit_factor";
 			$query .= ",tt_products.weight";
 			$query .= ",tt_products.image,tt_products.datasheet,tt_products.www";
 			$query .= ",tt_products.itemnumber,tt_products.category";
 			$query .= ",tt_products.inStock,tt_products.inStock_low,tt_products.on_demand,tt_products.ordered";
 			$query .= ",tt_products.fe_group";

 	       		// language ovelay
			if ($this->language > 0) {
				$query .= ",tt_products_language_overlay.title AS o_title";
				$query .= ",tt_products_language_overlay.note AS o_note";
				$query .= ",tt_products_language_overlay.unit AS o_unit";
				$query .= ",tt_products_language_overlay.datasheet AS o_datasheet";
				$query .= ",tt_products_language_overlay.www AS o_www";
			}
			$query .= " FROM tt_products";
			if ($this->language > 0) {
				$query .= " LEFT JOIN tt_products_language_overlay";
				$query .= " ON (tt_products.uid=tt_products_language_overlay.prd_uid";
				$query .= " AND tt_products_language_overlay.sys_language_uid=$this->language";
				$query .= $this->cObj->enableFields("tt_products_language_overlay");
				$query .= ")";
			}
			$query .= " WHERE 1=1";
			$query .= " AND tt_products.uid=".intval($this->tt_product_single);
			$query .= " AND tt_products.pid IN ($this->pid_list) ";
			$query .= $this->cObj->enableFields("tt_products");

			$res = $GLOBALS['TYPO3_DB']->sql_query($query);				

			if($this->config["displayCurrentRecord"] || $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
					// Get the subpart code
				$item ="";
				if ($this->config["displayCurrentRecord"])	{
					$row=$this->cObj->data;
					// debug($row);
					$item = trim($this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SINGLE_DISPLAY_RECORDINSERT###")));
				}
				// mkl $catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]:"");

				$catTitle= $this->categories[$row["category"]]["title"];
				if ($this->language > 0 && $row["o_datasheet"] != "") {
					$datasheetFile = $row["o_datasheet"] ;
				} else  {
					$datasheetFile = $row["datasheet"] ;
				}



				if (!$item)	{$item = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SINGLE_DISPLAY###"));}

					// Fill marker arrays
				$wrappedSubpartArray=array();
				$addQueryString=array();
				$wrappedSubpartArray["###LINK_ITEM###"]= array('<A href="'.$this->getLinkUrl(t3lib_div::GPvar("backPID"),"",$addQueryString).'">','</A>');

				// absolute link to this item ( for facebook like button ) 
					$itemAddQueryString=array();
					$itemAddQueryString["tt_products"]= 'tt_products='.$row["uid"];
					$itemUrl = $this->getLinkUrl($this->conf["PIDitemDisplay"],"",$itemAddQueryString) ;
					$itemAbsUrl = $GLOBALS['TSFE']->config['config']['baseURL'] . $itemUrl ;
			
				
				
				$catTitle= $this->categories[$row["category"]]["title"];


				if( $datasheetFile == "" )  {
					$wrappedSubpartArray["###LINK_DATASHEET###"]= array('<!--','-->');
				}  else  {
					$wrappedSubpartArray["###LINK_DATASHEET###"]= array('<A href="uploads/tx_mklproducts/datasheet/'.$datasheetFile.'">','</A>');
				}

				$markerArray = $this->getItemMarkerArray ($row,$catTitle,10);
				$markerArray["###FORM_NAME###"]="item_".$this->tt_product_single;
				$markerArray["###FORM_URL###"]=$formUrl;
				$markerArray["###ILIKE_URL###"] = $itemAbsUrl;					

					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($item,$markerArray,array(),$wrappedSubpartArray);
			}
		} elseif ($theCode=="SINGLE") {
			$content.="Wrong parameters, GET/POST var 'tt_products' was missing.";
		} else {
			$content="";

	        if ($theCode=="RECORDS") { // List of selected products uid's
	        	$where=" AND tt_products.uid IN (".$this->config["product_records"].")" ;
	        }
	        else {                  	// List products:
				$where="";
	        }	
			if ($theCode=="SEARCH")	{
					// Get search subpart
				$t["search"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SEARCH###"));
					// Substitute a few markers
				$out=$t["search"];
				$addQueryString=array();
				$out=$this->cObj->substituteMarker($out, "###FORM_URL###", $this->getLinkUrl($this->conf["PIDsearch"],"",$addQueryString));
				$out=$this->cObj->substituteMarker($out, "###SWORDS###", htmlspecialchars(t3lib_div::GPvar("swords")));
					// Add to content
				$content.=$out;
				if (t3lib_div::GPvar("swords"))	{
					$where = $this->searchWhere(trim(t3lib_div::GPvar("swords")));
				}
			}
			$begin_at=t3lib_div::intInRange(t3lib_div::GPvar("begin_at"),0,100000);
			if (($theCode!="SEARCH" && !t3lib_div::GPvar("swords")) || $where)	{

				$this->initRecursive($this->config["recursive"]);
				$this->generatePageArray();

					// Get products
				$selectConf = Array();
				$selectConf["pidInList"] = $this->pid_list;
				$selectConf["where"] = "1=1 ".$where;

					// performing query to count all products (we need to know it for browsing):
				// obsolete: $query = eregi_replace("^[\t ]*SELECT.+FROM", "SELECT count(*) FROM", $this->cObj->getQuery("tt_products",$selectConf));
				$query = preg_replace("/^[\t ]*SELECT.+FROM/i", "SELECT count(*) FROM", $this->cObj->getQuery("tt_products",$selectConf));
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);				

				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$productsCount = $row[0];

					// range check to current productsCount
				$begin_at = t3lib_div::intInRange(($begin_at >= $productsCount)?($productsCount-$this->config["limit"]):$begin_at,0);

				// Fetching products:
	 			$query = "select tt_products.uid,tt_products.pid";
	 			$query .= ",tt_products.title,tt_products.note";
	 			$query .= ",tt_products.price,tt_products.price2,tt_products.price2_qty,tt_products.price_factor";
	 			$query .= ",tt_products.unit,tt_products.unit_factor";
	 			$query .= ",tt_products.weight";
	 			$query .= ",tt_products.image,tt_products.datasheet,tt_products.www";
	 			$query .= ",tt_products.itemnumber,tt_products.category";
	 			$query .= ",tt_products.inStock,tt_products.inStock_low,tt_products.on_demand,tt_products.ordered";
	 			$query .= ",tt_products.fe_group";

	 	       		// language ovelay
				if ($this->language > 0) {
					$query .= ",tt_products_language_overlay.title AS o_title";
					$query .= ",tt_products_language_overlay.note AS o_note";
					$query .= ",tt_products_language_overlay.unit AS o_unit";
					$query .= ",tt_products_language_overlay.datasheet AS o_datasheet";
					$query .= ",tt_products_language_overlay.www AS o_www";
				}
				$query .= " FROM tt_products";
				if ($this->language > 0) {
					$query .= " LEFT JOIN tt_products_language_overlay";
					$query .= " ON (tt_products.uid=tt_products_language_overlay.prd_uid";
					$query .= " AND tt_products_language_overlay.sys_language_uid=$this->language";
					$query .= $this->cObj->enableFields("tt_products_language_overlay");
					$query .= ")";
				}
				$query .= " WHERE 1=1";
				$query .= $where ;
				$query .= " AND tt_products.pid IN ($this->pid_list) ";
				$query .= $this->cObj->enableFields("tt_products");
				$query .= " ORDER BY pid,category,sorting,title";
				$query .=" LIMIT ".$begin_at.",".($this->config["limit"]+1);


//				debug($this->cObj->enableFields("tt_products"));
//				debug($query);

				$res = $GLOBALS['TYPO3_DB']->sql_query($query);				
				$productsArray=array();
				while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
// debug($row);
					$productsArray[$row["pid"]][]=$row;
				}

					// Getting various subparts we're going to use here:
				$t["listFrameWork"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_LIST_TEMPLATE###"));
				$t["categoryTitle"] = $this->cObj->getSubpart($t["listFrameWork"],"###ITEM_CATEGORY###");
				$t["itemFrameWork"] = $this->cObj->getSubpart($t["listFrameWork"],"###ITEM_LIST###");
				$t["item"] = $this->cObj->getSubpart($t["itemFrameWork"],"###ITEM_SINGLE###");

				$pageArr=explode(",",$this->pid_list);
				$currentP="";
				$out="";
				$iCount=0;
				$more=0;		// If set during this loop, the next-item is drawn
				while(list(,$v)=each($pageArr))	{
					if (is_array($productsArray[$v]))	{
						reset($productsArray[$v]);
						$itemsOut="";
						while(list(,$row)=each($productsArray[$v]))	{
							$iCount++;
							if ($iCount>$this->config["limit"])	{
								$more=1;
								break;
							}


								// Print Category Title
							if ( ( $row["pid"]."_".$row["category"]!=$currentP )
							    	&& ! ($theCode=="RECORDS") )	{
								if ($itemsOut)	{
									$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
								}
								$itemsOut="";			// Clear the item-code var

								$currentP = $row["pid"]."_".$row["category"];
								if ($where || $this->conf["displayListCatHeader"])	{
									$markerArray=array();
								// mkl	$catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]:"");
									$catTitle= $this->categories[$row["category"]]["title"];
									$this->cObj->setCurrentVal($catTitle);
									$catImage = $this->categories[$row["category"]]["image"];
									if ($catImage)	{
										$this->conf[$imageRenderObj."."]["file"] = "uploads/pics/".$catImage;
									} else {
										$this->conf[$imageRenderObj."."]["file"] = $this->conf["noImageAvailable"];
									}
									$theImgCode = $this->cObj->IMAGE($this->conf[$imageRenderObj."."]);

								// mkl 	$this->cObj->setCurrentVal("kategoria testowa");
									$markerArray["###CATEGORY_TITLE###"]=$this->cObj->cObjGetSingle($this->conf["categoryHeader"],$this->conf["categoryHeader."], "categoryHeader");
									$markerArray["###CATEGORY_NOTE###"]= $this->categories[$row["category"]]["note"];
									$markerArray["###CATEGORY_IMAGE###"]= $theImgCode;
									$out.= $this->cObj->substituteMarkerArray($t["categoryTitle"], $markerArray);
								}

							}

							if ($this->language > 0 && $row["o_datasheet"] != "") {
								$datasheetFile = $row["o_datasheet"] ;
							} else  {
								$datasheetFile = $row["datasheet"] ;
							}
								// Print Item Title
							$wrappedSubpartArray=array();
							$addQueryString=array();
							$addQueryString["tt_products"]= 'tt_products='.$row["uid"];
							$itemUrl = $this->getLinkUrl($this->conf["PIDitemDisplay"],"",$addQueryString) ;
							$itemAbsUrl = $GLOBALS['TSFE']->config['config']['baseURL'] . $itemUrl ;	
							$itemLinkTitle = $this->conf["productLinkTitle"] ; 					
							
							$wrappedSubpartArray["###LINK_ITEM###"]= array('<A href="'.$itemUrl.'" title="'.$itemLinkTitle.'" >','</A>');
							if( $datasheetFile == "" )  {
								$wrappedSubpartArray["###LINK_DATASHEET###"]= array('<!--','-->');
							}  else  {
								$wrappedSubpartArray["###LINK_DATASHEET###"]= array('<A href="uploads/tx_mklproducts/datasheet/'.$datasheetFile.'">','</A>');
							}
							$markerArray = $this->getItemMarkerArray ($row,$catTitle,1,"listImage");	
							$markerArray["###ILIKE_URL###"] = $itemAbsUrl;						
							$markerArray["###FORM_URL###"]=$formUrl; // Applied later as well.
							$markerArray["###FORM_NAME###"]="item_".$iCount;
							$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);
						}
						if ($itemsOut)	{
							$out.=$this->cObj->substituteMarkerArrayCached($t["itemFrameWork"], array(), array("###ITEM_SINGLE###"=>$itemsOut));

						}
					}
				}
			}
			if ($out)	{
				// next / prev:
				$addQueryString=array();
					// Reset:
				$subpartArray=array();
				$wrappedSubpartArray=array();
				$markerArray=array();

				if ($more)	{
					$next = ($begin_at+$this->config["limit"] > $productsCount) ? $productsCount-$this->config["limit"] : $begin_at+$this->config["limit"];
					$addQueryString['begin_at'] = 'begin_at='.$next ;
					$wrappedSubpartArray["###LINK_NEXT###"]=array('<A href="'.$this->getLinkUrl("","begin_at",$addQueryString).'">','</A>');
				} else {
					$subpartArray["###LINK_NEXT###"]="";
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config["limit"] < 0) ? 0 : $begin_at-$this->config["limit"];
					$addQueryString['begin_at'] = 'begin_at='.$prev ;
					$wrappedSubpartArray["###LINK_PREV###"]=array('<A href="'.$this->getLinkUrl("","begin_at",$addQueryString).'">','</A>');
				} else {
					$subpartArray["###LINK_PREV###"]="";
				}
				if ($productsCount > $this->config["limit"] )	{ // there is more than one page, so let's browse
					$wrappedSubpartArray["###LINK_BROWSE###"]=array('',''); // <- this could be done better I think, or not?
					$markerArray["###BROWSE_LINKS###"]="";
					for ($i = 0 ; $i < ($productsCount/$this->config["limit"]); $i++) 	{
						if (($begin_at >= $i*$this->config["limit"]) && ($begin_at < $i*$this->config["limit"]+$this->config["limit"])) 	{
							$markerArray["###BROWSE_LINKS###"].= ' <b>'.(string)($i+1).'</b> ';
							//	you may use this if you want to link to the current page also
							//	$markerArray["###BROWSE_LINKS###"].= ' <A href="'.$url.'&begin_at='.(string)($i * $this->config["limit"]).'"><b>'.(string)($i+1).'</b></A> ';
						} else {
							$addQueryString['begin_at'] = 'begin_at='.(string)($i * $this->config["limit"]) ;
							$markerArray["###BROWSE_LINKS###"].= ' <A href="'.$this->getLinkUrl("","begin_at",$addQueryString).'">'.(string)($i+1).'</A> ';
						}
					}
				} else {
					$subpartArray["###LINK_BROWSE###"]="";
				}

				$subpartArray["###ITEM_CATEGORY_AND_ITEMS###"]=$out;
				$markerArray["###FORM_URL###"]=$formUrl;      // Applied it here also...
				$markerArray["###ITEMS_SELECT_COUNT###"]=$productsCount;

				$content.= $this->cObj->substituteMarkerArrayCached($t["listFrameWork"],$markerArray,$subpartArray,$wrappedSubpartArray);
			} elseif ($where)	{
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SEARCH_EMPTY###"));
			}
		}

// -->		
		
		return $content;
	}

	/**
	 * Sets the pid_list internal var
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}

	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 */
	function initRecursive($recursive)	{
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$pid_list_arr = explode(",",$this->pid_list);
			$this->pid_list="";
			while(list(,$val)=each($pid_list_arr))	{
				$this->pid_list.=$val.",".$this->cObj->getTreeList($val,intval($recursive));
			}
			// $this->pid_list = ereg_replace(",$","",$this->pid_list);
			$this->pid_list = preg_replace("/,$/","",$this->pid_list);
		}
	}

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function initCategories()	{
			// Fetching catagories:
	 	$query = "select tt_products_cat.uid,tt_products_cat.pid";
	 	$query .= ",tt_products_cat.tstamp,tt_products_cat.crdate";
	 	$query .= ",tt_products_cat.hidden,tt_products_cat.title";
	 	$query .= ",tt_products_cat.note,tt_products_cat.image,tt_products_cat.deleted";
	 	       // mkl: language ovelay
		if ($this->language > 0) {
			$query .= ",tt_products_cat_language_overlay.title AS o_title";
			$query .= ",tt_products_cat_language_overlay.note AS o_note";
		}
		$query .= " FROM tt_products_cat";
		if ($this->language > 0) {
			$query .= " LEFT JOIN tt_products_cat_language_overlay";
			$query .= " ON (tt_products_cat.uid=tt_products_cat_language_overlay.cat_uid";
			$query .= " AND tt_products_cat_language_overlay.sys_language_uid=$this->language";
			$query .= $this->cObj->enableFields("tt_products_cat_language_overlay");
			$query .= ")";
		}
		$query .= " WHERE 1=1";
		$query .= $this->cObj->enableFields("tt_products_cat");


//	 	debug($query);
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);		
		$this->categories=array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
//			debug($row);
		        if ( ($this->language > 0) && $row["o_title"] )	{
				$this->categories[$row["uid"]]["title"] = $row["o_title"];
		        }
		        else	{
				$this->categories[$row["uid"]]["title"] = $row["title"];
				}
		        if ( ($this->language > 0) && $row["o_note"] )	{
				$this->categories[$row["uid"]]["note"] = $this->pi_RTEcssText($row["o_note"]);
		        }
		        else	{
				$this->categories[$row["uid"]]["note"] = $this->pi_RTEcssText($row["note"]);
				}
				$this->categories[$row["uid"]]["image"] = $row["image"];
		}
	}





	/**
	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
	 */
	function generatePageArray()	{
			// Get pages (for category titles)
		$query="SELECT title,uid FROM pages WHERE uid IN(".$this->pid_list.")";
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$this->pageArray=array();
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row["uid"]] = $row;
		}
	}

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 *
     * $basket is the Typo3 default shopping basket array from ses-data
	 */
	function initBasket($basket)	{
		$this->recs = $basket;	// Sets it internally
		$this->basket=array();
		$uidArr=array();
		if (is_array($basket["tt_products"]))	{
			reset($basket["tt_products"]);
			while(list($uid,$count)=each($basket["tt_products"]))	{
				if (t3lib_div::testInt($uid))	{
					$count=t3lib_div::intInRange($count,0,100000);
					if ($count)	{
						$this->basket[$uid]=$count;
						$uidArr[]=$uid;
					}
				}
			}
		}
		$this->uid_list=implode($uidArr,",");
		$this->setBasketExtras($basket);

		$this->personInfo = $basket["personinfo"];
		$this->deliveryInfo = $basket["delivery"];
//      debug($this->deliveryInfo,"delivery");//MKL		
		if ($GLOBALS["TSFE"]->loginUser && (!$this->personInfo || $this->conf["lockLoginUserInfo"]))	{
			$address = implode(chr(10),t3lib_div::trimExplode(chr(10),
				$GLOBALS["TSFE"]->fe_user->user["address"].chr(10).
				$GLOBALS["TSFE"]->fe_user->user["zip"]." ".$GLOBALS["TSFE"]->fe_user->user["city"].chr(10).
				$GLOBALS["TSFE"]->fe_user->user["country"]
				,1)
			);

			$this->personInfo["name"] = $GLOBALS["TSFE"]->fe_user->user["name"];
			$this->personInfo["company"] = $GLOBALS["TSFE"]->fe_user->user["company"];
			$this->personInfo["address"] = $address;
			$this->personInfo["email"] = $GLOBALS["TSFE"]->fe_user->user["email"];
			$this->personInfo["telephone"] = $GLOBALS["TSFE"]->fe_user->user["telephone"];
			$this->personInfo["fax"] = $GLOBALS["TSFE"]->fe_user->user["fax"];

		}
		else {
			if( ! $this->personInfo["country_code"] ) {
				$this->personInfo["country_code"] = $this->conf["countryCode"] ;
	 		}
//          debug($this->personInfo["country_code"],"country_code");//MKL
		}

		$this->vatIncluded = $this->checkVatInclude() ;
	}

	/**
	 * Check if payment/shipping option is available
	 */
	function checkExtraAvailable($name,$key)	{
		if (is_array($this->conf[$name."."][$key."."]) && (!isset($this->conf[$name."."][$key."."]["show"]) || $this->conf[$name."."][$key."."]["show"]))	{
			return true;
		}
	}

	/**
	 * Setting shipping and payment methods
	 */
	function setBasketExtras($basket)	{
			// shipping			
		ksort($this->conf["shipping."]);
		reset($this->conf["shipping."]);
		$k=intval($basket["tt_products"]["shipping"]);
		if (!$this->checkExtraAvailable("shipping",$k))	{
			$k=intval(key($this->cleanConfArr($this->conf["shipping."],1)));
		}
		$this->basketExtra["shipping"] = $k;
		$this->basketExtra["shipping."] = $this->conf["shipping."][$k."."];
		$excludePayment = trim($this->basketExtra["shipping."]["excludePayment"]);

			// payment
		if ($excludePayment)	{
			$exclArr = t3lib_div::intExplode(",",$excludePayment);
			while(list(,$theVal)=each($exclArr))	{
				unset($this->conf["payment."][$theVal]);
				unset($this->conf["payment."][$theVal."."]);
			}
		}

		ksort($this->conf["payment."]);
		reset($this->conf["payment."]);
		$k=intval($basket["tt_products"]["payment"]);
		if (!$this->checkExtraAvailable("payment",$k))	{
			$k=intval(key($this->cleanConfArr($this->conf["payment."],1)));
		}
		$this->basketExtra["payment"] = $k;
		$this->basketExtra["payment."] = $this->conf["payment."][$k."."];

//		debug($this->basketExtra);
//		debug($this->conf);
	}

	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	function getClearBasketRecord()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs["tt_products"]);
		return ($this->recs);
	}







	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid, .PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. getBlankOrderUid, that first checks if a blank record is already created.
	 */
	function createOrder()	{
		$newId=0;
		$pid = intval($this->conf["PID_sys_products_orders"]);
		if (!$pid)	$pid = intval($GLOBALS["TSFE"]->id);
		if ($GLOBALS["TSFE"]->sys_page->getPage_noCheck ($pid))	{
			$advanceUid=0;
			if ($this->conf["advanceOrderNumberWithInteger"])	{
				$query="SELECT uid FROM sys_products_orders ORDER BY uid DESC LIMIT 1";
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);
				list($prevUid)	= $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				$rndParts = explode(",",$this->conf["advanceOrderNumberWithInteger"]);
				$advanceUid=$prevUid+t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
#debug(array($prevUid,$advanceUid));
			}
			if ($advanceUid>0)	{
				$query = "INSERT INTO sys_products_orders (uid,pid,tstamp,crdate,deleted) values (".$advanceUid.",".$pid.",".time().",".time().",1)";
			} else {
				$query = "INSERT INTO sys_products_orders (pid,tstamp,crdate,deleted) values (".$pid.",".time().",".time().",1)";
			}
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
	
		}
		return $newId;
	}

	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked "not deleted" and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankOrderUid()	{
		$orderUid = intval($this->recs["tt_products"]["orderUid"]);
		$query = "SELECT uid FROM sys_products_orders WHERE uid=".$orderUid." AND deleted AND NOT status";	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$orderUid = $this->createOrder();
			$this->recs["tt_products"]["orderUid"] = $orderUid;
			$this->recs["tt_products"]["orderDate"] = time();
			$this->recs["tt_products"]["orderTrackingNo"] = $this->getOrderNumber($orderUid)."-".strtolower(substr(md5(uniqid(time())),0,6));
			$GLOBALS["TSFE"]->fe_user->setKey("ses","recs",$this->recs);
		}
		return $orderUid;
	}

	/**
	 * Returns the orderRecord if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getOrderRecord($orderUid,$tracking="")	{
		$selectClause= $tracking ? "tracking_code='".$tracking."'" : "uid=".intval($orderUid);
		$query = "SELECT * FROM sys_products_orders WHERE ".$selectClause." AND NOT deleted";
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}

	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getOrderNumber($orderUid)	{
		return substr($this->conf["orderNumberPrefix"],0,10).$orderUid;
	}

	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked "not deleted" and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 */
	function finalizeOrder($orderUid,$mainMarkerArray=array())	{
			// Fix delivery address
		$this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
		$mainMarkerArray["###EXTERNAL_COBJECT###"] = $this->externalCObject."";
		$orderConfirmationHTML=trim($this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###","",$mainMarkerArray));		// Getting the template subpart for the order confirmation!

			// Saving order data
		$fieldsArray=array();
		$fieldsArray["note"]=$this->deliveryInfo["note"];
		$fieldsArray["name"]=$this->deliveryInfo["name"];

		//<-- MKL 2004.09.21
		$fieldsArray["forename"]=$this->personInfo["forename"];
		$fieldsArray["company"]=$this->personInfo["company"];
		$fieldsArray["vat_id"]=$this->personInfo["vat_id"];
		$fieldsArray["street"]=$this->deliveryInfo["street"];
		$fieldsArray["street_n1"]=$this->deliveryInfo["street_n1"];
		$fieldsArray["street_n2"]=$this->deliveryInfo["street_n2"];
		$fieldsArray["city"]=$this->deliveryInfo["city"];
		$fieldsArray["zip"]=$this->deliveryInfo["zip"];
		$fieldsArray["country_code"]=$this->personInfo["country_code"];
		$fieldsArray["client_ip"]=t3lib_div::getIndpEnv('REMOTE_ADDR');
		//--> MKL 2004.09.21

		$fieldsArray["telephone"]=$this->deliveryInfo["telephone"];
		$fieldsArray["email"]=$this->deliveryInfo["email"];
		$fieldsArray["email_notify"]=  $this->conf["email_notify_default"];		// Email notification is set here. Default email address is delivery email contact

			// can be changed after order is set.
		$fieldsArray["payment"]=$this->basketExtra["payment"].": ".$this->basketExtra["payment."]["title"];
		$fieldsArray["shipping"]=$this->basketExtra["shipping"].": ".$this->basketExtra["shipping."]["title"];
		$fieldsArray["amount"]=$this->calculatedSums_tax["total"];
                                $fieldsArray["amount_num"]=$fieldsArray["amount"] * 100 ;
		$fieldsArray["status"]=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			"time" => time(),
			"info" => $this->conf["statusCodes."][$fieldsArray["status"]],
			"status" => $fieldsArray["status"],
			"comment" => $this->deliveryInfo["note"]
		);

		$fieldsArray["status_log"]=serialize($status_log);

			// Order Data serialized
		$fieldsArray["orderData"]=serialize(array(
				"html_output" 			=> $orderConfirmationHTML,
				"deliveryInfo" 			=> $this->deliveryInfo,
				"personInfo" 			=> $this->personInfo,
				"calculatedBasket"		=>	$this->calculatedBasket,
				"calculatedSum_tax"		=>	$this->calculatedSums_tax,
				"calculatedSums_no_tax"	=>	$this->calculatedSums_no_tax
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray["tstamp"]=time();
		$fieldsArray["deleted"]=0;
		$fieldsArray["tracking_code"]=$this->recs["tt_products"]["orderTrackingNo"];

			// Saving the order record
		$query="UPDATE sys_products_orders SET ".$this->getUpdateQuery($fieldsArray)." WHERE uid=".$orderUid;
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = $this->getOrderRecord($orderUid);


			// Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
			// First: delete any existing. Shouldn't be any
		$query="DELETE FROM sys_products_orders_mm_tt_products WHERE sys_products_orders_uid=".$orderUid;
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			// Second: Insert a new relation for each ordered item
		reset($this->calculatedBasket);
		while(list(,$itemInfo)=each($this->calculatedBasket))	{
//			debug($itemInfo);
			$query="INSERT INTO sys_products_orders_mm_tt_products (sys_products_orders_uid,sys_products_orders_qty,tt_products_uid) VALUES ('".$orderUid."','".intval($itemInfo["count"])."','".intval($itemInfo["rec"]["uid"])."')";
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		}
//		debug($this->calculatedBasket);


	// Sends order emails:
	
// <--- Miklobit 20011.12.17 - send mail using swift mailer API		
 

	  $emailContent=trim($this->getBasket("###EMAIL_PLAINTEXT_TEMPLATE###"));
	  if ($emailContent)	{		// If there is plain text content - which is required!!
		  $parts = split(chr(10),$emailContent,2);		// First line is subject
		  $subject=trim($parts[0]);
		  $plain_message=trim($parts[1]);	  	  
	  
	  	  $HTMLmailShell=$this->cObj->getSubpart($this->templateCode,"###EMAIL_HTML_SHELL###");
	      $HTMLmailContent=$this->cObj->substituteMarker($HTMLmailShell,"###HTML_BODY###",$orderConfirmationHTML);
	      $HTMLmailContent=$this->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);	  

	      // Remove image tags to products:
		  if ($this->conf["orderEmail_htmlmail."]["removeImagesWithPrefix"])	{
			 $parser = t3lib_div::makeInstance("t3lib_parsehtml");
			 $htmlMailParts = $parser->splitTags("img",$HTMLmailContent);

			 reset($htmlMailParts);
			 while(list($kkk,$vvv)=each($htmlMailParts))	{
			 	if ($kkk%2)	{
			 		list($attrib) = $parser->get_tag_attributes($vvv);
			   		if (t3lib_div::isFirstPartOfStr($attrib["src"],$this->conf["orderEmail_htmlmail."]["removeImagesWithPrefix"]))	{
// debug($htmlMailParts[$kkk]);
				 		$htmlMailParts[$kkk]="";
					}
				}
			 }
			 $HTMLmailContent=implode("",$htmlMailParts);
		  }	  
	  
		  $mail = t3lib_div::makeInstance('t3lib_mail_message');
		  

		  $recipients = $this->conf["orderEmail_to"];
		  $recipients.=",".$this->deliveryInfo["email"];
		  $recipients=t3lib_div::trimExplode(",",$recipients,1);	  
		  $toEMail = array();
		  foreach ($recipients as $email) {
				$toEMail[] = $email;
		  }	 		  
		  
	      $mail->setFrom(array($this->conf["orderEmail_from"] => $this->conf["orderEmail_fromName"]));			
	      $mail->setTo($toEMail);
	      $mail->setSubject($subject);
	      // $mail->setBody($html, 'text/html', $GLOBALS['TSFE']->renderCharset ); // charset settings in tt_products
	      $mail->setBody($HTMLmailContent, 'text/html', $GLOBALS['TSFE']->renderCharset );
	      $mail->addPart($plain_message, 'text/plain', $GLOBALS['TSFE']->renderCharset);
	      $mail->send();		
	  }
// --->	send mail	

			// Empties the shopping basket!
		$GLOBALS["TSFE"]->fe_user->setKey("ses","recs",$this->getClearBasketRecord());

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->getExternalCObject("externalFinalizing");
	}






	// **************************
	// Utility functions
	// **************************


	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false. This function reads the TypoScript configuration to see whether prices in the database are entered with or without tax. That's why this function is needed.
	 * If curr <> base_curr -> multiply with price_factor 
	 */
	function getPrice($price,$tax=1,$price_factor=0)	{
		$taxFactor = 1+$this->TAXpercentage/100;
		$taxIncluded = $this->conf["TAXincluded"];
		$priceOut = 0;
		if ($tax)	{
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$priceOut = doubleval($price);
			} else {
				$priceOut = doubleval($price)*$taxFactor;
			}
		} else {
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$priceOut = doubleval($price)/$taxFactor;
			} else {
				$priceOut =  doubleval($price);
			}
		}
		
		if( ( doubleval($price_factor) > 0 ) &&
		    ( $this->currency != $this->baseCurrency ))  {
		  	$priceOut = $priceOut * doubleval($price_factor) ;
		  } 
		return $priceOut ;  
	}

	/**
	 * Takes an array with key/value pairs and returns it for use in an UPDATE query.
	 */
	function getUpdateQuery($Darray)	{
		reset($Darray);
		$query=array();
		while(list($field,$data)=each($Darray))	{
			$query[]=$field."='".addslashes($data)."'";
		}
		return implode($query,",");
	}

	/**
	 * Generates a search where clause.
	 */
	function searchWhere($sw)	{
		$where=$this->cObj->searchWhere($sw,$this->searchFieldList);
		return $where;
	}

	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkUrl($id="",$excludeList="",$addQueryString=array())	{
		$queryString=array();
		$queryString["id"] = "id=".($id ? $id : $GLOBALS["TSFE"]->id);
		$queryString["type"]= $GLOBALS["TSFE"]->type ? 'type='.$GLOBALS["TSFE"]->type : "";
		$queryString["L"]= t3lib_div::GPvar("L") ? 'L='.t3lib_div::GPvar("L") : "";
		$queryString["C"]= t3lib_div::GPvar("C") ? 'C='.t3lib_div::GPvar("C") : 'C='.$this->currency;
		if( isset($addQueryString["C"]) )  {
			$queryString["C"] = $addQueryString["C"] ;
			unset( $addQueryString["C"] );
		}
		$queryString["backPID"]= 'backPID='.$GLOBALS["TSFE"]->id;
		$queryString["begin_at"]= t3lib_div::GPvar("begin_at") ? 'begin_at='.t3lib_div::GPvar("begin_at") : "";
		$queryString["swords"]= t3lib_div::GPvar("swords") ? "swords=".rawurlencode(stripslashes(t3lib_div::GPvar("swords"))) : "";

		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}
		if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments'])   {
			$pageId = $id ? $id : $GLOBALS["TSFE"]->id ;
			$pageType = $GLOBALS["TSFE"]->type ;
			unset($queryString['id']);
			unset($queryString['type']);

			$allQueryString = implode($queryString,"&");
			if( $addQueryString )	{
				$allQueryString .= "&".implode($addQueryString,"&");
			}
//			debug($allQueryString);
                        return $GLOBALS["TSFE"]->makeSimulFileName("", $pageId, $pageType, $allQueryString ).".html";

		}
		else	{
			$allQueryString = implode($queryString,"&");
			if( $addQueryString )	{
				$allQueryString .= "&".implode($addQueryString,"&");
			}
			return $GLOBALS["TSFE"]->absRefPrefix.'index.php?'.$allQueryString;
		}
	}

	/**
	 * convert amount to selected currency
	 */
	function getCurrencyAmount($double)	{
		if( $this->currency != $this->baseCurrency )	{
			$double = $double * $this->xrate ;
		}
		return $double;
	}

	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		$double = $this->getCurrencyAmount($double) ;
		return number_format($double,intval($this->conf["priceDec"]),$this->conf["priceDecPoint"],$this->conf["priceThousandPoint"]);
	}

	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoToDelivery()	{
		// MKL $infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");
		$infoFields = explode(",","forename,name,address,telephone,fax,email,company,city,zip,state,street,street_n1,street_n2,country_code,vat_id");
//		debug($infoFields); // MKL
		while(list(,$fName)=each($infoFields))	{
			if (!trim($this->deliveryInfo[$fName]))	{
				$this->deliveryInfo[$fName] = $this->personInfo[$fName];
			}
		}
	}

	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$flag=1;
		if (trim($this->conf["requiredInfoFields"]))	{
			$infoFields = t3lib_div::trimExplode(",",$this->conf["requiredInfoFields"]);
			while(list(,$fName)=each($infoFields))	{
				if (!trim($this->personInfo[$fName]))	{
					$flag=0;
					break;
				}
			}
		}
		return $flag;
	}

	/**
	 * Include calculation script which should be programmed to manipulate internal data.
	 */
	function includeCalcScript($calcScript,$conf)	{
		include($calcScript);
	}

	/**
	 * Include handle script
	 */
	function includeHandleScript($handleScript,$conf)	{
		include($handleScript);
		return $content;
	}


	/**
	 * For shop inside EU country: check if TAX should be included
	 */
	function checkVatInclude()	{
		$include = 1;
		if( $this->conf["TAXeu"] )   {
			if( ($this->personInfo["country_code"] != "") && ($this->personInfo["country_code"] != $this->conf["countryCode"]) )    {
				$whereString =  'cn_iso_3 = "'.$this->personInfo["country_code"].'"';
				$euMember = 0 ;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','static_countries', $whereString);
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
					$euMember = $row['cn_eu_member'];
				}
				// exclude VAT for EU companies with valid VAT id and for everyone outside EU
				if( !$euMember  ||  ($euMember && $this->personInfo["vat_id"] != "") )   {
					$include = 0;
				}
			}
		}
//debug($include,"vat included" );
		return $include ;
	}




	// **************************
	// Template marker substitution
	// **************************

	/**
	 * Fills in the markerArray with data for a product
	 */
	function getItemMarkerArray ($row,$catTitle, $imageNum=0, $imageRenderObj="image")	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$markerArray=array();
			// Get image
		$theImgCode="";
		$imgs = explode(",",$row["image"]);
		$val = $imgs[0];
		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$this->conf[$imageRenderObj."."]["file"] = "uploads/pics/".$val;
			} else {
				$this->conf[$imageRenderObj."."]["file"] = $this->conf["noImageAvailable"];
			}
			$theImgCode.=$this->cObj->IMAGE($this->conf[$imageRenderObj."."]);
		}

		$iconImgCode = $this->cObj->IMAGE($this->conf["datasheetIcon."]);

			// Subst. fields

		if ( ($this->language > 0) && $row["o_title"] )	{
			$markerArray["###PRODUCT_TITLE###"] = $row["o_title"];
		}
		else  {
			$markerArray["###PRODUCT_TITLE###"] = $row["title"];
		}

		if ( ($this->language > 0) && $row["o_note"] )	{
//			$markerArray["###PRODUCT_NOTE###"] = nl2br($row["o_note"]);
			$markerArray["###PRODUCT_NOTE###"] = $this->pi_RTEcssText($row["o_note"]);
		}
		else  {
//			$markerArray["###PRODUCT_NOTE###"] = nl2br($row["note"]);
			$markerArray["###PRODUCT_NOTE###"] = $this->pi_RTEcssText($row["note"]);
		}

		if ( ($this->language > 0) && $row["o_unit"] )	{
			$markerArray["###UNIT###"] = $row["o_unit"];
		}
		else  {
			$markerArray["###UNIT###"] = $row["unit"];
		}
		$markerArray["###UNIT_FACTOR###"] = $row["unit_factor"];

		if (is_array($this->conf["parseFunc."]))	{
			$markerArray["###PRODUCT_NOTE###"] = $this->cObj->parseFunc($markerArray["###PRODUCT_NOTE###"],$this->conf["parseFunc."]);
		}
		$markerArray["###PRODUCT_ITEMNUMBER###"] = $row["itemnumber"];
		$markerArray["###PRODUCT_IMAGE###"] = $theImgCode;
		$markerArray["###ICON_DATASHEET###"]=$iconImgCode;
		$markerArray["###PRICE_TAX###"] = $this->priceFormat($this->getPrice($row["price"],
		 																	 $this->vatIncluded,
		 																	 doubleval( $row["price_factor"] ) )
		                                     				 );
		$markerArray["###PRICE_NO_TAX###"] = $this->priceFormat($this->getPrice($row["price"],
		 																		0,
		 																		doubleval( $row["price_factor"] ) )
																);
		$markerArray["###PRODUCT_INSTOCK###"] = $row["inStock"];

		$markerArray["###CATEGORY_TITLE###"] = $catTitle;

		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";
		$markerArray["###FIELD_QTY###"]= $this->basket[$row["uid"]] ? $this->basket[$row["uid"]] : "";

		if ($this->conf["itemMarkerArrayFunc"])	{
			$markerArray = $this->userProcess("itemMarkerArrayFunc",$markerArray);
		}
// debug( $markerArray, "marker array");
// debug( $row, "row");
		return $markerArray;
	}

	/**
	 * Calls user function
	 */
	function userProcess($mConfKey,$passVar)	{
		if ($this->conf[$mConfKey])	{
			$funcConf = $this->conf[$mConfKey."."];
			$funcConf["parentObj"]=&$this;
			$passVar = $GLOBALS["TSFE"]->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}

	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($markerArray)	{
			// Add's URL-markers to the $markerArray and returns it
		$addQueryString=array();
		$markerArray["###FORM_URL###"] = $this->getLinkUrl($this->conf["PIDbasket"],"",$addQueryString);
		$markerArray["###FORM_URL_INFO###"] = $this->getLinkUrl($this->conf["PIDinfo"] ? $this->conf["PIDinfo"] : $this->conf["PIDbasket"],"",$addQueryString);
		$markerArray["###FORM_URL_FINALIZE###"] = $this->getLinkUrl($this->conf["PIDfinalize"] ? $this->conf["PIDfinalize"] : $this->conf["PIDbasket"],"",$addQueryString);
		$markerArray["###FORM_URL_THANKS###"] = $this->getLinkUrl($this->conf["PIDthanks"] ? $this->conf["PIDthanks"] : $this->conf["PIDbasket"],"",$addQueryString);
		$markerArray["###FORM_URL_TARGET###"] = "_self";
//		debug($this->basketExtra["payment."]);
		if ($this->basketExtra["payment."]["handleURL"])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray["###FORM_URL_THANKS###"] = $this->basketExtra["payment."]["handleURL"];
		}
		if ($this->basketExtra["payment."]["handleTarget"])	{	// Alternative target
			$markerArray["###FORM_URL_TARGET###"] = $this->basketExtra["payment."]["handleTarget"];
		}
		return $markerArray;
	}

	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect($key)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:

			 	.radio 		[boolean]	Enables radiobuttons instead of the default, selector-boxes
			 	.wrap 		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
			 	.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below

			 */
		$type=$this->conf[$key."."]["radio"];
		$active = $this->basketExtra[$key];
		$confArr = $this->cleanConfArr($this->conf[$key."."]);
		$out="";

		$template = $this->conf[$key."."]["template"] ? $this->conf[$key."."]["template"] : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';
		$wrap = $this->conf[$key."."]["wrap"] ? $this->conf[$key."."]["wrap"] :'<select name="recs[tt_products]['.$key.']" onChange="submit()">|</select>';

		while(list($key,$val)=each($confArr))	{
			if ($val["show"] || !isset($val["show"]))	{
				if ($type)	{	// radio
					$markerArray=array();
					$markerArray["###VALUE###"]=intval($key);
					$markerArray["###CHECKED###"]=(intval($key)==$active?" checked":"");
					$markerArray["###TITLE###"]=$val["title"];
					$markerArray["###IMAGE###"]=$this->cObj->IMAGE($val["image."]);
					$out.=$this->cObj->substituteMarkerArrayCached($template, $markerArray);
				} else {
					$out.='<option value="'.intval($key).'"'.(intval($key)==$active?" selected":"").'>'.htmlspecialchars($val["title"]).'</option>';
				}
			}
		}
		if (!$type)	{
			$out=$this->cObj->wrap($out,$wrap);
		}
		return $out;
	}
	function cleanConfArr($confArr,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArr))	{
			reset($confArr);
			while(list($key,$val)=each($confArr))	{
				if (!t3lib_div::testInt($key) && intval($key) && is_array($val) && (!$checkShow || $val["show"] || !isset($val["show"])))	{
					$outArr[intval($key)]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	}
	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	function getBasket($subpartMarker="###BASKET_TEMPLATE###", $templateCode="", $mainMarkerArray=array())	{
//                 debug($subpartMarker,"getBasket():subpartMarker");//MKL
//                 debug($templateCode,"getBasket():templateCode");//MKL
//                 debug($mainMarkerArray,"getBasket():mainMarkerArray");//MKL
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.

				This function also calculates the internal arrays

				$this->calculatedBasket		- The basked elements, how many (quantity, count) and the price and total
				$this->calculatedSums_tax		- Sums of goods, shipping, payment and total amount WITH TAX included
				$this->calculatedSums_no_tax	- Sums of goods, shipping, payment and total amount WITHOUT TAX
				$this->calculatedSums_weight

				... which holds the total amount, the final list of products and the price of payment and shipping!!

			*/

		$templateCode = $templateCode ? $templateCode : $this->templateCode;
		$this->calculatedBasket=array();		// array that holds the final list of items, shipping and payment + total amounts

		// Get the products from the uid_list (initialized by the initBasket function)

	
		
		
		// new query
	 			$query = "select tt_products.uid,tt_products.pid";
	 			$query .= ",tt_products.title,tt_products.note";
	 			$query .= ",tt_products.price,tt_products.price2,tt_products.price2_qty,tt_products.price_factor";
	 			$query .= ",tt_products.unit,tt_products.unit_factor";
	 			$query .= ",tt_products.weight";
	 			$query .= ",tt_products.image,tt_products.datasheet,tt_products.www";
	 			$query .= ",tt_products.itemnumber,tt_products.category";
	 			$query .= ",tt_products.inStock,tt_products.inStock_low,tt_products.on_demand,tt_products.ordered";
	 			$query .= ",tt_products.fe_group";	

 	       		// language ovelay
			if ($this->language > 0) {
				$query .= ",tt_products_language_overlay.title AS o_title";
				$query .= ",tt_products_language_overlay.note AS o_note";
				$query .= ",tt_products_language_overlay.unit AS o_unit";
				$query .= ",tt_products_language_overlay.datasheet AS o_datasheet";
				$query .= ",tt_products_language_overlay.www AS o_www";
			}
			$query .= " FROM tt_products";
			if ($this->language > 0) {
				$query .= " LEFT JOIN tt_products_language_overlay";
				$query .= " ON (tt_products.uid=tt_products_language_overlay.prd_uid";
				$query .= " AND tt_products_language_overlay.sys_language_uid=$this->language";
				$query .= $this->cObj->enableFields("tt_products_language_overlay");
				$query .= ")";
			}
			$query .= " WHERE";
			$query .= " tt_products.uid IN (".$this->uid_list.")";
			$query .= " AND tt_products.pid IN ($this->pid_list) ";
			$query .= $this->cObj->enableFields("tt_products");		
		
// debug( $query, "query");
			
		// old query
	 	// $query = "SELECT * FROM tt_products WHERE uid IN (".$this->uid_list.") AND pid IN (".$this->pid_list.")".$this->cObj->enableFields("tt_products");
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$productsArray=array();
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$productsArray[$row["pid"]][]=$row;		// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
		}


			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t["basketFrameWork"] = $this->cObj->getSubpart($templateCode,$this->spMarker($subpartMarker));
		if (trim($this->cObj->getSubpart($t["basketFrameWork"],"###BILLING_ADDRESS_LOGIN###")))	{
			if ($GLOBALS["TSFE"]->loginUser)	{

				$t["basketFrameWork"]=$this->cObj->substituteSubpart($t["basketFrameWork"], "###BILLING_ADDRESS###", "");
			} else {

				$t["basketFrameWork"]=$this->cObj->substituteSubpart($t["basketFrameWork"], "###BILLING_ADDRESS_LOGIN###", "");

			}
		}

		$t["categoryTitle"] = $this->cObj->getSubpart($t["basketFrameWork"],"###ITEM_CATEGORY###");
		$t["itemFrameWork"] = $this->cObj->getSubpart($t["basketFrameWork"],"###ITEM_LIST###");
		$t["item"] = $this->cObj->getSubpart($t["itemFrameWork"],"###ITEM_SINGLE###");

		$pageArr=explode(",",$this->pid_list);
		$currentP="";
		$out="";

			// Initialize traversing the items in the basket
		$this->calculatedSums_tax=array();
		$this->calculatedSums_no_tax=array();
		$this->calculatedSums_weight = 0;

		while(list(,$v)=each($pageArr))	{
			if (is_array($productsArray[$v]))	{
				reset($productsArray[$v]);
				$itemsOut="";
				while(list(,$row)=each($productsArray[$v]))	{
						// Print Category Title
					if ($row["pid"]."_".$row["category"]!=$currentP)	{
						if ($itemsOut)	{
							$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
						}
						$itemsOut="";			// Clear the item-code var
						$currentP = $row["pid"]."_".$row["category"];
						if ($this->conf["displayBasketCatHeader"])	{
							$markerArray=array();
							$catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]["title"]:"");
							$this->cObj->setCurrentVal($catTitle);
							$markerArray["###CATEGORY_TITLE###"]=$this->cObj->cObjGetSingle($this->conf["categoryHeader"],$this->conf["categoryHeader."], "categoryHeader");
							$out.= $this->cObj->substituteMarkerArray($t["categoryTitle"], $markerArray);
						}
					}

						// Fill marker arrays
					$wrappedSubpartArray=array();
					$markerArray = $this->getItemMarkerArray ($row,$catTitle,1,"basketImage");

					$calculatedBasketItem = array(
						"priceTax" => $this->getPrice($row["price"],$this->vatIncluded,doubleval( $row["price_factor"] )),
						"priceNoTax" => $this->getPrice($row["price"],0,doubleval( $row["price_factor"])),
					    "weight" => doubleval( $row["weight"] ),
						"count" => intval($this->basket[$row["uid"]]),
						"rec" => $row
					);
					$calculatedBasketItem["totalTax"] = $calculatedBasketItem["priceTax"]*$calculatedBasketItem["count"];
					$calculatedBasketItem["totalNoTax"] = $calculatedBasketItem["priceNoTax"]*$calculatedBasketItem["count"];
					$calculatedBasketItem["totalWeight"] = $calculatedBasketItem["weight"]*$calculatedBasketItem["count"];
					$markerArray["###PRICE_TOTAL_TAX###"]=$this->priceFormat($calculatedBasketItem["totalTax"]);
					$markerArray["###PRICE_TOTAL_NO_TAX###"]=$this->priceFormat($calculatedBasketItem["totalNoTax"]);
					$addQueryString=array();
					$addQueryString['tt_products'] = 'tt_products='.$row["uid"];
					$wrappedSubpartArray["###LINK_ITEM###"]=array('<A href="'.$this->getLinkUrl($this->conf["PIDitemDisplay"],"",$addQueryString).'">','</A>');
						// Substitute
					$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);

					$this->calculatedSums_tax["goodstotal"]+= $calculatedBasketItem["totalTax"];
					$this->calculatedSums_no_tax["goodstotal"]+= $calculatedBasketItem["totalNoTax"];
					$this->calculatedSums_weight += $calculatedBasketItem["totalWeight"];
					$this->calculatedBasket[] = $calculatedBasketItem;
				}
				if ($itemsOut)	{
					$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
				}
			}
		}

//		debug($this->calculatedBasket);	
//		debug($this->calculatedSums_weight);	
			// Initializing the markerArray for the rest of the template
		$markerArray=$mainMarkerArray;

			// This is the total for the goods in the basket.
		$markerArray["###PRICE_GOODSTOTAL_TAX###"] = $this->priceFormat($this->calculatedSums_tax["goodstotal"]);
		$markerArray["###PRICE_GOODSTOTAL_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["goodstotal"]);


			// Shipping
		if( $this->vatIncluded )	{
			$this->calculatedSums_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceTax"]);
		}
		else {
			$this->calculatedSums_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceNoTax"]);
		}
		$this->calculatedSums_no_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceNoTax"]);
		$perc = doubleVal($this->basketExtra["shipping."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["shipping"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["shipping"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}
		if ($this->basketExtra["shipping."]["calculationScript"])	{
			$calcScript = $GLOBALS["TSFE"]->tmpl->getFileName($this->basketExtra["shipping."]["calculationScript"]);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra["shipping."]["calculationScript."]);
			}
		}

		$markerArray["###PRICE_SHIPPING_PERCENT###"] = $perc;
		$markerArray["###PRICE_SHIPPING_TAX###"] = $this->priceFormat($this->calculatedSums_tax["shipping"]);
		$markerArray["###PRICE_SHIPPING_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["shipping"]);

		$markerArray["###SHIPPING_SELECTOR###"] = $this->generateRadioSelect("shipping");
		$markerArray["###SHIPPING_IMAGE###"] = $this->cObj->IMAGE($this->basketExtra["shipping."]["image."]);
		$markerArray["###SHIPPING_TITLE###"] = $this->basketExtra["shipping."]["title"];


			// Payment
		if( $this->vatIncluded )	{
			$this->calculatedSums_tax["payment"]=doubleVal($this->basketExtra["payment."]["priceTax"]);
		}
		else {
			$this->calculatedSums_tax["payment"]=doubleVal($this->basketExtra["payment."]["priceNoTax"]);
		}
		$this->calculatedSums_no_tax["payment"]=doubleVal($this->basketExtra["payment."]["priceNoTax"]);
		$perc = doubleVal($this->basketExtra["payment."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["payment"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["payment"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}
//debug($this->basketExtra["payment."]["calculationScript"]);
		if ($this->basketExtra["payment."]["calculationScript"])	{
			$calcScript = $GLOBALS["TSFE"]->tmpl->getFileName($this->basketExtra["payment."]["calculationScript"]);
//debug($calcScript);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra["payment."]["calculationScript."]);
			}
		}

		$markerArray["###PRICE_PAYMENT_PERCENT###"] = $perc;
		$markerArray["###PRICE_PAYMENT_TAX###"] = $this->priceFormat($this->calculatedSums_tax["payment"]);
		$markerArray["###PRICE_PAYMENT_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["payment"]);

		$markerArray["###PAYMENT_SELECTOR###"] = $this->generateRadioSelect("payment");
		$markerArray["###PAYMENT_IMAGE###"] = $this->cObj->IMAGE($this->basketExtra["payment."]["image."]);
		$markerArray["###PAYMENT_TITLE###"] = $this->basketExtra["payment."]["title"];


			// This is the total for everything
		$this->calculatedSums_tax["total"] = $this->calculatedSums_tax["goodstotal"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["payment"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["shipping"];

		$this->calculatedSums_no_tax["total"] = $this->calculatedSums_no_tax["goodstotal"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["payment"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["shipping"];

		$markerArray["###PRICE_TOTAL_TAX###"] = $this->priceFormat($this->calculatedSums_tax["total"]);
		$markerArray["###PRICE_TOTAL_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["total"]);


			// Personal and delivery info:
		// MKL $infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");		// Fields...
		$infoFields = explode(",","forename,name,address,telephone,fax,email,company,city,zip,state,street,street_n1,street_n2,country_code,vat_id");
//debug($infoFields); //MKL
//debug($this->personInfo, "person_info"); //MKL
//debug($this->deliveryInfo, "delivery_info"); //MKL

		while(list(,$fName)=each($infoFields))	{
			if( $fName == "country_code" ) 	{
				$markerArray["###PERSON_".strtoupper($fName)."###"] =
					$this->buildCountrySelector('recs[personinfo][country_code]', '',$this->personInfo["country_code"], '');
				$markerArray["###PERSON_COUNTRY###"] = $this->personInfo["country_code"] ?
				        $this->staticInfo->getStaticInfoName('COUNTRIES', $this->personInfo["country_code"],'','') : '' ;
				$markerArray["###DELIVERY_".strtoupper($fName)."###"] =
					$this->buildCountrySelector('recs[delivery][country_code]', '',$this->deliveryInfo["country_code"], '');
				$markerArray["###DELIVERY_COUNTRY###"] = $this->deliveryInfo["country_code"] ?
				        $this->staticInfo->getStaticInfoName('COUNTRIES', $this->deliveryInfo["country_code"],'','') : '';

			}
			else   {
				$markerArray["###PERSON_".strtoupper($fName)."###"] = $this->personInfo[$fName];
				$markerArray["###DELIVERY_".strtoupper($fName)."###"] = $this->deliveryInfo[$fName];
			}

		}
			// Markers for use if you want to output line-broken address information
		$markerArray["###PERSON_ADDRESS_DISPLAY###"] = nl2br($markerArray["###PERSON_ADDRESS###"]);
		$markerArray["###DELIVERY_ADDRESS_DISPLAY###"] = nl2br($markerArray["###DELIVERY_ADDRESS###"]);
			// Delivery note.
		$markerArray["###DELIVERY_NOTE###"] = $this->deliveryInfo["note"];
		$markerArray["###DELIVERY_NOTE_DISPLAY###"] = nl2br($markerArray["###DELIVERY_NOTE###"]);


			// Order:	NOTE: Data exist only if the getBlankOrderUid() has been called. Therefore this field in the template should be used only when an order has been established
		$markerArray["###ORDER_UID###"] = $this->getOrderNumber($this->recs["tt_products"]["orderUid"]);
		$markerArray["###ORDER_DATE###"] = $this->cObj->stdWrap($this->recs["tt_products"]["orderDate"],$this->conf["orderDate_stdWrap."]);
		$markerArray["###ORDER_TRACKING_NO###"] = $this->recs["tt_products"]["orderTrackingNo"];
        $markerArray["###ORDER_TRACKING_PID###"] = $this->config["orderTrackingPid"];
		
			// Fe users:
		$markerArray["###FE_USER_USERNAME###"] = $GLOBALS["TSFE"]->fe_user->user["username"];
		$markerArray["###FE_USER_UID###"] = $GLOBALS["TSFE"]->fe_user->user["uid"];

			// URL
		$markerArray = $this->addURLMarkers($markerArray);
		$subpartArray = array();
		$wrappedSubpartArray = array();

			// Final substitution:
		if (!$GLOBALS["TSFE"]->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
			$subpartArray["###FE_USER_SECTION###"]="";
		}
		$bFrameWork = $t["basketFrameWork"];
//		debug(array($bFrameWork));
//		debug($this->basketExtra["payment"]); //MKL
		$subpartArray["###MESSAGE_SHIPPING###"] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,"###MESSAGE_SHIPPING_".$this->basketExtra["shipping"]."###"),$markerArray);
		$subpartArray["###MESSAGE_PAYMENT###"] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,"###MESSAGE_PAYMENT_".$this->basketExtra["payment"]."###"),$markerArray);
//		debug($subpartArray["###MESSAGE_PAYMENT###"]); //MKL
		$bFrameWork=$this->cObj->substituteMarkerArrayCached($t["basketFrameWork"],$markerArray,$subpartArray,$wrappedSubpartArray);

			// substitute the main subpart with the rendered content.
		$out=$this->cObj->substituteSubpart($bFrameWork, "###ITEM_CATEGORY_AND_ITEMS###", $out);
		return $out;
	}









	// **************************
	// tracking information
	// **************************

	/**
	 * Returns 1 if user is a shop admin
	 */
	function shopAdmin()	{
		$admin=0;
		if ($GLOBALS["TSFE"]->beUserLogin)	{
			if (t3lib_div::GPvar("update_code")==$this->conf["update_code"])	{
				$admin= 1;		// Means that the administrator of the website is authenticated.
			}
		}
		return $admin;
	}

	/**
	 * Tracking administration
	 */
	function getTrackingInformation($orderRow, $templateCode)	{
			/*



					Tracking information display and maintenance.

					status-values are
					0:	Blank order
					1: 	Order confirmed at website
					...
					50-59:	User messages, may be updated by the ordinary users.
					100-:	Order finalized.


					All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
			*/

		$admin = $this->shopAdmin();

		if ($orderRow["uid"])	{
				// Initialize update of status...
			$fieldsArray = array();
			$orderRecord = t3lib_div::GPvar("orderRecord");
			if (isset($orderRecord["email_notify"]))	{
				$fieldsArray["email_notify"]=$orderRecord["email_notify"];
				$orderRow["email_notify"] = $fieldsArray["email_notify"];
			}
			if (isset($orderRecord["email"]))	{
				$fieldsArray["email"]=$orderRecord["email"];
				$orderRow["email"] = $fieldsArray["email"];
			}

			if (is_array($orderRecord["status"]))	{
				$status_log = unserialize($orderRow["status_log"]);
				reset($orderRecord["status"]);
				$update=0;
				while(list(,$val)=each($orderRecord["status"]))	{
					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$status_log_element = array(
							"time" => time(),
							"info" => $this->conf["statusCodes."][$val],
							"status" => $val,
							"comment" => stripslashes($orderRecord["status_comment"])
						);
						if ($orderRow["email"] && $orderRow["email_notify"])	{
							$this->sendNotifyEmail($orderRow["email"], $status_log_element, t3lib_div::GPvar("tracking"), $this->getOrderNumber($orderRow["uid"]),$templateCode);
						}
						$status_log[] = $status_log_element;
						$update=1;
					}
				}
				if ($update)	{
					$fieldsArray["status_log"]=serialize($status_log);
					$fieldsArray["status"]=$status_log_element["status"];
					if ($fieldsArray["status"] >= 100)	{
							// Deletes any M-M relations between the tt_products table and the order.
							// In the future this should maybe also automatically count down the stock number of the product records. Else it doesn't make sense.
						$query="DELETE FROM sys_products_orders_mm_tt_products WHERE sys_products_orders_uid=".$orderRow["uid"];
						$res = $GLOBALS['TYPO3_DB']->sql_query($query);
					}
				}
	//				debug($fieldsArray);
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				$fieldsArray["tstamp"]=time();
				$query="UPDATE sys_products_orders SET ".$this->getUpdateQuery($fieldsArray)." WHERE uid=".$orderRow["uid"];
	//			debug($query);
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);
				$orderRow = $this->getOrderRecord($orderRow["uid"]);
			}
		}




			// Getting the template stuff and initialize order data.
		$content=$this->cObj->getSubpart($templateCode,"###TRACKING_DISPLAY_INFO###");
		$status_log = unserialize($orderRow["status_log"]);

		$orderData = unserialize($orderRow["orderData"]);

			// Status:
		$STATUS_ITEM=$this->cObj->getSubpart($content,"###STATUS_ITEM###");
		$STATUS_ITEM_c="";
		if (is_array($status_log))	{
			reset($status_log);
			while(list($k,$v)=each($status_log))	{
				$markerArray=Array();
				$markerArray["###ORDER_STATUS_TIME###"]=$this->cObj->stdWrap($v["time"],$this->conf["statusDate_stdWrap."]);
				$markerArray["###ORDER_STATUS###"]=$v["status"];
				$markerArray["###ORDER_STATUS_INFO###"]=$v["info"];
				$markerArray["###ORDER_STATUS_COMMENT###"]=nl2br($v["comment"]);

				$STATUS_ITEM_c.=$this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$subpartArray=array();
		$subpartArray["###STATUS_ITEM###"]=$STATUS_ITEM_c;




		$markerArray=Array();

			// Display admin-interface if access.
		if (!$GLOBALS["TSFE"]->beUserLogin)	{
			$subpartArray["###ADMIN_CONTROL###"]="";
		} elseif ($admin) {
			$subpartArray["###ADMIN_CONTROL_DENY###"]="";
		} else {
			$subpartArray["###ADMIN_CONTROL_OK###"]="";
		}
		if ($GLOBALS["TSFE"]->beUserLogin)	{
				// Status admin:
			if (is_array($this->conf["statusCodes."]))	{
				reset($this->conf["statusCodes."]);
				while(list($k,$v)=each($this->conf["statusCodes."]))	{
					if ($k!=1)	{
						$markerArray["###STATUS_OPTIONS###"].='<option value="'.$k.'">'.htmlspecialchars($k.": ".$v).'</option>';
					}
				}
			}

				// Get unprocessed orders.
			$query="SELECT uid,name,tracking_code,amount from sys_products_orders WHERE NOT deleted AND status!=0 AND status<100 ORDER BY crdate";
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$markerArray["###OTHER_ORDERS_OPTIONS###"].='<option value="'.$row["tracking_code"].'">'.htmlspecialchars($this->getOrderNumber($row["uid"]).": ".$row["name"]." (".$this->priceFormat($row["amount"])." ".$this->currency.")").'</option>';
			}
		}


			// Final things
		$markerArray["###ORDER_HTML_OUTPUT###"] = $orderData["html_output"];		// The save order-information in HTML-format
		$markerArray["###FIELD_EMAIL_NOTIFY###"] = $orderRow["email_notify"] ? " checked" : "";
		$markerArray["###FIELD_EMAIL###"] = $orderRow["email"];
		$markerArray["###ORDER_UID###"] = $this->getOrderNumber($orderRow["uid"]);
		$markerArray["###ORDER_DATE###"] = $this->cObj->stdWrap($orderRow["crdate"],$this->conf["orderDate_stdWrap."]);
		$markerArray["###TRACKING_NUMBER###"] = t3lib_div::GPvar("tracking");
		$markerArray["###UPDATE_CODE###"] = t3lib_div::GPvar("update_code");

		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		return $content;
	}

	/**
	 * Send notification email for tracking
	 */
	function sendNotifyEmail($recipient, $v, $tracking, $uid, $templateCode)	{

// <--- Miklobit 20011.12.17 - send mail using swift mailer API			

		  $recipients = $recipient;
		  $recipients .= ",".$this->conf["orderEmail_to"];
		  $recipients=t3lib_div::trimExplode(",",$recipients,1);	  
		  $toEMail = array();
		  foreach ($recipients as $email) {
				$toEMail[] = $email;
		  }	

		  if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->cObj->getSubpart($templateCode,"###TRACKING_EMAILNOTIFY_TEMPLATE###"));
			if ($emailContent)	{		// If there is plain text content - which is required!!

				$markerArray["###ORDER_STATUS_TIME###"]=$this->cObj->stdWrap($v["time"],$this->conf["statusDate_stdWrap."]);
				$markerArray["###ORDER_STATUS###"]=$v["status"];
				$markerArray["###ORDER_STATUS_INFO###"]=$v["info"];
				$markerArray["###ORDER_STATUS_COMMENT###"]=$v["comment"];

				$markerArray["###ORDER_TRACKING_NO###"]=$tracking;
				$markerArray["###ORDER_TRACKING_PID###"] = $this->config["orderTrackingPid"];
				$markerArray["###ORDER_UID###"]=$uid;

				$emailContent=$this->cObj->substituteMarkerArrayCached($emailContent, $markerArray);

				$parts = split(chr(10),$emailContent,2);
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

		        $mail = t3lib_div::makeInstance('t3lib_mail_message');
		        $mail->setFrom(array($this->conf["orderEmail_from"] => $this->conf["orderEmail_fromName"]));			
		        $mail->setTo($toEMail);
		        $mail->setSubject($subject);
		        $mail->setBody($plain_message, 'text/plain', $GLOBALS['TSFE']->renderCharset);
		        $mail->send();
			}
		}		  
		  
// --> send mail	

	}

	/**
	 * Buils a HTML drop-down selector of countries
	 * replacement for  buildStaticInfoSelector function from StaticInfo extension because we
	 * want to show country selector without default selection
	 *
	 * @param	string		A value for the name attribute of the <select> tag
	 * @param	string		A value for the class attribute of the <select> tag
	 * @param	string		The value of the code of the entry to be pre-selected in the drop-down selector: value of cn_iso_3, zn_code, cu_iso_3 or lg_iso_2
	 * @param	string		The value of the country code (cn_iso_3) for which a drop-down selector of type 'SUBDIVISIONS' is requested (meaningful only in this case)
	 * @param	boolean/string		If set to 1, an onchange attribute will be added to the <select> tag for immediate submit of the changed value; if set to other than 1, overrides the onchange script
	 * @return	string		A set of HTML <select> and <option> tags
	 */
	function buildCountrySelector($name='', $class='', $selected='', $country='', $submit=0)	{

		$nameAttribute = (trim($name)) ? 'name="'.trim($name).'" ' : '';
		$classAttribute = (trim($class)) ? 'class="'.trim($class).'" ' : '';
		$onchangeAttribute = '';
		if( $submit ) {
			if( $submit == 1 ) {
				$onchangeAttribute = 'onchange="'.$this->staticInfo->conf['onChangeAttribute'].'" ';
			} else {
				$onchangeAttribute = 'onchange="'.$submit.'" ';
			}
		}
		$selector = '<select size="1" '.$nameAttribute.$classAttribute.$onchangeAttribute.'>'.chr(10);


		$names = $this->staticInfo->initCountries();
		$selected = (trim($selected)) ? trim($selected) : $this->staticInfo->defaultCountry;

		$allowed = array();
		$excluded = array();
		if( $this->config["allowedCountry"] != '' || $this->config["excludedCountry"] != '' )   {
			if( $this->config["allowedCountry"] != '' ) {
				$allowed = explode(",",$this->config["allowedCountry"]);
			}
			if( $this->config["excludedCountry"] != '' ) {				
				$excluded = explode(",",$this->config["excludedCountry"]);
			}				
			reset($names);
			while(list($key,$name)=each($names))	{
				if ( (count($allowed) > 0) && (! in_array($key, $allowed))) {
					unset($names[$key]);
				} 
				if ( in_array($key, $excluded)) {
					unset($names[$key]);
				} 				
			}
			if( count($allowed) == 1 ) {
				$selected = $allowed[0] ; 
			}		   
		}
 		
		if( count($names) > 0 )	{
			$selector .= '<option value=""></option>'.chr(10);
			$selector .= $this->staticInfo->optionsConstructor($names, $selected);
			$selector .= '</select>'.chr(10);
		} else {
			$selector = '';
		}
		return $selector;
	}

}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/mkl_products/pi/class.tx_ttproducts.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/mkl_products/pi/class.tx_ttproducts.php"]);
}


?>