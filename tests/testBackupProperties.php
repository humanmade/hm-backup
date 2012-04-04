<?php

/**
 * Test the the property getters works
 *
 * @extends WP_UnitTestCase
 */
class testPropertiesTestCase extends WP_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object
	 *
	 * @access public
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();

	}

	/**
	 * Check that the default path is correct
	 *
	 * @access public
	 * @return null
	 */
	function testDefaultBackupPath() {

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/backups' ), $this->backup->get_path() );

	}
	
	function testRootBackupPath() {
		
		$this->backup->set_path( '/' );
		$this->backup->set_archive_filename( 'backup.zip' );
		
		$this->assertEquals( '/', $this->backup->get_path() );
		$this->assertEquals( '/backup.zip', $this->backup->get_archive_filepath() );
		
	}

	/**
	 * Make sure setting a custom path + archive filename correctly sets the archive filepath
	 *
	 * @access public
	 * @return null
	 */
	function testCustomBackupPath() {

		$this->backup->set_path( WP_CONTENT_DIR . '/custom' );
		$this->backup->set_archive_filename( 'backup.zip' );

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/custom/backup.zip' ), $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + database dump filename correctly sets the database dump filepath
	 *
	 * @access public
	 * @return null
	 */
	function testCustomDatabaseDumpPath() {

		$this->backup->set_path( WP_CONTENT_DIR . '/custom' );
		$this->backup->set_database_dump_filename( 'dump.sql' );

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/custom/dump.sql' ), $this->backup->get_database_dump_filepath() );

	}

}