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
	 * The temporary directory to write the backup file too
	 *
	 * @var string
	 * @access protected
	 */
	protected $tmp;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 * @access public
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->root = dirname( __FILE__ ) . '/test-data/';
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

		if ( file_exists( $this->backup->archive_filepath() ) )
			unlink( $this->backup->archive_filepath() );

		if ( file_exists( $this->tmp ) )
			rmdir( $this->tmp );

	}

	function testBackUpPathIsExcludedByDefault() {

		$this->assertEquals( $this->backup->excludes(), array( trailingslashit( $this->backup->path ) ) );

	}

	function testDefaultExcludesAreMergedWithUserExcludes() {

		$this->backup->excludes = '/exclude/';

		$this->assertEquals( $this->backup->excludes(), array( trailingslashit( $this->backup->path ), $this->backup->excludes ) );

	}

	function testExcludeAbsoluteDirPathWithZipCommand() {

		$this->backup->excludes = '/exclude/';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAbsoluteDirPathWithZipFallback() {

		$this->backup->excludes = '/exclude/';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeDirPathFragmentWithZipCommand() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeDirPathFragmentWithZipFallback() {

		$this->backup->excludes = 'exclude/';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithZipCommand() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAmbiguousAbsoluteDirPathWithZipFallback() {

		$this->backup->excludes = 'exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 1 );

	}

	function testExcludeAbsoluteFilePathWithZipCommand() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsoluteFilePathWithZipFallback() {

		$this->backup->excludes = '/exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithZipCommand() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsoluteFilePathWithZipFallback() {

		$this->backup->excludes = 'exclude/exclude.exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileWithZipCommand() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileWithZipFallback() {

		$this->backup->excludes = '/exclude/*';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZipCommand() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZipFallback() {

		$this->backup->excludes = 'exclude/*';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileNameWithZipCommand() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileNameWithZipFallback() {

		$this->backup->excludes = '*.exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}
	
	function testExcludeAbsolutePathWithWildcardFileNameWithZipCommand() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileNameWithZipFallback() {

		$this->backup->excludes = '/exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZipCommand() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZipFallback() {

		$this->backup->excludes = 'exclude/*.exclude';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileExtensionWithZipCommand() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeWildcardFileExtensionWithZipFallback() {

		$this->backup->excludes = 'exclude.*';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}
	
	function testExcludeAbsolutePathWithWildcardFileExtensionWithZipCommand() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAbsolutePathWithWildcardFileExtensionWithZipFallback() {

		$this->backup->excludes = '/exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZipCommand() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}

	function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZipFallback() {

		$this->backup->excludes = 'exclude/exclude.*';
		$this->backup->files_only = true;

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 2 );

	}
	
	function testWildCardWithZipCommand() {
		
		$this->backup->excludes = '*';
		$this->backup->files_only = true;

		$this->assertNotEmpty( $this->backup->zip_command_path );

		$this->backup->backup();

		$this->assertArchiveFileCount( $this->backup->archive_filepath(), 0 );
		
	}

}