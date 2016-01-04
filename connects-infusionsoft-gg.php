<?php
/**
* Plugin Name: ConvertPlug Infusionsoft Addon (Geek Goddess version)
* Plugin URI: 
* Description: Use this plugin to integrate Infusionsoft with ConvertPlug Connects. This version integrates using Web Forms rather than tags.
* Version: 1.0
* Author: Jaime Lerner
* Author URI: https://www.geekgoddess.com/
* License: GPL2
*/

if(!class_exists('Smile_Mailer_Infusionsoft')){
	class Smile_Mailer_Infusionsoft{

    private $slug;
    private $setting;

		function __construct(){
			require_once( 'infusionsoft/isdk.php' );
			add_action( 'wp_ajax_get_infusionsoft_data', array($this,'get_infusionsoft_data' ));
			add_action( 'wp_ajax_update_infusionsoft_authentication', array($this,'update_infusionsoft_authentication' ));
			add_action( 'wp_ajax_disconnect_infusionsoft', array($this,'disconnect_infusionsoft' ));
			add_action( 'wp_ajax_infusionsoft_add_subscriber', array($this,'infusionsoft_add_subscriber' ));
			add_action( 'wp_ajax_nopriv_infusionsoft_add_subscriber', array($this,'infusionsoft_add_subscriber' ));
			add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );
			$this->setting  = array(
				'name' => 'Infusionsoft',
				'parameters' => array( 'app', 'api_key' ),
				'where_to_find_url' => 'http://ug.infusionsoft.com/article/AA-00442/0/Infusionsoft-API-Key.html',
				'logo_url' => plugins_url('images/logo.png', __FILE__)
			);
			$this->slug = 'infusionsoft';
		}

		/*
		 * Function Name: enqueue_scripts
		 * Function Description: Add custon scripts
		 */
		
		function enqueue_scripts() {
			if( function_exists( 'cp_register_addon' ) ) {
				cp_register_addon( $this->slug, $this->setting );
			}
			wp_register_script( $this->slug.'-script', plugins_url('js/'.$this->slug.'-script.js', __FILE__), array('jquery'), '1.1', true );
			wp_enqueue_script( $this->slug.'-script' );
			add_action( 'admin_head', array( $this, 'hook_css' ) );
		}


		/*
		 * Function Name: hook_css
		 * Function Description: Adds background style script for mailer logo.
		 */


		function hook_css() {
			if( isset( $this->setting['logo_url'] ) ) {
				if( $this->setting['logo_url'] != '' ) {
					$style = '<style>table.bsf-connect-optins td.column-provider.'.$this->slug.'::after {background-image: url("'.$this->setting['logo_url'].'");}.bend-heading-section.bsf-connect-list-header .bend-head-logo.'.$this->slug.'::before {background-image: url("'.$this->setting['logo_url'].'");}</style>';
					echo $style;
				}
			}
		}


		/*
		 * Function Name: get_infusionsoft_data
		 * Function Description: Get Infusionsoft input fields
		 */
		 
		function get_infusionsoft_data() {
			$isKeyChanged = false;

			$connected = false;
			ob_start();

			$infusionsoft_api = get_option($this->slug.'_api_key');
			$infusionsoft_app = get_option($this->slug.'_app');

            if( $infusionsoft_api != '' ) {
            	try {
					$myApp = new iSDK;
					$res = $myApp->cfgCon( $infusionsoft_app, $infusionsoft_api, "on" );
					$campaigns = $myApp->getWebFormMap();
					$formstyle = 'style="display:none;"';
				} catch( iSDKException $ex ) {
					$formstyle = '';
					$isKeyChanged = true;    
				}	            	
            } else {
            	$formstyle = '';
            }
            ?>

	        <div class="bsf-cnlist-form-row" <?php echo $formstyle; ?>>
            <label for="cp-list-name" ><?php _e( $this->setting['name'] . " App Name", "smile" ); ?></label>
            	<input type="text" autocomplete="off" id="<?php echo $this->slug; ?>_app" name="<?php echo $this->slug; ?>_app" value="<?php echo esc_attr( $infusionsoft_app ); ?>"/>
	        </div>
          <div class="bsf-cnlist-form-row" <?php echo $formstyle; ?>>
            <label for="cp-list-name" ><?php _e( $this->setting['name'] . " API Key", "smile" ); ?></label>
            	<input type="text" autocomplete="off" id="<?php echo $this->slug; ?>_api_key" name="<?php echo $this->slug; ?>_api_key" value="<?php echo esc_attr( $infusionsoft_api ); ?>"/>
	        </div>

            <div class="bsf-cnlist-form-row <?php echo $this->slug; ?>-list">
					<span class="spinner" style="float: none;"></span>
	            <?php
	            $infusionsoft_lists = $this->get_infusionsoft_lists( $infusionsoft_api, $infusionsoft_app );

				if( !empty( $infusionsoft_lists ) ){
					$connected = true;
				?>
				<label for="<?php echo $this->slug; ?>-list"><?php echo __( "Select Web Form (email only or name/email only)", "smile" ); ?></label>
				<select id="<?php echo $this->slug; ?>-list" class="bsf-cnlist-select" name="<?php echo $this->slug; ?>-list">
				<?php
					foreach($infusionsoft_lists as $id => $name) {
				?>
					<option value="<?php echo $id; ?>"><?php echo $name; ?></option>
				<?php
					}
				?>
				</select>
				<?php
				}
	            ?>
            </div>

            <div class="bsf-cnlist-form-row">
            	<?php if( $infusionsoft_api == "" ) { ?>
	            	<button id="auth-<?php echo $this->slug; ?>" class="button button-secondary auth-button" disabled><?php _e( "Authenticate " . $this->setting['name'], "smile" ); ?></button><span class="spinner" style="float: none;"></span>
	            <?php } else {
	            		if( $isKeyChanged ) {
	            ?>
	            	<div id="update-<?php echo $this->slug; ?>" class="update-mailer" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>"><span><?php _e( "Your credentials seem to have changed.</br>Update " . $this->setting['name'] . " credentials?", "smile" ); ?></span></div><span class="spinner" style="float: none;"></span>
	            <?php
	            		} else {
	            ?>
					<div id="disconnect-<?php echo $this->slug; ?>" class="" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>">
						<span>
							[ <?php _e( "Change ".$this->slug." account", "smile" ); ?> ]
						</span>
					</div>
	            <?php
	            		}
	            ?>
	            <?php } ?>
	        </div>

            <?php
            $content = ob_get_clean();

            $result['data'] = $content;
            $result['helplink'] = $this->setting['where_to_find_url'];
            $result['isconnected'] = $connected;
            echo json_encode($result);
            exit();
        }

		
		/*
		 * Function Name: infusionsoft_add_subscriber
		 * Function Description: Add subscriber
		 */
		
		function infusionsoft_add_subscriber() {
			$post = $_POST;
			$data = array();
			$email = isset( $post['email'] ) ? $post['email'] : '';
			$name = isset( $post['name'] ) ? $post['name'] : '';
      $email=sanitize_email($email);
      $name=sanitize_text_field($name);
			$only_conversion = isset( $post['only_conversion'] ) ? true : false;
			$infusionsoft_api = get_option( $this->slug . '_api_key' );
			$infusionsoft_app = get_option( $this->slug . '_app' );
			$infusionsoft_list_id = sanitize_text_field($post['list_id']);
			
			$style_id = sanitize_text_field($_POST['style_id']);
			$option = sanitize_text_field($_POST['option']);
				
			$on_success = isset( $post['message'] ) ? 'message' : 'redirect';
			$msg_wrong_email = ( isset( $post['msg_wrong_email']  )  && $post['msg_wrong_email'] !== '' ) ? $post['msg_wrong_email'] : __( 'Please enter correct email address.', 'smile' );
      $msg_wrong_email=sanitize_text_field($msg_wrong_email);

			$msg = isset( $_POST['message'] ) ? $_POST['message'] : __( 'Thank You! Your submission was successful.', 'smile' );
      $msg=sanitize_text_field($msg);

			if($on_success == 'message'){
				$action	= 'message';
				$url	= 'none';
			} else {
				$action	= 'redirect';
				$url	= esc_url($post['redirect']);
			}
			$contact = array();
			$contact['name'] = $name;
			$contact['email'] = $email;
			$contact['date'] = date("j-n-Y");

			//	Check Email in MX records
      $myApp = new iSDK;
      $myApp->cfgCon( $infusionsoft_app, $infusionsoft_api, "on" );
			if( !$only_conversion ){
				$email_status = apply_filters('cp_valid_mx_email', $email );
			} else {
				$email_status = false;
			}
			if($email_status) {
        $status='success';
        $web_form_html=$myApp->getWebFormHtml($infusionsoft_list_id);
        $dom = new DOMDocument();
        $dom->loadHTML($web_form_html);
        $xpath = new DOMXPath($dom);
        $tags = $xpath->query('//input[@type="hidden"]');
        foreach ($tags as $tag) {
          if(trim($tag->getAttribute('name'))=="inf_form_xid"){ $inf_form_xid=trim($tag->getAttribute('value')); }
          if(trim($tag->getAttribute('name'))=="inf_form_name"){ $inf_form_name=trim($tag->getAttribute('value')); }
          if(trim($tag->getAttribute('name'))=="infusionsoft_version"){ $infusionsoft_version=trim($tag->getAttribute('value')); }
        }

        $posturl="https://".$infusionsoft_app.".infusionsoft.com/app/form/process/$inf_form_xid";
        $fields = array(
            'inf_field_FirstName' => urlencode($name),
            'inf_field_Email' => urlencode($email),
            'infusionsoft_version' => urlencode($infusionsoft_version),
            'inf_form_xid' => "$inf_form_xid",
            'inf_form_name' => "$inf_form_name"
         );
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        trim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $posturl);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        $curlResponse = curl_exec($ch);
        curl_close($ch);
        if($curlResponse === FALSE){
          // form was NOT successfully submitted
		      print_r(json_encode(array(
						'action' => $action,
						'email_status' => $email_status,
						'status' => 'error',
						'message' => __( "Something went wrong. Please try again.", "smile" ),
						'url' => '',
					)));
					exit();
        } else {
          // add the contact to central contacts database
					$style_id = $_POST['style_id'];
					$option = $_POST['option'];
					if( function_exists( "cp_add_subscriber_contact" ) ){
						$isuserupdated = cp_add_subscriber_contact( $option ,$contact );
					}
					if ( !$isuserupdated ) {  // if user is updated don't count as a conversion
						// update conversions 
						smile_update_conversions($style_id);
					}
        }
      } else {
				if( $only_conversion ){
					// update conversions
					$status = 'success';
					smile_update_conversions( $style_id );
				} else {
					$msg = $msg_wrong_email;
					$status = 'error';
				}
      }
			print_r(json_encode(array(
				'action' => $action,
				'email_status' => $email_status,
				'status' => $status,
				'message' => $msg,
				'url' => $url,
			)));
			exit();
		}

		/*
		 * Function Name: update_infusionsoft_authentication
		 * Function Description: Update infusionsoft values to ConvertPlug
		 */
		
		function update_infusionsoft_authentication() {
			$post = $_POST;
			
			$data = array();
			$infusionsoft_api = sanitize_text_field($post['infusionsoft_api_key']);
			$infusionsoft_app = sanitize_text_field($post['infusionsoft_app']);

			if( $infusionsoft_api == "" ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "Please provide a valid API Key for your " . $this->setting['name'] . " account.", "smile" )
				)));
				exit();
			}

			if( $infusionsoft_app == "" ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "Please provide a valid App Name for your " . $this->setting['name'] . " account.", "smile" )
				)));
				exit();
			}

      try{
        $myApp = new iSDK;
        $myApp->cfgCon($infusionsoft_app, $infusionsoft_api, "on");
      } catch(iSDKException $e) {
        if (strpos($e, 'ERROR') !== FALSE) {
  				print_r(json_encode(array(
  					'status' => "error",
  					'message' => __( "Access denied: Invalid credentials (App Name and/or API key).", "smile" )
  				)));
  				exit();
        }      
      }
      $html = '<div class="bsf-cnlist-form-row infusionsoft-list">';
      $myApp->cfgCon($infusionsoft_app, $infusionsoft_api, "on");
      $is_forms = $myApp->getWebFormMap();
			$connected = true;
			$html = '<label for="infusionsoft-list">'.__( "Select Web Form (email only or name/email only)", "smile" ).'</label>';
			$html .= '<select id="infusionsoft-list" class="bsf-cnlist-select" name="infusionsoft-list">';
			foreach($is_forms as $id => $name) {
				$html .= '<option value="'.$id.'">'.$name.'</option>';
			}
			$html .= '</select>';
      $html .= '</div>'; 
			update_option( $this->slug.'_api_key', $infusionsoft_api );
			update_option( $this->slug.'_app', $infusionsoft_app );

			print_r(json_encode(array(
				'status' => "success",
				'message' => $html
			)));
			exit();
		}


		/*
		 * Function Name: disconnect_infusionsoft
		 * Function Description: Disconnect current Infusionsoft from wp instance
		 */
		
		function disconnect_infusionsoft() {
			delete_option( 'infusionsoft_api_key' );
			delete_option( 'infusionsoft_app' );
			
			$smile_lists = get_option('smile_lists');			
			if( !empty( $smile_lists ) ){ 
				foreach( $smile_lists as $key => $list ) {
					$provider = $list['list-provider'];
					if( strtolower( $provider ) == strtolower( $this->slug ) ){
						$smile_lists[$key]['list-provider'] = "Convert Plug";
					}
				}
				update_option( 'smile_lists', $smile_lists );
			}
			
			print_r(json_encode(array(
                'message' => "disconnected",
			)));
			die();
		}

		/*
		 * Function Name: get_infusionsoft_lists
		 * Function Description: Get Infusionsoft Web Form list
		 */

		function get_infusionsoft_lists( $infusionsoft_api = '', $infusionsoft_app = '' ) {
			if( $infusionsoft_api != '' && $infusionsoft_app != '' ) {
				try {
					$myApp = new iSDK;
					$res = $myApp->cfgCon( $infusionsoft_app, $infusionsoft_api, "on" );
          $is_forms = $myApp->getWebFormMap();
          return $is_forms;
				} catch( iSDKException $ex ) {
					return array();
				}	
			}
			return array();
		}
	}
	new Smile_Mailer_Infusionsoft;
}

$bsf_core_version_file = realpath(dirname(__FILE__).'/admin/bsf-core/version.yml');
if(is_file($bsf_core_version_file)) {
	global $bsf_core_version, $bsf_core_path;
	$bsf_core_dir = realpath(dirname(__FILE__).'/admin/bsf-core/');
	$version = file_get_contents($bsf_core_version_file);
	if(version_compare($version, $bsf_core_version, '>')) {
		$bsf_core_version = $version;
		$bsf_core_path = $bsf_core_dir;
	}
}
add_action('init', 'bsf_core_load', 999);
if(!function_exists('bsf_core_load')) {
	function bsf_core_load() {
		global $bsf_core_version, $bsf_core_path;
		if(is_file(realpath($bsf_core_path.'/index.php'))) {
			include_once realpath($bsf_core_path.'/index.php');
		}
	}
}
?>