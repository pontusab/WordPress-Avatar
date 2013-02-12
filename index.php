<?php
/*
Plugin Name: WordPress Avatar
Version: 0.1
Author: Pontus Abrahamsson @NetRelations
*/

class WP_avatar
{
	private $upload_path,
			$meta_key,
			$avatar_url,
			$mime_type;

	private static $input_field = 'wp_avatar';

	public function __construct()
	{
		// Plugin path
		define( 'WP_AVATAR_PATH', plugin_dir_url( __FILE__ ) );

		// Set Object vars
		$this->avatar_url  = WP_CONTENT_URL . '/uploads/avatars/';
		$this->upload_path = WP_CONTENT_DIR . '/uploads/avatars/';
		$this->meta_key    = 'avatar';

		// Activation hook
		register_activation_hook( __FILE__, array( &$this, 'activation' ) );

		// Actions 
		add_action( 'admin_init', array( &$this, 'get_upload' ) );
		add_action( 'admin_menu', array( &$this, 'profile_menu' ) );
		add_filter( 'get_avatar', array( &$this, 'get_avatar'), 10, 5 );
		add_action( 'admin_init', array( &$this, 'init') );
	}

	public function init() 
	{
		wp_register_style( 'style', WP_AVATAR_PATH . 'assets/style.css' );
		wp_register_script( 'script', WP_AVATAR_PATH . 'assets/script.js' );
		wp_enqueue_style( 'style' );
		wp_enqueue_script( 'script' );
	}

	public function activation()
	{
		if( ! file_exists( $this->upload_path ) ) 
		{
			mkdir( $this->upload_path, 0766, true );
		}
	}

	public function profile_menu() 
	{
		add_submenu_page( 
			'profile.php', 
			__('Avatar', 'wpa'), 
			__('Avatar', 'wpa'), 
			'manage_options', 
			'upload-avatar', 
			array( &$this, 'avatar_page' ) 
		); 
	}

	public function avatar_page()
	{
		$output = '<div class="wrap">';
			$output .= '<div id="icon-users" class="icon32"></div>';
			$output .= '<h2>'. __('Upload Avatar', 'wpa') .'</h2>';

			$output .= '<div class="avatar-wrap">';

				$output .= $this->display_avatar( 250 );

				$output .= '<form method="post" enctype="multipart/form-data">';;
					$output .= '<div class="file-upload button">';
						$output .= '<label for="avatar-upload">'. __('Change avatar', 'wpa') .'</label>';
						$output .= '<input id="avatar-upload" type="file" name="'. self::$input_field .'" />';
					$output .= '</div>';
					$output .= '<input type="submit" name="save_avatar" value="'. __('Save Avatar', 'wpa') .'" class="button button-primary">';
				$output .= '</form>';

			$output .= '</div>';
		$output .= '</div>';

		echo $output;
	}

	public function get_upload()
	{
		if ( count( $_FILES ) > 0 ) 
		{
			$this->handle_upload();
		}
	}

	public function handle_upload()
	{
		require_once( ABSPATH . '/wp-admin/includes/image.php' );


		// Set the mime_type
		$this->mime_type = $_FILES[self::$input_field]['name'];

		// Save and run the magic on avatars
		$this->save_avatar( $_FILES[self::$input_field]['tmp_name'], 250 );
	}

	private function save_avatar( $sourcefile, $size )
	{
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$type    = wp_check_filetype( $this->mime_type );

		// Flyttade in så att den gör ny instans av sås-filen varje gång! :) /hAmpzter
		$image = wp_get_image_editor( $sourcefile );
		if ( ! is_wp_error( $image ) ) 
		{
		    $image->resize( $size, $size, true );
		    $image->save( $this->upload_path . $user->user_login . '.' . $type['ext'] );

		    update_user_meta( $user_id, $this->meta_key, $user->user_login . '.' . $type['ext'] );
		}
}

	public function display_avatar( $size = 32 )
	{
		$user_id = get_current_user_id();
		
		return get_avatar( $user_id, $size );
	}

	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt )
	{	
		if( $id_or_email ) 
		{
			$avatars = get_user_meta( $id_or_email, $this->meta_key, true );

			if( ! empty( $avatars ) )
			{
				$avatar_path = $this->avatar_url . $avatars .'?s='. $size .'';
			}
			else 
			{
				$avatar_path = $default;
			}

			$avatar_html = "<img src='{$avatar_path}' alt='{$alt}' height='{$size}' width='{$size}' />";

			return $avatar_html;
		}
		
		return $avatar;
	}
}

$WP_Avatar = new WP_Avatar;
