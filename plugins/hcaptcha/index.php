<?php

use RainLoop\Enumerations\PluginPropertyType;
use RainLoop\Plugins\Property;

class HcaptchaPlugin extends \RainLoop\Plugins\AbstractPlugin
{
	const
		NAME     = 'hCaptcha',
		VERSION  = '2.0',
		AUTHOR   = 'Unkorneglosk',
		URL      = 'https://github.com/Unkorneglosk',
		RELEASE  = '2021-01-04',
		REQUIRED = '2.1.0',
		CATEGORY = 'Security',
		DESCRIPTION = 'A CAPTCHA is a program that can generate and grade tests that humans can pass but current computer programs cannot.
For example, humans can read distorted text as the one shown below, but current computer programs can\'t.
More info at https://hcaptcha.com';

	/**
	 * @return void
	 */
	public function Init() : void
	{
		$this->UseLangs(true);

		$this->addJs('js/hcaptcha.js');

		$this->addHook('json.action-pre-call', 'JsonActionPreCall');
		$this->addHook('filter.json-response', 'FilterJsonResponse');
	}

	/**
	 * @return array
	 */
	public function configMapping() : array
	{
		return array(
			Property::NewInstance('site_key')->SetLabel('Site key')
				->SetAllowedInJs(true)
				->SetDefaultValue(''),
			Property::NewInstance('secret_key')->SetLabel('Secret key')
				->SetDefaultValue(''),
			Property::NewInstance('theme')->SetLabel('Theme')
				->SetAllowedInJs(true)
				->SetType(PluginPropertyType::SELECTION)
				->SetDefaultValue(array('light', 'dark')),
			Property::NewInstance('error_limit')->SetLabel('Limit')
				->SetType(PluginPropertyType::SELECTION)
				->SetDefaultValue(array(0, 1, 2, 3, 4, 5))
				->SetDescription('')
		);
	}

	/**
	 * @return string
	 */
	private function getCaptchaCacherKey()
	{
		return 'CaptchaNew/Login/'.\RainLoop\Utils::GetConnectionToken();
	}

	/**
	 * @return int
	 */
	private function getLimit()
	{
		$iConfigLimit = $this->Config()->Get('plugin', 'error_limit', 0);
		if (0 < $iConfigLimit)
		{
			$oCacher = $this->Manager()->Actions()->Cacher();
			$sLimit = $oCacher && $oCacher->IsInited() ? $oCacher->Get($this->getCaptchaCacherKey()) : '0';

			if (0 < \strlen($sLimit) && \is_numeric($sLimit))
			{
				$iConfigLimit -= (int) $sLimit;
			}
		}

		return $iConfigLimit;
	}

	/**
	 * @return void
	 */
	public function FilterAppDataPluginSection(bool $bAdmin, bool $bAuth, array &$aConfig) : void
	{
		if (!$bAdmin && !$bAuth && \is_array($aData))
		{
			$aData['show_captcha_on_login'] = 1 > $this->getLimit();
		}
	}

	/**
	 * @param string $sAction
	 */
	public function JsonActionPreCall($sAction)
	{
		if ('Login' === $sAction && 0 >= $this->getLimit())
		{
			$bResult = false;

			$sResult = $this->Manager()->Actions()->Http()->SendPostRequest(
				'https://hcaptcha.com/siteverify',
				array(
					'secret' => $this->Config()->Get('plugin', 'secret_key', ''),
					'response' => $this->Manager()->Actions()->GetActionParam('h-captcha-response', '')
				)
			);

			if ($sResult)
			{
				$aResp = @\json_decode($sResult, true);
				if (\is_array($aResp) && isset($aResp['success']) && $aResp['success'])
				{
					$bResult = true;
				}
			}

			if (!$bResult)
			{
				$this->Manager()->Actions()->Logger()->Write('HcaptchaResponse:'.$sResult);
				throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::CaptchaError);
			}
		}
	}

	/**
	 * @param string $sAction
	 * @param array $aResponseItem
	 */
	public function FilterJsonResponse($sAction, &$aResponseItem)
	{
		if ('Login' === $sAction && $aResponseItem && isset($aResponseItem['Result']))
		{
			$oCacher = $this->Manager()->Actions()->Cacher();
			$iConfigLimit = (int) $this->Config()->Get('plugin', 'error_limit', 0);

			$sKey = $this->getCaptchaCacherKey();

			if (0 < $iConfigLimit && $oCacher && $oCacher->IsInited())
			{
				if (false === $aResponseItem['Result'])
				{
					$iLimit = 0;
					$sLimut = $oCacher->Get($sKey);
					if (0 < \strlen($sLimut) && \is_numeric($sLimut))
					{
						$iLimit = (int) $sLimut;
					}

					$oCacher->Set($sKey, ++$iLimit);

					if ($iConfigLimit <= $iLimit)
					{
						$aResponseItem['Captcha'] = true;
					}
				}
				else
				{
					$oCacher->Delete($sKey);
				}
			}
		}
	}
}
