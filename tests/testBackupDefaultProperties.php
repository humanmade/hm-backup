<?php

/**
 * Test the the property getters works
 *
 * @extends WP_UnitTestCase
 */
class BackUpWordPressPropertiesTestCase extends WP_UnitTestCase {

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

		$this->backup = new HMBackup();

	}

	/**
	 * Check that the default path is correct
	 *
	 * @access public
	 * @return null
	 */
	function testDefaultBackupPath() {

		$this->assertEquals( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backups' , $this->backup->path );

	}

	/**
	 * Make sure settings a custom path + archive filename correctly sets the archive filepath
	 *
	 * @access public
	 * @return null
	 */
	function testCustomArchivePath() {

		$this->backup->path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom';
		$this->backup->archive_filename = 'backup.zip';

		$this->assertEquals( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom/backup.zip' , $this->backup->archive_filepath() );

	}

	/**
	 * Make sure settings a custom path + database dump filename correctly sets the database dump filepath
	 *
	 * @access public
	 * @return null
	 */
	function testCustomDatabaseDumpPath() {

		$this->backup->path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom';
		$this->backup->database_dump_filename = 'dump.sql';

		$this->assertEquals( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom/dump.sql' , $this->backup->database_dump_filepath() );

	}

}