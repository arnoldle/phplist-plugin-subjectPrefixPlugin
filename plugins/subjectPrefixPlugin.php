<?php

/**
 * generalSubjectPrefix plugin version 1.0a1.1
 * 
 *
 * @category  phplist
 * @package   submitByMail Plugin
 * @author    Arnold V. Lesikar
 * @copyright 2014 Arnold V. Lesikar
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
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

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 *
 * For more information about how to use this plugin, see
 * http://resources.phplist.com/plugins/generalSubjectPrefix .
 * 
 */
 
 class subjectPrefixPlugin extends phplistPlugin
{
    /*
     *  Inherited variables
     */
    public $name = 'Subject Prefix Plugin';
    public $version = '1.0a1.1';
    public $enabled = false;
    public $authors = 'Arnold Lesikar';
    public $description = 'Allows prefixes for the subject line of messages';
    public $DBstruct =array (	//For creation of the required tables by Phplist
    		'prefix' => array(
    			"id" => array("integer not null primary key", "ID of the list for this prefix"),
    			"prefix" => array("varchar(255) not null","The subject line prefix for the list")
    			)
		);  				// Structure of database tables for this plugin
	
	public $tables = array ('prefix');	// Table names are prefixed by Phplist
	public $pfxtbl;
	private $curpfx;	// The prefix for the message currently being sent out	
	
	function __construct()
    {
    	$this->coderoot = dirname(__FILE__) . '/subjectPrefixPlugin/';
		
		parent::__construct();
    }
    
    function activate() {
    	$this->pfxtbl = $GLOBALS['tables']['subjectPrefixPlugin_prefix'];  	
    	return true;
    }
    
    // No menu items in administrative menu on dashboard
    function adminmenu() {
    }
    
    private function endsWithPunct($str) {
    	$puncChars = array('!', '$', '#', '^', '*', ':', ';', ',', '.', '?', '`'. '~');
		return in_array (substr($str, -1), $puncChars);
    }
    
    /* Create a prefix from an array of list IDs */
	private function createPrefix ($lists = array())
	{
		
		// Get the list names for this message
    	$lists = array_keys($lists);
    	$pfxary = array ();
		foreach ($lists as $listid) {
    		$query = sprintf("select prefix from %s where id=%d", $this->pfxtbl, $listid);
    		if ($row = Sql_Fetch_Row_Query($query)) 
    			$pfxary[$row[0]] = 1;
    	}
    	
    	if (!$pfxary)
    		return '';
    	
    	$pfxary = array_keys($pfxary);
    	if (count($pfxary) == 1)
    		return $pfxary[0];
    	$pfx = '';
    	
    	// More than one prefix. Put them together separated by commas
    	$fl = true;
    	foreach ($pfxary as $val) {
    		if (!$fl) {
    			if ($fl2)
    				$pfx .= ' ';
    			else
    				$pfx .= ', ';
    		}
    		$pfx .= $val;
    		$fl = false;
    		$fl2 = $this->endsWithPunct($val);
    	}
    	
    	return $pfx;
	}
	
	/*
   * campaignStarted
   * called when sending of a campaign starts
   * @param array messagedata - associative array with all data for campaign
   * @return null
   * 
   * We create the list name prefix here.
   *
   */
	public function campaignStarted($messagedata = array()) 
  	{
  		// Create the list name prefix
    	$this->curpfx  = $this->createPrefix ($messagedata['targetlist']);
  	}	
	
	function displayEditList($list) {
    # purpose: return tablerows with list attributes for this list
    # Currently used in list.php
    # 200710 Bas
    
    $pfx = '';
    if (isset($list['id'])) {
    	$query = sprintf("select prefix from %s where id=%d", $this->pfxtbl, $list['id']);
    	if ($row = Sql_Fetch_Row_Query($query))
    		$pfx = $row[0];
    }
    $str = <<<EOD
    <p><fieldset>
<label>Subject line prefix for this list:<input type="text" name="prefx" value="$pfx" maxlength="255" /></label>
</fieldset></p>
EOD;
    	return $str;
 	 }

	function processEditList($id) {
    # purpose: process edit list page (usually save fields)
    # return false if failed
    # 200710 Bas
    	$pfx = trim($_POST['prefx']); // The subscriber list from already has a hidden field named 'prefix'
    	$query = sprintf("select prefix from %s where id=%d", $this->pfxtbl, $id);
    	$row = Sql_Fetch_Row_Query($query);
 
    	if (!$pfx) {
    		if ($row) 
    			Sql_Query (sprintf("delete from %s where id=%d", $this->pfxtbl, $id));
			return true;	
    	}
    	
    	if ($row)
    		$query = sprintf("update %s set prefix='%s' where id=%d", $this->pfxtbl, Sql_Escape($pfx), $id);
    	else	
    		$query = sprintf("insert into %s (id, prefix) values (%d,'%s')", $this->pfxtbl, $id, Sql_Escape($pfx));
    	Sql_Query($query);
    	
		return true;
  	}
  	
  	/* messageHeaders  -- The original purpose of this function is:
   	*
   	* return headers for the message to be added, as "key => val"
   	*
   	* @param object $mail
   	* @return array (headeritem => headervalue)
   	*
   	* Our use is to alter the subject line for the $mail object
   	*
   	* This is the last point at which we can reach into the queue processing and
   	* modify the subject line.
   	*
	*/
  
  	public function messageHeaders($mail)
  	{
  		$mail->Subject = $this->curpfx . ' ' . $mail->Subject;  // Add the prefix
  	
    	return array(); //@@@
  	}

}