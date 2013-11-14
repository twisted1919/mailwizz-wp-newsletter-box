<?php
/*
Plugin Name: MailWizz Newsletter Box
Plugin URI: http://www.mailwizz.com
Description: Adds a subscription widget for your <a href="http://www.mailwizz.com/" target="_blank">MailWizz Email Marketing Application</a> based on the <a href="https://github.com/twisted1919/mailwizz-php-sdk" target="_blank">PHP-SDK</a>. <br />Using the widget, you can generate a subscription form based on your mail list definition. You also have full control over the generated form, so you can continue changing it until it fits your needs.
Version: 1.1
Author: Serban Cristian <cristian.serban@mailwizz.com>
Author URI: http://www.mailwizz.com
License: MIT http://opensource.org/licenses/MIT
*/

// register the sdk autoloader.
if (!class_exists('MailWizzApi_Autoloader', false)) {
    require_once dirname(__FILE__) . '/mailwizz-php-sdk/MailWizzApi/Autoloader.php';
    MailWizzApi_Autoloader::register();
}
   
/**
 * MailWizzNewsletterBox
 * 
 * This is the widget that will allow newsletter subscriptions.
 * There are various configurations for it, but it really is very simple.
 */
class MailWizzNewsletterBox extends WP_Widget 
{
    /**
     * Register widget with WordPress.
     */
    public function __construct()
    {
        parent::__construct(
            'mwznb', // Base ID
            __('MailWizz Newsletter Box', 'mwznb'), // Name
            array( 'description' => __( 'MailWizz Newsletter Box', 'mwznb' ), ) // Args
        );
    }
    
    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) 
    {
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }    
		
		$nonce      = wp_create_nonce(basename(__FILE__));
		$nonceField = '<input type="hidden" name="mwznb_form_nonce" value="'.$nonce.'" />';
		$form       = $instance['generated_form'];
		$form       = str_replace('</form>', "\n" . $nonceField . "\n</form>", $form);
        ?>
        <div class="mwznb-widget" data-ajaxurl="<?php echo admin_url('admin-ajax.php'); ?>">
            <div class="message"></div>
            <?php echo $form;?>
        </div>
        <?php
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) 
    {
        $title              = isset($instance['title'])                 ? $instance['title']                : null;
        $apiUrl             = isset($instance['api_url'])               ? $instance['api_url']              : null;
        $publicKey          = isset($instance['public_key'])            ? $instance['public_key']           : null;
        $privateKey         = isset($instance['private_key'])           ? $instance['private_key']          : null;
        $listUid            = isset($instance['list_uid'])              ? $instance['list_uid']             : null;
        $listSelectedFields = isset($instance['selected_fields'])       ? $instance['selected_fields']      : array();
        $generatedForm      = isset($instance['generated_form'])        ? $instance['generated_form']       : '';
        
        $freshLists = array(
            array('list_uid' => null, 'name' => __('Please select', 'mwznb'))
        );
        $freshFields = array();
        
        if (!empty($apiUrl) && !empty($publicKey) && !empty($privateKey)) {
            
            $oldSdkConfig = MailWizzApi_Base::getConfig();
            MailWizzApi_Base::setConfig(mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey));

            $endpoint = new MailWizzApi_Endpoint_Lists();
            $response = $endpoint->getLists(1, 50);
            $response = $response->body->toArray();

            if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
                foreach ($response['data']['records'] as $list) {
                    $freshLists[] = array(
                        'list_uid'  => $list['general']['list_uid'],
                        'name'      => $list['general']['name']
                    );
                }
            }
            
            if (!empty($listUid)) {
                $endpoint = new MailWizzApi_Endpoint_ListFields();
                $response = $endpoint->getFields($listUid);
                $response = $response->body->toArray();
                
                if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
                    foreach ($response['data']['records'] as $field) {
                        $freshFields[] = $field;
                    }
                }
            }
            
            mwznb_restore_sdk_config($oldSdkConfig);
            unset($oldSdkConfig);
        }

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><strong><?php _e('Title:'); ?></strong></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('api_url'); ?>"><strong><?php _e('Api url:'); ?></strong></label> 
            <input class="widefat mwz-api-url" id="<?php echo $this->get_field_id('api_url'); ?>" name="<?php echo $this->get_field_name('api_url'); ?>" type="text" value="<?php echo esc_attr($apiUrl); ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('public_key'); ?>"><strong><?php _e('Public api key:'); ?></strong></label> 
            <input class="widefat mwz-public-key" id="<?php echo $this->get_field_id('public_key'); ?>" name="<?php echo $this->get_field_name('public_key'); ?>" type="text" value="<?php echo esc_attr($publicKey); ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('private_key'); ?>"><strong><?php _e('Private api key:'); ?></strong></label> 
            <input class="widefat mwz-private-key" id="<?php echo $this->get_field_id('private_key'); ?>" name="<?php echo $this->get_field_name('private_key'); ?>" type="text" value="<?php echo esc_attr($privateKey); ?>" />
        </p>
        
        <div class="widget-control-actions">
            <div class="alignleft"></div>
            <div class="alignright">
                 <input type="submit" class="button button-primary right mwz-fetch-available-lists" value="Fetch available lists">            
               <span class="spinner mwz-spinner" style="display: none;"></span>
            </div>
            <br class="clear">
        </div>
        
        <div class="lists-container" style="<?php echo !empty($freshFields) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('list_uid'); ?>"><strong><?php _e('Select a list:'); ?></strong></label> 
            <select data-listuid="<?php echo esc_attr($listUid); ?>" data-fieldname="<?php echo $this->get_field_name('selected_fields');?>" class="widefat mwz-mail-lists-dropdown" id="<?php echo $this->get_field_id('list_uid'); ?>" name="<?php echo $this->get_field_name('list_uid'); ?>">
            <?php foreach ($freshLists as $list) { ?>
            <option value="<?php echo $list['list_uid'];?>"<?php if ($listUid == $list['list_uid']) { echo ' selected="selected"';}?>><?php echo $list['name'];?></option>
            <?php } ?>
            </select>
            <br class="clear"/>
            <br class="clear"/>
        </div>
        
        <div class="fields-container" style="<?php echo !empty($listUid) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('selected_fields'); ?>"><strong><?php _e('Fields:'); ?></strong></label> 
            <div class="table-container" style="width:100%;max-height:200px; overflow-y: scroll">
                <?php mwznb_generate_fields_table($freshFields, $this->get_field_name('selected_fields'), $listSelectedFields);?>
            </div>
            <br class="clear">
            <div style="float: right;">
                Generate form again: <input name="<?php echo $this->get_field_name('generate_new_form'); ?>" value="1" type="checkbox" checked="checked"/>
            </div>
            <br class="clear">
        </div>
        
        <div class="generated-form-container" style="<?php echo !empty($listUid) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('generated_form'); ?>"><strong><?php _e('Generated form:'); ?></strong></label> 
            <textarea name="<?php echo $this->get_field_name('generated_form'); ?>" id="<?php echo $this->get_field_id('generated_form'); ?>" style="width: 100%; height: 200px; resize:none; outline:none"><?php echo $generatedForm;?></textarea>
        </div>
        
        <hr />
        <?php 
    }
    
    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) 
    {
        $instance = array();
        
        $instance['title']          = !empty($new_instance['title'])        ? sanitize_text_field($new_instance['title'])       : '';
        $instance['api_url']        = !empty($new_instance['api_url'])      ? sanitize_text_field($new_instance['api_url'])     : '';
        $instance['public_key']     = !empty($new_instance['public_key'])   ? sanitize_text_field($new_instance['public_key'])  : '';
        $instance['private_key']    = !empty($new_instance['private_key'])  ? sanitize_text_field($new_instance['private_key']) : '';
        $instance['list_uid']       = !empty($new_instance['list_uid'])     ? sanitize_text_field($new_instance['list_uid'])    : '';
        $instance['uid']            = !isset($old_instance['uid'])          ? uniqid()                                          : $old_instance['uid'];
        
        $instance['selected_fields'] = !empty($new_instance['selected_fields']) && is_array($new_instance['selected_fields']) ? array_map('sanitize_text_field', $new_instance['selected_fields']) : array();
 
        update_option('mwznb_widget_instance_' . $instance['uid'], array(
            'api_url'       => $instance['api_url'],
            'public_key'    => $instance['public_key'],
            'private_key'   => $instance['private_key'],
            'list_uid'      => $instance['list_uid']
        ));
        
        if (!empty($new_instance['generate_new_form'])) {
            $instance['generated_form'] = $this->generateForm($instance);    
        } else {
            $instance['generated_form'] = !empty($new_instance['generated_form']) ? $new_instance['generated_form'] : '';
        }
        
        return $instance;
    }
    
    /**
     * Helper method to generate the html form that will be pushed in the widgets area in frontend.
     * It exists so that we don't have to generate the html at each page load.
     */
    protected function generateForm(array $instance)
    {
        if (empty($instance['list_uid']) || empty($instance['public_key']) || empty($instance['private_key'])) {
            return;
        }
        
        $oldSdkConfig = MailWizzApi_Base::getConfig();
        MailWizzApi_Base::setConfig(mwznb_build_sdk_config($instance['api_url'], $instance['public_key'], $instance['private_key']));

        $endpoint = new MailWizzApi_Endpoint_ListFields();
        $response = $endpoint->getFields($instance['list_uid']);
        $response = $response->body->toArray();
        
        mwznb_restore_sdk_config($oldSdkConfig);
        unset($oldSdkConfig);
        
        if (!isset($response['status']) || $response['status'] != 'success' || empty($response['data']['records'])) {
            return;
        }
        
        $freshFields    = $response['data']['records'];
        $selectedFields = !empty($instance['selected_fields']) ? $instance['selected_fields'] : array();
        $rowTemplate    = '<div class="form-group"><label>[LABEL] [REQUIRED_SPAN]</label><input type="text" class="form-control" name="[TAG]" placeholder="[HELP_TEXT]" value="" [REQUIRED]/></div>';
        
        $output = array();
        foreach ($freshFields as $field) {
            $searchReplace = array(
                '[LABEL]'           => $field['label'],
                '[REQUIRED]'        => $field['required'] != 'yes' ? '' : 'required',
                '[REQUIRED_SPAN]'   => $field['required'] != 'yes' ? '' : '<span class="required">*</span>',
                '[TAG]'             => $field['tag'],
                '[HELP_TEXT]'       => $field['help_text'],
                
            );
            if (in_array($field['tag'], $selectedFields) || $field['required'] == 'yes') {
                $output[] = str_replace(array_keys($searchReplace), array_values($searchReplace), $rowTemplate);
            }
        }
        
        $out = '<form method="post" data-uid="'.$instance['uid'].'">' . "\n\n";
        $out .= implode("\n\n", $output);
        $out .= "\n\n";
        $out .= '<div class="clearfix"><!-- --></div><div class="actions pull-right"><button type="submit" class="btn btn-default btn-submit">Subscribe</button></div><div class="clearfix"><!-- --></div>';
        $out .= "\n\n" . '</form>';
        
        return $out;
    }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("MailWizzNewsletterBox");'));

// register admin assets
add_action('admin_enqueue_scripts', 'mwznb_load_admin_assets');
function mwznb_load_admin_assets() {
    wp_register_script('mwznb-admin', plugins_url('/js/admin.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('mwznb-admin');
}

// register frontend assets
add_action('wp_enqueue_scripts', 'mwznb_load_frontend_assets');
function mwznb_load_frontend_assets() {
    wp_register_style('mwznb-front', plugins_url('/css/front.css', __FILE__), array(), '1.0');
    wp_register_script('mwznb-front', plugins_url('/js/front.js', __FILE__), array('jquery'), '1.0', true);
    
    wp_enqueue_style('mwznb-front');
    wp_enqueue_script('mwznb-front');
}

// register ajax actions
// fetch the lists available for given api data
add_action('wp_ajax_mwznb_fetch_lists', 'mwznb_fetch_lists_callback');
function mwznb_fetch_lists_callback() {
    $apiUrl     = isset($_POST['api_url'])      ? sanitize_text_field($_POST['api_url'])        : null;
    $publicKey  = isset($_POST['public_key'])   ? sanitize_text_field($_POST['public_key'])     : null;
    $privateKey = isset($_POST['private_key'])  ? sanitize_text_field($_POST['private_key'])    : null;
    
    $errors = array();
    if (empty($apiUrl) || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        $errors['api_url'] = __('Please type a valid API url!', 'mwznb');
    }
    if (empty($publicKey) || strlen($publicKey) != 40) {
        $errors['public_key'] = __('Please type a public API key!', 'mwznb');
    }
    if (empty($privateKey) || strlen($privateKey) != 40) {
        $errors['private_key'] = __('Please type a private API key!', 'mwznb');
    }
    if (!empty($errors)) {
        exit(MailWizzApi_Json::encode(array(
            'result' => 'error',
            'errors' => $errors,
        )));
    }
    
    $oldSdkConfig = MailWizzApi_Base::getConfig();
    MailWizzApi_Base::setConfig(mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey));

    $endpoint = new MailWizzApi_Endpoint_Lists();
    $response = $endpoint->getLists(1, 50);
    $response = $response->body->toArray();
    
    mwznb_restore_sdk_config($oldSdkConfig);
    unset($oldSdkConfig);
    
    if (!isset($response['status']) || $response['status'] != 'success') {
        exit(MailWizzApi_Json::encode(array(
            'result' => 'error',
            'errors' => array(
                'general'   => isset($response['error']) ? $response['error'] : __('Invalid request!', 'mwznb'),
            ),
        )));
    }
    
    if (empty($response['data']['records']) || count($response['data']['records']) == 0) {
        exit(MailWizzApi_Json::encode(array(
            'result' => 'error',
            'errors' => array(
                'general'   => __('We couldn\'t find any mail list, are you sure you have created one?', 'mwznb'),
            ),
        )));
    }
    
    $lists = array(
        array(
            'list_uid'  => null, 
            'name'      => __('Please select', 'mwznb')
        )
    );
    
    foreach ($response['data']['records'] as $list) {
        $lists[] = array(
            'list_uid'  => $list['general']['list_uid'],
            'name'      => $list['general']['name']
        );
    }
    
    exit(MailWizzApi_Json::encode(array(
        'result' => 'success',
        'lists' => $lists,
    )));
}

// fetch list fields
add_action('wp_ajax_mwznb_fetch_list_fields', 'mwznb_fetch_list_fields_callback');
function mwznb_fetch_list_fields_callback() {
    $apiUrl     = isset($_POST['api_url'])      ? sanitize_text_field($_POST['api_url'])        : null;
    $publicKey  = isset($_POST['public_key'])   ? sanitize_text_field($_POST['public_key'])     : null;
    $privateKey = isset($_POST['private_key'])  ? sanitize_text_field($_POST['private_key'])    : null;
    $listUid    = isset($_POST['list_uid'])     ? sanitize_text_field($_POST['list_uid'])       : null;
    $fieldName  = isset($_POST['field_name'])   ? sanitize_text_field($_POST['field_name'])     : null;

    if (
        empty($apiUrl)      || !filter_var($apiUrl, FILTER_VALIDATE_URL) || 
        empty($publicKey)   || strlen($publicKey)   != 40 || 
        empty($privateKey)  || strlen($privateKey)  != 40 || 
        empty($listUid)     || empty($fieldName)
    ) {
        die();
    }
    
    $oldSdkConfig = MailWizzApi_Base::getConfig();
    MailWizzApi_Base::setConfig(mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey));

    $endpoint = new MailWizzApi_Endpoint_ListFields();
    $response = $endpoint->getFields($listUid);
    $response = $response->body->toArray();
    
    mwznb_restore_sdk_config($oldSdkConfig);
    unset($oldSdkConfig);
    
    if (!isset($response['status']) || $response['status'] != 'success' || empty($response['data']['records']) || count($response['data']['records']) == 0) {
        die();
    }
    mwznb_generate_fields_table($response['data']['records'], $fieldName, array());
    die();
}


// subscribe a user in given list
add_action('wp_ajax_mwznb_subscribe', 'mwznb_subscribe_callback');
add_action('wp_ajax_nopriv_mwznb_subscribe', 'mwznb_subscribe_callback');
function mwznb_subscribe_callback() {
	if (!isset($_POST['mwznb_form_nonce']) || !wp_verify_nonce($_POST['mwznb_form_nonce'], basename(__FILE__))) {
		exit(MailWizzApi_Json::encode(array(
            'result'    => 'error', 
            'message'   => __('Invalid nonce!', 'mwznb')
        )));
	}

    $uid = isset($_POST['uid']) ? sanitize_text_field($_POST['uid']) : null;
    if ($uid) {
        unset($_POST['uid']);
    }
    unset($_POST['action'], $_POST['mwznb_form_nonce']);
    
    if (empty($uid) || !($uidData = get_option('mwznb_widget_instance_' . $uid))) {
        exit(MailWizzApi_Json::encode(array(
            'result'    => 'error', 
            'message'   => __('Please try again later!', 'mwznb')
        )));
    }
    
    $keys = array('api_url', 'public_key', 'private_key', 'list_uid');
    foreach ($keys as $key) {
        if (!isset($uidData[$key])) {
            exit(MailWizzApi_Json::encode(array(
                'result'    => 'error', 
                'message'   => __('Please try again later!', 'mwznb')
            )));
        }
    }
    
    $oldSdkConfig = MailWizzApi_Base::getConfig();
    MailWizzApi_Base::setConfig(mwznb_build_sdk_config($uidData['api_url'], $uidData['public_key'], $uidData['private_key']));

    $endpoint = new MailWizzApi_Endpoint_ListSubscribers();
    $response = $endpoint->create($uidData['list_uid'], $_POST);
    $response = $response->body->toArray();
    
    mwznb_restore_sdk_config($oldSdkConfig);
    unset($oldSdkConfig);
    
    if (isset($response['status']) && $response['status'] == 'error' && isset($response['error'])) {
        $errorMessage = $response['error'];
        if (is_array($errorMessage)) {
            $errorMessage = implode("\n", array_values($errorMessage));
        }
        exit(MailWizzApi_Json::encode(array(
            'result'    => 'error', 
            'message'   => $errorMessage
        )));
    }
    
    if (isset($response['status']) && $response['status'] == 'success') {
        exit(MailWizzApi_Json::encode(array(
            'result'    => 'success', 
            'message'   => __('Please check your email to confirm the subscription!', 'mwznb')
        )));
    }
    
    exit(MailWizzApi_Json::encode(array(
        'result'    => 'success', 
        'message'   => __('Unknown error!', 'mwznb')
    )));
}

// admin notice if cache folder not writable.
function mwznb_admin_notice() {
    global $pagenow;
    if ($pagenow != 'widgets.php') {
        return;
    }
    if (is_writable($cacheDir = dirname(__FILE__) . '/mailwizz-php-sdk/MailWizzApi/Cache/data/cache')) {
        return;
    }
    ?>
    <div class="error">
        <p><?php _e('Permissions error!', 'mwznb'); ?></p>
        <p><?php _e('The directory "<strong>'.$cacheDir.'</strong>" must be writable by the web server (chmod -R 0777)!', 'mwznb'); ?></p>
        <p><?php _e('Please fix this error now.', 'mwznb'); ?></p>
    </div>
    <?php
}
add_action('admin_notices', 'mwznb_admin_notice');


// various function helpers
// build the sdk config
function mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey) {
    return new MailWizzApi_Config(array(
        'apiUrl'        => $apiUrl,
        'publicKey'     => $publicKey,
        'privateKey'    => $privateKey,
        
        // components
        'components' => array(
            'cache' => array(
                'class'     => 'MailWizzApi_Cache_File',
                'filesPath' => dirname(__FILE__) . '/mailwizz-php-sdk/MailWizzApi/Cache/data/cache', // make sure it is writable by webserver
            )
        ),
    ));
}

// restore the original config
function mwznb_restore_sdk_config($oldConfig) {
    if (!empty($oldConfig) && $oldConfig instanceof MailWizzApi_Config) {
        MailWizzApi_Base::setConfig($oldConfig);
    }
}

// small function to generate our fields table.
function mwznb_generate_fields_table(array $freshFields = array(), $fieldName, array $listSelectedFields = array()) {
    ?>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <th width="40" align="left"><?php echo  __('Show', 'mwznb');?></th>
            <th width="60" align="left"><?php echo  __('Required', 'mwznb');?></th>
            <th align="left"><?php echo  __('Label', 'mwznb');?></th>
        </thead>
        <tbody>
            <?php foreach ($freshFields as $field) { ?>
            <tr>
                <td width="40" align="left"><input name="<?php echo $fieldName; ?>[]" value="<?php echo $field['tag']?>" type="checkbox"<?php echo empty($listSelectedFields) || in_array($field['tag'], $listSelectedFields) ? ' checked="checked"':''?>/></td>
                <td width="60" align="left"><?php echo $field['required'];?></td>
                <td align="left"><?php echo $field['label'];?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php
}