<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testDatabaseDumpTestCase extends WP_UnitTestCase {

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

		if ( file_exists( $this->backup->database_dump_filepath() ) )
			unlink( $this->backup->database_dump_filepath() );

		if ( file_exists( $this->tmp ) )
			rmdir( $this->tmp );

	}
	
	/**
	 * Test a database dump with the zip command
	 * 
	 * @access public
	 * @return null
	 */
	function testDatabaseDumpWithMysqldump() {
		
		$this->assertNotEmpty( $this->backup->zip_command_path );
		
		$this->backup->mysqldump();
		
		$this->assertFileExists( $this->backup->database_dump_filepath() );
		
	}

	/**
	 * Test a database dump with the PHP fallback
	 * 
	 * @access public
	 * @return null
	 */
	function testDatabaseDumpWithFallback() {
		
		$this->backup->mysqldump_fallback();
		
		$this->assertFileExists( $this->backup->database_dump_filepath() );
		
	}
	
}