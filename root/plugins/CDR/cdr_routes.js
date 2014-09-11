/*
function crte_do_select(selection)
{
	$('#cdr_routes .checkbox').checkbox('checked', (selection == 'all'));
	crte_check_selection();
}

function crte_check_selection()
{
	checked = $('#cdr_routes .checkbox.checked')
	
	$('#toolbar_crte').toolbar('enable_item', 'delete', checked.length > 0);
}



function crte_delete_confirm()
{
	checked = $('#cdr_routes .checkbox.checked')

	if (checked.length == 0)
		return;

	$('#dialog_crte_delete').dialog('open');

}


function crte_do_delete()
{


}




$(document).ready(function() {
	$('#toolbar_crte').toolbar();
	crte_check_selection();
	
	$('#cdr_routes .checkbox').checkbox();
	
	$('#dialog_crte_delete').dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		buttons : {
			'#dialog_crte_delete_yes' : function() {
				$(this).dialog('close');
				crte_do_delete();
			},
			'#dialog_crte_delete_cancel' : function() {
				$(this).dialog('close');
			}
		},
	});
	

	$(".ui-dialog-buttonpane button:contains('#dialog_crte_delete_yes') span")
		.text($('#dialog_crte_delete_yes').text());

	$(".ui-dialog-buttonpane button:contains('#dialog_crte_delete_cancel') span")
		.text($('#dialog_crte_delete_cancel').text());
	
	
	
	$('#cdr_routes').mousedown(function(event) {
		if ($(event.target).hasClass('checkbox'))
			return;
	
		objCheckbox = $(event.target).closest('tr').find('.checkbox');
		objCheckbox.toggleClass('checked');
		
		crte_check_selection();
	});
	
	$('#cdr_routes .checkbox').click(function(event) {
		crte_check_selection();
	});});
	
*/	



