var section = "";
var module = "";
var type = "";
var template = "";

$(document).on('click', '.variable-edit', function(e){
	tag = $(this).attr('Tag');
	editorType = "text";
	if($(this).attr('type') == "html")
	{
		editorType = "html";
	}
	EditVariable(tag, $('.variable-value[Tag='+tag+']').html(), editorType);
	e.preventDefault();
});

function InitVariableEdit(in_section)
{
	section = in_section;
	
	var parts = section.split('/');
	if (parts.length >= 2)
	{
		module = parts[0];
		type = parts[1];
	}
	else
	{
		alert(GetTranslation('incorrect-parameter'));
	}
	
	if (parts.length > 2)
		template = parts[2];
	else
		template = '';
}

function EditVariable(tagName, value, editorType)
{
	var html = '<div class="form-group">';
	html += '	<label for="TagName">'+GetTranslation('tag-name')+'</label><br /><b>'+tagName+'</b>';
	html += '</div>';
	html += '<div class="form-group">';
	html += '	<label for="VariableValue">'+GetTranslation('variable-value')+'</label><br /><textarea name="VariableValue" id="VariableValue" class="form-control" rows="5">'+value+'</textarea>';
	html += '</div>';
	html += '<input type="hidden" name="Module" id="Module" value="'+module+'" />';
	html += '<input type="hidden" name="Type" id="Type" value="'+type+'" />';
	html += '<input type="hidden" name="Template" id="Template" value="'+template+'" />';
	html += '<input type="hidden" name="TagName" id="TagName" value="'+tagName+'" />';
	html += '<input type="hidden" name="Action" id="Action" value="SaveVariableToXML" />';

	$('#variable-edit .modal-body').html(html);
	$('.variable-cancel, #variable-edit .modal-backdrop, #variable-edit .close').unbind('click');
	$('.variable-cancel, #variable-edit .modal-backdrop, #variable-edit .close').click(function(e){
		if(typeof(CKEDITOR.instances.VariableValue) != 'undefined')
		{
			CKEDITOR.instances.VariableValue.destroy();
		}
		$('#variable-edit .modal-body').empty();
		$('#variable-edit').modal('hide');
		e.preventDefault();
	});
	if(editorType == "html")
	{
		createCKEditor('VariableValue', null, null, '150px', {enterMode : CKEDITOR.ENTER_BR});
	}
	$('#variable-edit').modal('show');
}

function SaveVariable()
{
	if(typeof CKEDITOR.instances.VariableValue != 'undefined'){
		$('[name=VariableValue]').val(CKEDITOR.instances.VariableValue.getData());
	}
	message = CreateMessage(GetTranslation('saving-variable'), 'info');
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:$('#variable-edit-form').serialize(),
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			$('.variable-value[Tag='+$('[name=TagName]').val()+']').html(data);
			$('#variable-edit').modal('hide');
			UpdateMessage(message, GetTranslation('variable-saved'), 'success');
			if(typeof(CKEDITOR.instances.VariableValue) != 'undefined')
			{
				CKEDITOR.instances.VariableValue.destroy();
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-saving-variable'), 'error');
		}
	});
}