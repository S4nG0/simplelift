<?php
/*
Plugin Name: WP Super Popup
Plugin Script: wp-super-popup.php
Plugin URI: http://wppluginspro.com/wp-super-popup-pro/
Description: Creates unblockable, dynamic and fully configurable popups for your blog. It works also if WP Super Cache or W3 Total Cache is enabled!
Version: 1.1.2
License: GPL
Author: WP Plugins Pro
Author URI: http://wppluginspro.com

*/

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
Online: http://www.gnu.org/licenses/gpl.txt
*/
require_once('mobile_ids.php');
$smp_default_options = array(
'exclusion_list'=>'',
'popup_url' => '',
'cookie_duration'=>'30',
'cookie_num_visits'=>'3',
'popup_height'=>'300',
'popup_width'=>'350',
'popup_opacity'=>'0.5',
'popup_delay'=>'2000',
'popup_speed'=>'700',
'popup_content' => '',
'popup_plain_content' => '',
'page_content_id' => '',
'show_mode'=>'1',
'load_mode'=>'1',
'messages'=>'',
'enabled' => 0,
'cookie_id' => 'mycookie',
'list_mode' => 3,
'overlay_close' => 'true',
'show_mobile' => 0
);

add_option('smp-options',$smp_default_options);

$smp_plugin_url_base = plugin_dir_url( __FILE__ );

$smp_uploads_dir = wp_upload_dir();
$smp_uploads_basedir = $smp_uploads_dir['basedir'];
$smp_uploads_baseurl = $smp_uploads_dir['baseurl'];

$smp_inline_popup_url = $smp_uploads_baseurl . '/smp_popup.html';
$smp_inline_popup_temp_url = $smp_uploads_baseurl . '/smp_popup_temp.html';
$smp_inline_popup_file = $smp_uploads_basedir . '/smp_popup.html';
$smp_inline_popup_temp_file = $smp_uploads_basedir . '/smp_popup_temp.html';

$smp_plain_popup_url = $smp_uploads_baseurl . '/smp_plain_popup.html';
$smp_plain_popup_temp_url = $smp_uploads_baseurl . '/smp_plain_popup_temp.html';
$smp_plain_popup_file = $smp_uploads_basedir . '/smp_plain_popup.html';
$smp_plain_popup_temp_file = $smp_uploads_basedir . '/smp_plain_popup_temp.html';


if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );


add_action('admin_menu', 'smp_create_menu');
add_action( 'init', 'smp_init' );

register_activation_hook(__FILE__, 'smp_install');
function smp_install(){
	$options = get_option('smp-options');
	$options['enabled']=0;
	update_option('smp-options', $options);
}

register_deactivation_hook(__FILE__, 'smp_uninstall');
function smp_uninstall(){
	$options = get_option('smp-options');
	$options['enabled']=0;
	update_option('smp-options', $options);
}

function smp_init(){
	global $smp_inline_popup_temp_file, $smp_plain_popup_temp_file, $smp_default_options;
	$options = get_option('smp-options');
	if (count($options) != count($smp_default_options)){
		$merged_options = array_merge((array)$smp_default_options, (array)$options);
		update_option('smp-options', $merged_options);
	}
	if (isset($_POST['smp_content'])){
		if(current_user_can("administrator")) {
			$content = stripslashes($_POST['smp_content']);
			
			if (smp_is_file_writable($smp_inline_popup_temp_file))
				smp_write_file_popup($smp_inline_popup_temp_file, $content);
		}
		die();
	}
	if (isset($_POST['smp_plain_content'])){
		if(current_user_can("administrator")) {
			$content = stripslashes($_POST['smp_plain_content']);
			
			if (smp_is_file_writable($smp_plain_popup_temp_file))
				smp_write_file_popup($smp_plain_popup_temp_file, $content);
		}
		die();
	}
	
	if (isset($_GET['smp_page_content_id'])) {
		$temp_query = new WP_Query(array('page_id' => intval($_GET['smp_page_content_id'])));
		if ($temp_query->have_posts()) {
			$temp_query->the_post();
			global $more;    // Declare global $more (before the loop).
			$more = 1; 
			die( get_the_content());
		}
	}
	
	if (smp_is_page_allowed()){
		add_action('wp_print_styles', 'smp_add_styles');
		add_action('wp_enqueue_scripts', 'smp_add_js');
		//add_action('wp_enqueue_scripts', 'smp_add_head_code');
	}
	
}

function smp_callback($buffer){
	$temp_query = new WP_Query(array('page_id' => intval($_GET['smp_page_content_id'])));
	if ($temp_query->have_posts()) {
		$temp_query->the_post();
		global $more;    // Declare global $more (before the loop).
		$more = 1; 
		die( get_the_content());
	}
	
}
function smp_string_begins_with($string, $search)
{
    return (strncmp($string, $search, strlen($search)) == 0);
}

function smp_get_full_url() {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

function smp_is_mobile(){
	$mobile_agents = 'android|iphone|iemobile|up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|ipod|xoom|blackberry';
	return preg_match('#('.$mobile_agents.')#i', $_SERVER['HTTP_USER_AGENT']);
}

function smp_is_page_allowed(){
	$options = get_option('smp-options');
	if ($options['show_mobile']==0 && smp_is_mobile()) {
		return false;
	}
	$res = true;
	$full_url = smp_get_full_url();
	if ($options['enabled']==0) {
		$res = false;
	} else {
		$popup_list_mode = $options['list_mode'];
		if ($popup_list_mode != 3){
			$paths = explode("\n", $options['exclusion_list']);
			$found = false;
			foreach($paths as $path){
				$path = trim($path);
				if (strcmp($path, $_SERVER["REQUEST_URI"]) == 0 || strcmp($path, $full_url) == 0){
					if ($popup_list_mode == 1) {
						$res = false;//exclusion
						$found = true;
						break;
					}
					else if($popup_list_mode == 2) {
						$res = true;//inclusion
						$found = true;
						break;
					}
				}  
			}
			if (!$found){
				if ($popup_list_mode == 1) $res = true;
				else if($popup_list_mode == 2) $res = false;
			}
		}
	}
	return $res;
}


function smp_add_js(){
	global $smp_plugin_url_base, $smp_inline_popup_url,$smp_plain_popup_url,$smp_page_popup_url;
	$options = get_option('smp-options');
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script('smp_colorbox',	$smp_plugin_url_base . '/jquery.colorbox-min.js', array('jquery'), mt_rand() );
	wp_enqueue_script('smp_cookie',	$smp_plugin_url_base . '/jquery.utils-min.js',	array('jquery'), mt_rand() );
	wp_enqueue_script( 'smp-script', $smp_plugin_url_base . '/wp-super-popup.js', array( 'jquery' ), rand() );
	
	$options = get_option('smp-options');
	if ($options['load_mode'] == 1){
		$smp_popup_url = $options['popup_url'];
	} else if ($options['load_mode'] == 2){
		$smp_popup_url = $smp_inline_popup_url;
	} else if ($options['load_mode'] == 3) {
		$smp_popup_url = $smp_plain_popup_url;
	} else {
		$smp_popup_url = '/?smp_page_content_id=' . $options['page_content_id'];
	}
	wp_localize_script( 'smp-script', 'smp_vars', array(
	'cookie_id' => $options['cookie_id'],
	'cookie_num_visits' => $options['cookie_num_visits'],
	'show_mode' => $options['show_mode'],
	'popup_url' => $options['popup_url'],
	'load_mode' => $options['load_mode'],
	'page_content_id' => $options['page_content_id'],
	'popup_width' => $options['popup_width'],
	'popup_height' => $options['popup_height'],
	'popup_opacity' => $options['popup_opacity'],
	'popup_speed' => $options['popup_speed'],
	'overlay_close' => $options['overlay_close'],
	'popup_url' => $smp_popup_url,
	'popup_delay' => $options['popup_delay'],
	'cookie_duration' => $options['cookie_duration'],
	'ajaxurl' 	=> admin_url( 'admin-ajax.php' )
	));
}


/*
============================================
ADMIN
============================================
*/

function smp_plugin_actions( $links, $file ) {
	if( $file == 'wp-super-popup/wp-super-popup.php' && function_exists( "admin_url" ) ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wp-super-popup/wp-super-popup.php' ) . '">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}
add_filter( 'plugin_action_links', 'smp_plugin_actions', 10, 2 );

function smp_admin_notice() {
	if( substr( $_SERVER["PHP_SELF"], -11 ) == 'plugins.php' && function_exists( "admin_url" ) )
		echo '<div class="error"><p><strong>' . sprintf( 'WP Super Popup is disabled. Please go to the <a href="%s">plugin admin page</a> to enable it.', 
				admin_url( 'admin.php?page=wp-super-popup/wp-super-popup.php' ) ) . '</strong></p></div>';
}
add_action( 'admin_notices', 'smp_admin_notice' );

function smp_create_menu() {

	//create new top-level menu
	$page = add_menu_page('Super Popup', 'Super Popup', 'administrator', __FILE__, 'smp_settings_page',plugins_url('/images/popup_icon.gif', __FILE__));

	add_action('admin_print_scripts-'.$page, 'smp_add_js_admin');
	add_action('admin_print_styles-'.$page, 'smp_add_styles');
	//call register settings function
	add_action( 'admin_init', 'smp_register_mysettings' ); 
	add_filter('admin_head-'.$page, 'smp_add_admin_head_code');

}

function smp_add_styles(){
	global $smp_plugin_url_base;
	wp_enqueue_style('smp_style',$smp_plugin_url_base . '/colorbox.css', array(), mt_rand(), 'all' );
}

function smp_add_js_admin(){
	global $smp_plugin_url_base;
	$options = get_option('smp-options'); 
    wp_enqueue_script( 'jquery' );
	
	wp_enqueue_script('smp_colorbox',	$smp_plugin_url_base . '/jquery.colorbox-min.js', array('jquery'), mt_rand() );
	wp_enqueue_script('smp_cookie',	$smp_plugin_url_base . '/jquery.utils-min.js',	array('jquery'), mt_rand() );
	wp_enqueue_script('smp_tiny_mce',	$smp_plugin_url_base . '/tiny_mce/tiny_mce.js',	array(), mt_rand() );
	wp_enqueue_script('smp_admin',	$smp_plugin_url_base . '/admin.js', array('smp_tiny_mce'), mt_rand() );
}

function smp_add_admin_head_code() {
    global $smp_plugin_url_base,$smp_inline_popup_temp_url,$smp_plain_popup_temp_url;
    $options = get_option('smp-options'); 

?>

<script type="text/javascript">
	jQuery.noConflict();
	jQuery(document).ready(function($) {
		$(document).ready(function(){
			
			$("input[rel='reset']").click(function(){
				if (confirm('Are you sure you want to save the options and fully reset the popup cookies already stored on all the browsers?')){
					var id = new Date().getTime();
					$("input[name='smp-options[cookie_id]']").val(id);
					$('#target').submit();
				}
			});
	
			$("input[rel='preview']").click(function(){
				var purl;
				var checkedLoadMode = $("input[name='smp-options[load_mode]']:checked").val();
				
				if (checkedLoadMode == 1){
					purl=$("input[name='smp-options[popup_url]']").val();
				}else if (checkedLoadMode == 2){
					purl='<?php echo($smp_inline_popup_temp_url)?>';
					var content = tinyMCE.get('popup_content').getContent();				
					$.post("/",{smp_content: content});
				} else if (checkedLoadMode == 3) {
					purl='<?php echo($smp_plain_popup_temp_url)?>';
					var content = $("textarea[name='smp-options[popup_plain_content]']").val();
					$.post("/",{smp_plain_content: content});
				} else {
					purl='/?smp_page_content_id=' + $("select[name='smp-options[page_content_id]']").val();
				}				
				
				var oc = $("input[name='smp-options[overlay_close]']:checked").val() == 'true';
				setTimeout(
					function() { $.colorbox({
						fixed:true,
						width:$("input[name='smp-options[popup_width]']").val()+"px", 
						height:$("input[name='smp-options[popup_height]']").val()+"px", 
						iframe:true, 
						opacity:$("input[name='smp-options[popup_opacity]']").val(), 
						speed:$("input[name='smp-options[popup_speed]']").val(), 
						overlayClose:oc,
						href:purl
					})},
					$("input[name='smp-options[popup_delay]']").val()
				);					
			});
		});
	})
</script>

<?php
}

function smp_register_mysettings() {
	//register our settings
	register_setting( 'smp-settings-group', 'smp-options', 'smp_options_validate' );
}

function smp_write_file_popup($file_name, $content){
	global $smp_plugin_url_base;
	$handle = fopen($file_name,"w");
	fwrite($handle, '<html><head><link type="text/css" media="screen" rel="stylesheet" href="' . $smp_plugin_url_base . '/tiny_mce/themes/advanced/skins/default/content.css" /></head><body>'.$content.'</body></html>');
	fclose($handle);
}

function smp_write_file_plain_popup($file_name, $content){
	global $smp_plugin_url_base;
	$handle = fopen($file_name,"w");
	fwrite($handle, $content);
	fclose($handle);
}

function smp_options_validate($options) {
	global $smp_inline_popup_file,$smp_plain_popup_file;
	$prev_options = get_option('smp-options'); 

	$options['messages'] = '';	
	$messages = '';
	
	if ($options['load_mode']==2){
		if (smp_is_file_writable($smp_inline_popup_file)){
			smp_write_file_popup($smp_inline_popup_file, $options['popup_content']);
		}else {
			$messages .= "Unable to save inline content. Please make sure that the file '$smp_inline_popup_file' is writable and try again.<br/>";
		}
	} elseif ($options['load_mode']==3){
		if (smp_is_file_writable($smp_plain_popup_file)){
			smp_write_file_plain_popup($smp_plain_popup_file, $options['popup_plain_content']);
		}else {
			$messages .= "Unable to save inline content. Please make sure that the file '$smp_plain_popup_file' is writable and try again.<br/>";
		}
	} 
	
	if (!is_numeric($options['popup_height'])){
		$messages .= "The field 'Popup Height' requires a numeric value.<br/>";
		$options['popup_height'] = $prev_options['popup_height'];
	}
	if (!is_numeric($options['popup_width'])){
		$messages .= "The field 'Popup Width' requires a numeric value.<br/>";
		$options['popup_width'] = $prev_options['popup_width'];
	}
	if (!is_numeric($options['popup_delay'])){
		$messages .= "The field 'Popup Delay' requires a numeric value.<br/>";
		$options['popup_delay'] = $prev_options['popup_delay'];
	}
	if (!is_numeric($options['popup_speed'])){
		$messages .= "The field 'Popup Speed' requires a numeric value.<br/>";
		$options['popup_speed'] = $prev_options['popup_speed'];
	}
	if (!preg_match('/[0-9\.]/',$options['popup_opacity'])){
		$messages .= "The field 'Popup Opacity' requires a value between 0 and 1 (using a '.' as separator)<br/>";
		$options['popup_opacity'] = $prev_options['popup_opacity'];
	}
	if (!isset($options['enabled'])){
		$options['enabled'] = 0;
	}
	if (!isset($options['show_backlink'])){
		$options['show_backlink'] = 0;
	}
	if (strlen($messages) > 0){
		$options['messages'] = $messages;
	}
	return $options;
}

function smp_is_file_writable($filename) {
	if(!is_writable($filename)) {
		if(!@chmod($filename, 0666)) {
			$pathtofilename = dirname($filename);
			if(!is_writable($pathtofilename)) {
				if(!@chmod($pathtoffilename, 0666)) {
					return false;
				}
			}
		}
	}
	return true;
}

function smp_settings_page() {
	$options = get_option('smp-options');
	global $smp_plugin_url_base;
?>
<div class="wrap">
	<h2>WP Super Popup</h2>
		
	<div style="width: 832px;">
		<div style="float: left; background-color: white; padding: 10px; margin-right: 15px; border: 1px solid rgb(221, 221, 221);">
		<div style="width: 350px; height: 80px;">
		<em>Need support and more advanced features like visitor email auto-load, sliding popups, <strong>exit popups</strong> and much more? <br/>Check now <strong><a href="http://wppluginspro.com?utm_source=superpopup&utm_medium=pluginadmin&utm_campaign=products">WP Super Popup PRO</a>!</strong></em>
		</div>
		</div>
		
		<div style="float: left; background-color: white; padding: 10px; border: 1px solid rgb(221, 221, 221);">
		<div style="width: 350px; height: 80px;">
		<em>Interested in boosting traffic and sales by sending Push Notifications to your visitors browsers? <br/>Check now <strong><a href="http://www.pushdominator.com?utm_source=superpopup&utm_medium=pluginadmin&utm_campaign=products">Push Dominator</a>!</strong></em>
		</div>
		</div>
	</div>
	
	<div style="clear:both;"></div>
  <?php      
  if (strlen($options['messages']) > 0){
  echo '<div class="error fade" style="background-color:red;"><p>' . $options['messages'] .'</p></div>';
  }?>

<form id="target" method="post" action="options.php">
    <?php settings_fields( 'smp-settings-group' ); ?>
    <input type="hidden" name="smp-options[cookie_id]" value="<?php echo $options['cookie_id']; ?>" /></td>
		

		<h2>Base Settings</h2>
    <table class="form-table">
        <tr valign="top"><th scope="row"><strong>Status:</strong></th>
           <td><input type="checkbox" <?php echo($options['enabled']==1?'checked':'')?> name="smp-options[enabled]" value="1"> Popup enabled </td>
        </tr>
        <tr valign="top"><th scope="row"><strong>Enable on mobile browsers:</strong></th>
           <td>
           <input type="radio" <?php echo($options['show_mobile']==1?'checked':'')?> name="smp-options[show_mobile]" value="1">Yes
           <input type="radio" <?php echo($options['show_mobile']==0?'checked':'')?> name="smp-options[show_mobile]" value="0">No
           </td>
        </tr>
        <!-- 
        <tr valign="top"><th scope="row"><strong>Backlink on popup footer:</strong></th>
           <td><input type="checkbox" <?php echo($options['show_backlink']==1?'checked':'')?> name="smp-options[show_backlink]" value="1">Show</td>
        </tr>-->
        <tr valign="top"><th scope="row"><strong>Paths inclusion/exclusion</strong>: type the paths (one for each line).</th>
           <td>
        		<input type="radio" <?php echo($options['list_mode']==3?'checked':'')?> name="smp-options[list_mode]" value="3"> Show the popup on all the pages  <br/>
        		<input type="radio" <?php echo($options['list_mode']==1?'checked':'')?> name="smp-options[list_mode]" value="1"> Don't show the popup for the following paths <br/>
        		<input type="radio" <?php echo($options['list_mode']==2?'checked':'')?> name="smp-options[list_mode]" value="2"> Show the popup only for the following paths  <br/>
           	<textarea name="smp-options[exclusion_list]" rows=10 cols=40><?php echo ($options['exclusion_list'])?></textarea> </td>
        </tr>
        <tr valign="top">
        	<th scope="row"><strong>Show the popup:</strong></th>
        	<td>
        		<input type="radio" <?php echo($options['show_mode']==1?'checked':'')?> name="smp-options[show_mode]" value="1"> Every <input size="5" type="text" name="smp-options[cookie_duration]" value="<?php echo $options['cookie_duration']; ?>" /> days<br/>
        		<input type="radio" <?php echo($options['show_mode']==2?'checked':'')?> name="smp-options[show_mode]" value="2"> For the first <input size="5" type="text" name="smp-options[cookie_num_visits]" value="<?php echo $options['cookie_num_visits']; ?>" /> visits
        	</td>
        </tr>          
       
               
    </table> 
    
		<h2>Popup Content</h2>	

    <table class="form-table">
        <tr valign="top">
        	<th scope="row"><strong>Popup content load mode:</strong><br/>
        		<p class="submit">
					  <input type="button" rel="preview" class="button-primary" value="<?php _e('Live Preview') ?>" />
					  </p>
        		</th>
          <td>
          	<input type="radio" <?php echo($options['load_mode']==1?'checked':'')?> name="smp-options[load_mode]" value="1"> Embed the following URL content:<br/>
          	<input size="70" type="text" name="smp-options[popup_url]" value="<?php echo $options['popup_url']; ?>" />
					  <br/><br/>
          	<input type="radio" <?php echo($options['load_mode']==2?'checked':'')?> name="smp-options[load_mode]" value="2"> Embed the following WYSIWYG content:<br/>
          	<textarea id="popup_content" rows=15 cols=60 name="smp-options[popup_content]"><?php echo $options['popup_content']; ?></textarea>
          	<br/><a href="javascript:smp_toggleEditor('popup_content');">Add/Remove editor</a>
					  <br/><br/>
          	<input type="radio" <?php echo($options['load_mode']==3?'checked':'')?> name="smp-options[load_mode]" value="3"> Embed the following plain HTML content:<br/>
          	<textarea id="popup_plain_content" rows=15 cols=60 name="smp-options[popup_plain_content]"><?php echo $options['popup_plain_content']; ?></textarea>
						<br/><br/>
			<input type="radio" <?php echo($options['load_mode']==4?'checked':''); ?> name="smp-options[load_mode]" value="4"> Embed the following page content (stylesheets will not be preserved):<br/>
				<?php wp_dropdown_pages(array('show_option_none' => 'Select Page', 'selected' => $options['page_content_id'], 'name' => "smp-options[page_content_id]")); ?>          	


          </td>

        </tr>
        
    </table>
    

		<h2>Visual Effects Settings</h2>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><strong>Background Opacity</strong> (between 0 and 1):</th>
        <td><input size="5" type="text" name="smp-options[popup_opacity]" value="<?php echo $options['popup_opacity']; ?>" /></td>
        </tr>         
        <tr valign="top">
        <th scope="row"><strong>Popup Height</strong>:</th>
        <td><input size="5" type="text" name="smp-options[popup_height]" value="<?php echo $options['popup_height']; ?>" />px</td>
        </tr>         
        <tr valign="top">
        <th scope="row"><strong>Popup Width</strong>:</th>
        <td><input size="5" type="text" name="smp-options[popup_width]" value="<?php echo $options['popup_width']; ?>" />px</td>
        </tr>         
        <tr valign="top">
        <th scope="row"><strong>Popup Delay</strong>:</th>
        <td><input size="5" type="text" name="smp-options[popup_delay]" value="<?php echo $options['popup_delay']; ?>" />ms</td>
        </tr>         
        <tr valign="top">
        <th scope="row"><strong>Popup Speed</strong>:</th>
        <td><input size="5" type="text" name="smp-options[popup_speed]" value="<?php echo $options['popup_speed']; ?>" />ms</td>
        </tr>         
        <tr valign="top">
        <th scope="row"><strong>Close Popup when clicking on the background</strong>:</th>
        <td><input type="radio" <?php echo($options['overlay_close']=='true'?'checked':'')?> name="smp-options[overlay_close]" value="true">Yes
        	<input type="radio" <?php echo($options['overlay_close']=='false'?'checked':'')?> name="smp-options[overlay_close]" value="false">No
        </td>
        </tr>         
    </table>

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	  <input type="button" rel="reset" class="button-primary" value="<?php _e('Save Changes and Reset Cookies') ?>" />
    </p>

</form>
</div>
<?php } 

function smp_debug($msg) {
    $today = date("Y-m-d H:i:s ");
    $myFile = dirname(__file__) . "/debug.log";
    $fh = fopen($myFile, 'a') or die("Can't open debug file. Please manually create the 'debug.log' file ");
    $ua_simple = preg_replace("/(.*)\s\(.*/","\\1",$_SERVER['HTTP_USER_AGENT']);
    fwrite($fh, $today . " [from: ".$_SERVER['REMOTE_ADDR']."|$ua_simple] - " . $msg . "\n");
    fclose($fh);
}

?>