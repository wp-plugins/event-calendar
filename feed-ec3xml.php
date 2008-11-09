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

class ec3_ec3xml extends ec3_BasicCalendar
{
  var $details = array();

  function ec3_ec3xml($options=false,$datetime=0)
  {
    $this->ec3_BasicCalendar($options,$datetime);
  }

  function wrap_month($monthstr)
  {
    return "<month id='".$this->dateobj->month_id()."'>\n"
           . $monthstr
           . "</month>\n";
  }
  
  function wrap_week($weekstr)
  {
    return $weekstr;
  }
  
  function wrap_day($daystr)
  {
    if(empty($this->dayobj))
      return $daystr;

    $day_id   = $this->dateobj->day_id();
    $date     = $this->dateobj->to_mysqldate();
    $day_link = $this->dateobj->day_link($this->show_only_events);
    $result ="<day id='$day_id' date='$date' link='$day_link'";
    if(!empty($this->dayobj->titles))
      $result.=" titles='".implode(', ',$this->dayobj->titles)."'";
    if($this->dayobj->has_events())
      $result.=" is_event='1'";
    if(empty($daystr))
      $result .= "/>\n";
    else
      $result .= ">\n".$daystr."</day>\n";
    return $result;
  }

  function make_pad($num_days,$is_start_of_month)
  {
    return '';
  }

  function make_event(&$event)
  {
    global $id;
    $this->_add_detail();
    $result = " <event post_id='pid_$id'";
    $result .= " sched_id='sid_$event->sched_id'";
    if($event->allday)
    {
      $result .= " allday='0'>\n";
    }
    else
    {
      $result .= ">\n";
      if(substr($event->start,0,10) < $this->dayobj->date)
        $result.= "  <end>$event->end</end>\n";
      elseif(substr($event->end,0,10) > $this->dayobj->date)
        $result.= "  <start>$event->start</start>\n";
      else
        $result.= "  <start>$event->start</start>\n  <end>$event->end</end>\n";
    }
    $result .= " </event>\n";
    return $result;
  }

  function make_post(&$post)
  {
    global $id;
    $this->_add_detail();
    $result = " <post post_id='pid_$id' />\n";
    return $result;
  }
    
  function _add_detail()
  {
    global $id, $post;

    // Record the post's title for today.
    $title=get_the_title();
    if(empty($this->dayobj->titles))
      $this->dayobj->titles = array();
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
    if(!empty($post->ec3_schedule))
      $safe_title .= ' @'.ec3_get_start_time();
    $this->dayobj->titles[] = $safe_title;

    // Make a unique <detail> element.
    if(array_key_exists($id,$this->details))
      return;

    $link=get_permalink(); 
    $d = " <detail id='pid_$id' title='$title' link='$link'";
    $excerpt = get_the_excerpt();
    if(empty($excerpt))
      $d .= " />\n";
    else
      $d .= "><excerpt><![CDATA[$excerpt]]></excerpt></detail>\n";
    $this->details[$id] = $d;
  }
}; // end class ec3_ec3xml


@header('Content-type: text/xml; charset=' . get_option('blog_charset'));
echo '<?xml version="1.0" encoding="'.get_option('blog_charset')
.    '" standalone="yes"?>'."\n";

// Turn off EC's content filtering.
remove_filter('the_content','ec3_filter_the_content',20);
remove_filter('get_the_excerpt', 'ec3_get_the_excerpt');
add_filter('get_the_excerpt', 'wp_trim_excerpt');

global $ec3,$wp_query;
$calobj = new ec3_ec3xml();
$calobj->add_events($wp_query);
if(!ec3_is_listing_q($wp_query))
  $calobj->add_posts($wp_query,!$ec3->advanced);

?>
<calendar><?php echo $calobj->generate() ?>
<details id="details">
<?php echo implode('',$calobj->details) ?>
</details>
</calendar>
