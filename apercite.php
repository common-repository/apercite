<?php
/*  Copyright 2009  Francis Besset  (email : http://www.apercite.fr/en/contact.html)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*
Plugin Name: Apercite
Plugin URI: http://www.apercite.fr/en/
Description: The aim of this plugin is to display a thumbnail when hovering over a link in the blog posts. | <a href="options-general.php?page=apercite/apercite.php">Plugin Settings</a>
Version: 1.0.3
Author: Francis Besset
Author URI: http://www.apercite.fr/en/contact.html
*/

$apercite_version = "1.0.3";
$apercite_uri = 'http://www.apercite.fr/';
$apercite_uri_update = $apercite_uri.'api/maj-apercite/%1$s/%2$s/adresse/%3$s/%4$s/%5$s';
$apercite_sizes = array(
	'4:3' => array(
		'80x60'		=> '80x60',
		'100x75'	=> '100x75',
		'120x90'	=> '120x90',
		'160x120'	=> '160x120',
		'180x135'	=> '180x135',
		'240x180'	=> '240x180',
		'320x240'	=> '320x240',
		'560x420'	=> '560x420',
		'640x480'	=> '640x480',
		'800x600'	=> '800x600'
	),
	'16:10' => array(
		'80x50'		=> '80x50',
		'120x75'	=> '120x75',
		'160x100'	=> '160x100',
		'200x125'	=> '200x125',
		'240x150'	=> '240x250',
		'320x200'	=> '320x200',
		'560x350'	=> '560x350',
		'640x400'	=> '640x400',
		'800x500'	=> '800x500'
	)
);


add_action('admin_menu', 'apercite_menu');
add_action('admin_menu', 'apercite_meta_box_add');

add_action('save_post', 'apercite_update');

add_action('wp', 'apercite_js');
add_action('wp_head', 'apercite_head_js');
add_action('wp_footer', 'apercite_footer');


if(!function_exists('apercite_js')) {
	function apercite_js() {
		global $apercite_version;

		$apercite_script_url = WP_PLUGIN_URL.'/apercite/js/apercite.js';
		$apercite_script_file = WP_PLUGIN_DIR.'/apercite/js/apercite.js';
		
		$apercite_style_url = WP_PLUGIN_URL.'/apercite/css/style.css';
        $apercite_style_file = WP_PLUGIN_DIR.'/apercite/css/style.css';
        
		if(file_exists($apercite_script_file)) {
			wp_enqueue_script('apercite',
				$apercite_script_url,
				array('jquery'),
				$apercite_version
			);
		}
		
		if(file_exists($apercite_style_file)) {
			wp_register_style('aperciteSteelsheets', $apercite_style_url);
			wp_enqueue_style( 'aperciteSteelsheets');
		}
	}
}

if(!function_exists('apercite_head_js')) {
	function apercite_head_js() {
		$apercite_arr = get_option('apercite_params');
		
		echo '<script type="text/javascript">'."\n".
			 '//<![CDATA['."\n".
			 'var $j = jQuery.noConflict();'."\n".

			 '$j(function(){'."\n".
				'$j("body").apercite({'."\n".
					'"workers":Array('."\n";
					foreach(unserialize($apercite_arr['apercite_workers']) as $k=>$v) {
						if($k) {
							echo ','."\n";
						}
						echo '"'.esc_attr($v).'"';
					}
		echo 		'),'."\n".
					'"baseURL":"'.get_option('siteurl').'",'."\n".
					'"localLink":"'.($apercite_arr['apercite_local_link'] ? 'oui' : 'non').'",'."\n".
					'"sizeX":120,'."\n".
					'"sizeY":75,'."\n".
					'"javascript":"'.($apercite_arr['apercite_javascript'] ? 'oui' : 'non').'",'."\n".
					'"java":"'.($apercite_arr['apercite_java'] ? 'oui' : 'non').'",'."\n".
					'"ssl":"'.(isset($apercite_arr['apercite_ssl']) && $apercite_arr['apercite_ssl'] ? 'oui' : 'non').'"'."\n".
				'});'."\n".
			 '});'."\n".
			 '//]]>'."\n".
			 '</script>';
	}
}

if(!function_exists('apercite_footer')) {
	function apercite_footer() {
		global $apercite_uri;

		$seo = array(
			'Générateur de miniatures',
			'Screenshot',
			'AscreeN',
			'Miniatures de site',
			'Thumbnail',
			'Miniature',
			'Aperçu de site',
			'Thumb de site',
			'Générateur AscreeN',
			'Apercite',
		);

		if (!empty($_SERVER['REQUEST_URI']))
		{
			$num = (string)(int)md5($_SERVER['REQUEST_URI']);
			$num = $num{0};
		}
		else
		{
			$num = rand(0, 9);
		}

		echo '<div id="apercite-thumbnail"><a href="'.$apercite_uri.'" title="'.$seo[$num].'">'.$seo[$num].'</a></div>';
	}
}

if(!function_exists('apercite_update')) {
	function apercite_update() {
		global $post_ID, $apercite_uri_update;
		if(!defined('APERCITE_UPDATE')) {
			define('APERCITE_UPDATE', true);
			
			
			if(empty($_POST['apercite_update'])) { return; }
			
			$apercite_arr = get_option('apercite_params');
			
			if(empty($apercite_arr['apercite_login']) OR empty($apercite_arr['apercite_api_key'])) { return; }
			
			$javascript = $apercite_arr['apercite_javascript'] ? 'yes' : 'no';
			$java = $apercite_arr['apercite_java'] ? 'yes' : 'no';
			
			$post = get_post($post_ID);
			$post = $post->post_excerpt.$post->post_content;
			
			if(preg_match_all('#<a(?:.*)href="((?:(?:https?://)?(?:(?:[-_a-zA-Z0-9]+\.)*(?:[-a-zA-Z0-9]{1,63})\.(?:[a-zA-Z]{2,4})|(?:(?:[0-1]|[0-9]{2}|1[0-9]{2}|2(?:[0-4][0-9]|5[0-5]))\.){3}(?:[0-1]|[0-9]{2}|1[0-9]{2}|2(?:[0-4][0-9]|5[0-5]))))?(?:\:[0-9]{0,5})?(?:/(?:[^"])*)?)#', $post, $match)) {
				$blog_url = preg_quote(get_option('siteurl'));
				
				$tab_uri = array();
				foreach($match[1] as $k=>$v) {
					if(!empty($v)) {
						$update = true;
						
						if(!$apercite_arr['apercite_local_link']) {
							if(preg_match('#^'.$blog_url.'#', $v)) {
								$update = false;
							}
						}
						
						if($update && !in_array($v, $tab_uri)) {
							$tab_uri[] = $v;
						}
					}
				}
				
				foreach($tab_uri as $k=>$uri) {
					file_get_contents(
						sprintf(
							$apercite_uri_update,
							$apercite_arr['apercite_login'],
							$apercite_arr['apercite_api_key'],
							$javascript,
							$java,
							$uri
						)
					);
				}
				
				return true;
			}
		}
	}
}


if(!function_exists('apercite_meta_box_add')) {
	function apercite_meta_box_add() {
		// Check whether the 2.5 function add_meta_box exists before adding the meta box
		if(function_exists('add_meta_box')) {
			add_meta_box('apercite_post','Apercite','apercite_post','post','side','high');
			add_meta_box('apercite_post','Apercite','apercite_post','page', 'side', 'high');
		}
	}
}

if(!function_exists('apercite_post')) {
	function apercite_post() {
		$apercite_arr = get_option('apercite_params');

                if(!empty($apercite_arr['apercite_login']) && !empty($apercite_arr['apercite_api_key'])) {
?>
	<label><input type="checkbox" name="apercite_update" value="1" /> Update thumbnails</label>
<?php
		}
		else {
?>
	<label>To update thumbnails, you must specify your login and API Key. (<a href="options-general.php?page=apercite/apercite.php">Apercite settings</a>)</label>
<?php
		}
	}
}

if(!function_exists('apercite_menu')) {
	function apercite_menu() {
		add_options_page('Apercite Options', 'Apercite', 8, __FILE__, 'apercite_options');
	}
}

if(!function_exists('apercite_update_options')) {
	function apercite_update_options() {
		global $apercite_sizes;
		
		check_admin_referer('apercite_check');
		
		$apercite_arr['apercite_local_link'] = isset($_POST['apercite_local_link']) && $_POST['apercite_local_link'] == 1 ? true : false;
		$apercite_arr['apercite_javascript'] = isset($_POST['apercite_javascript']) && $_POST['apercite_javascript'] == 1 ? true : false;
		$apercite_arr['apercite_java'] = isset($_POST['apercite_java']) && $_POST['apercite_java'] == 1 ? true : false;
		$apercite_arr['apercite_ssl'] = isset($_POST['apercite_ssl']) && $_POST['apercite_ssl'] == 1 ? true : false;
		
		$apercite_arr['apercite_size'] = esc_attr($_POST['apercite_size']);
		
		if(isset($_POST['apercite_workers']) && is_array($_POST['apercite_workers'])) {
			$workers = array();
			foreach($_POST['apercite_workers'] as $k=>$v) {
				$v = trim($v);
				if(!empty($v)) {
					$workers[] = stripslashes($v);
				}
			}
		}
		else {
			$workers = array();
		}
		$apercite_arr['apercite_workers'] = serialize($workers);
		
		$apercite_arr['apercite_login'] = stripslashes(trim($_POST['apercite_login']));
		$apercite_arr['apercite_api_key'] = stripslashes(trim($_POST['apercite_api_key']));
		
		update_option('apercite_params', $apercite_arr);
		
		return $apercite_arr;
	}
}

if(!function_exists('apercite_options')) {
	function apercite_options() {
		global $apercite_sizes;
		
		if(isset($_POST['update_apercite_options']) && $_POST['update_apercite_options']) {
			$apercite_arr = apercite_update_options();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. 'Settings saved.'
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}
		else {
			$apercite_arr = get_option('apercite_params');
		}
		
		echo '<div class="wrap">';
		echo '<h2>' . __('Apercite Settings') . '</h2>';
		echo 'This page lets you configure the plugin Apercite.'.nl2br("\n\n");
		echo '<form method="post" action="" enctype="multipart/form-data">';
		
		if(function_exists('wp_nonce_field')) {
			wp_nonce_field('apercite_check');
		}
		
		echo '<input type="hidden" name="update_apercite_options" value="1">';
?>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="apercite_local_link">General Settings</lablel></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span>General Settings</span></legend>
					<label><input type="checkbox" value="1" name="apercite_local_link"<?php echo $apercite_arr['apercite_local_link'] ? ' checked="checked"' : ''; ?> /> Display thumbnails of internal links</label><br />
					<label><input type="checkbox" value="1" name="apercite_javascript"<?php echo $apercite_arr['apercite_javascript'] ? ' checked="checked"' : ''; ?> /> Enable Javascript on thumbnails</label><br />
					<label><input type="checkbox" value="1" name="apercite_java"<?php echo $apercite_arr['apercite_java'] ? ' checked="checked"' : ''; ?> /> Enable Java on thumbnails</label><br />
					<label><input type="checkbox" value="1" name="apercite_ssl"<?php echo (isset($apercite_arr['apercite_ssl']) && $apercite_arr['apercite_ssl'] ? ' checked="checked"' : ''); ?> /> Call thumbnail with SSL</label><br />
					<label>Size of thumbnails : <?php echo combo_form('apercite_size', $apercite_sizes, $apercite_arr['apercite_size']); ?></label>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="apercite_workers_new">CSS attributes needed to display the thumbnails</lablel></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo __('Eléments CSS affichant une miniature'); ?></span></legend>
					<?php foreach(unserialize($apercite_arr['apercite_workers']) as $k=>$v) { ?>
						<label><input type="text" value="<?php echo esc_attr($v); ?>" name="apercite_workers[]" /></label><br />
					<?php } ?>
					<label><input id="apercite_workers_new" type="text" value="" name="apercite_workers[]" /></label>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>

<h3>Subscription</h3>
<p>If you have an Apercite subsciption, you can update the thumbnails from the article edition interface.<br />
<br />
I you didn't subscribed to the Apercite service yet, you can <a href="https://www.apercite.fr/abonnements.html" title="Subscribe now">subscribe now</a>.</p>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="apercite_login">Login</label></th>
			<td>
			 	<input class="regular-text" type="text" name="apercite_login" value="<?php echo esc_attr($apercite_arr['apercite_login']); ?>">
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="mailserver_url">API Key</label></th>
			<td>
			 	<input class="regular-text" type="text" name="apercite_api_key" value="<?php echo esc_attr($apercite_arr['apercite_api_key']); ?>">
			</td>
		</tr>
	</tbody>
</table>
<?php
		echo '<p class="submit">'
		. '<input class="button-primary" type="submit"'
			. ' value="' . 'Save Changes' . '"'
			. ' />'
		. '</p></form>';
	}
}

if(!function_exists('combo_form')) {
	function combo_form($name, $options, $selected) {
		$rtn = '<select name="'.esc_attr($name).'">';
		
		foreach($options as $k=>$option) {
			$rtn .= '<optgroup label="'.esc_attr($k).'">';
			
			$rtn .= options_form($option, $selected);
			
			$rtn .= '</optgroup>';
		}
		
		return $rtn.'</select>';
	}
}

if(!function_exists('options_form')) {
	function options_form($options, $default) {
		$rtn = '';
		
		foreach($options as $k=>$option) {
			$rtn .= '<option value="';
			$rtn .= esc_attr($k).'"';
			
			if($k == $default) {
				$rtn .= ' selected="selected"';
			}
			
			$rtn .= '>'.esc_attr($option).'</option>';
		}
		
		return $rtn;
	}
}


// Install Plugin
register_activation_hook(__FILE__,'apercite_install');

if(!function_exists('apercite_install')) {
	function apercite_install() {
		$apercite_arr = get_option('apercite_params');
		
		if(!is_array($apercite_arr)) {
			$apercite_arr = array(
				'apercite_local_link' => true,
				'apercite_javascript' => true,
				'apercite_java' => true,
				'apercite_ssl' => false,
				
				'apercite_size' => esc_attr('120x90'),
				
				'apercite_workers' => serialize(array(
					'.storycontent',
					'.entry',
					'.content',
					'.topContent'
				)),
				
				'apercite_login' => '',
				'apercite_api_key' => ''
			);
		}
		else {
			if(!array_key_exists('apercite_local_link', $apercite_arr)) {
				$apercite_arr['apercite_local_link'] = true;
			}
			
			if(!array_key_exists('apercite_javascript', $apercite_arr)) {
				$apercite_arr['apercite_javascript'] = true;
			}
			
			if(!array_key_exists('apercite_java', $apercite_arr)) {
				$apercite_arr['apercite_java'] = true;
			}
			
			if(!array_key_exists('apercite_ssl', $apercite_arr)) {
				$apercite_arr['apercite_ssl'] = false;
			}
			
			if(!array_key_exists('apercite_size', $apercite_arr)) {
				$apercite_arr['apercite_size'] = '120x90';
			}
			
			if(!array_key_exists('apercite_workers', $apercite_arr)) {
				$apercite_arr['apercite_workers'] = serialize(array(
					'.storycontent',
					'.entry',
					'.content',
					'.topContent'
				));
			}
			
			if(!array_key_exists('apercite_login', $apercite_arr)) {
				$apercite_arr['apercite_login'] = '';
			}
			
			if(!array_key_exists('apercite_api_key', $apercite_arr)) {
				$apercite_arr['apercite_api_key'] = '';
			}
		}
		
		update_option('apercite_params', $apercite_arr);
	}
}
