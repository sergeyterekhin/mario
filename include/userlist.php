<?php

es_include("localobjectlist.php");

class UserList extends LocalObjectList
{
	var $module;
	var $params;

	function UserList($data = array())
	{
		parent::LocalObjectList($data);

		$this->SetItemsOnPage(abs(intval(GetFromConfig("UsersPerPage"))));

		$this->SetSortOrderFields(array(
			"UserIDAsc" => "UserID ASC",
			"UserIDDesc" => "UserID DESC",
			"CreatedAsc" => "Created ASC",
			"CreatedDesc" => "Created DESC",
			"NameAsc" => "Name ASC",
			"NameDesc" => "Name DESC",
			"LastLoginAsc" => "LastLogin ASC",
			"LastLoginDesc" => "LastLogin DESC"));
		$this->SetDefaultOrderByKey(GetFromConfig("UsersOrderBy"));
	}

	function GetQueryPrefix()
	{
		$query = "SELECT UserID, Email, Name, UserImage, Role, WebsiteID, Created, LastLogin, LastIP 
					FROM `user`";

		return $query;
	}

	function LoadUserList($request)
	{
		$this->SetOrderBy(isset($_REQUEST[$this->GetOrderByParam()]) ? $_REQUEST[$this->GetOrderByParam()] : GetFromConfig("UsersOrderBy"));

		$where = array();

		$roleList = $request->GetProperty("RoleList");
		if (is_array($roleList) && count($roleList) > 0)
		{
			$where[] = "Role IN (".implode(",", Connection::GetSQLArray($roleList)).")";
		}
		if ($request->GetIntProperty("WebsiteID") > 0)
		{
			$where[] = "WebsiteID=".$request->GetIntProperty("WebsiteID");
		}
		if ($request->GetProperty("SearchString"))
		{
			$where[] = "(Name LIKE('%".Connection::GetSQLLike($request->GetProperty("SearchString"))."%') OR Email LIKE('%".Connection::GetSQLLike($request->GetProperty("SearchString"))."%'))";
		}

		$query = $this->GetQueryPrefix().(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "");

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);

		for ($i = 0; $i < count($this->_items); $i++)
		{
			$this->_items[$i]["RoleTitle"] = GetTranslation("role-".$this->_items[$i]["Role"]);
		}
	}

	function Remove($request)
	{
		$ids = $request->GetProperty("UserIDs");
		if (is_array($ids) && count($ids) > 0)
		{
			$where = array();

			$where[] = "UserID IN (".implode(",", Connection::GetSQLArray($ids)).")";

			$roleList = $request->GetProperty("RoleList");
			if (is_array($roleList) && count($roleList) > 0)
			{
				$where[] = "Role IN (".implode(",", Connection::GetSQLArray($roleList)).")";
			}
			if ($request->GetIntProperty("WebsiteID") > 0)
			{
				$where[] = "WebsiteID=".$request->GetIntProperty("WebsiteID");
			}
			if ($request->GetIntProperty("CurrentUserID") > 0)
			{
				$where[] = "UserID<>".$request->GetIntProperty("CurrentUserID");
			}

			$stmt = GetStatement();

			$removed = array();
			$removedIDs = array();

			$query = $this->GetQueryPrefix()." WHERE ".implode(" AND ", $where);
			if ($result = $stmt->FetchList($query))
			{
				for ($i = 0; $i < count($result); $i++)
				{
					$removed[] = $result[$i]['Name'];
					$removedIDs[] = $result[$i]['UserID'];
					if($result[$i]["UserImage"])
					{
						@unlink(USER_IMAGE_DIR.$result[$i]["UserImage"]);
					}
				}
			}

			$count = count($removed);

			if ($count > 0)
			{
				// Delete user sessions
				$query = "DELETE FROM `session` WHERE UserID IN (".implode(",", Connection::GetSQLArray($removedIDs)).")";
				$stmt->Execute($query);

				// Delete user
				$query = "DELETE FROM `user` WHERE UserID IN (".implode(",", Connection::GetSQLArray($removedIDs)).")";
				$stmt->Execute($query);

				if ($count > 1)
					$key = "users-are-removed";
				else
					$key = "user-is-removed";

				$this->AddMessage($key, array("UserList" => "\"".implode("\", \"", $removed)."\"", "UserCount" => $count));
			}
		}
	}
}

?>