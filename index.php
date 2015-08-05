<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>HTML5 Hotel Room Booking (JavaScript/PHP)</title>
        <!-- demo stylesheet -->
    	<link type="text/css" rel="stylesheet" href="media/layout.css" />    

	<!-- helper libraries -->
	<script src="js/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
	
	<!-- daypilot libraries -->
        <script src="js/daypilot/daypilot-all.min.js" type="text/javascript"></script>

        <style type="text/css">
            .scheduler_default_rowheader 
            {
                background: -webkit-gradient(linear, left top, left bottom, from(#eeeeee), to(#dddddd));
                    background: -moz-linear-gradient(top, #eeeeee 0%, #dddddd);
                    background: -ms-linear-gradient(top, #eeeeee 0%, #dddddd);
                    background: -webkit-linear-gradient(top, #eeeeee 0%, #dddddd);
                    background: linear-gradient(top, #eeeeee 0%, #dddddd);
                    filter: progid:DXImageTransform.Microsoft.gradient(startColorStr="#eeeeee", endColorStr="#dddddd");

            }
            .scheduler_default_rowheader_inner 
            {
                    border-right: 1px solid #ccc;
            }
            .scheduler_default_rowheadercol2
            {
                background: White;
            }
            .scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner 
            {
                top: 2px;
                bottom: 2px;
                left: 2px;
                background-color: transparent;
                    border-left: 5px solid #1a9d13; /* green */
                    border-right: 0px none;
            }
            .status_dirty.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner
            {
                    border-left: 5px solid #ea3624; /* red */
            }
            .status_cleanup.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner
            {
                    border-left: 5px solid #f9ba25; /* orange */
            }

        </style>
        
    </head>
    <body>
            <div id="header">
                <div class="bg-help">
                    <div class="inBox">
                        <h1 id="logo"><a href='http://code.daypilot.org/27453/html5-hotel-room-booking-javascript-php'>HTML5 Hotel Room Booking (JavaScript/PHP)</a></h1>
                        <p id="claim"><a href="http://javascript.daypilot.org/">DayPilot for JavaScript</a> - AJAX Calendar/Scheduling Widgets for JavaScript/HTML5/jQuery</p>
                        <hr class="hidden" />
                    </div>
                </div>
            </div>
            <div class="shadow"></div>
            <div class="hideSkipLink">
            </div>
            <div class="main">

                <div class="space">
                    Show rooms:
                    <select id="filter">
                        <option value="0">All</option>
                        <option value="1">Single</option>
                        <option value="2">Double</option>
                        <option value="4">Family</option>
                    </select>
                    
                    <div class="space">
                        Start date: <span id="start"></span> <a href="#" onclick="picker.show(); return false;">Select</a> 
                        Time range: 
                        <select id="timerange">
                            <option value="week">Week</option>
                            <option value="2weeks">2 Weeks</option>
                            <option value="month" selected>Month</option>
                            <option value="2months">2 Months</option>
                        </select>
                        <label for="autocellwidth"><input type="checkbox" id="autocellwidth">Auto Cell Width</label>
                    </div>

                    <script type="text/javascript">
                        var picker = new DayPilot.DatePicker({
                            target: 'start', 
                            pattern: 'M/d/yyyy', 
                            date: new DayPilot.Date().firstDayOfMonth(),
                            onTimeRangeSelected: function(args) { 
                                //dp.startDate = args.start;
                                loadTimeline(args.start);
                                loadEvents();
                            }
                        });
                        
                        $("#timerange").change(function() {
                            switch (this.value) {
                                case "week":
                                    dp.days = 7;
                                    break;
                                case "2weeks":
                                    dp.days = 14;
                                    break;
                                case "month":
                                    dp.days = dp.startDate.daysInMonth();
                                    break;
                                case "2months":
                                    dp.days = dp.startDate.daysInMonth() + dp.startDate.addMonths(1).daysInMonth();
                                    break;
                            }
                            loadTimeline(DayPilot.Date.today());
                            loadEvents();
                        });
                        
                        $("#autocellwidth").click(function() {
                            dp.cellWidth = 40;  // reset for "Fixed" mode
                            dp.cellWidthSpec = $(this).is(":checked") ? "Auto" : "Fixed";
                            dp.update();
                        });
                    </script>                    
                </div>

                <div id="dp"></div>
                

                <script>
                    var dp = new DayPilot.Scheduler("dp");

                    dp.allowEventOverlap = false;

                    //dp.scale = "Day";
                    //dp.startDate = new DayPilot.Date().firstDayOfMonth();
                    dp.days = dp.startDate.daysInMonth();
                    loadTimeline(DayPilot.Date.today().firstDayOfMonth());
                    
                    dp.eventDeleteHandling = "Update";

                    dp.timeHeaders = [
                        { groupBy: "Month", format: "MMMM yyyy" },
                        { groupBy: "Day", format: "d" }
                    ];

                    dp.eventHeight = 50;
                    dp.bubble = new DayPilot.Bubble({});
                    
                    dp.rowHeaderColumns = [
                        {title: "Room", width: 80},
                        {title: "Capacity", width: 80},
                        {title: "Status", width: 80}
                    ];
                    
                    dp.onBeforeResHeaderRender = function(args) {
                        var beds = function(count) {
                            return count + " bed" + (count > 1 ? "s" : "");
                        };
                        
                        args.resource.columns[0].html = beds(args.resource.capacity);
                        args.resource.columns[1].html = args.resource.status;
                        switch (args.resource.status) {
                            case "Dirty":
                                args.resource.cssClass = "status_dirty";
                                break;
                            case "Cleanup":
                                args.resource.cssClass = "status_cleanup";
                                break;
                        }
                    };
                                        
                    // http://api.daypilot.org/daypilot-scheduler-oneventmoved/ 
                    dp.onEventMoved = function (args) {
                        $.post("backend_move.php", 
                        {
                            id: args.e.id(),
                            newStart: args.newStart.toString(),
                            newEnd: args.newEnd.toString(),
                            newResource: args.newResource
                        }, 
                        function(data) {
                            dp.message(data.message);
                        });
                    };

                    // http://api.daypilot.org/daypilot-scheduler-oneventresized/ 
                    dp.onEventResized = function (args) {
                        $.post("backend_resize.php", 
                        {
                            id: args.e.id(),
                            newStart: args.newStart.toString(),
                            newEnd: args.newEnd.toString()
                        }, 
                        function() {
                            dp.message("Resized.");
                        });
                    };
                    
                    dp.onEventDeleted = function(args) {
                        $.post("backend_delete.php", 
                        {
                            id: args.e.id()
                        }, 
                        function() {
                            dp.message("Deleted.");
                        });
                    };
                    
                    // event creating
                    // http://api.daypilot.org/daypilot-scheduler-ontimerangeselected/
                    dp.onTimeRangeSelected = function (args) {
                        //var name = prompt("New event name:", "Event");
                        //if (!name) return;

                        var modal = new DayPilot.Modal();
                        modal.closed = function() {
                            dp.clearSelection();
                            
                            // reload all events
                            var data = this.result;
                            if (data && data.result === "OK") {
                                loadEvents();
                            }
                        };
                        modal.showUrl("new.php?start=" + args.start + "&end=" + args.end + "&resource=" + args.resource);
                        
                    };

                    dp.onEventClick = function(args) {
                        var modal = new DayPilot.Modal();
                        modal.closed = function() {
                            // reload all events
                            var data = this.result;
                            if (data && data.result === "OK") {
                                loadEvents();
                            }
                        };
                        modal.showUrl("edit.php?id=" + args.e.id());
                    };
                    
                    dp.onBeforeCellRender = function(args) {
                        var dayOfWeek = args.cell.start.getDayOfWeek();
                        if (dayOfWeek === 6 || dayOfWeek === 0) {
                            args.cell.backColor = "#f8f8f8";
                        }
                    };

                    dp.onBeforeEventRender = function(args) {
                        var start = new DayPilot.Date(args.e.start);
                        var end = new DayPilot.Date(args.e.end);
                        
                        var today = new DayPilot.Date().getDatePart();
                        
                        args.e.html = args.e.text + " (" + start.toString("M/d/yyyy") + " - " + end.toString("M/d/yyyy") + ")"; 
                        
                        switch (args.e.status) {
                            case "New":
                                var in2days = today.addDays(1);
                                
                                if (start.getTime() < in2days.getTime()) {
                                    args.e.barColor = 'red';
                                    args.e.toolTip = 'Expired (not confirmed in time)';
                                }
                                else {
                                    args.e.barColor = 'orange';
                                    args.e.toolTip = 'New';
                                }
                                break;
                            case "Confirmed":
                                var arrivalDeadline = today.addHours(18);

                                if (start.getTime() < today.getTime() || (start.getTime() === today.getTime() && now.getTime() > arrivalDeadline.getTime())) { // must arrive before 6 pm
                                    args.e.barColor = "#f41616";  // red
                                    args.e.toolTip = 'Late arrival';
                                }
                                else {
                                    args.e.barColor = "green";
                                    args.e.toolTip = "Confirmed";
                                }
                                break;
                            case 'Arrived': // arrived
                                var checkoutDeadline = today.addHours(10);

                                if (end.getTime() < today.getTime() || (end.getTime() === today.getTime() && now.getTime() > checkoutDeadline.getTime())) { // must checkout before 10 am
                                    args.e.barColor = "#f41616";  // red
                                    args.e.toolTip = "Late checkout";
                                }
                                else
                                {
                                    args.e.barColor = "#1691f4";  // blue
                                    args.e.toolTip = "Arrived";
                                }
                                break;
                            case 'CheckedOut': // checked out
                                args.e.barColor = "gray";
                                args.e.toolTip = "Checked out";
                                break;
                            default:
                                args.e.toolTip = "Unexpected state";
                                break;    
                        }
                        
                        args.e.html = args.e.html + "<br /><span style='color:gray'>" + args.e.toolTip + "</span>";
                        
                        var paid = args.e.paid;
                        var paidColor = "#aaaaaa";

                        args.e.areas = [
                            { bottom: 10, right: 4, html: "<div style='color:" + paidColor + "; font-size: 8pt;'>Paid: " + paid + "%</div>", v: "Visible"},
                            { left: 4, bottom: 8, right: 4, height: 2, html: "<div style='background-color:" + paidColor + "; height: 100%; width:" + paid + "%'></div>", v: "Visible" }
                        ];

                    };


                    dp.init();

                    loadResources();
                    loadEvents();
                    
                    function loadTimeline(date) {
                        dp.scale = "Manual";
                        dp.timeline = [];
                        var start = date.getDatePart().addHours(12);
                        
                        for (var i = 0; i < dp.days; i++) {
                            dp.timeline.push({start: start.addDays(i), end: start.addDays(i+1)});
                        }
                        dp.update();
                    }

                    function loadEvents() {
                        var start = dp.startDate;
                        var end = dp.startDate.addDays(dp.days);

                        $.post("backend_events.php", 
                            {
                                start: start.toString(),
                                end: end.toString()
                            },
                            function(data) {
                                dp.events.list = data;
                                dp.update();
                            }
                        );
                    }

                    function loadResources() {
                        $.post("backend_rooms.php", 
                        { capacity: $("#filter").val() },
                        function(data) {
                            dp.resources = data;
                            dp.update();
                        });
                    }
                    
                    $(document).ready(function() {
                        $("#filter").change(function() {
                            loadResources();
                        });
                    });

                </script>

            </div>
            <div class="clear">
            </div>
    </body>
</html>
