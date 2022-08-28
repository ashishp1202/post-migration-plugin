<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://metallic.io
 * @since      1.0.0
 *
 * @package    Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/admin/partials
 */
$global_enable_post_migration = get_option('global_enable_post_migration');
$metallic_posttype_migration = get_option('metallic_posttype_migration');
$global_production_link = get_option('global_production_link');
$global_is_staging = get_option('global_is_staging');
?>

<div class="builder-wrap">
	<h1>Post Migration Settings</h1>

  <?php if(isset($_REQUEST['save_migration_settings']) ){
        if ( ! isset( $_POST['postmig_nonce'] ) 
        || ! wp_verify_nonce( sanitize_text_field($_POST['postmig_nonce']), 'postmig_nonce_action' ) 
    ){
      die( 'Failed security check' );
    }?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html( 'Settings saved successfully !', 'metallic-post-migration' );?></p>
    </div>
    <?php }?>

	<?php if(isset($_REQUEST['import_all_mig']) ){
        if ( ! isset( $_POST['postmig_nonce'] ) 
        || ! wp_verify_nonce( sanitize_text_field($_POST['postmig_nonce']), 'postmig_nonce_action' ) 
    ){
      die( 'Failed security check' );
    }?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html( 'Post imported successfully!', 'metallic-post-migration' );?></p>
    </div>
    <?php }?>

	<form method="post" action="">
	
		<div class="">
		<table style="width:100%" class="migration-settings">
			<tr>
				<th>Enable post migration</th>
				<td><input type="checkbox" name="global_enable_post_mig" id="global_enable_post_mig" <?php echo ($global_enable_post_migration == 1) ? 'checked' : '';?>></td>
			</tr>
			<tr>
				<th>Add Production Link</th>
				<td><input type="text" name="global_production_link" id="global_production_link" value="<?php echo isset($global_production_link) ? esc_attr($global_production_link) : '';?>"></td>
			</tr>
			<tr>
				<th>Is PreProd</th>
				<td><input type="checkbox" name="global_is_staging" id="global_is_staging" <?php echo ($global_is_staging == 1) ? 'checked' : '';?>></td>
			</tr>

			<tr>
				<th></th>
				<td><?php wp_nonce_field( 'postmig_nonce_action', 'postmig_nonce' );?>
      			<input type="submit" name="save_migration_settings" id="save_migration_settings" class="button button-primary" value="<?php echo esc_attr( 'Save Settings', 'metallic-post-migration' );?>"></td>
			</tr>
		</table>
      		
      
		<h1>Import to production</h1>
      
			<table style="width:100%" class="migration-settings">
				<tr>
					<th><code>This will import all post data to production site.</code></th>
					
				</tr>
				<tr>
					<td><input type="submit" name="import_all_mig" id="import_all_mig" class="button button-primary" value="<?php echo esc_attr( 'Import to Production', 'metallic-post-migration' );?>" <?php if($global_enable_post_migration != 1) echo 'disabled';?>></td>
				</tr>
			</table>
    </div>
	</form>
</div>

<?php /* <div class="row">
				<div class="mpm-left-inner-label">
					<h2>Select the post type to migration</h2>
				</div>
				<div class="mpm-right-inner-field">
					<input type="checkbox" name="particular_enable_post_mig[]" id="enable_post_mig" value="post" <?php echo (in_array('post', explode(',', $metallic_posttype_migration))) ? 'checked' : '';?>><label for="enable_post_mig">Posts</label>
					<input type="checkbox" name="particular_enable_post_mig[]" id="enable_page_mig" value="page" <?php echo (in_array('page', explode(',', $metallic_posttype_migration))) ? 'checked' : '';?>><label for="enable_page_mig">Pages</label>
				</div>
		  	</div> 

			<div class="row">
				<div class="mpm-left-inner-label">
					<h2>Enable post migration</h2>
				</div>
				<div class="mpm-right-inner-field">
          			<input type="checkbox" name="global_enable_post_mig" id="global_enable_post_mig" <?php echo ($global_enable_post_migration == 1) ? 'checked' : '';?>>
			  </div>
		  </div>*/ ?>