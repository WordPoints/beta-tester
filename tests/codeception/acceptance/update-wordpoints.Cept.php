<?php

/**
 * Test updating WordPoints to a development version.
 *
 * @package WordPoints
 * @since 1.0.4
 */

// Force WordPress to re-check for updates.
delete_site_transient( 'update_plugins' );

$I = new AcceptanceTester( $scenario );
$I->wantTo( 'Update WordPoints to the latest beta version' );
$I->amLoggedInAsAdminOnPage( 'wp-admin/plugins.php' );
$I->see( 'There is a new version of WordPoints available' );
$I->click( '#wordpoints-update .open-plugin-details-modal' );
$I->switchToIFrame( 'TB_iframeContent' );
$I->see( 'Commit Log' );
$I->see( 'Last Updated:' );
$I->click( 'Install Update Now' );
$I->switchToIFrame();
$I->waitForJqueryAjax( 30 );
$I->see( 'Updated!' );

// EOF
