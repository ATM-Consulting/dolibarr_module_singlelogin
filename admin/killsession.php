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
dol_include_once('/singlelogin/class/singlelogin.class.php');
dol_include_once('/singlelogin/lib/singlelogin.lib.php');

// Translations
$langs->load("admin");
$langs->load("singlelogin@singlelogin");

$mesg=''; $error=0; $errors=array();

// Access control
if ((! $user->rights->singlelogin->read) && (! $user->admin)) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');


if ($action == 'confirm_delete')
{
	//Get all session
	$singlelogin = new SingleLogin($db);
	$singlelogin->id=$id;
	$result=$singlelogin->delete($user,1);
	if ($result < 0) {
		setEventMessage($singlelogin->error, 'errors');
	}else {
		setEventMessage($langs->trans('SLConfirmDelete'), 'mesgs');
	}
}

/*
 * Actions
 */

/*
 * View
 */
$page_name = "SLKillSession";
llxHeader('', $langs->trans($page_name));



$form=new Form($db);

// Subheader
if ($user->admin) {
	$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
		. $langs->trans("BackToModuleList") . '</a>';
	print_fiche_titre($langs->trans($page_name), $linkback);
	$head = singlelogin_admin_prepare_head();
	dol_fiche_head($head, 'killsession', $langs->trans("Module10055Name"), 0,"singlelogin@singlelogin");
}else {
	print_fiche_titre($langs->trans($page_name));
}



/*
 * Confirmation de la suppression
*/
if ($action == 'delete')
{
	$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("SLUnlockSession"),$langs->trans("SLUnlockSession"),"confirm_delete",'','',1);
	if ($ret == 'html') print '<br>';
}

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("SLUserLogin").'</td>';
print '<td>'.$langs->trans("SLLoginTime").'</td>';
print '<td>'.$langs->trans("SLLastAction").'</td>';
print '<td>'.$langs->trans("SLUnlock").'</td>';
print "</tr>\n";

//Get all session
$singlelogin = new SingleLogin($db);
$result=$singlelogin->fetch_all();
if ($result < 0) {
	setEventMessage($singlelogin->error, 'errors');
}

$style='impair';
foreach($singlelogin->lines as $line) {
	if ($style=='pair') {$style='impair';}
	else {$style='pair';}
	
	print '<tr class="'.$style.'">';
	print '<td>'.$line->login.' - '.$line->lastname.' '.$line->firstname.'</td>';
	print '<td>'.dol_print_date($line->datel,'dayhourtext').'</td>';
	print '<td>'.dol_print_date($line->datem,'dayhourtext').'</td>';
	print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$line->id.'">'.img_delete().'</a></td>';
	print '</tr>';
}

print '</table>';

llxFooter();

$db->close();