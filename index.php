<?php
/*
* Plugin Name: Om Dusupay Gateway Woocommerce
* Plugin URI: http://sanditsolution.com/
* Description: Dusupay Payment gateway for woocommerce
* Version: 01.01.03
* Author:Siddharth Singh
* Author URI:http://www.sanditsolution.com/about.html
* License: GPLv2 or later
*License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('plugins_loaded', 'woocommerce_mrova_dusupay_init', 0);
function woocommerce_mrova_dusupay_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Mrova_Payu extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'payu';
      $this -> medthod_title = 'Dusupay';
      $this -> has_fields = false;

      $this -> init_form_fields();
      $this -> init_settings();

      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> merchant_id = $this -> settings['merchant_id'];
      $this -> redirect_page_id = $this -> settings['redirect_page_id'];
	  $this -> environment = $this -> settings['environment'];
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";

      add_action('init', array(&$this, 'check_payu_response'));
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_payu', array(&$this, 'receipt_page'));
   }
    function init_form_fields(){
if($_GET['dusupay_transactionId']){
			$order_id=$_GET['order'];
			$order = new WC_Order( $order_id );
			$order -> update_status('completed');
			echo $return_url;
			$return_url = $order->get_checkout_order_received_url();
			wp_redirect($return_url);
			}
       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'mrova'),
                    'type' => 'checkbox',
                    'label' => __('Enable Dusupay Payment Module.', 'mrova'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'mrova'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'mrova'),
                    'default' => __('Dusupay', 'mrova')),
                'description' => array(
                    'title' => __('Description:', 'mrova'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'mrova'),
                    'default' => __('Pay securely by Credit or Debit card or internet banking through Dusupay Secure Servers.', 'mrova')),
                'merchant_id' => array(
                    'title' => __('Merchant ID', 'mrova'),
                    'type' => 'text',
                    'description' => __('This id(USER ID) available at "Generate Working Key" of "Settings and Options at Dusupay."')),
			'environment' => array(
			'title'		=> __( 'Dusupay Test Mode', 'mrova' ),
			'label'		=> __( 'Enable Test Mode', 'mrova' ),
			'type'		=> 'select',
			'options' => array("yes","no")
		)
            );
    }

       public function admin_options(){
        echo '<h3>'.__('Dusupay Payment Gateway', 'mrova').'</h3>';
        echo '<p>'.__('Dusupay is most popular payment gateway for online shopping in world').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        echo '<p>'.__('Thank you for your order, please click the button below to pay with Dusupay.', 'mrova').'</p>';
		if($_GET['dusupay_transactionId']){
			$order_id=$_GET['order'];
			$order = new WC_Order( $order_id );
			$order -> update_status('completed');
			}
        echo $this -> generate_payu_form($order);
             
    }
    /**
     * Generate payu button link
     **/
    public function generate_payu_form($order_id){

       global $woocommerce;
    	$order = new WC_Order( $order_id );				
        $txnid = $order_id.'_'.date("ymds");

        $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
        $productinfo = "Order $order_id";
        $str = "$this->merchant_id|$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|||||||||||$this->salt";
        $hash = hash('sha512', $str);
		$currency_code=trim(get_woocommerce_currency());

	$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		if($this->environment == FALSE ) {$environment_url="https://sandbox.dusupay.com";}else{
			$environment_url="https://www.dusupay.com";
			} 
        $payu_args = array(
          'key' => $this -> merchant_id,
          'txnid' => $txnid,
          'amount' => $order -> order_total,
          'productinfo' => $productinfo,
          'firstname' => $order -> billing_first_name,
          'lastname' => $order -> billing_last_name,
          'address1' => $order -> billing_address_1,
          'address2' => $order -> billing_address_2,
          'city' => $order -> billing_city,
          'state' => $order -> billing_state,
          'country' => $order -> billing_country,
          'zipcode' => $order -> billing_zip,
          'email' => $order -> billing_email,
          'phone' => $order -> billing_phone,
          'surl' => $redirect_url,
          'furl' => $redirect_url,
          'curl' => $redirect_url,
          'hash' => $hash,
          'pg' => 'NB' 
          );
$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";		  	  		  
ob_start();
$actual_link_two=site_url(); 
?>
<style>.order_details{ display:none;}
.alert-success{ margin-left:40px;}
</style>
<form method="post" action="<?php echo $environment_url; ?>/dusu_payments/dusupay" target="_self">
  <input type="hidden" name="dusupay_merchantId" value="<?php $this->merchant_id?>" required>
  <input type="hidden" name="dusupay_amount" value="<?php $order->order_total;?>" optional>
  <input type="hidden" name="dusupay_currency" value="<?php $currency_code;?>" required>
  <input type="hidden" name="dusupay_itemId" value="<?php $productinfo;?>" required>
  <input type="hidden" name="dusupay_itemName" value="<?php $productinfo;?>" required>
  <input type="hidden" name="dusupay_transactionReference" value="<?php $txnid;?>" required>
  <?php if($this->environment == FALSE ) { ?>
  <input type="hidden" name="dusupay_environment" value="sandbox" required>
<?php } ?>
  <input type="hidden" name="dusupay_redirectURL" value="<?php $actual_link;?>&txnid=<?php $txnid;?>&order_id=<?php $order_id?>&Amount=<?php $order->order_total;?>" optional>
  <input type="hidden" name="dusupay_successURL" value="<?php $actual_link;?>&txnid=<?php $txnid;?>&order_id=<?php $order_id?>&Amount=<?php $order->order_total;?>" optional>
  <input type="hidden" name="dusupay_logo" value="<?php echo plugins_url('/img/logo.png', __FILE__) ?>" optional>
  <!--<input type="hidden" name="dusupay_hash" value="hashValue..." required>-->
  <input type="image" name="submit" src="<?php echo plugins_url('/img/dusupaybtn6.png', __FILE__) ?>" />
</form>


 <?php
$om_dusupay_form= ob_get_clean();
//Check for valid payu server callback

return $om_dusupay_form.$this->check_payu_response($order,$txnid,$hash);

    }
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){
        global $woocommerce;
    	$order = new WC_Order( $order_id );
        return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
        );
    }

    /**
     * Check for valid payu server callback
     **/
     function check_payu_response(){
        global $woocommerce;
        if(isset($_REQUEST['txnid']) && isset($_REQUEST['dusupay_transactionId'])){
            $order_id_time = $_REQUEST['txnid'];
            $order_id = explode('_', $_REQUEST['txnid']);
            $order_id = (int)$order_id[0];
            if($order_id != ''){
                try{
                    $order = new WC_Order( $order_id );
                    $merchant_id = $_REQUEST['key'];
                    $amount = $_REQUEST['Amount'];
                    $hash = $_REQUEST['hash'];

                    $status = $_REQUEST['status'];
                    $productinfo = "Order $order_id";
                    echo $hash;
                    echo "{$this->salt}|$status|||||||||||{$order->billing_email}|{$order->billing_first_name}|$productinfo|{$order->order_total}|$order_id_time|{$this->merchant_id}";
                    $checkhash = hash('sha512', "{$this->salt}|$status|||||||||||{$order->billing_email}|{$order->billing_first_name}|$productinfo|{$order->order_total}|$order_id_time|{$this->merchant_id}");
                    $transauthorised = false;
                    if($order -> status !=='completed'){
                        if($hash == $checkhash)
                        {

                          $status = strtolower($status);

                            if($status=="success"){
                                $transauthorised = true;
                                $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $this -> msg['class'] = 'woocommerce_message';
                                if($order -> status == 'processing'){

                                }else{
                                    $order -> payment_complete();
                                    $order -> add_order_note('PayU payment successful<br/>Unnique Id from PayU: '.$_REQUEST['mihpayid']);
                                    $order -> add_order_note($this->msg['message']);
                                    $woocommerce -> cart -> empty_cart();
                                }
                            }else if($status=="pending"){
                                $this -> msg['message'] = "Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail";
                                $this -> msg['class'] = 'woocommerce_message woocommerce_message_info';
                                $order -> add_order_note('PayU payment status is pending<br/>Unnique Id from PayU: '.$_REQUEST['mihpayid']);
                                $order -> add_order_note($this->msg['message']);
                                $order -> update_status('on-hold');
                                $woocommerce -> cart -> empty_cart();
                            }
                            else{
                                $this -> msg['class'] = 'woocommerce_error';
                                $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                                $order -> add_order_note('Transaction Declined: '.$_REQUEST['Error']);
                                //Here you need to put in the routines for a failed
                                //transaction such as sending an email to customer
                                //setting database status etc etc
                            }
                        }else{
                            $this -> msg['class'] = 'error';
                            $this -> msg['message'] = "Security Error. Illegal access detected";

                            //Here you need to simply ignore this and dont need
                            //to perform any operation in this condition
                        }
                        if($transauthorised==false){
                            $order -> update_status('failed');
                            $order -> add_order_note('Failed');
                            $order -> add_order_note($this->msg['message']);
                        }
                        add_action('the_content', array(&$this, 'showMessage'));
                    }}catch(Exception $e){
                        // $errorOccurred = true;
                        $msg = "Error";
                    }

            }



        }

    }

    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_mrova_payu_gateway($methods) {
        $methods[] = 'WC_Mrova_Payu';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_mrova_payu_gateway' );

}






include_once dirname( __FILE__ ) . '/including_js_css.php';
add_filter( 'plugin_action_links', 'om_dusupay_gateway_add_action_plugin', 10, 5 ); 
function om_dusupay_gateway_add_action_plugin( $actions, $plugin_file ){static $plugin; if(!isset($plugin))$plugin = plugin_basename(__FILE__); 
if ($plugin == $plugin_file) { $more_product = array('more product' => '<a href="http://www.sanditsolution.com/shops/">' . __('More Product', 'General') . '</a>');$site_link = array('support' => '<a href="http://www.sanditsolution.com/contact.html" target="_blank">Support</a>');
$became_client = array('became client' => '<a href="http://doc.sanditsolution.com/register/" target="_blank">Became Client</a>');
$actions = array_merge($more_product, $actions);$actions = array_merge($site_link, $actions);$actions = array_merge($became_client, $actions);
}return $actions;}?>
