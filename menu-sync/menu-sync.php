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

require_once( 'inc/menu-sync.php' );
