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
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->root = dirname( __FILE__ ) . '/test-data/';
		$this->backup->path = dirname( __FILE__ ) . '/tmp';
		$this->backup->files_only = true;

		mkdir( $this->backup->path );

		$this->symlink = dirname( __FILE__ ) . '/test-data/' . basename( __FILE__ );

		symlink( __FILE__, $this->symlink );

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

		if ( file_exists( $this->backup->path ) )
			rmdir( $this->backup->path );

		if ( file_exists( $this->symlink ) )
			unlink( $this->symlink );

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveSymlinkFileWithZip() {

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveSymlinkFileWithZipArchive() {

		$this->backup->zip_command_path = false;

		$this->assertFileExists( $this->symlink );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

	}

	/**
	 * Test an unreadable file with the PclZip commands
	 *
	 * @access public
	 * @return null
	 */
	function testArchiveSymlinkFileWithPclZip() {

		$this->backup->zip_command_path = false;

		$this->assertFileExists( $this->symlink );

		$this->backup->pcl_zip();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$this->assertArchiveContains( $this->backup->archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 4 );

	}

}