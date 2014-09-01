<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * modify date string to formatted date
 * 
 * Type:     modifier<br>
 * Name:     date<br>
 * Purpose:  format dates
 *
 * @param DateTime $date date to format
 * @param string $format resulting format
 * @return string formatted date
 * @author Ricardo Schmidt <ricardo.schmidt@tyclipso.net>
 */
function smarty_modifier_date($date, $type = 'date') {
	$const = 'utils::DATETIME_FMT_'.strtoupper($type);
    if (defined($const)) {
        $format = constant($const);
    } else {
        $format = $type;
    }
	if( ( $date instanceof DateTime ) === false ) {
		$date = new DateTime($date);
	}
	return utils::formatDate($date, $format);
}
?>