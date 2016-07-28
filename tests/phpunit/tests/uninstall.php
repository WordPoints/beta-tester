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
 *
 * @group uninstall
 */
class WordPoints_Beta_Tester_Uninstall_Test extends WordPoints_Module_Uninstall_UnitTestCase {

	/**
	 * @since 1.0.4
	 */
	public function setUp() {

		$this->module_file = 'beta-tester/beta-tester.php';

		parent::setUp();
	}

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
