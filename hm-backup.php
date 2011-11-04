<?php

/**
 * Runs the backup process
 */
class HMBackup {

	/**
	 * The path to save the backup file
	 *
	 * @string
	 * @access public
	 */
	public $path;

	/**
	 * Whether the backup should be files only
	 *
	 * @bool
	 * @access public
	 */
	public $files_only;

	/**
	 * Whether the backup should be database only
	 *
	 * @bool
	 * @access public
	 */
	public $database_only;

	/**
	 * The filename of the backup
	 *
	 * @string
	 * @access public
	 */
	public $filename;

	/**
	 * The full path of the backup
	 *
	 * @string
	 * @access private
	 */
	private $filepath;

	/**
	 * The filename of the database dump
	 *
	 * @string
	 * @access private
	 */
	private $database_filename;

	/**
	 * The filepath of the database dump
	 *
	 * @var mixed
	 * @access private
	 */
	private $database_filepath;

	/**
	 * Is shell_exec available
	 *
	 * @bool
	 * @access private
	 */
	private $shell_exec_available;

	/**
	 * The path to the zip command
	 *
	 * @string
	 * @access public
	 */
	public $zip_path;

	/**
	 * The path to the mysqldump command
	 *
	 * @string
	 * @access public
	 */
	public $mysqldump_path;

	/**
	 * An array of exclude rules
	 *
	 * @array
	 * @access public
	 */
	public $excludes;

	/**
	 * Sets up the default properties
	 *
	 * @access public
	 * @return null
	 */
	public function __construct() {

		// Raise the memory limit and max_execution_time time
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );
		@set_time_limit( 0 );

		// Defaults
		$this->path = $this->conform_dir( WP_CONTENT_DIR . '/backups' );

		$this->database_filename = 'database_' . DB_NAME . '.sql';
		$this->database_filepath = trailingslashit( $this->path ) . $this->database_filename;

		$this->filename = sanitize_file_name( get_bloginfo( 'name' ) . '.backup.' . date( 'Y-m-d-H-i-s', time() + ( current_time( 'timestamp' ) - time() ) ) . '.zip' );
		$this->filepath = trailingslashit( $this->path ) . $this->filename;

		$this->mysqldump_path();
		$this->zip_path();

		$this->database_only = false;
		$this->files_only = false;

		$this->excludes = $this->excludes();

	}

	/**
	 * Run the backup
	 *
	 * @access public
	 * @return bool
	 */
	public function backup() {

		// Make sure it's possible to do a backup
		if ( ! $this->is_backup_possible() )
			return false;

		if ( file_exists( $this->path . '/.backup_complete' ) )
			unlink( $this->path . '/.backup_complete' );

		// Backup database
		if ( ! $this->files_only ) {

			$this->set_status( __( 'Dumping database', 'hmbkp' ) );

		    $this->mysqldump();

		}

		$this->set_status( __( 'Creating zip archive', 'hmbkp' ) );

		// Zip everything up
		$this->archive();

		// Delete the database dump file
		if ( ! $this->files_only )
			unlink( $this->database_filepath );

	    unlink( $this->path . '/.backup_running' );

		do_action( 'hmbkp_backup_complete' );

		if ( ! $handle = @fopen( $this->path . '/.backup_complete', 'w' ) )
			return false;

		fwrite( $handle, '' );

		fclose( $handle );

	}

	/**
	 * Check if a backup is running
	 *
	 * @return bool
	 */
	private function is_backup_in_progress() {
		return file_exists( $this->path . '/.backup_running' );
	}

	/**
	 * Set the status of the running backup
	 *
	 * @param string $message. (default: '')
	 * @return void
	 */
	private function set_status( $message = '' ) {

		if ( ! $handle = @fopen( $this->path . '/.backup_running', 'w' ) )
			return false;

		fwrite( $handle, $message );

		fclose( $handle );

	}

	/**
	 * Get the status of the running backup
	 *
	 * @return string
	 */
	private function get_status() {

		if ( ! file_exists( $this->path . '/.backup_running' ) )
			return false;

		return file_get_contents( $this->path .'/.backup_running' );

	}

	/**
	 * Check if a backup is possible with regards to file
	 * permissions etc.
	 *
	 * @return bool
	 */
	private function is_backup_possible() {

		if ( ! is_writable( $this->path ) || ! is_dir( $this->path ) || $this->is_safe_mode_active() )
			return false;

		if ( $this->files_only && $this->database_only )
			return false;

		return true;
	}

	/**
	 * Check whether safe mode if active or not
	 *
	 * @return bool
	 */
	private function is_safe_mode_active() {

		if ( ( $safe_mode = ini_get( 'safe_mode' ) ) && strtolower( $safe_mode ) != 'off' )
			return true;

		return false;

	}

	/**
	 * Zip up all the wordpress files.
	 *
	 * Attempts to use the shell zip command, if
	 * thats not available then it fallsback on
	 * PHP zip classes.
	 */
	private function archive() {

		// Do we have the path to the zip command
		if ( $this->zip_path ) {

			// Zip up ABSPATH
			if ( ! $this->database_only )
				shell_exec( 'cd ' . escapeshellarg( ABSPATH ) . ' && ' . escapeshellarg( $this->zip_path ) . ' -rq ' . escapeshellarg( $this->filepath ) . ' ./' . ' -x ' . $this->exclude_string( 'zip' ) );

			// Add the database dump to the archive
			if ( ! $this->files_only )
				shell_exec( 'cd ' . escapeshellarg( $this->path ) . ' && ' . escapeshellarg( $this->zip_path ) . ' -uq ' . escapeshellarg( $this->filepath ) . ' ' . escapeshellarg( $this->database_filename ) );

		// If not use the fallback
		} else {
			$this->archive_fallback();

		}

	}

	/**
	 * Attempt to work out the path to the zip command
	 */
	private function zip_path() {

		if ( ! $this->shell_exec_available() )
			return false;

		$this->zip_path = '';

		// List of possible zip locations
		$zip_locations = array(
			'zip',
			'/usr/bin/zip'
		);

	 	// If we don't have a path set
	 	if ( ! $this->zip_path = get_option( 'hmbkp_zip_path' ) ) {

			// Try to find out where zip is
			foreach ( $zip_locations as $location )
		 		if ( shell_exec( 'which ' . $location ) )
	 				$this->zip_path = $location;

			// Save it for later
	 		if ( $this->zip_path )
				update_option( 'hmbkp_zip_path', $this->zip_path );

		}

		// Check again in-case the saved path has stopped working for some reason
		if ( $this->zip_path && ! shell_exec( 'which ' . $this->zip_path ) ) {

			delete_option( 'hmbkp_zip_path' );

			$this->zip_path();

		} else {

			return true;

		}


	}

	/**
	 * Get the array of exclude rules
	 *
	 * @access private
	 * @return array
	 */
	private function excludes() {
		return array_unique( array_map( 'trim', array_merge( array( trailingslashit( $this->path ) ), (array) $this->excludes ) ) );
	}

	/**
	 * Generate the exclude param string for the zip backup
	 *
	 * Takes the exclude rules and formats them for use with either
	 * the shell zip command or pclzip
	 *
	 * @param string $context. (default: 'zip')
	 * @return string
	 */
	private function exclude_string( $context = 'zip' ) {

		// Return a comma separated list by default
		$separator = ', ';
		$wildcard = '';

		// The zip command
		if ( $context == 'zip' ) :
			$wildcard = '*';
			$separator = ' -x ';

		// The PCLZIP fallback library
		elseif ( $context == 'pclzip' ) :
			$wildcard = '([.]*?)';
			$separator = '|';

		endif;

		$excludes = $this->excludes();

		// Add wildcards to the directories
		foreach( $excludes as $key => &$rule ) :

			$file = $absolute = $fragment = false;

			// Files don't end with /
			if ( ! in_array( substr( $rule, -1 ), array( '\\', '/' ) ) )
				$file = true;

			// If rule starts with a / then treat as absolute path
			elseif ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
				$absolute = true;

			// Otherwise treat as dir fragment
			else
				$fragment = true;

			// Strip ABSPATH and conform
			$rule = str_replace( $this->conform_dir( ABSPATH ), '', untrailingslashit( $this->conform_dir( $rule ) ) );

			if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
				$rule = substr( $rule, 1 );

			// Escape string for regex
			if ( $context == 'pclzip' )
				//$rule = preg_quote( $rule );
				$rule = str_replace( '.', '\.', $rule );

			// Convert any existing wildcards
			if ( $wildcard != '*' && strpos( $rule, '*' ) !== false )
				$rule = str_replace( '*', $wildcard, $rule );

			// Wrap directory fragments in wildcards for zip
			if ( $context == 'zip' && $fragment )
				$rule = $wildcard . $rule . $wildcard;

			// Add a wildcard to the end of absolute url for zips
			if ( $context == 'zip' && $absolute )
				$rule .= $wildcard;

			// Add and end carrot to files for pclzip
			if ( $file && $context == 'pclzip' )
				$rule .= '$';

			// Add a start carrot to absolute urls for pclzip
			if ( $absolute && $context == 'pclzip' )
				$rule = '^' . $rule;

		endforeach;

		// Escape shell args for zip command
		if ( $context == 'zip' )
			$excludes = array_map( 'escapeshellarg', $excludes );

		return implode( $separator, $excludes );

	}

	/**
	 * Check whether shell_exec has been disabled.
	 *
	 * @return bool
	 */
	private function shell_exec_available() {

		// Are we in Safe Mode
		if ( $this->is_safe_mode_active() )
			return false;

		// Is shell_exec disabled?
		if ( strpos( ini_get( 'disable_functions' ), 'shell_exec' ) !== false )
			return false;

		return true;

	}

	/**
	 * Sanitize a directory path
	 *
	 * @param string $dir
	 * @param bool $rel. (default: false)
	 * @return string $dir
	 */
	private function conform_dir( $dir, $rel = false ) {

		// Normalise slashes
		$dir = str_replace( '\\', '/', $dir );
		$dir = str_replace( '//', '/', $dir );

		// Remove the trailingslash
		$dir = untrailingslashit( $dir );

		// If we're on Windows
		if ( strpos( ABSPATH, '\\' ) !== false )
			$dir = str_replace( '\\', '/', $dir );

		if ( $rel == true )
			$dir = str_replace( $this->conform_dir( ABSPATH ), '', $dir );

		return $dir;
	}

	/**
	 * Fallback for creating zip archives if zip command is
	 * unnavailable.
	 *
	 * Uses the PCLZIP library that ships with WordPress
	 *
	 * @todo support zipArchive
	 * @param string $path
	 */
	private function archive_fallback() {

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		$archive = new PclZip( $this->filepath );

		// Zip up everything
		if ( ! $this->database_only )
			$archive->create( ABSPATH, PCLZIP_OPT_REMOVE_PATH, ABSPATH, PCLZIP_CB_PRE_ADD, 'hmbkp_pclzip_exclude' );

		// Only zip up the database
		if ( $this->database_only )
			$archive->create( $this->database_filepath, PCLZIP_OPT_REMOVE_PATH, $this->path );

	}

	/**
	 * Add file callback, excludes files in the backups directory
	 * and sets the database dump to be stored in the root
	 * of the zip
	 *
	 * @param string $event
	 * @param array &$file
	 * @return bool
	 */
	private function pclzip_exclude( $event, &$file ) {

		// Don't try to add unreadable files.
		if ( ! is_readable( $file['filename'] ) )
			return false;

		// Include the database file
		if ( strpos( $file['filename'], $this->database_filename ) !== false )
			$file['stored_filename'] = $this->database_filename;

		// Match everything else past the exclude list
		elseif ( preg_match( '(' . $this->exclude_string( 'pclzip' ) . ')', $file['stored_filename'] ) )
			return false;

		return true;

	}

	/**
	 * Create the mysql backup
	 *
	 * Uses mysqldump if available, fallsback to PHP
	 * if not.
	 */
	private function mysqldump() {

		// Use mysqldump if we can
		if ( $this->mysqldump_path ) {

			// Path to the mysqldump executable
			$cmd = escapeshellarg( $this->mysqldump_path );

			// No Create DB command
			$cmd .= ' --no-create-db';

			// Make sure binary data is exported properly
			$cmd .= ' --hex-blob';

			// Username
			$cmd .= ' -u ' . escapeshellarg( DB_USER );

			// Don't pass the password if it's blank
			if ( DB_PASSWORD )
			    $cmd .= ' -p'  . escapeshellarg( DB_PASSWORD );

			// Set the host
			$cmd .= ' -h ' . escapeshellarg( DB_HOST );

			// Save the file
			$cmd .= ' -r ' . escapeshellarg( $this->database_filepath );

			// The database we're dumping
			$cmd .= ' ' . escapeshellarg( DB_NAME );

			error_log( $cmd );

			shell_exec( $cmd );

			// If the file doesn't exist then the shell_exec must have failed
			if ( file_exists( $this->database_filepath ) )
				return true;

		}

		// Fallback to using the PHP library
		$this->mysqldump_fallback();
	}

	/**
	 * Attempt to work out the path to mysqldump
	 *
	 * Can be overridden by defining HMBKP_MYSQLDUMP_PATH in
	 * wp-config.php.
	 *
	 * @return bool
	 */
	private function mysqldump_path() {

		if ( !$this->shell_exec_available() )
			return false;

		$this->mysqldump_path = '';

		// List of possible mysqldump locations
		$mysqldump_locations = array(
			'mysqldump',
			'/usr/local/bin/mysqldump',
			'/usr/local/mysql/bin/mysqldump',
			'/usr/mysql/bin/mysqldump',
			'/usr/bin/mysqldump',
			'/opt/local/lib/mysql6/bin/mysqldump',
			'/opt/local/lib/mysql5/bin/mysqldump',
			'/opt/local/lib/mysql4/bin/mysqldump',
			'\xampp\mysql\bin\mysqldump',
			'\Program Files\xampp\mysql\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 6.0\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 5.5\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 5.4\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 5.1\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 5.0\bin\mysqldump',
			'\Program Files\MySQL\MySQL Server 4.1\bin\mysqldump'
		);

	 	// If we don't have a path set
	 	if ( ! $this->mysqldump_path = get_option( 'hmbkp_mysqldump_path' ) ) {

			// Try to find out where mysqldump is
			foreach ( $mysqldump_locations as $location )
		 		if ( shell_exec( $location ) )
	 				$this->mysqldump_path = $location;

			// Save it for later
	 		if ( $this->mysqldump_path )
				update_option( 'hmbkp_mysqldump_path', $this->mysqldump_path );

		}

		// Check again in-case the saved path has stopped working for some reason
		if ( $this->mysqldump_path && ! shell_exec( $this->mysqldump_path ) ) {

			delete_option( 'hmbkp_mysqldump_path' );

			$this->mysqldump_path();

		} else {

			return true;

		}

	}

	/**
	 * Add backquotes to tables and db-names inSQL queries. Taken from phpMyAdmin.
	 *
	 * @access public
	 * @param mixed $a_name
	 */
	private function sql_backquote( $a_name ) {

	    if ( !empty( $a_name ) && $a_name != '*' ) {

	    	if ( is_array( $a_name ) ) {

	    		$result = array();

	    		reset( $a_name );

	    		while ( list( $key, $val ) = each( $a_name ) )
	    			$result[$key] = '`' . $val . '`';

	    		return $result;

	    	} else {

	    		return '`' . $a_name . '`';

	    	}

	    } else {

	    	return $a_name;

	    }

	}

	/**
	 * Reads the Database table in $table and creates
	 * SQL Statements for recreating structure and data
	 * Taken partially from phpMyAdmin and partially from
	 * Alain Wolf, Zurich - Switzerland
	 * Website: http://restkultur.ch/personal/wolf/scripts/db_backup/
	 *
	 * @access public
	 * @param mixed $sql_file
	 * @param mixed $table
	 */
	private function make_sql( $sql_file, $table ) {

	    // Add SQL statement to drop existing table
	    $sql_file = "\n";
	    $sql_file .= "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "# Delete any existing table " . $this->sql_backquote( $table ) . "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "\n";
	    $sql_file .= "DROP TABLE IF EXISTS " . $this->sql_backquote( $table ) . ";\n";

	    /* Table Structure */

	    // Comment in SQL-file
	    $sql_file .= "\n";
	    $sql_file .= "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "# Table structure of table " . $this->sql_backquote( $table ) . "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "\n";

	    // Get table structure
	    $query = 'SHOW CREATE TABLE ' . $this->sql_backquote( $table );
	    $result = mysql_query( $query, $this->connection );

	    if ( $result ) {

	    	if ( mysql_num_rows( $result ) > 0 ) {
	    		$sql_create_arr = mysql_fetch_array( $result );
	    		$sql_file .= $sql_create_arr[1];
	    	}

	    	mysql_free_result( $result );
	    	$sql_file .= ' ;';

	    }

	    /* Table Contents */

	    // Get table contents
	    $query = 'SELECT * FROM ' . $this->sql_backquote( $table );
	    $result = mysql_query( $query, $this->connection );

	    if ( $result ) {
	    	$fields_cnt = mysql_num_fields( $result );
	    	$rows_cnt   = mysql_num_rows( $result );
	    }

	    // Comment in SQL-file
	    $sql_file .= "\n";
	    $sql_file .= "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "# Data contents of table " . $table . " (" . $rows_cnt . " records)\n";
	    $sql_file .= "#\n";

	    // Checks whether the field is an integer or not
	    for ( $j = 0; $j < $fields_cnt; $j++ ) {
	    	$field_set[$j] = $this->sql_backquote( mysql_field_name( $result, $j ) );
	    	$type = mysql_field_type( $result, $j );

	    	if ( $type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' || $type == 'bigint'  || $type == 'timestamp')
	    		$field_num[$j] = true;
	    	else
	    		$field_num[$j] = false;

	    }

	    // Sets the scheme
	    $entries = 'INSERT INTO ' . $this->sql_backquote( $table ) . ' VALUES (';
	    $search   = array( '\x00', '\x0a', '\x0d', '\x1a' );  //\x08\\x09, not required
	    $replace  = array( '\0', '\n', '\r', '\Z' );
	    $current_row = 0;
	    $batch_write = 0;

	    while ( $row = mysql_fetch_row( $result ) ) {

	    	$current_row++;

	    	// build the statement
	    	for ( $j = 0; $j < $fields_cnt; $j++ ) {

	    		if ( !isset($row[$j] ) ) {
	    			$values[]     = 'NULL';

	    		} elseif ( $row[$j] == '0' || $row[$j] != '' ) {

	    		    // a number
	    		    if ( $field_num[$j] )
	    		    	$values[] = $row[$j];

	    		    else
	    		    	$values[] = "'" . str_replace( $search, $replace, $this->sql_addslashes( $row[$j] ) ) . "'";

	    		} else {
	    			$values[] = "''";

	    		}

	    	}

	    	$sql_file .= " \n" . $entries . implode( ', ', $values ) . ") ;";

	    	// write the rows in batches of 100
	    	if ( $batch_write == 100 ) {
	    		$batch_write = 0;
	    		$this->write_sql( $sql_file );
	    		$sql_file = '';
	    	}

	    	$batch_write++;

	    	unset( $values );

	    }

	    mysql_free_result( $result );

	    // Create footer/closing comment in SQL-file
	    $sql_file .= "\n";
	    $sql_file .= "#\n";
	    $sql_file .= "# End of data contents of table " . $table . "\n";
	    $sql_file .= "# --------------------------------------------------------\n";
	    $sql_file .= "\n";

		$this->write_sql( $sql_file );

	}

	/**
	 * Better addslashes for SQL queries.
	 * Taken from phpMyAdmin.
	 *
	 * @access public
	 * @param string $a_string. (default: '')
	 * @param bool $is_like. (default: false)
	 */
	private function sql_addslashes( $a_string = '', $is_like = false ) {

	    if ( $is_like )
	    	$a_string = str_replace( '\\', '\\\\\\\\', $a_string );

	    else
	    	$a_string = str_replace( '\\', '\\\\', $a_string );

	    $a_string = str_replace( '\'', '\\\'', $a_string );

	    return $a_string;
	}

	/**
	 * $this->mysql function.
	 *
	 * @access public
	 */
	private function mysqldump_fallback() {

	    $this->connection = mysql_pconnect( DB_HOST, DB_USER, DB_PASSWORD );

	    mysql_select_db( DB_NAME, $this->connection );

	    // Begin new backup of MySql
	    $tables = mysql_list_tables( DB_NAME );

	    $sql_file  = "# WordPress : " . get_bloginfo( 'url' ) . " MySQL database backup\n";
	    $sql_file .= "#\n";
	    $sql_file .= "# Generated: " . date( 'l j. F Y H:i T' ) . "\n";
	    $sql_file .= "# Hostname: " . DB_HOST . "\n";
	    $sql_file .= "# Database: " . $this->sql_backquote( DB_NAME ) . "\n";
	    $sql_file .= "# --------------------------------------------------------\n";

	    for ( $i = 0; $i < mysql_num_rows( $tables ); $i++ ) {

	    	$curr_table = mysql_tablename( $tables, $i );

	    	// Create the SQL statements
	    	$sql_file .= "# --------------------------------------------------------\n";
	    	$sql_file .= "# Table: " . $this->sql_backquote( $curr_table ) . "\n";
	    	$sql_file .= "# --------------------------------------------------------\n";

	    	$this->make_sql( $sql_file, $curr_table );

	    }

	}

	/**
	 * Write the SQL file
	 *
	 * @param string $sql
	 */
	private function write_sql( $sql ) {

	    $sqlname = $this->database_filepath;

	    // Actually write the sql file
	    if ( is_writable( $sqlname ) || ! file_exists( $sqlname ) ) {

	    	if ( ! $handle = fopen( $sqlname, 'a' ) )
	    		return;

	    	if ( ! fwrite( $handle, $sql ) )
	    		return;

	    	fclose( $handle );

	    	return true;

	    }

	}

}