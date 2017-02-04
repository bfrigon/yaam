<?php
    if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
        header("Location:../index.php");
        exit();
    }
?>

<script type="text/javascript">
    $(document).ready(function () {

        $(".box.viewer .content").contents().filter(function(){return this.nodeType !== 1;}).each(function() {

            if ($(this).text().match(/error|fatal|panic/i)) {
                $(this).wrap("<span class=\"error\" />");

            } else if ($(this).text().match(/warning/i)) {
                $(this).wrap("<span class=\"warning\" />");
            }
        });
    });
</script>
