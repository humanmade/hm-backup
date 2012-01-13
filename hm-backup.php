<?php

/**
 * Generic file and database backup class
 *
 * @version 1.4
 */
class HM_Backup {

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
	 * Holds the current db connection
	 *
	 * @var resource
	 * @access private
	 */
	private $db;

	/**
	 * Store the current backup instance
	 *
	 * @var object
	 * @static
	 * @access public
	 */
	private static $instance;

	/**
	 * An array of all the files in root
	 * excluding excludes
	 *
	 * @var array
	 * @access private
	 */
	private $files;

	/**
	 * Contains an array of errors
	 *
	 * @var mixed
	 * @access private
	 */
	private $errors;

	/**
	 * Contains an array of warnings
	 *
	 * @var mixed
	 * @access private
	 */
	private $warnings;

	/**
	 * The archive method used
	 *
	 * @var string
	 * @access private
	 */
	private $archive_method;

	/**
	 * The mysqldump method used
	 *
	 * @var string
	 * @access private
	 */
	private $mysqldump_method;

	/**
	 * Sets up the default properties
	 *
	 * @access public
	 * @return null
	 */
	public function __construct() {

		// Raise the memory limit and max_execution_time time
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( 0 );

		$this->errors = array();

		set_error_handler( array( &$this, 'error_handler' ) );

		// Defaults
		$this->root = $this->conform_dir( ABSPATH );

		$this->path = $this->conform_dir( WP_CONTENT_DIR . '/backups' );

		$this->database_dump_filename = 'database_' . DB_NAME . '.sql';

		$this->archive_filename = strtolower( sanitize_file_name( get_bloginfo( 'name' ) . '.backup.' . date( 'Y-m-d-H-i-s', time() + ( current_time( 'timestamp' ) - time() ) ) . '.zip' ) );

		$this->mysqldump_command_path = $this->guess_mysqldump_command_path();
		$this->zip_command_path = $this->guess_zip_command_path();

		$this->database_only = false;
		$this->files_only = false;

	}

	/**
	 * Return the current instance
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) )
			self::$instance = new HM_Backup();

		return self::$instance;

	}

	/**
	 * The full filepath to the archive file.
	 *
	 * @access public
	 * @return string
	 */
	public function archive_filepath() {
		return trailingslashit( $this->path() ) . $this->archive_filename();
	}

	/**
	 * The full filepath to the archive file.
	 *
	 * @access public
	 * @return string
	 */
	public function archive_filename() {
		return strtolower( sanitize_file_name( remove_accents( $this->archive_filename ) ) );
	}

	/**
	 * The full filepath to the database dump file.
	 *
	 * @access public
	 * @return string
	 */
	public function database_dump_filepath() {
		return trailingslashit( $this->path() ) . $this->database_dump_filename();
	}

	public function database_dump_filename() {
		return strtolower( sanitize_file_name( remove_accents( $this->database_dump_filename ) ) );
	}

    public function root() {
        return $this->conform_dir( $this->root );
    }

    public function path() {
        return $this->conform_dir( $this->path );
    }

	public function archive_method() {
		return $this->archive_method;
	}

	public function mysqldump_method() {
		return $this->mysqldump_method;
	}

	/**
	 * Kick off a backup
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

		do_action( 'hmbkp_backup_complete', $this );

	}

	/**
	 * Create the mysql backup
	 *
	 * Uses mysqldump if available, falls back to PHP
	 * if not.
	 *
	 * @access public
	 * @return null
	 */
	public function mysqldump() {

		do_action( 'hmbkp_mysqldump_started' );

		$this->mysqldump_method = 'mysqldump';

		// Use mysqldump if we can
		if ( $this->mysqldump_command_path ) {

			$host = reset( explode( ':', DB_HOST ) );
			$port = strpos( DB_HOST, ':' ) ? end( explode( ':', DB_HOST ) ) : '';

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
			$cmd .= ' -h ' . escapeshellarg( $host );

			// Set the port if it was set
			if ( ! empty( $port ) )
				$cmd .= ' -P ' . $port;

			// The file we're saving too
			$cmd .= ' -r ' . escapeshellarg( $this->database_dump_filepath() );

			// The database we're dumping
			$cmd .= ' ' . escapeshellarg( DB_NAME );

			// Pipe STDERR to STDOUT
			$cmd .= ' 2>&1';

			// Store any returned data in warning
			$this->warning( $this->mysqldump_method, shell_exec( $cmd ) );

		}

		// If not or if the shell mysqldump command failed, use the PHP fallback
		if ( ! file_exists( $this->database_dump_filepath() ) )
			$this->mysqldump_fallback();

		do_action( 'hmbkp_mysqldump_finished' );

	}

	/**
	 * PHP mysqldump fallback functions, exports the database to a .sql file
	 *
	 * @access public
	 * @return null
	 */
	public function mysqldump_fallback() {

		$this->errors_to_warnings( $this->mysqldump_method );

		$this->mysqldump_method = 'mysqldump_fallback';

	    $this->db = mysql_pconnect( DB_HOST, DB_USER, DB_PASSWORD );

	    mysql_select_db( DB_NAME, $this->db );
	    mysql_set_charset( DB_CHARSET, $this->db );

	    // Begin new backup of MySql
	    $tables = mysql_query( 'SHOW TABLES' );

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
	 * Zip up all the files.
	 *
	 * Attempts to use the shell zip command, if
	 * thats not available then it fallsback to
	 * PHP ZipArchive and finally PclZip.
	 *
	 * @access public
	 * @return null
	 */
	public function archive() {

		do_action( 'hmbkp_archive_started' );

		// Do we have the path to the zip command
		if ( $this->zip_command_path )
			$this->zip();

		// If not or if the shell zip failed then use ZipArchive
		if ( empty( $this->archive_verified ) && class_exists( 'ZipArchive' ) && empty( $this->skip_zip_archive ) )
			$this->zip_archive();

		// If ZipArchive is unavailable or one of the above failed
		if ( empty( $this->archive_verified ) )
			$this->pcl_zip();

		// Delete the database dump file
		if ( file_exists( $this->database_dump_filepath() ) )
			unlink( $this->database_dump_filepath() );

		do_action( 'hmbkp_archive_finished' );

	}

	/**
	 * Zip using the native zip command
	 *
	 * @access public
	 * @return null
	 */
	public function zip() {

		$this->archive_method = 'zip';

		// Zip up $this->root with excludes
		if ( ! $this->database_only && $this->exclude_string( 'zip' ) )
		    $this->warning( $this->archive_method, shell_exec( 'cd ' . escapeshellarg( $this->root() ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -rq ' . escapeshellarg( $this->archive_filepath() ) . ' ./' . ' -x ' . $this->exclude_string( 'zip' ) . ' 2>&1' ) );

		// Zip up $this->root without excludes
		elseif ( ! $this->database_only )
		    $this->warning( $this->archive_method, shell_exec( 'cd ' . escapeshellarg( $this->root() ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -rq ' . escapeshellarg( $this->archive_filepath() ) . ' ./' . ' 2>&1' ) );

		// Add the database dump to the archive
		if ( ! $this->files_only )
		    $this->warning( $this->archive_method, shell_exec( 'cd ' . escapeshellarg( $this->path() ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -uq ' . escapeshellarg( $this->archive_filepath() ) . ' ' . escapeshellarg( $this->database_dump_filename() ) . ' 2>&1' ) );

		$this->check_archive();

	}

	/**
	 * Fallback for creating zip archives if zip command is
	 * unnavailable.
	 *
	 * @access public
	 * @param string $path
	 */
	public function zip_archive() {

		$this->errors_to_warnings( $this->archive_method );
		$this->archive_method = 'ziparchive';

    	$zip = new ZipArchive();

    	if ( ! class_exists( 'ZipArchive' ) || ! $zip->open( $this->archive_filepath(), ZIPARCHIVE::CREATE ) )
    	    return;

		if ( ! $this->database_only ) {

			$files_added = 0;

			foreach ( $this->files() as $file ) {

			    if ( is_dir( trailingslashit( $this->root() ) . $file ) )
					$zip->addEmptyDir( trailingslashit( $file ) );

			    elseif ( is_file( trailingslashit( $this->root() ) . $file ) )
					$zip->addFile( trailingslashit( $this->root() ) . $file, $file );

				if ( ++$files_added % 500 === 0 )
					if ( ! $zip->close() || ! $zip->open( $this->archive_filepath(), ZIPARCHIVE::CREATE ) )
						return;

			}

		}

		// Add the database
		if ( ! $this->files_only )
			$zip->addFile( $this->database_dump_filepath(), $this->database_dump_filename() );

		if ( $zip->status )
			$this->warning( $this->archive_method, $zip->status );

		if ( $zip->statusSys )
			$this->warning( $this->archive_method, $zip->statusSys );

		$zip->close();

		$this->check_archive();

	}

	/**
	 * Fallback for creating zip archives if zip command and ZipArchive are
	 * unnavailable.
	 *
	 * Uses the PclZip library that ships with WordPress
	 *
	 * @access public
	 * @param string $path
	 */
	public function pcl_zip() {

		$this->errors_to_warnings( $this->archive_method );
		$this->archive_method = 'pclzip';

		global $_hmbkp_exclude_string;

		$_hmbkp_exclude_string = $this->exclude_string( 'regex' );

		$this->load_pclzip();

		$archive = new PclZip( $this->archive_filepath() );

		// Zip up everything
		if ( ! $this->database_only )
			if ( ! $archive->add( $this->root(), PCLZIP_OPT_REMOVE_PATH, $this->root(), PCLZIP_CB_PRE_ADD, 'hmbkp_pclzip_callback' ) )
				$this->warning( $this->archive_method, $archive->errorInfo( true ) );

		// Add the database
		if ( ! $this->files_only )
			if ( ! $archive->add( $this->database_dump_filepath(), PCLZIP_OPT_REMOVE_PATH, $this->path() ) )
				$this->warning( $this->archive_method, $archive->errorInfo( true ) );

		unset( $GLOBALS['_hmbkp_exclude_string'] );

		$this->check_archive();

	}

	/**
	 * Verify that the archive is valid and contains all the files it should contain.
	 *
	 * @access public
	 * @return bool
	 */
	public function check_archive() {

		// If we've already passed then no need to check again
		if ( ! empty( $this->archive_verified ) )
			return true;

		if ( ! file_exists( $this->archive_filepath() ) )
			$this->error( $this->archive_method(), __( 'The backup file was not created', 'hmbkp' ) );

		// Verify using the zip command if possible
		if ( $this->zip_command_path ) {

			$verify = shell_exec( escapeshellarg( $this->zip_command_path ) . ' -T ' . escapeshellarg( $this->archive_filepath() ) . ' 2> /dev/null' );

			if ( strpos( $verify, 'OK' ) === false )
				$this->error( $this->archive_method(), $verify );

		}

		// If there are errors delete the backup file.
		if ( $this->errors( $this->archive_method() ) && file_exists( $this->archive_filepath() ) )
			unlink( $this->archive_filepath() );

		if ( $this->errors( $this->archive_method() ) )
			return false;

		return $this->archive_verified = true;

	}

	/**
	 * Generate the array of files to be backed up by looping through
	 * root, ignored unreadable files and excludes
	 *
	 * @access public
	 * @return array
	 */
	public function files() {

		if ( ! empty( $this->files ) )
			return $this->files;

		$this->files = array();

		if ( defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINKS' ) ) {

			$filesystem = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root(), RecursiveDirectoryIterator::FOLLOW_SYMLINKS ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

			$excludes = $this->exclude_string( 'regex' );

			foreach ( $filesystem as $file ) {

			    if ( ! $file->isReadable() ) {
			        $this->unreadable_files[] = $file->getPathName();
			        continue;
			    }

			    $pathname = str_ireplace( trailingslashit( $this->root() ), '', $this->conform_dir( $file->getPathname() ) );

			    // Excludes
			    if ( $excludes && preg_match( '(' . $excludes . ')', $pathname ) )
			        continue;

			    // Don't include database dump as it's added separately
			    if ( basename( $pathname ) == $this->database_dump_filename() )
			    	continue;

			    $this->files[] = $pathname;

			}

		} else {

			$this->files = $this->files_fallback( $this->root() );

		}

		if ( ! empty( $this->unreadable_files ) )
			$this->warning( $this->archive_method(), __( 'The following files are unreadable and couldn\'t be backed up: ', 'hmbkp' ) . implode( ', ', $this->unreadable_files ) );

		return $this->files;

	}

	/**
	 * Fallback function for generating a filesystem
	 * array
	 *
	 * Used if RecursiveDirectoryIterator::FOLLOW_SYMLINKS isn't available
	 *
	 * @access private
	 * @param stromg $dir
	 * @param array $files. (default: array())
	 * @return array
	 */
	private function files_fallback( $dir, $files = array() ) {

	    $handle = opendir( $dir );

	    $excludes = $this->exclude_string( 'regex' );

	    while ( $file = readdir( $handle ) ) :

	    	// Ignore current dir and containing dir and any unreadable files or directories
	    	if ( $file == '.' || $file == '..' )
	    		continue;

	    	$filepath = $this->conform_dir( trailingslashit( $dir ) . $file );
	    	$file = str_ireplace( trailingslashit( $this->root() ), '', $filepath );

	    	if ( ! is_readable( $filepath ) ) {
				$this->unreadable_files[] = $filepath;
				continue;
	    	}

	    	// Skip the backups dir and any excluded paths
	    	if ( ( $excludes && preg_match( '(' . $excludes . ')', $file ) ) )
	    		continue;

	    	$files[] = $file;

	    	if ( is_dir( $filepath ) )
	    		$files = $this->files_fallback( $filepath, $files );

		endwhile;

		return $files;

	}

	private function load_pclzip() {

		// Load PclZip
		if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) )
			define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( $this->path() ) );

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

	}

	/**
	 * Attempt to work out the path to mysqldump
	 *
	 * @access public
	 * @return bool
	 */
	public function guess_mysqldump_command_path() {

		if ( ! $this->shell_exec_available() )
			return '';

		// List of possible mysqldump locations
		$mysqldump_locations = array(
			'/usr/local/bin/mysqldump',
			'/usr/local/mysql/bin/mysqldump',
			'/usr/mysql/bin/mysqldump',
			'/usr/bin/mysqldump',
			'/opt/local/lib/mysql6/bin/mysqldump',
			'/opt/local/lib/mysql5/bin/mysqldump',
			'/opt/local/lib/mysql4/bin/mysqldump',
			'/xampp/mysql/bin/mysqldump',
			'/Program Files/xampp/mysql/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 6.0/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 5.4/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 5.1/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 5.0/bin/mysqldump',
			'/Program Files/MySQL/MySQL Server 4.1/bin/mysqldump'
		);

		if ( is_null( shell_exec( 'hash mysqldump 2>&1' ) ) )
			return 'mysqldump';

		// Find the one which works
		foreach ( $mysqldump_locations as $location )
		    if ( @file_exists( $this->conform_dir( $location ) ) )
	 	    	return $location;

		return '';

	}

	/**
	 * Attempt to work out the path to the zip command
	 *
	 * @access public
	 * @return bool
	 */
	public function guess_zip_command_path() {

		// Check shell_exec is available and hasn't been explicitly bypassed
		if ( ! $this->shell_exec_available() )
			return '';

		// List of possible zip locations
		$zip_locations = array(
			'/usr/bin/zip'
		);

		if ( is_null( shell_exec( 'hash zip 2>&1' ) ) )
			return 'zip';

		// Find the one which works
		foreach ( $zip_locations as $location )
			if ( @file_exists( $this->conform_dir( $location ) ) )
				return $location;

		return '';

	}

	/**
	 * Generate the exclude param string for the zip backup
	 *
	 * Takes the exclude rules and formats them for use with either
	 * the shell zip command or pclzip
	 *
	 * @access public
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

		// The PclZip fallback library
		} elseif ( $context == 'regex' ) {
			$wildcard = '([\s\S]*?)';
			$separator = '|';

		}

		// Sanitize the excludes
		$excludes = array_filter( array_unique( array_map( 'trim', (array) $this->excludes ) ) );

		// If path() is inside root(), exclude it
		if ( strpos( $this->path(), $this->root() ) !== false )
			$excludes[] = trailingslashit( $this->path() );

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
			$rule = str_ireplace( $this->root(), '', untrailingslashit( $this->conform_dir( $rule ) ) );

			// Strip the preceeding slash
			if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
				$rule = substr( $rule, 1 );

			// Escape string for regex
			if ( $context == 'regex' )
				$rule = str_replace( '.', '\.', $rule );

			// Convert any existing wildcards
			if ( $wildcard != '*' && strpos( $rule, '*' ) !== false )
				$rule = str_replace( '*', $wildcard, $rule );

			// Wrap directory fragments and files in wildcards for zip
			if ( $context == 'zip' && ( $fragment || $file ) )
				$rule = $wildcard . $rule . $wildcard;

			// Add a wildcard to the end of absolute url for zips
			if ( $context == 'zip' && $absolute )
				$rule .= $wildcard;

			// Add and end carrot to files for pclzip but only if it doesn't end in a wildcard
			if ( $file && $context == 'regex' )
				$rule .= '$';

			// Add a start carrot to absolute urls for pclzip
			if ( $absolute && $context == 'regex' )
				$rule = '^' . $rule;

		}

		// Escape shell args for zip command
		if ( $context == 'zip' )
			$excludes = array_map( 'escapeshellarg', array_unique( $excludes ) );

		return implode( $separator, $excludes );

	}

	/**
	 * Check whether safe mode is active or not
	 *
	 * @access private
	 * @return bool
	 */
	public function is_safe_mode_active() {

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

		// Can we issue a simple command
		if ( ! @shell_exec( 'pwd' ) )
			return false;

		return true;

	}

	/**
	 * Sanitize a directory path
	 *
	 * @access public
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
		if ( ! $recursive && $this->conform_dir( $dir, true ) != $dir )
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

	    if ( ! empty( $a_name ) && $a_name != '*' ) {

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
	    $sql_file .= "\n";
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
	    $result = mysql_query( $query, $this->db );

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
	    $result = mysql_query( $query, $this->db );

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

	    		if ( ! isset($row[$j] ) ) {
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

	/**
	 * Get the errors
	 *
	 * @access public
	 * @return null
	 */
	public function errors( $context = null ) {

		if ( ! empty( $context ) )
			return isset( $this->errors[$context] ) ? $this->errors[$context] : array();

		return $this->errors;

	}


	/**
	 * Add an error to the errors stack
	 *
	 * @access private
	 * @param string $context
	 * @param mixed $error
	 * @return null
	 */
	private function error( $context, $error ) {

		if ( empty( $context ) || empty( $error ) )
			return;

		$this->errors[$context][$_key = md5( implode( ':' , (array) $error ) )] = $error;

	}

	/**
	 * Migrate errors to warnings
	 *
	 * @access private
	 * @param string $context. (default: null)
	 * @return null
	 */
	private function errors_to_warnings( $context = null ) {

		$errors = empty( $context ) ? $this->errors() : array( $context => $this->errors( $context ) );

		if ( empty( $errors ) )
			return;

		foreach ( $errors as $error_context => $errors )
			foreach( $errors as $error )
				$this->warning( $error_context, $error );

		if ( $context )
			unset( $this->errors[$context] );

		else
			$this->errors = array();

	}

	/**
	 * Get the warnings
	 *
	 * @access public
	 * @return null
	 */
	public function warnings( $context = null ) {

		if ( ! empty( $context ) )
			return isset( $this->warnings[$context] ) ? $this->warnings[$context] : array();

		return $this->warnings;

	}


	/**
	 * Add an warning to the warnings stack
	 *
	 * @access private
	 * @param string $context
	 * @param mixed $warning
	 * @return null
	 */
	private function warning( $context, $warning ) {

		if ( empty( $context ) || empty( $warning ) )
			return;

		$this->warnings[$context][$_key = md5( implode( ':' , (array) $warning ) )] = $warning;

	}


	/**
	 * Custom error handler for catching errors
	 *
	 * @access private
	 * @param string $type
	 * @param string $message
	 * @param string $file
	 * @param string $line
	 * @return null
	 */
	public function error_handler( $type ) {

		if ( in_array( $type, array( E_STRICT, E_DEPRECATED ) ) || error_reporting() === 0 )
			return false;

		$args = func_get_args();

		$this->warning( 'php', array_splice( $args, 0, 4 ) );

		return false;

	}

}

/**
 * Add file callback for PclZip, excludes files
 * and sets the database dump to be stored in the root
 * of the zip
 *
 * @access private
 * @param string $event
 * @param array &$file
 * @return bool
 */
function hmbkp_pclzip_callback( $event, &$file ) {

	global $_hmbkp_exclude_string;

    // Don't try to add unreadable files.
    if ( ! is_readable( $file['filename'] ) || ! file_exists( $file['filename'] ) )
    	return false;

    // Match everything else past the exclude list
    elseif ( $_hmbkp_exclude_string && preg_match( '(' . $_hmbkp_exclude_string . ')', $file['stored_filename'] ) )
    	return false;

    return true;

}