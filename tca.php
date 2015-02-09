<?php

// ******************************************************************
// This is the standard TypoScript address table, tt_address
// ******************************************************************
$TCA["tt_products"] = Array (
	"ctrl" => $TCA["tt_products"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "title,itemnumber,price,price2,note,category,inStock,image,hidden,starttime,endtime"
	),
	"columns" => Array (	
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check"
			)
		),
		"fe_group" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),					
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.note",
			"config" => Array (
				"type" => "text",
				"cols" => "48",	
				"rows" => "5"
			)
		),
		"weight" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.weight",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),		
		"price" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.price",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"price2" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.price2",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"price2_qty" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.price2_qty",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"eval" => "int",
				"default" => "0",				
				"max" => "6"				
			)
		),		
		"price_factor" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.price_factor",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
		        "default" => "1.00",
				"max" => "20"
			)
		),	
		"unit_factor" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.unit_factor",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"eval" => "int",
				"default" => "1",				
				"max" => "6"				
			)
		),		
		"unit" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.unit",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "20"
			)
		),		
		"www" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.www",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
		"itemnumber" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.itemnumber",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "40"
			)
		),
		"category" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.category",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0)
				),
				"foreign_table" => "tt_products_cat"
			)
		),
		"inStock" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.inStock",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"range" => Array (
					"lower" => -1
				)
			)
		),
		"inStock_low" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.inStock_low",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"range" => Array (
					"lower" => -1
				)
			)
		),	
		"on_demand" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.on_demand",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"range" => Array (
					"lower" => -1
				)
			)
		),			
		"image" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.image",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
				"max_size" => "10000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "10",
				"maxitems" => "20",
				"minitems" => "0"
			)
		),
		"datasheet" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.datasheet",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "pdf",
				"max_size" => "10000",
				"uploadfolder" => "uploads/tx_mklproducts/datasheet",
				"show_thumbs" => "1",
				"size" => "1",
				"maxitems" => "1",
				"minitems" => "0"
			)
		)		
	),
	"types" => Array (	
		"1" => Array("showitem" => "hidden;;;;1-1-1, title;;3;;3-3-3, itemnumber, category;;4 , price;;2, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/mkl_products/rte/], image;;;;4-4-4,datasheet")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group"),
		"2" => Array("showitem" => "price2, price2_qty, price_factor, weight, inStock, inStock_low, on_demand "),
		"3" => Array("showitem" => "www"),
		"4" => Array("showitem" => "unit_factor, unit")		
	)
);

$TCA["tt_products_language_overlay"] = Array (
	"ctrl" => $TCA["tt_products_language_overlay"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,prd_uid,sys_language_uid,title,note,www"
	),
	"feInterface" => $TCA["tt_products_language_overlay"]["feInterface"],
	"columns" => Array (	
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check"
			)
		),
		"prd_uid" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay.prd_uid",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tt_products",	
				"foreign_table_where" => "AND tt_products.pid=###CURRENT_PID### ORDER BY tt_products.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"sys_language_uid" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay.sys_language_uid",		
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay.sys_language_uid.I.0", "0"),
				),
				"itemsProcFunc" => "tx_ttproducts_language->main",
			)
		),
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"unit" => Array (
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products.unit",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "20"
			)
		),			
		"note" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.note",
			"config" => Array (
				"type" => "text",
				"cols" => "48",	
				"rows" => "5"
			)
		),
		"datasheet" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay.datasheet",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "pdf",
				"max_size" => "10000",
				"uploadfolder" => "uploads/tx_mklproducts/datasheet",
				"show_thumbs" => "1",
				"size" => "1",
				"maxitems" => "1",
				"minitems" => "0"
			)
		),				
		"www" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.www",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
	),
	"types" => Array (	
		"0" => Array("showitem" => "hidden;;;;1-1-1, prd_uid;;;;2-2-2, sys_language_uid,title,unit;;;;3-3-3,note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/mkl_products/rte/],datasheet,www")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);


$TCA["tt_products_post_rates"] = Array (
	"ctrl" => $TCA["tt_products_post_rates"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,weight_limit,local_rate,a1_rate,a2_rate,a3_rate,a4_rate,a5_rate,b_rate,c_rate,d_rate"
	),
	"feInterface" => $TCA["tt_products_post_rates"]["feInterface"],	
	"columns" => Array (	
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),	
		"weight_limit" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.weight_limit",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20",
                "default" > "00.0"
			)
		),		
		"local_rate" => Array (
		    "exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.local_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),		
		"a1_rate" => Array (
		    "exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.a1_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"a2_rate" => Array (
		    "exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.a2_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),	
		"a3_rate" => Array (
		    "exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.a3_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"a4_rate" => Array (
		    "exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.a4_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"a5_rate" => Array (
		    			"exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.a5_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"b_rate" => Array (
					"exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.b_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"c_rate" => Array (
					"exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.c_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"d_rate" => Array (
					"exclude" => 0,	
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_post_rates.d_rate",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim,double2",
				"max" => "20"
			)
		)												
	),
	"types" => Array (	
		"0" => Array("showitem" => "hidden;;1;;1-1-1, weight_limit, local_rate, a1_rate, a2_rate, a3_rate, a4_rate, a5_rate, b_rate, c_rate, d_rate")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)	

);



// ******************************************************************
// This is the standard TypoScript products category table, tt_products_cat
// ******************************************************************
$TCA["tt_products_cat"] = Array (
	"ctrl" => $TCA["tt_products_cat"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,title"
	),
	"feInterface" => $TCA["tt_products_cat"]["feInterface"],
	"columns" => Array (	
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
                                                                "default" => "0"
			)
		),
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.note",
			"config" => Array (
				"type" => "text",
				"cols" => "48",	
				"rows" => "5"
			)
		),
		"image" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.image",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
				"max_size" => "1000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "6",
				"minitems" => "0"
			)
		),		
		"parent_cat" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat.parent_cat",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "tt_products_cat",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		)
		
	),
	"types" => Array (	
//		"0" => Array("showitem" => "hidden;;;;1-1-1, title,parent_cat;;;;3-3-3")
		"0" => Array("showitem" => "hidden;;;;1-1-1, title;;;;3-3-3, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/mkl_products/rte/], image;;;;4-4-4, parent_cat;;;;3-3-3")		
	)
);

// ******************************************************************
// This is the language overlay for  products category table, tt_products_cat
// ******************************************************************
$TCA["tt_products_cat_language_overlay"] = Array (
	"ctrl" => $TCA["tt_products_cat_language_overlay"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,cat_uid,sys_language_uid,title"
	),
	"feInterface" => $TCA["tt_products_cat_language_overlay"]["feInterface"],
	"columns" => Array (	
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
                                                                "default" => "0"
			)
		),
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.note",
			"config" => Array (
				"type" => "text",
				"cols" => "48",	
				"rows" => "5"
			)
		),		
		"cat_uid" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat_language_overlay.cat_uid",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tt_products_cat",	
				"foreign_table_where" => "AND tt_products_cat.pid=###CURRENT_PID### ORDER BY tt_products_cat.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"sys_language_uid" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat_language_overlay.sys_language_uid",		
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat_language_overlay.sys_language_uid.I.0", "0"),
				),
				"itemsProcFunc" => "tx_ttproducts_language->main",
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "hidden;;;;1-1-1, cat_uid;;;;2-2-2, sys_language_uid, title;;;;3-3-3, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/mkl_products/rte/]")

	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);




?>