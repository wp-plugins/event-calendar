<?php
/*
Copyright (c) 2008, Alex Tingle.  $Revision$

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

  //
  // Generate the iCalendar

  $name=preg_replace('/([\\,;])/','\\\\$1',get_bloginfo_rss('name'));
  $filename=preg_replace('/[^0-9a-zA-Z]/','',$name).'.ics';

  header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
//  header('Content-Type: text/calendar; charset=' . get_option('blog_charset'));
  header('Content-Disposition: inline; filename=' . $filename);
  header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  header('Cache-Control: no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  echo "BEGIN:VCALENDAR\r\n";
  echo "VERSION:2.0\r\n";
  echo "X-WR-CALNAME:$name\r\n";

  global $ec3,$wpdb;

  remove_filter('the_content','ec3_filter_the_content',20);
  remove_filter('get_the_excerpt', 'ec3_get_the_excerpt');
  add_filter('get_the_excerpt', 'wp_trim_excerpt');

  $month_ago = ec3_strftime('%Y-%m-%d',time()-(3600*24*31));
  query_posts('ec3_after='.$month_ago.'&nopaging=1');
  if(have_posts())
  {
    for($evt=ec3_iter_all_events(); $evt->valid(); $evt->next())
    {
      // ?? Should add line folding at 75 octets at some time as per RFC 2445.
      $summary=preg_replace('/([\\,;])/','\\\\$1',get_the_title());
      $permalink=get_permalink();
      $entry =& $ec3->event;

      echo "BEGIN:VEVENT\r\n";
      echo "SUMMARY:$summary\r\n";
      echo "URL;VALUE=URI:$permalink\r\n";
      echo "UID:$entry->sched_id-$permalink\r\n";
      $description='';
      $excerpt = get_the_excerpt();
      if(strlen($excerpt)>0)
      {
        // I can't get iCal to understand iCalendar encoding.
        // So just strip out newlines here:
        $description=preg_replace('/[ \r\n]+/',' ',$excerpt.' ');
        $description=preg_replace('/([\\,;])/','\\\\$1',$description);
      }
      $description.='['.sprintf(__('by: %s'),get_the_author_nickname()).']';
      echo "DESCRIPTION:$description\r\n";
      if($entry->allday)
      {
        $dt_start=mysql2date('Ymd',$entry->start);
        $dt_end=date('Ymd', mysql2date('U',$entry->end)+(3600*24) );
        echo "TRANSP:TRANSPARENT\r\n"; // for availability.
        echo "DTSTART;VALUE=DATE:$dt_start\r\n";
        echo "DTEND;VALUE=DATE:$dt_end\r\n";
      }
      else
      {
        echo "TRANSP:OPAQUE\r\n"; // for availability.
        // Convert timestamps to UTC
        echo sprintf("DTSTART;VALUE=DATE-TIME:%s\r\n",ec3_to_utc($entry->start));
        echo sprintf("DTEND;VALUE=DATE-TIME:%s\r\n",ec3_to_utc($entry->end));
      }
      do_action('ical_item');
      echo "END:VEVENT\r\n";
    }
  }
  echo "END:VCALENDAR\r\n";

?>
