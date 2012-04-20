<?php

/**
 * Tests for excludes logic of the back up
 * files process
 *
 * @extends WP_UnitTestCase
 */
class testExcludesTestCase extends WP_UnitTestCase {

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

		$this->backup = new HM_Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );

		mkdir( $this->backup->get_path() );

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

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

		if ( file_exists( $this->backup->get_path() ) )
			rmdir( $this->backup->get_path() );

	}

	public function testExcludeAbsoluteDirPathWithZip() {

		$this->backup->set_excludes( '/exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
		      $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsoluteDirPathWithPclZip() {

		$this->backup->set_excludes( '/exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsoluteRootDirPathWithZip() {

		$this->backup->set_excludes( dirname( __FILE__ ) . '/test-data/exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsoluteRootDirPathWithPclZip() {

		$this->backup->set_excludes( dirname( __FILE__ ) . '/test-data/exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeDirPathFragmentWithZip() {

		$this->backup->set_excludes( 'exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeDirPathFragmentWithPclZip() {

		$this->backup->set_excludes( 'exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsoluteDirPathWithZip() {

		$this->backup->set_excludes( 'exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsoluteDirPathWithPclZip() {

		$this->backup->set_excludes( 'exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsoluteFilePathWithZip() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

        $this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsoluteFilePathWithPclZip() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsoluteFilePathWithZip() {

		$this->backup->set_excludes( 'exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsoluteFilePathWithPclZip() {

		$this->backup->set_excludes( 'exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileWithZip() {

		$this->backup->set_excludes( '/exclude/*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->set_excludes( '/exclude/*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZip() {

		$this->backup->set_excludes( 'exclude/*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->set_excludes( 'exclude/*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeWildcardFileNameWithZip() {

		$this->backup->set_excludes( '*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( '*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->set_excludes( '/exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( '/exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->set_excludes( 'exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( 'exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( 'exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( 'exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( '/exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( '/exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( 'exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( 'exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	public function testWildCardWithZip() {

		$this->backup->set_excludes( '*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 0 );

		// Expect an error "Nothing to do"
		$this->assertNotEmpty( $this->backup->errors() );

	}

	public function testWildCardWithPclZip() {

		$this->backup->set_excludes( '*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 0 );

		$this->assertEmpty( $this->backup->errors() );

	}

}