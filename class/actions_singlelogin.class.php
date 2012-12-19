<?php
/* Copyright (C) 2012	Florian Henry	<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file 		/singlelogin/class/actions_singlelogin.class.php
 *	\ingroup    singlelogin
 *	\brief      File of class to manage advanced singlelogin
 */


/**
 * 	\class 		ActionsSinglelogin
 *	\brief      Class to manage advanced singlelogin
 */
class ActionsSinglelogin
{
	var $db;

	var $error;
	var $errors=array();

	var $elements=array();
	private $element=array();
	
	/**
	 *  Constructor
	 *
	 *	@param	DoliDB	$db			Database handler
	 */
	function __construct($db)
	{
		$this->db = $db ;
		$this->error = 0;
		$this->errors = array();
	}
	
	
	/**
	 * 	Return action of hook
	 *
	 *	@param	array	$parameters		Linked object
	 *	@return	string
	 */
	function printLeftBlock($parameters=false) {
		
		global $conf,$user,$langs;
		
		$langs->load("singlelogin@singlelogin");
		
		$out = '';
		if (($user->admin) || ($user->rights->singlelogin->read)) {
			$out = '<div class="blockvmenupair">';
			$out .= '<div class="menu_titre">';
			$out .= '<a href="'.dol_buildpath('/singlelogin/admin/killsession.php',1).'">'.$langs->trans('SLKillSession').'</a>';
			$out .= '</div>';
			$out .= '</div>';
		}
		return $out; 
		
	}
}