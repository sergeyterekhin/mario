<?php
define('IS_ADMIN', true);
require_once(dirname(__FILE__)."/../../../include/init.php");

$language = GetLanguage();
$request = new LocalObject(array_merge($_GET, $_POST));
if ($request->GetProperty('Module'))
	$translation = $language->LoadForJS($request->GetProperty('Module'));
else
	$translation = $language->LoadForJS();
$cookieExpire = COOKIE_EXPIRE * 30;
?>

//global vars
PROJECT_PATH = '<?php echo PROJECT_PATH; ?>';
ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
PATH2MAIN = ADMIN_PATH+'template/';
var cookieExpires = <?php echo COOKIE_EXPIRE;?>*30;

function GetTranslation(key)
{
	switch (key)
	{
<?php
		foreach ($translation as $key => $value)
		{
?>
		case "<?php echo $key; ?>": return "<?php echo htmlspecialchars(addcslashes($value["Value"], "\r\n'\\")); ?>";
<?php
		}
?>
		default: return key;
	}
}

$(document).ready(function(){
	$('#select-data-language').change(function(){
		changeDLang($(this).val());
	});
	$('.check-all').on('ifToggled', function(e){
		checkAll(this, $(this).attr('InputName'));
	});
		
});

function changeDLang(languageCode)
{
	$.cookie("DLangCode", escape(languageCode), {expire: <?php echo $cookieExpire; ?>, path: "<?php echo PROJECT_PATH; ?>"});
	window.location.href = window.location.href;
}

function createCKEditor(name, toolbarSet, width, height, params)
{
	var toolbars = {basic: [ [ 'Source'], [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-','RemoveFormat'], [ 'NumberedList','BulletedList']], 
					standart: [['Source','-','Maximize','-','Templates' ], [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ],[ 'Find','Replace','-','SelectAll'], [ 'Link','Unlink','Anchor' ], [ 'Image','Flash','Table','HorizontalRule','SpecialChar','Iframe' ], '/',
							['Format', '-', 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ], [ 'NumberedList','BulletedList','-','Outdent','Indent','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']																		
					]
	};	
 	cfg = new Object({
		contentsCss : [ '<?php echo PROJECT_PATH; ?>website/<?php echo WEBSITE_FOLDER; ?>/fckconfig/fck_editorarea.css'],
		width: width || '100%',
		height : height || '400px',
		toolbar: toolbars[toolbarSet] ? toolbars[toolbarSet] : toolbars['standart'] 
	});
	if(typeof params == 'object')
	{
		for (var attrname in params) { cfg[attrname] = params[attrname]; }
	}
	var editor = CKEDITOR.replace(name, cfg);
	
	// Add FileManager to CKEditor
	ajexFileManager(editor, '<?php echo substr(CKEDITOR_PATH, 0, -1); ?>');
}

function ToggleMenuImages(target)
{
	var hide = true;
	if ($('#ParentID').val() > 0)
	{
		pageID = 0;
		$('#ParentURL').html("");
		if($('#ParentID option:selected').attr('Level') == 0)
		{
			pageID = $('#ParentID').val();
		}
		else
		{
			selectedLevel = parseInt($('#ParentID option:selected').attr('Level'));

			staticPath = $('#ParentID option:selected').attr('StaticPath');
			$('#ParentURL').prepend(staticPath + "/");
			
			for(i = selectedLevel - 1; i > 0; i--)
			{
				staticPath = $('#ParentID option:selected').prevUntil('[value=""]','[Level='+i+']').filter(':first').attr('StaticPath');
				$('#ParentURL').prepend(staticPath + "/");
			}
			pageID = $('#ParentID option:selected').prevUntil('[value=""]','[Level=0]').filter(':first').val();
		}
		
		for (var i = 0; i < menuList[pageID].length; i++)
		{
			var num = menuList[pageID][i]['key'];
			switch (menuList[pageID][i]['val'])
			{
				case '2':
					$('#MenuImage'+num+'-box').show();
					hide = false;
					break;
				case '1':
					if ($('#ParentID option:selected').attr('Level') < 1)
					{
						$('#MenuImage'+num+'-box').show();
						hide = false;
					}
					else
					{
						$('#MenuImage'+num+'-box').hide();
					}
					break;
				default:
					$('#MenuImage'+num+'-box').hide();
					break;
			}
		}
	}
	else
	{
		for (i = 1; i < menuImagesCount + 1; i++)
		{
			$('#MenuImage'+i+'-box').hide();
		}
	}

	if ($('#menu-image-empty'))
	{
		if (hide) $('#menu-image-empty').show();
		else $('#menu-image-empty').hide();
	}
}

function CreateImageInput(imageName, image, imagePath, savedImage, itemID, removeAction, ajaxPath, pageID, params)
{
	var html = $('#'+imageName+'-box').html();
	html += '<div id="'+imageName+'-img" '+(image ? '' : ' style="display:none;"')+'><img src="'+image+'" alt="" style="max-width:260px;" class="input-img" /></div>';
	html += '<a id="'+imageName+'-btn" class="btn btn-primary btn-icon change-file" title="'+(image ? GetTranslation('change-image') : GetTranslation('add-image'))+'"><i class="fa fa-image"></i>'+(image ? GetTranslation('change-image') : GetTranslation('add-image'))+'</a> ';
	html += '<a onclick="$(\'#'+imageName+'-crop\').modal(\'show\');" id="'+imageName+'-cfg" class="btn btn-primary btn-icon configure-file" style="display:none;"><i class="fa fa-cog"></i>'+GetTranslation('configure-image')+'</a> ';
	html += '<a id="'+imageName+'-del" class="btn btn-danger btn-icon delete-file" ImageName="'+imageName+'" ItemID="'+itemID+'" RemoveAction="'+removeAction+'" AjaxPath="'+ajaxPath+'" PageID="'+pageID+'" '+(image ? '' : ' style="display:none;"')+'><i class="fa fa-remove"></i>'+GetTranslation('remove-image')+'</a>';
	html += '<div class="clearfix"></div>';
	html += '<div class="hidden" id="'+imageName+'-file"><input name="'+imageName+'" id="'+imageName+'" type="file" size="1" /></div>';
	html += '<input type="hidden" name="Saved'+imageName+'" id="Saved'+imageName+'" value="'+savedImage+'" />';
	$('#'+imageName+'-box').html(html);
	
	if(typeof params != 'undefined' && params.length > 0)
	{
		showConfigBtn = false;
		for(i = 0; i < params.length; i++)
		{
			if(params[i]["Resize"] == 13)
			{
				showConfigBtn = true;
				if(image)
				{
					//create inputs to save image config
					html  = '<input type="hidden" name="'+imageName+'Config['+params[i]['Name']+'][X1]" value="'+params[i]['X1']+'" />';
					html += '<input type="hidden" name="'+imageName+'Config['+params[i]['Name']+'][Y1]" value="'+params[i]['Y1']+'" />';
					html += '<input type="hidden" name="'+imageName+'Config['+params[i]['Name']+'][X2]" value="'+params[i]['X2']+'" />';
					html += '<input type="hidden" name="'+imageName+'Config['+params[i]['Name']+'][Y2]" value="'+params[i]['Y2']+'" />';
					$('#'+imageName+'-box').closest('form').append(html);
					
					//create cropper popup
					if($('#'+imageName+'-crop').size() == 0)
					{
						html =  '<div id="'+imageName+'-crop" class="modal">';
						html +=	'	<div class="modal-dialog">';
						html +=	'		<div class="modal-content"><div class="modal-body"></div></div>';
						html +=	'	</div>';
						html += '</div>';
						$('#'+imageName+'-box').after(html);
					}
					
					$('#'+imageName+'-crop .modal-body').append('<img id="'+imageName+params[i]['Name']+'-cropper-img" src="'+imagePath+'" />');
					
					//init cropper
					var $image = $("#"+imageName+params[i]["Name"]+"-cropper-img"),
					$dataX1 = $("[name='"+imageName+"Config["+params[i]["Name"]+"][X1]']"),
					$dataY1 = $("[name='"+imageName+"Config["+params[i]["Name"]+"][Y1]']"),
					$dataX2 = $("[name='"+imageName+"Config["+params[i]["Name"]+"][X2]']"),
					$dataY2 = $("[name='"+imageName+"Config["+params[i]["Name"]+"][Y2]']");
					$image.cropper({
						x1: $dataX1,
						y1: $dataY1,
						x2: $dataX2,						
						y2: $dataY2,
						aspectRatio: params[i]["Width"] / params[i]["Height"],
						data: {
							x: params[i]["X1"],
							y: params[i]["Y1"],
							width: params[i]["X2"] - params[i]["X1"],
							height: params[i]["Y2"] - params[i]["Y1"]
						},
						minContainerWidth:540,
						minContainerHeight:360,
						done: function(data) {
							this.x1.val(parseInt(data.x));
							this.y1.val(parseInt(data.y));
							this.x2.val(parseInt(data.x + data.width));
							this.y2.val(parseInt(data.y + data.height));
						},
						zoomable: false
					});
					$('#'+imageName+params[i]['Name']+'-cropper-img').before('<h3 align="center">'+params[i]["Name"]+'</h3>');
				}
			}
		}
		if(showConfigBtn == true && image)
		{
			$('#'+imageName+'-box .configure-file').show();
		}
	}
	
	$('#'+imageName+'-del').click(function(e){
		RemoveImage($(this).attr('ImageName'), $(this).attr('ItemID'), $(this).attr('RemoveAction'), $(this).attr('AjaxPath'), $(this).attr('PageID'));
		e.preventDefault();
	});
	
	$('#'+imageName+'-btn').click(function(e){
		$('#'+imageName).trigger('click');
		e.preventDefault();
	});
	
	$('#'+imageName).change(function(){
		if ($(this).val())
		{
			var fileName = $(this).val().replace(/^([^\\\/]*(\\|\/))*/, "");
			if (fileName.length > 20)
				newFileName = fileName.substr(0, 14)+'...'+fileName.substr(fileName.length-3, 3);
			else
				newFileName = fileName;
			$('#'+imageName+'-btn').html(newFileName);
		}
		else
		{
			$('#'+imageName+'-btn').html($('#'+imageName+'-btn').attr('title'));
		}
	});
}

function CreateImageCropper(container, imageName, imagePath, params, srcImgW, srcImgH)
{
	if(typeof params != 'undefined' && params.length > 0)
	{
		for(i = 0; i < params.length; i++)
		{
			if(params[i]["Resize"] == 13)
			{
				//create inputs to save image config
				html  = '<input type="hidden" name="'+imageName+'Config['+params[i]['SourceName']+'][X1]" value="'+params[i]['X1']+'" />';
				html += '<input type="hidden" name="'+imageName+'Config['+params[i]['SourceName']+'][Y1]" value="'+params[i]['Y1']+'" />';
				html += '<input type="hidden" name="'+imageName+'Config['+params[i]['SourceName']+'][X2]" value="'+params[i]['X2']+'" />';
				html += '<input type="hidden" name="'+imageName+'Config['+params[i]['SourceName']+'][Y2]" value="'+params[i]['Y2']+'" />';
				html += '<input type="hidden" name="'+imageName+'Config[Width]" value="'+srcImgW+'" />';
				html += '<input type="hidden" name="'+imageName+'Config[Height]" value="'+srcImgH+'" />';
				container.append(html);
				container.append('<img id="'+imageName+params[i]['SourceName']+'-cropper-img" src="'+imagePath+'" />');
				
				//init cropper
				var $image = $("#"+imageName+params[i]["SourceName"]+"-cropper-img"),
				$dataX1 = $("[name='"+imageName+"Config["+params[i]["SourceName"]+"][X1]']"),
				$dataY1 = $("[name='"+imageName+"Config["+params[i]["SourceName"]+"][Y1]']"),
				$dataX2 = $("[name='"+imageName+"Config["+params[i]["SourceName"]+"][X2]']"),
				$dataY2 = $("[name='"+imageName+"Config["+params[i]["SourceName"]+"][Y2]']");
				$image.cropper({
					x1: $dataX1,
					y1: $dataY1,
					x2: $dataX2,
					y2: $dataY2,
					aspectRatio: params[i]["Width"] / params[i]["Height"],
					data: {
						x: params[i]["X1"],
						y: params[i]["Y1"],
						width: params[i]["X2"] - params[i]["X1"],
						height: params[i]["Y2"] - params[i]["Y1"]
					},
					minContainerWidth:540,
					minContainerHeight:360,
					done: function(data) {
						this.x1.val(parseInt(data.x));
						this.y1.val(parseInt(data.y));
						this.x2.val(parseInt(data.x + data.width));
						this.y2.val(parseInt(data.y + data.height));
					},
					zoomable: false
				});
				$('#'+imageName+params[i]['SourceName']+'-cropper-img').before('<h3 align="center">'+params[i]["SourceName"]+'</h3>');
			}
		}
	}
}

function RemoveImage(imageName, itemID, removeAction, ajaxPath, pageID)
{
	if (!confirm(GetTranslation('remove-image-confirm')))
		return;

	$('#'+imageName+'-img').html('<i>'+GetTranslation('removing-image')+'</i>');

	message = CreateMessage(GetTranslation('removing-image'), 'info');
	$.ajax({
		url: ajaxPath,
		dataType: 'JSON',
		data:{
			'Action': removeAction,
			'PageID': pageID,
			'ItemID': itemID,
			'ImageName': imageName,
			'SavedImage': $('#Saved'+imageName).val()
		},
		success: function(data){
			if (data)
			{
				$('#'+imageName+'-btn').html('<i class="fa fa-image"></i>'+GetTranslation('add-image'));
				$('#'+imageName+'-img').hide();
				$('#'+imageName+'-del').hide();
				$('#'+imageName+'-cfg').hide();
				$("[name^='"+imageName+"Config']").remove();
				$('#Saved'+imageName).val('');
				UpdateMessage(message, GetTranslation('image-removed'), 'success');
			}
			else
			{
				UpdateMessage(message, GetTranslation('error-removing-image'), 'error');
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-removing-image'), 'error');
		}
	});
}

function ModalConfirm(message, onconfirm)
{
	if(!$.isFunction(onconfirm))
		onconfirm = function(){};
	html = '<div id="confirm-dialog" class="modal">';
	html +='	<div class="modal-dialog">';
	html +='		<div class="modal-content">';
	html +='			<div class="modal-header">';
	html +='				<button type="button" class="close" aria-hidden="true">&times;</button>';
	html +='				<h4 class="modal-title">'+GetTranslation('confirm-action')+'</h4>';
	html +='			</div>';
	html +='			<div class="modal-body">'+message+'</div>';
	html +='			<div class="modal-footer">';
	html +='				<button id="confirm-no" type="button" class="btn btn-icon"><i class="fa fa-ban"></i>'+GetTranslation('no')+'</button>';
	html +='				<button id="confirm-yes" type="button" class="btn btn-warning btn-icon"><i class="fa fa-check"></i>'+GetTranslation('yes')+'</button>';
	html +='			</div>';
	html +='		</div>';
	html +='	</div>';
	html +='</div>';
	
	$(html).modal('show');
	
	$('#confirm-yes').click(function(){
		onconfirm();
		$(this).closest('.modal').modal('hide');
		$(this).closest('.modal').remove();
	});
	
	//custom handler to close and completely remove dialog
	$('#confirm-no, #confirm-dialog .close, #confirm-dialog .modal-backdrop').click(function(){
		$('#confirm-dialog').modal('hide');
		$('#confirm-dialog').remove();
	});
}

function checkAll(elm, name)
{
	for (i = 0; i < elm.form.elements.length; i++)
	{
		if (elm.form.elements[i].type == "checkbox" && elm.form.elements[i].name == name)
		{
			if(elm.checked){
				$(elm.form.elements[i]).iCheck('check');
			}else{
				$(elm.form.elements[i]).iCheck('uncheck');
			}
		}
	}
}

function GetMessenger()
{
	return Messenger({
		extraClasses: 'messenger-fixed messenger-on-right messenger-on-top',
		theme: 'flat'
	});
}

function CreateMessage(msg, type) 
{
	return GetMessenger().post({
		message: msg,
		type: type,
		showCloseButton: true,
		hideAfter: 4
	});
}

function HideMessage(message)
{
	message.hide();
}

function UpdateMessage(message, msg, type)
{
	message.update({
		type: type,
		message: msg
	});
}