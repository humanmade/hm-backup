<?php

/**
 * Runs the backup process
 */
class HMBackup {

	/**
	 * The path where the backup file should be saved
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
	 * The filename of the backup file
	 *
	 * @string
	 * @access public
	 */
	public $archive_filename;

	/**
	 * The filename of the database dump
	 *
	 * @string
	 * @access public
	 */
	public $database_dump_filename;

	/**
	 * The path to the zip command
	 *
	 * @string
	 * @access public
	 */
	public $zip_command_path;

	/**
	 * The path to the mysqldump command
	 *
	 * @string
	 * @access public
	 */
	public $mysqldump_command_path;

	/**
	 * An array of exclude rules
	 *
	 * @array
	 * @access public
	 */
	public $excludes;

	/**
	 * The path that should be backed up
	 *
	 * @var string
	 * @access public
	 */
	public $root;

	/**
	 * Sets up the default properties
	 *
	 * @access public
	 * @return null
	 */
	public function __construct() {

		global $hm_backup;

		// Raise the memory limit and max_execution_time time
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( 0 );

		// Defaults
		$this->root = ABSPATH;

		$this->path = $this->conform_dir( WP_CONTENT_DIR . '/backups' );

		$this->database_dump_filename = 'database_' . DB_NAME . '.sql';

		$this->archive_filename = strtolower( sanitize_file_name( get_bloginfo( 'name' ) . '.backup.' . date( 'Y-m-d-H-i-s', time() + ( current_time( 'timestamp' ) - time() ) ) . '.zip' ) );

		$this->mysqldump_command_path = $this->guess_mysqldump_command_path();
		$this->zip_command_path = $this->guess_zip_command_path();

		$this->database_only = false;
		$this->files_only = false;

		$hm_backup = $this;

	}

	/**
	 * The full filepath to the archive filepath.
	 *
	 * @access public
	 * @return string
	 */
	public function archive_filepath() {
		return trailingslashit( $this->path ) . $this->archive_filename;
	}

	/**
	 * The full filepath to the database dump filepath.
	 *
	 * @access public
	 * @return string
	 */
	public function database_dump_filepath() {
		return trailingslashit( $this->path ) . $this->database_dump_filename;
	}

	/**
	 * Run the backup
	 *
	 * @access public
	 * @return bool
	 */
	public function backup() {

		do_action( 'hmbkp_backup_started', $this );

		// Backup database
		if ( ! $this->files_only )
		    $this->mysqldump();

		// Zip everything up
		$this->archive();

		do_action( 'hmbkp_backup_complete' );

	}

	/**
	 * Create the mysql backup
	 *
	 * Uses mysqldump if available, falls back to PHP
	 * if not.
	 *
	 * @access private
	 * @return null
	 */
	public function mysqldump() {

		do_action( 'hmbkp_mysqldump_started' );

		// Use mysqldump if we can
		if ( $this->mysqldump_command_path ) {

			// Path to the mysqldump executable
			$cmd = escapeshellarg( $this->mysqldump_command_path );

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
			$cmd .= ' -r ' . escapeshellarg( $this->database_dump_filepath() );

			// The database we're dumping
			$cmd .= ' ' . escapeshellarg( DB_NAME );

			error_log( $cmd );

			shell_exec( $cmd );

		}

		// If not or if the shell mysqldump command failed, use the PHP fallback
		if ( ! file_exists( $this->database_dump_filepath() ) )
			$this->mysqldump_fallback();

		do_action( 'hmbkp_mysqldump_finished' );

	}

	/**
	 * PHP mysqldump fallback functions, exports the database to a .sql file
	 *
	 * @access private
	 */
	public function mysqldump_fallback() {

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
	 * Zip up all the wordpress files.
	 *
	 * Attempts to use the shell zip command, if
	 * thats not available then it fallsback on
	 * PHP zip classes.
	 *
	 * @access private
	 * @return null
	 */
	public function archive() {

		do_action( 'hmbkp_archive_started' );

		// Do we have the path to the zip command
		if ( $this->zip_command_path ) {

			// Zip up $this->root
			if ( ! $this->database_only )
				shell_exec( 'cd ' . escapeshellarg( $this->root ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -rq ' . escapeshellarg( $this->archive_filepath() ) . ' ./' . ' -x ' . $this->exclude_string( 'zip' ) );

			// Add the database dump to the archive
			if ( ! $this->files_only )
				shell_exec( 'cd ' . escapeshellarg( $this->path ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -uq ' . escapeshellarg( $this->archive_filepath() ) . ' ' . escapeshellarg( $this->database_dump_filename ) );

		}

		// If not or if the shell zip failed then use the PHP fallback
		if ( ! file_exists( $this->archive_filepath() ) )
			$this->archive_fallback();

		// Delete the database dump file
		if ( file_exists( $this->database_dump_filepath() ) )
			unlink( $this->database_dump_filepath() );

		do_action( 'hmbkp_archive_finished' );

	}

	/**
	 * Fallback for creating zip archives if zip command is
	 * unnavailable.
	 *
	 * Uses the PCLZIP library that ships with WordPress
	 *
	 * @todo support zipArchive
	 * @access private
	 * @param string $path
	 */
	public function archive_fallback() {

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		$archive = new PclZip( $this->archive_filepath() );

		// Zip up everything
		if ( ! $this->database_only )
			$archive->add( $this->root, PCLZIP_OPT_REMOVE_PATH, $this->root, PCLZIP_CB_PRE_ADD, 'hmbkp_pclzip_callback' );

		if ( ! $this->files_only )
			$archive->add( $this->database_dump_filepath(), PCLZIP_OPT_REMOVE_PATH, $this->path );

	}

	/**
	 * Attempt to work out the path to mysqldump
	 *
	 * @access private
	 * @return bool
	 */
	public function guess_mysqldump_command_path() {

		if ( !$this->shell_exec_available() )
			return '';

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

		// Try to find out where mysqldump is
		foreach ( $mysqldump_locations as $location )
		    if ( shell_exec( $location ) )
	 	    	return $location;

		return '';

	}

	/**
	 * Attempt to work out the path to the zip command
	 *
	 * @access private
	 * @return bool
	 */
	public function guess_zip_command_path() {

		// Check shell_exec is available and hasn't been explicitly bypassed
		if ( ! $this->shell_exec_available() )
			return '';

		// List of possible zip locations
		$zip_locations = array(
			'zip',
			'/usr/bin/zip'
		);

		// Find the one which works
		foreach ( $zip_locations as $location )
		    if ( shell_exec( 'which ' . $location ) )
	 	    	return $location;

		return '';

	}

	/**
	 * Get the array of exclude rules
	 *
	 * @access private
	 * @return array
	 */
	public function excludes() {
		return array_unique( array_map( 'trim', array_merge( array( trailingslashit( $this->path ) ), (array) $this->excludes ) ) );
	}

	/**
	 * Generate the exclude param string for the zip backup
	 *
	 * Takes the exclude rules and formats them for use with either
	 * the shell zip command or pclzip
	 *
	 * @access private
	 * @param string $context. (default: 'zip')
	 * @return string
	 */
	public function exclude_string( $context = 'zip' ) {

		// Return a comma separated list by default
		$separator = ', ';
		$wildcard = '';

		// The zip command
		if ( $context == 'zip' ) {
			$wildcard = '*';
			$separator = ' -x ';

		// The PCLZIP fallback library
		} elseif ( $context == 'pclzip' ) {
			$wildcard = '([.]*?)';
			$separator = '|';

		}

		$excludes = $this->excludes();

		// Add wildcards to the directories
		foreach( $excludes as $key => &$rule ) {

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

			// Strip $this->root and conform
			$rule = str_replace( $this->conform_dir( $this->root ), '', untrailingslashit( $this->conform_dir( $rule ) ) );

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

		}

		// Escape shell args for zip command
		if ( $context == 'zip' )
			$excludes = array_map( 'escapeshellarg', $excludes );

		return implode( $separator, $excludes );

	}

	/**
	 * Check whether safe mode if active or not
	 *
	 * @access private
	 * @return bool
	 */
	private function is_safe_mode_active() {

		if ( $safe_mode = ini_get( 'safe_mode' ) && strtolower( $safe_mode ) != 'off' )
			return true;

		return false;

	}

	/**
	 * Check whether shell_exec has been disabled.
	 *
	 * @access private
	 * @return bool
	 */
	private function shell_exec_available() {

		// Are we in Safe Mode
		if ( $this->is_safe_mode_active() )
			return false;

		// Is shell_exec disabled?
		if ( in_array( 'shell_exec', array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) ) ) )
			return false;

		return true;

	}

	/**
	 * Sanitize a directory path
	 *
	 * @access private
	 * @param string $dir
	 * @param bool $rel. (default: false)
	 * @return string $dir
	 */
	public function conform_dir( $dir, $recursive = false ) {

		// Replace single forward slash (looks like double slash because we have to escape it)
		$dir = str_replace( '\\', '/', $dir );
		$dir = str_replace( '//', '/', $dir );

		// Remove the trailing slash
		$dir = untrailingslashit( $dir );
		
		// Carry on until completely normalized
		if ( !$recursive && $this->conform_dir( $dir, true ) != $dir )
			return $this->conform_dir( $dir );

		return $dir;
	}

	/**
	 * Add backquotes to tables and db-names inSQL queries. Taken from phpMyAdmin.
	 *
	 * @access private
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
	 * @access private
	 * @param string $sql_file
	 * @param string $table
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
	 * @access private
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
	 * Write the SQL file
	 *
	 * @access private
	 * @param string $sql
	 */
	private function write_sql( $sql ) {

	    $sqlname = $this->database_dump_filepath();

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

/**
 * Add file callback, excludes files in the backups directory
 * and sets the database dump to be stored in the root
 * of the zip
 *
 * @access private
 * @param string $event
 * @param array &$file
 * @return bool
 */
function hmbkp_pclzip_callback( $event, &$file ) {

	global $hm_backup;

    // Don't try to add unreadable files.
    if ( ! is_readable( $file['filename'] ) )
    	return false;

    // Match everything else past the exclude list
    elseif ( preg_match( '(' . $hm_backup->exclude_string( 'pclzip' ) . ')', $file['stored_filename'] ) )
    	return false;

    return true;

}