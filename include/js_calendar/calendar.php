<?php

class Calendar
{

	var $_instanceName;
	var $_value;
	var $_valueFmt;
	var $_dateOnly;
	static $_includeScripts = true;

	function Calendar($fieldName, $value = null, $dateOnly = false)
	{
		$this->_dateOnly = $dateOnly;

		if (strtotime($value))
		{
			$language =& GetLanguage();
			$this->_value = $value;
			if ($this->_dateOnly)
				$this->_valueFmt = LocalDate($language->GetDateFormat(), strtotime($value));
			else
				$this->_valueFmt = LocalDate($language->GetDateFormat()." ".$language->GetTimeFormat(), strtotime($value));
		}
		$this->_instanceName = $fieldName;
	}

	function GetHTMLAsField()
	{
		$language =& GetLanguage();

		if ($this->_dateOnly)
		{
			$valueIF = is_null($this->_value) ? date("Y-m-d") : $this->_value;
			$valueDA = is_null($this->_valueFmt) ? LocalDate($language->GetDateFormat()) : $this->_valueFmt;
			$format = $language->GetDateFormatForJS();
		}
		else
		{
			$valueIF = is_null($this->_value) ? date("Y-m-d H:i") : $this->_value;
			$valueDA = is_null($this->_valueFmt) ? LocalDate($language->GetDateFormat()." ".$language->GetTimeFormat()) : $this->_valueFmt;
			$format = $language->GetDateFormatForJS()." ".$language->GetTimeFormatForJS();
		}

		$title = GetTranslation("open-calendar");

		$result = '<span id="'.$this->_instanceName.'Display" class="calendar-da">'.$valueDA.'</span>
<i class="fa fa-calendar calendar-ctrl" id="'.$this->_instanceName.'Trigger" alt="'.$title.'"></i>
<input type="hidden" name="'.$this->_instanceName.'" id="'.$this->_instanceName.'" value="'.$valueIF.'" />';

if(self::$_includeScripts)
	$result.='<script type="text/javascript" src="'.PROJECT_PATH.'include/js_calendar/calendar.js"></script>
<script type="text/javascript" src="'.PROJECT_PATH.'include/js_calendar/calendar-setup.js"></script>
	<script type="text/javascript" src="'.PROJECT_PATH.'language/'.INTERFACE_LANGCODE.'/js_calendar.js"></script>';
 
$result .= '<script type="text/javascript">
Calendar.setup({
	displayArea: "'.$this->_instanceName.'Display",
	inputField: "'.$this->_instanceName.'",
	ifFormat: "%Y-%m-%d %H:%M",
	daFormat: "'.$format.'",
	button: "'.$this->_instanceName.'Trigger",
	singleClick: false,
	align: "Br",
	showsTime: '.($this->_dateOnly ? "false" : "true").'});
</script>';
		
		self::$_includeScripts = false;
		
		return $result;
	}

}

?>