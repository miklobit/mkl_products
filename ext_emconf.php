<?php

########################################################################
# Extension Manager/Repository config file for ext "mkl_products".
#
# Auto generated 10-12-2011 15:31
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop system (extended by MikloBit)',
	'description' => 'Extension ( and replacement ) of tt_products:
			 - multilanguage categories and products,
			 - multicurrency,
			 - static url handling,
			 - css styled product templates,
			 - sortable products,
			 - hierarchical categories,
			 - RTE for product/category description,
			 - VAT handling in EU transaction,
			 - payment gateway to AllPay.pl
			 Must be used as "tt_products"!',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,sr_static_info,mkl_currxrate',
	'conflicts' => 'tt_products',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_mklproducts/datasheet',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Milosz Klosowicz',
	'author_email' => 'typo3@miklobit.com',
	'author_company' => 'MikloBit <www.miklobit.com>',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.1.1',
	'_md5_values_when_last_written' => 'a:33:{s:32:"class.tx_ttproducts_language.php";s:4:"aae0";s:31:"class.tx_ttproducts_wizicon.php";s:4:"e3c4";s:12:"ext_icon.gif";s:4:"f5c6";s:14:"ext_tables.php";s:4:"9839";s:14:"ext_tables.sql";s:4:"3e12";s:28:"ext_typoscript_constants.txt";s:4:"d706";s:24:"ext_typoscript_setup.txt";s:4:"abae";s:18:"flexform_ds_pi.xml";s:4:"80d9";s:23:"icon_tx_tt_products.gif";s:4:"1ebd";s:27:"icon_tx_tt_products_cat.gif";s:4:"f852";s:44:"icon_tx_tt_products_cat_language_overlay.gif";s:4:"d4fe";s:40:"icon_tx_tt_products_language_overlay.gif";s:4:"9d4e";s:13:"locallang.php";s:4:"8b97";s:17:"locallang_tca.php";s:4:"3d2f";s:15:"productlist.gif";s:4:"a6c1";s:7:"tca.php";s:4:"c777";s:15:"doc/history.txt";s:4:"8bcb";s:14:"doc/manual.sxw";s:4:"0e7f";s:26:"pi/class.tx_ttproducts.php";s:4:"1eaf";s:21:"pi/payment_ALLPAY.php";s:4:"f8b5";s:34:"pi/payment_ALLPAY_template_en.tmpl";s:4:"a4db";s:34:"pi/payment_ALLPAY_template_pl.tmpl";s:4:"8a60";s:19:"pi/payment_DIBS.php";s:4:"a4b8";s:32:"pi/payment_DIBS_template_uk.tmpl";s:4:"96f9";s:31:"pi/products_comp_calcScript.inc";s:4:"046b";s:20:"pi/products_mail.inc";s:4:"9de5";s:35:"pi/products_shipping_calcScript.inc";s:4:"fe4d";s:24:"pi/products_template.css";s:4:"e061";s:25:"pi/products_template.tmpl";s:4:"47dd";s:28:"pi/products_template_en.tmpl";s:4:"d1c0";s:34:"pi/products_template_htmlmail.tmpl";s:4:"aa8a";s:28:"pi/products_template_pl.tmpl";s:4:"4d85";s:16:"pi/icons/pdf.gif";s:4:"d953";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'sr_static_info' => '',
			'mkl_currxrate' => '',
			'php' => '3.0.0-0.0.0',
			'typo3' => '3.5.0-0.0.0',
		),
		'conflicts' => array(
			'tt_products' => '',
		),
		'suggests' => array(
		),
	),
);

?>