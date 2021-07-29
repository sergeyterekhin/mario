<?php

require_once(dirname(__FILE__)."/commonobject.php");

class ObjectList extends CommonObject
{
	var $_items;
	var $_currentPage = 1;
	var $_countPages;
	var $_itemsOnPage = 0;
	var $_countTotalItems;
	var $_sortOrderFields;
	var $_orderBy;
	var $_defaultOrderByKey;
	var $_orderByParam = "OrderBy";
	var $_pageParam = "Page";
	var $_pagesArray;

	function ObjectList($data = array(), $statement = null)
	{
		if (is_array($data))
		{
			$this->LoadFromArray($data);
		}
		else if (!is_null($data))
		{
			$this->LoadFromSQL($data, $statement);
		}
		else
		{
			$this->LoadFromArray(array());
		}
	}

	function LoadFromArray($data)
	{
		$this->_items = array_values($data);

		$this->SetCurrentPage(1);
		$this->_SetCountPages(1);
		$this->SetItemsOnPage(0);
		$this->_SetCountTotalItems($this->GetCountItems());
	}

	function LoadFromSQL($query, $statement = null)
	{
		if (preg_match('/^\(?\s*select/is', $query))
		{
			if ($this->GetItemsOnPage() > 0)
			{
				$start = ($this->GetCurrentPage() - 1)*$this->GetItemsOnPage();
				$q = preg_replace('/^(\(?\s*?select)/is', '$1 SQL_CALC_FOUND_ROWS ', $query ).$this->GetOrderBySQLString()." LIMIT ".$start.", ".$this->GetItemsOnPage();
				$items = $statement->FetchList($q);

				$this->_SetCountTotalItems($statement->FetchField("SELECT FOUND_ROWS()"));
				if ($this->GetCountTotalItems() % $this->GetItemsOnPage() > 0)
				{
					$this->_SetCountPages(intval($this->GetCountTotalItems()/$this->GetItemsOnPage()) + 1);
				}
				else
				{
					$this->_SetCountPages($this->GetCountTotalItems()/$this->GetItemsOnPage());
				}

				if ($this->GetCurrentPage() > 1 && count($items) == 0)
				{
					if ($this->GetCountPages() > 0)
					{
						$this->SetCurrentPage($this->GetCountPages());
						$start = ($this->GetCurrentPage() - 1)*$this->GetItemsOnPage();
						$q = preg_replace('/^(\(?\s*?select)/is', '$1 SQL_CALC_FOUND_ROWS ', $query ).$this->GetOrderBySQLString()." LIMIT ".$start.", ".$this->GetItemsOnPage();
						$items = $statement->FetchList($q);
					}
					else
					{
						$this->_SetCountPages(1);
					}
				}
				$this->_items = is_array($items) ? $items : array();
			}
			else
			{
				$items = $statement->FetchList($query.$this->GetOrderBySQLString());
				$this->_items = is_array($items) ? $items : array();
				$this->_SetCountPages(1);
				$this->_SetCountTotalItems($this->GetCountItems());
			}
		}
	}

	function LoadFromObjectList($object)
	{
		$this->_items = $object->GetItems();
		$this->SetCurrentPage($object->GetCurrentPage());
		$this->_SetCountPages($object->GetCountPages());
		$this->SetItemsOnPage($object->GetItemsOnPage());
		$this->_SetCountTotalItems($object->GetCountTotalItems());
	}

	function SetDefaultOrderByKey($key)
	{
 		if (array_key_exists($key, $this->_sortOrderFields))
 			$this->_defaultOrderByKey = $key;
	}

	function SetSortOrderFields($value)
	{
		if (is_array($value))
		{
			$this->_sortOrderFields = $value;
			// Set first order by param as default
			$array = array_keys($value);
			$this->SetDefaultOrderByKey($array[0]);
		}
	}

	function SetOrderBy($key)
	{
		$this->_orderBy = array();
		if (is_null($key)) return;

		if (is_array($this->_sortOrderFields))
		{
			if (is_null($this->_defaultOrderByKey))
			{
				$keys = array_keys($this->_sortOrderFields);
				$this->_defaultOrderByKey = $keys[0];
			}

			if (is_array($key))
			{
				foreach ($key as $v)
				{
					if (array_key_exists($v, $this->_sortOrderFields))
					{
						array_push($this->_orderBy, $this->_sortOrderFields[$v]);
					}
				}
			}
			else
			{
				if (array_key_exists($key, $this->_sortOrderFields))
				{
					$this->_orderBy = array($this->_sortOrderFields[$key]);
				}
			}

			// If order by is not defined use default
			if (count($this->_orderBy) == 0)
			{
				$this->_orderBy = array($this->_sortOrderFields[$this->_defaultOrderByKey]);
			}
		}
	}

	function GetOrderBySQLString()
	{
		$sqlOrderBy = "";
		if (count($this->_orderBy) > 0)
		{
			$sqlOrderBy = " ORDER BY ".implode(", ", $this->_orderBy);
		}
		return $sqlOrderBy;
	}

	function GetOrderByParam()
	{
		return $this->_orderByParam;
	}

	function SetOrderByParam($value)
	{
		$this->_orderByParam = $value;
	}

	function SetCurrentPage($page = null)
	{
		$this->_currentPage = 1;

		if (is_null($page) && $this->GetItemsOnPage() > 0)
		{
			if (isset($_REQUEST[$this->GetPageParam()]))
			{
				$this->_currentPage = intval($_REQUEST[$this->GetPageParam()]);
			}
		}
		else if ($this->GetItemsOnPage() > 0)
		{
			$this->_currentPage = intval($page);
		}

		if ($this->_currentPage < 1)
		{
			$this->_currentPage = 1;
		}
	}

	function AppendFromArray($data)
	{
		$this->_items = array_merge($this->_items, array_values($data));
	}

	function AppendFromSQL($query, $statement)
	{
		$items = $statement->FetchList($query);
		if (is_array($items))
		{
			$this->_items = array_merge($this->_items, $items);
		}
	}

	function AppendFromObjectList($object)
	{
		$this->_items = array_merge($this->_items, $object->GetItems());
	}

	function AppendItem($item)
	{
		$this->_items[] = $item;
	}

	function RemoveItem($id)
	{
		array_splice($this->_items, $id, 1);
	}

	function InsertItem($item, $id)
	{
		array_splice($this->_items, $id, 0, array($item));
	}

	function GetItems()
	{
		return $this->_items;
	}

	function GetCountItems()
	{
		return count($this->_items);
	}

	function GetCurrentPage()
	{
		return $this->_currentPage;
	}

	function GetCountPages()
	{
		return $this->_countPages;
	}

	function _SetCountPages($value)
	{
		$value = intval($value);
		if ($value < 1)
			$this->_countPages = 1;
		else
			$this->_countPages = $value;
	}

	function GetItemsOnPage()
	{
		return $this->_itemsOnPage;
	}

	function SetItemsOnPage($value)
	{
		$this->_itemsOnPage = abs(intval($value));
	}

	function GetCountTotalItems()
	{
		return $this->_countTotalItems;
	}

	function _SetCountTotalItems($value)
	{
		$this->_countTotalItems = abs(intval($value));
	}

	function GetPageParam()
	{
		return $this->_pageParam;
	}

	function SetPageParam($value)
	{
		$this->_pageParam = $value;
	}

	function _GeneratePaging()
	{
		$currentPage = $this->GetCurrentPage();
		$countTotalItems = $this->GetCountTotalItems();
		$itemsOnPage = $this->GetItemsOnPage();

		if ($itemsOnPage < 1)
		{
			$this->_pagesArray = array();
			return;
		}

		if ($countTotalItems > 0)
		{
			$countPages = (int)(($countTotalItems-1)/$itemsOnPage)+1;
		}
		else
		{
			$countPages = 0;
		}

		$borders = 1;
		$window = $borders*2 + 1;
		$maxPagesWithoutHoles = $window*3 + $borders*2*2;

		$center = array();
		$right = array();
		if ($currentPage > 1)
			$left = array(0 => array("Title" => "", "Page" => $currentPage - 1, "First" => 1));
		else
			$left = array(0 => array("Title" => "", "Page" => 1, "First" => 1, "Selected" => 1));

		if ($countPages > $maxPagesWithoutHoles)
		{
			if ($countPages - $currentPage < $borders + 1)
				$start = $countPages - $window + 1;
			else if ($currentPage > $borders + 1)
				$start = $currentPage - $borders;
			else
				$start = 1;

			if ($window < $countPages && $currentPage + $borders < $window)
				$end = $window; // we have to show at least first 5 pages in left
			else if ($currentPage + $borders < $countPages)
				$end = $currentPage + $borders;
			else
				$end = $countPages;

			// Define center part of the paging
			for ($i = $start; $i < $end + 1; $i++)
			{
				if ($currentPage == $i)
					array_push($center, array("Title" => $i, "Page" => $i, "Selected" => 1));
				else
					array_push($center, array("Title" => $i, "Page" => $i));
			}

			// Define left part of the paging
			if ($start < $window*2 + 1 && $start > 0)
			{
				for ($i = 1; $i < $start; $i++)
				{
					array_push($left, array("Title" => $i, "Page" => $i));
				}
			}
			else
			{
				for ($i = 1; $i < $window + 1; $i++)
				{
					array_push($left, array("Title" => $i, "Page" => $i));
				}
				$leftHole = (int)(($window + 1 + $start)/2);
				array_push($left, array("Title" => "...", "Page" => $leftHole));
			}

			// Define right part of the paging
			if ($end > $countPages - $window*2)
			{
				for ($i = $end + 1; $i < $countPages + 1; $i++)
				{
					array_push($right, array("Title" => $i, "Page" => $i));
				}
			}
			else
			{
				$rightHole = intval(($countPages - $window + 1 + $end)/2);
				array_push($right, array("Title" => "...", "Page" => $rightHole));
				for ($i = $countPages - $window + 1; $i < $countPages + 1; $i++)
				{
					array_push($right, array("Title" => $i, "Page" => $i));
				}
			}
		}
		else
		{
			for ($i = 1; $i < $countPages + 1; $i++)
			{
				if ($currentPage == $i)
					array_push($center, array("Title" => $i, "Page" => $i, "Selected" => 1));
				else
					array_push($center, array("Title" => $i, "Page" => $i));
			}
		}

		if ($currentPage < $countPages)
			array_push($right, array("Title" => "", "Page" => $currentPage + 1, "Last" => 1));
		else
			array_push($right, array("Title" => "", "Page" => $currentPage, "Last" => 1, "Selected" => 1));

		$result = array_merge($left, $center, $right);
		if (count($result) > 3)
			$this->_pagesArray = array_merge($left, $center, $right);
		else
			$this->_pagesArray = array();
	}

	function GetPagingAsArray($url, $urlFirstPage = null)
	{
		$this->_GeneratePaging();

		$urlComponents = parse_url($url);

		if (!isset($urlComponents["query"]))
		{
			$urlComponents["query"] = "";
		}
		else if (substr($urlComponents["query"], 0, 1) != "?")
		{
			$urlComponents["query"] = "?".$urlComponents["query"];
		}

		// If placeholder for page parameter is not found in path, create it in query
		if (!strstr($urlComponents["path"], "[[".$this->GetPageParam()."]]"))
		{
			if ($urlComponents["query"])
				$urlComponents["query"] = $urlComponents["query"]."&".$this->GetPageParam()."=[[".$this->GetPageParam()."]]";
			else
				$urlComponents["query"] = "?".$this->GetPageParam()."=[[".$this->GetPageParam()."]]";
		}

		// Construct URL
		if (isset($urlComponents["scheme"]) && isset($urlComponents["host"]))
		{
			if (isset($urlComponents["port"]))
			{
				$url = $urlComponents["scheme"]."://".$urlComponents["host"].":".$urlComponents["port"].$urlComponents["path"].$urlComponents["query"];
			}
			else
			{
				$url = $urlComponents["scheme"]."://".$urlComponents["host"].$urlComponents["path"].$urlComponents["query"];
			}
		}
		else
		{
			$url = $urlComponents["path"].$urlComponents["query"];
		}

		if (count($this->_pagesArray) > 0)
		{
			foreach ($this->_pagesArray as $k => $v)
			{
				if ($v["Page"] == 1 && !is_null($urlFirstPage))
					$newUrl = $urlFirstPage;
				else
					$newUrl = str_replace("[[".$this->GetPageParam()."]]", $v["Page"], $url);
				$this->_pagesArray[$k]["URL"] = $newUrl;
			}
		}

		return $this->_pagesArray;
	}

	function GetPagingAsHTML($url, $urlFirstPage = null)
	{
		$this->GetPagingAsArray($url, $urlFirstPage);

		$pagesHTML = "";
		if (count($this->_pagesArray) > 0)
		{
			foreach ($this->_pagesArray as $v)
			{
				if (isset($v["First"]) && $v["First"] == 1)
				{
					if (isset($v["Selected"]) && $v["Selected"] == 1)
						$pagesHTML .= "";
					else
						$pagesHTML .= "<li><a href=\"".$v["URL"]."\">«</a>";
				}
				else if (isset($v["Last"]) && $v["Last"] == 1)
				{
					if (isset($v["Selected"]) && $v["Selected"] == 1)
						$pagesHTML .= "";
					else
						$pagesHTML .= "<li><a href=\"".$v["URL"]."\">»</a>";
				}
				else if (isset($v["Selected"]) && $v["Selected"] == 1)
				{
					$pagesHTML .= "<li class=\"active\"><a href=\"".$v["URL"]."\">".$v["Title"]."</a></li>";
				}
				else
				{
					$pagesHTML .= "<li><a href=\"".$v["URL"]."\">".$v["Title"]."</a></li>";
				}
			}
		}

		return $pagesHTML;
	}

	function GetItemsRange()
	{
		$currentPage = $this->GetCurrentPage();
		$itemsOnPage = $this->GetItemsOnPage();
		$totalItems = $this->GetCountTotalItems();

		$first = $currentPage * $itemsOnPage - $itemsOnPage + 1;
		$last = $currentPage * $itemsOnPage;
		if ($last > $totalItems || $itemsOnPage == 0) $last = $totalItems;

		return $first.'-'.$last;
	}
}
?>