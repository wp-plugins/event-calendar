<?php
/*
Copyright (c) 2008, Alex Tingle.

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

require_once(dirname(__FILE__).'/calendar-basic.php');

/** Renders a sidebar calendar. */
class ec3_SidebarCalendar extends ec3_BasicCalendar
{
  /** Universal table header. */
  var $thead;

  function ec3_SidebarCalendar($datetime=0,$num=1)
  {
    // Initialise the parent class.
    $this->ec3_BasicCalendar($datetime,$num);

    // Make the table header (same for every month).
    global $ec3,$weekday,$weekday_abbrev,$weekday_initial;
    $this->thead="<thead><tr>\n";
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
      $this->thead.="\t<th abbr='$full_day_name' scope='col' title='$full_day_name'>"
             . "$display_day_name</th>\n";
    }
    $this->thead.="</tr></thead>\n";
  }

  function wrap_month($monthstr)
  {
    // Make a table for this month.
    $title = sprintf(
      __('View posts for %1$s %2$s'),$this->dateobj->month_name(),$this->dateobj->year_num);
    $result =  '<table id="'.$this->id.'-'.$this->dateobj->month_id().'">'."\n"
      . '<caption>'
      . '<a href="' . $this->dateobj->month_link() . '" title="' . $title . '">'
      . $this->dateobj->month_name() . ' ' . $this->dateobj->year_num . "</a>"
      . "</caption>\n"
      . $this->thead
      . "<tbody>\n" . $monthstr . "</tbody>\n</table>\n";
    return $result;
  }

  function wrap_week($weekstr)
  {
    return "\t<tr>$weekstr</tr>\n";
  }
  
  function make_pad($num_days,$is_start_of_month)
  {
    global $ec3;
    if(!$is_start_of_month && $num_days>1)
    {
      return
        "<td colspan='$num_days' class='pad' style='vertical-align:bottom'>"
        . "<a href='http://wpcal.firetree.net/?ec3_version=$ec3->version'"
        . " title='Event Calendar $ec3->version'"
        . ($ec3->hide_logo? " style='display:none'>": ">")
        . "<span class='ec3_ec'><span>EC</span></span></a></td>";
    }
    else
    {
      return "<td colspan='$num_days' class='pad'>&nbsp;</td>";
    }
  }
  
  /** dayobj - ec3_CalendarDay object, may be empty. */
  function wrap_day($daystr)
  {
    $day_id = $this->dateobj->day_id();
    $td_attr = ' id="'.$this->id.'-'.$day_id.'"';
    $td_classes = array();
    if($day_id=='today')
      $td_classes[] = 'ec3_today';
    if(!empty($this->dayobj))
    {
      $td_classes[] = 'ec3_postday';
      $a_attr = ' href="'.$this->dateobj->day_link().'" title="'.$daystr.'"';
      if($this->dayobj->has_events())
      {
        $td_classes[] = 'ec3_eventday';
        $a_attr  .= ' class="eventday"';
      }
      $daystr = "<a$a_attr>" . $this->dateobj->day_num . '</a>';
    }
    else
    {
      $daystr = $this->dateobj->day_num;
    }
    if(!empty($td_classes))
      $td_attr .= ' class="' . implode(' ',$td_classes) . '"';
    return "<td$td_attr>$daystr</td>";
  }

  function make_event(&$event)
  {
    global $post;
    return $this->make_post($post) . ' @' . ec3_get_start_time();
  }

  function make_post(&$post)
  {
    $safe_title=strip_tags(get_the_title());
    $safe_title=
      str_replace(
        array(',','@'),
        ' ',
        htmlspecialchars(
          stripslashes($safe_title),
          ENT_QUOTES,
          get_option('blog_charset')
        )
      );
    return $safe_title;
  }

  function generate($cal_id)
  {
    global $ec3;
    $this->id = $cal_id;
    $result = "<div id='$this->id'>\n";

    // Display navigation panel.
    $nav=ec3_get_calendar_nav($this->begin_dateobj,$ec3->num_months,$this->id);
    if(0==$ec3->navigation)
      $result .= $nav;

    $q = 'ec3_after='  .$this->begin_dateobj->to_mysqldate()
       . '&ec3_before='.$this->limit_dateobj->to_mysqldate()
       . '&nopaging=1';
    if(!$ec3->show_only_events)
        $q .= '&ec3_listing=no';
    $query = new WP_Query();
    $query->query($q);

    $this->add_events($query);
    if(!ec3_is_listing_q($query))
      $this->add_posts($query,!$ec3->advanced);
    $result .= parent::generate();

    // Display navigation panel.
    if(1==$ec3->navigation)
      $result .= $nav;

    $result .= "</div>\n";

    if(!$ec3->disable_popups && empty($ec3->done_popups_javascript))
    {
      $ec3->done_popups_javascript=true;
      $result .= "\t<script type='text/javascript' src='"
      .    $ec3->myfiles . "/popup.js'></script>\n";
    }
    $result .= "\t<script type='text/javascript'><!--\n"
      .        "\t  ec3.new_calendar('$cal_id');\n"
      .        "\t--></script>\n";
    return $result;
  }

};

