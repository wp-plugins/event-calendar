<?php
/*
Copyright (c) 2005-2008, Alex Tingle.

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


/** Returns the event calendar navigation controls. */
function ec3_get_calendar_nav($date,$num_months,$cal_id=false)
{
  global $ec3;
  $idprev = '';
  $idnext = '';
  if(empty($cal_id))
  {
    $ec3previd    = "ec3_prev";
    $ec3nextid    = "ec3_next";
    $ec3spinnerid = "ec3_spinner";
    $ec3publishid = "ec3_publish";
  }
  else
  {
    $ec3previd    = "$cal_id-ec3_prev";
    $ec3nextid    = "$cal_id-ec3_next";
    $ec3spinnerid = "$cal_id-ec3_spinner";
    $ec3publishid = "$cal_id-ec3_publish";
    if($cal_id=='wp-calendar')
    {
      // For compatibility with standard wp-calendar.
      $idprev = " id='prev'";
      $idnext = " id='next'";
    }
  }
  $nav = "<table class='nav'><tbody><tr>\n";

  // Previous
  $prev=$date->prev_month();
  $nav .= "\t<td$idprev><a id='$ec3previd' href='" . $prev->month_link() . "'"
     . '>&laquo;&nbsp;' . $prev->month_abbrev() . "</a></td>\n";

  $nav .= "\t<td><img id='$ec3spinnerid' style='display:none' src='" 
     . $ec3->myfiles . "/ec_load.gif' alt='spinner' />\n";
  // iCalendar link.
  $webcal=get_feed_link('ical');
  // Macintosh always understands webcal:// protocol.
  // It's hard to guess on other platforms, so stick to http://
  if(strstr($_SERVER['HTTP_USER_AGENT'],'Mac OS X'))
      $webcal=preg_replace('/^http:/','webcal:',$webcal);
  $nav .= "\t    <a id='$ec3publishid' href='$webcal'"
     . " title='" . __('Subscribe to iCalendar.','ec3') ."'>\n"
     . "\t     <img src='$ec3->myfiles/publish.gif' alt='iCalendar' />\n"
     . "\t    </a>\n";
  $nav .= "\t</td>\n";

  // Next
  $next=$date->plus_months($num_months);
  $nav .= "\t<td$idnext><a id='$ec3nextid' href='" . $next->month_link() . "'"
     . '>' . $next->month_abbrev() . "&nbsp;&raquo;</a></td>\n";

  $nav .= "</tr></tbody></table>\n";
  return $nav;
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


define('EC3_DEFAULT_FORMAT_SINGLE',
       '<tr class="%2$s"><td colspan="3">%1$s</td></tr>');
define('EC3_DEFAULT_FORMAT_RANGE',
       '<tr class="%4$s"><td class="ec3_start">%1$s</td>'
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
