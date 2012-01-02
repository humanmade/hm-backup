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
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->root = dirname( __FILE__ ) . '/test-data/';
		$this->backup->path = dirname( __FILE__ ) . '/tmp';

		mkdir( $this->backup->path );

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

		if ( file_exists( $this->backup->archive_filepath() ) )
			unlink( $this->backup->archive_filepath() );

		if ( file_exists( $this->backup->path ) )
			rmdir( $this->backup->path );

	}

	function testExcludeAbsoluteDirPathWithZip() {

		$this->backup->excludes = '/exclude/';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
		      $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsoluteDirPathWithPclZip() {

		$this->backup->excludes = '/exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsoluteRootDirPathWithZip() {

		$this->backup->excludes = dirname( __FILE__ ) . '/test-data/exclude/';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsoluteRootDirPathWithPclZip() {

		$this->backup->excludes = dirname( __FILE__ ) . '/test-data/exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeDirPathFragmentWithZip() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeDirPathFragmentWithPclZip() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithZip() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithPclZip() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsoluteFilePathWithZip() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

        $this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsoluteFilePathWithPclZip() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithZip() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithPclZip() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileWithZip() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZip() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeWildcardFileNameWithZip() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeWildcardFileNameWithPclZip() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeWildcardFileExtensionWithZip() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->errors() );

	}

	function testWildCardWithZip() {

		$this->backup->excludes = '*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 0 );

		// Expect an error "Nothing to do"
		$this->assertNotEmpty( $this->backup->errors() );

	}

	function testWildCardWithPclZip() {

		$this->backup->excludes = '*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 0 );

		// Expect a nothing to do! error
		$this->assertNotEmpty( $this->backup->errors() );

	}

}