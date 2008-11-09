<?php
/*
Copyright (c) 2005-2008, Alex Tingle.  $Revision: 281 $

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

/** Singleton class. Manages EC3 options. Global options that are guaranteed to
 *  exist (start of week, siteurl, home) are not managed by this class. */
class ec3_Options
{
  // Some global variables.
  var $version='3.2.dev-01';
  var $myfiles='';
  var $call_count=0;
  var $schedule='ec3_schedule'; // table name

  // State variables used by the custom loop

  /** Points to a sorted list of all events.
   *  Set by ec3_iter_all_events() or ec3_iter_post_events(). */
  var $events=false;
  /** Points to the current event. Set by ec3_Iterator::next(). */
  var $event=false;
  
  // Code differences required by different versions of WordPress.
  // Defaults represent the latest version of WP.

  /** The name of the column wp_posts.user_nicename. */
  var $wp_user_nicename='user_nicename';
  /** The root of the XHTML id of the category checkboxes (edit page). */
  var $wp_in_category='in-category-';
  /** Is DBX available? */
  var $wp_have_dbx=true;
  /** Do we have categories? (WP<2.3) */
  var $wp_have_categories=false;

  // Settings used to flags activity between posts_where and other filters:
  var $query=false; ///< Set by reset_query()
  var $is_date_range=false;
  var $is_today=false;
  var $days=false;
  var $range_from=false;
  var $range_before=false;
  var $join_ec3_sch=false;
  var $join_only_active_events=false;
  var $order_by_start=false;
  
  /** May be set TRUE by a template before the call to wp_head().
    * Turns off CSS in header. */
  var $nocss=false;

  /** Which category is used for events? DEFAULT=0 */
  var $event_category;
  /** Display event box within post. DEFAULT=0 */
  var $hide_event_box;
  /** Use advanced post behaviour? DEFAULT=0 */
  var $advanced;
  /** Local timezone. */
  var $tz;

  function ec3_Options()
  {
    global $table_prefix,$wp_version;

    $mydir=
      preg_replace('%^.*[/\\\\]([^/\\\\]+)[/\\\\]options.php$%','$1',__FILE__);
    load_plugin_textdomain('ec3','wp-content/plugins/'.$mydir.'/gettext');

    $this->myfiles=get_option('siteurl').'/wp-content/plugins/'.$mydir;
    $this->schedule=$table_prefix.$this->schedule; // table name

    // wp_version < 2.0
    if(ereg('^1[.]',$wp_version))
    {
      $this->wp_user_nicename='user_nickname';
      $this->wp_have_dbx=false;
    }
    // wp_version < 2.1
    if(ereg('^(1[.]|2[.]0)',$wp_version))
    {
      $this->wp_in_category='category-';
    }
    // wp_version < 2.3
    if(ereg('^(1[.]|2[.][012])',$wp_version))
    {
      $this->wp_have_categories=true;
    }

    $this->read_event_category();
    $this->read_hide_event_box();
    $this->read_advanced();
    $this->read_tz();
  }
  
  function reset_query(&$query)
  {
    $this->query =& $query;
    $this->query->ec3_listing=''; ///< Valid: ''|'YES'|'NO'

    $this->is_date_range=false;
    $this->is_today=false;
    $this->days=false;
    $this->range_from=false;
    $this->range_before=false;
    $this->join_ec3_sch=false;
    $this->join_only_active_events=false;
    $this->order_by_start=false;
  }

  // READ functions
  function read_event_category()
  {
    $this->event_category=intval( get_option('ec3_event_category') );
  }
  function read_hide_event_box()
  {
    $this->hide_event_box=intval(get_option('ec3_hide_event_box'));
  }
  function read_advanced()
  {
    $this->advanced=intval(get_option('ec3_advanced'));
    // Sometimes we want to play around with the value of advanced.
    // 'advanced_setting' ALWAYS holds the REAL value.
    $this->advanced_setting=$this->advanced;
  }
  function read_tz()
  {
    $this->tz = get_option('ec3_tz');
    if(empty($this->tz) || $this->tz=='wordpress')
    {
      // Use WordPress default (doesn't understand daylight saving time).
      $gmt_offset=-intval(get_option('gmt_offset'));
      $this->tz='UTC';
      if($gmt_offset>0)
        $this->tz.='+'.$gmt_offset;
      elseif($gmt_offset<0)
        $this->tz.=$gmt_offset;
    }
  }
  
  // SET functions
  function set_event_category($val)
  {
    if($this->event_category!=$val)
    {
      update_option('ec3_event_category',$val);
      $this->read_event_category();
    }
  }
  function set_hide_event_box($val)
  {
    if($this->hide_event_box!=$val)
    {
      update_option('ec3_hide_event_box',$val);
      $this->read_hide_event_box();
    }
  }
  function set_advanced($val)
  {
    if($this->advanced_setting!=$val)
    {
      update_option('ec3_advanced',$val);
    }
    // read_advanced() does some special magic, so we always call it.
    $this->read_advanced();
  }
  function set_tz($val)
  {
    if(!preg_match('/(WordPress|[_a-zA-Z\/]+)/',$val))
      return;
    if($this->tz!=$val)
    {
      update_option('ec3_tz',$val);
      $this->read_tz();
    }
  }
} // end class ec3_Options


/** Singleton instance of ec3_Options. */
$ec3=new ec3_Options();


?>
