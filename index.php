<?
/*
      ___         ___                   
     /\__\       /\  \         _____    
    /:/ _/_     /::\  \       /::\  \   
   /:/ /\__\   /:/\:\  \     /:/\:\  \  
  /:/ /:/  /  /:/ /::\  \   /:/ /::\__\ 
 /:/_/:/  /  /:/_/:/\:\__\ /:/_/:/\:|__|
 \:\/:/  /   \:\/:/  \/__/ \:\/:/ /:/  /
  \::/__/     \::/__/       \::/_/:/  / 
   \:\  \      \:\  \        \:\/:/  /  
    \:\__\      \:\__\        \::/  /   
     \/__/       \/__/         \/__/
     

Plugin Name: Twitter Bootstrap Shortcodes
Description: Shortcodes Library for Twitter Bootstrap.
Author: Mark Fabrizio
Version: 1.2
Author URI: http://www.owlwatch.com
*/

add_action('init', 'fabs_twitter_bootstrap_shortcodes');
function fabs_twitter_bootstrap_shortcodes()
{
  /********************************************************
  *  Reqires Snap
  *********************************************************/
  if( !class_exists('Snap') ){
    add_action('admin_notices', 'fabs_twitter_bootstrap_shortcodes_alert');
    function fabs_twitter_bootstrap_shortcodes_alert()
    {
      ?>
      <div class="error">
        <p>Twitter Bootstrap Shortcodes requires the
        <a href="https://github.com/fabrizim/Snap">Snap plugin</a>.
        </p>
      </div>
      <?
    }
    return;
  }
  
  require_once(dirname(__FILE__).'/lib/Shortcodes.php');
  Snap::inst('Fabs_Bootstrap_Shortcodes');
  
}