<?php

/**
 * Plugin Name: Menu Sync
 * Description: WordPress plugin to sync pages and their hierarchy with a menu.
 * Version: 1.0
 * Author: Joni Halabi
 * Author URI: https://jhalabi.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
  exit;
}

// Start the menu syncing only after init; make it a really low priority
// to make sure the menu is actually created.
function menu_sync_init() {
  require_once( 'inc/menu-sync.php' );
}

add_action( 'init', 'menu_sync_init', 99 );
