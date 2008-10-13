<?php
/*
Plugin Name: Event Calendar Widget
Plugin URI: http://wpcal.firetree.net
Description: Adds sidebar widgets for Event Calendar and Upcoming Events. Requires the EventCalendar and <a href="http://automattic.com/code/widgets/">Widget</a> plugins (WordPress version 2.1 and earlier). After activating, please visit <a href="themes.php?page=widgets/widgets.php">Sidebar Widgets for WordPress version 2.1 and earlier</a> or <a href="widgets.php">Widgets for WordPress version 2.2 and subsequent</a> to configure and arrange your new widgets.
Author: Darrell Schulte
Version: 3.1.1
Author URI: http://wpcal.firetree.net

    This is a WordPress plugin (http://wordpress.org) and widget
    (http://automattic.com/code/widgets/).
*/

/*
Copyright (c) 2006, Darrell Schulte.  $Revision: 285 $

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

function ec3_widget_init() 
{

  if ( !function_exists('register_sidebar_widget') )
    return;

  /** Utility function: Returns $s, or if it's empty, __($default,'ec3'). */
  function ec3_default_string($s,$default)
  {
    if ( empty($s) )
        return __($default,'ec3');
    else
        return $s;
  }


  /** Event Calendar widget. */
  function ec3_widget_cal($args) 
  {
    extract($args);
    $options = get_option('ec3_widget_cal');
    echo $before_widget . $before_title;
    echo ec3_default_string($options['title'],'Event Calendar');
    echo $after_title;
    ec3_get_calendar(); 
    echo $after_widget;
  }

  function ec3_widget_cal_control() 
  {
    $options = $newoptions = get_option('ec3_widget_cal');
    if ( $_POST["ec3_cal_submit"] ) 
    {
      $newoptions['title']=strip_tags(stripslashes($_POST["ec3_cal_title"]));
    }
    if ( $options != $newoptions ) 
    {
      $options = $newoptions;
      update_option('ec3_widget_cal', $options);
    }
    $title = ec3_default_string($options['title'],'Event Calendar');
    ?>
    <p>
     <label for="ec3_cal_title">
      <?php _e('Title:'); ?>
      <input class="widefat" id="ec3_cal_title" name="ec3_cal_title" type="text" value="<?php echo htmlspecialchars($title,ENT_QUOTES); ?>" />
     </label>
    </p>

    <p><a href="options-general.php?page=ec3_admin">
      <?php _e('Go to Event Calendar Options','ec3') ?>.</a>
    </p>

    <input type="hidden" name="ec3_cal_submit" value="1" />
    <?php
  }

  wp_register_sidebar_widget( 
	'event-calendar', __('Event Calendar','ec3'), 'ec3_widget_cal', 
	array('description' => __( 'A calendar of events (Event Calendar Plugin)', 'ec3') ) 
  );

  register_widget_control(
    array(__('Event Calendar','ec3'),'widgets'),
    'ec3_widget_cal_control'
  );


  /** Upcoming Events widget. */
  function ec3_widget_list($args) 
  {
    extract($args);
    $options = get_option('ec3_widget_list');
    echo $before_widget . $before_title;
    echo ec3_default_string($options['title'],'Upcoming Events');
    echo $after_title;
    ec3_get_events($options['limit']); 
    echo $after_widget;
  }

  function ec3_widget_list_control() 
  {
    $options = $newoptions = get_option('ec3_widget_list');
    if ( $_POST["ec3_list_submit"] ) 
    {
      $newoptions['title'] = strip_tags(stripslashes($_POST["ec3_list_title"]));
      $newoptions['limit'] = strip_tags(stripslashes($_POST["ec3_limit"]));
    }
    if ( $options != $newoptions ) 
    {
      $options = $newoptions;
      update_option('ec3_widget_list', $options);
    }
  
    $title = ec3_default_string($options['title'],'Upcoming Events');
    $limit = $options['limit'];
    ?>

    <p>
     <label for="ec3_list_title">
      <?php _e('Title:'); ?>
      <input class="widefat" id="ec3_list_title" name="ec3_list_title" type="text" value="<?php echo htmlspecialchars($title,ENT_QUOTES); ?>" />
     </label>
    </p>
    <p>
     <label title="Eg. '5', '5 days', '5d'" for="ec3_limit"><?php _e('&#035; of events:','ec3'); ?>
      <br />
      <input class="widefat" style="width: 50px; text-align: center;" id="ec3_limit" name="ec3_limit" type="text" value="<?php echo $limit? $limit: '5'; ?>" />
     </label>
      <br />
      <small>To display recent past events,<br />use a negative number (e.g., -5)</small>
    </p>
    
    <p>
      <a href="options-general.php?page=ec3_admin"><?php _e('Go to Event Calendar Options','ec3') ?>.</a>
    </p>

    <input type="hidden" name="ec3_list_submit" value="1" />

    <?php
  }

	wp_register_sidebar_widget( 
	  'upcoming-events', __('Upcoming Events','ec3'), 'ec3_widget_list', 
	  array('description' => __( 'A list of events (Event Calendar Plugin)', 'ec3') )
  );

  register_widget_control(
    array(__('Upcoming Events','ec3'),'widgets'),
    'ec3_widget_list_control'
  );
}

add_action('widgets_init', 'ec3_widget_init');

?>