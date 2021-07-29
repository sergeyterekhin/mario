TREE_SELECTOR = '#tree';
TAB_SELECTOR = '#tree-tabs';
CONTAINER_SELECTOR = '#tree-containers';
var DATA_LANGCODE = "";
var nodeList = Array();

//event handlers
$(document).on('click', '#menu-add', function(e){
	EditMenu(0);
	e.preventDefault();
});

$(document).on('dblclick', '.menu-edit', function(e){
	EditMenu($(this).attr('PageID'));
});

$(document).on('click', '.menu-edit', function(e){	
	pageID = $(this).attr('PageID');
	$.cookie("SelectedMenuPageID", pageID, {expires: cookieExpires, path: PROJECT_PATH});
	UpdateAddLinks(pageID);
});

$(document).on('click', '.menu-delete', function(e){
	id = $(this).attr('PageID');
	var confirmMsg = GetTranslation('delete-menu-confirm');
	confirmMsg = confirmMsg.replace(/%Title%/g, $('.menu-edit[PageID='+id+']').text());
	ModalConfirm(confirmMsg, function(){
		DeleteMenu(id);
	});
});

$(document).on('click', '.page-delete', function(e){
	id = $(this).attr('PageID');
	if (nodeList[id]['PageType'] == 3)
		var confirmMsg = GetTranslation('delete-link-confirm');
	else
		var confirmMsg = GetTranslation('delete-page-confirm');
	confirmMsg = confirmMsg.replace(/%Title%/g, nodeList[id]['Title']);
	if ($('#children-'+id).children('.page-node').length > 0)
	{
		confirmMsg += "\n"+GetTranslation("has-subpages");
	}
	
	ModalConfirm(confirmMsg, function(){
		DeletePage(id);
	});
});

$(document).on('click', '.page-switch', function(e){
	SwitchPage($(this));
});

//functions 

function DrawSiteMap(data, lng)
{
	DATA_LANGCODE = lng;
	
	//add menu button
	var tabHTML = "";
	tabHTML += "<li>";
	tabHTML += "<a href='#' id='menu-add'>+</a>";
	tabHTML += "</li>";			
	$(TAB_SELECTOR).append(tabHTML);
	
	for(i = 0; i < data.length; i++)
	{
		AddNode(data[i]);
	}
	if(typeof $.cookie('SelectedMenuPageID') != 'undefined')
	{
		UpdateAddLinks($.cookie('SelectedMenuPageID'));
	}	
}

function AddNode(data)
{
	nodeList[data["PageID"]] = data;
	if(data["ParentID"] > 0)
	{
		object = 'page';
		switch(data["PageType"]){
			case 1:
				href = 'page_edit.php?PageID='+data["PageID"];
				break;
			case 2:
				href = 'module_edit.php?PageID='+data["PageID"];
				break;
			case 3:
				href = 'link_edit.php?PageID='+data["PageID"];
				object = 'link';
				break;
		}
		if(data["Active"] == "Y")
		{
			titleColor = data["ColorA"];
			switchClass = " active";
			switchTitle = GetTranslation('page-deactivate');
		}
		else
		{
			titleColor = data["ColorI"];
			switchClass = " inactive";
			switchTitle = GetTranslation('page-activate');
		}	
		var html = "";
		html += "<li data-item='"+data["ShortTitle"]+"' data-item-id='"+data["PageID"]+"' class='page-node uk-nestable-list-item' PageID='"+data["PageID"]+"'>";
		html += 	"<div class='uk-nestable-item'>";
	    html += 		"<div class='uk-nestable-handle'></div>";
	    html += 		"<div data-nestable-action='toggle'></div>";
	    html += 		"<div class='list-label'><a href='"+href+"' style='color:"+titleColor+"' class='page-link'>"+data["ShortTitle"]+"</a></div>";
	    html +=			"<i class='fa fa-close page-delete' PageID='" + data["PageID"] + "' title=\""+GetTranslation(object+'-delete').replace(/%Title%/g, data["Title"])+"\"></i>";
	    html +=			"<i class='fa fa-power-off page-switch"+switchClass+"' PageID='" + data["PageID"] + "' title='"+switchTitle+"'></i>";
	    html += 	"</div>";
        html += "</li>";
        if($('#children-'+data["ParentID"]).size() == 0)
        {
        	$('.page-node[PageID='+data["ParentID"]+']').append("<ul id='children-"+data["ParentID"]+"' class='uk-nestable-list'></ul>");
  			if(IsNodeCollapsed(data["ParentID"]))
			{
				collapsedClass = " uk-collapsed";
			}
			else
			{
				collapsedClass = "";
			}      	
			$('.page-node[PageID='+data["ParentID"]+']').addClass(collapsedClass);
        }
       	$('#children-'+data["ParentID"]).append(html);
	}
	else
	{
		if(!$.cookie("SelectedMenuPageID"))
		{
			$.cookie("SelectedMenuPageID", data["PageID"], {expires: cookieExpires, path : PROJECT_PATH});
		}
		if($.cookie("SelectedMenuPageID") == data["PageID"])
		{
			active = " active";
		}
		else
		{
			active = "";
		}
		var tabHTML = "";
		tabHTML += "<li class='menu-tab"+active+"' PageID='" + data["PageID"] + "'>";
		tabHTML += 		"<a class='menu-edit' PageID='" + data["PageID"] + "' href='#page-" + data["PageID"] + "' data-toggle='tab'><span>" + data["ShortTitle"] + "</span>";
		tabHTML +=			"<i class='fa fa-close menu-delete' PageID='" + data["PageID"] + "'></i></a>";
		tabHTML += "</li>";
		$(TAB_SELECTOR).find('li:last').before(tabHTML);
		
		var containerHTML = "<div class='tab-pane menu-container"+active+"' id='page-" + data["PageID"] + "' PageID='" + data["PageID"] + "'>";
		containerHTML += "<ul id='children-" + data["PageID"] + "' class='uk-nestable' data-uk-nestable></ul>";
		containerHTML += "</div>";
		$(CONTAINER_SELECTOR).append(containerHTML);
		
		$('#children-' + data["PageID"]).on('expand.uk.nestable', function(ev, li) {
			if(typeof $.cookie("CollapsedPageIDs") != 'undefined'){
				collapsedPageIDs = $.cookie("CollapsedPageIDs").split(",");
				newCollapsedPageIDs = new Array();
				for(i = 0; i < collapsedPageIDs.length; i++){
					if(!(collapsedPageIDs[i] == $(li).attr("PageID"))){
						newCollapsedPageIDs.push(collapsedPageIDs[i]);
					}
				}
				$.cookie("CollapsedPageIDs", newCollapsedPageIDs.join(","), {expires: cookieExpires, path: PROJECT_PATH});
			}
		});
		
		$('#children-' + data["PageID"]).on('collapse.uk.nestable', function(ev, li) {
			if(typeof $.cookie("CollapsedPageIDs") != 'undefined')
				collapsedPageIDs = $.cookie("CollapsedPageIDs");
			else
				collapsedPageIDs = "";
				
			if(collapsedPageIDs.length > 0)
			{
				collapsedPageIDs += ","+$(li).attr("PageID");
			}
			else
			{
				collapsedPageIDs = $(li).attr("PageID");
			}
			$.cookie("CollapsedPageIDs", collapsedPageIDs, {expires: cookieExpires, path: PROJECT_PATH});
		});
		
		$('#children-' + data["PageID"]).on('change.uk.nestable', function(ev, li, ac) {
            SaveSortOrder($(li));
        });
	}
}

function IsNodeCollapsed(id)
{
	if(typeof $.cookie("CollapsedPageIDs") != 'undefined'){
		collapsedPageIDs = $.cookie("CollapsedPageIDs").split(",");
		for(j = 0; j < collapsedPageIDs.length; j++){
			if(collapsedPageIDs[j] == id){
				return true;
			}
		}
	}
	return false;
}

function EditMenu(pageID)
{
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:{
			Action: 'LoadMenu',
			PageID: pageID
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if(data)
			{
				DrawMenu(pageID, data.Title, data.Description, data.StaticPath, data.MenuImages);
			}
			else
			{
				CreateMessage(GetTranslation('error-loading-menu'), 'error');
			}
		},
		error:function(){
			CreateMessage(GetTranslation('error-loading-menu'), 'error');
		}
	});
}

function DrawMenu(id, title, description, staticPath, menuImages)
{
	var html = '<div class="form-group">';
	html += '	<label for="Title">'+GetTranslation('menu-title')+'</label><input name="Title" id="Title" class="form-control" type="text" value="'+title.replace(/\"/g,"&#34;")+'" />';
	html += '</div>';
	html += '<div class="form-group">';
	html += '	<label for="Description">'+GetTranslation('menu-description')+'</label><textarea name="Description" id="Description" class="form-control">'+description.replace(/\"/g,"&#34;")+'</textarea>';
	html += '</div>';
	html += '<div class="form-group">';
	html += '	<label for="StaticPath">'+GetTranslation('menu-static-path')+'</label><input name="StaticPath" id="StaticPath" class="form-control" type="text" value="'+staticPath.replace(/\"/g,"&#34;")+'" />';
	html += '</div>';
	if (menuImages)
	{
		var options = [{'title': GetTranslation('menu-image-no'), 'value': ''}, {'title': GetTranslation('menu-image-first-level'), 'value': 1}, {'title': GetTranslation('menu-image-all-levels'), 'value': 2}];
		for (var i = 0; i < menuImages.length; i++)
		{
			html += '<div class="form-group form-inline">';
			html += '	<label for="'+menuImages[i].Name+'">'+menuImages[i].Title+'</label>';
			html += '	<select name="'+menuImages[i].Name+'" id="'+menuImages[i].Name+'" class="form-control">';
			for (var j = 0; j < 3; j++)
			{
				if (menuImages[i].Value == options[j].value)
					html += '<option value="'+options[j].value+'" selected="true">'+options[j].title+'</option>';
				else
					html += '<option value="'+options[j].value+'">'+options[j].title+'</option>';
			}
			html += '	</select>';
			html += '</div>';
		}
	}
	html += '<input type="hidden" name="PageID" id="PageID" value="'+id+'" />';
	html += '<input type="hidden" name="Active" id="Active" value="Y" />';
	html += '<input type="hidden" name="LanguageCode" id="LanguageCode" value="'+DATA_LANGCODE+'" />';
	html += '<input type="hidden" name="Action" id="Action" value="SaveMenu" />';
	
	$('#menu-edit .alert-error').html("").hide();
	$('#menu-edit .modal-body').html(html);
	$('#menu-edit').modal('show');	
}

function SaveMenu()
{
	message = CreateMessage(GetTranslation('saving-menu'), 'info');
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:$('#menu-edit-form').serialize(),
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if(typeof data.Error == 'undefined')
			{
				data.ParentID = 0;
				if($('#PageID').val() > 0)
				{
					$('.menu-edit[PageID='+$('#PageID').val()+'] span').html(data.ShortTitle);
				}
				else
				{
					AddNode(data);
				}				
				$('#menu-edit').modal('hide');
				UpdateMessage(message, GetTranslation('menu-saved'), 'success');
			}
			else
			{
				$('#menu-edit .alert-error').html(data.Error).show();
				HideMessage(message);
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-saving-menu'), 'error');
		}
		
	});
}

function DeleteMenu(id)
{
	message = CreateMessage(GetTranslation('removing-menu'), 'info');
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:{
			'Action': 'Remove',
			'PageID': id
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if($.cookie("SelectedMenuPageID") == id)
			{
				$.removeCookie("SelectedMenuPageID");
			}
			if(parseInt($('.menu-edit:first').attr('PageID')) > 0)
			{
				$('.menu-edit:first').trigger('click');
			}
			$('.menu-tab[PageID='+id+']').remove();
			$('.menu-container[PageID='+id+']').remove();
			UpdateMessage(message, GetTranslation('menu-removed'), 'success');			
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-removing-menu'), 'error');
		}		
	});
}

function DeletePage(id)
{
	message = CreateMessage(GetTranslation('removing-page'), 'info');
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:{
			'Action': 'Remove',
			'PageID': id
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			$('.page-node[PageID='+id+']').remove();
			UpdateMessage(message, GetTranslation('page-removed'), 'success');
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-removing-page'), 'error');
		}
	});
}

function UpdateAddLinks(pageID)
{
	$('.page-add').attr('href', ADMIN_PATH+'page_edit.php?ParentID='+pageID);
	$('.link-add').attr('href', ADMIN_PATH+'link_edit.php?ParentID='+pageID);
	$('.module-add').each(function(){
		$(this).attr('href', ADMIN_PATH+'module_edit.php?Link='+$(this).attr('Link')+'&ParentID='+pageID);
	});
}

function SaveSortOrder(li)
{
	menuContainer = li.closest('.menu-container');
	menuPageID = menuContainer.attr('PageID');
	updatePageList = new Array();
	menuContainer.find('.page-node').each(function(){
		path = new Array();
		$(this).parents('.page-node').each(function(){
			path.push($(this).attr('PageID'));
		});
		path.push(menuPageID);
		updatePageList.push({'PageID': $(this).attr('PageID'), 'SortOrder': $(this).index()+1, 'Path': path.reverse()});
	});

	message = CreateMessage(GetTranslation('saving-sort'), 'info');
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:{
			'Action': 'SaveSort',
			'MenuPageID': menuPageID,
			'PageList': updatePageList
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if (data)
			{
				UpdateMessage(message, GetTranslation('sort-saved'), 'success');
			}
			else
			{
				UpdateMessage(message, GetTranslation('error-saving-sort'), 'error');
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-saving-sort'), 'error');
		}
	})
}

function SwitchPage(icon)
{
	id = icon.attr('PageID'); 
	if(icon.is('.active'))
	{
		active = 'N';
		message = CreateMessage(GetTranslation('deactivating-page'), 'info');
	}
	else
	{
		active = 'Y';
		message = CreateMessage(GetTranslation('activating-page'), 'info');
	}
	$.ajax({
		url: ADMIN_PATH+'ajax.php',
		method: 'POST',
		dataType: 'JSON',
		data:{
			'Action': 'SwitchActive',
			'PageID': id,
			'Active': active
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if (active == 'Y')
			{
				icon.attr('title', GetTranslation('page-deactivate'));
				icon.removeClass('inactive').addClass('active');
				icon.closest('.page-node').find('.page-link:first').css('color', nodeList[id].ColorA);
				UpdateMessage(message, GetTranslation('page-activated'), 'success');
			}
			else
			{
				icon.attr('title', GetTranslation('page-activate'));
				icon.removeClass('active').addClass('inactive');
				icon.closest('.page-node').find('.page-link:first').css('color', nodeList[id].ColorI);
				UpdateMessage(message, GetTranslation('page-deactivated'), 'success');
			}
		},
		error:function(){
			if (active == 'Y')
			{
				UpdateMessage(message, GetTranslation('error-activating-page'), 'error');
			}
			else
			{
				UpdateMessage(message, GetTranslation('error-deactivating-page'), 'error');
			}
		}
	});
}