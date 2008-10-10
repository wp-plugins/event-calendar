<?php

/** Comparison function for events' start times.
 *  Example: Sort the events in a post by start time.
 *
 *    usort( $post, 'ec3_cmp_events' );
 *
 * (Note. This isn't a practical example, because posts' events are already
 *  sorted by start time.)
 */
function ec3_cmp_events($e0,$e1)
{
  if($e0<$e1) return -1;
  if($e0>$e1) return 1;
  return 0;
}

/** Fetch the first sensible 'current' event. Use this function if you want
 *  to look at the start time. */
function &ec3_sensible_start_event()
{
  global $ec3, $post;
  if($ec3->event)
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[0];
  else
    return false;
}

/** Fetch the last sensible 'current' event. Use this function if you want
 *  to look at the end time. */
function &ec3_sensible_end_event()
{
  global $ec3, $post;
  if($ec3->event)
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[ count($post->ec3_schedule) - 1 ];
  else
    return false;
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
 *      ec3_post_events();
 *      while(ec3_have_events())
 *      {
 *        ec3_the_event();
 *        ...
 *      }
 *    }
 */
function ec3_post_events($id=0)
{
  global $ec3;
  $post = &get_post($id);
  if(!isset($post->ec3_schedule) || count($post->ec3_schedule)==0)
  {
    $ec3->events       = false;
    $ec3->events_count = 0;
  }
  else
  {
    $ec3->events       = $post->ec3_schedule;
    $ec3->events_count = count($ec3->events);
    $ec3->event        = false;
    $ec3->event_idx    = -1;
  }
}

/** Inialialise an event-loop, for ALL events in all posts in the current query. .
 *  By default we use the global query $wp_query, but you can supply your own,
 *  if you prefer.
 *  Example:
 *
 *    if(have_posts())
 *    {
 *      ec3_all_events();
 *      while(ec3_have_events())
 *      {
 *        ec3_the_event();
 *        ...
 *      }
 *    }
 */
function ec3_all_events($query=0)
{
  global $ec3, $post, $wp_query;
  if(empty($query))
    $query =& $wp_query;
  $ec3->events = array();
  while($query->have_posts())
  {
    $query->the_post();
    if(!isset($post->ec3_schedule))
      continue;
    foreach($post->ec3_schedule as $s)
      array_push($ec3->events,$s);
  }
  usort($ec3->events,'ec3_cmp_events');
  $ec3->events_count = count($ec3->events);
  $ec3->event        = false;
  $ec3->event_idx    = -1;
}

/** Event loop function. Returns TRUE if the next call to ec3_the_event() will
 *  succeed. */
function ec3_have_events()
{
  global $ec3;
  return( $ec3->event_idx+1 < $ec3->events_count );
}

/** Event loop function. Fetches the next event, and places it in $ec3->event.
 *  From there, ec3 template functions will automatically find it. */
function ec3_the_event()
{
  global $ec3,$post;
  // Assert: ec3_have_events() just returned true.
  $ec3->event_idx++;
  $ec3->event = $ec3->events[$ec3->event_idx];
  if($post->ID != $ec3->event->post_id)
  {
    $post = get_post($ec3->event->post_id);
    setup_postdata($post);
  }
}


/** Template function. Call this from your template to insert a list of
 *  forthcoming events. Available template variables are:
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
  global $post;

  // Parse $limit:
  //  NUMBER      - limits number of posts
  //  NUMBER days - next NUMBER of days
  $query =& new WP_Query();
  if(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
      $query->query( 'ec3_listing=event&ec3_days='.intval($matches[1]) );
  elseif(intval($limit)>0)
      $query->query( 'ec3_after=today&posts_per_page='.intval($limit) );
  elseif(intval($limit)<0)
      $query->query( 'ec3_before=today&posts_per_page='.intval($limit) );
  else
      $query->query( 'ec3_after=today&posts_per_page=5' );

  echo "<ul class='ec3_events'>";
  echo '<!-- Generated by Event Calendar v'.ec3_get_version().' -->'."\n";

  if($query->have_posts())
  {
    $current_month=false;
    $current_date=false;
    $data=array();
    ec3_all_events($query);
    while(ec3_have_events())
    {
      ec3_the_event();

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

      $data['TIME']  =ec3_get_time();
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


function ec3_widget_upcoming_events($limit)
{
  if(!ec3_check_installed(__('Upcoming Events','ec3')))
    return;

  // Parse $limit:
  //  NUMBER      - limits number of posts
  //  NUMBER days - next NUMBER of days
  $query =& new WP_Query();
  if(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
      $query->query( 'ec3_days='.intval($matches[1]) );
  elseif(intval($limit)>0)
      $query->query( 'ec3_after=today&posts_per_page='.intval($limit) );
  elseif(intval($limit)<0)
      $query->query( 'ec3_before=today&posts_per_page='.intval($limit) );
  else
      $query->query( 'ec3_after=today&posts_per_page=5' );

  echo "<ul class='ec3_events'>";
  echo '<!-- Generated by Event Calendar v'.ec3_get_version().' -->'."\n";
  if($query->have_posts())
  {
    $current_date=false;
    ec3_all_events($query);
    while(ec3_have_events())
    {
      ec3_the_event();

      // Date changed?
      $date=ec3_get_date('j F');
      if(!$current_date || $current_date!=$date)
      {
        if($current_date)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_day'>$date:'\n<ul>\n";
        $current_date=$date;
      }
      // Print the event.
      echo ' <li><a href="'.get_permalink().'">'
        .  get_the_title().' ('.ec3_get_time().')</a></li>'."\n";
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
  ec3_post_events();
  while(ec3_have_events())
  {
    ec3_the_event();
    $date_start=ec3_get_start_date();
    $date_end  =ec3_get_end_date();
    $time_start=ec3_get_start_time();
    $time_end  =ec3_get_end_time();

    if($ec3->event->allday)
    {
      if($date_start!=$date_end)
      {
        $result.=sprintf($format_range,$date_start,$date_end,__('to','ec3'));
      }
      elseif($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start);
      }
    }
    else
    {
      if($date_start!=$date_end)
      {
        $current=$date_start;
        $result.=sprintf($format_range,
          "$date_start $time_start","$date_end $time_end",__('to','ec3'));
      }
      else
      {
        if($date_start!=$current)
        {
          $current=$date_start;
          $result.=sprintf($format_single,$date_start);
        }
        if($time_start==$time_end)
          $result.=sprintf($format_single,$time_start);
        else
          $result.=sprintf($format_range,$time_start,$time_end,__('to','ec3'));
      }
    }
  }
  return sprintf($format_wrapper,$result);
}

?>
