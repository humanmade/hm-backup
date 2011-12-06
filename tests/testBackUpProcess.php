<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testBackUpProcessTestCase extends WP_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 * @access public
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->root = dirname( __FILE__ ) . '/test-data/';
		$this->backup->path = $this->tmp = dirname( __FILE__ ) . '/tmp';

		mkdir( $this->tmp );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 * @return null
	 */
	function tearDown() {

		if ( file_exists( $this->backup->archive_filepath() ) )
			unlink( $this->backup->archive_filepath() );

		if ( file_exists( $this->tmp ) )
			rmdir( $this->tmp );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithCommands() {

		$this->assertNotEmpty( $this->backup->zip_command_path );
		$this->assertNotEmpty( $this->backup->mysqldump_command_path );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt', $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );


	}

	/**
	 * Test a full backup with the fallbacks
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithFallbacks() {

		$this->backup->zip_command_path = $this->backup->mysqldump_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt', $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

	}

	/**
	 * Test a files only backup with the zip command
	 *
	 * @access public
	 * @return null
	 */
	function testFileOnlyWithZipCommand() {

		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 3 );

	}

	/**
	 * Test a files only backup with the PHP fallback
	 *
	 * @access public
	 * @return null
	 */
	function testFileOnlyWithArchiveFallback() {

		$this->backup->files_only = true;
		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 3 );

	}

	/**
	 * Test a database only backup with the mysqldump command
	 *
	 * @access public
	 * @return null
	 */
	function testDatabaseOnlyWithMysqldumpCommand() {

		$this->backup->database_only = true;
		$this->assertNotEmpty( $this->backup->mysqldump_command_path );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	/**
	 * Test a files only backup with the shell command
	 *
	 * @access public
	 * @return null
	 */
	function testDatabaseOnlyWithMysqldumpFallback() {

		$this->backup->database_only = true;
		$this->backup->mysqldump_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	/**
	 * Test a database only backup with the mysqldump command and the zip command
	 *
	 * @access public
	 * @return null
	 */
	function testDatabaseOnlyWithMysqldumpCommandAndZipCommand() {

		$this->backup->database_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );
		$this->assertNotEmpty( $this->backup->mysqldump_command_path );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	/**
	 * Test a database only backup with the mysqldump fallback and the zip fallback
	 *
	 * @access public
	 * @return null
	 */
	function testDatabaseOnlyWithMysqldumpFallbackAndZipFallback() {

		$this->backup->database_only = true;
		$this->backup->mysqldump_command_path = $this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

}