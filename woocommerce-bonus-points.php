<?php

/*
Plugin Name: Bonus Points
Plugin URI: http://woodev.ru
Description: Adding bonus points system into customer profile.
Version: 1.0
Author: Maksim Martirosov
Author URI: http://martirosoff.ru
Project: wc-bonus-points
License: A "Slug" license name e.g. GPL2
*/

class WBP {

	protected static $_instance = null;
	/**
	 * Main WooCommerce Bonus Points Instance
	 * @return WBP
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Main construct
	 */
	public function __construct() {

		add_filter('manage_users_columns', array( $this, 'add_user_bonus_column' ) );
		add_action('manage_users_custom_column',  array( $this, 'show_user_bonus_column' ), 10, 3);
	}

	private function add_user_bonus_column( $columns ) {
		$columns['user_bonus'] = __('Бонусы');
		return $columns;
	}

	private function show_user_bonus_column( $value, $column_name, $user_id ) {
		$user = get_userdata( $user_id );
		if ( 'user_bonus' == $column_name )
			return sprintf('<input type="number" min="0" name="user_bonus[%d]" value="%d" placeholder="%s" >', $user_id, $user->user_bonus, __('Укажите бонусы') );
		return $value;
	}

}

return WBP::instance();