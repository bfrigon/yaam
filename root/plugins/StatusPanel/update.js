
$(document).ready(function() {
	window.setInterval(update_widgets, 5000);

});


function update_widgets()
{


	$.ajax({
		type : 'get', 
		url : 'ajax.php?function=StatusPanel/update', 
		dataType : 'text',
		success : function (data) {

			$(data).children('div').each(function(index) {
	
				var id = $(this).attr('id');
			
				var objWidget = $("#" + id);	
				objWidget.html($(this).html());
			});

		}
	});
}




