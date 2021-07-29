var ajaxPath = PROJECT_PATH+"module/catalog/ajax.php"; 
var nodeList = new Array();
nodeList[0] = new Object();
nodeList[0].active = 'Y';

$(document).on('expand.uk.nestable', '.uk-nestable', function(ev, li) {
	if(typeof $.cookie("CollapsedCatalogCategoryIDs") != 'undefined'){
		collapsedCategoryIDs = $.cookie("CollapsedCatalogCategoryIDs").split(",");
		newCollapsedCategoryIDs = new Array();
		for(i = 0; i < collapsedCategoryIDs.length; i++){
			if(!(collapsedCategoryIDs[i] == $(li).attr("CategoryID"))){
				newCollapsedCategoryIDs.push(collapsedCategoryIDs[i]);
			}
		}
		$.cookie("CollapsedCatalogCategoryIDs", newCollapsedCategoryIDs.join(","), {expires: cookieExpires, path: PROJECT_PATH});
	}
});

$(document).on('collapse.uk.nestable', '.uk-nestable', function(ev, li) {
	if(typeof $.cookie("CollapsedCatalogCategoryIDs") != 'undefined')
		collapsedCategoryIDs = $.cookie("CollapsedCatalogCategoryIDs");
	else
		collapsedCategoryIDs = "";
		
	if(collapsedCategoryIDs.length > 0)
	{
		collapsedCategoryIDs += ","+$(li).attr("CategoryID");
	}
	else
	{
		collapsedCategoryIDs = $(li).attr("CategoryID");
	}
	$.cookie("CollapsedCatalogCategoryIDs", collapsedCategoryIDs, {expires: cookieExpires, path: PROJECT_PATH});
});

$(document).on('change.uk.nestable', '.uk-nestable', function(ev, li, ac) {
    SaveCategoryPosition($(li));
});

$(document).on('click', '.category-delete', function(e){
	id = $(this).attr('CategoryID');
	if (nodeList[id].itemCount > 0)
	{
		alert(GetTranslation("delete-restricted"));
		return;
	}
	var confirmMsg = GetTranslation("delete-category-confirm");
	confirmMsg = confirmMsg.replace(/%CategoryTitle%/g, "'" + nodeList[id].jsTitle + "'");
	if ($('#children-'+id+' li').size() > 0)
	{
		confirmMsg += "\n"+GetTranslation("has-subcategories");
	}
	ModalConfirm(confirmMsg, function(){
		DeleteCategory(id);
	});
	e.preventDefault();
});

function AddCategoryNode(id, parentID, jsTitle, active, itemCount)
{
	if (nodeList[parentID])
	{
		var titleColor = "#000000";
		if (active == 'Y' && nodeList[parentID].active == 'Y')
		{
			active = 'Y';
			titleColor = "#000000";
		}
		else
		{
			active = 'N';
			titleColor = "#8C8E8D";
		}

		// Add node
		html = "";
		html += "<li data-item='"+jsTitle+"' data-item-id='"+id+"' class='category-node uk-nestable-list-item' CategoryID='"+id+"'>";
		html += 	"<div class='uk-nestable-item'>";
	    html += 		"<div class='uk-nestable-handle'></div>";
	    html += 		"<div data-nestable-action='toggle'></div>";
	    html += 		"<div class='list-label'><a href='"+moduleURL+"&ViewCategoryID="+id+"' style='color:"+titleColor+"' class='category-link'>"+jsTitle+" (<span class='item-count'>"+itemCount+"</span>)</a></div>";
	    html +=			"<i class='fa fa-close category-delete' CategoryID='" + id + "' title=\""+GetTranslation('category-delete').replace(/%Title%/g, jsTitle)+"\"></i>";
	    html +=			"<a href='"+moduleURL+"&CategoryID="+id+"'><i class='fa fa-edit category-edit' title=\""+GetTranslation('category-edit').replace(/%Title%/g, jsTitle)+"\"></i></a>";
	    html += 	"</div>";
        html += "</li>";
        
        if($('#children-'+parentID).size() == 0)
        {
        	$('.category-node[CategoryID='+parentID+']').append("<ul id='children-"+parentID+"' class='uk-nestable-list'></ul>");
  			if(IsCategoryCollapsed(parentID))
			{
				collapsedClass = " uk-collapsed";
			}
			else
			{
				collapsedClass = "";
			} 
			$('.category-node[CategoryID='+parentID+']').addClass(collapsedClass);
		}
       	$('#children-'+parentID).append(html);
	}
	else
	{
		alert("ERROR: parent '" + parentID + "' is not found for '" + id + "'");
		return;
	}

	nodeList[id] = new Object();
	nodeList[id].parentID = parentID;
	nodeList[id].jsTitle = jsTitle;
	nodeList[id].active = active;
	nodeList[id].itemCount = itemCount;
}

function IsCategoryCollapsed(id)
{
	if(typeof $.cookie("CollapsedCatalogCategoryIDs") != 'undefined'){
		collapsedCategoryIDs = $.cookie("CollapsedCatalogCategoryIDs").split(",");
		for(j = 0; j < collapsedCategoryIDs.length; j++){
			if(collapsedCategoryIDs[j] == id){
				return true;
			}
		}
	}
	return false;
}

function DeleteCategory(id)
{
	// Hide node & show removing message
	$('.category-node[CategoryID='+id+']').hide();
	
	message = CreateMessage(GetTranslation('removing-category'), 'info');
	$.ajax({
		url: ajaxPath,
		method: 'POST',
		dataType: 'JSON',
		data:{
			Action: 'RemoveCategory',
			CategoryID: id,
			PageID: pageID,
			CategoryTreeHash: $('#category-tree-hash').val()
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if (data)
			{
				$('#category-tree-hash').val(data.CategoryTreeHash);
				parent = $('.category-node[CategoryID='+id+']').closest('.uk-parent');
				$('.category-node[CategoryID='+id+']').remove();
				if(parent.size() > 0)
				{
					if(parent.find('li').size() == 0)
					{
						parent.removeClass('uk-parent').find('ul').remove();
					}
				}
				UpdateMessage(message, GetTranslation('category-removed'), 'success');
			}
			else
			{
				UpdateMessage(message, GetTranslation("error-removing-category"), 'error');
				$('.category-node[CategoryID='+id+']').show();
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation("error-removing-category"), 'error');
		}
	});		
}

function SaveCategoryPosition(li)
{
	categoryID = li.attr('CategoryID');
	newParent = li.closest('ul');
	if(newParent.closest('.category-node').size() > 0){
		parentID = newParent.closest('.category-node').attr('CategoryID');
		sortOrder = newParent.find(' > .category-node').index(li[0]);
	}else{
		parentID = 0;
		sortOrder = newParent.find(' > .category-node').index(li[0]);
	}

	message = CreateMessage(GetTranslation('saving-sort-category'), 'info');
	$.ajax({
		url: ajaxPath,
		method: 'POST',
		dataType: 'JSON',
		data:{
			Action: 'SaveCategoryPosition',
			CategoryID: categoryID,
			ParentID: parentID,
			SortOrder: sortOrder,
			PageID: pageID,
			CategoryTreeHash: $('#category-tree-hash').val()
		},
		success:function(data){
			if(typeof data.SessionExpired != 'undefined')
      		{
      			window.location.href = ADMIN_PATH+"index.php";
      			return;
      		}
			if (data)
			{
				$('#category-tree-hash').val(data.CategoryTreeHash);
				if(typeof data.UpdateCategoryList != 'undefined')
				{
					for(i = 0; i < data.UpdateCategoryList.length; i++)
					{
						$('.category-node[CategoryID='+data.UpdateCategoryList[i]['CategoryID']+'] > div .item-count').html(data.UpdateCategoryList[i]['ItemCount']);
						nodeList[data.UpdateCategoryList[i]['CategoryID']].itemCount = data.UpdateCategoryList[i]['ItemCount'];
					}
				}
				UpdateMessage(message, GetTranslation('sort-category-saved'), 'success');
			}
			else
			{
				UpdateMessage(message, GetTranslation('error-saving-sort-category'), 'error');
			}
		},
		error:function(){
			UpdateMessage(message, GetTranslation('error-saving-sort-category'), 'error');
		}
	})
}
