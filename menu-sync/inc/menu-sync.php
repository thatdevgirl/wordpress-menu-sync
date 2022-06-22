<?php

/**
 * Main menu syncing functionality.
 */

namespace ThatDevGirl\MenuSync;

class MenuSync {

  /**
   * __construct()
   */
  public function __construct() {
    
  }

}


if ( is_admin() ) {
  new MenuSync();
}
