//******************************************************************************
// index.js - index.php javascript
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 22 sept. 2012
//
// Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************

const default_panel = 'StatusPanel.status';
var objTimerLogout = null;



/*--------------------------------------------------------------------------
 * reset_idletimer() : Reset auto-logout timer
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function reset_idletimer()
{
    clearTimeout(objTimerLogout);

    objTimerLogout = window.setTimeout(function () {
        window.location = "login.php?logout=true";
    }, 600000);
}


/*--------------------------------------------------------------------------
 * current_tab_url() : Get the current tab url
 *
 * Arguments : None
 *
 * Returns   : Current tab url
 */
function current_tab_url()
{
    var url = window.location.href.split('#')[1];

    if (url == undefined || url.length == 0)
        url = 'StatusPanel.status';

    return '#' + url;
}


/*--------------------------------------------------------------------------
 * convert_tab_url() : Convert the url from ?path=xxx&param=1 to #path?param=1
 *
 * Arguments :
 *  - url : url to convert
 *
 * Returns   : New url
 */
function convert_tab_url(url)
{
    if (url.charAt(0) == '#' || url.charAt(0) != '?')
        return url;

    var params = '';
    var path = default_panel
    var query = url.substring(1).split('&');

    $.each(query, function(index, param) {
        param = param.split('=');

        if (param[0] == 'path') {
            path = param[1] != undefined ? param[1] : default_panel;
            return;
        }

        params += param[0] + '=' + (param[1] != undefined ? param[1] : '');

        params += '&';
    });

    return '#' + path + '?' + params;
}


/*--------------------------------------------------------------------------
 * select_tab() : Select a tab without changing its content.
 *
 * Arguments :
 *  - path : tab to select (PLUGIN_NAME.TAB[.CHILD])
 *
 * Returns   : Nothing
 */
function select_tab(path)
{
    if (!$.isArray(path))
        path = path.split('.');

    objPage = $('.page#tab_' + path[1]);

    if (objPage.length == 0)
        return;


    if (path == $('#main').attr('current_tab'))
        return;


    /* Hide previous tab */
    $('#tabs li').removeClass('selected');
    $('.page').hide();

    $('#main').attr('current_url', current_tab_url());
    $('#main').attr('current_tab', path);

    /* Show new tab */
    $('#tabs li#tab_' + path[1]).addClass('selected');

    if (path[2] != undefined && path[2].length > 0)
        $('#tabs li#tab_' + path[1] + ' ul li#page_' + path[2]).addClass('selected');

    objPage.fadeIn(250);

    objPage.promise().done(function() {
        objPage.trigger('tab.aftershow');
        //console.log('event: tab.aftershow');
    });
}


/*--------------------------------------------------------------------------
 * set_tab_url() : Select a tab and update it's content if url has changed
 *
 * Arguments :
 *  - url       : New tab url
 *  - post_data : Form data to submit
 *  - force     : Force to update the tab content.
 *
 * Returns   : Nothing
 */
function set_tab_url(url, post_data, force)
{
    if (url == null || url == undefined || url.length == 0)
        url = $('#main').attr('current_url');

    if (url == undefined)
        url = default_panel;

    if (url.charAt(0) == '?')
        url = convert_tab_url(url);

    if (url.charAt(0) != '#')
        return;


    path = url.split("?");
    params = path[1];
    path = path[0].split(".");
    path[0] = path[0].substring(1);


    /* Find the tab */
    var objTab = $('#tabs li#tab_' + path[1]);
    if (objTab.length == 0)
        return;

    /* If the tab contains child items and no child has been specified
       select the first one by default */
    if (objTab.find('ul li').length > 0 && (path[2] == undefined || path[2].length == 0)) {

        objTabChild = objTab.find('ul li:first-child a');
        url = convert_tab_url(objTabChild.attr('href'));

        if (url.charAt(0) != '#')
            return;

        set_tab_url(url, post_data, force);
        return;
    }

    /* Find the page object associated with this tab */
    objPage = $('.page#tab_' + path[1]);

    /* Create it if it does not exists */
    if (objPage.length == 0) {
        objPage = $('<div>');

        objPage.attr('id', 'tab_' + path[1]);
        objPage.addClass('page');

        if (objTab.find('ul').length > 0)
            objPage.addClass('has-childs');

        $('#main').append(objPage);
    }

    /* Check if the url for the selected tab has changed */
    current_url = objTab.find('> a').attr('href');

    if (url == current_url && !objPage.is(':empty') && post_data == null && !force) {
        select_tab(path.join('.'));
        return;
    }

    objTab.find('> a').attr('href', url);

    /* Prepare the ajax request for the page content */
    url = 'ajax.php?js=1&output=html&path=' + path.join('.');

    //var referer = $('#main').attr('current_url');
    //if (referer != undefined && referer.length > 0)
    //  url += '&referer=' + encodeURIComponent(referer);

    if (params != undefined && params.length > 0)
        url += '&' + params;

    //console.log('url: ' + url + '  ---- post:' + post_data);


    /* Set error handler */
    /*
    window.onerror = function(msg, url, num) {
        alert("Javascript error: '" + msg + "'\nat " + url + ":" + num);

        window.onerror = null;
    };
    */

    /* Send the ajax request */
    $.ajax({
        url: url,
        data: post_data,
        type: (post_data != null ? 'POST' : 'GET'),

        success : function(data) {
            objPage.html(data);

            objPage.trigger('tab.content_change');

            select_tab(path);
        },

        error: function(request) {
            switch (request.status) {
                case 403:
                    window.location = "login.php?logout=true";
                    break;

                default:
                    alert('Ajax request failed : code ' + request.status + '\n url: ' + url);
                    break;
            }
        }

    });
}



//*****************************************************************************
//
// Events
//
//*****************************************************************************

// ----------------------------------------------
// Event: Document ready
// ----------------------------------------------
$(document).ready(function() {


    $('#main').on('click', 'div.toolbar ul li ul a', function(event) {
        $(this).closest('ul').hide();
    });

    $('#main').on('hover', 'div.toolbar > ul > li', function(event) {
        $(this).find('ul').css('display', '');
    });

    $('.dateinput').dateinput({
        format: 'dd/mm/yyyy'
    });


    // ----------------------------------------------
    // Event: anchors click
    // ----------------------------------------------
    $('#main').on('click', 'a', function(event) {

        if ($(this).hasClass('disabled')) {
            event.preventDefault();
            return;
        }

        url = convert_tab_url($(this).attr('href'));
        if (url.charAt(0) != '#')
            return;

        event.preventDefault();


        if ($(this).hasClass('cancel')) {
            history.go(-1);

        } else  if ($(this).hasClass('refresh')) {
            set_tab_url(current_tab_url(), null, true);

        } else {
            window.location = url;
        }


    });





    // ----------------------------------------------
    // Event: submit buttons click
    // ----------------------------------------------
    $('#main').on('click', 'button[type=submit]', function(event) {
        event.preventDefault();

        objForm = $(this).closest('form');

        var url = convert_tab_url(objForm.attr('action'));
        data = objForm.serialize();

        if (this.name.length > 0)
            data += '&' + this.name + '=' + this.value;

        if (objForm.attr("method").toLowerCase() == 'post') {
            set_tab_url(url, data, true);

        } else {
            window.location = convert_tab_url('?' + data);
        }
    });


    $(window).hashchange();

    reset_idletimer();
})


// ----------------------------------------------
// Event: Window location changed (hash)
// ----------------------------------------------
$(window).hashchange( function() {
    set_tab_url(current_tab_url(), null, false);
});


// ----------------------------------------------
// Event : On document mouseover
// ----------------------------------------------
$(document).mousemove(function (event) {
    reset_idletimer();
});

// ----------------------------------------------
// Event : On document keypress
// ----------------------------------------------
$(document).keypress(function (event) {
    reset_idletimer();
});



