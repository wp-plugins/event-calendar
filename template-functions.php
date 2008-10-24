<?php
/*
Copyright (c) 2005-2007, Alex Tingle.  $Revision: 287 $

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


/** Report an error if EventCalendar not yet installed. */
function ec3_check_installed($title)
{
  global $ec3;
  if(!$ec3->event_category)
  {?>
    <div style="background-color:black; color:red; border:2px solid red; padding:1em">
     <div style="font-size:large"><?php echo $title; ?></div>
     <?php _e('You must choose an event category.','ec3'); ?>
     <a style="color:red; text-decoration:underline" href="<?php echo
       get_option('home');?>/wp-admin/options-general.php?page=ec3_admin">
      <?php _e('Go to Event Calendar Options','ec3'); ?>
     </a>
    </div>
   <?php
  }
  return $ec3->event_category;
}


/** Calculate the header (days of week). */
function ec3_util_thead()
{
  global
    $ec3,
    $weekday,
    $weekday_abbrev,
    $weekday_initial;

  $result="<thead><tr>\n";

  $start_of_week =intval( get_option('start_of_week') );
  for($i=0; $i<7; $i++)
  {
    $full_day_name=$weekday[ ($i+$start_of_week) % 7 ];
    if(3==$ec3->day_length)
        $display_day_name=$weekday_abbrev[$full_day_name];
    elseif($ec3->day_length<3)
        $display_day_name=$weekday_initial[$full_day_name];
    else
        $display_day_name=$full_day_name;
    $result.="\t<th abbr='$full_day_name' scope='col' title='$full_day_name'>"
           . "$display_day_name</th>\n";
  }

  $result.="</tr></thead>\n";
  return $result;
}


/** Echos the event calendar navigation controls. */
function ec3_get_calendar_nav($date,$num_months)
{
  global $ec3;
  echo "<table class='nav'><tbody><tr>\n";

  // Previous
  $prev=$date->prev_month();
  echo "\t<td id='prev'><a id='ec3_prev' href='" . $prev->month_link() . "'"
     . '>&laquo;&nbsp;' . $prev->month_abbrev() . "</a></td>\n";

  echo "\t<td><img id='ec3_spinner' style='display:none' src='" 
     . $ec3->myfiles . "/ec_load.gif' alt='spinner' />\n";
  // iCalendar link.
  $webcal=get_feed_link('ical');
  // Macintosh always understands webcal:// protocol.
  // It's hard to guess on other platforms, so stick to http://
  if(strstr($_SERVER['HTTP_USER_AGENT'],'Mac OS X'))
      $webcal=preg_replace('/^http:/','webcal:',$webcal);
  echo "\t    <a id='ec3_publish' href='$webcal'"
     . " title='" . __('Subscribe to iCalendar.','ec3') ."'>\n"
     . "\t     <img src='$ec3->myfiles/publish.gif' alt='iCalendar' />\n"
     . "\t    </a>\n";
  echo "\t</td>\n";

  // Next
  $next=$date->plus_months($num_months);
  echo "\t<td id='next'><a id='ec3_next' href='" . $next->month_link() . "'"
     . '>' . $next->month_abbrev() . "&nbsp;&raquo;</a></td>\n";

  echo "</tr></tbody></table>\n";
}


/** Generates an array of all 'ec3_Day's between the start of
 *  begin_month & end_month. Indexed by day_id.
 *  month_id is in the form: ec3_<year_num>_<month_num> */
function ec3_util_calendar_days($begin_month_id,$end_month_id)
{
  $begin_date=date('Y-m-d 00:00:00',ec3_dayid2php($begin_month_id));
  $end_date  =date('Y-m-d 00:00:00',ec3_dayid2php($end_month_id));
  global $ec3, $wpdb;

  $sql=
    "SELECT DISTINCT
       id,
       post_title,
       GREATEST(start,'$begin_date') AS start_date,
       LEAST(end,'$end_date') AS end_date,
       allday,
       1 AS is_event
     FROM $wpdb->posts,$ec3->schedule
     WHERE post_status='publish'
       AND post_type='post'
       AND post_id=id
       AND end>='$begin_date'
       AND start<'$end_date'";
  if(!$ec3->show_only_events)
  {
    // We are interested in normal posts, as well as events.
    $sql="( $sql ) UNION
     ( SELECT DISTINCT
         id,
         post_title,
         post_date AS start_date,
         post_date AS end_date,
         0 AS allday,
         0 AS is_event
       FROM $wpdb->posts
       WHERE post_status='publish'
         AND post_type='post'
         AND post_date>='$begin_date'
         AND post_date<'$end_date'
         AND post_date<NOW()
     )";
  }
  $sql.=' ORDER BY id, allday DESC, start_date, is_event DESC';
  $calendar_entries = $wpdb->get_results($sql);

  $calendar_days = array(); // result
  if(!$calendar_entries)
      return $calendar_days;

  // In advanced mode, we don't want to show events as blog posts in the cal.
  $ignore_post_ids=array();
  if($ec3->advanced && !$ec3->show_only_events)
  {
    foreach($calendar_entries as $ent)
      if($ent->is_event)
        $ignore_post_ids[] = $ent->id;
  }

  $current_post_id=0;
  $current_day_id ='';
  $time_format=get_option('time_format');
  $allday=str_replace(' ','&#160;',__('all day','ec3')); // #160==nbsp
  foreach($calendar_entries as $ent)
  {
    if(!$ent->is_event && in_array($ent->id,$ignore_post_ids))
        continue;
    if($current_post_id!=$ent->id)
    {
      $current_post_id=$ent->id;
      $current_day_id='';
    }
    $date=ec3_mysql2date($ent->start_date);
    $end_date=ec3_mysql2date($ent->end_date);
    while(true)
    {
      $day_id=$date->day_id();
      if($current_day_id==$day_id)
          break;
      $current_day_id=$day_id;
      if(empty($calendar_days[$day_id]))
          $calendar_days[$day_id] = new ec3_Day();

      if($ent->allday)
          $time=$allday;
      else
          $time=mysql2date($time_format,$ent->start_date);
      //?? Should only record start time on FIRST day.
      $calendar_days[$day_id]->add_post($ent->post_title,$time,$ent->is_event);
      if($date->equals($end_date))
        break;
      $date->increment_day();
    }
  }
  return $calendar_days;
}

/** Echos one event calendar month table. */
function ec3_get_calendar_month($date,$calendar_days,$thead)
{
  global $ec3;
  //
  // Table start.
  $title=
    sprintf(__('View posts for %1$s %2$s'),$date->month_name(),$date->year_num);
  echo "<table id='" . $date->month_id() . "'>\n<caption>"
    . '<a href="' . $date->month_link() . '" title="' . $title . '">'
    . $date->month_name() . ' ' . $date->year_num . "</a></caption>\n";
  echo $thead;

  //
  // Table body
  echo "<tbody>\n\t<tr>";

  $days_in_month =$date->days_in_month();
  $week_day=( $date->week_day() + 7 - intval(get_option('start_of_week')) ) % 7;
  $col =0;
  
  while(True)
  {
    if($col>6)
    {
      echo "</tr>\n\t<tr>";
      $col=0;
    }
    if($col<$week_day)
    {
      // insert padding
      $pad=$week_day-$col;
      echo "<td colspan='$pad' class='pad'>&nbsp;</td>";
      $col=$week_day;
    }
    // insert day
    $day_id = $date->day_id();
    echo "<td id='$day_id'";

    if(array_key_exists($day_id,$calendar_days))
    {
      echo ' class="ec3_postday';
      if($calendar_days[$day_id]->is_event)
          echo ' ec3_eventday';
      echo '">';
      echo '<a href="' . $date->day_link()
         . '" title="' . $calendar_days[$day_id]->get_titles() . '"';
      if($calendar_days[$day_id]->is_event)
          echo ' class="eventday"';
      echo ">$date->day_num</a>";
    }
    else
    {
      echo '>' . $date->day_num;
    }

    echo '</td>';

    $col++;
    $date->increment_day();
    if(1==$date->day_num)
        break;
    $week_day=($week_day+1) % 7;
  }
  // insert padding
  $pad=7-$col;
  if($pad>1)
      echo "<td colspan='$pad' class='pad' style='vertical-align:bottom'>"
      . "<a href='http://blog.firetree.net/?ec3_version=$ec3->version'"
      . " title='Event Calendar $ec3->version'"
      . ($ec3->hide_logo? " style='display:none'>": ">")
      . "<span class='ec3_ec'><span>EC</span></span></a></td>";
  elseif($pad)
      echo "<td colspan='$pad' class='pad'>&nbsp;</td>";

  echo "</tr>\n</tbody>\n</table>";
}


/** Template function. Call this from your template to insert the
 *  Event Calendar. */
function ec3_get_calendar()
{
  if(!ec3_check_installed(__('Event Calendar','ec3')))
    return;
  global $ec3;

  // Can't cope with more than one calendar on the same page. Everything has
  // a unique ID, so it can't be duplicated.
  // Simple fix for problem: Just ignore all calls after the first.
  $ec3->call_count++;
  if($ec3->call_count>1)
  {
    echo "<!-- You can only have one Event Calendar on each page. -->\n";
    return;
  }

  echo "<div id='wp-calendar'>\n";

  $this_month = new ec3_Date();

  // Display navigation panel.
  if(0==$ec3->navigation)
    ec3_get_calendar_nav($this_month,$ec3->num_months);
  
  // Get entries
  $end_month=$this_month->plus_months($ec3->num_months);
  $calendar_days =
    ec3_util_calendar_days(
      $this_month->month_id(),
      $end_month->month_id()
    );

  // Display months.
  $thead=ec3_util_thead();
  for($i=0; $i<$ec3->num_months; $i++)
  {
    $next_month=$this_month->next_month();
    ec3_get_calendar_month($this_month,$calendar_days,$thead);
    $this_month=$next_month;
  }

  // Display navigation panel.
  if(1==$ec3->navigation)
    ec3_get_calendar_nav(new ec3_Date(),$ec3->num_months);

  echo "</div>\n";

  if(!$ec3->disable_popups)
    echo "\t<script type='text/javascript' src='"
    .    $ec3->myfiles . "/popup.js'></script>\n";
}


/** Substitutes placeholders like '%key%' in $format with 'value' from $data
 *  array. */
function ec3_format_str($format,$data)
{
  foreach($data as $k=>$v)
      $format=str_replace("%$k%",$v,$format);
  return $format;
}


define('EC3_DEFAULT_TEMPLATE_EVENT','<a href="%LINK%">%TITLE% (%TIME%)</a>');
define('EC3_DEFAULT_TEMPLATE_DAY',  '%DATE%:');
define('EC3_DEFAULT_DATE_FORMAT',   'j F');
define('EC3_DEFAULT_TEMPLATE_MONTH','');
define('EC3_DEFAULT_MONTH_FORMAT',  'F Y');


define('EC3_DEFAULT_FORMAT_SINGLE','<tr><td colspan="3">%s</td></tr>');
define('EC3_DEFAULT_FORMAT_RANGE','<tr><td class="ec3_start">%1$s</td>'
 . '<td class="ec3_to">%3$s</td><td class="ec3_end">%2$s</td></tr>');
define('EC3_DEFAULT_FORMAT_WRAPPER','<table class="ec3_schedule">%s</table>');

/** Echos the schedule for the current post. */
function ec3_the_schedule(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  echo ec3_get_schedule($format_single,$format_range,$format_wrapper);
}

?>
