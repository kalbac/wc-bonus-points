<?php

/*
 * Plugin Name: Bonus Points
 * Plugin URI: http://woodev.ru
 * Description: Adding bonus points system into customer profile.
 * Version: 1.0.2
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
	 * Contain notice text if woocommerce not activated
	 *
	 * @var   string
	 */
	private $notice;
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
			$this->notice = __('Для работы плагина необходимо установить WooCommerce.');
			return false;
		}

		$this->includes();

		add_action( 'woocommerce_before_my_account', array( $this, 'before_account_notice' ) );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'show_bonus_points_in_cart' ) );
		add_filter( 'woocommerce_get_discounted_price', array( $this, 'add_bonus_discounted'), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_script' ) );

		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'checkout_update' ) );

		/*
		 * Actions for admin panel only
		 */
		if( is_admin() ) {
			$this->admin_includes();

			if( class_exists( 'Plugin_Updater' ) )
				new WBP_Plugin_Updater( __FILE__, 'kalbac', 'wc-bonus-points' );

			add_filter( 'manage_users_columns', array( $this, 'add_user_bonus_column' ) );
			add_action( 'manage_users_custom_column',  array( $this, 'show_user_bonus_column' ), 10, 3);
			add_action( 'admin_init', array( $this, 'update_user_bonus_points' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}

	}

	private function includes() {

	}

	private function admin_includes() {
		include_once( 'updater/wp-github-plugin-updater.php' );
	}

	public function add_user_bonus_column( $columns ) {
		$columns['user_bonus'] 		= __('Бонусы');
		$columns['user_actions']    = __( 'Действие' );
		return $columns;
	}

	public function show_user_bonus_column( $value, $column_name, $user_id ) {

		switch( $column_name ) {
			case 'user_bonus' :
				return sprintf('<input type="number" min="0" name="user_bonus[%d]" value="%d" placeholder="%s" >', $user_id, get_user_meta( $user_id, '_user_bonus', true ), __('Укажите бонусы') );
				break;
			case 'user_actions' :
				return sprintf( '<button type="submit" class="button update_bonus">%s</button>%s', __('Обновить'), wp_nonce_field( 'bonus_points', 'bonus_points_nonce', false, false ) );
				break;
		}

		return $value;
	}

	public function update_user_bonus_points() {
		if ( isset( $_GET['bonus_points_nonce']) && check_admin_referer( 'bonus_points', 'bonus_points_nonce' ) ) {
			$bonus_points = $_GET['user_bonus'];

			if( is_array( $bonus_points ) ) {
				foreach( $bonus_points as $user_id => $points ) {

					$current_points = absint( get_user_meta( $user_id, '_user_bonus', true ) );

					if( $current_points !== $points ) {
						update_user_meta( $user_id, '_user_bonus', $points );
					}
				}
			}

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) );
			die();
		}

	}

	public function enqueue_scripts( $hook ) {
		if ( 'users.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'admin_user_bonus_points', plugin_dir_url( __FILE__ ) . 'admin-style.css' );
	}

	public function wp_enqueue_script() {
		if( is_checkout() ) {
			wp_enqueue_style( 'bonus-points', plugin_dir_url( __FILE__ ) . 'style.css' );
			wp_enqueue_script( 'bonus-points', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), null, true );
		}
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

	public function admin_notice() {
		if( ! empty( $this->notice ) ) {
			printf('<div class="error"><p>%s</p></div>', $this->notice );
		}
	}

	public function before_account_notice() {

		$customer = get_user_by( 'id', get_current_user_id() );

		if( $customer->_user_bonus > 0 ) {

			$message = sprintf(__('%s, у Вас есть <strong>%s</strong> бонусов. Вы можете использовать их как скидку при <a href="%s">покупке</a> товара.'), $customer->display_name, $customer->_user_bonus, wc_get_page_permalink( 'shop' ) );

			wc_get_template( 'notices/notice.php', array(
				'messages'	=> array( $message )
			) );
		}
	}

	public function show_bonus_points_in_cart() {
		$customer = get_user_by( 'id', get_current_user_id() );
		if( $customer->_user_bonus && $customer->_user_bonus > 0 ) {

			$checked_on = WC()->session->get( 'customer_use_points' ) == 'yes';

			printf('<tr class="customer-bonus-points"><th>%s</th><td><input type="checkbox" name="use_bonus_points" %s class="check_box_radio" id="use_bonus_points"><label class="check_label_radio" for="use_bonus_points">Использовать %s</label></td></tr>', __('Бонусы' ), checked( $checked_on, true, false ), $this->plural_form( $customer->_user_bonus, array( __('Бонус'), __('Бонуса'), __('Бонусов') ) ) );
		}
	}

	private function plural_form( $number, $after = array() ) {
		$cases = array(2, 0, 1, 1, 1, 2);
		return sprintf( '%s %s', $number, $after[ ( $number % 100 > 4 && $number % 100 < 20 ) ? 2 : $cases[ min( $number % 10, 5 ) ] ] );
	}

	public function add_bonus_discounted( $price, $values, $cart ) {
		$customer = get_user_by( 'id', get_current_user_id() );
		$use_points = WC()->session->get( 'customer_use_points' );

		if( $use_points && $use_points == 'yes' && $customer->_user_bonus && $customer->_user_bonus > 0 ) {
			$price = $price - $customer->_user_bonus;
		}
		return $price;
	}

	public function checkout_update( $post ) {
		if( ! empty( $post ) ) {
			parse_str( $post, $data );

			WC()->session->set( 'customer_use_points', 'no' );

			if( ! empty( $data['use_bonus_points'] ) ) {
				$use_points = $data['use_bonus_points'] == 'on';
				if( $use_points ) {
					WC()->session->set( 'customer_use_points', 'yes' );
				}
			}
		}
	}

}

return WoocommerceBonusPoints::instance();