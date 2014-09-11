/*

function cnam_apply_filters()
{
	$('#cnam_filters').submit();

}

function cnam_clear_filters()
{
	$('#cnam_txtsearch').val('');
	$('#cnam_filters').submit();
}

function cnam_do_select(selection)
{
	$('#cnam .checkbox').checkbox('checked', (selection == 'all'));
	cnam_check_selection();
}

function cnam_delete_confirm()
{

	checked = $('#cnam .checkbox.checked')

	if (checked.length == 0)
		return;

	$('#dialog_cnam_delete').dialog('open');
}

function cnam_check_selection()
{
	checked = $('#cnam .checkbox.checked')
	
	$('#toolbar_cnam').toolbar('enable_item', 'delete', checked.length > 0);
}


function cnam_do_delete()
{
	checked = $('#cnam .checkbox.checked')

	if (checked.length == 0)
		return;

	id_list = [];

	checked.each(function() {
		var id = $(this).attr('data-id');

		id_list.push(id);
	});
	
	$.ajax({
		type : 'post', 
		url : 'ajax.php?path=Tools/cnam&action=delete', 
		data : {'id' : id_list}, 
		dataType : 'json',
		success : function (data) {
			if (data.error != undefined) {
				alert(data.error);
				return;
			} else if (data.success == undefined) {
				alert('Error: Ajax script failed')
				return;
			}
			
			reload_current_tab(true);
		}
	});
}

$(document).ready(function() {

	$('#toolbar_cnam').toolbar();
	cnam_check_selection();
	
	$('.checkbox').checkbox();
	
	$('#dialog_cnam_delete').dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		buttons : {
			'#dialog_cnam_delete_yes' : function() {
				$(this).dialog('close');
				cnam_do_delete();
			},
			'#dialog_cnam_delete_cancel' : function() {
				$(this).dialog('close');
			}
		},
	});
	
	
	$(".ui-dialog-buttonpane button:contains('#dialog_cnam_delete_yes') span")
		.text($('#dialog_cnam_delete_yes').text());

	$(".ui-dialog-buttonpane button:contains('#dialog_cnam_delete_cancel') span")
		.text($('#dialog_cnam_delete_cancel').text());
	
	
	
	$('#cnam').mousedown(function(event) {
		if ($(event.target).hasClass('checkbox'))
			return;
			
		objTablecell = $(event.target).closest('td');
		objTablerow = $(event.target).closest('tr');
		
		if (objTablecell.hasClass('column-icon')) {
			objCheckbox = objTablerow.find('.checkbox');
			objCheckbox.toggleClass('checked');
			
			cnam_check_selection();
		} else if (objTablecell.hasClass('column-actions')) {
			
			return;
		} else {
		
		
		}
	});
	
	$('#cnam .checkbox').click(function(event) {
		cnam_check_selection();
	});
	
});

*/
