/**
 */

$(document).ready(function() {
    if (window.location.href.indexOf("/front/ticket.form.php?id=") > -1) {
        function blockDateField() {
            console.log('Blocking date field');
        }

        blockDateField();
        /*
        var interval = setInterval(() => {
            if ($('input[name="date"]').length > 0) {
                clearInterval(interval);
                blockDateField();
            }
        }, 100);
        */
    }
});
