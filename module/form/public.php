<?php

es_include('modulehandler.php');

class FormHandler extends ModuleHandler
{
	function ProcessPublic()
	{
		$this->header["InsideModule"] = $this->module;
		if (!$this->IsMainHTML())
			Send404();

		if ($this->templateSet == "userauth")
		{
			es_include("user.php");
			$request = new LocalObject(array_merge($_POST, $_GET));
			$user = new User();

			if ($request->GetProperty("Logout"))
			{
				$user->Logout();
			}

			if ($user->LoadBySession())
			{
				// Edit personal info form
				$this->Register($user);
			}
			else
			{
				switch ($request->GetProperty("Show"))
				{
					case "fp":
						// Forgot password form
						$this->ForgotPassword($user);
						break;
					case "r":
						// Registration form
						$this->Register($user);
						break;
					default:
						// Login form
						$this->Login($user);
						break;
				}
			}
		}
		else
		{
			$this->Form2Email();
		}
	}

	function Form2Email()
	{
		$publicPage = new PublicPage($this->module);
		$content = $publicPage->Load($this->tmplPrefix.'form.html', $this->header, $this->pageID);
		$content->SetVar('Title', $this->header['Title']);
		$content->SetVar('TitleH1', $this->header['TitleH1']);
		$content->SetVar('FormAction', $this->baseURL.HTML_EXTENSION);
		$content->SetLoop('Navigation', $this->header['Navigation']);

		if ($this->templateSet)
			$lngPrefix = $this->templateSet;
		else
			$lngPrefix = $this->module;

		es_include("filesys.php");
		$fileSys = new FileSys();

		$request = new LocalObject(array_merge($_POST, $_FILES));

		$formFields = array();

		if ($request->GetProperty('FormSubmitted') == 'Yes')
		{
			$errors = array();
			$emailFields = array();

			if ($this->config['UseCaptcha'])
			{
				$session =& GetSession();
				if (strlen($request->GetProperty('CaptchaCode')) == 0 || strtoupper($request->GetProperty('CaptchaCode')) != $session->GetProperty('CaptchaCode'))
				{
					$errors[] = array('Message' => GetTranslation('incorrect-captcha', $this->module), 'Field' => 'CaptchaCode');
				}
			}

			$supportedTypes = array('Text', 'Email', 'Select', 'File');
			$acceptMimeTypes = array(
				'image/png',
				'image/gif',
				'image/jpeg',
				'image/pjpeg',
				'application/x-shockwave-flash',
				'image/bmp',
				'audio/mpeg',
				'text/xml',
				'text/plain',
				'application/pdf',
				'application/vnd.visio',
				'application/vnd.ms-excel',
				'application/vnd.ms-powerpoint',
				'application/msword',
				'application/x-zip-compressed',
				'video/avi'
			);
			$uploadedFiles = array();

			foreach ($request->GetProperties() as $property => $value)
			{
				$words = explode('_', $property);

				if (is_array($words) && isset($words[0]) && in_array($words[0], $supportedTypes))
				{
					$type = $words[0];
				}
				else
				{
					continue;
				}

				array_shift($words);

				$fieldName = null;
				if (!is_array($value)) $value = trim($value);

				if ($type == "File")
				{
					$file = $fileSys->Upload($property, PROJECT_DIR."var/log/", true, $acceptMimeTypes);
					if ($file) $uploadedFiles[$property] = $file["FileName"];
				}

				if (count($words) == 1)
				{
					$fieldName = $words[0];
				}
				else if (count($words) > 1)
				{
					$required = false;
					if (strtolower($words[count($words) - 1]) == 'r')
					{
						array_pop($words);
						$required = true;
					}

					$fieldName = implode('_', $words);

					if ($type == "File")
					{
						if ($required && !$uploadedFiles[$property])
						{
							$errors[] = array('Message' => GetTranslation($lngPrefix.'-required-'.$fieldName, $this->module), 'Field' => $fieldName);
						}
					}
					else if (is_array($value))
					{
						if ($required && count($value) == 1)
						{
							$errors[] = array('Message' => GetTranslation($lngPrefix.'-required-'.$fieldName, $this->module), 'Field' => $fieldName);
						}
					}
					else
					{
						if ($required && $value == '')
						{
							$errors[] = array('Message' => GetTranslation($lngPrefix.'-required-'.$fieldName, $this->module), 'Field' => $fieldName);
						}
					}
				}

				if (!is_null($fieldName))
				{
					if ($type == "File")
					{
					}
					else if (is_array($value))
					{
						// Create variables for checked values
						foreach ($value as $k => $v)
						{
							$content->SetVar($fieldName.'_'.$k, 1);
						}
					}
					else if ($type == "Select" && strlen($value) > 0)
					{
						// Create variable for selected value
						$content->SetVar($fieldName.'_'.$value, 1);
						$value = GetTranslation($lngPrefix.'-value-'.$fieldName.'_'.$value, $this->module);
					}
					else if ($type == 'Email' && strlen($value) > 0)
					{
						// Check e-mail format
						if (!preg_match("/^[a-z0-9\._-]+@([a-z0-9_-]+\.)+[a-z0-9_-]+/i", $value))
						{
							$errors[] = array('Message' => GetTranslation($lngPrefix.'-incorrect-'.$fieldName, $this->module), 'Field' => $fieldName);
						}
					}

					if ($type == "File")
					{
						$emailFields[] = array('Name' => GetTranslation($lngPrefix.'-email-'.$fieldName, $this->module), 'Value' => (isset($uploadedFiles[$property]) ? $uploadedFiles[$property] : GetTranslation("file-not-uploaded", $this->module)));

					}
					else if (is_array($value))
					{
						$result = array();
						foreach ($value as $k => $v)
						{
							if ($v) $result[$k] = GetTranslation($lngPrefix.'-value-'.$fieldName.'_'.$v, $this->module);
						}
						$emailFields[] = array('Name' => GetTranslation($lngPrefix.'-email-'.$fieldName, $this->module), 'Value' => implode(", ", $result));
					}
					else
					{
						$emailFields[] = array('Name' => GetTranslation($lngPrefix.'-email-'.$fieldName, $this->module), 'Value' => $value);
						$formFields[$fieldName] = strip_tags($value);
					}
				}
			}

			if (count($uploadedFiles) > 0)
			{
				foreach ($uploadedFiles as $k => $v)
				{
					$uploadedFiles[$k] = PROJECT_DIR."var/log/".$v;
				}
			}

			if (count($errors) > 0)
			{
				array_unshift($errors, array('Message' => GetTranslation($lngPrefix.'-error', $this->module), 'Field' => 'Common'));
				
				if ($request->GetProperty('UseAjax') == 'Yes')
				{
					echo json_encode(array(
						"Status" => "error",
						"ErrorList" => $errors,
					));
					die();
				}
				else
				{
					foreach ($errors as $k => $v)
					{
						$content->SetVar('Error'.$v['Field'], $v['Message']);
					}
					$content->SetLoop('ErrorList', $errors);
					$content->LoadFromArray($formFields);
				}

				if (count($uploadedFiles) > 0)
				{
					foreach ($uploadedFiles as $v) @unlink($v);
				}
			}
			else if (count($emailFields) > 0)
			{
				$emailTemplate = new PopupPage($this->module, false);
				$email = $emailTemplate->Load($this->tmplPrefix.'email.html');
				$email->SetLoop('FieldList', $emailFields);

				$result = SendMailFromAdmin($this->config['Email'], $this->config['Subject'], $emailTemplate->Grab($email), $uploadedFiles);

				if (count($uploadedFiles) > 0)
				{
					foreach ($uploadedFiles as $v) @unlink($v);
				}

				if ($result === true)
				{
					$session = GetSession();
					$messages = array(array('Message' => GetTranslation($lngPrefix.'-message', $this->module)));
					if ($request->GetProperty('UseAjax') == 'Yes')
					{
						echo json_encode(array(
							"Status" => "success",
							"MessageList" => $messages,
						));
						die();
					}
					else 
					{
						$content->SetLoop('MessageList', $messages);
					}
					$session->RemoveProperty('CaptchaCode');
					$session->SaveToDB();
				}
				else
				{
					$content->LoadFromArray($formFields);
					$errors = array(array('Message' => GetTranslation('error-sending-email').' ('.$result.')', 'Field' => 'Common'));
					if ($request->GetProperty('UseAjax') == 'Yes')
					{
						echo json_encode(array(
							"Status" => "error",
							"ErrorList" => $errors,
						));
						die();
					}
					else
					{
						$content->SetLoop('ErrorList', $errors);
					}
				}
			}
		}

		$content->SetVar("RandomString", $fileSys->RandStr(10));

		for ($i = 1; $i < $this->header['MenuImageCount'] + 1; $i++)
		{
			$content->SetVar('MenuImage'.$i, $this->header['MenuImage'.$i]);
			$content->SetVar('MenuImage'.$i.'Path', $this->header['MenuImage'.$i.'Path']);
		}

		$content->SetVar('PageID', $this->pageID);

		$content->SetVar('PageContent', $this->content);

		$publicPage->Output($content);
	}

	function Login($user)
	{
		$publicPage = new PublicPage($this->module);
		$content = $publicPage->Load($this->tmplPrefix.'login.html', $this->header, $this->pageID);
		$content->SetVar('Title', $this->header['Title']);
		$content->SetVar('FormAction', $this->baseURL.HTML_EXTENSION);
		$content->SetLoop('Navigation', $this->header['Navigation']);

		$request = new LocalObject(array_merge($_POST, $_GET));

		if ($request->GetProperty("Login"))
		{
			if ($user->LoadByRequest($request))
			{
				// TODO: Discuss & decide where to redirect user after login
				if ($request->GetProperty("ReturnPath"))
					header("Location: ".$request->GetProperty("ReturnPath"));
				else
					header("Location: ".PROJECT_PATH.INDEX_PAGE.HTML_EXTENSION);
				exit();
			}
		}
		$content->LoadErrorsFromObject($user);
		$content->LoadMessagesFromObject($user);

		$content->SetVar('Login', $request->GetProperty("Login"));
		$content->SetVar("ReturnPath", $request->GetProperty("ReturnPath"));

		for ($i = 1; $i < $this->header['MenuImageCount'] + 1; $i++)
		{
			$content->SetVar('MenuImage'.$i, $this->header['MenuImage'.$i]);
			$content->SetVar('MenuImage'.$i.'Path', $this->header['MenuImage'.$i.'Path']);
		}

		$content->SetVar('PageID', $this->pageID);

		$content->SetVar('PageContent', $this->content);

		$publicPage->Output($content);
	}

	function ForgotPassword($user)
	{
		$publicPage = new PublicPage($this->module);

		// Modify header
		$this->header["Title"] = GetTranslation("forgot-password-title", $this->module);
		$lIndex = count($this->header["Navigation"]) - 1;
		$this->header["Navigation"][$lIndex]["Title"] = $this->header["Title"];
		$this->header["Navigation"][$lIndex]["PageURL"] = $this->header["Navigation"][$lIndex]["PageURL"]."?Show=fp";

		$content = $publicPage->Load($this->tmplPrefix.'forgot.html', $this->header, $this->pageID);
		$content->SetVar('Title', $this->header["Title"]);
		$content->SetVar('FormAction', $this->baseURL.HTML_EXTENSION);
		$content->SetLoop('Navigation', $this->header['Navigation']);

		$request = new LocalObject(array_merge($_POST, $_GET));

		if ($request->GetProperty('Email'))
		{
			$session =& GetSession();
			if (strtoupper($request->GetProperty('CaptchaCode')) != $session->GetProperty('CaptchaCode'))
			{
				$user->AddError('incorrect-captcha', $this->module);
				$content->SetVar('Email', $request->GetProperty('Email'));
			}
			else
			{
				$user->SetProperty('Email', $request->GetProperty('Email'));
				if (!$user->SendPasswordToEmail())
				{
					$content->SetVar('Email', $request->GetProperty('Email'));
				}
			}
		}
		$content->LoadErrorsFromObject($user);
		$content->LoadMessagesFromObject($user);

		for ($i = 1; $i < $this->header['MenuImageCount'] + 1; $i++)
		{
			$content->SetVar('MenuImage'.$i, $this->header['MenuImage'.$i]);
			$content->SetVar('MenuImage'.$i.'Path', $this->header['MenuImage'.$i.'Path']);
		}

		$content->SetVar('PageID', $this->pageID);

		$content->SetVar('PageContent', GetTranslation("forgot-password-content", $this->module));

		$publicPage->Output($content);
	}

	function Register($user)
	{
		$publicPage = new PublicPage($this->module);

		// Modify header
		if ($user->GetProperty("UserID") > 0)
		{
			$this->header["Title"] = GetTranslation("profile-title", $this->module);
			$pageContent = GetTranslation("profile-content", $this->module);
		}
		else
		{
			$this->header["Title"] = GetTranslation("register-title", $this->module);
			$pageContent = GetTranslation("register-content", $this->module);
		}
		$lIndex = count($this->header["Navigation"]) - 1;
		$this->header["Navigation"][$lIndex]["Title"] = $this->header["Title"];
		$this->header["Navigation"][$lIndex]["PageURL"] = $this->header["Navigation"][$lIndex]["PageURL"]."?Show=r";

		$content = $publicPage->Load($this->tmplPrefix.'register.html', $this->header, $this->pageID);
		$content->SetVar('Title', $this->header["Title"]);
		$content->SetVar('FormAction', $this->baseURL.HTML_EXTENSION);
		$content->SetLoop('Navigation', $this->header['Navigation']);

		$request = new LocalObject(array_merge($_POST, $_GET));

		if ($request->GetProperty("FormSubmitted") == "Yes")
		{
			$authUserID = $user->GetProperty("UserID");
			$user->AppendFromObject($request);
			if ($user->UpdateRegistrationData($authUserID))
			{
				$content->LoadMessagesFromObject($user);
			}
			else
			{
				$content->LoadErrorsFromObject($user);
			}
		}

		$content->LoadFromObject($user);

		for ($i = 1; $i < $this->header['MenuImageCount'] + 1; $i++)
		{
			$content->SetVar('MenuImage'.$i, $this->header['MenuImage'.$i]);
			$content->SetVar('MenuImage'.$i.'Path', $this->header['MenuImage'.$i.'Path']);
		}

		$content->SetVar('PageID', $this->pageID);

		$content->SetVar('PageContent', $pageContent);

		$publicPage->Output($content);
	}

	function ProcessHeader($module)
	{
		$data = array();

		$pageList = new PageList();
		$pageList->LoadPageListForModule($module);
		$result = $pageList->GetItems();

		$page = new Page();

		for ($i = 0; $i < count($result); $i++)
		{
			$page->LoadByID($result[$i]['PageID']);
			$data[strtoupper($module).'_'.$result[$i]['PageStaticPath'].'_URL'] = $page->GetPageURL(false);
		}

		return $data;
	}
}

?>