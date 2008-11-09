<?php
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


/** Utility function: Gets the (possibly translated) widget title, given the
 *  value of the 'title' option. */
function ec3_widget_title($title,$default)
{
  if ( empty($title) )
      return __($default,'ec3');
  else
      return apply_filters('widget_title',$title);
}


/** Event Calendar widget. */
function ec3_widget_cal($args) 
{
  extract($args);
  $options = get_option('ec3_widget_cal');
  echo $before_widget . $before_title;
  echo ec3_widget_title($options['title'],'Event Calendar');
  echo $after_title;
  if(ec3_check_installed(__('Event Calendar','ec3')))
  {
    require_once(dirname(__FILE__).'/calendar-sidebar.php');
    global $ec3;
    $calobj = new ec3_SidebarCalendar(0,$options);
    echo $calobj->generate('wp-calendar');
  }
  echo $after_widget;
}


/** Event Calendar widget - control. */
function ec3_widget_cal_control() 
{
  $options = $newoptions = get_option('ec3_widget_cal');
  if( $_POST["ec3_cal_submit"] ) 
  {
    $newoptions['title']=strip_tags(stripslashes($_POST["ec3_cal_title"]));
    $newoptions['num_months']      =abs(intval($_POST["ec3_cal_num_months"]));
    $newoptions['show_only_events']=intval($_POST["ec3_cal_show_only_events"]);
    $newoptions['day_length']      =abs(intval($_POST["ec3_cal_day_length"]));
    $newoptions['hide_logo']       =intval($_POST["ec3_cal_hide_logo"]);
    $newoptions['navigation']      =intval($_POST["ec3_cal_navigation"]);
    $newoptions['disable_popups']  =intval($_POST["ec3_cal_disable_popups"]);
  }
  if( $options != $newoptions ) 
  {
    $options = $newoptions;
    update_option('ec3_widget_cal', $options);
  }
  require_once(dirname(__FILE__).'/calendar-sidebar.php');
  $title = ec3_widget_title($options['title'],'Event Calendar');
  $cal = new ec3_SidebarCalendar(0,$options); // Use this to get defaults.
  ?>
  <p>
   <label for="ec3_cal_title">
    <?php _e('Title:') ?><br />
    <input class="widefat" id="ec3_cal_title" name="ec3_cal_title"
     type="text" value="<?php echo htmlspecialchars($title,ENT_QUOTES); ?>" />
   </label>
  </p>
  <p>
   <label for="ec3_cal_num_months">
    <?php _e('Number of months','ec3') ?>:<br />
    <input class="widefat" id="ec3_cal_num_months" name="ec3_cal_num_months"
     type="text" value="<?php echo $cal->num_months ?>" />
   </label>
  </p>
  <p>
   <label for="ec3_cal_show_only_events">
    <?php _e('Show all categories in calendar','ec3') ?>:<br />
    <select name="ec3_cal_show_only_events">
     <option value='1'<?php if($cal->show_only_events) echo " selected='selected'" ?> >
      <?php _e('Only Show Events','ec3'); ?>
     </option>
     <option value='0'<?php if(!$cal->show_only_events) echo " selected='selected'" ?> >
      <?php _e('Show All Posts','ec3'); ?>
     </option>
    </select>
   </label>
  </p>
  <p>
   <label for="ec3_cal_day_length">
    <?php _e('Show day names as','ec3') ?>:<br />
    <select name="ec3_cal_day_length">
     <option value='1'<?php if($cal->day_length<3) echo " selected='selected'" ?> >
      <?php _e('Single Letter','ec3'); ?>
     </option>
     <option value='3'<?php if(3==$cal->day_length) echo " selected='selected'" ?> >
      <?php _e('3-Letter Abbreviation','ec3'); ?>
     </option>
     <option value='9'<?php if($cal->day_length>3) echo " selected='selected'" ?> >
      <?php _e('Full Day Name','ec3'); ?>
     </option>
    </select>
   </label>
  </p>
  <p>
   <label for="ec3_cal_hide_logo">
    <?php _e('Show Event Calendar logo','ec3') ?>:<br />
    <select name="ec3_cal_hide_logo">
     <option value='0'<?php if(!$cal->hide_logo) echo " selected='selected'" ?> >
      <?php _e('Show Logo','ec3'); ?>
     </option>
     <option value='1'<?php if($cal->hide_logo) echo " selected='selected'" ?> >
      <?php _e('Hide Logo','ec3'); ?>
     </option>
    </select>
   </label>
  </p>
  <p>
   <label for="ec3_cal_navigation">
    <?php _e('Position of navigation links','ec3') ?>:<br />
    <select name="ec3_navigation">
     <option value='0'<?php if(0==!$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Above Calendar','ec3'); ?>
     </option>
     <option value='1'<?php if(1==$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Below Calendar','ec3'); ?>
     </option>
     <option value='2'<?php if(2==$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Hidden','ec3'); ?>
     </option>
    </select>
    <br /><em>
     <?php _e('The navigation links are more usable when they are above the calendar, but you might prefer them below or hidden for aesthetic reasons.','ec3'); ?>
    </em> 
   </label>
  </p>
  <p>
   <label for="ec3_cal_disable_popups">
    <?php _e('Popup event lists','ec3') ?>:<br />
    <select name="ec3_cal_disable_popups">
     <option value='0'<?php if(!$cal->disable_popups) echo " selected='selected'" ?> >
      <?php _e('Show Popups','ec3'); ?>
     </option>
     <option value='1'<?php if($cal->disable_popups) echo " selected='selected'" ?> >
      <?php _e('Hide Popups','ec3'); ?>
     </option>
    </select>
    <br /><em>
     <?php _e('You might want to disable popups if you use Nicetitles.','ec3'); ?>
    </em>
   </label>
  </p>

  <input type="hidden" name="ec3_cal_submit" value="1" />
  <?php
}


/** Upcoming Events widget. */
function ec3_widget_list($args) 
{
  extract($args);
  $options = get_option('ec3_widget_list');
  echo $before_widget . $before_title;
  echo ec3_widget_title($options['title'],'Upcoming Events');
  echo $after_title;
  if(ec3_check_installed(__('Upcoming Events','ec3')))
  {
    // Parse $limit:
    //  NUMBER      - limits number of posts
    //  NUMBER days - next NUMBER of days
    $limit = $options['limit'];
    $num =intval($limit);
    $query = new WP_Query();
    if(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
        $query->query( 'ec3_days='.intval($matches[1]) );
    elseif($num>0)
        $query->query( 'ec3_after=today&posts_per_page='.$num );
    elseif($num<0)
        $query->query( 'ec3_before=today&order=asc&posts_per_page='.abs($num) );
    else
        $query->query( 'ec3_after=today&posts_per_page=5' );

    echo "<ul class='ec3_events'>";
    echo '<!-- Generated by Event Calendar v'.ec3_get_version().' -->'."\n";
    if($query->have_posts())
    {
      $current_date=false;
      for($evt=ec3_iter_all_events_q($query); $evt->valid(); $evt->next())
      {
        // Date changed?
        $date=ec3_get_date();
        if(!$current_date || $current_date!=$date)
        {
          if($current_date)
              echo "</ul></li>\n";
          echo "<li class='ec3_list ec3_list_day'>$date:'\n<ul>\n";
          $current_date=$date;
        }
        // Print the event.
        echo ' <li><a href="'.get_permalink().'">'
          .  get_the_title().' ('.ec3_get_start_time().')</a></li>'."\n";
      }
      if($current_date)
          echo "</ul></li>\n";
    }
    else
    {
      echo "<li>".__('No events.','ec3')."</li>\n";
    }
    echo "</ul>\n";
  }
  echo $after_widget;
}


/** Upcoming Events widget - control. */
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

  $title = ec3_widget_title($options['title'],'Upcoming Events');
  $limit = $options['limit'];

  $ec3_limit_title =
    __("Examples: '5', '5 days', '5d'. To display recent past events, use a negative number: '-5'.");
  ?>

  <p>
   <label for="ec3_list_title">
    <?php _e('Title:'); ?>
    <input class="widefat" id="ec3_list_title" name="ec3_list_title"
     type="text" value="<?php echo htmlspecialchars($title,ENT_QUOTES); ?>" />
   </label>
  </p>
  <p>
   <label for="ec3_limit" title="<?php echo $ec3_limit_title ?>">
    <?php _e('Number of events:','ec3'); ?>
    <input class="widefat" style="width: 50px; text-align: center;"
     id="ec3_limit" name="ec3_limit" type="text"
     value="<?php echo $limit? $limit: '5'; ?>" />
   </label>
  </p>

  <p>
    <a href="options-general.php?page=ec3_admin">
     <?php _e('Go to Event Calendar Options','ec3') ?>.</a>
  </p>

  <input type="hidden" name="ec3_list_submit" value="1" />

  <?php
}


function ec3_widgets_init() 
{
  if(!function_exists('wp_register_sidebar_widget'))
    return;

  // Event Calendar widget
  wp_register_sidebar_widget(
    'event-calendar',
    __('Event Calendar','ec3'),
    'ec3_widget_cal', 
    array('description' =>
          __( 'Display upcoming events in a dynamic calendar.','ec3')
              . ' (Event Calendar '. __('Plugin') .')' ) 
  );
  register_widget_control(
    array(__('Event Calendar','ec3'),'widgets'),
    'ec3_widget_cal_control'
  );

  // Upcoming event widget
  wp_register_sidebar_widget(
    'upcoming-events',
    __('Upcoming Events','ec3'),
    'ec3_widget_list',
    array('description' =>
          __('Display upcoming events as a list.','ec3')
              . ' (Event Calendar '. __('Plugin') .')' )
  );
  register_widget_control(
    array(__('Upcoming Events','ec3'),'widgets'),
    'ec3_widget_list_control'
  );
}

add_action('widgets_init', 'ec3_widgets_init');

?>
