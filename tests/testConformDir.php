<?php

/**
 * Tests that the conform_dir method
 * properly normalized various combinations of slashes
 *
 * @extends WP_UnitTestCase
 */
class testConformDirTestCase extends WP_UnitTestCase {

	/**
 	 * The correct dir
	 *
	 * @var string
	 * @access protected
	 */
	protected $dir;

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function setUp() {

		$this->backup = new HM_Backup;
		$this->dir = '/one/two/three';

	}

	public function testBackSlash() {

		$this->assertEquals( $this->backup->conform_dir( $this->dir ), $this->dir );

	}

	public function testForwardSlash() {

		$this->assertEquals( $this->backup->conform_dir( '\one\two\three' ), $this->dir );

	}

	public function testTrailingSlash() {

		$this->assertEquals( $this->backup->conform_dir( '/one/two/three/' ), $this->dir );

	}

	public function testDoubleBackSlash() {

		$this->assertEquals( $this->backup->conform_dir( '//one//two//three' ), $this->dir );

	}

	public function testDoubleForwardSlash() {

		$this->assertEquals( $this->backup->conform_dir( '\\one\\two\\three' ), $this->dir );

	}

	public function testMixedSlashes() {

		$this->assertEquals( $this->backup->conform_dir( '\/one\//\two\/\\three' ), $this->dir );

	}

}