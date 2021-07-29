<?php
require_once(dirname(__FILE__)."/../init.php");

$language = GetLanguage();
$request = new LocalObject(array_merge($_GET, $_POST));
if ($request->GetProperty('Module'))
	$translation = $language->LoadForJS($request->GetProperty('Module'));
else
	$translation = $language->LoadForJS();
?>
function Querystring()
{
	this.params = new Object()
	this.get = GetQuerystring;

	var qs = location.search.substring(1, location.search.length)
	if (qs.length == 0) return

	// Turn <plus> back to <space>
	// See: http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.13.4.1
	qs = qs.replace(/\+/g, ' ');
	var args = qs.split('&') // parse out name/value pairs separated via &

	// split out each name=value pair
	for (var i = 0; i < args.length; i++)
	{
		var value;
		var pair = args[i].split('=');
		var name = unescape(pair[0]);

		if (pair.length == 2)
			value = unescape(pair[1]);
		else
			value = name;

		this.params[name] = value;
	}
}

function GetQuerystring(key, default_)
{
	// This silly looking line changes UNDEFINED to NULL
	if (default_ == null) default_ = null;

	var value = this.params[key]
	if (value == null) value = default_;

	return value
}

function GetTranslation(key)
{
	switch (key)
	{
<?php
		foreach ($translation as $key => $value)
		{
?>
		case "<?php echo $key; ?>": return "<?php echo htmlspecialchars(addcslashes($value, "\r\n'\\")); ?>";
<?php
		}
?>
		default: return key;
	}
}
