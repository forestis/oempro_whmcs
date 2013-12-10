<?php
/**
 * Oempro Subscribe Add-on
 *
 * @package    Oempro_Subscribe
 * @author     Octeth <support@octeth.com>
 * @copyright  Copyright (c) Octeth 1999-2013
 * @license    http://www.octeth.com
 * @version    $Id$
 * @link       http://octeth.com/
 */

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");



function hook_oempro($Variables)
{
	include_once('oempro_subscribe.php');
	$OemproSubscribe = new oempro_subscribe();
	$OemproSubscribe->get_configuration();

	$OemproSubscribe->subscribe($Variables);
}

add_hook('ClientAdd', 1, 'hook_oempro');