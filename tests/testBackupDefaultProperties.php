<?php

class BackUpWordPressPropertiesTestCase extends WP_UnitTestCase {

	function testDefaultBackupPath() {
		
		$backup = new HMBackup();
		
		$this->assertEquals( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backups' , $backup->path );
		
	}
	
	function testCustomBackupPath() {
	
		$backup = new HMBackup();
		$backup->path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom';
		
		$this->assertEquals( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'custom' , $backup->path );
	
	}
		
}