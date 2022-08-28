<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://metallic.io
 * @since      1.0.0
 *
 * @package    Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/admin
 * @author     Metallic
 */
class Metallic_Post_Migration_Admin{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->version = $version;
		$this->enable_post_migration = get_option('global_enable_post_migration');
		$this->timeout = 300;
		$this->secrectkey = 'metallic_import_section_key';
		
		add_action( 'add_meta_boxes', array($this, 'mpm_global_notice_meta_box') );

		if($this->enable_post_migration == 1){
			add_action('save_post', array( $this,'save_post_mpm_migrate_postdata' ),20,3);
			add_action('wp_trash_post', array( $this, 'trash_post_mpm_migrate_postdata') );
			add_action('untrash_post', array( $this, 'untrash_post_mpm_migrate_postdata') );
			add_action("draft_to_publish",array( $this, 'draft_to_publish_mpm_migrate_postdata'),20,1);
			add_action("delete_post",array( $this, 'delete_post_mpm_migrate_postdata'),10,1);
		}
		

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Metallic_Post_Migration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Metallic_Post_Migration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/metallic-post-migration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Metallic_Post_Migration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Metallic_Post_Migration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/metallic-post-migration-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Initialize the admin menu under custom post type and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu(){

		add_menu_page(
			'Post Migration Settings',
			__( 'Post Migration Settings', 'metallic-post-migration' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_display' )
		);
	}

	/**
	 * admin settings page
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_display(){

		/**
		 * if the admin settings form is submitted
		 */
		if( isset( $_POST['save_migration_settings'] ) ){
			
			if ( ! isset( $_POST['postmig_nonce'] ) 
    			|| ! wp_verify_nonce( sanitize_text_field($_POST['postmig_nonce']), 'postmig_nonce_action' ) 
			){
				die( 'Failed security check' );
			}

			if(isset($_POST['global_enable_post_mig'])){
				if(sanitize_text_field($_POST['global_enable_post_mig']) === 'on'){
					update_option('global_enable_post_migration', 1);
				}
			}else{
				update_option('global_enable_post_migration', 0);
			}

			if(isset($_POST['global_production_link']) && !empty(sanitize_text_field($_POST['global_production_link']))){
				update_option('global_production_link', sanitize_text_field($_POST['global_production_link']));
			}else{
				update_option('global_production_link', '');
			}

			if(isset($_POST['global_is_staging'])){
				if(sanitize_text_field($_POST['global_is_staging']) === 'on'){
					update_option('global_is_staging', 1);
				}
			}else{
				update_option('global_is_staging', 0);
			}
			
		}

		/**
		 * if the Import to Production form is submitted
		 */
		if( isset( $_POST['import_all_mig'] ) ){
			$all_posts = array();
			if ( ! isset( $_POST['postmig_nonce'] ) 
    			|| ! wp_verify_nonce( sanitize_text_field($_POST['postmig_nonce']), 'postmig_nonce_action' ) 
			){
				die( 'Failed security check' );
			}

			$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/post';
		
			$args = array(
				'post_type' => array( 'post', 'page'),
				'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
				'posts_per_page' => -1,
			);
			$the_query = new WP_Query( $args );
			global $post;
			// The Loop
			if ( $the_query->have_posts() ) {
				
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$is_migration_post = get_post_meta($post->ID, 'is_migration_post',true);
					if($is_migration_post == 1){
						$single_post = (array)$post;
					
						$category_detail = get_the_category($post->ID);
						if(!empty($category_detail)){
							
							foreach($category_detail as $category){
								$single_post['category'][] = $category;
							}
						}

						if(has_post_thumbnail($post->ID)){
							$single_post['post_thumb'] = get_the_post_thumbnail_url($post->ID, 'full'); 
						}

						$post_meta = get_post_meta($post->ID);
						if(!empty($post_meta)){
							$single_post['metadata'] = $post_meta;
						}
						
						$single_post['home_url'] = home_url();
				
						$body = wp_json_encode( $single_post, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES );
						
						$options = [
							'body'        => $body,
							'headers'     => [
								'Content-Type' => 'application/json',
								'Authorization' => base64_encode( $this->secrectkey ),
							],
							'timeout'     => $this->timeout,
							'redirection' => 5,
							'blocking'    => true,
							'httpversion' => '1.0',
							'sslverify'   => false,
							'data_format' => 'body',
						];
						

						/*//generate post migration log
						$this->mpm_cust_log(date('Y-m-d H:i:s').'All post migration action: '.serialize($options));*/

						$api_response = wp_remote_post( $endpoint, $options );
						if ( is_wp_error( $api_response ) ) {
							$error_message = $api_response->get_error_message();
							echo esc_html('Something went wrong: '.$error_message.' for the Post ID:'.$post->ID); exit();
						}
						
						
					}
					
				}
				
			} else {
				// no posts found
			}
			/* Restore original Post Data */
			wp_reset_postdata();
			
		}

		require_once 'partials/'.$this->plugin_name.'-admin-display.php';

	}

	/**
	 * Add post meta box to all the posts
	 *
	 * @since    1.0.0
	 */
	public function mpm_global_notice_meta_box() {

		$global_enable_post_mig = get_option('global_enable_post_migration');
		$global_is_staging = get_option('global_is_staging');

		if(($global_enable_post_mig == 1) && ($global_is_staging == 1)){
			$screens = array( 'post', 'page', 'cmv_event', 'cmv_resource', 'glossary_term' );
		
			add_meta_box(
				'mpm-post-migration-metabox',
				__( 'Migrate Post', 'metallic-post-migration' ),
				array($this, 'mpm_post_migration_metabox_callback'),
				$screens,
				'side',
				'high',
			);
		}
		
	}

	/**
	 * Add post meta box to all the posts
	 *
	 * @since    1.0.0
	 */
	public function mpm_post_migration_metabox_callback( $post ) {

		wp_nonce_field( 'mpm_metabox_nonce', 'mpm_metabox_nonce' );
	
		$is_migration_post = get_post_meta( $post->ID, 'is_migration_post', true );
		$checkbox_val = ($is_migration_post == '1') ? 'checked' : '';
		echo '<p>If checked, current post will migrate to production site.</p>';
		echo '<input type="checkbox" id="is_migration_post" name="is_migration_post" '.esc_html($checkbox_val).'>';
		wp_nonce_field( 'utmmigration_nonce_action', 'utmmigration_nonce' );
		echo '<p><code>If the global import is disabled, this option won\'t work.</code></p>';
	}

	/**
	 * Save post action
	 *
	 * @since    1.0.0
	 */
	public function save_post_mpm_migrate_postdata( $post_id, $post, $update ) {

		
		if(isset($_POST['is_migration_post'])){

			if ( ! isset( $_POST['utmmigration_nonce'] ) 
			|| ! wp_verify_nonce( sanitize_text_field($_POST['utmmigration_nonce']), 'utmmigration_nonce_action' ) 
			){
				die( 'Failed security check' );
			}

			$is_migration_post = sanitize_text_field( $_POST['is_migration_post']);
			if(isset($is_migration_post) && !empty($is_migration_post) && ($is_migration_post === 'on')){
				update_post_meta($post_id, 'is_migration_post',1);
			}else{
				update_post_meta($post_id, 'is_migration_post','');
			}	

			$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
			if($is_migration_post == 1){
				$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/post';
			
				$body = (array)$post;
				
				$category_detail = get_the_category($post_id);
				if(!empty($category_detail)){
					foreach($category_detail as $category){
						$body['category'][] = $category;
					}
				}

				if(has_post_thumbnail($post_id)){
					$body['post_thumb'] = get_the_post_thumbnail_url($post_id, 'full'); 
				}

				$post_meta = get_post_meta($post->ID);
				if(!empty($post_meta)){
					$post_meta = array_combine(array_keys($post_meta), array_column($post_meta, '0'));
					$body['metadata'] = $post_meta;
				}

				$body['home_url'] = home_url();
				
				$body = wp_json_encode( $body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES );
				
				$options = [
					'body'        => $body,
					'headers'     => [
						'Content-Type' => 'application/json',
						'Authorization' => base64_encode( $this->secrectkey ),
					],
					'timeout'     => $this->timeout,
					'redirection' => 5,
					'blocking'    => true,
					'httpversion' => '1.0',
					'sslverify'   => false,
					'data_format' => 'body',
				];
				
				/*//generate post migration log
				$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin save post action: '.serialize($options));*/
				
				$api_response = wp_remote_post( $endpoint, $options );
				if ( is_wp_error( $api_response ) ) {
					$error_message = $api_response->get_error_message();
					echo esc_html("Something went wrong: $error_message");
				}
			}
		}else{
			$is_migration_post = sanitize_text_field( $_POST['is_migration_post']);
			if(isset($is_migration_post) && !empty($is_migration_post) && ($is_migration_post === 'on')){
				update_post_meta($post_id, 'is_migration_post',1);
			}else{
				update_post_meta($post_id, 'is_migration_post','');
			}
		}
	}

	/**
	 * Save post action
	 *
	 * @since    1.0.0
	 */
	public function draft_to_publish_mpm_migrate_postdata( $array ) {

		$post = $array;
		$post_id = $post->ID;

		$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
		if($is_migration_post == 1){

			
			
			$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/post';
		
			$body = (array)$post;
			
			$category_detail = get_the_category($post_id);
			if(!empty($category_detail)){
				foreach($category_detail as $category){
					$body['category'][] = $category;
				}
			}

			if(has_post_thumbnail($post_id)){
				$body['post_thumb'] = get_the_post_thumbnail_url($post_id, 'full'); 
			}

			$post_meta = get_post_meta($post->ID);
			if(!empty($post_meta)){
				$body['metadata'] = $post_meta;
			}
			
			
			$body = wp_json_encode( $body );
			
			
			$options = [
				'body'        => $body,
				'headers'     => [
					'Content-Type' => 'application/json',
					'Authorization' => base64_encode( $this->secrectkey ),
				],
				'timeout'     => $this->timeout,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'data_format' => 'body',
			];
			
			/*//generate post migration log
			$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin Draft to Publish action: '.serialize($options));*/

			$api_response = wp_remote_post( $endpoint, $options );
			if ( is_wp_error( $api_response ) ) {
				$error_message = $api_response->get_error_message();
				echo esc_html("Something went wrong: $error_message");
			}
		}
		
		
		
	}


	/**
	 * Trash post action
	 *
	 * @since    1.0.0
	 */
	public function trash_post_mpm_migrate_postdata( $post_id ) {

		if ( isset( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
			
			foreach ( $_GET['post'] as $post_id ) {
				$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
				if($is_migration_post == 1){
					
					$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/posttrash';
				
					$post_data = get_post($post_id);

					$body = (array)$post_data;
					
					$body = wp_json_encode( $body );
					
					
					$options = [
						'body'        => $body,
						'headers'     => [
							'Content-Type' => 'application/json',
							'Authorization' => base64_encode( $this->secrectkey ),
						],
						'timeout'     => $this->timeout,
						'redirection' => 5,
						'blocking'    => true,
						'httpversion' => '1.0',
						'sslverify'   => false,
						'data_format' => 'body',
					];
					
					/*//generate post migration log
					$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin Trash multiple post action: '.serialize($options));*/

					$api_response = wp_remote_post( $endpoint, $options );
					if ( is_wp_error( $api_response ) ) {
						$error_message = $api_response->get_error_message();
						echo esc_html("Something went wrong: $error_message");
					}
				}
			}
		} else {
			$post_id = sanitize_text_field($_GET['post']);
			$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
			if($is_migration_post == 1){
				
				
				$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/posttrash';
			
				$post_data = get_post($post_id);

				$body = (array)$post_data;
				
				$category_detail = get_the_category($post_id);
				if(!empty($category_detail)){
					foreach($category_detail as $category){
						$body['category'][] = $category;
					}
				}

				if(has_post_thumbnail($post_id)){
					$body['post_thumb'] = get_the_post_thumbnail_url($post_id, 'full'); 
				}

				$post_meta = get_post_meta($post->ID);
				if(!empty($post_meta)){
					$body['metadata'] = $post_meta;
				}
				
				
				$body = wp_json_encode( $body );
				
				
				$options = [
					'body'        => $body,
					'headers'     => [
						'Content-Type' => 'application/json',
						'Authorization' => base64_encode( $this->secrectkey ),
					],
					'timeout'     => $this->timeout,
					'redirection' => 5,
					'blocking'    => true,
					'httpversion' => '1.0',
					'sslverify'   => false,
					'data_format' => 'body',
				];
				
				/*//generate post migration log
				$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin Trash single post action: '.serialize($options));*/

				$api_response = wp_remote_post( $endpoint, $options );
				if ( is_wp_error( $api_response ) ) {
					$error_message = $api_response->get_error_message();
					echo esc_html("Something went wrong: $error_message");
				}
			}
		}
		
	}


	/**
	 * Untrash post action
	 *
	 * @since    1.0.0
	 */
	public function untrash_post_mpm_migrate_postdata( $post_id ) {
		
		if ( isset( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
			
			foreach ( $_GET['post'] as $post_id ) {
				$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
				if($is_migration_post == 1){
					$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/postuntrash';
				
					$post_data = get_post($post_id);

					$body = (array)$post_data;
					
					$body = wp_json_encode( $body );
					
					
					$options = [
						'body'        => $body,
						'headers'     => [
							'Content-Type' => 'application/json',
							'Authorization' => base64_encode( $this->secrectkey ),
						],
						'timeout'     => $this->timeout,
						'redirection' => 5,
						'blocking'    => true,
						'httpversion' => '1.0',
						'sslverify'   => false,
						'data_format' => 'body',
					];
					
					/*//generate post migration log
					$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin UnTrash multiple post action: '.serialize($options));*/

					$api_response = wp_remote_post( $endpoint, $options );
					if ( is_wp_error( $api_response ) ) {
						$error_message = $api_response->get_error_message();
						echo esc_html("Something went wrong: $error_message");
					}
				}
			}
		} else {
			$post_id = $_GET['post'];
			$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
			if($is_migration_post == 1){
				
				$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/postuntrash';
			
				$post_data = get_post($post_id);

				$body = (array)$post_data;
				
				$category_detail = get_the_category($post_id);
				if(!empty($category_detail)){
					foreach($category_detail as $category){
						$body['category'][] = $category;
					}
				}

				if(has_post_thumbnail($post_id)){
					$body['post_thumb'] = get_the_post_thumbnail_url($post_id, 'full'); 
				}

				$post_meta = get_post_meta($post_id);
				if(!empty($post_meta)){
					$body['metadata'] = $post_meta;
				}
				
				
				$body = wp_json_encode( $body );
				
				
				$options = [
					'body'        => $body,
					'headers'     => [
						'Content-Type' => 'application/json',
						'Authorization' => base64_encode( $this->secrectkey ),
					],
					'timeout'     => $this->timeout,
					'redirection' => 5,
					'blocking'    => true,
					'httpversion' => '1.0',
					'sslverify'   => false,
					'data_format' => 'body',
				];
				
				/*//generate post migration log
				$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin UnTrash single post action: '.serialize($options));*/

				$api_response = wp_remote_post( $endpoint, $options );
				if ( is_wp_error( $api_response ) ) {
					$error_message = $api_response->get_error_message();
					echo esc_html("Something went wrong: $error_message");
				}
			}
		}
		
	}


	/**
	 * Delete post action
	 *
	 * @since    1.0.0
	 */
	public function delete_post_mpm_migrate_postdata( $post_id ) {
		
		if(isset($_GET['action']) && $_GET['action'] === 'delete'){
			if ( isset( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
				
				foreach ( $_GET['post'] as $post_id ) {
					$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
					if($is_migration_post == 1){
						$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/postdelete';
					
						$post_data = get_post($post_id);

						$body = (array)$post_data;
						
						$body = wp_json_encode( $body );
						
						
						$options = [
							'body'        => $body,
							'headers'     => [
								'Content-Type' => 'application/json',
								'Authorization' => base64_encode( $this->secrectkey ),
							],
							'timeout'     => $this->timeout,
							'redirection' => 5,
							'blocking'    => true,
							'httpversion' => '1.0',
							'sslverify'   => false,
							'data_format' => 'body',
						];
						
						/*//generate post migration log
						$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin delete multiple post action: '.serialize($options));*/

						$api_response = wp_remote_post( $endpoint, $options );
						if ( is_wp_error( $api_response ) ) {
							$error_message = $api_response->get_error_message();
							echo esc_html("Something went wrong: $error_message");
						}
					}
				}
			} else {
				$post_id = $_GET['post'];
				$is_migration_post = get_post_meta($post_id, 'is_migration_post',true);
				if($is_migration_post == 1){
					
					$endpoint = Metallic_Post_Migration_Prod_Link.'/wp-json/metallic-migration/v1/postdelete';
				
					$post_data = get_post($post_id);

					$body = (array)$post_data;
					
					$category_detail = get_the_category($post_id);
					if(!empty($category_detail)){
						foreach($category_detail as $category){
							$body['category'][] = $category;
						}
					}

					if(has_post_thumbnail($post_id)){
						$body['post_thumb'] = get_the_post_thumbnail_url($post_id, 'full'); 
					}

					$post_meta = get_post_meta($post_id);
					if(!empty($post_meta)){
						$body['metadata'] = $post_meta;
					}
					
					
					$body = wp_json_encode( $body );
					
					
					$options = [
						'body'        => $body,
						'headers'     => [
							'Content-Type' => 'application/json',
							'Authorization' => base64_encode( $this->secrectkey ),
						],
						'timeout'     => $this->timeout,
						'redirection' => 5,
						'blocking'    => true,
						'httpversion' => '1.0',
						'sslverify'   => false,
						'data_format' => 'body',
					];
					
					/*//generate post migration log
					$this->mpm_cust_log(date('Y-m-d H:i:s').'Admin Delete single post action: '.serialize($options));*/

					$api_response = wp_remote_post( $endpoint, $options );
					if ( is_wp_error( $api_response ) ) {
						$error_message = $api_response->get_error_message();
						echo esc_html("Something went wrong: $error_message");
					}
				}
			}
		}
		
	}

	/**
	 * Generate log for Post migration.
	 */
	/*function mpm_cust_log($log_msg)
	{
		$log_filename = plugin_dir_path( __FILE__ )."/postmigration-log";
		if (!file_exists($log_filename)) 
		{
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		$log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($log_file_data, $log_msg . "\n********\n", FILE_APPEND);
	}*/

}