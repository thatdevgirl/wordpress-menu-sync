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
    // Create the synced menu (if it does not already exist) on init. Yes, this
    // runs on every page load, but we want to make sure the menu exists and the
    // callback exits right away if it does already exist.
    add_action( 'init', [ $this, 'create_menu' ], 99 );

    // Action for when posts are saved.
    add_action( 'save_post_page', [ $this, 'update_item' ], 10, 2 );
  }


  /**
   * create_menu()
   *
   * Create a new synced menu, assuming it does not already exist.
   *
   * @return void
   */
  public function create_menu(): void {
    // Check to see if our custom synced menu already exists.
    $menu_exists = wp_get_nav_menu_object( self::MENU_NAME );

    if ( !$menu_exists ) {
      // If the synced menu does not exist, create it.
      $menu_id = wp_create_nav_menu( self::MENU_NAME );

      // Initialize the synced menu with a list of current pages.
      $this->sync_menu( $menu_id );

      // Add the menu ID as a WP option, since we will need it for updates later.
      // Using update_option here instead of add_option in case this option
      // already exists from a previous plugin activation.
      update_option( 'gu_synced_menu_id', $menu_id );
    }
  }


  /**
   * sync_menu()
   *
   * Sync the menu with the list of pages, preserving the page order and parents
   * set from within each page.
   *
   * @param int $menu_id
   *
   * @return void
   */
  private function sync_menu( $menu_id=0 ): void {
    // First, get all pages. Default for this WP function is to get all
    // published pages only.
    $all_pages = get_pages();

    // Initialize an array to serve as a mapping between page IDs and menu
    // database IDs. We will need this mapping later to mimic the page
    // hierarchy inside the menu.
    $menu_id_map = [];

    // Loop through all of the pages to create a new menu item for each one.
    foreach ( $all_pages as $page ) {
      $menu_id_map = $this->add_item( $page, $menu_id, $menu_id_map );
    }

    // JSON encode the page / DB ID map and add it as a WP option, since we
    // will need it for updates later.
    // Using update_option here instead of add_option in case this option
    // already exists from a previous plugin activation.
    update_option( 'gu_synced_menu_mapping', json_encode( $menu_id_map ) );
  }


  /**
   * add_item()
   */
  private function add_item( $page, $menu_id, $menu_id_map ): array {
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
    if ( !array_key_exists( $page_id, $menu_id_map ) ) {
      $menu_id_map[$page_id] = $item_db_id;
    }

    // If this menu item has a parent, add its parentage to the menu.
    if ( $page->post_parent ) {
      // Get the parent menu database ID from the page / DB ID map, based
      // on the page's parent ID. If, for some reason the parent page does
      // not exist in the mapping, we can't set its parent, so set the ID to 0.
      // If we cannot set the parent, it's possible that the child page was
      // created before its parent (and therefore has a lower post ID).
      // That's a problem for another day.
      $parent_db_id = $menu_id_map[$page->post_parent] ?? 0;

      // Update this page's menu parent.
      update_post_meta( $item_db_id, '_menu_item_menu_item_parent', $parent_db_id );
    }

    // Return the page / DB ID mapping.
    return $menu_id_map;
  }


  /**
   * update_item()
   *
   * Update the synced menu to add a new page to the menu, remove a deleted
   * page from the menu, or update the info of an existing page.
   *
   * @param int $post_id
   * @param object $post
   *
   * @return void
   */
  public function update_item( $post_id, $post ) {
    // Do nothing if this page is unpublished.
    if ( $post->post_status !== 'publish' ) { return; }

    // Get the synced menu ID and page / DB ID mapping from the options.
    $menu_id = get_option( 'gu_synced_menu_id' );
    $menu_id_map = json_decode( get_option( 'gu_synced_menu_mapping' ), true );

    // If, for some reason, we didn't get the menu ID or the mapping, we
    // can't do anything. Exit.
    if ( !$menu_id || !$menu_id_map ) { return; }

    // Get the page's DB ID from the mapping.
    $db_id = $menu_id_map[ $post_id ] ?? 0;

    // If the page is already in the mapping, update its parent and position.
    // Everything else (e.g. title and permalink) is updated automatically
    // by WordPress.
    if ( $db_id ) {
      // Parent. (Get the new parent from the mapping.)
      $parent_db_id = $menu_id_map[ $post->post_parent ] ?? 0;
      update_post_meta( $db_id, '_menu_item_menu_item_parent', $parent_db_id );

      // Menu order.
      // TODO: This isn't actually working. no idea why.
      update_post_meta( $db_id, '_menu_item_menu_item_position', $post->menu_order );
    }
    // Otherwise, add the page to the menu and the mapping.
    else {
      $menu_id_map = $this->add_item( $post, $menu_id, $menu_id_map );
      update_option( 'gu_synced_menu_mapping', json_encode( $menu_id_map ) );
    }
  }

}

new MenuSync();
