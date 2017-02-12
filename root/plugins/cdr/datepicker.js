//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
//
//
// -----------------------------------------------------------------------------
//
// Copyright (c) 2017 Benoit Frigon
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
//******************************************************************************

$(document).ready(function() {

    date_format = $('#cdr_d_from').attr("placeholder");


    $('#cdr_d_from,#cdr_d_to').each(function() {
        $(this).dateRangePicker({
            format: date_format,
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
});
