(rl => {
	if (rl) {
		var
			nId = null,
			bStarted = false
		;

		function ShowHcaptcha()
		{
			if (window.hcaptcha && null === nId)
			{
				var
					oEl = null,
					oLink = document.getElementById('plugin-Login-BottomControlGroup')
				;

				if (oLink)
				{
					oEl = document.createElement('div');
					oEl.className = 'controls';

					oLink.append(oEl);

					nId = hcaptcha.render(oEl, {
						'sitekey': rl.pluginSettingsGet('hcaptcha', 'site_key'),
						'theme': rl.pluginSettingsGet('hcaptcha', 'theme')
					});
				}
			}
		}

		window.__globalShowHcaptcha = ShowHcaptcha;

		function StartHcaptcha()
		{
			if (window.hcaptcha)
			{
				ShowHcaptcha();
			}
			else
			{
				const script = document.createElement('script');
				script.src = 'https://hcaptcha.com/1/api.js?onload=__globalShowHcaptcha&render=explicit';
				document.head.append(script);
			}
		}

		rl.addHook('user-login-submit', fSubmitResult => {
			if (null !== nId && !window.hcaptcha.getResponse(nId))
			{
				fSubmitResult(105);
			}
		});

		rl.addHook('view-model-on-show', (sName, oViewModel) => {
			if (!bStarted && oViewModel && 'LoginUserView' === sName
			 && rl.pluginSettingsGet('hcaptcha', 'show_captcha_on_login'))
			{
				bStarted = true;
				StartHcaptcha();
			}
		});

		rl.addHook('json-default-request', (sAction, oParameters) => {
			if ('Login' === sAction && oParameters && null !== nId && window.hcaptcha)
			{
				oParameters['h-captcha-response'] = hcaptcha.getResponse(nId);
			}
		});

		rl.addHook('json-default-response', (sAction, oData, sType) => {
			if ('Login' === sAction)
			{
				if (!oData || 'success' !== sType || !oData['Result'])
				{
					if (null !== nId && window.hcaptcha)
					{
						hcaptcha.reset(nId);
					}
					else if (oData && oData['Captcha'])
					{
						StartHcaptcha();
					}
				}
			}
		});
	}
})(window.rl);
