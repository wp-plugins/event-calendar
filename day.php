<?php
/*
Copyright (c) 2005, Alex Tingle.  $Revision: 275 $

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

/** Represents all posts from a particular day.
 *  Generated by ec3_util_calendar_days(). */

class ec3_Day_Event
{
//Matthew: Adding this class for more flexibility in calendar displays.
 var $title;
 var $time;
 var $is_event;
 var $allday = 0;
 var $id;
 function ec3_Day_Event(){}
 
}
 
class ec3_Day
{
  var $is_event =False;
  var $events   =array(); //Matthew: Changed from "titles", as this now holds objects.  (this is an array of objects)
  function ec3_Day(){}
  function add_post($title,$time,$is_event,$allday,$id) //Matthew: add allday and id
  {
    $safe_title=strip_tags($title);
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
    if($is_event)
    {
      //Matthew: BUGFIX: don't include "@ time" in title.
      //$safe_title.=' @'.$time;  //don't put times here anymore
      $this->is_event=True;
    }
    $nextEvent = count($this->events);
    //Matthew: set the (new) event object
    $this->events[$nextEvent] = new ec3_Day_Event();
    $this->events[$nextEvent]->title = $safe_title;
    $this->events[$nextEvent]->time = $time;
    $this->events[$nextEvent]->is_event = $is_event;
    $this->events[$nextEvent]->allday = $allday;
    $this->events[$nextEvent]->id = $id;
  }
  function get_titles()
  {
    //Matthew: update to use new array of objects
    $temporary_title_array = array();
    foreach ($this->events as $key=>&$val) { //Thus, $val becomes a pointer to the ec3_Day_Event object.
     //This loops through the events
    if($val->allday) {
     $temporary_title_array[] = $val->title . ' ' . $val->time;
       } else {
     $temporary_title_array[] = $val->title . ': ' . $val->time;
     }
    }
    return implode(', ',$temporary_title_array);
  }
}

?>
