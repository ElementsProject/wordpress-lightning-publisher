<?php
/*
    Plugin Name: Lightning Paywall
    Version:     0.1.4
    Plugin URI:  https://github.com/ElementsProject/wordpress-lightning-paywall
    Description: Lightning paywall for WordPress posts
    Author:      Blockstream
    Author URI:  https://blockstream.com
*/

if (!defined('ABSPATH')) exit;

require_once 'vendor/autoload.php';
define('LIGHTNING_PAYWALL_KEY', hash_hmac('sha256', 'lightning-paywall-token', AUTH_KEY));

class Lightning_Paywall {
  public function __construct() {
    $this->options = get_option('ln_paywall');
    $this->charge = new LightningChargeClient($this->options['server_url'], $this->options['api_token']);

    // frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
    add_filter('the_content',        array($this, 'paywall_filter'));

    // ajax
    add_action('wp_ajax_ln_paywall_invoice',        array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_nopriv_ln_paywall_invoice', array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_ln_paywall_token',          array($this, 'ajax_make_token'));
    add_action('wp_ajax_nopriv_ln_paywall_token',   array($this, 'ajax_make_token'));

    // admin
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  /**
   * Process [paywall] tags in post content
   */
  public function paywall_filter($content) {
    $paywall = self::extract_paywall_tag($content);
    if (!$paywall) return $content;

    $post_id = get_the_ID();
    list($public, $protected) = preg_split('/(<p>)?' . preg_quote($paywall->tag, '/') . '(<\/p>)?/', $content, 2);

    return self::check_payment($post_id) ? self::format_paid($post_id, $paywall, $public, $protected)
                                         : self::format_unpaid($post_id, $paywall, $public);
  }

  /**
   * Register scripts and styles
   */
  public function enqueue_script() {
    wp_enqueue_script('ln-paywall', plugins_url('js/paywall.js', __FILE__), array('jquery'));
    wp_enqueue_style('ln-paywall', plugins_url('css/paywall.css', __FILE__));
    wp_localize_script('ln-paywall', 'LN_paywall', array(
      'ajax_url'   => admin_url('admin-ajax.php'),
      'charge_url' => !empty($this->options['public_url']) ? $this->options['public_url'] : $this->options['server_url']
    ));
  }

  /**
   * AJAX endpoint to create new invoices
   */
  public function ajax_make_invoice() {
    $post_id = (int)$_POST['post_id'];
    $paywall = self::extract_paywall_tag(get_post_field('post_content', $post_id));
    if (!$paywall) return status_header(404);

    $invoice = $this->charge->invoice([
      'currency'    => $paywall->currency,
      'amount'      => $paywall->amount,
      'description' => get_bloginfo('name') . ': pay to continue reading ' . get_the_title($post_id),
      'metadata'    => [ 'source' => 'wordpress-lightning-paywall', 'post_id' => $post_id, 'url' => get_permalink($post_id) ]
    ]);

    wp_send_json($invoice->id, 201);
  }

  /**
   * AJAX endpoint to exchange invoices for HMAC access tokens
   * @TODO persist to cookie?
   */
  public function ajax_make_token() {
    $invoice = $this->charge->fetch($_POST['invoice_id']);

    if (!$invoice)                    return status_header(404);
    if (!$invoice->completed)         return status_header(402);
    if (!$invoice->metadata->post_id) return status_header(500); // should never actually happen

    $post_id = $invoice->metadata->post_id;
    $token   = self::make_token($post_id);
    $url     = add_query_arg('paywall_access', $token, get_permalink($post_id));

    wp_send_json([ 'post_id' => $post_id, 'token' => $token, 'url' => $url ]);
  }

  /**
   * Create HMAC tokens granting access to $post_id
   * @param int $post_id
   * @return str base36 token
   * @TODO expiry time, link token to invoice
   */
  protected static function make_token($post_id) {
    return base_convert(hash_hmac('sha256', $post_id, LIGHTNING_PAYWALL_KEY), 16, 36);
  }


  /**
   * Check whether the current visitor has access to $post_id
   * @param int $post_id
   * @return bool
   */
  protected static function check_payment($post_id) {
    return isset($_GET['paywall_access']) && self::make_token($post_id) === $_GET['paywall_access'];
  }

  /**
   * Parse [paywall] tags and return as structured data
   * Expected format: [paywall AMOUNT CURRENCY KEY=VAL]
   * @param string $content
   * @return array
   */
  protected static function extract_paywall_tag($content) {
    if (!preg_match('/\[paywall [\d.]+ [a-z]+.*?\]/i', $content, $m)) return;
    $tag = html_entity_decode(str_replace('&#8221;', '"', $m[0]));
    if (substr($tag, -2, 1) !== ' ') $tag = substr($tag, 0, -1) . ' ]';
    $attrs = shortcode_parse_atts($tag);
    return (object)[ 'tag' => $m[0], 'amount' => $attrs[1], 'currency' => $attrs[2], 'attrs' => $attrs ];
  }

  /**
   * Format display for paywalled, unpaid post
   */
  protected static function format_paid($post_id, $paywall, $public, $protected) {
    $text = isset($paywall->attrs['thanks']) ? $paywall->attrs['thanks']
      : "<p>Thank you for paying! The rest of the post is available below.</p><p>To return to this content later, please add this page to your bookmarks (Ctrl-d).</p>";

    return sprintf('%s<div class="paywall-paid" id="paid">%s</div>%s', $public, $text, $protected);
  }

  /**
   * Format display for paywalled, paid post
   */
  protected static function format_unpaid($post_id, $paywall, $public) {
    $attrs  = $paywall->attrs;
    $text   = '<p>' . sprintf(!isset($attrs['text']) ? 'To continue reading the rest of this post, please pay <em>%s</em>.' : $attrs['text'], $paywall->amount . ' ' . $paywall->currency).'</p>';
    $button = sprintf('<a class="paywall-btn" href="#" data-paywall-postid="%d">%s</a>', $post_id, !isset($attrs['button']) ? 'Pay to continue reading' : $attrs['button']);

    return sprintf('%s<div class="paywall-pay">%s%s</div>', $public, $text, $button);
  }

  /**
   * Admin settings page
   */

  public function admin_menu() {
    add_options_page('Lightning Paywall Settings', 'Lightning Paywall',
                     'manage_options', 'ln_paywall', array($this, 'admin_page'));
  }
  public function admin_init() {
    register_setting('ln_paywall', 'ln_paywall');
    add_settings_section('ln_paywall_server', 'Lightning Charge Server', null, 'ln_paywall');

    add_settings_field('ln_paywall_server_url', 'URL', array($this, 'field_server_url'), 'ln_paywall', 'ln_paywall_server');
    add_settings_field('ln_paywall_server_public_url', 'Public URL', array($this, 'field_public_url'), 'ln_paywall', 'ln_paywall_server');
    add_settings_field('ln_paywall_token', 'API token', array($this, 'field_token'), 'ln_paywall', 'ln_paywall_server');
  }
  public function admin_page() {
    ?>
    <div class="wrap">
        <h1>Lightning Paywall Settings</h1>
        <form method="post" action="options.php">
        <?php
            settings_fields('ln_paywall');
            do_settings_sections('ln_paywall');
            submit_button();
        ?>
        </form>
    </div>
    <?php
  }
  public function field_server_url(){
    printf('<input type="text" name="ln_paywall[server_url]" value="%s" />', esc_attr($this->options['server_url']));
  }
  public function field_public_url(){
    printf('<input type="text" name="ln_paywall[public_url]" value="%s" />', esc_attr($this->options['public_url']));
  }
  public function field_token(){
    printf('<input type="text" name="ln_paywall[api_token]" value="%s" />', esc_attr($this->options['api_token']));
  }
}

new Lightning_Paywall();
