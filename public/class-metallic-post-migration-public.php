<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link	   https://metallic.io
 * @since	  1.0.0
 *
 * @package	Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package	Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/public
 * @author	 Metallic <>
 */
class Metallic_Post_Migration_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$plugin_name	The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$version	The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.0.0
	 * @param	  string	$plugin_name	   The name of the plugin.
	 * @param	  string	$version	The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->message = '';
		$this->code = '';
		$this->secretkey = 'metallic_import_section_key';

		add_action("rest_api_init", array( $this, 'rest_api_init_mpm_migrate_postdata'));
		add_action("rest_api_init", array( $this, 'rest_api_init_mpm_trash_postdata'));
		add_action("rest_api_init", array( $this, 'rest_api_init_mpm_untrash_postdata'));
		add_action("rest_api_init", array( $this, 'rest_api_init_mpm_delete_postdata'));
		add_filter( 'http_request_timeout', array( $this, 'mpm_rest_api_timeout_extend') );
	}

	
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since	1.0.0
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/metallic-post-migration-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since	1.0.0
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/metallic-post-migration-public.js', array( 'jquery' ), $this->version, false );

	}


	public function rest_api_init_mpm_migrate_postdata(){
		
		register_rest_route( 'metallic-migration/v1', '/post', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mpm_migrate_postdata_callback'),
			'permission_callback' => function () {
				return true;
			}
		  ) );
	}

	public function rest_api_init_mpm_trash_postdata(){
		
		register_rest_route( 'metallic-migration/v1', '/posttrash', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mpm_trash_postdata_callback'),
			'permission_callback' => function () {
				return true;
			}
		  ) );
	}

	public function rest_api_init_mpm_untrash_postdata(){
		
		register_rest_route( 'metallic-migration/v1', '/postuntrash', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mpm_untrash_postdata_callback'),
			'permission_callback' => function () {
				return true;
			}
		  ) );
	}

	public function rest_api_init_mpm_delete_postdata(){
		
		register_rest_route( 'metallic-migration/v1', '/postdelete', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mpm_delete_postdata_callback'),
			'permission_callback' => function () {
				return true;
			}
		  ) );
	}

	/**
	 * Save post action
	 *
	 * @since    1.0.0
	 */
	public function mpm_migrate_postdata_callback(WP_REST_Request $request) {

		$key  = $request->get_header('Authorization');
		$decode_key = base64_decode( $key );
		
		$arr_request = $request;
		
		/*$this->mpm_cust_log(date('Y-m-d H:i:s').'Save Post data: '.(array)$arr_request);*/

		if($this->secretkey === $decode_key){
			$cat_arr = array();
			$args = array(
				'name'        => $request['post_name'],
				'post_type'   => $request['post_type'],
				'post_status' => $request['post_status'],
				'numberposts' => 1
			);
			$post_data = get_posts($args);
			
			$categories = $request['category'];
			$metadata = $request['metadata'];
			$home_url = $request['home_url'];
			
			
			
			if(!empty($categories)){
				
				foreach($categories as $category){

					$term = term_exists( $category['name'], $category['taxonomy'] );
					/*$term = wpcom_vip_term_exists( $category['name'], $category['taxonomy'] );*/
					
					if ( $term == 0 || $term == null ) {
						$insert_term = wp_insert_term(
							$category['name'],
							$category['taxonomy'],
							array(
								'description' => $category['description'],
								'slug'        => $category['slug'],
								'parent'      => $category['parent'],
							)
						);

						if(is_wp_error($insert_term)){
							$error_message = $insert_term->get_error_message();
							echo esc_html("Something went wrong: $error_message");
						}else{
							array_push($cat_arr, $insert_term['term_id']);
						}
					}else{
						array_push($cat_arr, $term['term_id']);
					}

				}
				
			}
			

			$return_content = " ";
			if(!empty($post_data)){
				$return_content = $this->fetch_images($post_data[0]->ID, $request['post_content'], $home_url);
				
				$data_args = array(
					'ID' => $post_data[0]->ID,
					'post_title'    => $request['post_title'],
					'post_content'  => $return_content,
					'post_status'   => $request['post_status'],
					'post_author'   => $request['post_author'],
					'post_type' => $request['post_type'],
					'post_name' => $request['post_name'],
					'post_excerpt' => $request['post_excerpt'],
					'post_date' => $request['post_date'],
					'post_author' => $request['post_author'],
					'post_date_gmt' => $request['post_date_gmt'],
					'comment_status' => $request['comment_status'],
					'ping_status' => $request['ping_status'],
					'post_password' => $request['post_password'],
					'to_ping' => $request['to_ping'],
					'pinged' => $request['pinged'],
					'post_modified' => $request['post_modified'],
					'post_modified_gmt' => $request['post_modified_gmt'],
					'post_content_filtered' => $request['post_content_filtered'],
					'menu_order' => $request['menu_order'],
					'post_mime_type' => $request['post_mime_type'],
					'comment_count' => $request['comment_count'],
					'filter' => $request['filter'],
				);
				if(!empty($cat_arr)){
					$data_args['post_category'] = $cat_arr;
				}
				kses_remove_filters();
				$post_id = wp_update_post( $data_args );
				kses_init_filters();
				if (is_wp_error($post_id)) {
					
					$errors = $post_id->get_error_messages();
					foreach ($errors as $error) {
						echo esc_attr($error);
					}
					$this->message = array(
						"code" 		=> 201,
						"message" 	=> __("Update Error", "metallic-post-migration"),
					);
					$this->code = 200;
				}else{
					
					if(isset($request['post_thumb']) && !empty($request['post_thumb'])){
						$this->metallic_generate_featured_image($request['post_thumb'], $post_id);
					}

					if(!empty($metadata)){
						foreach($metadata as $key => $single_metadata){
							if($key != 'is_migration_post' && $key != '_thumbnail_id')
							update_post_meta($post_id, $key, maybe_serialize($single_metadata));
						}
					}
					
					$this->message = array(
						"code" 		=> 201,
						"message" 	=> __("Update Success", "metallic-post-migration"),
					);
					$this->code = 200;
				}
			}else{
				$my_post = array(
					'post_title'    => $request['post_title'],
					'post_content'  => $return_content,
					'post_status'   => $request['post_status'],
					'post_author'   => $request['post_author'],
					'post_type' => $request['post_type'],
					'post_name' => $request['post_name'],
					'post_excerpt' => $request['post_excerpt'],
					'post_date' => $request['post_date'],
					'post_author' => $request['post_author'],
					'post_date_gmt' => $request['post_date_gmt'],
					'comment_status' => $request['comment_status'],
					'ping_status' => $request['ping_status'],
					'post_password' => $request['post_password'],
					'to_ping' => $request['to_ping'],
					'pinged' => $request['pinged'],
					'post_modified' => $request['post_modified'],
					'post_modified_gmt' => $request['post_modified_gmt'],
					'post_content_filtered' => $request['post_content_filtered'],
					'menu_order' => $request['menu_order'],
					'post_mime_type' => $request['post_mime_type'],
					'comment_count' => $request['comment_count'],
					'filter' => $request['filter'],
				);
				if(!empty($cat_arr)){
					$my_post['post_category'] = $cat_arr;
				}
				
				kses_remove_filters();
				$post_id = wp_insert_post( $my_post );
				kses_init_filters();

				if (is_wp_error($post_id)) {
					$errors = $post_id->get_error_messages();
					foreach ($errors as $error) {
						echo esc_attr($error);
					}
					$this->message = array(
						"code" 		=> 201,
						"message" 	=> __("Insert Error", "metallic-post-migration"),
						'post_id'	=> $post_id
					);
					$this->code = 200;
				}else{
					if(isset($request['post_thumb']) && !empty($request['post_thumb'])){
						$this->metallic_generate_featured_image($request['post_thumb'], $post_id);
					}

					$return_content = $this->fetch_images($post_id, $request['post_content'], $home_url);
					$data_args = array(
						'ID' => $post_id,
						'post_content'  => $return_content,
						
					);
					
					kses_remove_filters();
					$post_id = wp_update_post( $data_args );
					kses_init_filters();
					if(!empty($metadata)){
						foreach($metadata as $key => $single_metadata){
							if($key != 'is_migration_post' && $key != '_thumbnail_id')
							update_post_meta($post_id, $key, maybe_serialize($single_metadata));
						}
					}
					
					$this->message = array(
						"code" 		=> 201,
						"message" 	=> __("Insert Success", "metallic-post-migration"),
						'post_id'	=> $post_id
					);
					$this->code = 200;
				}
			}
		}else{
			$this->message = array(
				"code" 		=> 201,
				"message" 	=> __("Invalid Authorization", "metallic-post-migration"),
				'post_id'	=> $post_id
			);
			$this->code = 200;
		}
		return new WP_REST_Response( $this->message, $this->code);
	}

	/**
	 * Trash post action
	 *
	 * @since    1.0.0
	 */
	public function mpm_trash_postdata_callback(WP_REST_Request $request) {
		
		$key  = $request->get_header('Authorization');
		$decode_key = base64_decode( $key );

		/*//generate post migration log
		$arr_request = $request;
		$this->mpm_cust_log(date('Y-m-d H:i:s').'Trash post action: '.(array)$arr_request);*/

		if($this->secretkey === $decode_key){
			$cat_arr = array();
			$args = array(
				'name'        => $request['post_name'],
				'post_type'   => $request['post_type'],
				'post_status' => $request['post_status'],
				'numberposts' => 1
			);
			$post_data = get_posts($args);
			
			wp_trash_post( $post_id = $post_data[0]->ID );
		}else{
			$this->message = array(
				"code" 		=> 201,
				"message" 	=> __("Invalid Authorization", "metallic-post-migration"),
				'post_id'	=> $post_id
			);
			$this->code = 200;
		}
		return new WP_REST_Response( "Trashed completed", 200);
	}

	/**
	 * Untrash post action
	 *
	 * @since    1.0.0
	 */
	public function mpm_untrash_postdata_callback(WP_REST_Request $request) {
		$key  = $request->get_header('Authorization');
		$decode_key = base64_decode( $key );

		/*//generate post migration log
		$arr_request = $request;
		$this->mpm_cust_log(date('Y-m-d H:i:s').'Untrash post action: '.(array)$arr_request);*/

		if($this->secretkey === $decode_key){
			$cat_arr = array();
			$args = array(
				'name'        => $request['post_name'],
				'post_type'   => $request['post_type'],
				'post_status' => $request['post_status'],
				'numberposts' => 1
			);
			$post_data = get_posts($args);
			
			wp_untrash_post( $post_id = $post_data[0]->ID );
		}else{
			$this->message = array(
				"code" 		=> 201,
				"message" 	=> __("Invalid Authorization", "metallic-post-migration"),
				'post_id'	=> $post_id
			);
			$this->code = 200;
		}
		return new WP_REST_Response( "Untrashed completed", 200);
	}

	
	/**
	 * Delete post action
	 *
	 * @since    1.0.0
	 */
	public function mpm_delete_postdata_callback(WP_REST_Request $request) {
		$key  = $request->get_header('Authorization');
		$decode_key = base64_decode( $key );

		/*//generate post migration log
		$arr_request = $request;
		$this->mpm_cust_log(date('Y-m-d H:i:s').'Delete post action: '.(array)$request);*/

		if($this->secretkey === $decode_key){
			$cat_arr = array();
			$args = array(
				'name'        => $request['post_name'],
				'post_type'   => $request['post_type'],
				'post_status' => $request['post_status'],
				'numberposts' => 1
			);
			$post_data = get_posts($args);
			
			wp_delete_post( $post_data[0]->ID, true );
		}else{
			$this->message = array(
				"code" 		=> 201,
				"message" 	=> __("Invalid Authorization", "metallic-post-migration"),
				'post_id'	=> $post_id
			);
			$this->code = 200;
		}
		return new WP_REST_Response( "Delete completed", 200);
	}

	public function mpm_rest_api_timeout_extend( $time )
	{
		return 50;
	}

	public function metallic_generate_featured_image( $image_url, $post_id  ){
		
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents($image_url);
		
		$filename = basename($image_url);
		if(wp_mkdir_p($upload_dir['path']))
		  $file = $upload_dir['path'] . '/' . $filename;
		else
		  $file = $upload_dir['basedir'] . '/' . $filename;
		file_put_contents($file, $image_data);
	
		$wp_filetype = wp_check_filetype($filename, null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		$res1= wp_update_attachment_metadata( $attach_id, $attach_data );
		$res2= set_post_thumbnail( $post_id, $attach_id );
	}

	function fetch_images($post_id, $post_content, $home_url){
		
		$doc = new DOMDocument();
		$post_content = $post_content;
		
		$data = mb_convert_encoding($post_content, 'HTML-ENTITIES', 'UTF-8');
		
		$doc->loadHTML(mb_convert_encoding($post_content, 'HTML-ENTITIES', 'UTF-8'));
		
		$tags = $doc->getElementsByTagName('img');
		
		foreach ($tags as $tag) {

			$oldSrc = $tag->getAttribute('src');

			

			if (strpos($oldSrc,$home_url) !== false) {

				$upload_dir = wp_upload_dir();
				$image_data = file_get_contents($oldSrc);
				$filename = basename($oldSrc);
				if(wp_mkdir_p($upload_dir['path']))
				$file = $upload_dir['path'] . '/' . $filename;
				else
				$file = $upload_dir['basedir'] . '/' . $filename;
				file_put_contents($file, $image_data);
			
				$wp_filetype = wp_check_filetype($filename, null );
				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title' => sanitize_file_name($filename),
					'post_content' => '',
					'post_status' => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				$res1= wp_update_attachment_metadata( $attach_id, $attach_data );
				$newlink = wp_get_attachment_url($attach_id);
				
				
				$tag->setAttribute('src', $newlink);
				/*$tag->setAttribute('data-id', $attach_id);
				$tag->setAttribute('data-full-url', $newlink);
				$tag->setAttribute('data-link', get_the_permalink($attach_id));
				$tag->setAttribute('wp-image-270', 'wp-image-'.$attach_id);*/
			}
			
			
			
		}

		$htmlString = utf8_encode($doc->saveHTML());
		return $htmlString;
	
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