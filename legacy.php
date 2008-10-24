<?php

/** Template function. Call this from your template to insert a list of
 *  forthcoming events. Available template variables are:
 *   - template_day: %DATE% %SINCE% (only with Time Since plugin)
 *   - template_event: %DATE% %TIME% %LINK% %TITLE% %AUTHOR%
 */
function ec3_get_events_old(
  $limit,
  $template_event=EC3_DEFAULT_TEMPLATE_EVENT,
  $template_day  =EC3_DEFAULT_TEMPLATE_DAY,
  $date_format   =EC3_DEFAULT_DATE_FORMAT,
  $template_month=EC3_DEFAULT_TEMPLATE_MONTH,
  $month_format  =EC3_DEFAULT_MONTH_FORMAT)
{
  if(!ec3_check_installed(__('Upcoming Events','ec3')))
    return;
  global $ec3,$wpdb,$wp_version;

  // Parse $limit:
  //  NUMBER      - limits number of posts
  //  NUMBER days - next NUMBER of days
  if(empty($limit))
  {
    $limit_numposts='LIMIT 5';
  }
  elseif(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
  {
    $secs=intval($matches[1])*24*3600;
    $and_before="AND start<='".ec3_strftime('%Y-%m-%d',time()+$secs)."'";
  }
  elseif(intval($limit)<1)
  {
    $limit_numposts='LIMIT 5';
  }
  else
  {
    $limit_numposts='LIMIT '.intval($limit);
  }
  
  if(!$date_format)
      $date_format=get_option('date_format');

  // Find the upcoming events.
  $calendar_entries = $wpdb->get_results(
    "SELECT DISTINCT
       p.id AS id,
       post_title,
       start,
       u.$ec3->wp_user_nicename AS author,
       allday
     FROM $ec3->schedule s
     LEFT JOIN $wpdb->posts p ON s.post_id=p.id
     LEFT JOIN $wpdb->users u ON p.post_author = u.id
     WHERE p.post_status='publish'
       AND end>='$ec3->today' $and_before
     ORDER BY start $limit_numposts"
  );

  echo "<ul class='ec3_events'>";
  echo "<!-- Generated by Event Calendar v$ec3->version -->\n";
  if($calendar_entries)
  {
    $time_format=get_option('time_format');
    $current_month=false;
    $current_date=false;
    $data=array();
    foreach($calendar_entries as $entry)
    {
      // To use %SINCE%, you need Dunstan's 'Time Since' plugin.
      if(function_exists('time_since'))
          $data['SINCE']=time_since( time(), ec3_to_time($entry->start) );

      // Month changed?
      $data['MONTH']=mysql2date($month_format,$entry->start);
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
      $data['DATE'] =mysql2date($date_format, $entry->start);
      if((!$current_date || $current_date!=$data['DATE']) && $template_day)
      {
        if($current_date)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_day'>"
        .    ec3_format_str($template_day,$data)."\n<ul>\n";
        $current_date=$data['DATE'];
      }

      if($entry->allday)
          $data['TIME']=__('all day','ec3');
      else
          $data['TIME']=mysql2date($time_format,$entry->start);

      $data['TITLE'] =
        htmlentities(
          stripslashes(strip_tags($entry->post_title)),
          ENT_QUOTES,get_option('blog_charset')
        );
      $data['LINK']  =get_permalink($entry->id);
      $data['AUTHOR']=
        htmlentities($entry->author,ENT_QUOTES,get_option('blog_charset'));
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
function ec3_get_schedule_old(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  global $ec3,$post;
  // Should have been set by ec3_filter_the_posts()
  if(!$post || !$post->ec3_schedule)
      return '';
  $result='';
  $date_format=get_option('date_format');
  $time_format=get_option('time_format');
  $current=false;
  foreach($post->ec3_schedule as $s)
  {
    $date_start=mysql2date($date_format,$s->start);
    $date_end  =mysql2date($date_format,$s->end);
    $time_start=mysql2date($time_format,$s->start);
    $time_end  =mysql2date($time_format,$s->end);

    if($s->allday)
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