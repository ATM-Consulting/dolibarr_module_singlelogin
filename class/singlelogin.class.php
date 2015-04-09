<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Florian HENRY				<florian.henry@open-concept.pro>
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
 *  \file       singlelogin/class/singlelogin.class.php
 *  \ingroup    singlelogin
 *  \brief      this file is use to map element_lock table to my purpose
 */


/**
 *	Use to store and check login vs session
 */
class SingleLogin
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='singlelogin';			//!< Id that identify managed objects
	var $table_element='element_lock';	//!< Name of table without prefix where object is stored

	var $lines=array();
	
    var $id;
    
	var $fk_element;
	var $elementtype;
	var $datel='';
	var $datem='';
	var $sessionid;
	
	var $login;
	

	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}
	
	
	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
	
		// Clean parameters
	
		if (isset($this->fk_element)) $this->fk_element=trim($this->fk_element);
		if (isset($this->elementtype)) $this->elementtype=trim($this->elementtype);
		if (isset($this->sessionid)) $this->sessionid=trim($this->sessionid);
	
	
	
		// Check parameters
		// Put here code to add control on parameters values
	
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_lock(";
	
		$sql.= "fk_element,";
		$sql.= "elementtype,";
		$sql.= "datel,";
		$sql.= "datem,";
		$sql.= "sessionid";
	
	
		$sql.= ") VALUES (";
	
		$sql.= " ".(! isset($this->fk_element)?'NULL':"'".$this->fk_element."'").",";
		$sql.= " ".(! isset($this->elementtype)?'NULL':"'".$this->db->escape($this->elementtype)."'").",";
		$sql.= " ".(! isset($this->datel) || dol_strlen($this->datel)==0?'NULL':$this->db->idate($this->datel)).",";
		$sql.= " ".(! isset($this->datem) || dol_strlen($this->datem)==0?'NULL':$this->db->idate($this->datem)).",";
		$sql.= " ".(! isset($this->sessionid)?'NULL':"'".$this->db->escape($this->sessionid)."'")."";
	
	
		$sql.= ")";
	
		$this->db->begin();
	
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
	
		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."element_lock");
	
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
	
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
	
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}
	
	
	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
	
		$sql.= " t.fk_element,";
		$sql.= " t.elementtype,";
		$sql.= " t.datel,";
		$sql.= " t.datem,";
		$sql.= " t.sessionid";
	
	
		$sql.= " FROM ".MAIN_DB_PREFIX."element_lock as t";
		$sql.= " WHERE t.rowid = ".$id;
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
	
				$this->id    = $obj->rowid;
	
				$this->fk_element = $obj->fk_element;
				$this->elementtype = $obj->elementtype;
				$this->datel = $this->db->jdate($obj->datel);
				$this->datem = $this->db->jdate($obj->datem);
				$this->sessionid = $obj->sessionid;
	
	
			}
			$this->db->free($resql);
	
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	/**
	 *  Load objects in memory from the database
	 *
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch_all()
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
	
		$sql.= " t.fk_element,";
		$sql.= " t.elementtype,";
		$sql.= " t.datel,";
		$sql.= " t.datem,";
		$sql.= " t.sessionid,";
		$sql.= " u.login,";
		$sql.= " u.lastname,";
		$sql.= " u.firstname";
	
		$sql.= " FROM ".MAIN_DB_PREFIX."element_lock as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=t.fk_element";
		$sql.= ' AND t.elementtype = "user"';
		$sql.= ' ORDER BY u.login';
	
		dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		
		if ($resql) {
		
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				
				$this->lines[$i]=new SingleLoginLine();
				$this->lines[$i]->id    = $obj->rowid;
				$this->lines[$i]->fk_element = $obj->fk_element;
				$this->lines[$i]->elementtype = $obj->elementtype;
				$this->lines[$i]->datel = $this->db->jdate($obj->datel);
				$this->lines[$i]->datem = $this->db->jdate($obj->datem);
				$this->lines[$i]->sessionid = $obj->sessionid;
				$this->lines[$i]->login = $obj->login;
				$this->lines[$i]->lastname = $obj->lastname;
				$this->lines[$i]->firstname = $obj->firstname;
				$i++;
			}
			$this->db->free($resql);
			
			return $num;
		}
		else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_all ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	/**
	 *  Load object in memory from the database
	 *
	 *  @param	user		$user    	user to test
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function check_user($user)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
	
		$sql.= " t.fk_element,";
		$sql.= " t.elementtype,";
		$sql.= " t.datel,";
		$sql.= " t.datem,";
		$sql.= " t.sessionid";
		
		$sql.= " FROM ".MAIN_DB_PREFIX."element_lock as t";
		$sql.= " WHERE t.fk_element = ".$user->id;
		$sql.= ' AND t.elementtype = "user"';
		
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
	
				$this->id    = $obj->rowid;
	
				$this->fk_element = $obj->fk_element;
				$this->elementtype = $obj->elementtype;
				$this->datel = $this->db->jdate($obj->datel);
				$this->datem = $this->db->jdate($obj->datem);
				$this->sessionid = $obj->sessionid;
	
	
			}
			$this->db->free($resql);
	
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	
	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
	
		// Clean parameters
	
		if (isset($this->fk_element)) $this->fk_element=trim($this->fk_element);
		if (isset($this->elementtype)) $this->elementtype=trim($this->elementtype);
		if (isset($this->sessionid)) $this->sessionid=trim($this->sessionid);
	
	
	
		// Check parameters
		// Put here code to add a control on parameters values
	
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."element_lock SET";
	
		$sql.= " fk_element=".(isset($this->fk_element)?$this->fk_element:"null").",";
		$sql.= " elementtype=".(isset($this->elementtype)?"'".$this->db->escape($this->elementtype)."'":"null").",";
		$sql.= " datel=".(dol_strlen($this->datel)!=0 ? "'".$this->db->idate($this->datel)."'" : 'null').",";
		$sql.= " datem=".(dol_strlen($this->datem)!=0 ? "'".$this->db->idate($this->datem)."'" : 'null').",";
		$sql.= " sessionid=".(isset($this->sessionid)?"'".$this->db->escape($this->sessionid)."'":"null")."";
	
	
		$sql.= " WHERE rowid=".$this->id;
	
		$this->db->begin();
	
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
	
		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
	
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
	
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}
	
	
	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
	
		$this->db->begin();
	
		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
	
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
	
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_lock";
			$sql.= " WHERE rowid=".$this->id;
	
			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
	
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}
	
	
	
	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;
	
		$error=0;
	
		$object=new Elementlock($this->db);
	
		$this->db->begin();
	
		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;
	
		// Clear fields
		// ...
	
		// Create clone
		$result=$object->create($user);
	
		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}
	
		if (! $error)
		{
	
	
		}
	
		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}
	
	
	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
	
		$this->fk_element='';
		$this->elementtype='';
		$this->datel='';
		$this->datem='';
		$this->sessionid='';
	
	
	}
	
}

class SingleLoginLine 
{
	var $id;
	
	var $fk_element;
	var $elementtype;
	var $datel='';
	var $datem='';
	var $sessionid;
	var $login;
	var $lastname;
	var $firstname;
	
	/**
	 *  Constructor
	 *
	 */
	function __construct()
	{
		return 1;
	}
}