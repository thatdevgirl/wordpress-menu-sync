<?php

/**
 * Main menu syncing functionality.
 */

namespace ThatDevGirl\MenuSync;

class MenuSync {

  private const MENU_NAME = 'Synced Menu';


  /**
   * __construct()
   */
  public function __construct() {
    $this->create();
  }


  /**
   * create()
   *
   * Create a new synced menu, assuming it does not already exist.
   *
   * @return void
   */
  private function create(): void {
    // Check to see if our custom synced menu already exists.
    $menu_exists = wp_get_nav_menu_object( self::MENU_NAME );

    if ( !$menu_exists ) {
      // If the synced menu does not exist, create it.
      $menu_id = wp_create_nav_menu( self::MENU_NAME );

      // Initialize the synced menu with a list of current pages.
      $this->sync( $menu_id );
    }
  }


  /**
   * sync()
   *
   * Sync the menu with the list of pages, preserving the page order and parents
   * set from within each page.
   *
   * @param int $menu_id
   *
   * @return void
   */
  private function sync( $menu_id=0 ): void {
    // First, get all pages. Default for this WP function is to get all
    // published pages only.
    $all_pages = get_pages();

    // Initialize an array to serve as a mapping between page IDs and menu
    // database IDs. We will need this mapping later to mimic the page
    // hierarchy inside the menu.
    $page_id_db_id_map = [];

    // Loop through all of the pages to create a new menu item for each one.
    foreach ( $all_pages as $page ) {
      // Extract the page ID, for reading ease.
      $page_id = $page->ID;

      // Add the menu item for the current page, saving its database ID.
      $item_db_id = wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-title'     => $page->post_title,
        'menu-item-object-id' => $page_id,
        'menu-item-object'    => 'page',
        'menu-item-status'    => 'publish',
        'menu-item-type'      => 'post_type',
        'menu-item-position'  => $page->menu_order,
      ] );

      // If this page is not in the page / DB ID map, add it.
      if ( !array_key_exists( $page_id, $page_id_db_id_map ) ) {
        $page_id_db_id_map[$page_id] = $item_db_id;
      }

      // If this menu item has a parent, add its parentage to the menu.
      if ( $page->post_parent ) {
        // Get the parent menu database ID from the page / DB ID map, based
        // on the page's parent ID. If, for some reason the parent page does
        // not exist in the mapping, we can't set its parent, so set the ID to 0.
        // If we cannot set the parent, it's possible that the child page was
        // created before its parent (and therefore has a lower post ID).
        // That's a problem for another day.
        $parent_db_id = $page_id_db_id_map[$page->post_parent] ?? 0;

        // Update this page's menu parent.
        update_post_meta( $item_db_id, '_menu_item_menu_item_parent', $parent_db_id );
      }
    }
  }

}

new MenuSync();
