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

	}

	function testExcludeAbsoluteDirPathWithPclZip() {

		$this->backup->excludes = '/exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAbsoluteRootDirPathWithZip() {

		$this->backup->excludes = dirname( __FILE__ ) . '/test-data/exclude/';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAbsoluteRootDirPathWithPclZip() {

		$this->backup->excludes = '/Users/willmot/Dropbox/Sites/WordPress/wp-content/plugins/backupwordpress/hm-backup/tests/test-data/exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeDirPathFragmentWithZip() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeDirPathFragmentWithPclZip() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithZip() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithPclZip() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAbsoluteFilePathWithZip() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

        $this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsoluteFilePathWithPclZip() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithZip() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithPclZip() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileWithZip() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZip() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileNameWithZip() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileNameWithPclZip() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileExtensionWithZip() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->pcl_zip();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testWildCardWithZip() {

		$this->backup->excludes = '*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->zip();

		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 0 );

	}

	function testWildCardWithPclZip() {

		$this->backup->excludes = '*';
		$this->backup->files_only = true;

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->pcl_zip();

		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 0 );

	}

}