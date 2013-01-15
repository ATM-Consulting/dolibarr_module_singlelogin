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
 * 	\file		admin/singlelogin.php
 * 	\ingroup	singlelogin
 * 	\brief		This file is  setup page
 * 				
 */


// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if ( ! $res)
		$res = @include("../../../main.inc.php"); // From "custom" directory

	
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

dol_include_once('/singlelogin/lib/singlelogin.lib.php');

// Translations
$langs->load("singlelogin@singlelogin");

$mesg=''; $error=0; $errors=array();

// Access control
if ( ! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');


if ($action == 'setvar')
{
	$timeout=GETPOST('SINGLE_LOGIN_TIMEOUT','int');
	$res = dolibarr_set_const($db, 'SINGLE_LOGIN_TIMEOUT', $timeout,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
	$errmesg=GETPOST('SINGLE_LOGIN_ERRMSG','alpha');
	$res = dolibarr_set_const($db, 'SINGLE_LOGIN_ERRMSG', $errmesg,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
	$useradmin=GETPOST('SINGLE_LOGIN_SUPERUSER_ID','int');
	if (!empty($useradmin)) {
		$adminuser=new User($db);
		$adminuser->fetch($useradmin);
		if (empty($adminuser->email)) {
			setEventMessage($langs->trans('SLMailAdminMandatory'), 'errors'); 
		}else {
			$res = dolibarr_set_const($db, 'SINGLE_LOGIN_SUPERUSER_ID', $useradmin,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
		}
	}
}

if ($action == 'setvaruseadmin')
{
	$useadmin=GETPOST('SINGLE_LOGIN_SUPERUSER_USE','int');
	$res = dolibarr_set_const($db, 'SINGLE_LOGIN_SUPERUSER_USE', $useadmin,'yesno',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
}

/*
 * Actions
 */

/*
 * View
 */
$page_name = "SLSetup";
llxHeader('', $langs->trans($page_name));

$form=new Form($db);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = singlelogin_admin_prepare_head();
dol_fiche_head($head, 'settings', $langs->trans("Module10055Name"), 0,"singlelogin@singlelogin");

// Setup page goes here
echo $langs->trans("SLSetupPage");


print '<table class="noborder" width="100%">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvar">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Valeur").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
print "</tr>\n";

//Time out
print '<tr class="pair"><td>'.$langs->trans("SLTimeOut").'</td>';
print '<td align="left">';
print '<input type="text" name="SINGLE_LOGIN_TIMEOUT" value="'.$conf->global->SINGLE_LOGIN_TIMEOUT.'" size="5" >'.$langs->trans("SLMinutes").'</td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("SLTimeOutHelp"),1,'help');
print '</td>';
print '</tr>';

//SessionErrorMessage
print '<tr class="pair"><td>'.$langs->trans("SLErrContactAdminMess").'</td>';
print '<td align="left">';
if (empty($conf->global->SINGLE_LOGIN_ERRMSG)) {
	$value = $langs->trans('SLErrContactAdmin'); 
} else {
	$value = $conf->global->SINGLE_LOGIN_ERRMSG;
}
print '<input type="text" name="SINGLE_LOGIN_ERRMSG" value="'.$value.'" size="100" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("SLErrContactAdminMessHelp"),1,'help');
print '</td>';
print '</tr>';


//USe super user
print '<tr class="pair"><td>'.$langs->trans("SLUseSuperUser").'</td>';
print '<td align="left">';
if (!$conf->global->SINGLE_LOGIN_SUPERUSER_USE) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvaruseadmin&SINGLE_LOGIN_SUPERUSER_USE=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
} else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvaruseadmin&SINGLE_LOGIN_SUPERUSER_USE=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("SLUseSuperUserHelp"),1,'help');
print '</td>';
print '</tr>';

if ($conf->global->SINGLE_LOGIN_SUPERUSER_USE) {
	//Admin users
	print '<tr class="impair"><td>'.$langs->trans("SLAdminUser").'</td>';
	print '<td align="left">';
	
	//Select only useres with email 
	
	$exclude_array = array();
	
	$sql = "SELECT u.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql.= " WHERE (u.email IS NULL) OR u.email=\"\"";
	
	$resql=$db->query($sql);
	if ($resql) {
		
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$exclude_array[]=$obj->rowid;
			$i++;
		}
		$db->free($resql);
	}
	$form->select_users($conf->global->SINGLE_LOGIN_SUPERUSER_ID,'SINGLE_LOGIN_SUPERUSER_ID',1,$exclude_array);
	print '</td>';
	print '<td align="left">';
	print $form->textwithpicto('',$langs->trans("SLMailAdminMandatory"),1,'help');
	print '</td>';
	print '</tr>';
}

print '</table>';
print '</form>';

llxFooter();

$db->close();