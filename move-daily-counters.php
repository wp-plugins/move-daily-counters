<?php
/*
Plugin Name: Move Daily Counters
Plugin URI: http://www.easycpmods.com
Description: Move Daily Counters is a Classipress plugin that will move values from a Classipress table where daily counters are stored to a plugin table. Why? Because a large table with daily counters will make your site very slow.
Author: Easy CP Mods
Version: 1.3.5
Author URI: http://www.eayscpmods.com
*/

  define('ECPM_DDC', 'ecpm-ddc');
  define('DDC_NAME', '/move-daily-counters');
  
  register_activation_hook( __FILE__, 'ecpm_ddc_activate');
  register_deactivation_hook( __FILE__, 'ecpm_ddc_deactivate');
  
  add_action('plugins_loaded', 'ecpm_ddc_plugins_loaded');
  add_action('ecpm_move_daily_counters', 'ecpm_ddc_event');
  add_action('admin_init', 'ecpm_ddc_requires_cp_version');
    
  // Settings
  add_action('appthemes_add_submenu_page', 'ecpm_ddc_create_menu');
  
  function ddc_is_pro(){
    if ( file_exists( WP_PLUGIN_DIR . DDC_NAME . '/ddc-pro.php' ) ) {
      return true;
    }
    return false;
  }
  
  if ( ddc_is_pro() ) {
    require_once( WP_PLUGIN_DIR . DDC_NAME . '/ddc-pro.php' );
  } 
  
  function ecpm_ddc_requires_cp_version() {
    global $app_version;
    
    if ( isset($app_version) )
      $ecm_cp_version = $app_version;
    else
      $ecm_cp_version = CP_VERSION;
        
    if ( !$ecm_cp_version ) {
  	  $plugin_data = get_plugin_data( __FILE__, false );
  		
      if( is_plugin_active($plugin) ) {
  			deactivate_plugins( $plugin );
  			wp_die( "<strong>".$plugin_data['Name']."</strong> requires a Classipress theme to be installed. Your Wordpress installation does not appear to have Classipress installed. The plugin has been deactivated!<br />If this is a mistake, please contact plugin developer!<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
  		}
  	}
  }
  
  function ecpm_ddc_activate() {
    ddc_table_create();
    
    $ecpm_ddc_installed = get_option('ecpm_ddc_installed');
    if ( $ecpm_ddc_installed != 'yes' ) {
      update_option( 'ecpm_ddc_leave_days', '1' );
      update_option( 'ecpm_ddc_record_threshold', '0' );
      update_option( 'ecpm_ddc_freq', 'manual' );
      update_option( 'ecpm_ddc_installed', 'yes' );
      update_option( 'ecpm_ddc_remove_data', '' );
      update_option( 'ecpm_ddc_move_back_data', '' );            
    }
  }
  
  function ecpm_ddc_deactivate() {                                   
    update_option( 'ecpm_ddc_freq', 'manual' );
    wp_clear_scheduled_hook('ecpm_move_daily_counters');
    
    $ecpm_ddc_move_back_data = get_option('ecpm_ddc_move_back_data');
    $ecpm_ddc_remove_data = get_option('ecpm_ddc_remove_data');
    
    if ($ecpm_ddc_move_back_data == 'on')
      $moved = ecpm_get_data('moveback');
      
    if ($ecpm_ddc_remove_data == 'on') {
       delete_option( 'ecpm_ddc_installed' );
       delete_option( 'ecpm_ddc_leave_days' );
       delete_option( 'ecpm_ddc_record_threshold' );
       delete_option( 'ecpm_ddc_freq' );
       delete_option( 'ecpm_ddc_installed' );
       delete_option( 'ecpm_ddc_remove_data' );
       delete_option( 'ecpm_ddc_move_back_data' );
       delete_option( 'ecpm_ddc_max_time' );
       delete_option( 'ecpm_ddc_min_time' );
       delete_option( 'ecpm_ddc_avg_hits' );
       
       global $table_prefix, $wpdb;
       $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."ecpm_ddc");
    }  
  }
  
  function ecpm_ddc_plugins_loaded() {
  	$dir = dirname(plugin_basename(__FILE__)).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR;
  	load_plugin_textdomain(ECPM_DDC, false, $dir);
  }
  
  function ecpm_get_data( $action = 'count' ) {
    global $wpdb;
      switch ( $action ) {
        case 'count':
          $return_count = count_daily($wpdb);
          break;

        case 'move':
          $ecpm_ddc_record_threshold = get_option('ecpm_ddc_record_threshold');
          $count = count_daily($wpdb);
     
          if ( $count >= $ecpm_ddc_record_threshold  && $count > 0 ) {
            $return_count = move_daily($wpdb);
          } else
            $return_count = 0;
          break;
        
        case 'moveback':
          $return_count = move_data_back($wpdb);
          break;  
      }
      return $return_count;
  }
  
  function time_daily_table($wpdb) {
    $ecpm_ddc_max_time = get_option('ecpm_ddc_max_time');
    $ecpm_ddc_min_time = get_option('ecpm_ddc_min_time');
    
    $ad_ids = $wpdb->get_results( "SELECT ID FROM $wpdb->posts p WHERE p.post_type = '".APP_POST_TYPE."' and p.post_status = 'publish' ORDER BY RAND() LIMIT 30;" );
    $today_date = date( 'Y-m-d', current_time( 'timestamp' ) );

// time max value
    $start_time = microtime();
    
    foreach ( $ad_ids as $ad_id ) {
      $result = $wpdb->get_var( "SELECT postcount FROM ".$wpdb->prefix."ecpm_ddc WHERE postnum = $ad_id->ID and time = $today_date" );
    } 
    $exec_time = microtime() - $start_time;
    
    if ($exec_time > $ecpm_ddc_max_time) 
      update_option( 'ecpm_ddc_max_time', $exec_time );
    
    
// time min value
    $start_time = microtime();
    
    foreach ( $ad_ids as $ad_id ) {
      $result = $wpdb->get_var( "SELECT postcount FROM $wpdb->cp_ad_pop_daily WHERE postnum = $ad_id->ID and time < $today_date" );
    } 
    $exec_time = microtime() - $start_time;
    
    if ($exec_time < $ecpm_ddc_min_time || !$ecpm_ddc_min_time)
      update_option( 'ecpm_ddc_min_time', $exec_time );

// average hits
    $time_to = appthemes_mysql_date( current_time( 'mysql' ) );
    $time_from = appthemes_mysql_date( current_time( 'mysql' ) - 90 );
    $sql = "SELECT AVG(total) AS tot FROM ( SELECT SUM(postcount) as total, time FROM ".$wpdb->prefix."ecpm_ddc WHERE time >= CURDATE()-90 and time < CURDATE() GROUP BY DATE(time) ) sumtotal";
	  $result = $wpdb->get_var( $sql );
    update_option( 'ecpm_ddc_avg_hits', round($result,0) );
	}
  
  function count_daily($wpdb) {
    $result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->cp_ad_pop_daily" );
    return $result;
  }
  
  function move_daily($wpdb) {
    $ecpm_ddc_leave_days = get_option('ecpm_ddc_leave_days');

    // copy data to plugin table for statistics
    $wpdb->query("INSERT INTO ".$wpdb->prefix."ecpm_ddc SELECT * FROM $wpdb->cp_ad_pop_daily WHERE ".$wpdb->cp_ad_pop_daily.".time <= CURRENT_DATE - INTERVAL $ecpm_ddc_leave_days DAY");
    
    // delete data from original table
    $result = $wpdb->query("DELETE FROM $wpdb->cp_ad_pop_daily WHERE time <= CURRENT_DATE - INTERVAL $ecpm_ddc_leave_days DAY");
    
    time_daily_table($wpdb);
    
    return $result;
  }
  
  function move_data_back($wpdb) {
    // copy data to original table
    $wpdb->query("INSERT INTO $wpdb->cp_ad_pop_daily SELECT * FROM ".$wpdb->prefix."ecpm_ddc");
    
    // delete data from ecpm table
    $result = $wpdb->query("DELETE FROM ".$wpdb->prefix."ecpm_ddc");
    
    return $result;
  }
  
  function ddc_table_create () {
    global $wpdb;

    $table_name = $wpdb->prefix . "ecpm_ddc";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL,
            time date NOT NULL DEFAULT '0000-00-00',
            postnum int(11) NOT NULL,
            postcount int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (id)
          ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }
  
  function ecpm_ddc_event() {
    if ( ddc_is_pro() ){
      $ecpm_connect = ecpm_get_data('move');
      echo $ecpm_connect;
    } else {
      ecpm_ddc_deactivate();
    }
  }
  
  function ecpm_ddc_create_menu() {
  	add_submenu_page('app-dashboard','Move Daily Counters','Move Daily Counters','manage_options', __FILE__.'ecpm_ddc_settings_page','ecpm_ddc_settings_page');
  }
  
  function ecpm_ddc_settings_page() {
  ?>
  	<div class="wrap">
  	<?php
  	
  	if( isset( $_POST['ecpm_ddc_submit'] ) )
  	{
  		
      if ( !isset($_POST[ 'ecpm_ddc_remove_data' ]) )
        $ecpm_ddc_remove_data = '';
      else
        $ecpm_ddc_remove_data = $_POST[ 'ecpm_ddc_remove_data' ];
        
      if ( !isset($_POST[ 'ecpm_ddc_move_back_data' ]) )
        $ecpm_ddc_move_back_data = '';
      else
        $ecpm_ddc_move_back_data = $_POST[ 'ecpm_ddc_move_back_data' ];
      

      if ( !isset($_POST[ 'ecpm_ddc_leave_days' ]) )
        $ecpm_ddc_leave_days = '';
      else {
        $ecpm_ddc_leave_days = $_POST[ 'ecpm_ddc_leave_days' ];
        if ( $ecpm_ddc_leave_days  < 1 ) {
          $ecpm_ddc_leave_days = 1;
        }
      }   
      
      if ( !isset($_POST[ 'ecpm_ddc_record_threshold' ]) )
        $ecpm_ddc_record_threshold = '';
      else
        $ecpm_ddc_record_threshold = $_POST[ 'ecpm_ddc_record_threshold' ];  
        
      if ( !isset($_POST[ 'ecpm_ddc_freq' ]) )
        $ecpm_ddc_freq = '';
      else {
        $ecpm_ddc_freq = $_POST[ 'ecpm_ddc_freq' ];
        
        if ( $ecpm_ddc_freq == 'manual' || !ddc_is_pro() ) {
          wp_clear_scheduled_hook('ecpm_move_daily_counters');
          update_option( 'ecpm_ddc_freq' , 'manual' );
        } else {
            wp_schedule_event( current_time( 'timestamp' ), $ecpm_ddc_freq, 'ecpm_move_daily_counters');
            update_option( 'ecpm_ddc_freq' , $ecpm_ddc_freq );
        }
      }  
        
      update_option( 'ecpm_ddc_leave_days' , $ecpm_ddc_leave_days );
      //update_option( 'ecpm_ddc_freq' , $ecpm_ddc_freq );
      update_option( 'ecpm_ddc_record_threshold' , $ecpm_ddc_record_threshold );
      update_option( 'ecpm_ddc_move_back_data', $ecpm_ddc_move_back_data );
      update_option( 'ecpm_ddc_remove_data', $ecpm_ddc_remove_data );
  
      ?>
          <div id="message" class="updated">
              <p><strong><?php _e('Settings saved.') ?></strong></p>
          </div>
      <?php  
  	}
    
    if( isset($_POST['ecpm_ddc_submit_move']) ) { 
      $moved = ecpm_get_data('move');
    ?>
        <div id="message" class="updated">
            <p><strong><?php echo sprintf( __('Records moved: %s', ECPM_DDC ), $moved ) ?></strong></p>
        </div>
    <?php  
    }
  	
    $ecpm_ddc_leave_days = get_option('ecpm_ddc_leave_days');
    $ecpm_ddc_record_threshold = get_option('ecpm_ddc_record_threshold');
    $ecpm_ddc_freq = get_option('ecpm_ddc_freq');
    $time_diff = get_option('ecpm_ddc_max_time') - get_option('ecpm_ddc_min_time');
    
    $ecpm_ddc_move_back_data = get_option('ecpm_ddc_move_back_data');
    $ecpm_ddc_remove_data = get_option('ecpm_ddc_remove_data');

    $ecpm_ddc_avg_hits = get_option('ecpm_ddc_avg_hits');
    $ecpm_ddc_avg_time_saved = $ecpm_ddc_avg_hits * $time_diff;
    $sec = intval($ecpm_ddc_avg_time_saved);
    $final = strftime('%T', mktime(0, 0, $sec))
    
    ?>
  		<div id="ddcsetting">
  			<h2><?php echo _e('Move Daily Counters Settings', ECPM_DDC); ?></h2>
        <hr>
        <strong>The development of this plugin is discontinued!</strong> There is a new plugin that does the same thing with some additions.<br>
        <h3>Please download a new plugin called <a href="https://wordpress.org/plugins/faster-with-stats">Faster with stats<a>.</h3>
         
        <table width="100%" border=0 cellspacing=5 cellpadding=10><tr><td valign="top">
        <form id='ddcsettingform' method="post" action="">
          <h3><?php echo _e('Move records older then (in days):', ECPM_DDC); ?>
          <Input type='text' size='3' Name ='ecpm_ddc_leave_days' value='<?php echo $ecpm_ddc_leave_days;?>'></h3>

          <h3><?php echo _e('Record threshold:', ECPM_DDC); ?>
          <Input type='text' size='5' Name ='ecpm_ddc_record_threshold' value='<?php echo $ecpm_ddc_record_threshold;?>'></h3>
          
          <h3><?php echo _e('Run this script:', ECPM_DDC); ?>
          <?php if ( ddc_is_pro() ) { ?>
            <select name="ecpm_ddc_freq">
              <option value="manual" <?php echo ($ecpm_ddc_freq == 'manual' ? 'selected':'') ;?>><?php _e('Manually', ECPM_DDC);?></option>
              <option value="daily" <?php echo ($ecpm_ddc_freq == 'daily' ? 'selected':'') ;?>><?php _e('Daily', ECPM_DDC);?></option>
            </select>
          <?php 
          } else { 
            echo _e('Manually', ECPM_DDC);
          } 
          ?></h3>
          <hr>
          <p><strong><?php echo _e('Plugin deactivation', DM_OCA); ?></strong></p>
          <p>
          <Input type='checkbox' Name='ecpm_ddc_move_back_data' <?php echo ($ecpm_ddc_move_back_data == 'on' ? 'checked':'') ;?> >
          <?php echo _e('Move daily counters data back', DM_OCA); ?><br>
          <Input type='checkbox' Name='ecpm_ddc_remove_data' <?php echo ($ecpm_ddc_remove_data == 'on' ? 'checked':'') ;?> >
          <?php echo _e('Remove all settings', DM_OCA); ?>
          </p>
 				  <input type="submit" id="ecpm_ddc_submit" name="ecpm_ddc_submit" class="button-primary" value="<?php _e('Save settings', ECPM_DDC); ?>" />
  			</form>
        </td>
        <td valign="top" align="left">
          <h3><?php echo sprintf( __( 'Maximum time for getting daily counts: %s', ECPM_DDC ), '<font size=+1>'.round(get_option('ecpm_ddc_max_time'), 4).'</font>' );?></h3>
          <h3><?php echo sprintf( __( 'Minimum time for getting daily counts: %s', ECPM_DDC ), '<font size=+1>'.round(get_option('ecpm_ddc_min_time'), 4).'</font>' );?></h3>
          <h3><?php echo sprintf( __( 'Time gained for every visitor (in seconds): %s', ECPM_DDC ), '<font size=+2>'.round($time_diff, 4).'</font>' );?></h3>
          <h3><?php echo sprintf( __( 'Average time gained in one day (%s hits): %s', ECPM_DDC ), $ecpm_ddc_avg_hits, '<font size=+2>'.$final.'</font>' );?></h3>
          <hr>
          <form id='ddcmoveform' method="post" action="">
            <h3><?php echo sprintf( __( 'Records currently in the table: %s', ECPM_DDC ), '<font size=+2>'.ecpm_get_data('count').'</font>' );?></h3>
				    <input type="submit" id="ecpm_ddc_submit_move" name="ecpm_ddc_submit_move" class="button-primary" value="<?php _e('Move records now', ECPM_DDC); ?>" />
          </form>
        </td></tr></table>
        <hr>
        
        <h3><?php echo _e( 'Ad hit statistics', ECPM_DDC );?></h3>
        <?php
        if ( ddc_is_pro() ) {
          ecpm_stats();
        } else {
          ?>
          <p align="center"><img src="<?php echo plugins_url( 'images/screenshot-pro.png', __FILE__ );?>"></p>
          <h3 align="center"><font color="darkred">This is not live data from your site!</font></h3>
          <h3 align="center">To see real statistics data about ad visits,<br>please purchase a PRO version of this plugin.</h3>
          <p align="center">
          <a href="http://www.easycpmods.com/plugin-move-daily-counters/" target="_blank"><img src="<?php echo plugins_url( 'images/pay-pal-paynow-button.png', __FILE__ );?>" border="0"></a>
          </p>
          <?php
        }  
        ?>
  		</div>
  	</div>
  <?php
  }
  ?>