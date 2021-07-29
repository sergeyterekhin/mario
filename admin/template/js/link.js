var oParser = new Object();

var oRegex = new Object();
oRegex.UriProtocol = /^(((http|https|ftp|news):\/\/)|mailto:)/gi;
oRegex.UrlOnChangeProtocol = /^(http|https|ftp|news):\/\/(?=.)/gi;
oRegex.UrlOnChangeTestOther = /^((javascript:)|[#\/\.])/gi;
oRegex.ReserveTarget = /^_(blank|self|top|parent)$/i;
oRegex.PopupUri = /^javascript:void\(\s*window.open\(\s*'([^']+)'\s*,\s*(?:'([^']*)'|null)\s*,\s*'([^']*)'\s*\)\s*\)\s*;\s*$/;
oRegex.PopupFeatures = /(?:^|,)([^=]+)=(\d+|yes|no)/gi;

//var y = new RegExp("/^(((http|https|ftp|news):\/\/)|mailto:)/gi");

oParser.ParseEMailUrl = function( emailUrl )
{
	// Initializes the EMailInfo object.
	var oEMailInfo = new Object();
	oEMailInfo.Address	= "";
	oEMailInfo.Subject	= "";
	oEMailInfo.Body		= "";

	var oParts = emailUrl.match( /^([^\?]+)\??(.+)?/ );
	if ( oParts )
	{
		// Set the e-mail address.
		oEMailInfo.Address = oParts[1];

		// Look for the optional e-mail parameters.
		if ( oParts[2] )
		{
			var oMatch = oParts[2].match( /(^|&|&amp;)subject=([^&]+)/i );
			if ( oMatch ) oEMailInfo.Subject = unescape( oMatch[2] );

			oMatch = oParts[2].match( /(^|&|&amp;)body=([^&]+)/i );
			if ( oMatch ) oEMailInfo.Body = unescape( oMatch[2] );
		}
	}

	return oEMailInfo;
}

oParser.CreateEMailUri = function( address, subject, body )
{
	var sBaseUri = "mailto:" + address;

	var sParams = "";

	if ( subject.length > 0 )
		sParams = "?subject=" + escape( subject );

	if ( body.length > 0 )
	{
		sParams += ( sParams.length == 0 ? "?" : "&" );
		sParams += "body=" + escape( body );
	}

	return sBaseUri + sParams;
}

function FillLinkDivFromHRef(sHRef, sTarget, pageList)
{
	var sType = "url";

	// Look for a popup javascript link.
	var oPopupMatch = oRegex.PopupUri.exec(sHRef);
	if (oPopupMatch)
	{
		$('#cmbTarget').val('popup');
		sHRef = oPopupMatch[1];
		FillPopupFields(oPopupMatch[2], oPopupMatch[3]);
		SetTarget('popup');
	}

	// Here we have to try to find internal page
	internalPageFound = false;
	console.log(sHRef)
	for (var i in pageList)
	{
		if (pageList[i]["PageURL"] != null && pageList[i]["PageURL"] != "" && pageList[i]["PageURL"] == sHRef && pageList[i]["Type"] != 3)
		{
			$("#cmbLinkInternalPage").val(sHRef);
			internalPageFound = true;
		}
	}

	// Search for the protocol
	oRegex.UriProtocol.lastIndex = 0;
	var sProtocol = oRegex.UriProtocol.exec(sHRef);

	if (sProtocol)
	{
		sProtocol = sProtocol[0].toLowerCase();

		// Remove the protocol and get the remainig URL
		var sUrl = sHRef.replace(sProtocol, "");

		if (sProtocol == "mailto:")
		{
			// It is an e-mail link
			sType = "email";
			var oEMailInfo = oParser.ParseEMailUrl(sUrl);
			$("#txtEmailAddress").val(oEMailInfo.Address);
			$("#txtEmailSubject").val(oEMailInfo.Subject);
			$("#txtEmailBody").val(oEMailInfo.Body);
		}
		else
		{
			// It is a normal link
			sType = "url";
			$("#cmbLinkProtocol").val(sProtocol);
			$("#txtUrl").val(sUrl);
		}
	}
	else
	{
		if (internalPageFound)
		{
			// It is link to internal page
			sType = "internal";
		}
		else
		{
			// It is another type of link
			sType = "url";
			$("#cmbLinkProtocol").val("");
			$("#txtUrl").val(sHRef);
		}
	}

	if (!oPopupMatch)
	{
		if (sTarget && sTarget.length > 0)
		{
			if (oRegex.ReserveTarget.test(sTarget))
			{
				sTarget = sTarget.toLowerCase();
				$('#cmbTarget').val(sTarget);
			}
			else
			{
				$('#cmbTarget').val('frame');
			}
			$('#txtTargetFrame').val(sTarget);
		}
	}

	return sType;
}

function GenerateLinkHRef(form)
{
	var sUri = "";
	var sTarget = "";

	switch (form.elements["LinkType"].options[form.elements["LinkType"].selectedIndex].value)
	{
		case "url":
			sUri = $("#txtUrl").val();
			if (sUri.length == 0)
			{
				alert(GetTranslation("msg-no-url"));
				return false;
			}
			sUri = $("#cmbLinkProtocol").val() + sUri;

			if ($("#cmbTarget").val() == "popup")
				sUri = BuildPopup(sUri);
			else
				sTarget = $("#txtTargetFrame").val();

			break;
		case "email":
			sUri = $("#txtEmailAddress").val();
			if (sUri.length == 0)
			{
				alert(GetTranslation("msg-no-email"));
				return false;
			}
			sUri = oParser.CreateEMailUri(sUri, $("#txtEmailSubject").val(), $("#txtEmailBody").val());
			break;
		case "internal":
			sUri = $("#cmbLinkInternalPage").val();
			if (sUri.length == 0)
			{
				alert(GetTranslation("msg-no-page-selected"));
				return false;
			}

			if ($("#cmbTarget").val() == "popup")
				sUri = BuildPopup(sUri);
			else
				sTarget = $("#txtTargetFrame").val();

			break;
	}

	$('[name=Link]').val(sUri);
	$('[name=Target]').val(sTarget);

	return true;
}

function SetTarget(targetType)
{
	$('#tdTargetFrame').css('display', (targetType == 'popup' || targetType == '' ? 'none' : ''));
	$('#tdPopupName').css('display', (targetType == 'popup' ? '' : 'none'));
	$('#tablePopupFeatures').css('display', (targetType == 'popup' ? '' : 'none'));

	switch (targetType)
	{
		case "_blank":
		case "_self":
		case "_parent":
		case "_top":
			$('#txtTargetFrame').val(targetType);
			break ;
		case "":
			$('#txtTargetFrame').val('');
			break ;
	}
}

function OnUrlChange()
{
	var sUrl = $('#txtUrl').val() ;
	var sProtocol = oRegex.UrlOnChangeProtocol.exec(sUrl);

	if (sProtocol)
	{
		sUrl = sUrl.substr(sProtocol[0].length);
		$('#txtUrl').val(sUrl);
		$('#cmbLinkProtocol').val(sProtocol[0].toLowerCase());
	}
	else if (oRegex.UrlOnChangeTestOther.test(sUrl))
	{
		$('#cmbLinkProtocol').val('');
	}
}

function OnTargetNameChange()
{
	var sFrame = $('#txtTargetFrame').val();

	if (sFrame.length == 0)
		$('#cmbTarget').val('');
	else if (oRegex.ReserveTarget.test(sFrame))
		$('#cmbTarget').val(sFrame.toLowerCase());
	else
		$('#cmbTarget').val('frame');
}

function FillPopupFields(windowName, features)
{
	if (windowName)
		$('#txtPopupName').val(windowName);

	var oFeatures = new Object();
	var oFeaturesMatch;
	while ((oFeaturesMatch = oRegex.PopupFeatures.exec(features)) != null)
	{
		var sValue = oFeaturesMatch[2];
		if (sValue == ('yes' || '1'))
			oFeatures[oFeaturesMatch[1]] = true;
		else if (!isNaN(sValue) && sValue != 0)
			oFeatures[oFeaturesMatch[1]] = sValue;
	}

	// Update all features check boxes.
	var aChkFeatures = document.getElementsByName('chkFeature');
	for (var i = 0; i < aChkFeatures.length; i++)
	{
		if (oFeatures[aChkFeatures[i].value])
			aChkFeatures[i].checked = true;
	}

	// Update position and size text boxes.
	if (oFeatures['width']) $('#txtPopupWidth').val(oFeatures['width']);
	if (oFeatures['height']) $('#txtPopupHeight').val(oFeatures['height']);
	if (oFeatures['left']) $('#txtPopupLeft').val(oFeatures['left']);
	if (oFeatures['top']) $('#txtPopupTop').val(oFeatures['top']);
}

function BuildPopup(sUri)
{
	var sWindowName = "'" + $('#txtPopupName').val().replace(/\W/gi, "") + "'" ;

	var sFeatures = '' ;
	var aChkFeatures = document.getElementsByName('chkFeature');
	for (var i = 0; i < aChkFeatures.length; i++)
	{
		if (i > 0) sFeatures += ",";
		sFeatures += aChkFeatures[i].value + "=" + (aChkFeatures[i].checked ? "yes" : "no");
	}

	if ($('#txtPopupWidth').val().length > 0) sFeatures += ',width=' + $('#txtPopupWidth').val();
	if ($('#txtPopupHeight').val().length > 0) sFeatures += ',height=' + $('#txtPopupHeight').val();
	if ($('#txtPopupLeft').val().length > 0) sFeatures += ',left=' + $('#txtPopupLeft').val();
	if ($('#txtPopupTop').val().length > 0) sFeatures += ',top=' + $('#txtPopupTop').val();

	if (sFeatures != '')
		sFeatures = sFeatures + ",status";

	return ("javascript:void(window.open('" + sUri + "'," + sWindowName + ",'" + sFeatures + "'));");
}


function ShowLinkTypeDiv(sType)
{
	var typeURL = $("#divLinkTypeURL");
	var typeEmail = $("#divLinkTypeEmail");
	var typeInternalPage = $("#divLinkTypeInternalPage");
	var target = $("#divLinkTarget");

	typeURL.css('display', "none");
	typeEmail.css('display', "none");
	typeInternalPage.css('display', "none");
	target.css('display', "none");

	switch (sType)
	{
		case "url":
			typeURL.css('display', "block");
			target.css('display', "block");
			break;
		case "email":
			typeEmail.css('display', "block");
			break;
		case "internal":
			typeInternalPage.css('display', "block");
			target.css('display', "block");
			break;
	}
}

function ChangeLinkType(elm)
{
	ShowLinkTypeDiv(elm.options[elm.selectedIndex].value);
}
