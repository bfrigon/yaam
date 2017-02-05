<?php
    if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
        header("Location:../index.php");
        exit();
    }
?>

<script type="text/javascript">

    var objProgressDialog;
    var call_hangup = false
    var call_id = null;
    var timer_check_progress = null;


    /*--------------------------------------------------------------------------
     * reset_progress_dialog() : Delete progress dialog box content, or creates
     *                           the dialog box if it does not exists.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns   : Nothing
     */
    function reset_progress_dialog()
    {
        objProgressDialog = $("#dialog_originate");

        /* Create the status dialog if it does not exists */
        if (objProgressDialog.length == 0) {

            objProgressDialog = $("<div></div>");

            objProgressDialog.addClass("box dialog");
            objProgressDialog.attr("id","dialog_originate");

            /* Adds it to the page */
            $(".page#tab_tools").prepend(objProgressDialog);
        }

        objProgressDialog.empty();
        objProgressDialog.show();
    }


    /*--------------------------------------------------------------------------
     * add_progress_message() : Adds a message to the progress dialog box.
     *
     * Arguments
     * ---------
     *  - message : The message to add.
     *  - error   : True to display the message as an error, false otherwise.
     *
     * Returns   : Nothing
     */
    function add_progress_message(message, error=false)
    {
        objMessage = $("<div></div>");

        objMessage.append(message);

        if (error) {
            objMessage.addClass("error");
            objProgressDialog.addClass("error");
            objProgressDialog.removeClass("info");
        } else {
            objProgressDialog.addClass("info");
            objProgressDialog.removeClass("error");
        }

        objProgressDialog.append(objMessage);
    }


    /*--------------------------------------------------------------------------
     * on_timer_check_progress() : Timer event callback for monitoring the
     *                             originate call request progress.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns   : Nothing
     */
    function on_timer_check_progress()
    {
        request = send_ajax_request("progress", {"uniqueid" : call_id});

        request.done(function(data) {

            response = JSON.parse(data);
            //console.log(response);

            switch(response["status"]) {
                case "done":

                    add_progress_message(response["message"]);

                    /* Hangup channels which were not picked up */
                    request = send_ajax_request("hangup", { "uniqueid" : call_id });

                    call_id = null;
                    return;

                case "error":

                    add_progress_message(response["message"], true);

                    call_id = null;
                    return;

                default:
                    /* Rearm the timer for the next progress check */
                    timer_check_progress = setTimeout(on_timer_check_progress, 1000);
            }
        });
    }


    /*--------------------------------------------------------------------------
     * send_ajax_request() : Sends a request to the originate ajax function
     *
     * Arguments
     * ---------
     *  - action : Action to perform
     *  - query  : Additional parameters to send.
     *
     * Returns   : Request object
     */
    function send_ajax_request(action, query)
    {

        frm_data = $("#frm_originate").serializeArray();
        $(frm_data).each(function(index, obj){

            query[obj.name] = obj.value;
        });

        query["output"] = "json";
        query["function"] = "tools/originate";
        query["action"] = action;

        /* Send the originate call request */
        return $.post("ajax.php", query);
    }


    /*--------------------------------------------------------------------------
     *
     * jquery : #frm_originate 'on submit form' event handler
     *
     */
    $("#frm_originate").on("submit", function(event){
        call_hangup = false;

        event.preventDefault();

        /* Return if a request is already running */
        if (call_id != null)
            return;

        /* Initialize the call progress dialog box */
        reset_progress_dialog();


        /* Send the originate call request */
        request = send_ajax_request("call", {});

        request.done(function(data) {

            response = JSON.parse(data);
            call_id = response["call_id"];

            /* Display result from the originate request */
            add_progress_message(response["message"], response["status"] == "error")

            if (response["status"] == "ok") {
                timer_check_progress = setTimeout(on_timer_check_progress, 1000);
            }
        });
    });


    /*--------------------------------------------------------------------------
     *
     * jquery : #frm_originate 'on submit form' event handler
     *
     */
    $("#frm_originate_cancel").on("click", function(event) {

        if (call_id == null)
            return;

        event.preventDefault();

        /* Cancel progress check timer */
        if (timer_check_progress != null)
           clearTimeout(timer_check_progress)

        /* Hangup all channels */
        request = send_ajax_request("hangup", { "uniqueid" : call_id });

        objProgressDialog.hide();

        call_id = null;
    });
</script>
