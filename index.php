<?php
/*
Plugin Name: WordPress Avatar
Version: 0.1
Author: Pontus Abrahamsson @NetRelations
*/


/**
 * Self Initialize the object when plugins_loaded
 * @return object WP_avatar
 * @since 0.1
*/ 
add_action( 'plugins_loaded', array( 'WP_avatar', 'init' ) );


class WP_avatar
{
	/**
	 * Setup some vars in the object
	 * @since 0.1
	*/ 
	private 
		$upload_path,
		$meta_key,
		$avatar_url,
		$mime_type,
		$formats;


	/**
	 * Name of the form
	 * @since 0.1
	*/ 
	private static $input_field = 'wp_avatar';


	/**
	 * Self Initialize the object when plugins_loaded
	 * @since 0.1
	*/ 
	public static function init() 
	{
		$class = __CLASS__;
		new $class;
	}


	/**
	 * Run all the functions and filters on startup
	 * @since 0.1
	*/
	public function __construct()
	{
		// Plugin path
		define( 'WP_AVATAR_URL', plugin_dir_url( __FILE__ ) );
		define( 'WP_AVATAR_PATH', plugin_dir_path( __FILE__ ) );

		// Set Object vars
		$this->avatar_url  = WP_CONTENT_URL . '/uploads/avatars/';
		$this->upload_path = WP_CONTENT_DIR . '/uploads/avatars/';
		$this->meta_key    = 'avatar';
		$this->formats     = array( 'jpg', 'jpeg', 'png', 'gif' );

		// Activation hook
		register_activation_hook( __FILE__, array( &$this, 'activation' ) );

		// Get translations
		load_plugin_textdomain( 'wpa', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Actions 
		add_action( 'admin_init', array( &$this, 'handle_upload' ) );
		add_action( 'admin_menu', array( &$this, 'profile_menu' ) );
		add_filter( 'get_avatar', array( &$this, 'get_avatar'), 10, 5 );
		add_action( 'admin_init', array( &$this, 'scripts') );
	}


	/**
	 * Add Scripts to Profile-page 
	 * @since 0.1
	*/
	public function scripts() 
	{	
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		
		// Only add on Upload Avatar Page
		if( $page == 'upload-avatar' )
		{
			wp_register_style( 'style', WP_AVATAR_URL . 'assets/style.css' );
			wp_register_script( 'script', WP_AVATAR_URL . 'assets/script.js' );
			wp_enqueue_style( 'style' );
			wp_enqueue_script( 'script' );
		}
	}


	/**
	 * Add avatars folder on activation 
	 * @since 0.1
	*/
	public function activation()
	{
		if( ! file_exists( $this->upload_path ) ) 
		{
			mkdir( $this->upload_path, 0766, true );
		}
	}


	/**
	 * Add Pofile menu to Users
	 * @since 0.1
	*/
	public function profile_menu() 
	{
		add_submenu_page( 
			'profile.php', 
			__('Profile picture', 'wpa'),  
			__('Profile picture', 'wpa'), 
			'read', 
			'upload-avatar', 
			array( &$this, 'avatar_page' ) 
		); 
	}


	/**
	 * Submitform for avatar uplaod
	 * @return Html and form
	 * @since 0.1
	*/
	public function avatar_page()
	{
		$user_id = get_current_user_id();
		
		$output = '<div class="wrap">';
			$output .= '<div id="icon-users" class="icon32"></div>';
			$output .= '<h2>'. __('Change profile picture', 'wpa') .'</h2>';

			$output .= '<div class="avatar-wrap">';

				$output .= get_avatar( $user_id, 200 );

				$output .= '<form method="post" enctype="multipart/form-data">';;
					$output .= '<div class="file-upload button">';
						$output .= '<label for="avatar-upload">'. __('Change Profile picture', 'wpa') .'</label>';
						$output .= '<input id="avatar-upload" type="file" name="'. self::$input_field .'" />';
					$output .= '</div>';
					$output .= '<input type="submit" name="save_avatar" value="'. __('Change Profile picture', 'wpa') .'" class="button button-primary">';
				$output .= '</form>';

			$output .= '</div>';
		$output .= '</div>';

		echo $output;
	}


	/**
	 * Takes care of the upload 
	 * @return mime_type and upload file
	 * @since 0.1
	*/
	public function handle_upload()
	{
		if ( count( $_FILES ) > 0 ) 
		{
			require_once( ABSPATH . '/wp-admin/includes/image.php' );

			// Set the mime_type
			$this->mime_type = $_FILES[self::$input_field]['name'];

			// Save and run the magic on avatars
			$this->save_avatar( $_FILES[self::$input_field]['tmp_name'], 200 );
		}
	}


	/**
	 * Save the avatar to disk and update usermeta
	 * @since 0.1
	*/
	private function save_avatar( $sourcefile, $size )
	{
		$user_id        = get_current_user_id();
		$user           = get_userdata( $user_id );
		$type           = wp_check_filetype( $this->mime_type );
		$image          = wp_get_image_editor( $sourcefile );
		$path_and_name  = $this->upload_path . $user->user_login . '.';

		// User have avatar but not the same format
		foreach ( $this->formats as $format ) 
		{
			if( file_exists( $path_and_name . $format ) )
			{
				unlink( $path_and_name . $format );
			}
		}

		if ( ! is_wp_error( $image ) ) 
		{
		    $image->resize( $size, $size, true );
		    $image->save( $path_and_name . $type['ext'] );

		    // Save the name of the file to user_meta
		    update_user_meta( $user_id, 'avatar', $user->user_login . '.' . $type['ext'] );
		}
	}


	/**
	 * Override get_avatar with uploaded avatar else default
	 * @return uploaded avatar else default
	 * @since 0.1
	*/
	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt )
	{	
		if( $id_or_email ) 
		{
			$avatar = get_user_meta( $id_or_email, $this->meta_key, true );

			if( ! empty( $avatar ) )
			{
				$avatar_path = $this->avatar_url . $avatar .'?s='. $size .'';
			}
			else 
			{
				$avatar_path = $default;
			}

			$avatar = "<img alt='{$alt}' src='{$avatar_path}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";

			return $avatar;
		}
		
		return $avatar;
	}
}