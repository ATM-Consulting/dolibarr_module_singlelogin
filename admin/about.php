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
 * 	\file		admin/about.php
 * 	\ingroup	mymodule
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if ( ! $res)
	$res = @include("../../../main.inc.php"); // From "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/singlelogin/lib/singlelogin.lib.php');
dol_include_once('/singlelogin/lib/PHP Markdown 1.0.1o/markdown.php');

//require_once "../class/myclass.class.php";
// Translations
$langs->load("singlelogin@singlelogin");

// Access control
if ( ! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

/*
 * View
 */
$page_name = "SLAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = singlelogin_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Module10055Name"), 0,"singlelogin@singlelogin");

// About page goes here
echo $langs->trans("SLAboutPage");

$buffer = file_get_contents(dol_buildpath('/singlelogin/README.md',0));
print Markdown($buffer);

print '<BR>';

print '<img src="'.dol_buildpath('/singlelogin/img/gplv3.png',1).'"/>';

print '<a href="'.dol_buildpath('/singlelogin/COPYING',1).'">License GPL V 3</a>';




llxFooter();

$db->close();