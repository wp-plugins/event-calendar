/* EventCalendar. Copyright (C) 2005-2008, Alex Tingle.
 * This file is licensed under the GNU GPL. See LICENSE file for details.
 */

// Set in HTML file:
//   var ec3.start_of_week
//   var ec3.month_of_year
//   var ec3.month_abbrev
//   var ec3.myfiles
//   var ec3.home
//   var ec3.hide_logo
//   var ec3.viewpostsfor

// namespace
var ec3 = {
  version:'3.2.dev-01',

  /** Get today's date.
   *  Note - DO THIS ONCE, so that the value of today never changes! */
  today : new Date(),

  /** Global store for ec3.Calendar objects. */
  calendars : [],

  ELEMENT_NODE: 1,
  TEXT_NODE:    3,

  init : function()
    {
      // Set-up calculated stuff about today.
      ec3.today_day_num  = ec3.today.getDate();
      ec3.today_month_num= ec3.today.getMonth() + 1;
      ec3.today_year_num = ec3.today.getFullYear();

      // Pre-load image.
      ec3.imgwait=new Image(14,14);
      ec3.imgwait.src=ec3.myfiles+'/ec_load.gif';

      // Convert strings from PHP into Unicode
      ec3.viewpostsfor=ec3.unencode(ec3.viewpostsfor);
      for(var i=0; i<ec3.month_of_year.length; i++)
        ec3.month_of_year[i]=ec3.unencode(ec3.month_of_year[i]);
      for(var j=0; j<ec3.month_abbrev.length; j++)
        ec3.month_abbrev[j]=ec3.unencode(ec3.month_abbrev[j]);
    },

  /** Register an onload function. */
  do_onload : function(fn)
    {
      var prev=window.onload;
      window.onload=function(){ if(prev)prev(); fn(); }
    },

  /** Register a new calendar with id: cal_id. */
  new_calendar : function(cal_id)
    {
      var cal = new ec3.Calendar(cal_id);
      ec3.do_onload( function(){cal.init();} );
      ec3.calendars[cal_id] = cal;
      return cal;
    },

  /** Converts HTML encoded text (e.g. "&copy Copyright") into Unicode. */
  unencode : function(text)
    {
      if(!ec3.unencodeDiv)
        ec3.unencodeDiv=document.createElement('div');
      ec3.unencodeDiv.innerHTML=text;
      return(ec3.unencodeDiv.innerText || ec3.unencodeDiv.firstChild.nodeValue);
    },

  get_child_by_tag_name : function(element,tag_name)
    {
      var results=element.getElementsByTagName(tag_name);
      if(results)
        for(var i=0; i<results.length; i++)
          if(results[i].parentNode==element)
            return results[i];
      return 0;
    },

  calc_day_id : function(day_num,month_num,year_num)
    {
      if(ec3.today_day_num==day_num &&
         ec3.today_month_num==month_num &&
         ec3.today_year_num==year_num)
      {
        return 'today';
      }
      else
      {
        return 'ec3_'+year_num+'_'+month_num+'_'+day_num;
      }
    },
    
  add_class : function(element,new_class_name)
    {
      if(element.className.length)
        element.className+=' '+new_class_name;
      else
        element.className=new_class_name;
    }

} // end namespace ec3


ec3.do_onload( function(){ec3.init();} );


/** Calendar class. */
ec3.Calendar = function(cal_id)
{
  this.cal_id = cal_id;
}

ec3.Calendar.prototype = {

  full_id : function(short_id)
    {
      return this.cal_id+'-'+short_id;
    },

  short_id : function(full_id)
    {
      return full_id.substr(this.cal_id.length);
    },

  getElementById : function(short_id)
    {
      return document.getElementById(this.full_id(short_id));
    },

  init : function()
    {
      // Holds ongoing XmlHttp requests.
      this.reqs =  new Array();

      // Get the calendar's root div element.
      this.div = document.getElementById(this.cal_id);

      // Overwrite the href links in ec3_prev & ec3_next to activate EC3.
      var prev=this.getElementById('ec3_prev');
      var next=this.getElementById('ec3_next');
      if(prev && next)
      {
        // Check for cat limit in month link
        var xCat=new RegExp('&cat=[0-9]+$');
        var match=xCat.exec(prev.href);
        if(match)
          this.catClause=match[0];
        // Replace links
        var self = this;
        prev.onclick = function(){self.go_prev(); return false;}
        next.onclick = function(){self.go_next(); return false;}
        prev.href='#';
        next.href='#';
      }

      if(typeof ec3_Popup != 'undefined')
      {
        // Set-up popup.
        var cals=this.get_calendars();
        if(cals)
        {
          // Add event handlers to the calendars.
          for(var i=0,len=cals.length; i<len; i++)
            ec3_Popup.add_tbody( ec3.get_child_by_tag_name(cals[i],'tbody') );
        }
      }
    },

  /** Replaces the caption and tbody in table to be the specified year/month. */
  create_calendar : function(table_cal,month_num,year_num)
    {
      // Take a deep copy of the current calendar.
      var table=table_cal.cloneNode(1);

      // Calculate the zero-based month_num
      var month_num0=month_num-1;

      // Set the new caption
      var caption=ec3.get_child_by_tag_name(table,'caption');
      if(caption)
      {
        var c=ec3.get_child_by_tag_name(caption,'a');
        var caption_text=ec3.month_of_year[month_num0] + ' ' + year_num;
        if(c && c.firstChild && c.firstChild.nodeType==ec3.TEXT_NODE )
        {
	  if(month_num<10) 
	  {
	    c.href=ec3.home+'/?m='+year_num+'0'+month_num;
	  }
	  else
	  {
	    c.href=ec3.home+'/?m='+year_num+month_num;
	  }
          if(this.catClause)
             c.href+=this.catClause; // Copy cat' limit from original month link.
          c.title=ec3.viewpostsfor;
          c.title=c.title.replace(/%1\$s/,ec3.month_of_year[month_num0]);
          c.title=c.title.replace(/%2\$s/,year_num);
          c.firstChild.data=caption_text;
        }
      }

      if(caption &&
         caption.firstChild &&
         caption.firstChild.nodeType==ec3.TEXT_NODE)
      {
        caption.firstChild.data=ec3.month_of_year[month_num0] + ' ' + year_num;
      }

      var tbody=ec3.get_child_by_tag_name(table,'tbody');

      // Remove all children from the table body
      while(tbody.lastChild)
        tbody.removeChild(tbody.lastChild);

      // Make a new calendar.
      var date=new Date(year_num,month_num0,1, 12,00,00);

      var tr=document.createElement('tr');
      var td,div;
      tbody.appendChild(tr);
      var day_count=0
      var col=0;
      while(date.getMonth()==month_num0 && day_count<40)
      {
        var day=(date.getDay()+7-ec3.start_of_week)%7;
        if(col>6)
        {
          tr=document.createElement('tr');
          tbody.appendChild(tr);
          col=0;
        }
        if(col<day)
        {
          // insert padding
          td=document.createElement('td');
          td.colSpan=day-col;
          td.className='pad';
          tr.appendChild(td);
          col=day;
        }
        // insert day
        td=document.createElement('td');
        td.appendChild(document.createTextNode(date.getDate()));
        var short_id=ec3.calc_day_id(date.getDate(),month_num,year_num);
        td.id=this.full_id(short_id);
        if(short_id=='today')
          td.className='ec3_today';
        tr.appendChild(td);
        col++;
        day_count++;
        date.setDate(date.getDate()+1);
      }
      // insert padding
      if(col<7)
      {
        td=document.createElement('td');
        td.colSpan=7-col;
        td.className='pad';
        tr.appendChild(td);
      }

      // add the 'dog'
      if((7-col)>1 && !ec3.hide_logo)
      {
        a=document.createElement('a');
        a.href='http://blog.firetree.net/?ec3_version='+ec3.version;
        a.title='Event Calendar '+ec3.version;
        td.style.verticalAlign='bottom';
        td.appendChild(a);
        div=document.createElement('div');
        div.className='ec3_ec';
        div.align='right'; // keeps IE happy
        a.appendChild(div);
      }

      // set table's element id
      table.id=this.full_id('ec3_'+year_num+'_'+month_num);

      return table;
    }, // end create_calendar()

  /** Dispatch an XMLHttpRequest for a month of calendar entries. */
  loadDates : function(month_num,year_num)
    {
      var req=new XMLHttpRequest();
      if(req)
      {
        this.reqs.push(req);
        var self = this;
        req.onreadystatechange = function(){self.process_xml();};
        req.open("GET",
          ec3.home+'/?feed=ec3xml&year='+year_num+'&monthnum='+month_num,true);
        this.set_spinner(1);
        req.send(null);
      }
    },
  

  /** Obtain an array of all the calendar tables. */
  get_calendars : function()
    {
      var result=new Array();
      for(var i=0; i<this.div.childNodes.length; i++)
      {
        var c=this.div.childNodes[i];
        if(c.id &&
           c.id.search(this.full_id('ec3_[0-9]'))==0 &&
           c.style.display!='none')
        {
          result.push(this.div.childNodes[i]);
        }
      }
      if(result.length>0)
        return result;
      else
        return 0;
    },


  /** Changes the link text in the forward and backwards buttons.
   *  Parameters are the 0-based month numbers. */
  rewrite_controls : function(prev_month0,next_month0)
    {
      var prev=this.getElementById('ec3_prev');
      if(prev && prev.firstChild && prev.firstChild.nodeType==ec3.TEXT_NODE)
        prev.firstChild.data='\u00ab\u00a0'+ec3.month_abbrev[prev_month0%12];
      var next=this.getElementById('ec3_next');
      if(next && next.firstChild && next.firstChild.nodeType==ec3.TEXT_NODE)
        next.firstChild.data=ec3.month_abbrev[next_month0%12]+'\u00a0\u00bb';
    },


  /** Turn the busy spinner on or off. */
  set_spinner : function(on)
    {
      var spinner=this.getElementById('ec3_spinner');
      var publish=this.getElementById('ec3_publish');
      if(spinner)
      {
        if(on)
        {
          spinner.style.display='inline';
          if(publish)
            publish.style.display='none';
        }
        else
        {
          spinner.style.display='none';
          if(publish)
            publish.style.display='inline';
        }
      }
    },


  /** Called when the user clicks the 'previous month' button. */
  go_prev : function()
    {
      var calendars=this.get_calendars();
      if(!calendars)
        return;
      var pn=calendars[0].parentNode;

      // calculate date of new calendar
      var id_array=this.short_id(calendars[0].id).split('_');
      if(id_array.length<3)
        return;
      var year_num=parseInt(id_array[1]);
      var month_num=parseInt(id_array[2])-1;
      if(month_num==0)
      {
        month_num=12;
        year_num--;
      }
      // Get new calendar
      var newcal=this.getElementById('ec3_'+year_num+'_'+month_num);
      if(newcal)
      {
        // Add in the new first calendar
        newcal.style.display=this.calendar_display;
      }
      else
      {
        newcal=this.create_calendar(calendars[0],month_num,year_num);
        pn.insertBefore( newcal, calendars[0] );
        this.loadDates(month_num,year_num);
      }
      // Hide the last calendar
      this.calendar_display=calendars[calendars.length-1].style.display;
      calendars[calendars.length-1].style.display='none';

      // Re-write the forward & back buttons.
      this.rewrite_controls(month_num+10,month_num+calendars.length-1);
    },


  /** Called when the user clicks the 'next month' button. */
  go_next : function()
    {
      var calendars=this.get_calendars();
      if(!calendars)
        return;
      var pn=calendars[0].parentNode;
      var last_cal=calendars[calendars.length-1];

      // calculate date of new calendar
      var id_array=this.short_id(last_cal.id).split('_');
      if(id_array.length<3)
        return;
      var year_num=parseInt(id_array[1]);
      var month_num=1+parseInt(id_array[2]);
      if(month_num==13)
      {
        month_num=1;
        year_num++;
      }
      // Get new calendar
      var newcal=this.getElementById('ec3_'+year_num+'_'+month_num);
      if(newcal)
      {
        // Add in the new last calendar
        newcal.style.display=this.calendar_display;
      }
      else
      {
        newcal=this.create_calendar(calendars[0],month_num,year_num);
        if(last_cal.nextSibling)
          pn.insertBefore(newcal,last_cal.nextSibling);
        else
          pn.appendChild(newcal);
        this.loadDates(month_num,year_num);
      }
      // Hide the first calendar
      this.calendar_display=calendars[0].style.display;
      calendars[0].style.display='none';

      // Re-write the forward & back buttons.
      this.rewrite_controls(month_num-calendars.length+11,month_num);
    },


  /** Triggered when the XML load is complete. Checks that load is OK, and then
   *  updates calendar days. */
  process_xml : function()
    {
      var busy=0;
      for(var i=0; i<this.reqs.length; i++)
      {
        var req=this.reqs[i];
        if(req)
        {
          if(req.readyState==4)
          {
            this.reqs[i]=0;
            if(req.status==200)
              this.update_days(req.responseXML);
          }
          else
            busy=1;
        }
      }
      if(!busy)
      {
        // Remove old requests.
        while(this.reqs.shift && this.reqs.length && !this.reqs[0])
          this.reqs.shift();
        this.set_spinner(0);
      }
    },


  /** Adds links to the calendar for each day listed in the XML. */
  update_days : function(xml)
    {
      var days_xml=xml.getElementsByTagName('day');
      if(!days_xml)
        return;
      for(var i=0, len=days_xml.length; i<len; i++)
      {
        var td=this.getElementById(days_xml[i].getAttribute('id'));
        if(td && td.firstChild && td.firstChild.nodeType==ec3.TEXT_NODE)
        {
          this.make_day(td,days_xml[i],xml);
        }
      }
      if(typeof ec3_Popup != 'undefined')
      {
        var calendar_xml=xml.documentElement;
        var month=
          this.getElementById(calendar_xml.childNodes[0].getAttribute('id'));
        if(month)
          ec3_Popup.add_tbody( ec3.get_child_by_tag_name(month,'tbody') );
      }
    },

  /** Renders a single day into a TD element. This member function may be
   *  over-ridden to change the way the day cell is rendered.
   *  Parameters:
   *
   *   td - the TD element into which the day should be written.
   *
   *   day_xml - an XML element containing the day's posts and events.
   *         day@titles contains a summary of all the day's titles.
   *     Examples:
   *       <day id='ec3_2008_11_4' date='2008-11-04'
   *               link='http://theraven.local/2008/11/04/'
   *               titles='Election Day'>
   *           <post post_id='554' />
   *       </day>
   *       <day id='ec3_2008_11_5' date='2008-11-05'
   *               link='http://theraven.local/2008/11/05/'
   *               titles='WLUW 88.7 @10:00 pm'
   *               is_event='1'>
   *           <event post_id='pid_486' sched_id='sid_39'>
   *               <start>2008-11-05 22:00:00</start>
   *               <end>2008-11-05 23:00:00</end>
   *           </event>
   *       </day>
   *
   *   xml - the whole XML document. Use it to find details about posts.
   *         detail@title contains the post's title.
   *     Examples:
   *       <details>
   *           <detail id='pid_554' title='Election Day'
   *                   link='http://theraven.local/2008/11/04/election-day/'>
   *              <excerpt>...</excerpt>
   *           </detail>
   *           <detail id='pid_486' title='WLUW 88.7'
   *                   link='http://theraven.local/2008/08/04/wluw-887-13/'>
   *              <excerpt>...</excerpt>
   *           </detail>
   *       </details>
   */
  make_day : function(td,day_xml,xml)
    {
      ec3.add_class(td,'ec3_postday');
      // Save the TD's text node for later.
      var txt=td.removeChild(td.firstChild);
      // Make an A element
      var a=document.createElement('a');
      a.href=day_xml.getAttribute('link');
      a.title=day_xml.getAttribute('titles');
      if(day_xml.getAttribute('is_event'))
      {
        ec3.add_class(td,'ec3_eventday');
        a.className='eventday';
      }
      // Put the saves text node into the A.
      a.appendChild(txt);
      // Finally, put the A into the TD.
      td.appendChild(a);
    }

} // end ec3.Calendar.prototype
