/**
 * @Package: Ultra Admin HTML Theme
 * @Since: Ultra 1.0
 * This file is part of Ultra Admin Theme HTML package.
 */


jQuery(function($) {

    'use strict';

    var ULTRA_SETTINGS = window.ULTRA_SETTINGS || {};





    /*--------------------------------
         Window Based Layout
     --------------------------------*/
    ULTRA_SETTINGS.windowBasedLayout = function() {
        var width = window.innerWidth;
        //console.log(width);

        if ($("body").hasClass("chat-open") || $("body").hasClass("sidebar-collapse")) {

            ULTRA_SETTINGS.mainmenuCollapsed();

        } else if (width < 1025) {

            // small window
            $(".page-topbar").addClass("sidebar_shift").removeClass("chat_shift");
            $(".page-sidebar").addClass("collapseit").removeClass("expandit");
            $("#main-content").addClass("sidebar_shift").removeClass("chat_shift");
            $(".page-chatapi").removeClass("showit").addClass("hideit");
            $(".chatapi-windows").removeClass("showit").addClass("hideit");
            ULTRA_SETTINGS.mainmenuCollapsed();

        } else {

            // large window
            $(".page-topbar").removeClass("sidebar_shift chat_shift");
            $(".page-sidebar").removeClass("collapseit chat_shift");
            $("#main-content").removeClass("sidebar_shift chat_shift");
            ULTRA_SETTINGS.mainmenuScroll();
        }


    }



    /*--------------------------------
         CHAT API
     --------------------------------*/
    ULTRA_SETTINGS.chatAPI = function() {


        $('.page-topbar .toggle_chat').on('click', function() {
            var chatarea = $(".page-chatapi");
            var chatwindow = $(".chatapi-windows");
            var topbar = $(".page-topbar");
            var mainarea = $("#main-content");
            var menuarea = $(".page-sidebar");

            if (chatarea.hasClass("hideit")) {
                chatarea.addClass("showit").removeClass("hideit");
                chatwindow.addClass("showit").removeClass("hideit");
                topbar.addClass("chat_shift");
                mainarea.addClass("chat_shift");
                menuarea.addClass("chat_shift");
                ULTRA_SETTINGS.mainmenuCollapsed();
            } else {
                chatarea.addClass("hideit").removeClass("showit");
                chatwindow.addClass("hideit").removeClass("showit");
                topbar.removeClass("chat_shift");
                mainarea.removeClass("chat_shift");
                menuarea.removeClass("chat_shift");
                //ULTRA_SETTINGS.mainmenuScroll();
                ULTRA_SETTINGS.windowBasedLayout();
            }
        });

        $('.page-topbar .sidebar_toggle').on('click', function() {
            var chatarea = $(".page-chatapi");
            var chatwindow = $(".chatapi-windows");
            var topbar = $(".page-topbar");
            var mainarea = $("#main-content");
            var menuarea = $(".page-sidebar");

            if (menuarea.hasClass("collapseit") || menuarea.hasClass("chat_shift")) {
                menuarea.addClass("expandit").removeClass("collapseit").removeClass("chat_shift");
                topbar.removeClass("sidebar_shift").removeClass("chat_shift");
                mainarea.removeClass("sidebar_shift").removeClass("chat_shift");
                chatarea.addClass("hideit").removeClass("showit");
                chatwindow.addClass("hideit").removeClass("showit");
                ULTRA_SETTINGS.mainmenuScroll();
            } else {
                menuarea.addClass("collapseit").removeClass("expandit").removeClass("chat_shift");
                topbar.addClass("sidebar_shift").removeClass("chat_shift");
                mainarea.addClass("sidebar_shift").removeClass("chat_shift");
                ULTRA_SETTINGS.mainmenuCollapsed();
            }
        });

    };


    /*--------------------------------
         CHAT API Scroll
     --------------------------------*/
    ULTRA_SETTINGS.chatApiScroll = function() {

        var topsearch = $(".page-chatapi .search-bar").height();
        var height = window.innerHeight - topsearch;
        $('.chat-wrapper').height(height).perfectScrollbar({
            suppressScrollX: true
        });
    };


    /*--------------------------------
         CHAT API window
     --------------------------------*/
    ULTRA_SETTINGS.chatApiWindow = function() {

        var chatarea = $(".page-chatapi");

        $('.page-chatapi .user-row').on('click', function() {

            var name = $(this).find(".user-info h4 a").html();
            var img = $(this).find(".user-img a img").attr("src");
            var id = $(this).attr("data-user-id");
            var status = $(this).find(".user-info .status").attr("data-status");

            if ($(this).hasClass("active")) {
                $(this).toggleClass("active");

                $(".chatapi-windows #user-window" + id).hide();

            } else {
                $(this).toggleClass("active");

                if ($(".chatapi-windows #user-window" + id).length) {

                    $(".chatapi-windows #user-window" + id).removeClass("minimizeit").show();

                } else {
                    var msg = chatformat_msg('Wow! What a Beautiful theme!', 'receive', name);
                    msg += chatformat_msg('Yes! Ultra Admin Theme ;)', 'sent', 'You');
                    var html = "<div class='user-window' id='user-window" + id + "' data-user-id='" + id + "'>";
                    html += "<div class='controlbar'><img src='" + img + "' data-user-id='" + id + "' rel='tooltip' data-animate='animated fadeIn' data-toggle='tooltip' data-original-title='" + name + "' data-placement='top' data-color-class='primary'><span class='status " + status + "'><i class='fa fa-circle'></i></span><span class='name'>" + name + "</span><span class='opts'><i class='fa fa-times closeit' data-user-id='" + id + "'></i><i class='fa fa-minus minimizeit' data-user-id='" + id + "'></i></span></div>";
                    html += "<div class='chatarea'>" + msg + "</div>";
                    html += "<div class='typearea'><input type='text' data-user-id='" + id + "' placeholder='Type & Enter' class='form-control'></div>";
                    html += "</div>";
                    $(".chatapi-windows").append(html);
                }
            }

        });

        $(document).on('click', ".chatapi-windows .user-window .controlbar .closeit", function(e) {
            var id = $(this).attr("data-user-id");
            $(".chatapi-windows #user-window" + id).hide();
            $(".page-chatapi .user-row#chat_user_" + id).removeClass("active");
        });

        $(document).on('click', ".chatapi-windows .user-window .controlbar img, .chatapi-windows .user-window .controlbar .minimizeit", function(e) {
            var id = $(this).attr("data-user-id");

            if (!$(".chatapi-windows #user-window" + id).hasClass("minimizeit")) {
                $(".chatapi-windows #user-window" + id).addClass("minimizeit");
                ULTRA_SETTINGS.tooltipsPopovers();
            } else {
                $(".chatapi-windows #user-window" + id).removeClass("minimizeit");
            }

        });

        $(document).on('keypress', ".chatapi-windows .user-window .typearea input", function(e) {
            if (e.keyCode == 13) {
                var id = $(this).attr("data-user-id");
                var msg = $(this).val();
                msg = chatformat_msg(msg, 'sent', 'You');
                $(".chatapi-windows #user-window" + id + " .chatarea").append(msg);
                $(this).val("");
                $(this).focus();
            }
            $(".chatapi-windows #user-window" + id + " .chatarea").perfectScrollbar({
                suppressScrollX: true
            });
        });

    };

    function chatformat_msg(msg, type, name) {
        var d = new Date();
        var h = d.getHours();
        var m = d.getMinutes();
        return "<div class='chatmsg msg_" + type + "'><span class='name'>" + name + "</span><span class='text'>" + msg + "</span><span class='ts'>" + h + ":" + m + "</span></div>";
    }


    /*--------------------------------
         Login Page
     --------------------------------*/
    ULTRA_SETTINGS.loginPage = function() {

        var height = window.innerHeight;
        var formheight = $("#login").height();
        var newheight = (height - formheight) / 2;
        //console.log(height+" - "+ formheight + " / "+ newheight);
        $('#login').css('margin-top', +newheight + 'px');

        if ($('#login #user_login').length) {
            var d = document.getElementById('user_login');
            d.focus();
        }

    };



    /*--------------------------------
         Search Page
     --------------------------------*/
    ULTRA_SETTINGS.searchPage = function() {

        $('.search_data .tab-pane').perfectScrollbar({
            suppressScrollX: true
        });
        var search = $(".search-page-input");
        if (search.length) {
            search.focus();
        }
    };


    /*--------------------------------
        Viewport Checker
     --------------------------------*/
    ULTRA_SETTINGS.viewportElement = function() {

        if ($.isFunction($.fn.viewportChecker)) {

            $('.inviewport').viewportChecker({
                callbackFunction: function(elem, action) {
                    //setTimeout(function(){
                    //elem.html((action == "add") ? 'Callback with 500ms timeout: added class' : 'Callback with 500ms timeout: removed class');
                    //},500);
                }
            });


            $('.number_counter').viewportChecker({
                classToAdd: 'start_timer',
                offset: 10,
                callbackFunction: function(elem) {
                    $('.start_timer:not(.counted)').each(count);
                    //$(elem).removeClass('number_counter');
                }
            });

        }

    };



    /*--------------------------------
        Sortable / Draggable Panels
     --------------------------------*/
    ULTRA_SETTINGS.draggablePanels = function() {

        if ($.isFunction($.fn.sortable)) {
            $(".sort_panel").sortable({
                connectWith: ".sort_panel",
                handle: "header.panel_header",
                cancel: ".panel_actions",
                placeholder: "portlet-placeholder"
            });
        }
    };



    /*--------------------------------
         Breadcrumb autoHidden
     --------------------------------*/
    ULTRA_SETTINGS.breadcrumbAutoHidden = function() {

        $('.breadcrumb.auto-hidden a').on('mouseover', function() {
            $(this).removeClass("collapsed");
        });
        $('.breadcrumb.auto-hidden a').on('mouseout', function() {
            $(this).addClass("collapsed");
        });

    };





    /*--------------------------------
         Section Box Actions
     --------------------------------*/
    ULTRA_SETTINGS.sectionBoxActions = function() {

        $('section.box .actions .box_toggle').on('click', function() {

            var content = $(this).parent().parent().parent().find(".content-body");
            if (content.hasClass("collapsed")) {
                content.removeClass("collapsed").slideDown(500);
                $(this).removeClass("fa-chevron-up").addClass("fa-chevron-down");
            } else {
                content.addClass("collapsed").slideUp(500);
                $(this).removeClass("fa-chevron-down").addClass("fa-chevron-up");
            }

        });

        $('section.box .actions .box_close').on('click', function() {
            content = $(this).parent().parent().parent().remove();
        });



    };






    /*--------------------------------
         Main Menu Scroll
     --------------------------------*/
    ULTRA_SETTINGS.mainmenuScroll = function() {

    	var topbar = $(".page-topbar").height();
        var projectinfo = $(".project-info").innerHeight();

        var height = window.innerHeight - topbar - projectinfo;

        $('#main-menu-wrapper').height(height).perfectScrollbar({
            suppressScrollX: true
        });
        $("#main-menu-wrapper .wraplist").height('auto');


        /*show first sub menu of open menu item only - opened after closed*/
        // > in the selector is used to select only immediate elements and not the inner nested elements.
        $("li.open > .sub-menu").attr("style", "display:block;");


    };


    /*--------------------------------
         Collapsed Main Menu
     --------------------------------*/
    ULTRA_SETTINGS.mainmenuCollapsed = function() {

        if ($(".page-sidebar.chat_shift #main-menu-wrapper").length > 0 || $(".page-sidebar.collapseit #main-menu-wrapper").length > 0) {

            var topbar = $(".page-topbar").height();
            var windowheight = window.innerHeight;
            var minheight = windowheight - topbar;
            var fullheight = $(".page-container #main-content .wrapper").height();

            var height = fullheight;

            if (fullheight < minheight) {
                height = minheight;
            }

            $('#main-menu-wrapper').perfectScrollbar('destroy');

            $('.page-sidebar.chat_shift #main-menu-wrapper .wraplist, .page-sidebar.collapseit #main-menu-wrapper .wraplist').height(height);

            /*hide sub menu of open menu item*/
            $("li.open .sub-menu").attr("style", "");

        }

    };




    /*--------------------------------
         Main Menu
     --------------------------------*/
    ULTRA_SETTINGS.mainMenu = function() {
        $('#main-menu-wrapper li a').click(function(e) {

            if ($(this).next().hasClass('sub-menu') === false) {
                return;
            }

            var parent = $(this).parent().parent();
            var sub = $(this).next();

            parent.children('li.open').children('.sub-menu').slideUp(200);
            parent.children('li.open').children('a').children('.arrow').removeClass('open');
            parent.children('li').removeClass('open');

            if (sub.is(":visible")) {
                $(this).find(".arrow").removeClass("open");
                sub.slideUp(200);
            } else {
                $(this).parent().addClass("open");
                $(this).find(".arrow").addClass("open");
                sub.slideDown(200);
            }

        });
    };



    /*--------------------------------
         Mailbox
     --------------------------------*/
    ULTRA_SETTINGS.mailboxInbox = function() {

        $('.mail_list table .star i').click(function(e) {
            $(this).toggleClass("fa-star fa-star-o");
        });

        $('.mail_list .open-view').click(function(e) {
            window.location = 'mail-view.html';
        });

        $('.mail_view_info .labels .cc').click(function(e) {
            var ele = $(".mail_compose_cc");
            if (ele.is(":visible")) {
                ele.hide();
            } else {
                ele.show();
            }
        });

        $('.mail_view_info .labels .bcc').click(function(e) {
            var ele = $(".mail_compose_bcc");
            if (ele.is(":visible")) {
                ele.hide();
            } else {
                ele.show();
            }
        });

    };




    /*--------------------------------
         Top Bar
     --------------------------------*/
    ULTRA_SETTINGS.pageTopBar = function() {
        $('.page-topbar li.searchform .input-group-addon').click(function(e) {
            $(this).parent().parent().toggleClass("focus");
            $(this).parent().find("input").focus();
        });

        $('.page-topbar li .dropdown-menu .list').perfectScrollbar({
            suppressScrollX: true
        });

    };


    /*--------------------------------
         Extra form settings
     --------------------------------*/
    ULTRA_SETTINGS.extraFormSettings = function() {

        // transparent input group focus/blur
        $('.input-group .form-control').focus(function(e) {
            $(this).parent().find(".input-group-addon").addClass("input-focus");
            $(this).parent().find(".input-group-btn").addClass("input-focus");
        });

        $('.input-group .form-control').blur(function(e) {
            $(this).parent().find(".input-group-addon").removeClass("input-focus");
            $(this).parent().find(".input-group-btn").removeClass("input-focus");
        });

    };

   

    /*--------------------------------
         Pretty Photo
     --------------------------------*/
    ULTRA_SETTINGS.loadPrettyPhoto = function() {

        if ($.isFunction($.fn.prettyPhoto)) {
            //Pretty Photo
            $("a[rel^='prettyPhoto']").prettyPhoto({
                social_tools: false
            });
        }
    };

    /*--------------------------------
         Sortable (Nestable) List
     --------------------------------*/
    ULTRA_SETTINGS.nestableList = function() {

        $("#nestableList-1").on('stop.uk.nestable', function(ev) {
            var serialized = $(this).data('nestable').serialize(),
                str = '';

            str = nestableIterate(serialized, 0);

            $("#nestableList-1-ev").val(str);
        });


        function nestableIterate(items, depth) {
            var str = '';

            if (!depth)
                depth = 0;

            //console.log(items);

            jQuery.each(items, function(i, obj) {
                str += '[ID: ' + obj.itemId + ']\t' + nestableRepeat('—', depth + 1) + ' ' + obj.item;
                str += '\n';

                if (obj.children) {
                    str += nestableIterate(obj.children, depth + 1);
                }
            });

            return str;
        }

        function nestableRepeat(s, n) {
            var a = [];
            while (a.length < n) {
                a.push(s);
            }
            return a.join('');
        }
    };









    /*--------------------------------
         Tooltips & Popovers
     --------------------------------*/
    ULTRA_SETTINGS.tooltipsPopovers = function() {

        $('[rel="tooltip"]').each(function() {
            var animate = $(this).attr("data-animate");
            var colorclass = $(this).attr("data-color-class");
            $(this).tooltip({
                template: '<div class="tooltip ' + animate + ' ' + colorclass + '"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
            });
        });

        $('[rel="popover"]').each(function() {
            var animate = $(this).attr("data-animate");
            var colorclass = $(this).attr("data-color-class");
            $(this).popover({
                template: '<div class="popover ' + animate + ' ' + colorclass + '"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'
            });
        });

    };





    /*--------------------------------
         iCheck
     --------------------------------*/
    ULTRA_SETTINGS.iCheck = function() {



        if ($.isFunction($.fn.iCheck)) {


            $('input[type="checkbox"].iCheck').iCheck({
                checkboxClass: 'icheckbox_minimal',
                radioClass: 'iradio_minimal',
                increaseArea: '20%'
            });


            var x;
            var colors = ["-green", "-red", "-yellow", "-blue", "-aero", "-orange", "-grey", "-pink", "-purple","-white"];

            for (x = 0; x < colors.length; x++) {

                if (x == 0) {
                    $('input.icheck-minimal').iCheck({
                        checkboxClass: 'icheckbox_minimal' + colors[x],
                        radioClass: 'iradio_minimal' + colors[x],
                        increaseArea: '20%'
                    });

                    $('input.skin-square').iCheck({
                        checkboxClass: 'icheckbox_square' + colors[x],
                        radioClass: 'iradio_square' + colors[x],
                        increaseArea: '20%'
                    });

                    $('input.skin-flat').iCheck({
                        checkboxClass: 'icheckbox_flat' + colors[x],
                        radioClass: 'iradio_flat' + colors[x],
                    });


                    $('input.skin-line').each(function() {
                        var self = $(this),
                            label = self.next(),
                            label_text = label.text();

                        label.remove();
                        self.iCheck({
                            checkboxClass: 'icheckbox_line' + colors[x],
                            radioClass: 'iradio_line' + colors[x],
                            insert: '<div class="icheck_line-icon"></div>' + label_text
                        });
                    });

                } // end x = 0

                $('input.icheck-minimal' + colors[x]).iCheck({
                    checkboxClass: 'icheckbox_minimal' + colors[x],
                    radioClass: 'iradio_minimal' + colors[x],
                    increaseArea: '20%'
                });


                $('input.skin-square' + colors[x]).iCheck({
                    checkboxClass: 'icheckbox_square' + colors[x],
                    radioClass: 'iradio_square' + colors[x],
                    increaseArea: '20%'
                });


                $('input.skin-flat' + colors[x]).iCheck({
                    checkboxClass: 'icheckbox_flat' + colors[x],
                    radioClass: 'iradio_flat' + colors[x],
                });


                $('input.skin-line' + colors[x]).each(function() {
                    var self = $(this),
                        label = self.next(),
                        label_text = label.text();

                    label.remove();
                    self.iCheck({
                        checkboxClass: 'icheckbox_line' + colors[x],
                        radioClass: 'iradio_line' + colors[x],
                        insert: '<div class="icheck_line-icon"></div>' + label_text
                    });
                });

            } // end for loop


        }
    };

    /*--------------------------------
         Other Form component Scripts
     --------------------------------*/
    ULTRA_SETTINGS.otherScripts = function() {



        /*--------------------------------*/


        if ($.isFunction($.fn.autosize)) {
            $(".autogrow").autosize();
        }

        /*---------------------------------*/

        // autoNumeric
        if ($.isFunction($.fn.autoNumeric)) {
            $('.autoNumeric').autoNumeric('init');
        }

        /*---------------------------------*/

        // Slider
        if ($.isFunction($.fn.slider)) {
            $(".slider").each(function(i, el) {
                var $this = $(el),
                    $label_1 = $('<span class="ui-label"></span>'),
                    $label_2 = $label_1.clone(),

                    orientation = getValue($this, 'vertical', 0) != 0 ? 'vertical' : 'horizontal',

                    prefix = getValue($this, 'prefix', ''),
                    postfix = getValue($this, 'postfix', ''),

                    fill = getValue($this, 'fill', ''),
                    $fill = $(fill),

                    step = getValue($this, 'step', 1),
                    value = getValue($this, 'value', 5),
                    min = getValue($this, 'min', 0),
                    max = getValue($this, 'max', 100),
                    min_val = getValue($this, 'min-val', 10),
                    max_val = getValue($this, 'max-val', 90),

                    is_range = $this.is('[data-min-val]') || $this.is('[data-max-val]'),

                    reps = 0;


                // Range Slider Options
                if (is_range) {
                    $this.slider({
                        range: true,
                        orientation: orientation,
                        min: min,
                        max: max,
                        values: [min_val, max_val],
                        step: step,
                        slide: function(e, ui) {
                            var min_val = (prefix ? prefix : '') + ui.values[0] + (postfix ? postfix : ''),
                                max_val = (prefix ? prefix : '') + ui.values[1] + (postfix ? postfix : '');

                            $label_1.html(min_val);
                            $label_2.html(max_val);

                            if (fill)
                                $fill.val(min_val + ',' + max_val);

                            reps++;
                        },
                        change: function(ev, ui) {
                            if (reps == 1) {
                                var min_val = (prefix ? prefix : '') + ui.values[0] + (postfix ? postfix : ''),
                                    max_val = (prefix ? prefix : '') + ui.values[1] + (postfix ? postfix : '');

                                $label_1.html(min_val);
                                $label_2.html(max_val);

                                if (fill)
                                    $fill.val(min_val + ',' + max_val);
                            }

                            reps = 0;
                        }
                    });

                    var $handles = $this.find('.ui-slider-handle');

                    $label_1.html((prefix ? prefix : '') + min_val + (postfix ? postfix : ''));
                    $handles.first().append($label_1);

                    $label_2.html((prefix ? prefix : '') + max_val + (postfix ? postfix : ''));
                    $handles.last().append($label_2);
                }
                // Normal Slider
                else {

                    $this.slider({
                        range: getValue($this, 'basic', 0) ? false : "min",
                        orientation: orientation,
                        min: min,
                        max: max,
                        value: value,
                        step: step,
                        slide: function(ev, ui) {
                            var val = (prefix ? prefix : '') + ui.value + (postfix ? postfix : '');

                            $label_1.html(val);


                            if (fill)
                                $fill.val(val);

                            reps++;
                        },
                        change: function(ev, ui) {
                            if (reps == 1) {
                                var val = (prefix ? prefix : '') + ui.value + (postfix ? postfix : '');

                                $label_1.html(val);

                                if (fill)
                                    $fill.val(val);
                            }

                            reps = 0;
                        }
                    });

                    var $handles = $this.find('.ui-slider-handle');
                    //$fill = $('<div class="ui-fill"></div>');

                    $label_1.html((prefix ? prefix : '') + value + (postfix ? postfix : ''));
                    $handles.html($label_1);

                    //$handles.parent().prepend( $fill );

                    //$fill.width($handles.get(0).style.left);
                }

            })
        }



        /*------------- Color Slider widget---------------*/

        function hexFromRGB(r, g, b) {
            var hex = [
                r.toString(16),
                g.toString(16),
                b.toString(16)
            ];
            $.each(hex, function(nr, val) {
                if (val.length === 1) {
                    hex[nr] = "0" + val;
                }
            });
            return hex.join("").toUpperCase();
        }

        function refreshSwatch() {
            var red = $("#slider-red").slider("value"),
                green = $("#slider-green").slider("value"),
                blue = $("#slider-blue").slider("value"),
                hex = hexFromRGB(red, green, blue);
            $("#slider-swatch").css("background-color", "#" + hex);
        }


        if ($.isFunction($.fn.slider)) {

            $(function() {
                $("#slider-red, #slider-green, #slider-blue").slider({
                    orientation: "horizontal",
                    range: "min",
                    max: 255,
                    value: 127,
                    slide: refreshSwatch,
                    change: refreshSwatch
                });
                $("#slider-red").slider("value", 235);
                $("#slider-green").slider("value", 70);
                $("#slider-blue").slider("value", 60);
            });
        }



        /*-------------------------------------*/

        /*--------------------------------*/


        // Spinner
        if ($.isFunction($.fn.spinner)) {

                $( "#spinner" ).spinner();

                $( "#spinner2" ).spinner({
                    min: 5,
                    max: 2500,
                    step: 25,
                    start: 1000,
                    numberFormat: "C"
                });


                $( "#spinner3" ).spinner({
                    spin: function( event, ui ) {
                        if ( ui.value > 10 ) {
                            $( this ).spinner( "value", -10 );
                            return false;
                        } else if ( ui.value < -10 ) {
                            $( this ).spinner( "value", 10 );
                            return false;
                        }
                    }
                });
}
        /*------------------------------------*/

        // tagsinput
        if ($.isFunction($.fn.tagsinput)) {

            // categorize tags input
            var i = -1,
                colors = ['primary', 'info', 'warning', 'success'];

            colors = shuffleArray(colors);

            $("#tagsinput-2").tagsinput({
                tagClass: function() {
                    i++;
                    return "label label-" + colors[i % colors.length];
                }
            });


            $(".mail_compose_to").tagsinput({
                tagClass: function() {
                    i++;
                    return "label label-" + colors[i % colors.length];
                }
            });


        }

        // Just for demo purpose
        function shuffleArray(array) {
            for (var i = array.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = array[i];
                array[i] = array[j];
                array[j] = temp;
            }
            return array;
        }

        /*------------------------------------------*/

    };



    /*--------------------------------
        Widgets
     --------------------------------*/
    ULTRA_SETTINGS.ultraWidgets = function() {

        /*notification widget*/
        var notif_widget = $(".notification-widget").height();
        $('.notification-widget').height(notif_widget).perfectScrollbar({
            suppressScrollX: true
        });

    };



    /*--------------------------------
        weather widget
     --------------------------------*/
    ULTRA_SETTINGS.ultraWidgetWeather = function() {

        /*notification widget*/
        /*var wid = $(".wid-weather");
        var notif_widget = $(".notification-widget").height();
        $('.notification-widget').height(notif_widget).perfectScrollbar({suppressScrollX: true});

        $('.wid-weather').each( function () {
                var days = $(this).find(".weekdays");
                var today = $(this).find(".today");

                var height = days.height();
                if(days.height() < today.height()){
                    height = today.height();
                }

                days.height(height);
                today.height(height);
        });*/


        $('.wid-weather .weekdays ul').perfectScrollbar({
            suppressScrollX: true
        });


    };





    /*--------------------------------
        To Do Task Widget
     --------------------------------*/
    ULTRA_SETTINGS.ultraToDoWidget = function() {

        /*todo task widget*/
        $(".icheck-minimal-white.todo-task").on('ifChecked', function(event) {
            $(this).parent().parent().addClass("checked");
        });
        $(".icheck-minimal-white.todo-task").on('ifUnchecked', function(event) {
            $(this).parent().parent().removeClass("checked");
        });

        $(".wid-all-tasks ul").perfectScrollbar({
            suppressScrollX: true
        });

    };



    /*--------------------------------
        To Do Add Task Widget
     --------------------------------*/
    ULTRA_SETTINGS.ultraToDoAddTaskWidget = function() {

        $(".wid-add-task input").on('keypress', function(e) {
            if (e.keyCode == 13) {
                var i = Math.random().toString(36).substring(7);
                var msg = $(this).val();
                var msg = '<li><input type="checkbox" id="task-' + i + '" class="icheck-minimal-white todo-task"><label class="icheck-label form-label" for="task-' + i + '">' + msg + '</label></li>';
                $(this).parent().parent().find(".wid-all-tasks ul").append(msg);
                $(this).val("");
                $(this).focus();
                ULTRA_SETTINGS.iCheck();
                ULTRA_SETTINGS.ultraToDoWidget();
                $(this).parent().parent().find(".wid-all-tasks ul").perfectScrollbar('update');
            }
        });

    };

    // Element Attribute Helper
    function getValue($el, data_var, default_val) {
        if (typeof $el.data(data_var) != 'undefined') {
            return $el.data(data_var);
        }

        return default_val;
    }


    /******************************
     initialize respective scripts 
     *****************************/
    $(document).ready(function() {
        ULTRA_SETTINGS.windowBasedLayout();
        ULTRA_SETTINGS.mainmenuScroll();
        ULTRA_SETTINGS.mainMenu();
        ULTRA_SETTINGS.mainmenuCollapsed();
        ULTRA_SETTINGS.pageTopBar();
        ULTRA_SETTINGS.otherScripts();
        ULTRA_SETTINGS.iCheck();
        ULTRA_SETTINGS.extraFormSettings();
        ULTRA_SETTINGS.tooltipsPopovers();
        ULTRA_SETTINGS.nestableList();
        ULTRA_SETTINGS.loadPrettyPhoto();
        ULTRA_SETTINGS.breadcrumbAutoHidden();
        ULTRA_SETTINGS.chatAPI();
        ULTRA_SETTINGS.chatApiScroll();
        ULTRA_SETTINGS.chatApiWindow();
        ULTRA_SETTINGS.mailboxInbox();
        ULTRA_SETTINGS.ultraWidgets();
        ULTRA_SETTINGS.sectionBoxActions();
        ULTRA_SETTINGS.draggablePanels();
        ULTRA_SETTINGS.viewportElement();
        ULTRA_SETTINGS.searchPage();
        ULTRA_SETTINGS.ultraToDoAddTaskWidget();
        ULTRA_SETTINGS.ultraToDoWidget();
        ULTRA_SETTINGS.ultraWidgetWeather();
    });

    $(window).resize(function() {
        ULTRA_SETTINGS.windowBasedLayout();
        ULTRA_SETTINGS.mainmenuScroll();
        ULTRA_SETTINGS.loginPage();
    });

    $(window).load(function() {
        ULTRA_SETTINGS.loginPage();
    });

});
