$(document).ready(function() {
    $('#loginForm').submit(function(event)
    {
      if($('#pwhash').val())
        $('#pwhash').val(CryptoJS.SHA256($('#pwhash').val()));
    });
    $('#toggleDiv :input').attr('disabled', true);
    //$('#toggle').attr('checked', true);
});
function toggle() {
    if (!$('#toggle').is(':checked')) {
        $('#toggleDiv :input').attr('disabled', true);
    } else {
        $('#toggleDiv :input').removeAttr('disabled');
    }
}
removeParent = function(e) {
    $(e).parent().remove();
}
