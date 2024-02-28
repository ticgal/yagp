/**
 */

$(document).ready(function() {
    if (window.location.href.indexOf("/front/ticket.form.php?id=") > -1) {
        function blockDateField() {
            var input = $('div#itil-data input[name="date"]').next();
            input.attr('disabled', 'disabled');
        }

        var interval = setInterval(() => {
            if ($('div#itil-data input[name="date"]').length > 0) {
                clearInterval(interval);
                blockDateField();
            }
        }, 100);
    }
});
