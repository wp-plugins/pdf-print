<?php
/*
Plugin Name: PDF & Print by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: Plugin adds PDF creation and Print button on your site.
Author: BestWebSoft
Version: 1.8.4
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add our own menu */
if ( ! function_exists( 'pdfprnt_add_pages' ) ) {
	function pdfprnt_add_pages() {
		bws_add_general_menu( plugin_basename( __FILE__ ) );
		add_submenu_page( 'bws_plugins', __( 'PDF & Print Settings', 'pdf-print' ), 'PDF & Print', 'manage_options', 'pdf-print.php', 'pdfprnt_settings_page' );
	}
}

/* Init plugin */
if ( ! function_exists ( 'pdfprnt_init' ) ) {
	function pdfprnt_init() {
		global $pdfprnt_plugin_info;
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'pdf-print', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_functions.php' );

		if ( empty( $pdfprnt_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$pdfprnt_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check WordPress version */
		bws_wp_version_check( plugin_basename( __FILE__ ), $pdfprnt_plugin_info, '3.3' );

		/* Get/Register and check settings for plugin */
		if ( ! is_admin() || ( isset( $_GET['page'] ) && 'pdf-print.php' == $_GET['page'] ) )
			pdfprnt_settings();
	}
}

if ( ! function_exists( 'pdfprnt_admin_init' ) ) {
	function pdfprnt_admin_init() {
		global $bws_plugin_info, $pdfprnt_plugin_info;

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '101', 'version' => $pdfprnt_plugin_info["Version"] );
	}
}

/* Register settings for plugin */
if ( ! function_exists( 'pdfprnt_settings' ) ) {
	function pdfprnt_settings() {
		global $pdfprnt_options_array, $pdfprnt_plugin_info, $pdfprnt_options_defaults;

		if ( ! $pdfprnt_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$pdfprnt_plugin_info = get_plugin_data( __FILE__ );	
		}
	
		/* Variable to verify performance number of once function. */
		$pdfprnt_default_post_types		=	array();
		/* Default post types of WordPress. */
		foreach ( get_post_types( array( 'public' => 1, 'show_ui' => 1,	'_builtin' => true ), 'names' ) as $value )
			$pdfprnt_default_post_types[]	=	$value;

		$pdfprnt_options_defaults		=	array(
			'plugin_option_version' 		=> $pdfprnt_plugin_info["Version"],
			'position'						=>	'top-right',
			'position_search_archive'		=>	'top-right',
			'position_custom'				=>	'right',
			'show_btn_print'				=>	1,
			'show_btn_pdf'					=>	1,
			'show_btn_print_search_archive'	=>	1,
			'show_btn_pdf_search_archive'	=>	1,
			'use_theme_stylesheet'			=>	0,
			'tmpl_shorcode'					=>	1,
			'use_types_posts'				=>	$pdfprnt_default_post_types,
			'show_print_window'				=>	0,
			'additional_fonts'				=>	0
		);
		
		if ( ! get_option( 'pdfprnt_options_array' ) )
			add_option( 'pdfprnt_options_array', $pdfprnt_options_defaults );

		$pdfprnt_options_array	= get_option( 'pdfprnt_options_array' );

		if ( ! isset( $pdfprnt_options_array['plugin_option_version'] ) || $pdfprnt_options_array['plugin_option_version'] != $pdfprnt_plugin_info["Version"] ) {
			if ( in_array( $pdfprnt_options_array['position_search_archive'], array( 'pdfprnt-right', 'pdfprnt-left' ) ) )
				$pdfprnt_options_array['position_search_archive'] = 'pdfprnt-left' == $pdfprnt_options_array['position_search_archive'] ? 'top-left' : 'top-right';
			if ( in_array( $pdfprnt_options_array['position_custom'], array( 'pdfprnt-right', 'pdfprnt-left' ) ) )
				$pdfprnt_options_array['position_custom'] = 'pdfprnt-left' == $pdfprnt_options_array['position_custom'] ? 'left' : 'right';
			$pdfprnt_options_array	= array_merge( $pdfprnt_options_defaults, $pdfprnt_options_array );
			$pdfprnt_options_array['plugin_option_version'] = $pdfprnt_plugin_info["Version"];
			update_option( 'pdfprnt_options_array', $pdfprnt_options_array );
		}
	}
}

/**
 * Display <input type="radio">  on settings page
 **/
if ( ! function_exists( 'pdfprnt_display_radio' ) ) {
	function pdfprnt_display_radio( $name, $selected ) {
		$positions_values	=	array(
			'top-left'		=>	__( 'Top Left', 'pdf-print' ),
			'top-right'		=>	__( 'Top Right', 'pdf-print' ),
			'bottom-left'	=>	__( 'Bottom Left', 'pdf-print' ),
			'bottom-right'	=>	__( 'Bottom Right', 'pdf-print' ),
			'top-bottom-left'	=>	__( 'Top & Bottom Left', 'pdf-print' ),
			'top-bottom-right'	=>	__( 'Top & Bottom Right', 'pdf-print' )
		);
		foreach ( $positions_values as $key => $value ) { ?>
			<label><input type="radio" name="<?php echo $name; ?>" value="<?php echo $key ?>"<?php echo $key == $selected ? ' checked="checked"' : ''; ?> />&nbsp;<?php echo $value; ?></label><br/>
		<?php }
	}
}
/* Add admin page */
if ( ! function_exists ( 'pdfprnt_settings_page' ) ) {
	function pdfprnt_settings_page () {
		global $pdfprnt_options_array, $wp_version, $pdfprnt_plugin_info, $pdfprnt_options_defaults;
		$message = $error  = "";
		$plugin_basename   = plugin_basename( __FILE__ );
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			$upload_dir = wp_upload_dir(); 
			restore_current_blog();
		} else {
			$upload_dir = wp_upload_dir();
		}
		$need_fonts_reload = false;
		$fonts_path        = $upload_dir['basedir'] .'/pdf-print-fonts';
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();
		if ( isset( $_REQUEST['pdfprnt_form_submit'] ) && check_admin_referer( $plugin_basename, 'pdfprnt_nonce_name' ) ) {
			$pdfprnt_options_array['position']						=	isset( $_REQUEST['pdfprnt_position'] ) ? $_REQUEST['pdfprnt_position'] : 'top-right';
			$pdfprnt_options_array['position_search_archive']		=	isset( $_REQUEST['pdfprnt_position_search_archive'] ) ? $_REQUEST['pdfprnt_position_search_archive'] : 'top-right';
			$pdfprnt_options_array['position_custom']				=	isset( $_REQUEST['pdfprnt_position_custom'] ) ? $_REQUEST['pdfprnt_position_custom'] : 'right';
			$pdfprnt_options_array['use_theme_stylesheet']			=	isset( $_REQUEST['pdfprnt_use_theme_stylesheet'] ) ? $_REQUEST['pdfprnt_use_theme_stylesheet'] : 0;
			$pdfprnt_options_array['show_btn_pdf']					=	isset( $_REQUEST['pdfprnt_show_btn_pdf'] ) ? 1 : 0;
			$pdfprnt_options_array['show_btn_print']				=	isset( $_REQUEST['pdfprnt_show_btn_print'] ) ? 1 : 0;
			$pdfprnt_options_array['show_btn_pdf_search_archive']	=	isset( $_REQUEST['pdfprnt_show_btn_pdf_search_archive'] ) ? 1 : 0;
			$pdfprnt_options_array['show_btn_print_search_archive']	=	isset( $_REQUEST['pdfprnt_show_btn_print_search_archive'] ) ? 1 : 0;
			$pdfprnt_options_array['tmpl_shorcode']					=	isset( $_REQUEST['pdfprnt_tmpl_shorcode'] ) ? 1 : 0;
			$pdfprnt_options_array['show_print_window']				=	isset( $_REQUEST['pdfprnt_show_print_window'] ) ? 1 : 0;
			$pdfprnt_options_array['use_types_posts']				=	isset( $_REQUEST['pdfprnt_use_types_posts'] ) ? $_REQUEST['pdfprnt_use_types_posts'] : array();
			update_option( 'pdfprnt_options_array', $pdfprnt_options_array );
			$message	=	__( 'Settings saved.', 'pdf-print' );
		}
		if ( ( ! is_dir( $fonts_path ) ) && 0 != $pdfprnt_options_array['additional_fonts'] ) { /* if "pdf-print-fonts" folder was removed somehow */
			$error = __( 'The folder "uploads/pdf-print-fonts" was removed.', 'pdf-print' );
			$need_fonts_reload = true;
		} elseif ( 
			is_dir( $fonts_path ) && 
			$pdfprnt_options_array['additional_fonts'] != count( scandir( $fonts_path ) ) && 
			0 < $pdfprnt_options_array['additional_fonts'] 
		) { /* if some fonts was removed somehow from "pdf-print-fonts" folder */
			$error = __( 'Some fonts were removed from the folder "uploads/pdf-print-fonts".', 'pdf-print' );
			$need_fonts_reload = true;
		}
		if ( $need_fonts_reload ) {
			$error .= __( ' You may need to reload fonts.', 'pdf-print' );
			$pdfprnt_options_array['additional_fonts'] = 0;
		}
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			/* GO PRO */
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
		} 
		if ( isset( $_REQUEST['pdfprnt_load_fonts'] ) ) { 
			/* load additional fonts if javascript is disabled */ 
			$result = pdfprnt_load_fonts();
			if ( isset( $result['error'] ) )
				$error .= '&nbsp;' . $result['error'];
			if ( isset( $result['done'] ) )
				$message .= '&nbsp;' . $result['done'];
		} 
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$pdfprnt_options_array = $pdfprnt_options_defaults;
			update_option( 'pdfprnt_options_array', $pdfprnt_options_array );
			$message = __( 'All plugin settings were restored.', 'pdf-print' );
		} 
		?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2>PDF & Print<?php if ( isset( $_GET['action'] ) && 'templates' == $_GET['action'] ) { ?> <a href="admin.php?page=pdf-print.php&amp;action=templates&amp;pdfprnt_tab_action=new" class="<?php if ( 3.8 > $wp_version ) echo 'button '; ?>add-new-h2"><?php _e( 'Add New Template', 'pdf-print' ); ?></a><?php } ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( !isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=pdf-print.php"><?php _e( 'Settings', 'pdf-print' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'extra' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=pdf-print.php&amp;action=extra"><?php _e( 'Extra settings', 'pdf-print' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'templates' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=pdf-print.php&amp;action=templates"><?php _e( 'Templates', 'pdf-print' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/pdf-print/faq/" target="_blank"><?php _e( 'FAQ', 'pdf-print' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=pdf-print.php&amp;action=go_pro"><?php _e( 'Go PRO', 'pdf-print' ); ?></a>
			</h2>
			<div class="updated fade" <?php if ( empty( $message ) || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div id="pdfprnt_settings_notice" class="updated fade" style="display:none"><p><strong><?php _e( "Notice:", 'pdf-print' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'pdf-print' ); ?></p></div>
			<div class="error" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! isset( $_GET['action'] ) ) { 
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( $plugin_basename );
				} else { ?>
					<form method="post" action="admin.php?page=pdf-print.php" id="pdfprnt_settings_form">
						<table class="form-table pdfprnt_settings_table">
							<tr id="pdfprnt_load_fonts_button">
								<th scope="row"><?php _e( 'Load additional fonts', 'pdf-print' ); ?></th>
								<td style="position: relative;">
									<?php if ( 0 == $pdfprnt_options_array['additional_fonts'] && class_exists( 'ZipArchive' ) ) { 
										$ajax_nonce = wp_create_nonce( 'pdfprnt_ajax_nonce' ); ?>
										<input type="submit" class="button" value="<?php _e( 'Load Fonts', 'pdf-print' ); ?>" name="pdfprnt_load_fonts" />&nbsp;<span id="pdfprnt_font_loader" class="pdfprnt_loader"><img src="<?php echo plugins_url( 'images/ajax-loader.gif', __FILE__ ); ?>" alt="loader" /></span><br />
										<span class="pdfprnt_info"><?php _e( 'You can load additional fonts, needed for the PDF creation. When creating the PDF-doc, this will allow automatic selection of fonts necessary for text, according to languages used in the content.', 'pdf-print' ); ?></span>
										<input type="hidden" name="pdfprnt_action" value="pdfprnt_load_fonts" />
										<input type="hidden" name="pdfprnt_ajax_nonce" value="<?php echo $ajax_nonce; ?>" />
									<?php } elseif ( 0 == $pdfprnt_options_array['additional_fonts'] && ! class_exists( 'ZipArchive' ) ) { ?>
										<span style="color: red"><strong><?php _e( 'WARNING', 'pdf-print' ); ?>:&nbsp;</strong><?php _e( 'Class ZipArchive is not installed on your server. It is impossible to load additional fonts.', 'pdf-print' ); ?></span>
									<?php } elseif ( 0 < $pdfprnt_options_array['additional_fonts'] ) { ?>
										<span><?php _e( 'Additional fonts were loaded successfully', 'pdf-print' ); ?>.</span>
									<?php } ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Use the theme stylesheet or plugin default style', 'pdf-print' ); ?></th>
								<td>
									<select name="pdfprnt_use_theme_stylesheet">
										<option value="0" <?php if ( 0 == $pdfprnt_options_array['use_theme_stylesheet'] ) echo 'selected="selected"'; ?>><?php echo __( 'Default stylesheet', 'pdf-print' ); ?></option>
										<option value="1" <?php if ( 1 == $pdfprnt_options_array['use_theme_stylesheet'] ) echo 'selected="selected"'; ?>><?php echo __( 'Current theme stylesheet', 'pdf-print' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Types of posts that will be used in the plugin', 'pdf-print' ); ?></th>
								<td>
									<select name="pdfprnt_use_types_posts[]" multiple="multiple">
										<?php foreach ( get_post_types( array( 'public' => 1, 'show_ui' => 1 ), 'objects' ) as $key => $value  ) {
											if ( 'attachment' != $key ) { ?>
												<option value="<?php echo $key; ?>" <?php if ( in_array( $key, $pdfprnt_options_array['use_types_posts'] ) ) echo 'selected="selected"'; ?>><?php echo $value->label; ?></option>
											<?php }
										} ?>
									</select>
								</td>
							</tr>
						</table>
						<table class="form-table pdfprnt_settings_table pdfprnt_buttons" style="width: auto !important;">
							<tr class="pdfprnt_table_head">
								<th scope="row"></th>
								<th><?php _e( 'Posts and pages', 'pdf-print' ); ?></th>
								<th><?php _e( 'Search and archive pages', 'pdf-print' ); ?></th>
							</tr>
							<tr class="pdfprnt_pdf_buttton">
								<th scope="row"><?php _e( 'Show PDF button', 'pdf-print' ); ?></th>
								<td>
									<input type="checkbox" name="pdfprnt_show_btn_pdf" <?php if ( 1 == $pdfprnt_options_array['show_btn_pdf'] ) echo 'checked="checked"'; ?> />
								</td>
								<td>
									<input type="checkbox" name="pdfprnt_show_btn_pdf_search_archive" <?php if ( 1 == $pdfprnt_options_array['show_btn_pdf_search_archive'] ) echo 'checked="checked"'; ?> />
								</td>
							</tr>
							<tr class="pdfprnt_print_buttton">
								<th scope="row"><?php _e( 'Show Print button', 'pdf-print' ); ?></th>
								<td>
									<input type="checkbox" name="pdfprnt_show_btn_print" <?php if ( 1 == $pdfprnt_options_array['show_btn_print'] ) echo 'checked="checked"'; ?> />
								</td>
								<td>
									<input type="checkbox" name="pdfprnt_show_btn_print_search_archive" <?php if ( 1 == $pdfprnt_options_array['show_btn_print_search_archive'] ) echo 'checked="checked"'; ?> />
								</td>
							</tr>
							<tr class="pdfprnt_position_buttton">
								<th scope="row"><?php _e( 'Position of buttons in the content', 'pdf-print' ); ?></th>
								<td>
									<fieldset><?php pdfprnt_display_radio( 'pdfprnt_position', $pdfprnt_options_array['position'] ); ?></fieldset>
								</td>
								<td>
									<fieldset><?php pdfprnt_display_radio( 'pdfprnt_position_search_archive', $pdfprnt_options_array['position_search_archive'] ); ?></fieldset>
								</td>
							</tr>
						</table>
						<div>
							<p>
								<?php _e( 'In order to use PDF and Print buttons in the custom post or page template, see', 'pdf-print' ); ?>&nbsp;<a href="http://bestwebsoft.com/products/pdf-print/faq/" target="_blank"><?php _e( 'FAQ', 'pdf-print' ); ?></a>
							</p>
						</div>
						<table class="form-table pdfprnt_settings_table">
							<tr>
								<th scope="row">
									<?php _e( 'Settings for shortcodes', 'pdf-print' ); ?>
								</th>
								<td>
									<label><input type="checkbox" name="pdfprnt_tmpl_shorcode"  <?php if ( 1 == $pdfprnt_options_array['tmpl_shorcode'] ) echo 'checked="checked"'; ?> /> <span><?php _e( 'Do!', 'pdf-print' ); ?></span></label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Show the print window', 'pdf-print' ); ?></th>
								<td>
									<input type="checkbox" name="pdfprnt_show_print_window" <?php if ( 1 == $pdfprnt_options_array['show_print_window'] ) echo 'checked="checked"'; ?> />
								</td>
							</tr>
						</table>
						<div class="bws_pro_version_bloc">
							<div class="bws_pro_version_table_bloc">
								<div class="bws_table_bg"></div>
								<table class="form-table bws_pro_version">
									<tr>
										<th scope="row"><?php _e( 'PDF files name', 'pdf-print' ); ?></th>
										<td>
											<fieldset>
												<label><input disabled="disabled" type="radio" name="pdfprntpr_select_file_name" value="1" /> <?php _e( 'use post or page slug', 'pdf-print' ); ?></label><br />
												<input type="radio" disabled="disabled" name="pdfprntpr_select_file_name" value="0" /><input disabled="disabled" type="text" name='pdfprntpr_file_name' value="mpdf" /><br />
												<span class="pdfprnt_info">
													<?php _e( 'File name cannot contain more than 195 symbols. The file name can include Latin letters, numbers and symbols "-" , "_" only.', 'pdf-print' )  ?>	
												</span>
											</fieldset>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Do not index pdf and print pages', 'pdf-print' ); ?></th>
										<td>
											<input type="checkbox" name="pdfprntpr_noindex_page" disabled="disabled" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" colspan="2">
											* <?php _e( 'If you upgrade to Pro version, all your settings will be saved.', 'pdf-print' ); ?>
										</th>
									</tr>		
								</table>	
							</div>
							<div class="bws_pro_version_tooltip">
								<div class="bws_info">
									<?php _e( 'Unlock premium options by upgrading to Pro version', 'pdf-print' ); ?> 
								</div>
								<a class="bws_button" href="http://bestwebsoft.com/products/pdf-print/?k=d9da7c9c2046bed8dfa38d005d4bffdb&pn=101&v=<?php echo $pdfprnt_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="PDF & Print Pro"><?php _e( 'Learn More', 'pdf-print' ); ?></a>
								<div class="clear"></div>					
							</div>
						</div>
						<input type="hidden" name="pdfprnt_form_submit" value="1" />
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'pdf-print' ); ?>" />
						</p>
						<?php wp_nonce_field( $plugin_basename, 'pdfprnt_nonce_name' ); ?>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				} ?>
			<?php } elseif ( 'extra' == $_GET['action'] ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">	
						<div class="bws_table_bg"></div>
						<div class="bws_pro_version">
							<p><?php _e( 'Please choose the necessary post types (or single pages), where PDF & Print buttons should be displayed:', 'pdf-print' ); ?></p>											
							<p><label>
								<input disabled="disabled" checked="checked" id="twttrpr_jstree_url" type="checkbox" name="twttrpr_jstree_url" value="1" />
								<?php _e( "Show URL for pages", 'pdf-print' );?>
							</label></p>
							<img src="<?php echo plugins_url( 'images/pro_screen_1.png', __FILE__ ); ?>" alt="<?php _e( "Example of site pages' tree", 'pdf-print' ); ?>" title="<?php _e( "Example of site pages' tree", 'pdf-print' ); ?>" />
							<p class="submit">
								<input disabled="disabled" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'pdf-print' ); ?>" />
							</p>
							<p><strong>* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'pdf-print' ); ?></strong></p>
						</div>
					</div>
					<div class="bws_pro_version_tooltip">
						<div class="bws_info">
							<?php _e( 'Unlock premium options by upgrading to Pro version', 'pdf-print' ); ?> 
						</div>
						<a class="bws_button" href="http://bestwebsoft.com/products/pdf-print/?k=d9da7c9c2046bed8dfa38d005d4bffdb&pn=101&v=<?php echo $pdfprnt_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="PDF & Print Pro"><?php _e( 'Learn More', 'pdf-print' ); ?></a>
						<div class="clear"></div>					
					</div>
				</div>
			<?php } elseif ( 'templates' == $_GET['action'] ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">	
						<div class="bws_table_bg"></div>	
				<?php if ( isset( $_GET['pdfprnt_tab_action'] ) && 'new' == $_GET['pdfprnt_tab_action'] ) { ?>
					<h2><?php _e( 'Add New template', 'pdf-print' ); ?></h2>
					<p class="hide-if-no-js desription"><i><?php _e( 'For more info click "Help" tab at the top of the page.', 'pdf-print' ); ?></i></p>
					<div id="pdfprntpr_template_page">
						<div id="titlediv"><div id="titlewrap"><input disabled type="text" name="pdfprntpr_template_title" size="30" value="" id="title" placeholder="<?php _e( 'Enter Template Title', 'pdf-print' ); ?>" /></div></div>
						<h3><span><?php _e( 'Top running title', 'pdf-print' ); ?></span></h3>
						<div class="postarea wp-editor-expand">
							<div id="wp-pdfprntpr_top-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
							<div id="wp-pdfprntpr_top-editor-tools" class="wp-editor-tools hide-if-no-js">
								<div id="wp-pdfprntpr_top-media-buttons" class="wp-media-buttons">
									<link rel='stylesheet' id='editor-buttons-css'  href='#' type='text/css' media='all' />
									<a disabled href="#" id="insert-media-button" class="button insert-media add_media" data-editor="pdfprntpr_top" title="<?php _e( 'Add Media', 'pdf-print' ); ?>"><span class="wp-media-buttons-icon"></span> <?php _e( 'Add Media', 'pdf-print' ); ?></a>
								</div>
							</div>
							<div id="wp-pdfprntpr_top-editor-container" class="wp-editor-container"><textarea disabled style="width: 100%;" class="pdfprntpr_top wp-editor-area" rows="5" autocomplete="off" name="pdfprntpr_top" id="pdfprntpr_top"></textarea></div>
							</div>
						</div>
						<h3><span><?php _e( 'Bottom running title', 'pdf-print' ); ?></span></h3>
						<div class="postarea wp-editor-expand">
							<div id="wp-pdfprntpr_bottom-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
								<div id="wp-pdfprntpr_bottom-editor-tools" class="wp-editor-tools hide-if-no-js">
									<div id="wp-pdfprntpr_bottom-media-buttons" class="wp-media-buttons"><a disabled href="#" class="button insert-media add_media" data-editor="pdfprntpr_bottom" title="<?php _e( 'Add Media', 'pdf-print' ); ?>"><span class="wp-media-buttons-icon"></span> <?php _e( 'Add Media', 'pdf-print' ); ?></a></div>
								</div>
								<div id="wp-pdfprntpr_bottom-editor-container" class="wp-editor-container"><textarea disabled style="width: 100%;" class="pdfprntpr_bottom wp-editor-area" rows="5" autocomplete="off" name="pdfprntpr_bottom" id="pdfprntpr_bottom"></textarea></div>
							</div>
						</div>
					</div>
					<p><input disabled name="pdfprntpr_template_submit" type="submit" class="button-primary" value="<?php _e( 'Save template', 'pdf-print' ); ?>" /></p>			
				<?php } else { ?>
					<ul class='subsubsub'>
						<li class='all'><a class="current" href="#"><?php _e( 'All', 'pdf-print' ); ?><span class="count"> ( 2 )</span></a> |</li>
						<li class='trash'><a href="#"><?php _e( 'Trash', 'pdf-print' ); ?><span class="count"> ( 0 )</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text" for="pdfprntpr-search-input"><?php _e( 'search', 'pdf-print' ); ?>:</label>
						<input disabled type="search" id="pdfprntpr-search-input" name="s" value="" />
						<input disabled type="submit" id="search-submit" class="button" value="<?php _e( 'search', 'pdf-print' ); ?>" />
					</p>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<label for='bulk-action-selector-top' class='screen-reader-text'><?php _e( 'Select bulk action', 'pdf-print' ); ?></label>
							<select disabled name='action' id='bulk-action-selector-top'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'pdf-print' ); ?></option>
								<option value='trash'><?php _e( 'Trash', 'pdf-print' ); ?></option>
							</select>
							<input disabled type="submit" id="doaction" class="button action" value="<?php _e( 'Apply', 'pdf-print' ); ?>" />
						</div>
						<div class='tablenav-pages one-page'>
							<span class="displaying-num">2 <?php _e( 'items', 'pdf-print' ); ?></span>
							<span class='pagination-links'><a class='first-page disabled' title='<?php _e( 'Go to the first page', 'pdf-print' ); ?>' href='#'>&laquo;</a>
								<a class='prev-page disabled' title='<?php _e( 'Go to the previous page', 'pdf-print' ); ?>' href='#'>&lsaquo;</a>
								<span class="paging-input">
									<label for="current-page-selector" class="screen-reader-text"><?php _e( 'Select Page', 'pdf-print' ); ?></label>
									<input disabled class='current-page' id='current-page-selector' title='<?php _e( 'Current page', 'pdf-print' ); ?>' type='text' name='paged' value='1' size='1' /> <?php _e( 'of', 'pdf-print' ); ?> <span class='total-pages'>1</span>
								</span>
								<a class='next-page disabled' title='<?php _e( 'Go to the next page', 'pdf-print' ); ?>' href='#'>&rsaquo;</a>
								<a class='last-page disabled' title='<?php _e( 'Go to the last page', 'pdf-print' ); ?>' href='#'>&raquo;</a>
							</span>
						</div>
						<br class="clear" />
					</div>
					<table class="wp-list-table widefat striped templates">
						<thead>
							<tr><th scope='col' id='cb' class='manage-column column-cb check-column' style=""><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'pdf-print' ); ?></label><input disabled id="cb-select-all-1" type="checkbox" /></th><th scope='col' id='title' class='manage-column column-title sortable desc' style=""><a href="#"><span><?php _e( 'Title', 'pdf-print' ); ?></span><span class="sorting-indicator"></span></a></th><th scope='col' id='pdf' class='manage-column column-pdf'  style="">PDF</th><th scope='col' id='print' class='manage-column column-print'  style="">Print</th><th scope='col' id='date' class='manage-column column-date sortable desc'  style=""><a href="#"><span><?php _e( 'Date', 'pdf-print' ); ?></span><span class="sorting-indicator"></span></a></th></tr>
						</thead>
						<tbody id="the-list" data-wp-lists='list:template'>
							<tr><th scope="row" class="check-column"><input disabled id="cb_2" type="checkbox" name="pdfprntpr_template_id[]" value=" 2" /></th><td class='title column-title'><strong><a href="#">Custom template</a></strong> <div class="row-actions"><span class='edit'><a href="#"><?php _e( 'Edit', 'pdf-print' ); ?></a> | </span><span class='trash'><a class="submitdelete" href="#"><?php _e( 'Trash', 'pdf-print' ); ?></a></span></div></td><td class='pdf column-pdf'><input disabled type="radio" name="pdfprntpr_template_for_pdf" value="2" /></td><td class='print column-print'><input disabled type="radio" name="pdfprntpr_template_for_print" value="2" checked="checked" /></td><td class='date column-date'>June 26, 2015</td></tr><tr><th scope="row" class="check-column"><input disabled id="cb_1" type="checkbox" name="pdfprntpr_template_id[]" value=" 1" /></th><td class='title column-title'><strong><a href="#">New template</a></strong> <div class="row-actions"><span class='edit'><a href="#"><?php _e( 'Edit', 'pdf-print' ); ?></a> | </span><span class='trash'><a class="submitdelete" href="#"><?php _e( 'Trash', 'pdf-print' ); ?></a></span></div></td><td class='pdf column-pdf'><input disabled type="radio" name="pdfprntpr_template_for_pdf" value="1" checked="checked" /></td><td class='print column-print'><input disabled type="radio" name="pdfprntpr_template_for_print" value="1" /></td><td class='date column-date'>June 25, 2015</td></tr>
						</tbody>
						<tfoot>
							<tr><th scope='col' class='manage-column column-cb check-column' style=""><label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All', 'pdf-print' ); ?></label><input disabled id="cb-select-all-2" type="checkbox" /></th><th scope='col' class='manage-column column-title sortable desc'  style=""><a href="#"><span><?php _e( 'Title', 'pdf-print' ); ?></span><span class="sorting-indicator"></span></a></th><th scope='col' class='manage-column column-pdf' style="">PDF</th><th scope='col'  class='manage-column column-print'  style="">Print</th><th scope='col' class='manage-column column-date sortable desc'  style=""><a href="#"><span><?php _e( 'Date', 'pdf-print' ); ?></span><span class="sorting-indicator"></span></a></th></tr>
						</tfoot>
					</table>
					<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<label for='bulk-action-selector-top' class='screen-reader-text'><?php _e( 'Select bulk action', 'pdf-print' ); ?></label>
							<select disabled name='action' id='bulk-action-selector-top'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'pdf-print' ); ?></option>
								<option value='trash'><?php _e( 'Trash', 'pdf-print' ); ?></option>
							</select>
							<input disabled type="submit" id="doaction" class="button action" value="<?php _e( 'Apply', 'pdf-print' ); ?>" />
						</div>
						<div class='tablenav-pages one-page'>
							<span class="displaying-num">2 <?php _e( 'items', 'pdf-print' ); ?></span>
							<span class='pagination-links'><a class='first-page disabled' title='<?php _e( 'Go to the first page', 'pdf-print' ); ?>' href='#'>&laquo;</a>
								<a class='prev-page disabled' title='<?php _e( 'Go to the previous page', 'pdf-print' ); ?>' href='#'>&lsaquo;</a>
								<span class="paging-input">
									<label for="current-page-selector" class="screen-reader-text"><?php _e( 'Select Page', 'pdf-print' ); ?></label>
									<input disabled class='current-page' id='current-page-selector' title='<?php _e( 'Current page', 'pdf-print' ); ?>' type='text' name='paged' value='1' size='1' /> <?php _e( 'of', 'pdf-print' ); ?> <span class='total-pages'>1</span>
								</span>
								<a class='next-page disabled' title='<?php _e( 'Go to the next page', 'pdf-print' ); ?>' href='#'>&rsaquo;</a>
								<a class='last-page disabled' title='<?php _e( 'Go to the last page', 'pdf-print' ); ?>' href='#'>&raquo;</a>
							</span>
						</div>
						<br class="clear" />
					</div>				
				<?php } ?>
					</div>
					<div class="bws_pro_version_tooltip">
						<div class="bws_info">
							<?php _e( 'Unlock premium options by upgrading to PRO version.', 'pdf-print' ); ?> 
						</div>
						<a class="bws_button" href="http://bestwebsoft.com/products/pdf-print/?k=d9da7c9c2046bed8dfa38d005d4bffdb&pn=101&v=<?php echo $pdfprnt_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="PDF & Print Pro"><?php _e( 'Learn More', 'pdf-print' ); ?></a>
						<div class="clear"></div>					
					</div>
				</div>
			<?php } elseif ( 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab( $pdfprnt_plugin_info, $plugin_basename, 'pdf-print.php', 'pdf-print-pro.php', 'pdf-print-pro/pdf-print-pro.php', 'pdf-print', 'd9da7c9c2046bed8dfa38d005d4bffdb', '101', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			}
			bws_plugin_reviews_block( $pdfprnt_plugin_info['Name'], 'pdf-print' ); ?>
		</div>
	<?php }
}

/* Positioning buttons in the page */
if ( ! function_exists( 'pdfprnt_content' ) ) {
	function pdfprnt_content( $content ) {
		if ( is_admin() )
			return;
		global $pdfprnt_options_array, $post;
		if ( is_search() || is_archive() || is_category() || is_tax() || is_tag() || is_author() || ! in_array( $post->post_type, $pdfprnt_options_array['use_types_posts'] ) ) // Check for existence the type of posts.
			return $content;
		if ( 1 == $pdfprnt_options_array['show_btn_pdf'] || 1 == $pdfprnt_options_array['show_btn_print'] ) { 
			$str = '<div class="pdfprnt-' . $pdfprnt_options_array['position'] . '">';
			if ( 1 == $pdfprnt_options_array['show_btn_pdf'] ) {
				if ( ! is_front_page () )
					$permalink = add_query_arg( 'print' , 'pdf' , get_permalink( $post->ID ) );
				else 
					$permalink = add_query_arg( 'print' , 'pdf' , get_permalink() . '?page_id=' . $post->ID );
				$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/pdf.png', __FILE__ ) . '" alt="image_pdf" title="View PDF" /></a>';
			}
			if ( 1 == $pdfprnt_options_array['show_btn_print'] ) {
				if ( ! is_front_page () )
					$permalink = add_query_arg( 'print' , 'print' , get_permalink( $post->ID ) );
				else 
					$permalink = add_query_arg( 'print' , 'print' , get_permalink() . '?page_id=' . $post->ID );
				$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/print.gif', __FILE__ ) . '" alt="image_print" title="Print Content" /></a>';
			}
			$str .= '</div>';

			if ( 'top-left' == $pdfprnt_options_array['position'] || 'top-right' == $pdfprnt_options_array['position'] )
				$content = $str . $content;
			elseif ( 'bottom-left' == $pdfprnt_options_array['position'] || 'bottom-right' == $pdfprnt_options_array['position'] )
				$content = $content . $str;
			else
				$content = $str . $content . $str;
		}
		return $content;
	}
}

/* Output buttons for search or archive pages */
if ( ! function_exists( 'pdfprnt_show_buttons_search_archive' ) ) {
	function pdfprnt_show_buttons_search_archive( $content ) {
		global $wp_query;
		/* make sure that we display pdf/print buttons only with main loop */
		if ( is_main_query() && $content === $wp_query ) {
			global $pdfprnt_options_array, $wp, $posts, $pdfprnt_show_archive_start, $pdfprnt_show_archive_end;
			$loop_position = current_filter();
			if ( ! ( 1 == $pdfprnt_options_array['show_btn_pdf_search_archive'] || 1 == $pdfprnt_options_array['show_btn_print_search_archive'] ) )
				return;
			/* Check for existence the type of posts. */
			$is_return = true;
			foreach ( $posts as $post ) {
				if ( in_array( $post->post_type, $pdfprnt_options_array['use_types_posts'] ) ) {
					$is_return = false;
					break;
				}
			}
			if ( $is_return )
				return;

			if ( ( 'loop_start' == $loop_position && $pdfprnt_show_archive_start == 1 ) || ( 'loop_end' == $loop_position && $pdfprnt_show_archive_end == 1 ) )
				return;

			$current_url = ( empty( $wp->request ) ) ? add_query_arg( $wp->query_string, '', home_url() ) : home_url( $wp->request );

			$str = '<div class="pdfprnt-' . $pdfprnt_options_array['position_search_archive'] . '">';
			if ( 1 == $pdfprnt_options_array['show_btn_pdf_search_archive'] ) {
				$permalink = add_query_arg( 'print' , 'pdf-search' , $current_url );
				$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/pdf.png', __FILE__ ) . '" alt="image_pdf" title="Print PDF" /></a>';
			}
			if ( 1 == $pdfprnt_options_array['show_btn_print_search_archive'] ) {
				$permalink		=	add_query_arg( 'print' , 'print-search' , $current_url );
				$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/print.gif', __FILE__ ) . '" alt="image_print" title="Print Content" /></a>';
			}
			$str .= '</div>';
			echo $str;
			if ( 'loop_start' == $loop_position )
				$pdfprnt_show_archive_start++;
			elseif ( 'loop_end' == $loop_position )
				$pdfprnt_show_archive_end++;
		}
	}
}

/**
 * Add buttons before or after the loop
 */
if ( ! function_exists( 'pdfprnt_auto_show_buttons_search_archive' ) ) {
	function pdfprnt_auto_show_buttons_search_archive() {
		if ( is_search() || is_archive() || is_category() || is_tax() || is_tag() || is_author() ) {
			global $pdfprnt_options_array, $pdfprnt_show_archive_start, $pdfprnt_show_archive_end;
			$pdfprnt_show_archive_start = $pdfprnt_show_archive_end = 0;
			if ( in_array( $pdfprnt_options_array['position_search_archive'], array( 'top-left', 'top-right' ) ) ) {
				add_action( 'loop_start', 'pdfprnt_show_buttons_search_archive' ); 
			} elseif ( in_array( $pdfprnt_options_array['position_search_archive'], array( 'bottom-left', 'bottom-right' ) ) ) {
				add_action( 'loop_end', 'pdfprnt_show_buttons_search_archive' );
			} else {
				add_action( 'loop_start', 'pdfprnt_show_buttons_search_archive' );
				add_action( 'loop_end', 'pdfprnt_show_buttons_search_archive' );
			}
		}
	}
}

/**
 * Output buttons of page for BWS Portfolio plugin 
 * @deprecated since 1.8.4
 */
if( ! function_exists( 'pdfprnt_show_buttons_for_bws_portfolio' ) ) {
	function pdfprnt_show_buttons_for_bws_portfolio() {
		return pdfprnt_show_buttons_for_custom_post_type();
	}
}

/**
 * Output buttons of post for BWS Portfolio plugin 
 * @deprecated since 1.8.4
 */
if ( ! function_exists( 'pdfprnt_show_buttons_for_bws_portfolio_post' ) ) {
	function pdfprnt_show_buttons_for_bws_portfolio_post() {
		return pdfprnt_show_buttons_for_custom_post_type();
	}
}


/**
 * Display plugin buttons via action call 
 * @param     string       $where         where to display
 * @param     mixed        $user_query    WP_Query parameters
 */
if ( ! function_exists( 'pdfprnt_display_plugin_butttons' ) ) {
	function pdfprnt_display_plugin_butttons( $where = 'top', $user_query = '' ) {
		global $pdfprnt_options_array;
		if ( preg_match( "|" . $where . "|", $pdfprnt_options_array['position'] ) || empty( $where ) )
			echo pdfprnt_show_buttons_for_custom_post_type( $user_query );
	}
}

/* Output buttons of page for custom post type */
if ( ! function_exists( 'pdfprnt_show_buttons_for_custom_post_type' ) ) {
	function pdfprnt_show_buttons_for_custom_post_type( $user_query = '' ) {
		global $pdfprnt_options_array, $post, $wp, $posts;
		if ( 
			/* not display anything if displaying buttons for post and pages is disabled */
			( ! ( 1 == $pdfprnt_options_array['show_btn_pdf'] || 1 == $pdfprnt_options_array['show_btn_print'] ) ) ||
			/* not display anything if we have wrong $user_query */
			( ! ( is_array( $user_query ) || is_string( $user_query ) ) )
		)
			return;
		
		$is_return = true;
		if ( empty( $user_query ) ) { /* set necessary values of parameters for pdf/print buttons */
			$current_url = empty( $wp->request ) ? add_query_arg( $wp->query_string, '', home_url() ) : home_url( $wp->request );
			$nothing_else = false;
			if ( is_search() || is_archive() || is_category() || is_tax() || is_tag() || is_author() ) { /* search, cattegories, archives */
				foreach ( $posts as $value ) {
					if ( in_array( $value->post_type, $pdfprnt_options_array['use_types_posts'] ) ) {
						$is_return = false;
						break;
					}
				}
				if ( $is_return )
					return;
				$pdf_query_parameter   = 'pdf-search';
				$print_query_parameter = 'print-search';
			} elseif ( is_page() ) { /* pages */
				if ( in_array( 'page', $pdfprnt_options_array['use_types_posts'] ) ) {
					$nothing_else = true;
				} else {
					return;
				}
			} elseif ( is_single() ) { /* posts */
				$post_type = get_post_type( $post->ID );
				if ( in_array( $post_type, $pdfprnt_options_array['use_types_posts'] ) ) {
					$nothing_else = true;
				} else {
					return;
				}
			} else { 
				$nothing_else = true;
			} 
			if ( $nothing_else ) {
				$pdf_query_parameter   = 'pdf';
				$print_query_parameter = 'print';
			}
		} else {
			$custom_query = new WP_Query( $user_query );
			$current_url  = add_query_arg( $custom_query->query, '', home_url() );
			/* Check for existence the type of posts. */
			if ( ! empty( $custom_query->posts) ) {
				foreach ( $custom_query->posts as $post ) {
					if ( in_array( get_post_type( $post ), $pdfprnt_options_array['use_types_posts'] ) ) {
						$is_return = false;
						break;
					}
				}
			}
			if ( $is_return )
				return;
			$pdf_query_parameter   = 'pdf-custom';
			$print_query_parameter = 'print-custom';
		}

		$str = '<div class="pdfprnt-' . $pdfprnt_options_array['position'] . '">';
		if ( 1 == $pdfprnt_options_array['show_btn_pdf'] ) {
			$permalink = add_query_arg( 'print' , $pdf_query_parameter , $current_url );
			$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/pdf.png', __FILE__ ) . '" alt="image_pdf" title="Print PDF" /></a>';
		}
		if ( 1 == $pdfprnt_options_array['show_btn_print'] ) {
			$permalink = add_query_arg( 'print' , $print_query_parameter , $current_url );
			$str .= '<a href="' . $permalink . '" target="_blank"><img src="' . plugins_url( 'images/print.gif', __FILE__ ) . '" alt="image_print" title="Print Content" /></a>';
		}
		$str .= '</div>';
		return $str;
	}
}

/* Add links */
if ( ! function_exists( 'pdfprnt_action_links' ) ) {
	function pdfprnt_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			$base = plugin_basename( __FILE__ );
			if ( $file == $base ) {
				$settings_link = '<a href="admin.php?page=pdf-print.php">' . __( 'Settings', 'pdf-print' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add links */
if ( ! function_exists( 'pdfprnt_links' ) ) {
	function pdfprnt_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[]	=	'<a href="admin.php?page=pdf-print.php">' . __( 'Settings', 'pdf-print' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/pdf-print/faq/" target="_blank">' . __( 'FAQ', 'pdf-print' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'pdf-print' ) . '</a>';
		}
		return $links;
	}
}

/* Add stylesheets */
if ( ! function_exists ( 'pdfprnt_admin_head' ) ) {
	function pdfprnt_admin_head() {
		global $wp_version;
		if ( $wp_version < 3.8 )
			wp_enqueue_style( 'pdfprnt_stylesheet', plugins_url( 'css/style_wp_before_3.8.css', __FILE__ ) );	
		else
			wp_enqueue_style( 'pdfprnt_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );

		if ( isset( $_GET['page'] ) && "pdf-print.php" == $_GET['page'] ) {
			wp_enqueue_script( 'pdfprnt_script', plugins_url( 'js/script.js', __FILE__ ) );
			wp_localize_script( 'pdfprnt_script', 'pdfprnt_var', array(
					'loading_fonts' => __( 'Loading of fonts. It might take a several minutes', 'pdf-print' ),
					'ajax_nonce'    => wp_create_nonce( 'pdfprnt_ajax_nonce' ),
				) 
			);
		}
	}
}
/* Remove inline 'font-family' and 'font' styles from content */
if ( ! function_exists( 'pdfprnt_preg_replace' ) ) {
	function pdfprnt_preg_replace( $patterns, $content ) {
		foreach( $patterns as $pattern ) {
			$content = preg_replace( "/" . $pattern . "(.*?);/", "", $content );
			preg_match_all( "~style=(\"\'?)~", $content, $quotes );/* get array with quotes */
			if ( isset( $quotes[1] ) && ! empty( $quotes[1] ) ) {
				foreach ( $quotes[1] as $quote ) {
					preg_match_all( "~style=" . $quote . "(.*?)" . $quote . "~", $content, $styles );
					if ( ! empty( $styles[1] ) ) {
						foreach ( $styles[1] as $style ) {
							if ( preg_match( "/" . $pattern . "/", $style ) )
								$content = preg_replace( "/" . $style . "/", "", $content );
						}
						
					}
				}
			}
		}
		return $content;
	}
}

/* Generate templates for pdf file or print */
if ( ! function_exists( 'pdfprnt_generate_template' ) ) {
	function pdfprnt_generate_template( $content, $isprint = false ) {
		global $pdfprnt_options_array;
		$html =
		'<html>
			<head>';
				if ( 1 == $pdfprnt_options_array['use_theme_stylesheet'] ) {
					/* remove 'font-family' and 'font' styles from theme css-file if additional fonts not loaded */
					if ( 0 == $pdfprnt_options_array['additional_fonts'] ) {
						$css = file_get_contents( get_bloginfo( 'stylesheet_url' ) );
						if ( ( ! empty( $css ) ) )
							$html .= '<style type="text/css">' . preg_replace( "/(font:(.*?);)|(font-family(.*?);)/", "", $css ) . '</style>';
					} else {
						$html .= '<link type="text/css" rel="stylesheet" href="' . get_bloginfo( 'stylesheet_url' ) . '" media="all" />';
					}
				} else {
					$html .= '<link type="text/css" rel="stylesheet" href="' . plugins_url( 'css/default.css', __FILE__ ) . '" media="all" />';
				}
				if ( $isprint && 1 == $pdfprnt_options_array['show_print_window'] )
					$html .= '<script>window.onload = function(){ window.print(); };</script>';
			$html .= 
			'</head>
			<body>';
				/* Remove inline 'font-family' and 'font' styles from content */
				if ( 0 == $pdfprnt_options_array['additional_fonts'] )
					$content = pdfprnt_preg_replace( array( "font-family", "font:" ), $content );
				$html .= apply_filters( 'bwsplgns_get_pdf_print_content', $content ) .
			'</body>
		</html>';
		return $html;
	}
}

/* Output print page or pdf document and include plugin script */
if ( ! function_exists( 'pdfprnt_print' ) ) {
	function pdfprnt_print( $query ) {
		global $pdfprnt_options_array, $posts, $post;
		if ( $print	= get_query_var( 'print' ) ) {
			remove_all_filters( 'the_content' );
			add_filter( 'the_content', 'capital_P_dangit', 11 );
			add_filter( 'the_content', 'wptexturize' );
			add_filter( 'the_content', 'convert_smilies' );
			add_filter( 'the_content', 'convert_chars' );
			add_filter( 'the_content', 'wpautop' );
			if ( ! 0 == $pdfprnt_options_array['tmpl_shorcode'] ) {
				add_filter( 'the_content', 'do_shortcode' ); /* executing shortcodes on the page */
			} else {
				$pattern = get_shortcode_regex();
				if ( preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches ) ) { /* getting all shortcodes we are using */
					foreach ( array_unique( $matches[0] ) as $value) {
						$post->post_content = str_replace( $value, "", $post->post_content ); /* replacing shortcodes to an empty string */
					}
				}
			}
			/* set path to directory with fonts for PDF document */
			if ( ! ( 0 == $pdfprnt_options_array['additional_fonts'] && defined( "_MPDF_SYSTEM_TTFONTS" ) ) ) {
				if ( is_multisite() ) {
					switch_to_blog( 1 );
					$upload_dir = wp_upload_dir();
					restore_current_blog();
				} else {
					$upload_dir = wp_upload_dir();
				}
				define( '_MPDF_SYSTEM_TTFONTS', $upload_dir['basedir'] .'/pdf-print-fonts/' ); 
			}
			switch ( $print ) {
				case 'pdf':	/* Content for PDF from post */
					include ( 'mpdf/mpdf.php' );
					$user_info    = get_userdata( $post->post_author );
					$default_font = 0 == $pdfprnt_options_array['additional_fonts'] ? 'dejavusansmono' : '';
					$mpdf         = new mPDF( '+aCJK', 'A4', 0, $default_font );
					$mpdf->allow_charset_conversion = true;
					$mpdf->charset_in               = get_bloginfo( 'charset' );
					if ( ! 0 == $pdfprnt_options_array['additional_fonts'] ) {
						$mpdf->autoScriptToLang = true;
						$mpdf->autoLangToFont   = true;
						$mpdf->baseScript       = 1;
						$mpdf->autoVietnamese   = true;
						$mpdf->autoArabic       = true;
					}
					if ( is_rtl() )
						$mpdf->SetDirectionality( 'rtl' );
					$mpdf->SetAuthor( $user_info->display_name );
					$mpdf->SetTitle( $post->post_title );
					$mpdf->SetSubject( get_bloginfo( 'blogdescription' ) );
					$html = '<div id="content">
								<div class="post">
									<div class="entry-header"><h1 class="entry-title"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></h1></div><br/>
									<div class="entry-content">' . apply_filters( 'the_content', $post->post_content ) . '</div>
								</div>
							</div>';
					$mpdf->WriteHTML( pdfprnt_generate_template( $html ) );
					$mpdf->Output();
					break;
				case 'print': /* Content for printing from post */
					$html = '<div id="content">
								<div class="post">
									<div class="entry-header"><h1 class="entry-title">' . $post->post_title . '</h1></div><br/>
									<div class="entry-content">' . apply_filters( 'the_content', $post->post_content ) . '</div>
								</div>
							</div>';
					echo pdfprnt_generate_template( $html, true );
					die();
					break;
				case 'pdf-search': /* Content for PDF from archive or searching page */
					$titles		=	array();
					$authors	=	array();
					include ( 'mpdf/mpdf.php' );
					$user_info    = get_userdata( $post->post_author );
					$default_font = 0 == $pdfprnt_options_array['additional_fonts'] ? 'dejavusansmono' : '';
					$mpdf         = new mPDF( '+aCJK', 'A4', 0, $default_font );
					$mpdf->allow_charset_conversion = true;
					$mpdf->charset_in               = get_bloginfo( 'charset' );
					if ( ! 0 == $pdfprnt_options_array['additional_fonts'] ) {
						$mpdf->autoScriptToLang = true;
						$mpdf->autoLangToFont   = true;
						$mpdf->baseScript           = 1;
						$mpdf->autoVietnamese  = true;
						$mpdf->autoArabic          = true;
					}
					if ( is_rtl() )
						$mpdf->SetDirectionality( 'rtl' );
					$html		=	'<div id="content">';
					foreach ( $posts as $p ) {
						if ( ! in_array( get_post_type( $p ), $pdfprnt_options_array['use_types_posts'] ) )
							continue;
						$titles[]	=	$p->post_title;
						$user_info	=	get_userdata( $p->post_author );
						$authors[]	=	$user_info->display_name;
						$html      .=	'<div class="post">
									<div class="entry-title"><h1 class="entry-header"><a href="' . get_permalink( $p->ID ) . '">' . $p->post_title . '</a></h1></div><br/>
									<div class="entry-content">' . apply_filters( 'the_content', $p->post_content ) . '</div>
								</div><br/><hr/><br/>';
					}
					$html .= '</div>';
					$titles		=	array_unique( $titles );
					$authors	=	array_unique( $authors );
					$mpdf->SetTitle( implode( ',', $titles ) );
					$mpdf->SetAuthor( implode( ',', $authors ) );
					$mpdf->SetSubject( get_bloginfo( 'blogdescription' ) );
					$mpdf->WriteHTML( pdfprnt_generate_template( $html ) );
					$mpdf->Output();
					break;
				case 'print-search': /* Content for printing from archive or searching page */
					$html = '<div id="content">';
					foreach ( $posts as $p ) {
						if ( ! in_array( get_post_type( $p ), $pdfprnt_options_array['use_types_posts'] ) )
							continue;
						$html .= '<div class="post">
									<div class="entry-header"><h1 class="entry-title">' . $p->post_title . '</h1></div><br/>
									<div class="entry-content">' . apply_filters( 'the_content', $p->post_content ) . '</div>
								</div><br/><hr/><br/>';
					}
					$html .= '</div>';
					echo pdfprnt_generate_template( $html, true );
					die();
					break;
				case 'pdf-custom': /* Content for PDF from custom post */
					$url_data = parse_url( $_SERVER['REQUEST_URI'] );
					parse_str( $url_data['query'], $args );
					unset( $args['print'] );
					if ( ! empty( $args ) ) {
						$posts = get_posts( $args );
					}
					$html		=	'<div id="content">';
					$titles		=	$authors	=	array();
					foreach ( $posts as $p ) {
						if ( ! in_array( get_post_type( $p ), $pdfprnt_options_array['use_types_posts'] ) )
							continue;
						$titles[]	=	$p->post_title;
						$user_info	=	get_userdata( $p->post_author );
						$authors[]	=	$user_info->display_name;
						$html		.=	'<div class="post">
										<div class="entry-header"><h1 class="entry-title"><a href="' . get_permalink( $p->ID ) . '">' . $p->post_title . '</a></h1></div><br/>
										<div class="entry-content">';
						/*if ( has_post_thumbnail( $p->ID ) )
							$html	.=	get_the_post_thumbnail( $p->ID, 'thumbnail' );*/
						$html		.=	apply_filters( 'the_content', $p->post_content ) . '</div></div><br/><hr/><br/>';
					}
					$html .= '</div>';
					include ( 'mpdf/mpdf.php' );
					$default_font = 0 == $pdfprnt_options_array['additional_fonts'] ? 'dejavusansmono' : '';
					$mpdf         = new mPDF( '+aCJK', 'A4', 0, $default_font );
					$mpdf->allow_charset_conversion = true;
					$mpdf->charset_in               = get_bloginfo( 'charset' );
					if ( ! 0 == $pdfprnt_options_array['additional_fonts'] ) {
						$mpdf->autoScriptToLang = true;
						$mpdf->autoLangToFont   = true;
						$mpdf->baseScript       = 1;
						$mpdf->autoVietnamese   = true;
						$mpdf->autoArabic       = true;
					}
					if ( is_rtl() )
						$mpdf->SetDirectionality( 'rtl' );
					$titles		=	array_unique( $titles );
					$authors	=	array_unique( $authors );
					$mpdf->SetTitle( implode( ',', $titles ) );
					$mpdf->SetAuthor( implode( ',', $authors ) );
					$mpdf->SetSubject( get_bloginfo( 'blogdescription' ) );
					$mpdf->WriteHTML( pdfprnt_generate_template( $html ) );
					$mpdf->Output();
					break;
				case 'print-custom': /* Content for printing from custom post */
					$url_data = parse_url( $_SERVER['REQUEST_URI'] );
					parse_str( $url_data['query'], $args );
					unset( $args['print'] );
					if ( ! empty( $args ) ) {
						$posts = get_posts( $args );
					}
					$html = '<div id="content">';
					foreach ( $posts as $p ) {
						if ( ! in_array( get_post_type( $p ), $pdfprnt_options_array['use_types_posts'] ) )
							continue;
						$html	.=	'<div class="post">
									<div class="entry-header"><h1 class="entry-title">' . $p->post_title . '</h1></div><br/>
									<div class="entry-content">';
						/*if ( has_post_thumbnail( $p->ID ) )
							$html .= get_the_post_thumbnail( $p->ID, 'thumbnail' );*/
						$html 	.=	apply_filters( 'the_content', $p->post_content ) . '</div></div><br/><hr/><br/>';
					}
					$html .= '</div>';
					echo pdfprnt_generate_template( $html, true );
					die();
					break;
			}
		}
	}
}

/* Add query vars */
if ( ! function_exists( 'print_vars_callback' ) ) {
	function print_vars_callback( $query_vars ) {
		$query_vars[] = 'print';
		return $query_vars;
	}
}

/**
 * Class Pdfprnt_ZipArchive for extracting
 * necessary folder from zip-archive
 */
if ( class_exists( 'ZipArchive' ) && ! class_exists( 'Pdfprnt_ZipArchive' ) ) {
	class Pdfprnt_ZipArchive extends ZipArchive {
		/**
		 * constructor of class
		 */
		public function extractSubdirTo( $destination, $subdir ) {
			$errors = array();
			$charset = get_bloginfo( 'charset' );
			// Prepare dirs
			$destination = str_replace( array("/", "\\"), DIRECTORY_SEPARATOR, $destination );
			$subdir = str_replace( array("/", "\\"), "/", $subdir);

			if ( substr( $destination, mb_strlen( DIRECTORY_SEPARATOR, $charset ) * -1 ) != DIRECTORY_SEPARATOR )
				$destination .= DIRECTORY_SEPARATOR;

			if ( substr( $subdir, -1 ) != "/" )
				$subdir .= "/";
			// Extract files
			for ( $i = 0; $i < $this->numFiles; $i++ ) {
				$filename = $this->getNameIndex( $i );

				if ( substr( $filename, 0, mb_strlen( $subdir, $charset ) ) == $subdir ) {
					$relativePath = substr( $filename, mb_strlen( $subdir, $charset ) );
					$relativePath = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $relativePath );

					if ( mb_strlen( $relativePath, $charset ) > 0 ) {
						if ( substr( $filename, -1 ) == "/" ) {
							if ( ! is_dir( $destination . $relativePath ) )
								if ( ! @mkdir( $destination . $relativePath, 0755, true ) )
									$errors[$i] = $filename;
						} else {
							if ( dirname( $relativePath) != "." ) {
								if ( ! is_dir( $destination . dirname( $relativePath ) ) ) {
									// New dir (for file)
									@mkdir( $destination . dirname( $relativePath ), 0755, true );
								}
							}
							// New file
							if ( @file_put_contents( $destination . $relativePath, $this->getFromIndex( $i ) ) === false )
								$errors[$i] = $filename;
						}
					}
				}
			}
			return $errors;
		}
	}
}

/**
 * Download Zip 
  */
if ( ! function_exists( 'pdfprnt_download_zip' ) ) {
	function pdfprnt_download_zip( $zip_file, $upload_dir ) {
		/* check permissions */
		if ( is_writable( $upload_dir ) ) {
			/* load ZIP-archive */
			$result = array();
			$fp     = fopen( $zip_file, 'w+');
			$curl   = curl_init();
			$curl_parametres = array(
				CURLOPT_URL       => 'http://mpdf1.com/repos/MPDF60.zip',
				CURLOPT_FILE      => $fp,
				CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0"
			);
			curl_setopt_array( $curl, $curl_parametres );
			if ( curl_exec( $curl ) ) {
				$result['done'] = 'ok';
			} else {
				$result['error'] = curl_error( $curl ) . '<br />' . __( 'Check your internet connection', 'pdf-print' );
			}
			curl_close( $curl );
			fclose( $fp );
		} else {
			$result['error'] = __( 'Cannot load files in to "uploads" folder. Check your permissions', 'pdf-print' );
		} 
		return $result;
	}
}

/**
 * Copy neccesary fonts to mpdf/ttfonts 
 * @param     string   $zip_file     path to zip archive
 */
if ( ! function_exists( 'pdfprnt_copy_fonts' ) ) {
	function pdfprnt_copy_fonts( $zip_file, $upload_dir ) {
		global $pdfprnt_options_array;
		if ( empty( $pdfprnt_options_array ) )
			$pdfprnt_options_array = get_option( 'pdfprnt_options_array' );
		if ( is_multisite() )
			$network_options = get_site_option( 'pdfprnt_options_array' );
		$destination = $upload_dir .'/pdf-print-fonts';
		if ( ! file_exists( $destination ) ) {
			mkdir( $destination, 0755, true );
		}
		$result = array();
		/* check permissions */
		if ( is_writable( $destination ) ) {
			$zip = new Pdfprnt_ZipArchive();
			/* open zip-archive */
			if ( true === $zip->open( $zip_file ) ) {
				/* extract folder with fonts */
				$errors = $zip->extractSubdirTo( $destination, "mpdf60/ttfonts" );
				$zip->close();
				/* delete zip-archive */
				unlink( $zip_file );
				if ( empty( $errors ) ) {
					$result['done'] = __( 'Additional fonts were successfully loaded', 'pdf-print' );
					$pdfprnt_options_array['additional_fonts'] = count( scandir( $destination ) );
					update_option( 'pdfprnt_options_array', $pdfprnt_options_array );
					if ( is_multisite() ) {
						$network_options['additional_fonts'] = $pdfprnt_options_array['additional_fonts'];
						update_site_option( 'pdfprnt_options_array', $network_options );
					}
				} else {
					$result['error'] = __( 'Some errors occur during loading files', 'pdf-print' );
				}
			} else {
				$result['error'] = __( 'Some errors occur during loading files', 'pdf-print' );
			}
		} else {
			$result['error'] = __( 'Cannot create "uploads/pdf-print-fonts" folder. Check your permissions', 'pdf-print' );
		}
		return $result;
	}
}

/**
 * Download ZIP-archive with MPDF library,
 * copy fonts to folder 'pdf-print-fons'
 */
if ( ! function_exists( 'pdfprnt_load_and_copy' ) ) {
	function pdfprnt_load_and_copy( $zip_file, $upload_dir ) {
		if ( file_exists( $zip_file ) )
			unlink( $zip_file );
		/* load ZIP-archive  */
		$result = pdfprnt_download_zip( $zip_file, $upload_dir );
		/* if ZIP-archive was download successfully */
		if ( isset( $result['done'] ) )
			$result = pdfprnt_copy_fonts( $zip_file, $upload_dir );
		return $result;
	}
}
/**
 * Function to load fonts for MPDF library
 */
if ( ! function_exists( 'pdfprnt_load_fonts' ) ) {
	function pdfprnt_load_fonts() {
		global $pdfprnt_options_array;
		if ( empty( $pdfprnt_options_array ) )
			$pdfprnt_options_array = get_option( 'pdfprnt_options_array' );
		$ajax_request = isset( $_REQUEST['action'] ) && 'pdfprnt_load_fonts' == $_REQUEST['action'] ? true : false;
		$php_request  = isset( $_REQUEST['pdfprnt_action'] ) && 'pdfprnt_load_fonts' == $_REQUEST['pdfprnt_action'] ? true : false;
		$verified     = isset( $_REQUEST['pdfprnt_ajax_nonce'] ) && wp_verify_nonce( $_REQUEST['pdfprnt_ajax_nonce'], 'pdfprnt_ajax_nonce' ) ? true : false;
		if ( ( $ajax_request || $php_request ) && $verified ) {
			$result = array();
			$flag   = false;
			/* get path to directory for ZIP-archive uploading */
			if ( is_multisite() ) {
				switch_to_blog( 1 );
				$upload_dir = wp_upload_dir();
				restore_current_blog();
			} else {
				$upload_dir = wp_upload_dir();
			}
			$zip_file    = $upload_dir['basedir'] . '/MPDF60.zip';
			$destination = $upload_dir['basedir'] .'/pdf-print-fonts';
			if ( file_exists( $destination ) ) { /* if folder with fonts already exists */
				if ( is_multisite() ) { 
					$network_options = get_site_option( 'pdfprnt_options_array' ); /* get network options */
					if ( $network_options['additional_fonts'] == count( scandir( $destination ) ) ) { /* if all fonts was loaded successfully */
						$pdfprnt_options_array['additional_fonts'] = $network_options['additional_fonts'];
						update_option( 'pdfprnt_options_array', $pdfprnt_options_array ); /* update only option of current blog */
						$result['done'] = __( 'Additional fonts was successfully loaded', 'pdf-print' );
					} else { /* if something wrong */
						$result = pdfprnt_load_and_copy( $zip_file, $upload_dir['basedir'] ); /* load fonts */
					}
				} else {
					$result = pdfprnt_load_and_copy( $zip_file, $upload_dir['basedir'] ); /* load fonts */
				}
			} else {
				mkdir( $destination, 0755, true );
				$result = pdfprnt_load_and_copy( $zip_file, $upload_dir['basedir'] );
			}
			if ( $ajax_request )
				echo json_encode( $result );
			else
				return $result;
		}
		if ( $ajax_request )
			die();
	}
}

if ( ! function_exists ( 'pdfprnt_plugin_banner' ) ) {
	function pdfprnt_plugin_banner() {
		global $hook_suffix, $pdfprnt_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner( $pdfprnt_plugin_info, 'pdfprnt', 'pdf-print', 'e2f2549f4d70bc4cb9b48071169d264e', '101', '//ps.w.org/pdf-print/assets/icon-128x128.png' );
		}
	}
}

/* Deleting plugin options on uninstalling */
if ( ! function_exists( 'pdfprnt_uninstall' ) ) {
	function pdfprnt_uninstall() {
		global $wpdb;
		if ( is_multisite() ) {
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			$tables = '';
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'pdfprnt_options_array' );
			}
			restore_current_blog();
		} else {
			delete_option( 'pdfprnt_options_array' );
		}
	}
}

/* Adding function to output PDF document or pirnt page */
add_action( 'wp', 'pdfprnt_print' );
add_action( 'wp_head', 'pdfprnt_auto_show_buttons_search_archive' );
/* Initialization */
add_action( 'init', 'pdfprnt_init' );
add_action( 'admin_init', 'pdfprnt_admin_init' );
/* Adding stylesheets */
add_action( 'admin_enqueue_scripts', 'pdfprnt_admin_head' );
add_action( 'wp_enqueue_scripts', 'pdfprnt_admin_head' );
/* Adding 'BWS Plugins' admin menu */
add_action( 'admin_menu', 'pdfprnt_add_pages' );
/* Add query vars */
add_filter( 'query_vars', 'print_vars_callback' );
/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pdfprnt_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'pdfprnt_links', 10, 2 );
/* Adding buttons plugin to content */
add_filter( 'the_content', 'pdfprnt_content' );
/* load additional fonts */
add_action( 'wp_ajax_pdfprnt_load_fonts', 'pdfprnt_load_fonts' );
/* Adding banner */
add_action( 'admin_notices', 'pdfprnt_plugin_banner' );
/* Plugin uninstall function */

add_action( 'bwsplgns_display_pdf_print_buttons', 'pdfprnt_display_plugin_butttons', 10, 2 );
register_uninstall_hook( __FILE__, 'pdfprnt_uninstall' );