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

// **************************************************
//
//        NON-FUNCTIONING!!   EXPERIMENTAL!!
// 
// **************************************************

require_once(dirname(__FILE__).'/calendar-sidebar.php');

/** Renders a big calendar. */
class ec3_BigCalendar extends ec3_SidebarCalendar
{
  function ec3_BigCalendar($options=false,$datetime=0)
  {
    // Initialise the parent class.
    $this->ec3_SidebarCalendar($options,$datetime);
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
      $a_attr = ' href="'.$this->dateobj->day_link($this->show_only_events).'" title="'.$daystr.'"';
      if($this->dayobj->has_events())
      {
        $td_classes[] = 'ec3_eventday';
        $a_attr  .= ' class="eventday"';
      }
      $daynum = "<a$a_attr>" . $this->dateobj->day_num . '</a>';
    }
    else
    {
      $daynum = $this->dateobj->day_num;
    }
    if(!empty($td_classes))
      $td_attr .= ' class="' . implode(' ',$td_classes) . '"';
    return "<td$td_attr><h4>$daynum</h4><div>$daystr</div></td>";
  }

  function make_event(&$event)
  {
    global $post;
    // MORE GOES HERE
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
    // MORE GOES HERE
    return $safe_title;
  }

  function generate()
  {
    $result = parent::generate();
    $result .= "\t<script type='text/javascript'><!--
	  ec3.calendars['$this->id'].make_day = function(td,day_xml,xml)
	  {
	    ec3.add_class(td,'ec3_postday');
	    // Save the TD's text node for later.
	    var txt=td.removeChild(td.firstChild);
	    // Make an A element
	    var a=document.createElement('a');
	    a.href=day_xml.getAttribute('link');
	    if(day_xml.getAttribute('is_event'))
	    {
	      ec3.add_class(td,'ec3_eventday');
	      a.className='eventday';
	    }
	    // Put the saves text node into the A.
	    a.appendChild(txt);
	    // Put the A into the TD.
	    td.appendChild(a);
	    // Now, make a DIV for the event details.
	    var events=day_xml.getElementsByTagName('event');
	    if(events)
	    {
	      var div=document.createElement('div');
	      for(var i=0, len=events.length; i<len; i++)
	      {
		var detail=ec3.find_detail(events[i].getAttribute('post_id'),xml);
		if(detail)
		{
		  var p=document.createElement('p');
		  p.innerHTML='<a href=\"'+ detail.getAttribute('link') +'\">'
		   + detail.getAttribute('title') + '</a>';
		  div.appendChild(p);
		}
	      }
	      td.appendChild(div);
	    }
	  }
	--></script>\n";

    // If we were in a loop, re-set the global $post.
    global $wp_query,$post;
    if($wp_query->in_the_loop)
    {
      $post = $wp_query->next_post();
      setup_postdata($post);
    }
    return $result;
  }

};


function ec3_filter_the_content_bigcal(&$post_content)
{
  if(is_page())
  {
    $placeholder = '[EC3BigCalendar]';
    $pos=strpos($post_content,$placeholder);
    if($pos!==FALSE)
    {
      $options=array();
      $options['id']='ec3_big_cal';
      $options['num_months']=1;
      $calobj = new ec3_BigCalendar($options);
      $calcode = $calobj->generate();
      $post_content = str_replace($placeholder,$calcode,$post_content);
    }
  }
  return $post_content;
}

add_filter('the_content','ec3_filter_the_content_bigcal');

?>
