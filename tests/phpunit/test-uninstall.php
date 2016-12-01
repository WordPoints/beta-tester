<?php

/**
 * Test case for the module's unisntallation routine.
 *
 * @package WordPoints_Beta_Tester
 * @since 1.0.4
 */

/**
 * Test that the module uninstalls itself properly.
 *
 * @since 1.0.4
 */
class WordPoints_Beta_Tester_Uninstall_Test
	extends WordPoints_Dev_Lib_PHPUnit_TestCase_Module_Uninstall {

	/**
	 * Tests uninstallation.
	 *
	 * @since 1.0.4
	 */
	public function test() {

		add_site_option( 'wordpoints_beta_version', 'test' );

		$this->uninstall();

		$this->assertEmpty( get_site_option( 'wordpoints_beta_version' ) );
	}
}

// EOF
