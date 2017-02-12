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


    /*--------------------------------------------------------------------------
     * On change event handler for datagrid select-all checkboxes. Check or
     * uncheck all select-all-item checkboxes in the datagrid.
     *
     */
    $(".select-all").change(function() {

        objGrid = $(this).closest(".grid");
        checked = $(this).is(":checked");


        objGrid.find(".select-all-item").each(function() {
            $(this).prop("checked", checked);
        });

    });


    /*--------------------------------------------------------------------------
     * On change event handler for datagrid select-all-item checkboxes. Set the
     * state of the select-all checkbox (uncheck all, check all or indeterminate)
     *
     */
    $(".select-all-item").change(function() {
        var checkCount = 0;

        objGrid = $(this).closest(".grid");
        objSelectItems = objGrid.find(".select-all-item");
        objSelect = objGrid.find(".select-all");

        /* Count the number of checked items */
        objSelectItems.each(function() {
            if ($(this).is(":checked"))
                checkCount++;
        });


        if (checkCount == 0) {
            objSelect.prop("indeterminate", false);
            objSelect.prop("checked", false);

        } else if (checkCount == objSelectItems.length) {
            objSelect.prop("indeterminate", false);
            objSelect.prop("checked", true);

        } else {
            objSelect.prop("indeterminate", true);
        }
    });
});
