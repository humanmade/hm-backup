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
	 */
	public function setUp() {

		$this->backup = new HM_Backup();

		if ( ! file_exists( WP_CONTENT_DIR . '/custom' ) )
			mkdir( WP_CONTENT_DIR . '/custom' );

	}

	public function tearDown() {

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

		if ( file_exists( $this->backup->get_database_dump_filepath() ) )
			unlink( $this->backup->get_database_dump_filepath() );

		if ( file_exists( WP_CONTENT_DIR . '/custom' ) )
			unlink( WP_CONTENT_DIR . '/custom' );

		unset( $this->backup );

	}

	/**
	 * Check that the default path is correct
	 *
	 * @access public
	 */
	public function testDefaultBackupPath() {

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/backups' ), $this->backup->get_path() );

	}

	/**
	 * What if the backup path is in root
	 *
	 * @access public
	 */
	public function testRootBackupPath() {

		$this->backup->set_path( '/' );
		$this->backup->set_archive_filename( 'backup.zip' );

		$this->assertEquals( '/', $this->backup->get_path() );
		$this->assertEquals( '/backup.zip', $this->backup->get_archive_filepath() );

		if ( ! is_writable( $this->backup->get_path() ) )
			$this->markTestSkipped( 'Root not writable' );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + archive filename correctly sets the archive filepath
	 *
	 * @access public
	 */
	public function testCustomBackupPath() {

		$this->backup->set_path( WP_CONTENT_DIR . '/custom' );
		$this->backup->set_archive_filename( 'backup.zip' );

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/custom/backup.zip' ), $this->backup->get_archive_filepath() );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + archive filename correctly sets the archive filepath
	 *
	 * @access public
	 */
	public function testUTF8BackupPath() {

		$this->backup->set_path( WP_CONTENT_DIR . '/custom' );
		$this->backup->set_archive_filename( 'sphärenriss.zip' );

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/custom/spharenriss.zip' ), $this->backup->get_archive_filepath() );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + database dump filename correctly sets the database dump filepath
	 *
	 * @access public
	 */
	public function testCustomDatabaseDumpPath() {

		$this->backup->set_path( WP_CONTENT_DIR . '/custom' );
		$this->backup->set_database_dump_filename( 'dump.sql' );

		$this->assertEquals( $this->backup->conform_dir( WP_CONTENT_DIR . '/custom/dump.sql' ), $this->backup->get_database_dump_filepath() );

		$this->backup->mysqldump();

		$this->assertFileExists( $this->backup->get_database_dump_filepath() );

	}

}