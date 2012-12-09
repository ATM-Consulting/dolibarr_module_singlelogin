<?php
/* Single Login At One Time module for Dolibarr
 * Copyright (C) 2012		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/singlelogin.lib.php
 *	\ingroup	singlemodule
 *	\brief		lib for singlelogin module
 */

function singlelogin_admin_prepare_head()
{
	global $langs, $conf;
	
	$langs->load("singlelogin@singlelogin");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/singlelogin/admin/singlelogin.php",1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/singlelogin/admin/killsession.php",1);
	$head[$h][1] = $langs->trans("SLKillSession");
	$head[$h][2] = 'killsession';
	$h++;
	$head[$h][0] = dol_buildpath("/singlelogin/admin/about.php",1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'singlelogin');

	return $head;
}