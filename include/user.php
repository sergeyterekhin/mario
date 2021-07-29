<?php

es_include("localobject.php");
es_include("phpmailer/phpmailer.php");

class User extends LocalObject
{
	var $_acceptMimeTypes = array(
			'image/png',
			'image/x-png',
			'image/gif',
			'image/jpeg',
			'image/pjpeg'
	);
	var $params;

	function User($data = array())
	{
		parent::LocalObject($data);
		$this->params = LoadImageConfig('UserImage', "user", GetFromConfig("UserImage"));
	}
	
	function GetQueryPrefix()
	{
		$query = "SELECT UserID, Email, Name, UserImage, UserImageConfig, Phone, Role, WebsiteID, Created, LastLogin, LastIP 
					FROM `user`";

		return $query;
	}

	function LoadByID($id, $authRole = null, $authWebsiteID = null)
	{
		if (is_null($authRole))
		{
			$query = $this->GetQueryPrefix()." WHERE UserID=".intval($id);
		}
		else
		{
			$roles = $this->GetAvailableRoles($authRole, false);
			if (is_array($roles) && count($roles) > 0)
			{
				// Do not load users with higher role or another website
				$query = $this->GetQueryPrefix()." WHERE UserID=".intval($id)."
					AND Role IN (".implode(",", Connection::GetSQLArray($roles)).")";

				if (!($authRole == INTEGRATOR || ($authRole == ADMINISTRATOR && is_null($authWebsiteID))))
				{
					$query .= " AND WebsiteID=".intval($authWebsiteID);
				}
			}
			else
			{
				return false;
			}
		}
		$this->LoadFromSQL($query);

		if ($this->GetIntProperty("UserID"))
		{
			$this->PrepareBeforeShow();
			return true;
		}
		else
		{
			return false;
		}
	}

	function LoadBySession()
	{
		// Clear properties before load
		$this->LoadFromArray(array());

		$session =& GetSession();
		if (is_array($session->GetProperty("LoggedInUser")))
		{
			// Check that logged in user has access to current website
			$user = $session->GetProperty("LoggedInUser");
			if (is_null($user["WebsiteID"]) || $user["WebsiteID"] == WEBSITE_ID)
			{
				$this->LoadFromArray($user);
				$session->UpdateExpireDate();
				return true;
			}
		}
		return false;
	}

	function LoadByRequest($request)
	{
		$query = $this->GetQueryPrefix()." WHERE
			Email=".$request->GetPropertyForSQL("Login")." AND
			Passwd=MD5(".$request->GetPropertyForSQL("Password").")
			AND (WebsiteID IS NULL OR WebsiteID=".intval(WEBSITE_ID).")";
		$this->LoadFromSQL($query);

		if ($this->GetIntProperty("UserID"))
		{
			$this->PrepareBeforeShow();

			$stmt = GetStatement();
			$query = "UPDATE `user` SET LastLogin=NOW(),
				LastIP=".Connection::GetSQLString(getenv("REMOTE_ADDR"))."
				WHERE UserID=".$this->GetIntProperty("UserID");
			$stmt->Execute($query);

			$session =& GetSession();
			$session->SetProperty("LoggedInUser", $this->GetProperties());
			$session->SaveToDB($request->GetIntProperty("RememberMe"));

			return true;
		}
		else
		{
			$this->AddError("wrong-login-password");
			return false;
		}
	}

	function PrepareBeforeShow()
	{
		if ($this->GetIntProperty("UserID") > 0)
			$this->SetProperty("RoleTitle", GetTranslation("role-".$this->GetProperty("Role")));
		
		if ($this->GetProperty("UserImage"))
		{
			$imageConfig = LoadImageConfigValues("UserImage", $this->GetProperty("UserImageConfig"));
			$this->AppendFromArray($imageConfig);
			
			for ($i = 0; $i < count($this->params); $i++)
			{
				$v = $this->params[$i];

				if($v["Resize"] == 13)
					$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"], 
																	$this->GetIntProperty($v["Name"]."X1"), 
																	$this->GetIntProperty($v["Name"]."Y1"), 
																	$this->GetIntProperty($v["Name"]."X2"), 
																	$this->GetIntProperty($v["Name"]."Y2")).$this->GetProperty("UserImage"));
				else
					$this->SetProperty($v["Name"]."Path", $v["Path"].$this->GetProperty("UserImage"));
		
				if ($v["Name"] != 'UserImage')
				{
					$this->SetProperty($v["Name"]."Width", $v["Width"]);
					$this->SetProperty($v["Name"]."Height", $v["Height"]);
				}
			}
		}
	}

	function GetImageParams()
	{
		$paramList = array();
		for ($i = 0; $i < count($this->params); $i++)
		{
			$paramList[] = array(
				"Name" => $this->params[$i]['Name'],
				"SourceName" => $this->params[$i]['SourceName'],
				"Width" => $this->params[$i]['Width'],
				"Height" => $this->params[$i]['Height'],
				"Resize" => $this->params[$i]['Resize'],
				"X1" => $this->GetIntProperty("UserImage".$this->params[$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty("UserImage".$this->params[$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty("UserImage".$this->params[$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty("UserImage".$this->params[$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}

	function Validate($role = null)
	{
		if ($this->GetIntProperty("UserID"))
		{
			if (is_array($role))
			{
				if (in_array($this->GetProperty("Role"), $role))
				{
					return true;
				}
			}
			else if ($this->GetProperty("Role") == $role || is_null($role))
			{
				return true;
			}
		}

		return false;
	}

	function ValidateAccess($role = null)
	{
		if ($this->LoadBySession())
		{
			if (is_array($role))
			{
				if (in_array($this->GetProperty("Role"), $role))
				{
					return true;
				}
			}
			else if ($this->GetProperty("Role") == $role || is_null($role))
			{
				return true;
			}

			if (defined('IS_ADMIN'))
				Send403();
			else
				return false;
		}
		else
		{
			// Not logged in users redirect to home page
			if (defined('IS_ADMIN'))
			{
				header("Location: ".ADMIN_PATH."index.php?ReturnPath=".urlencode($_SERVER['REQUEST_URI']));
				exit();
			}
			else
			{
				return false;
			}
		}
	}

	function Logout()
	{
		// Clear properties before logout
		$this->LoadFromArray(array());

		$session =& GetSession();
		$session->RemoveProperty("LoggedInUser");
		$session->SaveToDB();

		$this->AddMessage("logged-out");
	}

	function GetAvailableRoles($authRole, $forTemplate = true)
	{
		$roles = array();
		switch($authRole)
		{
			case INTEGRATOR:
				if ($forTemplate)
				{
					$roles[] = array("Value" => INTEGRATOR, "Title" => GetTranslation("role-".INTEGRATOR));
					$roles[] = array("Value" => ADMINISTRATOR, "Title" => GetTranslation("role-".ADMINISTRATOR));
					$roles[] = array("Value" => MODERATOR, "Title" => GetTranslation("role-".MODERATOR));
					$roles[] = array("Value" => USER, "Title" => GetTranslation("role-".USER));
				}
				else
				{
					$roles = array(INTEGRATOR, ADMINISTRATOR, MODERATOR, USER);
				}
				break;
			case ADMINISTRATOR:
				if ($forTemplate)
				{
					$roles[] = array("Value" => ADMINISTRATOR, "Title" => GetTranslation("role-".ADMINISTRATOR));
					$roles[] = array("Value" => MODERATOR, "Title" => GetTranslation("role-".MODERATOR));
					$roles[] = array("Value" => USER, "Title" => GetTranslation("role-".USER));
				}
				else
				{
					$roles = array(ADMINISTRATOR, MODERATOR, USER);
				}
				break;
			case MODERATOR:
			case USER:
				if ($forTemplate)
				{
					$roles[] = array("Value" => USER, "Title" => GetTranslation("role-".USER));
				}
				else
				{
					$roles = array(USER);
				}
				break;
		}

		return $roles;
	}

	function GetAvailableWebsites($authRole, $authWebsiteID)
	{
		$websiteList = array();

		if ($authRole == INTEGRATOR || ($authRole == ADMINISTRATOR && is_null($authWebsiteID)))
		{
			$websiteList = $GLOBALS["WebsiteList"];
			for ($i = 0; $i < count($websiteList); $i++)
			{
				$websiteList[$i]["Selected"] = false;
				if ($websiteList[$i]["WebsiteID"] == $this->GetIntProperty("WebsiteID"))
					$websiteList[$i]["Selected"] = true;
			}
		}

		return $websiteList;
	}

	function Save($authRole, $authWebsiteID, $authUserID)
	{
		$stmt = GetStatement();

		$roles = $this->GetAvailableRoles($authRole, false);

		if ($this->GetIntProperty("UserID") > 0)
		{
			$query = "SELECT Role, WebsiteID FROM `user` WHERE UserID=".$this->GetIntProperty("UserID");
			$currentRole = $stmt->FetchField($query);
			if (!(is_array($roles) && count($roles) > 0 && in_array($currentRole, $roles)))
			{
				// Do not allow to edit user with higher role
				$this->RemoveProperty("UserID");
			}
		}

		if (!$this->ValidateEmail("Email"))
			$this->AddError("incorrect-email-format");

		if (!$this->ValidateNotEmpty("Name"))
			$this->AddError("name-required");

		if ($authUserID == $this->GetProperty("UserID"))
		{
			$this->SetProperty("Role", $authRole);
			$this->SetProperty("WebsiteID", $authWebsiteID);

			if ($this->GetProperty("Password1"))
			{
				$query = "SELECT COUNT(UserID) FROM `user` WHERE
					UserID=".$this->GetIntProperty("UserID")." AND
					Passwd=MD5(".$this->GetPropertyForSQL("OldPassword").")";
				if (!$stmt->FetchField($query))
				{
					$this->AddError("wrong-old-password");
				}
			}
		}
		else
		{
			if (count($roles) > 0 && in_array($this->GetProperty("Role"), $roles))
			{
				if (($authRole == INTEGRATOR || ($authRole == ADMINISTRATOR && is_null($authWebsiteID))))
				{
					if ($this->GetIntProperty("WebsiteID") == 0 && ($this->GetProperty("Role") == MODERATOR || $this->GetProperty("Role") == USER))
					{
						$this->AddError("website-undefined");
					}
				}
				else
				{
					$this->SetProperty("WebsiteID", $authWebsiteID);
				}
			}
			else
			{
				$this->AddError("role-undefined");
			}
		}

		if ($this->GetIntProperty("UserID") == 0 && !$this->GetProperty("Password1"))
			$this->AddError("password-empty");

		if ($this->GetProperty("Password1") && $this->GetProperty("Password1") != $this->GetProperty("Password2"))
			$this->AddError("password-not-equal");

		$this->SaveUserImage($this->GetProperty("SavedUserImage"));
		
		if ($this->HasErrors())
		{
			return false;
		}
		else
		{
			if ($this->GetIntProperty("WebsiteID") == 0)
			{
				$this->SetProperty("WebsiteID", null);
				$query = "SELECT COUNT(*) FROM `user` WHERE
					Email=".$this->GetPropertyForSQL("Email")." AND WebsiteID IS NULL
					AND UserID<>".$this->GetIntProperty("UserID");
			}
			else
			{
				$query = "SELECT COUNT(*) FROM `user` WHERE
					((Email=".$this->GetPropertyForSQL("Email")."
						AND WebsiteID IS NULL)
					OR
					(Email=".$this->GetPropertyForSQL("Email")."
						AND WebsiteID=".$this->GetIntProperty("WebsiteID")."))
					AND UserID<>".$this->GetIntProperty("UserID");
			}

			if ($stmt->FetchField($query))
			{
				$this->AddError("email-is-not-unique");
				return false;
			}

			if ($this->GetIntProperty("UserID") > 0)
			{
				$query = "UPDATE `user` SET
						Email=".$this->GetPropertyForSQL("Email").",
						".($this->GetProperty("Password1") ? "Passwd=MD5(".$this->GetPropertyForSQL("Password1").")," : "")."
						Name=".$this->GetPropertyForSQL("Name").",
						UserImage=".$this->GetPropertyForSQL("UserImage").",
						UserImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("UserImageConfig"))).",
						Phone=".$this->GetPropertyForSQL("Phone").",
						Role=".$this->GetPropertyForSQL("Role").",
						WebsiteID=".$this->GetPropertyForSQL("WebsiteID")."
					WHERE UserID=".$this->GetIntProperty("UserID");
			}
			else
			{
				$query = "INSERT INTO `user` (Email, Passwd, Name, UserImage, UserImageConfig, 
					Phone, Role, WebsiteID,	Created) VALUES (
						".$this->GetPropertyForSQL("Email").",
						MD5(".$this->GetPropertyForSQL("Password1")."),
						".$this->GetPropertyForSQL("Name").",
						".$this->GetPropertyForSQL("UserImage").",
						".Connection::GetSQLString(json_encode($this->GetProperty("UserImageConfig"))).",
						".$this->GetPropertyForSQL("Phone").",
						".$this->GetPropertyForSQL("Role").",
						".$this->GetPropertyForSQL("WebsiteID").",
						NOW())";
			}

			if ($stmt->Execute($query))
			{
				if ($this->GetIntProperty("UserID") == 0)
				{
					$this->SetProperty("UserID", $stmt->GetLastInsertID());
				}

				// Update current user info in session
				if ($authUserID == $this->GetProperty("UserID"))
				{
					// We have to reload data by UserID to save actual info
					$this->LoadByID($this->GetProperty("UserID"));
					$session =& GetSession();
					$session->SetProperty("LoggedInUser", $this->GetProperties());
					$session->SaveToDB();
				}

				$this->AddMessage("user-is-updated");
				return true;
			}
			else
			{
				$this->AddError("sql-error");
				return false;
			}
		}
	}

	function UpdateRegistrationData($authUserID)
	{
		if ($authUserID != $this->GetProperty("UserID"))
		{
			$this->AddError("user-edit-access-denied");
			return false;
		}

		$stmt = GetStatement();

		$session =& GetSession();

		if (!$authUserID && strtoupper($this->GetProperty('CaptchaCode')) != $session->GetProperty('CaptchaCode'))
			$this->AddError('incorrect-captcha');

		if (!$this->ValidateEmail("Email"))
			$this->AddError("incorrect-email-format");

		if (!$this->ValidateNotEmpty("Name"))
			$this->AddError("name-required");

		if ($this->GetIntProperty("UserID") == 0 && !$this->GetProperty("Password1"))
			$this->AddError("password-empty");

		if ($this->GetProperty("Password1") && $this->GetProperty("Password1") != $this->GetProperty("Password2"))
			$this->AddError("password-not-equal");

		if ($this->HasErrors())
		{
			return false;
		}
		else
		{
			$query = "SELECT COUNT(*) FROM `user` WHERE
				((Email=".$this->GetPropertyForSQL("Email")."
					AND WebsiteID IS NULL)
				OR
				(Email=".$this->GetPropertyForSQL("Email")."
					AND WebsiteID=".intval(WEBSITE_ID)."))
				AND UserID<>".$this->GetIntProperty("UserID");

			if ($stmt->FetchField($query))
			{
				$this->AddError("email-is-not-unique");
				return false;
			}

			if ($this->GetIntProperty("UserID") > 0)
			{
				$query = "UPDATE `user` SET
						Email=".$this->GetPropertyForSQL("Email").",
						".($this->GetProperty("Password1") ? "Passwd=MD5(".$this->GetPropertyForSQL("Password1").")," : "")."
						Name=".$this->GetPropertyForSQL("Name").",
						Phone=".$this->GetPropertyForSQL("Phone")."
					WHERE UserID=".$this->GetIntProperty("UserID");
			}
			else
			{
				$query = "INSERT INTO `user` (Email, Passwd, Name,
					Phone, Role, WebsiteID,	Created) 
					VALUES (
						".$this->GetPropertyForSQL("Email").",
						MD5(".$this->GetPropertyForSQL("Password1")."),
						".$this->GetPropertyForSQL("Name").",
						".$this->GetPropertyForSQL("Phone").",
						'user',
						".intval(WEBSITE_ID).",
						NOW())";
			}

			if ($stmt->Execute($query))
			{
				if ($this->GetIntProperty("UserID") > 0)
				{
					$this->AddMessage("public-user-is-updated");
				}
				else
				{
					$this->AddMessage("public-user-is-registered");
					$this->SetProperty("UserID", $stmt->GetLastInsertID());
				}

				// Update current user info in session
				// We have to reload data by UserID to save actual info
				$this->LoadByID($this->GetProperty("UserID"));
				$session->SetProperty("LoggedInUser", $this->GetProperties());
				$session->SaveToDB();

				return true;
			}
			else
			{
				$this->AddError("sql-error");
				return false;
			}
		}
	}
	
	function SaveUserImage($savedImage = "")
	{
		$fileSys = new FileSys();
	
		$newUserImage = $fileSys->Upload("UserImage", USER_IMAGE_DIR, false, $this->_acceptMimeTypes);
		if ($newUserImage)
		{
			$this->SetProperty("UserImage", $newUserImage["FileName"]);
	
			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newUserImage["FileName"])
				@unlink(USER_IMAGE_DIR.$savedImage);
		}
		else
		{
			if ($savedImage)
				$this->SetProperty("UserImage", $savedImage);
			else
				$this->SetProperty("UserImage", null);
		}

		$this->_properties["UserImageConfig"]["Width"] = 0;
		$this->_properties["UserImageConfig"]["Height"] = 0;
		
		if ($this->GetProperty('UserImage'))
		{
			if ($info = @getimagesize(USER_IMAGE_DIR.$this->GetProperty('UserImage')))
			{
				$this->_properties["UserImageConfig"]["Width"] = $info[0];
				$this->_properties["UserImageConfig"]["Height"] = $info[1];
			}
		}
		
		$this->AppendErrorsFromObject($fileSys);
	
		return !$fileSys->HasErrors();
	}
	
	function RemoveUserImage($userID, $savedImage)
	{
		if ($savedImage)
		{
			@unlink(USER_IMAGE_DIR.$savedImage);
		}
	
		$userID = intval($userID);
		if ($userID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT UserImage FROM `user` WHERE UserID=".$userID);
	
			if ($imageFile)
				@unlink(USER_IMAGE_DIR.$imageFile);
	
			$stmt->Execute("UPDATE `user` SET UserImage=NULL WHERE UserID=".$userID);
		}
	}

	function SendPasswordToEmail()
	{
		if ($this->ValidateEmail('Email'))
		{
			$stmt = GetStatement();
			$password = $this->GeneratePassword();
			$stmt->Execute("UPDATE `user` SET
				Passwd=md5(".Connection::GetSQLString($password).")
				WHERE Email=".$this->GetPropertyForSQL('Email')."
					AND (WebsiteID IS NULL OR WebsiteID=".intval(WEBSITE_ID).")");
			if ($stmt->GetAffectedRows())
			{
				$emailTemplate = new PopupPage();
				$tmpl = $emailTemplate->Load("forgot_password_email.html");
				$tmpl->SetVar("Password", $password);
				SendMailFromAdmin($this->GetProperty('Email'), GetTranslation("new-password"), $emailTemplate->Grab($tmpl));

				$this->AddMessage("password-is-changed-and-sent");
				return true;
			}
			else
			{
				$this->AddError("incorrect-email-address");
			}
		}
		else
		{
			$this->AddError("incorrect-email-format");
		}
		return false;
	}

	function GeneratePassword()
	{
		$arr = array('a','b','c','d','e','f',
			'g','h','j','k',
			'm','n','p','r','s',
			't','u','v','x','y','z',
			'A','B','C','D','E','F',
			'G','H','J','K',
			'M','N','P','R','S',
			'T','U','V','X','Y','Z',
			'2','3','4','5','6',
			'7','8','9');

		$number = mt_rand(6, 10);

		$pass = "";

		for ($i = 0; $i < $number; $i++)
		{
			$index = mt_rand(0, count($arr) - 1);
			$pass .= $arr[$index];
		}

		return $pass;
	}
}

?>