<?php
/*
*
* Plugin Name: MF Alepay Installment
* Plugin URI: https://thietkewebfindme.com/
* Description: Easy installment payment with Alepay
* Author: Media Findme
* Author URI: https://thietkewebfindme.com/
* Contributors: insc15
* Version: 1.0
* Text Domain: alepay-installment 
*
*/

if (!defined('ABSPATH')) exit; 

include(plugin_dir_path(__FILE__) . 'libraries/ApiServices.php');

add_action('plugins_loaded', 'init_installment_gateway_class');

function init_installment_gateway_class(){

    class MF_Installment_Gateway extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'mf_installment_gateway';

            $this->id                 = 'mf_installment_gateway';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->title              = __( 'Thanh toán trả góp', $this->domain );
            $this->method_description = __( 'Cho phép khách hàng thanh toán trả góp thông qua dịch vụ của Alepay.', $this->domain );

            $this->init_form_fields();

            $this->description        = $this->get_option('description');
            $this->publicKey        = $this->get_option('publicKey');
			$this->checksum       = $this->get_option('checksum');
			$this->token        = $this->get_option('token');
            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			wp_enqueue_style( 'my-style', plugins_url( '/assets/style.css', __FILE__ ), false, '1.0', 'all' );
        }

        /**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields()
		{

			$this->form_fields = array(
				'enabled'      => array(
					'title'   => __('Enable/Disable', 'woocommerce'),
					'type'    => 'checkbox',
					'label'   => __('Kích hoạt thanh toán trả góp', $this->domain),
					'default' => 'no',
				),
				'encryptKey'        => array(
					'title'       => __('encryptKey', 'woocommerce'),
					'type'        => 'text',
					'description' => __('Thông tin encryptKey', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true,
				),
				'checksum'        => array(
					'title'       => __('checkSum', 'woocommerce'),
					'type'        => 'text',
					'description' => __('checkSum', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true,
				),
				'token'        => array(
					'title'       => __('Token', 'woocommerce'),
					'type'        => 'text',
					'description' => __('Token', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __('Description', 'woocommerce'),
					'type'        => 'textarea',
					'description' => __( 'Thanh toán trả góp qua Alepay. Bạn sẽ được chuyển tới Alepay.vn để tiến hành thanh toán trả góp.', $this->domain ),
					'default'     => __( 'Thanh toán trả góp qua Alepay. Bạn sẽ được chuyển tới Alepay.vn để tiến hành thanh toán trả góp.', $this->domain ),
					'desc_tip'    => true,
				)
			);
		}

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

			$installment_info = getInstallmentInfo(
				array(
					"amount" => $this->get_order_total(),
					"currencyCode" => get_woocommerce_currency(),
					"tokenKey" => $this->token,
				),
				$this->checksum
			);

			wp_enqueue_script('mf-installment-script', plugin_dir_url(__FILE__) . 'assets/main.js', array('jquery'), '1.0', true);

			add_action('wp_footer', function() use ($installment_info) {
				?>
					<script type="text/javascript">
						const installmentInfo = <?php echo json_encode($installment_info['data']); ?>;
						const orderTotal = <?php echo $this->get_order_total(); ?>;
					</script>
				<?php
			});
			
            ?>
            <div id="mfig">
                <div class="mfig__step mfig__step-bank">
					<p class="mfig__label">Bước 1: Chọn ngân hàng trả góp</p>
					<div class="mfig__choices-container">
						<?php foreach ($installment_info['data'] as $key => $value) { ?>
							<label>
								<input type="radio" name="bankCode" hidden value="<?php echo $value['bankCode'] ?>">
								<img src="<?php echo $value['logo'] ?>" alt="">
							</label>
						<?php }	?>
					</div>
				</div>
            </div>
            <?php
        }

		public function process_payment($order_id)
		{

			$order = wc_get_order($order_id);
			
			$requestData = array(
				"tokenKey" => $this->token,
				"orderCode" => (string) $order_id,
				"customMerchantId" => "",
				"amount" => $order->get_total(),
				"currency" => get_woocommerce_currency(),
				"orderDescription" => "Thanh toán trả góp",
				"totalItem" => $order->get_item_count(),
				"checkoutType" => 2,
				"installment" => true,
				"month" => $_POST['period'],
				"bankCode" => $_POST['bankCode'],
				"paymentMethod" => $_POST['paymentMethod'],
				"returnUrl" => $this->get_return_url($order),
				"cancelUrl" => html_entity_decode($order->get_cancel_order_url(home_url())),
				"buyerName" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				"buyerEmail" => $order->get_billing_email(),
				"buyerPhone" => $order->get_billing_phone(),
				"buyerAddress" => $order->get_billing_address_1(),
				"buyerCity" => $order->get_billing_city(),
				"buyerCountry" => "Việt Nam",
			);

			$res = processCheckout($requestData, $this->checksum);

			return array(
				'result'    => 'success',
				'redirect'  => $res['checkoutUrl']
			);
		}
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_gateway_class' );
function add_gateway_class( $methods ) {
    $methods[] = 'MF_Installment_Gateway'; 
    return $methods;
}

add_action('woocommerce_checkout_process', 'process_custom_payment');
function process_custom_payment(){

    if($_POST['payment_method'] != 'mf_installment_gateway')
        return;

    if( !isset($_POST['bankCode']) || empty($_POST['bankCode']) )
        wc_add_notice( __( 'Vui lòng chọn ngân hàng trả góp', 'mf_installment_gateway' ), 'error' );

    else if( !isset($_POST['paymentMethod']) || empty($_POST['paymentMethod']) )
        wc_add_notice( __( 'Vui lòng chọn thẻ trả góp', 'mf_installment_gateway' ), 'error' );

	else if( !isset($_POST['period']) || empty($_POST['period']) )
        wc_add_notice( __( 'Vui lòng chọn kỳ hạn', 'mf_installment_gateway' ), 'error' );

}

add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );
function custom_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'mf_installment_gateway')
        return;

    update_post_meta( $order_id, 'bankCode', $_POST['bankCode'] );
    update_post_meta( $order_id, 'paymentMethod', $_POST['paymentMethod'] );
	update_post_meta( $order_id, 'period', $_POST['period'] );
}
