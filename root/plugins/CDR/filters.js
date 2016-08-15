//******************************************************************************
// filters.js - CDR plugin javascript
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author    : Benoit Frigon <benoit@frigon.info>
//
// Copyright (c) 2011 - 2012 Benoit Frigon
// www.bfrigon.com
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************
$(document).ready(function() {

    $('#cdr_date_filter').dateRangePicker({
        format: '<?php echo get_config_dateformat(DATE_FORMAT_DATEPICKER) ?>',
        autoClose: true,
        separator : ' to ',
        showShortcuts: true,
        shortcuts : null,
        customShortcuts: [
            {
                name: 'Today',
                dates: function() {
                    start = moment().toDate();
                    end = moment().toDate();
                    return [start, end];
               }
            },{
                name: 'Yesterday',
                dates: function() {
                    start = moment().subtract(1, 'days').toDate();
                    end = moment().subtract(1, 'days').toDate();
                    return [start, end];
                }
            },{
                name: 'Previous week',
                dates: function() {
                    start = moment().subtract(1, 'weeks').startOf('week').toDate();
                    end = moment().subtract(1, 'weeks').endOf('week').toDate();
                    return [start, end];
                }
            },{
                name: 'Previous month',
                dates: function() {
                    start = moment().subtract(1, 'months').startOf('month').toDate();
                    end = moment().subtract(1, 'months').endOf('month').toDate();
                    return [start, end];
                }
            },{
                name: 'Year-to-date',
                dates: function() {
                    start = moment().startOf('year').toDate();
                    end = moment().toDate()
                    return [start, end]
                }
            }
        ],

        getValue: function() {
            if ($('#cdr_d_from').val() && $('#cdr_d_to').val() )
                return $('#cdr_d_from').val() + ' to ' + $('#cdr_d_to').val();
            else
                return '';
        },

        setValue: function(s_date, s_from, s_to) {
            $('#cdr_d_from').val(s_from);
            $('#cdr_d_to').val(s_to);
        },

        customOpenAnimation: function(cb) {
            $(this).fadeIn(300, cb);
        },

        customCloseAnimation: function(cb) {
            $(this).fadeOut(300, cb);
        }
    })
    .bind('datepicker-open', function() {

        var elem = $('#cdr_d_from');
        $('.date-picker-wrapper').css({
            position: 'absolute',
            top: '' + (elem.offset().bottom) + 'px',
            left: '' + (elem.offset().left) + 'px',
        });
    });
});
