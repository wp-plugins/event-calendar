<?php

//
// ** This is all very experimental **


// Ignore the params for now: make a dummy query,
$month_ago = ec3_strftime('%Y-%m-%d',time()-(3600*24*31));
//query_posts('ec3_after='.$month_ago.'&nopaging=1');

require_once(dirname(__FILE__).'/calendar.php');

@header('Content-type: text/plain; charset=' . get_option('blog_charset'));
//@header('Content-type: text/xml; charset=' . get_option('blog_charset'));

echo '<?xml version="1.0" encoding="'.get_option('blog_charset')
.    '" standalone="yes"?>'."\n";

global $ec3,$wp_query;

//var_dump($wp_query);

class ec3_ec3xml extends ec3_Calendar
{
  function ec3_ec3xml($datetime=0,$num=1) {$this->ec3_Calendar($datetime,$num);}

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
    $day_link = $this->dateobj->day_link();
    $result ="<day id='$day_id' link='$day_link'";
    if(!empty($this->dayobj->titles))
      $result.=" titles='".implode(', ',$this->dayobj->titles)."'";
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
    $title=get_the_title();
    $this->_add_title($title);
    $link=get_permalink(); 
    $result = " <event title='$title' post_id='$id' link='$link'";
    $result .= " sched_id='$event->sched_id'";
    if($event->allday)
      $result .= " allday='0'";
    $result .= ">\n  <start>$event->start</start>\n  <end>$event->end</end>\n";
    $excerpt = get_the_excerpt();
    if(!empty($excerpt))
      $result .= "  <excerpt><![CDATA[$excerpt]]></excerpt>\n";
    $result .= " </event>\n";
    return $result;
  }

  function make_post(&$post)
  {
    global $id;
    $title=get_the_title();
    $this->_add_title($title);
    $link=get_permalink(); 
    $result = " <post title='$title' post_id='$id' link='$link'>\n";
    $excerpt = get_the_excerpt();
    if(!empty($excerpt))
      $result .= "  <excerpt><![CDATA[$excerpt]]></excerpt>\n";
    $result .= " </post>\n";
    return $result;
  }
  
  function _add_title($title)
  {
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
    $safe_title .= ' @'.ec3_get_start_time();
    $this->dayobj->titles[] = $safe_title;
  }

};

remove_filter('the_content','ec3_filter_the_content',20);
remove_filter('get_the_excerpt', 'ec3_get_the_excerpt');
add_filter('get_the_excerpt', 'wp_trim_excerpt');

$cal = new ec3_ec3xml();
$cal->add_events($wp_query);
$cal->add_posts($wp_query);


echo "<calendar>\n";
echo $cal->generate();
echo "</calendar>\n";

?>
