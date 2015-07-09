//Get URL parameter for pre-sorting the contact list
function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) 
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) 
        {
            return sParameterName[1];
        }
    }
} 
//Search function for pre-sorting and textbox search
function searchStaff(searchString)
{
	var query = searchString.replace("_", " ");
	var count = 0;
	jQuery('div.staff-box').each(function () {
        var jQuerythis = jQuery(this);
        if (jQuerythis.text().toLowerCase().indexOf(query) === -1) jQuerythis.closest('div.staff-box').hide();
        else 
        {
        	jQuerythis.closest('div.staff-box').show();
        count++;
    }}
    );
    if (count === 0) jQuery('#msgNoResults').css('visibility','visible');
    else jQuery('#msgNoResults').css('visibility','hidden');;
}
//Search on textbox input
jQuery('#searchBox').keyup(function () {
    var query = jQuery.trim(jQuery('#searchBox').val()).toLowerCase();
    searchStaff(query);
});
//Search on dropdown list change
jQuery('#searchDDL').change(function () {
    var query = jQuery("#searchDDL option:selected").text().toLowerCase();
    searchStaff(query);
});
//Page load: Forces google docs table to format correctly and handles URL params
jQuery(document).ready(function(jQuery) {
	jQuery('table').removeAttr('style').css("border-collapse","separate");
	var department = getUrlParameter('department');
	if (department != 'undefined')
	{
		searchStaff(department);
	}
});


         