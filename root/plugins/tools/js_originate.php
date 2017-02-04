<?php
    if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
        header("Location:../index.php");
        exit();
    }
?>

<script type="text/javascript">


$("#frm_originate").on("submit", function(event){

    event.preventDefault();
    data = $(this).serializeArray();

    data.push({name: "function", value: "tools/originate"});
    alert(data);


    var objProgressDialog = $("#dialog_originate");

    if (objProgressDialog.length == 0) {

        objProgressDialog = $("<div></div>");

        objProgressDialog.addClass("box dialog");
        objProgressDialog.attr("id","dialog_originate");

        $(".page#tab_tools").prepend(objProgressDialog);
    }




    request = $.post("ajax.php", data);

    request.done(function(data) {
        alert(data);


    });

});


/*
$("#frm_originate [name='call']").click(function(e) {

    e.preventDefault();


});
*/


</script>
