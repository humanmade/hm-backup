<?php

/**
 * Tests for the Archive process with unreadble files
 *
 * @extends WP_UnitTestCase
 */
class testUnreadableFileTestCase extends WP_UnitTestCase {

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
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );
		$this->backup->set_type( 'file' );

		mkdir( $this->backup->get_path() );

		chmod( $this->backup->get_root() . '/test-data.txt', 0220 );

		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 * @return null
	 */
	function tearDown() {

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

		if ( file_exists( $this->backup->get_path() ) )
			rmdir( $this->backup->get_path() );

		chmod( $this->backup->get_root() . '/test-data.txt', 0664 );

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveUnreadableFileWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->assertFalse( is_readable( $this->backup->get_root() . '/test-data.txt' ) );

		$this->backup->zip();

		$this->assertEmpty( $this->backup->errors() );
		
		$this->assertNotEmpty( $this->backup->warnings() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveUnreadableFileWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFalse( is_readable( $this->backup->get_root() . '/test-data.txt' ) );

		$this->backup->zip_archive();

		$this->assertEmpty( $this->backup->errors() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

	}

	/**
	 * Test an unreadable file with the PclZip commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveUnreadableFileWithPclZip() {

		$this->backup->set_zip_command_path( false );

		$this->assertFalse( is_readable( $this->backup->get_root() . '/test-data.txt' ) );

		$this->backup->pcl_zip();

		$this->assertEmpty( $this->backup->errors() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

	}

}