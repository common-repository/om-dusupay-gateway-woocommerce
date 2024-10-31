<?php 
/**********************************
*****working on js and css*********
***********************************/
function om_stripe_register_style() {
 wp_enqueue_style( 'om_stripe_style', plugins_url('/css/sidd.css', __FILE__), array(), '1.0.0', 'all' );
}add_action('wp_enqueue_scripts', "om_stripe_register_style");

function my_admin_theme_style() {
wp_enqueue_script( 'om_admin_script', plugins_url('/js/om_admin_script.js', __FILE__), array('jquery'), '1.0.0', true );
}add_action('admin_enqueue_scripts', 'my_admin_theme_style'); ?>