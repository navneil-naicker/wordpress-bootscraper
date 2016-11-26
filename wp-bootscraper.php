<?php
/**
	Plugin Name: WordPress Bootscraper
	Plugin URI: http://www.navz.me
	Description: Scrap out things you don't need on your WordPress
	Version: 1.0.0
	Author: Navneil Naicer
	Author URI: http://www.navz.me
	License: GPLv2 or later
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
	
	Copyright 2016 Navneil Naicker

*/

//Preventing from direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class wpbootscraper{
	
	private $slug = 'wp-bootscraper';
	private $menu = 'WP Bootscraper';
	private $title = 'WordPress Bootscraper';
	private $admin_url = null;
	private $plugin_url = null;
	private $plugin_path = null;
	
	public function __construct(){
		$this->admin_url = admin_url('/tools.php?page=' . $this->slug);
		$this->plugin_path = dirname(__FILE__);
		$this->plugin_url = plugin_dir_url( $this->plugin_path . '/' . $this->slug);
		add_action('admin_menu', array($this, 'menu'));
		add_action('admin_enqueue_scripts', array($this, 'scripts'));
	}
	
	public function menu(){
		add_management_page($this->title, $this->menu, 'manage_options', $this->slug, array($this, 'wpbootscraper_page'));	
	}
		
	public function wpbootscraper_page(){
		require_once( dirname(__FILE__) . '/templates/settings.php' );
	}
		
	public function scripts($hook){
		if ( 'tools_page_wp-bootscraper' != $hook ){
			return;
    }
		wp_enqueue_style($this->slug, $this->plugin_url . $this->slug . '.css');
    wp_enqueue_script($this->slug, $this->plugin_url . $this->slug . '.js', array(), '1.0', true);
	}
	
	public function frontend(){
		$settings = get_option('wp_bootscraper_frontend');
		$settings = unserialize($settings);
		
		if( !empty($settings['frontend_wp_emoji']) ){
			function wp_bootscraper_disable_wp_emojicons() {
				remove_action('admin_print_styles', 'print_emoji_styles');
				remove_action('wp_head', 'print_emoji_detection_script', 7);
				remove_action('admin_print_scripts', 'print_emoji_detection_script');
				remove_action('wp_print_styles', 'print_emoji_styles');
				remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
				remove_filter('the_content_feed', 'wp_staticize_emoji');
				remove_filter('comment_text_rss', 'wp_staticize_emoji');
				add_filter('tiny_mce_plugins', 'disable_emojicons_tinymce');
			}
			add_action( 'init', 'wp_bootscraper_disable_wp_emojicons' );
		}
		
		if( !empty($settings['frontend_json_api_links']) ){
			remove_action('wp_head', 'rest_output_link_wp_head');
			remove_action('wp_head', 'wp_oembed_add_discovery_links');
			remove_action('template_redirect', 'rest_output_link_header', 11, 0);
		}
		
		if( !empty($settings['frontend_remove_dns_prefetch_to_s_w_org']) ){
			function remove_dns_prefetch( $hints, $relation_type ) {
				if ( 'dns-prefetch' === $relation_type ) {
					return array_diff( wp_dependencies_unique_hosts(), $hints );
				}
				return $hints;
			}
			add_filter( 'wp_resource_hints', 'remove_dns_prefetch', 10, 2 );
		}

		if( !empty($settings['frontend_remove_wp_json']) ){
			remove_action( 'wp_head', 'rsd_link');
		}

		if( !empty($settings['frontend_remove_wp_embed_min_js']) ){
			// Remove the REST API endpoint.
			remove_action( 'rest_api_init', 'wp_oembed_register_route' );
			// Turn off oEmbed auto discovery.
			add_filter( 'embed_oembed_discover', '__return_false' );
			// Don't filter oEmbed results.
			remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
			// Remove oEmbed discovery links.
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			// Remove oEmbed-specific JavaScript from the front-end and back-end.
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			// Remove all embeds rewrite rules.
			add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );
		}

		if( !empty($settings['frontend_remove_wordpress_generator']) ){
			remove_action('wp_head', 'wp_generator');
		}

		if( !empty($settings['frontend_remove_wlwmanifest_link']) ){
			remove_action( 'wp_head', 'wlwmanifest_link');
		}
				
	}
	
	public function admin(){
		$settings = get_option('wp_bootscraper_administrator');
		$settings = unserialize($settings);
		
		if( !empty($settings['administrator_remove_wp_logo_from_toolbar']) ){
				add_action( 'admin_bar_menu', 'remove_wp_logo', 999 );
				function remove_wp_logo( $wp_admin_bar ) {
					$wp_admin_bar->remove_node( 'wp-logo' );
				}
		}
		
		if( !empty($settings['administrator_remove_comment_link_from_toolbar']) ){
			function my_admin_bar_render() {
				global $wp_admin_bar;
				$wp_admin_bar->remove_menu('comments');
			}
			add_action( 'wp_before_admin_bar_render', 'my_admin_bar_render' );
		}
		
		if( !empty($settings['administrator_remove_new_link_from_toolbar']) ){
			add_action( 'admin_bar_menu', 'remove_wp_nodes', 999 );
			function remove_wp_nodes(){
				global $wp_admin_bar;   
				$wp_admin_bar->remove_node( 'new-content' );
			}
		}
		
		if( !empty($settings['administrator_remove_post_from_side_menu']) ){
			function remove_post_menu(){
				remove_menu_page('edit.php');
			}
			add_action('admin_menu', 'remove_post_menu');
		}
		
		if( !empty($settings['administrator_remove_comments_from_side_menu']) ){
			function remove_comment_menu(){
				remove_menu_page('edit-comments.php');
			}
			add_action('admin_menu', 'remove_comment_menu');
		}
		
		if( !empty($settings['administrator_move_acf_under_settings']) ){
			add_action('admin_menu', 'move_acf_under_settings');
			function move_acf_under_settings() {
				add_filter('acf/settings/show_admin', '__return_false');
				add_options_page('Custom Fields', 'Custom Fields', 'manage_options', '/edit.php?post_type=acf');
			}
			function remove_acf_menu() {
				remove_menu_page('edit.php?post_type=acf');
			}
			add_action( 'admin_menu', 'remove_acf_menu', 999);
		}
		
		if( !empty($settings['administrator_move_ctp_ui_under_settings']) ){
			add_action('admin_menu', 'move_cpt_ui_under_settings');
			function move_cpt_ui_under_settings() {
				add_options_page('Post Types', 'Post Types', 'manage_options', '/admin.php?page=cptui_manage_post_types');
			}
		}
		
		if( !empty($settings['administrator_remove_trackback_metabox']) ){
			function remove_trackbacksdiv() {
				remove_meta_box( 'trackbacksdiv', 'page', 'normal' );
			}
			add_action( 'admin_menu', 'remove_trackbacksdiv' );
		}
		
		if( !empty($settings['administrator_remove_comment_metabox']) ){
			function remove_commentsdiv() {
				remove_meta_box( 'commentstatusdiv', 'page', 'normal' );
				remove_meta_box( 'commentsdiv', 'page', 'normal' );
			}
			add_action( 'admin_menu', 'remove_commentsdiv' );
		}
		
		if( !empty($settings['administrator_remove_author_metabox']) ){
			function remove_authordiv() {
				remove_meta_box( 'authordiv', 'page', 'normal' );
			}
			add_action( 'admin_menu', 'remove_authordiv' );
		}
		
		if( !empty($settings['administrator_remove_custom_fields_metabox']) ){
			function remove_postcustom() {
				remove_meta_box( 'postcustom', 'page', 'normal' );
			}
			add_action( 'admin_menu', 'remove_postcustom' );
		}
		
		if( !empty($settings['administrator_remove_slug_metabox']) ){
			function remove_slugdiv() {
				remove_meta_box( 'slugdiv', 'page', 'normal' );
			}
			add_action( 'admin_menu', 'remove_slugdiv' );
		}
		
		if( !empty($settings['administrator_add_support_excerpt_metabox']) ){
			function administrator_add_support_excerpt_metabox() {
				add_post_type_support( 'page', 'excerpt' );
			}
			add_action( 'init', 'administrator_add_support_excerpt_metabox' );
		}
		
		if( !empty($settings['administrator_add_support_featured_image_metabox']) ){
			function administrator_add_support_featured_image_metabox() {
				add_theme_support( 'post-thumbnails' );
				add_post_type_support( 'page', 'thumbnail' );
			}
			add_action( 'init', 'administrator_add_support_featured_image_metabox' );
		}
		
		if( !empty($settings['administrator_footer_thankyou']) ){
			function administrator_footer_thankyou(){
				$settings = get_option('wp_bootscraper_administrator');
				$settings = unserialize($settings);
				echo html_entity_decode(stripslashes_deep(!empty($settings['administrator_footer_thankyou_text'])? $settings['administrator_footer_thankyou_text']: ''));
			}
			add_filter('admin_footer_text', 'administrator_footer_thankyou');
		}
		
		if( !empty($settings['administrator_remove_post_by_email_from_writing']) ){
			add_filter( 'enable_post_by_email_configuration', '__return_false' );
		}
		
		if( !empty($settings['administrator_completely_turn_off_commenting_functionality']) ){
			// Disable support for comments and trackbacks in post types
			function df_disable_comments_post_types_support() {
				$post_types = get_post_types();
				foreach ($post_types as $post_type) {
					if(post_type_supports($post_type, 'comments')) {
						remove_post_type_support($post_type, 'comments');
						remove_post_type_support($post_type, 'trackbacks');
					}
				}
			}
			add_action('admin_init', 'df_disable_comments_post_types_support');
			
			// Close comments on the front-end
			function df_disable_comments_status(){
				return false;
			}
			add_filter('comments_open', 'df_disable_comments_status', 20, 2);
			add_filter('pings_open', 'df_disable_comments_status', 20, 2);
			
			// Hide existing comments
			function df_disable_comments_hide_existing_comments($comments) {
				$comments = array();
				return $comments;
			}
			add_filter('comments_array', 'df_disable_comments_hide_existing_comments', 10, 2);
			
			// Remove comments page in menu
			function df_disable_comments_admin_menu() {
				remove_menu_page('edit-comments.php');
			}
			add_action('admin_menu', 'df_disable_comments_admin_menu');
			
			// Redirect any user trying to access comments page
			function df_disable_comments_admin_menu_redirect() {
				global $pagenow;
				if ($pagenow === 'edit-comments.php') {
					wp_redirect(admin_url()); exit;
				}
			}
			add_action('admin_init', 'df_disable_comments_admin_menu_redirect');
			
			// Remove comments metabox from dashboard
			function df_disable_comments_dashboard() {
				remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
			}
			add_action('admin_init', 'df_disable_comments_dashboard');
			
			// Remove comments links from admin bar
			function df_disable_comments_admin_bar() {
				if (is_admin_bar_showing()) {
					remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
				}
			}
			add_action('init', 'df_disable_comments_admin_bar');
		}
		
	}
}

//Save all the checked choices
add_action( 'wp_ajax_save_wp_bootscraper', 'save_wp_bootscraper' );
add_action( 'wp_ajax_nopriv_save_wp_bootscraperr', 'save_wp_bootscraper' );
function save_wp_bootscraper(){
	if( !empty($_POST['nonce_save_wp_bootscraper']) and wp_verify_nonce($_POST['nonce_save_wp_bootscraper'], 'nonce_save_wp_bootscraper')){
		$request = $_POST;
		$section = $request['section'];
		if( in_array($section,['frontend', 'administrator']) ){
			$data = array();
			if( $section == 'frontend' ){
				if( !empty($request['frontend_wp_emoji']) and $request['frontend_wp_emoji'] == 1 ){
					$data['frontend_wp_emoji'] = 1;
				}
				if( !empty($request['frontend_json_api_links']) and $request['frontend_json_api_links'] == 1 ){
					$data['frontend_json_api_links'] = 1;
				}
				if( !empty($request['frontend_remove_dots_from_excerpt']) and $request['frontend_remove_dots_from_excerpt'] == 1 ){
					$data['frontend_remove_dots_from_excerpt'] = 1;
				}
				if( !empty($request['frontend_remove_dns_prefetch_to_s_w_org']) and $request['frontend_remove_dns_prefetch_to_s_w_org'] == 1 ){
					$data['frontend_remove_dns_prefetch_to_s_w_org'] = 1;
				}
				if( !empty($request['frontend_remove_wp_json']) and $request['frontend_remove_wp_json'] == 1 ){
					$data['frontend_remove_wp_json'] = 1;
				}
				if( !empty($request['frontend_remove_wp_embed_min_js']) and $request['frontend_remove_wp_embed_min_js'] == 1 ){
					$data['frontend_remove_wp_embed_min_js'] = 1;
				}
				if( !empty($request['frontend_remove_wordpress_generator']) and $request['frontend_remove_wordpress_generator'] == 1 ){
					$data['frontend_remove_wordpress_generator'] = 1;
				}
				if( !empty($request['frontend_remove_wlwmanifest_link']) and $request['frontend_remove_wlwmanifest_link'] == 1 ){
					$data['frontend_remove_wlwmanifest_link'] = 1;
				}
			} else if( $section == 'administrator' ){
				if( !empty($request['administrator_remove_wp_logo_from_toolbar']) and $request['administrator_remove_wp_logo_from_toolbar'] == 1 ){
					$data['administrator_remove_wp_logo_from_toolbar'] = 1;
				}
				if( !empty($request['administrator_remove_comment_link_from_toolbar']) and $request['administrator_remove_comment_link_from_toolbar'] == 1 ){
					$data['administrator_remove_comment_link_from_toolbar'] = 1;
				}
				if( !empty($request['administrator_remove_new_link_from_toolbar']) and $request['administrator_remove_new_link_from_toolbar'] == 1 ){
					$data['administrator_remove_new_link_from_toolbar'] = 1;
				}
				if( !empty($request['administrator_remove_post_from_side_menu']) and $request['administrator_remove_post_from_side_menu'] == 1 ){
					$data['administrator_remove_post_from_side_menu'] = 1;
				}
				if( !empty($request['administrator_remove_comments_from_side_menu']) and $request['administrator_remove_comments_from_side_menu'] == 1 ){
					$data['administrator_remove_comments_from_side_menu'] = 1;
				}
				if( !empty($request['administrator_move_acf_under_settings']) and $request['administrator_move_acf_under_settings'] == 1 ){
					$data['administrator_move_acf_under_settings'] = 1;
				}
				if( !empty($request['administrator_move_ctp_ui_under_settings']) and $request['administrator_move_ctp_ui_under_settings'] == 1 ){
					$data['administrator_move_ctp_ui_under_settings'] = 1;
				}
				if( !empty($request['administrator_remove_trackback_metabox']) and $request['administrator_remove_trackback_metabox'] == 1 ){
					$data['administrator_remove_trackback_metabox'] = 1;
				}
				if( !empty($request['administrator_remove_comment_metabox']) and $request['administrator_remove_comment_metabox'] == 1 ){
					$data['administrator_remove_comment_metabox'] = 1;
				}
				if( !empty($request['administrator_remove_author_metabox']) and $request['administrator_remove_author_metabox'] == 1 ){
					$data['administrator_remove_author_metabox'] = 1;
				}
				if( !empty($request['administrator_remove_custom_fields_metabox']) and $request['administrator_remove_custom_fields_metabox'] == 1 ){
					$data['administrator_remove_custom_fields_metabox'] = 1;
				}
				if( !empty($request['administrator_remove_slug_metabox']) and $request['administrator_remove_slug_metabox'] == 1 ){
					$data['administrator_remove_slug_metabox'] = 1;
				}
				if( !empty($request['administrator_add_support_excerpt_metabox']) and $request['administrator_add_support_excerpt_metabox'] == 1 ){
					$data['administrator_add_support_excerpt_metabox'] = 1;
				}
				if( !empty($request['administrator_add_support_featured_image_metabox']) and $request['administrator_add_support_featured_image_metabox'] == 1 ){
					$data['administrator_add_support_featured_image_metabox'] = 1;
				}
				if( !empty($request['administrator_completely_turn_off_commenting_functionality']) and $request['administrator_completely_turn_off_commenting_functionality'] == 1 ){
					$data['administrator_completely_turn_off_commenting_functionality'] = 1;
				}
				if( !empty($request['administrator_remove_post_by_email_from_writing']) and $request['administrator_remove_post_by_email_from_writing'] == 1 ){
					$data['administrator_remove_post_by_email_from_writing'] = 1;
				}
				if( !empty($request['administrator_footer_thankyou']) and $request['administrator_footer_thankyou'] == 1 ){
					$data['administrator_footer_thankyou'] = 1;
				}
				if( !empty($request['administrator_footer_thankyou_text']) and $request['administrator_footer_thankyou_text'] == 1 ){
					$data['administrator_footer_thankyou_text'] = sanitize_text_field($request['administrator_footer_thankyou_text']);
				}
			}
			
			$data = array_filter($data);
			$data = serialize($data);
			update_option('wp_bootscraper_' . $section, $data);
		}		
		
		die();
	}
}

//Init the class
$wpbootscraper = new wpbootscraper();

//Only run the frontend filters
if( !is_admin() ){
	$wpbootscraper->frontend();
}

//Only run the administrator filters
if( is_admin() ){
	$wpbootscraper->admin();
}




