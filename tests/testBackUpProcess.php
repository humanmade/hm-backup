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
		$this->backup->path = dirname( __FILE__ ) . '/tmp';

		mkdir( $this->backup->path() );

		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );
		remove_action( 'hmbkp_backup_complete', 'hmbkp_backup_complete' );

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

		if ( file_exists( $this->backup->path() ) )
			rmdir( $this->backup->path() );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithCommands() {

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( 'Empty zip command path' );

        if ( ! $this->backup->mysqldump_command_path )
            $this->markTestSkipped( 'Empty mysqldump command path' );

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'zip' );
		$this->assertEquals( $this->backup->mysqldump_method(), 'mysqldump' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt', $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );


	}

	/**
	 * Test a full backup with the ZipArchive
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZipArchiveMysqldumpFallback() {

		$this->backup->zip_command_path = false;
		$this->backup->mysqldump_command_path = false;

		$this->assertTrue( class_exists( 'ZipArchive' ) );

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'ziparchive' );
		$this->assertEquals( $this->backup->mysqldump_method(), 'mysqldump_fallback' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt', $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a full backup with the PclZip
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithPclZipAndMysqldumpFallback() {

		$this->backup->zip_command_path = false;
		$this->backup->mysqldump_command_path = false;

		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'pclzip' );
		$this->assertEquals( $this->backup->mysqldump_method(), 'mysqldump_fallback' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt', $this->backup->database_dump_filename ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a files only backup with the zip command
	 *
	 * @access public
	 * @return null
	 */
	function testFileOnlyWithZipCommand() {

		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'zip' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a files only backup with ZipArchive
	 *
	 * @access public
	 * @return null
	 */
	function testFileOnlyWithZipArchive() {

		$this->backup->files_only = true;
		$this->backup->zip_command_path = false;

		$this->assertTrue( class_exists( 'ZipArchive' ) );

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'ziparchive' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a files only backup with PclZip
	 *
	 * @access public
	 * @return null
	 */
	function testFileOnlyWithPclZip() {

		$this->backup->files_only = true;
		$this->backup->zip_command_path = false;

		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->archive_method(), 'pclzip' );

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->errors() );

	}

}