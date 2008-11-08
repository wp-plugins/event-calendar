<?php

/** Returns TRUE if the current post is an event. */
function ec3_is_event()
{
  global $post;
  return( !empty($post->ec3_schedule) );
}

/** Comparison function for events' start times.
 *  Example: Sort the events in a post by start time.
 *
 *    usort( $post, 'ec3_cmp_events' );
 *
 * (Note. This isn't a practical example, because posts' events are already
 *  sorted by start time.)
 */
function ec3_cmp_events(&$e0,&$e1)
{
  if( $e0->start < $e1->start ) return -1;
  if( $e0->start > $e1->start ) return 1;
  return 0;
}

/** Fetch the first sensible 'current' event. Use this function if you want
 *  to look at the start time. */
function ec3_sensible_start_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[0];
  else
    return false;
}

/** Fetch the last sensible 'current' event. Use this function if you want
 *  to look at the end time. */
function ec3_sensible_end_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[ count($post->ec3_schedule) - 1 ];
  else
    return false;
}

/** Get the sched_id of the current event. */
function ec3_get_sched_id()
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  else
    return $event->sched_id;
}

/** Return TRUE if the current event is in the past. */
function ec3_is_past()
{
  $event = ec3_sensible_end_event();
  if(empty($event))
    return false;
  else
    return( $event->end < $ec3->today );
}

/** Get a human-readable 'time since' the current event. */
function ec3_get_since()
{
  // To use %SINCE%, you need Dunstan's 'Time Since' plugin.
  if(function_exists('time_since'))
  {
    $event = ec3_sensible_start_event();
    if(!empty($event))
      return time_since( time(), ec3_to_time($event->start) );
  }
  return '';
}

/** Get the start time of the current event. */
function ec3_get_start_time($d='')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  elseif($event->allday)
    return __('all day','ec3');
  $d = empty($d)? get_option('time_format'): $d;
  return mysql2date($d,$event->start);
}

/** Get the end time of the current event. */
function ec3_get_end_time($d='')
{
  $event = ec3_sensible_end_event();
  if(empty($event) || $event->allday)
    return '';
  $d = empty($d)? get_option('time_format'): $d;
  return mysql2date($d,$event->end);

}

/** Get the start month of the current event. */
function ec3_get_start_month($d='F Y')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  return mysql2date($d,$event->start);
}

/** Get the end month of the current event. */
function ec3_get_end_month($d='F Y')
{
  $event = ec3_sensible_end_event();
  if(empty($event))
    return '';
  return mysql2date($d,$event->end);
}

/** Get the start date of the current event. */
function ec3_get_start_date($d='')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->start);
}

/** Get the end date of the current event. */
function ec3_get_end_date($d='')
{
  $event = ec3_sensible_end_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->end);
}

function ec3_get_time($d='')  { return ec3_get_start_time( $d); }
function ec3_get_month($d='') { return ec3_get_start_month($d); }
function ec3_get_date($d='')  { return ec3_get_start_date( $d); }


/** Get the current version of the EC3 plug-in. */
function ec3_get_version()
{
  global $ec3;
  return $ec3->version;
}

/** Initialise an event-loop, just for the events in the current $post.
 *  Example:
 *
 *    // First a normal loop over the current query's posts.
 *    while(have_posts())
 *    {
 *      the_post();
 *      // Now a nested loop, over the events in each post.
 *      for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
 *      {
 *        ...
 *      }
 *    }
 */
function ec3_iter_post_events($id=0)
{
  global $ec3;
  $post = get_post($id);
  unset($ec3->events);
  if(!isset($post->ec3_schedule) || empty($post->ec3_schedule))
  {
    $ec3->events       = false;
  }
  else
  {
    $ec3->events       = $post->ec3_schedule;
  }
  return new ec3_EventIterator();
}


/** Initialise an event-loop, for ALL events in all posts in a query.
 *  You must explicitly state which query is to be used. If you just want to use
 *  the current query, then use the variant form: ec3_iter_all_events(). */
function ec3_iter_all_events_q(&$query)
{
  global $ec3, $post;
  unset($ec3->events);
  $ec3->events = array();

  if($query->is_page || $query->is_single || $query->is_admin):

      // Emit all events.
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          $ec3->events[] = $s;
      }

  elseif($query->query_vars['m'] && strlen($query->query_vars['m'])>=4):

      // Only emit events that occur on the given day (or month or year).
      if(strlen($query->query_vars['m'])>=8)
      {
        $m=substr($query->query_vars['m'],0,8);
        $fmt='Ymd';
      }
      elseif(strlen($query->query_vars['m'])>=6)
      {
        $m=substr($query->query_vars['m'],0,6);
        $fmt='Ym';
      }
      else
      {
        $m=substr($query->query_vars['m'],0,4);
        $fmt='Y';
      }

      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if(mysql2date($fmt,$s->end) >= $m && mysql2date($fmt,$s->start) <= $m)
            $ec3->events[] = $s;
      }

  elseif($ec3->is_date_range):

      // The query is date-limited, so only emit events that occur
      // within the date range.
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if( ( empty($ec3->range_from) ||
                  mysql2date('Y-m-d',$s->end) >= $ec3->range_from ) &&
              ( empty($ec3->range_before) ||
                  mysql2date('Y-m-d',$s->start) <= $ec3->range_before ) )
          {
            $ec3->events[] = $s;
          }
      }

  elseif($ec3->advanced &&(ec3_is_event_category($query) || $query->is_search)):

      // Hide inactive events
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if( $s->end >= $ec3->today )
            $ec3->events[] = $s;
      }

  else:

      // Emit all events (same as the first branch).
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          $ec3->events[] = $s;
      }

  endif;
  usort($ec3->events,'ec3_cmp_events');
  // This is a bit of a hack - only detect 'order=ASC' query var.
  // Really need our own switch.
  if(strtoupper($query->query_vars['order'])=='ASC')
    $ec3->events=array_reverse($ec3->events);
  return new ec3_EventIterator();
}


/** Initialise an event-loop, for ALL events in all posts in the current query.
 *  Example:
 *
 *    if(have_posts())
 *    {
 *      for($evt=ec3_iter_all_events(); $evt->valid(); $evt->next())
 *      {
 *        ...
 *      }
 *    }
 */
function ec3_iter_all_events()
{
  global $wp_query;
  return ec3_iter_all_events_q($wp_query);
}


/** Iterator class implements loops over events. Generated by
 *  ec3_iter_post_events() or ec3_iter_all_events().
 *  These iterators are not independent - don't try to get smart with nested
 *  loops!
 *  This class is ready to implement PHP5's Iterator interface.
 */
class ec3_EventIterator
{
  var $_idx   =0;
  var $_begin =0;
  var $_limit =0;

  /** Parameters are andices into the $ec3->events array.
   *  'begin' points to the first event.
   *  'limit' is one higher than the last event. */
  function ec3_EventIterator($begin=0, $limit=-1)
  {
    global $ec3;
    $this->_begin = $begin;
    if(empty($ec3->events))
      $this->_limit = 0;
    elseif($limit<0)
      $this->_limit = count($ec3->events);
    else
      $this->_limit = $limit;
    $this->rewind();
  }

  /** Resets this iterator to the beginning. */
  function rewind()
  {
    $this->_idx = $this->_begin - 1;
    $this->next();
  }

  /** Move along to the next (possibly empty) event. */
  function next()
  {
    $this->_idx++;
    $this->current();
  }
  
  /** Returns TRUE if this iterator points to an event. */
  function valid()
  {
    return( $this->_idx < $this->_limit );
  }

  /** Set the global $ec3->event to match this iterator's index. */
  function current()
  {
    global $ec3,$post;
    if( $this->_idx < $this->_limit )
    {
      $ec3->event = $ec3->events[$this->_idx];
      if($post->ID != $ec3->event->post_id)
      {
        $post = get_post($ec3->event->post_id);
        setup_postdata($post);
      }
    }
    else
    {
      unset($ec3->event); // Need to break the reference.
      $ec3->event = false;
    }
  }
  
  function key()
  {
    return $this->_idx;
  }
}; // limit class ec3_EventIterator


/** Template function, for backwards compatibility.
 *  Call this from your template to insert a list of forthcoming events.
 *  Available template variables are:
 *   - template_day: %DATE% %SINCE% (only with Time Since plugin)
 *   - template_event: %DATE% %TIME% %LINK% %TITLE% %AUTHOR%
 */
function ec3_get_events(
  $limit,
  $template_event=EC3_DEFAULT_TEMPLATE_EVENT,
  $template_day  =EC3_DEFAULT_TEMPLATE_DAY,
  $date_format   =EC3_DEFAULT_DATE_FORMAT,
  $template_month=EC3_DEFAULT_TEMPLATE_MONTH,
  $month_format  =EC3_DEFAULT_MONTH_FORMAT)
{
  if(!ec3_check_installed(__('Upcoming Events','ec3')))
    return;

  // Parse $limit:
  //  NUMBER      - limits number of posts
  //  NUMBER days - next NUMBER of days
  $query = new WP_Query();
  if(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
      $query->query( 'ec3_listing=event&ec3_days='.intval($matches[1]) );
  elseif(intval($limit)>0)
      $query->query( 'ec3_after=today&posts_per_page='.intval($limit) );
  elseif(intval($limit)<0)
      $query->query( 'ec3_before=today&order=asc&posts_per_page='.abs(intval($limit)) );
  else
      $query->query( 'ec3_after=today&posts_per_page=5' );

  echo "<ul class='ec3_events'>";
  echo '<!-- Generated by Event Calendar v'.ec3_get_version().' -->'."\n";

  if($query->have_posts())
  {
    $current_month=false;
    $current_date=false;
    $data=array();
    for($evt=ec3_iter_all_events_q($query); $evt->valid(); $evt->next())
    {
      $data['SINCE']=ec3_get_since();

      // Month changed?
      $data['MONTH']=ec3_get_month($month_format);
      if((!$current_month || $current_month!=$data['MONTH']) && $template_month)
      {
        if($current_date)
            echo "</ul></li>\n";
        if($current_month)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_month'>"
        .    ec3_format_str($template_month,$data)."\n<ul>\n";
        $current_month=$data['MONTH'];
        $current_date=false;
      }

      // Date changed?
      $data['DATE'] =ec3_get_date($date_format);
      if((!$current_date || $current_date!=$data['DATE']) && $template_day)
      {
        if($current_date)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_day'>"
        .    ec3_format_str($template_day,$data)."\n<ul>\n";
        $current_date=$data['DATE'];
      }

      $data['TIME']  =ec3_get_start_time();
      $data['TITLE'] =get_the_title();
      $data['LINK']  =get_permalink();
      $data['AUTHOR']=get_the_author();
      echo " <li>".ec3_format_str($template_event,$data)."</li>\n";
    }
    if($current_date)
        echo "</ul></li>\n";
    if($current_month)
        echo "</ul></li>\n";
  }
  else
  {
    echo "<li>".__('No events.','ec3')."</li>\n";
  }
  echo "</ul>\n";
}


/** Formats the schedule for the current post.
 *  Returns the HTML fragment as a string. */
function ec3_get_schedule(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  global $ec3;
  $result='';
  $date_format=get_option('date_format');
  $time_format=get_option('time_format');
  $current=false;
  for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
  {
    $date_start=ec3_get_start_date();
    $date_end  =ec3_get_end_date();
    $time_start=ec3_get_start_time();
    $time_end  =ec3_get_end_time();
    if($ec3->event->active)
      $active ='';
    else
      $active ='ec3_past';

    if($ec3->event->allday)
    {
      if($date_start!=$date_end)
      {
        $result.=
          sprintf($format_range,$date_start,$date_end,__('to','ec3'),$active);
      }
      elseif($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start,$active);
      }
    }
    else
    {
      if($date_start!=$date_end)
      {
        $current=$date_start;
        $result.=sprintf(
            $format_range,
            "$date_start $time_start",
            "$date_end $time_end",
            __('to','ec3'),
            $active
          );
      }
      else
      {
        if($date_start!=$current)
        {
          $current=$date_start;
          $result.=sprintf($format_single,$date_start,$active);
        }
        if($time_start==$time_end)
          $result.=sprintf($format_single,$time_start,$active);
        else
          $result.=
            sprintf($format_range,$time_start,$time_end,__('to','ec3'),$active);
      }
    }
  }
  return sprintf($format_wrapper,$result);
}


/** Template function, for backwards compatibility.
 *  Call this from your template to insert the Sidebar Event Calendar. */
function ec3_get_calendar($cal_id='wp-calendar')
{
  if(!ec3_check_installed(__('Event Calendar','ec3')))
    return;

  require_once(dirname(__FILE__).'/calendar-sidebar.php');

  global $ec3;
  $calobj = new ec3_SidebarCalendar(0,$ec3->num_months);
  echo $calobj->generate($cal_id);
}


?>
