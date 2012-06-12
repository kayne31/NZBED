<?php

define( 'MYSQL_INCLUDE', 1 );

class mysql {

	var $hostname;
	var $username;
	var $password;
	var $dbname;
	var $dbcox;
	var $dbcon;
	var $debug = false;
	
	function mysql( $options ) {
		$this->hostname = $options['hostname'];
		$this->username = $options['username'];
		$this->password = $options['password'];
		$this->dbname = $options['dbname'];	
	}
	
	function connect() {
		$this->dbcox = @mysql_pconnect($this->hostname,$this->username,$this->password);
		if (!$this->dbcox) {
			$this->dbcox = @mysql_connect($this->hostname,$this->username,$this->password);
			if (!$this->dbcox) {
				die('Could not connect to mysql server'.mysql_error());
			}
		}
		if ($this->debug) { echo "<b>Connect:</b><br />&nbsp;Hostname: ".$this->hostname."<br />&nbsp;Username: ".$this->username."<br />&nbsp;Password: ".$this->password."<br />&nbsp;dbcox: ".$this->dbcox." <br />"; }
		$this->dbcon = mysql_select_db($this->dbname,$this->dbcox) or die("<b>Select DB</b><br />&nbsp;Unable to select DB<br />&nbsp;".mysql_error());
		if ($this->debug) { echo "<b>Select db:</b><br />&nbsp;DBname: ".$this->dbname."<br />&nbsp;DBcon: ".$this->dbcon."<br />"; }		
	}

	function query ( $query, $file = false, $line = false ) {
		$result = mysql_query( $query , $this->dbcox );
		if ($this->debug) { echo "<b>Query:</b> $query<br />&nbsp;result: $result<br />"; }
		if (!$result) { 
			$isfile = ($file)? "<br /><b>File:</b> $file ":"";
			$isline = ($line)? "<br /><b>line:</b> $line ":"";
			die("DB Error $isfile$isline<br /><br /><b>Query:</b> $query<br /><b>Error:</b> ".mysql_error());
		}
		else { return $result; }
	}
	
	function fquery ( $query , $file = false, $line = false ) {
		$result = $this->query ( $query , $file , $line );
		$row = $this->fetch ( $result );
		if ($row) { return $row; }
	}

	function select ( $what, $table, $where = false, $file = false, $line = false ) {
		if ($where) {
			$query = "SELECT $what FROM $table WHERE ";
			foreach($where as $field => $value) {
				$query .= "`".mysql_real_escape_string($field)."`='".mysql_real_escape_string($value)."' AND";
			}
			$query = substr($query,0,-4);
		} else { $query = "SELECT $what FROM $table"; }
	return $this->query($query,$file,$line);
	}
	
	function fselect ( $what, $table, $where = false, $file = false, $line = false ) {
		$result = $this->select($what, $table, $where, $file, $line);
		$row = $this->fetch ( $result );
		if ($row) { return $row; }		
	}

	function whereBranch( $type, $where ) {
		$end = $type;
		$query = '( ';
		if ( is_array( $where ) ) {
			foreach($where as $field => $value) {
				if ( is_array( $value ) ) {
					if ( !is_numeric( $field ) ) {
						$query .= $this->whereBranch( $field, $value )." $end \n ";
					} else {
						$end = (isset($value[4]))? $value[4]:$type; // for backwards compatibility
						if ( isset( $value[3] ) and $value[3] == 1) { $query .= "`".$this->parsefield($value[0])."` ".$value[1]." `".$this->parsefield($value[2])."` ".$end." \n "; }
						else if ( isset( $value[3] ) and $value[3] == 2) { $query .= "`".$this->parsefield($value[0])."` ".$value[1]." '".mysql_real_escape_string($value[2])."' ".$end." \n "; }
						else if ( isset( $value[3] ) and $value[3] == 3) { $query .= "'".mysql_real_escape_string($value[0])."' ".$value[1]." '".mysql_real_escape_string($value[2])."' ".$end." \n "; }
						else { $query .= "`".mysql_real_escape_string($value[0])."` ".$value[1]." '".mysql_real_escape_string($value[2])."' ".$end."\n "; }										
					}
				} else {
					$query .= "`".mysql_real_escape_string($field)."` = '".mysql_real_escape_string($value)."' $end \n ";
				}
			}
		}
		$qo1 = substr($query,0,-(strlen($end)+3));
		if ( strlen( $qo1 ) == 0 ) {
			return '1';
		} else {
			return substr($query,0,-(strlen($end)+3))." )";	
		}
	}

	function select2 ( $what, $table, $where = false, $order = false, $extra = false, $file = false, $line = false ) {
		$table = str_replace( ',', '`,`', $table );
		if ($where) {
			$query = "SELECT $what FROM `$table` WHERE\n ".
					 $this->whereBranch( 'AND', $where );
		} else { $query = "SELECT $what FROM `$table`\n "; }
		if ( $order ) {
			$query .= "ORDER BY\n ";
			foreach( $order as $field => $value ) {
				$query .= '`'.$this->parsefield($field).'` '.$value.', ';
			}
			$query = substr($query,0,-2);
		}
		if ( $extra ) {
			$query .= " ".$extra;
		}
		return $this->query($query,$file,$line);
	}

	function fselect2 ( $what, $table, $where = false, $order = false, $extra = false, $file = false, $line = false ) {
		$result = $this->select2($what, $table, $where, $order, $extra, $file, $line);
		$row = $this->fetch ( $result );
		if ($row) { return $row; }
	}


	function fetch ( $result ) {
		$row = mysql_fetch_object ( $result );
		if ($this->debug) { echo "<b>Fetch:</b> $result<br />"; }
		if ($row) { 
/*			foreach($row as $key=>$value) {
				$key = stripslashes($key);
				$value = stripslashes($value);
				$row->$key = $value;
			}*/
			if ($this->debug) { 
				echo "<pre>"; 
				print_r( $row );
				echo "</pre>";
			}
			return $row;
		}
	}

	function rows ( $result ) {
		$rows = mysql_num_rows ( $result );
		if ($this->debug) { echo "<b>Rows:</b> $rows : $result<br />"; }
		if ($rows) { return $rows; }
		else { return 0; }
	}

	function id ( ) {
		$id = mysql_insert_id( );
		if ($this->debug) { echo "<b>Rows:</b> $id<br />"; }
		if ($id) { return $id; }
	}
	
	function insert ( $table , $data, $file = false, $line = false ) {
		$query = "INSERT INTO `$table` SET ";
		foreach($data as $field => $value) {
			$query .= "`".mysql_real_escape_string($field)."`='".mysql_real_escape_string($value)."',";
		}
		$query = substr($query,0,-1);
		$this->query($query,$file,$line);
		return $this->id();
	}
		
	function update ( $table , $data, $where, $file = false, $line = false ) {
		$query = "UPDATE `$table` SET ";
		foreach($data as $field => $value) {
			$query .= "`".mysql_real_escape_string($field)."`='".mysql_real_escape_string($value)."',";
		}
		$query = substr($query,0,-1)." WHERE ";
		foreach($where as $field => $value) {
			$query .= "`".mysql_real_escape_string($field)."`='".mysql_real_escape_string($value)."' AND ";
		}
		$query = substr($query,0,-4);
		$this->query($query,$file,$line);
		return mysql_affected_rows( );
	}	
	
	function truncate ( $table, $file = false, $line = false ) {
		$query = "TRUNCATE `$table`";
		$this->query($query,$file,$line);
	}
	
	function delete ( $table, $where, $file = false, $line = false ) {
		$query = "DELETE FROM $table WHERE ";
		foreach($where as $field => $value) {
			$query .= "`".mysql_real_escape_string($field)."`='".mysql_real_escape_string($value)."' AND ";
		}		
		$query = substr($query,0,-4);
		$this->query($query,$file,$line);
		return mysql_affected_rows( );
	}
	
	function parsefield( $field ) {
		return preg_replace( '/(?<!`)\.(?!`)/', '`.`', $field );
	}

}
?>