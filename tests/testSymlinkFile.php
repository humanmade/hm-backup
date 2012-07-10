<?php

/**
 * Tests for the Archive process with symlinks
 *
 * @extends WP_UnitTestCase
 */
class testSymlinkFileTestCase extends WP_UnitTestCase {

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
	 */
	public function setUp() {

		if ( ! function_exists( 'symlink' ) )
			$this->markTestSkipped( 'symlink function not defined' );

		$this->backup = new HM_Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );
		$this->backup->set_type( 'file' );

		mkdir( $this->backup->get_path() );

		$this->symlink = dirname( __FILE__ ) . '/test-data/' . basename( __FILE__ );

		symlink( __FILE__, $this->symlink );

		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		if ( ! function_exists( 'symlink' ) )
			return;

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

		if ( file_exists( $this->backup->get_path() ) )
			rmdir( $this->backup->get_path() );

		if ( file_exists( $this->symlink ) )
			unlink( $this->symlink );

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkFileWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkFileWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the PclZip commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkFileWithPclZip() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->symlink );

		$this->backup->pcl_zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}