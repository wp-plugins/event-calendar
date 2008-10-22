<?php

//
// ** This is all very experimental **


// Ignore the params for now: make a dummy query,
$month_ago = ec3_strftime('%Y-%m-%d',time()-(3600*24*31));
query_posts('ec3_after='.$month_ago.'&nopaging=1');

require_once(dirname(__FILE__).'/calendar.php');

@header('Content-type: text/plain; charset=' . get_option('blog_charset'));
//@header('Content-type: text/xml; charset=' . get_option('blog_charset'));

echo '<?xml version="1.0" encoding="'.get_option('blog_charset')
.    '" standalone="yes"?>'."\n";

global $ec3,$wp_query;

class ec3_ec3xml extends ec3_Calendar
{
  function ec3_ec3xml($datetime,$num) { $this->ec3_Calendar($datetime,$num); }

  function wrap_month($monthstr,$dateobj)
  {
    return "<month id='".$dateobj->month_id()."'>\n".$monthstr."</month>\n";
  }
  
  function wrap_week($weekstr,$dateobj)
  {
    return $weekstr;
  }
  
  /** dayobj - ec3_CalendarDay object, may be empty. */
  function wrap_day($daystr,$dateobj,$dayobj)
  {
    if(empty($dayobj))
      return $daystr;

    $day_id   = $dateobj->day_id();
    $day_link = $dateobj->day_link();
    $result ="<day id='$day_id' link='$day_link'";
    $titles = array();
    if($dayobj->has_events())
    {
      $result .= " is_event='1'";
      for($evt=$dayobj->iter_events(); $evt->valid(); $evt->next())
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
        $safe_title .= ' @'.ec3_get_start_time();
        $titles[] = $safe_title;
      }
    }
    $result.=" titles='".implode(', ',$titles)."'";
    return $result.'>'.$daystr."</day>\n";
  }

  function make_pad($num_days,$is_start_of_month)
  {
    return '';
  }
  
  /** Second param may be empty. */
  function make_day($dateobj,$dayobj)
  {}

};

$cal = new ec3_ec3xml($month_ago,2);
$cal->add_events($wp_query);


echo "<calendar>\n";
echo $cal->generate();
echo "</calendar>\n";

?>
