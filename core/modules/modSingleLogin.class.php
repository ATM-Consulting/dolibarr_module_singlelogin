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
 * 	\defgroup	singlelogin	Single Login module
 * 	\brief		Unique connexion per login.
 * 	\file		core/modules/modSingleLogin.class.php
 * 	\ingroup	mymodule
 * 	\brief		Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module MyModule
 */
class modSingleLogin extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero = 103550;
		$this->rights_class = 'singlelogin';
		$this->family = "other";
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Unique connexion per login";
		$this->version = '1.9';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->special = 3;
		$this->picto = 'singlelogin@singlelogin'; 
		$this->module_parts = array(
			'triggers' => 1,
			'hooks' => array('leftblock'));
		$this->dirs = array();
		$this->config_page_url = array("singlelogin.php@singlelogin");
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(5, 3);
		$this->need_dolibarr_version = array(3, 6);
		$this->langfiles = array("singlelogin@singlelogin"); 
		$this->const = array(
				0=>array(
					'SINGLE_LOGIN_TIMEOUT',
					'chaine',
					'10',
					'Time in minute before auto kill a session',
					0,
					'current',
					0
				),
				1=>array(
					'SINGLE_LOGIN_SUPERUSER_ID',
					'chaine',
					'1',
					'User that can override the protection',
					0,
					'current',
					1
				),
				2=>array(
					'SINGLE_LOGIN_SUPERUSER_USE',
					'chaine',
					'1',
					'if set to yes all user that have right to admin are allow to  override protection',
					0,
					'current',
					1
					),
				3=>array(
					'SINGLE_LOGIN_ERRMSG',
					'chaine',
					'',
					'if set use this error message rather than the one set in lag files',
					0,
					'current',
					1
				),
				4=>array(
					'MAIN_ACTIVATE_UPDATESESSIONTRIGGER',
					'chaine',
					'1',
					'Use to update connexion time',
					0,
					'current',
					1
				)
		
		
		);

		$this->tabs = array();

		// Dictionnaries
	  if (! isset($conf->singlelogin->enabled)) {
        	$conf->singlelogin = ( object ) array ();
            $conf->singlelogin->enabled = 0;
        }
		$this->dictionnaries = array();

		// Boxes
		$this->boxes = array(); // Boxes list
		$r = 0;

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;
		$this->rights[$r][0] = 1035501;
		$this->rights[$r][1] = 'Admin for unlock session';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;

	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	function init($options = '')
	{
		$sql = array();

		$result = $this->load_tables();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/singlelogin/sql/');
	}

}