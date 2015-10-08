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
function expand(elem)
{
	elem = jQuery(elem);
	if(elem.css('max-height') == '0px')
	{
		elem.css('max-height', '600px');
		elem.parent().children('a.fa').removeClass('fa-chevron-down').addClass('fa-chevron-up');
	}
	else
	{
		elem.css('max-height', '0px');
		elem.parent().children('a.fa').removeClass('fa-chevron-up').addClass('fa-chevron-down');
	}
}
