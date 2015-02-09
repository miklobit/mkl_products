<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

if (TYPO3_MODE=="BE")   include_once(t3lib_extMgm::extPath("mkl_products")."class.tx_ttproducts_language.php");

t3lib_div::loadTCA("tt_content");
$TCA["tt_content"]["types"]["list"]["subtypes_excludelist"]["5"]="layout,select_key";
$TCA["tt_content"]["types"]["list"]["subtypes_addlist"]["5"]="pi_flexform";
t3lib_extMgm::addPiFlexFormValue('5', 'FILE:EXT:mkl_products/flexform_ds_pi.xml');


$TCA["tt_products"] = Array (
	"ctrl" => Array (
		"label" => "title",
        "sortby" => "sorting",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"delete" => "deleted",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime",
			"fe_group" => "fe_group",
		),
		"thumbnail" => "image",
		"useColumnsForDefaultValues" => "category",
		"mainpalette" => 1,
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products.gif",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php"
	)
);
$TCA["tt_products_language_overlay"] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products_language_overlay.gif",
	)
);

$TCA["tt_products_post_rates"] = Array (
	"ctrl" => Array (		
        "title" => 'LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates',
		"tstamp" => "tstamp",
		"label" => "weight_limit",
//		"label_alt" => "a1_rate,a2_rate",
//		"label_alt_force" => 1,
    	        "rootLevel" => 1,	
		"default_sortby" => "ORDER BY weight_limit",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden,weight_limit,local_rate,a1_rate,a2_rate,a3_rate,a4_rate,a5_rate,b_rate,c_rate,d_rate",
	)	
);
//$TCA['tt_products_post_rates']['ctrl']['readOnly'] = 0;
//$TCA['tt_products_post_rates']['ctrl']['adminOnly'] = 1;




$TCA["tt_products_cat"] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products_cat.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, title",
	)
);
$TCA["tt_products_cat_language_overlay"] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat_language_overlay",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products_cat_language_overlay.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden,cat_uid, sys_language_uid,title",
	)
);

t3lib_extMgm::addPlugin(Array("LLL:EXT:mkl_products/locallang_tca.php:tt_content.list_type_pi1","5"),"list_type");
t3lib_extMgm::addPlugin(Array("LLL:EXT:mkl_products/locallang_tca.php:tt_products", "5"));
t3lib_extMgm::allowTableOnStandardPages("tt_products");
t3lib_extMgm::allowTableOnStandardPages("tt_products_language_overlay");
t3lib_extMgm::allowTableOnStandardPages("tt_products_cat");
t3lib_extMgm::allowTableOnStandardPages("tt_products_cat_language_overlay");
t3lib_extMgm::addToInsertRecords("tt_products");
if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_ttproducts_wizicon"] = t3lib_extMgm::extPath($_EXTKEY)."class.tx_ttproducts_wizicon.php";
?>