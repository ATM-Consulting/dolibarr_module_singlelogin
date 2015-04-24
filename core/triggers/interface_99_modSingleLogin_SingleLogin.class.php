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
 * 	\file		singlelogin/core/triggers/interface_99_modSingleLogin_SingleLogin.class.php
 * 	\ingroup	singlelogin
 * 	\brief		Control at each login
 * 	\remarks	You can create other triggers by copying this one
 */

/**
 * Trigger class
 */
class InterfaceSingleLogin
{
	var $db;
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)

	/**
	 * Constructor
	 *
	 * 	@param		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "singlelogin";
		$this->description = "On each load page this trigger page is executed to test is login isn't already logged with an another session";
		$this->version = 'dolibarr';
		$this->picto = 'singlelogin@singlelogin';
	}

	/**
	 * Trigger name
	 *
	 * 	@return		string	Name of trigger file
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * 	@return		string	Description of trigger file
	 */
	function getDesc()
	{
		return $this->description;
	}

	/**
	 * Trigger version
	 *
	 * 	@return		string	Version of trigger file
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental')
				return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * 	@param		string		$action		Event action code
	 * 	@param		Object		$object		Object
	 * 	@param		User		$user		Object user
	 * 	@param		Translate	$langs		Object langs
	 * 	@param		conf		$conf		Object conf
	 * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function run_trigger($action, $object, $user, $langs, $conf)
	{	
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		
		$error=0;
		
		$run_this_trigger=true;
		
		if (defined('NOLOGIN') || (defined('NOREQUIREHTML') && $action!='USER_LOGOUT')) {
			$run_this_trigger=false;
		}
		if ((!is_object($object)) || (!is_object($conf)) || (!is_object($langs))) {
			$run_this_trigger=false;
		}
		
		if ($run_this_trigger) {
			dol_include_once('/singlelogin/class/singlelogin.class.php');
			$langs->load("singlelogin@singlelogin");
		}

		if ((($action == 'USER_UPDATE_SESSION') || ($action == 'USER_LOGIN')) && ($run_this_trigger)) {
		
			dol_include_once('/core/class/users.class.php');
			
			$first_connect=false;
			
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
			
			//Test if user is admin is this case we do not test at all
			if (($object->admin)) {
				dol_syslog("Trigger '" . $this->name . "' for action '$action' aborted because user logged is admin");
				return 1;
			}
			
			// If super admin is use
			if ($conf->global->SINGLE_LOGIN_SUPERUSER_USE) {
				//If the current user is the authorize super admin, the do not do test
				if ($object->id==$conf->global->SINGLE_LOGIN_SUPERUSER_ID) {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' aborted because user logged is SINGLE_LOGIN_SUPERUSER_ID");
					return 1;
				}
			} else {
				//esle control on the right management
				$currentuser=new User($this->db);
				$result=$currentuser->fetch($object->id);
				$currentuser->getrights('singlelogin');
				if (($currentuser->rights->singlelogin->read)) {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' aborted because user logged get rights->singlelogin->read");
					return 1;
				}
			}
			
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
			
			$singlelogin = new SingleLogin($this->db);
			$result=$singlelogin->check_user($object);
			if ($result < 0) {
				$error++;
				$this->errors[]="Failed on check_user: ".$singlelogin->error;
			}
			
			
			//If user is not present in user_lock table then create it
            if ((! $error) && empty($singlelogin->id)) {
            	$singlelogin->fk_element=$object->id;
            	$singlelogin->elementtype='user';
            	$singlelogin->sessionid=session_id();
            	$singlelogin->datel=dol_now();
            	$singlelogin->datem=dol_now();
            	$result=$singlelogin->create($object,1);
            	if ($result < 0) {
            		$error++;
            		$this->errors[]="Failed on create: ".$singlelogin->error;
            	}
            	else {
            		$first_connec=true;
            		dol_syslog(get_class($this).": first_connec".$first_connec, LOG_DEBUG);
            	}
            }
            
            //The user is already logged in we have to check the session is the same
            if ((! $error) && (! $first_connect)){
            	
            	$timeoutpassed=true;

            	$currenttimeconnection=abs($singlelogin->datel-$singlelogin->datem);
            	//convert date difference in minutes
            	if ($currenttimeconnection!=0){$currenttimeconnection=$currenttimeconnection/60;}
				else {$timeoutpassed=false;}
				
            	//dol_syslog(get_class($this).": currenttimeconnection(l-m):".$currenttimeconnection, LOG_DEBUG);
				
            	//If timeout is pass we let it go
            	if ($currenttimeconnection>$conf->global->SINGLE_LOGIN_TIMEOUT) {
            		$timeoutpassed=false;
            	}
            	
            	//dol_syslog(get_class($this).": timeoutpassed".$timeoutpassed, LOG_DEBUG);
            	
            	//This is another user session 
            	if ($singlelogin->sessionid!=session_id() && (!$timeoutpassed)) {
					
            		//redirect to login page with message to contact admin
            		unset($_SESSION["dol_login"]);
            		
            		// If super admin is use
            		if ($conf->global->SINGLE_LOGIN_SUPERUSER_USE) {
            			//We retreive the mail of administrator
            			$adminuser=new User($this->db);
            			$result=$adminuser->fetch($conf->global->SINGLE_LOGIN_SUPERUSER_ID);
            			if ($result < 0) {
            				$error++;
            				$this->errors[]=$adminuser->error;
            			}
            			if (empty($conf->global->SINGLE_LOGIN_ERRMSG)) {
            				$usererrmegs=str_replace('MAILADMIN',$adminuser->email,$langs->trans('SLErrContactAdmin'));
            			} else {
            				$usererrmegs=str_replace('MAILADMIN',$adminuser->email,$conf->global->SINGLE_LOGIN_ERRMSG);
            			}
            		} else {
            			if (empty($conf->global->SINGLE_LOGIN_ERRMSG)) {
            				$usererrmegs=str_replace('MAILADMIN',$langs->trans('SLUserAdmin'),$langs->trans('SLErrContactAdmin'));
            			} else {
            				$usererrmegs=str_replace('MAILADMIN',$langs->trans('SLUserAdmin'),$conf->global->SINGLE_LOGIN_ERRMSG);
            			}
            		}
            		
            		$_SESSION["dol_loginmesg"]=$usererrmegs;
            		header('Location: '.DOL_URL_ROOT.'/index.php');
            		exit;
            	}
            	
            	//we update the last action date
            	if ($singlelogin->sessionid==session_id()) {
            		$singlelogin->datem=dol_now();
            		$result=$singlelogin->update($user,1);
            		if ($result < 0) {
            			$error++;
            			$this->errors[]=$singlelogin->error;
            		}
            	}
            	
            	//Can be the same login after timeout so we allow
            	if ($singlelogin->sessionid!=session_id() && $timeoutpassed) {
            		$singlelogin->sessionid=session_id();
            		$singlelogin->datel=dol_now();
            		$singlelogin->datem=dol_now();
            		$result=$singlelogin->update($user,1);
            		if ($result < 0) {
            			$error++;
            			$this->errors[]=$singlelogin->error;
            		}
            	}
            }
            
            if (! $error)
            {
            	return 1;
            }
            else
            {
            	foreach($this->errors as $errmsg)
				{
					dol_syslog(get_class($this).": ".$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
            	return -1;
            }
            
		}

		if (($action == 'USER_LOGOUT') && ($run_this_trigger)) {
			
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
			
			$singlelogin = new SingleLogin($this->db);
			$result=$singlelogin->check_user($object);
			if ($result < 0) {
				$error++;
				$this->errors[]="Failed on check_user: ".$singlelogin->error;
			}
			
			$result=$singlelogin->delete($user);
			if ($result < 0) {
				$error++;
				$this->errors[]="Failed on delete: ".$singlelogin->error;
			}
			
			if (! $error)
			{
				return 1;
			}
			else
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(get_class($this).": ".$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				return -1;
			}
		}
		
		return 0;
	}
}