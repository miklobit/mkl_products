<?php
/***************************************************************
*  Copyright notice
*  
*  orginal (c) 2003 Karsten Hachmeister (webmaster@hachmeister.org)
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
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Class/Function which manipulates the item-array for table/field tt_products_language_overlay.sys_language_uid.
 *
 * @author  Milosz Klosowicz <typo3@miklobit.com>
 */



class tx_ttproducts_language {
	function main(&$params,&$pObj)	{
	/*
	debug("Hello World!",1);
	debug("\$params:",1);
	debug($params);
	debug("\$pObj:",1);
	debug($pObj);
	*/							

	// Adding an item!
	$query = "SELECT uid,title ".
                         "FROM sys_language ".
                         //"WHERE ".$GLOBALS["TSFE"]->cObj->enableFields("sys_language").
                         " ORDER BY title";
                $res = mysql(TYPO3_db, $query);

                unset($params["items"]["0"]);

                while ($row = mysql_fetch_array($res)) {
                        $params["items"][]=Array($row["title"], $row["uid"]);
                }
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/mkl_products/class.tx_ttproducts_language.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/mkl_products/class.tx_ttproducts_language.php"]);
}

?>