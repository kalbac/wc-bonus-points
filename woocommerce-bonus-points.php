<?php

/*
 * Plugin Name: Bonus Points
 * Plugin URI: http://woodev.ru
 * Description: Adding bonus points system into customer profile.
 * Version: 1.0
 * Author: Maksim Martirosov
 * Author URI: http://martirosoff.ru
 * Project: wc-bonus-points
 * License: The MIT License (MIT)
 *
 * @package  Woocommerce Bonus Points
 * @category Checkout
 * @author   Maksim Martirosov
 * @license  MIT
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

final class WoocommerceBonusPoints {

	/**
	 * A reference to an instance of this class.
	 *
	 * @var   object
	 */
	protected static $_instance = null;

	/**
	 * Trigger checks is woocoomerce active or not
	 *
	 * @var   bool
	 */
	protected $has_woocommerce = null;
	/**
	 * Main WooCommerce Bonus Points Instance
	 * @return WoocommerceBonusPoints
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Main constructor
	 */
	public function __construct() {

		if ( ! $this->has_woocommerce() ) {
			// TODO: add admin notice about need activated woocommerce plugin
			return false;
		}

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

	public function has_woocommerce() {
		if ( null == $this->has_woocommerce ) {
			$this->has_woocommerce = in_array(
				'woocommerce/woocommerce.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
			);
		}
		return $this->has_woocommerce;
	}

}

return WoocommerceBonusPoints::instance();