<?php
/*
Copyright (c) 2008, Alex Tingle.  $Revision: 287 $

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


/**

Glossary:

 date         - string in the form "YYYY-MM-DD" (same as MySQL)
 datetime, m  - string in the form "YYYY-MM-DD HH-mm-ss" (same as MySQL)
 unixtime, u  - integer, seconds since the epoch.
 day_id       - string in the form "ec3_YYYY_MM_DD"
 month_id     - string in the form "ec3_YYYY_MM"
 dateobj, dob - ec3_Date object.

 */


class ec3_CalendarDay
{
  var $_date; // MySQL date "YYYY-MM-DD"
  var $_posts;
  var $_events;
  var $_events_allday;

  function ec3_CalendarDay($date)
  {
    $this->_date = $date;
    $this->_posts = array();
    $this->_events = array();
    $this->_events_allday = array();
  }
  
  function add_post(&$post)
  {
    $this->_posts[] = $post;
  }
  
  function add_event(&$event)
  {
    if(empty($event->allday))
      $this->_events[] = $event;
    else
      $this->_events_allday[] = $event;
  }
  
  function has_events()
  {
    return !( empty($this->_events_allday) && empty($this->_events) );
  }

  function has_posts()
  {
    return !empty($this->_posts);
  }

  function is_empty()
  {
    return !( $this->has_posts() || $this->has_events() );
  }

  function iter_events()
  {
    global $ec3;
    $ec3->events = $this->_events;
    return new ec3_EventIterator();
  }

  // ... More goes here

};


/** Calendar class, used for rendering calendars. */
class ec3_Calendar
{
  /** First date covered by this calendar (always 1st of the month).
   *  An ec3_Date object. */
  var $begin_dateobj;
  /** Next date AFTER the range covered by this calendar (always 1st of the
   *  month). An ec3_Date object. */
  var $limit_dateobj;

  /** Map of ec3_CalendarDay objects. */
  var $_days = false;

  /** $month_date is a string of the form "YYYY-MM..."
   *  $num_months is the number of months covered by the calendar.*/
  function ec3_Calendar($month_date,$num_months=1)
  {
    $parts=explode('-',$month_date);
    $year_num =intval($parts[0]);
    $month_num=intval($parts[1]);
    $this->begin_dateobj = new ec3_Date($year_num,$month_num,1);
    $this->limit_dateobj = $this->begin_dateobj->plus_months($num_months);
    $this->_days = array();
  }
  
  function &_get_day($mysqldate)
  {
    if(!isset($this->_days[$mysqldate]))
        $this->_days[$mysqldate] = new ec3_CalendarDay($mysqldate);;
    return $this->_days[$mysqldate];
  }
  
  function add_posts(&$query)
  {
    foreach($query->posts as $p)
    {
      $day =& $this->_get_day( substr($p->post_date,0,10) );
      $day->add_post($p);
    }
  }
  
  function add_events(&$query)
  {
    foreach($query->posts as $p)
    {
      if(empty($p->ec3_schedule))
        continue;
      $begin_datetime = $this->begin_dateobj->to_mysqldate();
      foreach($p->ec3_schedule as $event)
      {
        $dob     = ec3_mysql2date( max($event->start,$begin_datetime) );
        $end_dob = ec3_mysql2date( $event->end );
        while( $dob->less_than($this->limit_dateobj) )
        {
          $day =& $this->_get_day($dob->to_mysqldate());
          $day->add_event($event);
          if( $dob->equals($end_dob) )
            break;
          $dob->increment_day();
        }
      }
    }
  }

  function wrap_month($monthstr,$dateobj)
  {
    return $dateobj->month_name().' '.$dateobj->year_num."\n".$monthstr."\n";
  }
  
  function wrap_week($weekstr,$dateobj)
  {
    return $weekstr."\n";
  }
  
  /** dayobj - ec3_CalendarDay object, may be empty. */
  function wrap_day($daystr,$dateobj,$dayobj)
  {
    return $daystr.' ';
  }

  function make_pad($num_days,$is_start_of_month)
  {
    return substr('                              ',0,$num_days*3);
  }
  
  /** Second param may be empty. */
  function make_day($dateobj,$dayobj)
  {
    return zeroise($dateobj->day_num,2);
  }

  function generate()
  {
    $result='';
    $dateobj = $this->begin_dateobj;
    while($dateobj->less_than($this->limit_dateobj))
    {
      $days_in_month =$dateobj->days_in_month();
      $week_day=( $dateobj->week_day() + 7 - intval(get_option('start_of_week')) ) % 7;
      $col =0;

      $monthstr= '';
      $weekstr = '';

      while(True)
      {
        if($col>6)
        {
          $monthstr .= $this->wrap_week($weekstr,$dateobj);
          $weekstr = '';
          $col=0;
        }
        if($col<$week_day)
        {
          // insert padding
          $weekstr .= $this->make_pad( $week_day - $col, true );
          $col=$week_day;
        }
        // insert day
        $datetime = $dateobj->to_mysqldate();
        $dayobj = $this->_days[$datetime]; // might be empty
        $daystr = $this->make_day($dateobj,$dayobj);
        $weekstr .= $this->wrap_day($daystr,$dateobj,$dayobj);

        $col++;
        $last_dob = $dateobj;
        $dateobj->increment_day();
        if(1==$dateobj->day_num)
            break;
        $week_day=($week_day+1) % 7;
      }
      // insert padding
      $weekstr .= $this->make_pad( 7 - $col, false );
      $monthstr .= $this->wrap_week($weekstr,$last_dob);
      $result .= $this->wrap_month($monthstr,$last_dob);
    }
    return $result;
  }

};

