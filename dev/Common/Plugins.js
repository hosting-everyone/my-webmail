import { settingsAddViewModel } from 'Screen/AbstractSettings';
import { SettingsGet } from 'Common/Globals';
import { AbstractViewPopup } from 'Knoin/AbstractViews';
import { isArray, isFunction } from 'Common/Utils';

const SIMPLE_HOOKS = {},
	USER_VIEW_MODELS_HOOKS = [],
	ADMIN_VIEW_MODELS_HOOKS = [];

/**
 * @param {string} name
 * @param {Function} callback
 */
rl.addHook = (name, callback) => {
	if (isFunction(callback)) {
		if (!isArray(SIMPLE_HOOKS[name])) {
			SIMPLE_HOOKS[name] = [];
		}
		SIMPLE_HOOKS[name].push(callback);
	}
};

/**
 * @param {string} name
 * @param {Array=} args = []
 */
export function runHook(name, args = []) {
	if (isArray(SIMPLE_HOOKS[name])) {
		SIMPLE_HOOKS[name].forEach(callback => callback(...args));
	}
}

/**
 * @param {Function} callback
 * @param {string} action
 * @param {Object=} parameters
 * @param {?number=} timeout
 */
rl.pluginRemoteRequest = (callback, action, parameters, timeout) => {
	rl.app.Remote.request('Plugin' + action, callback, parameters, timeout);
};

/**
 * @param {Function} SettingsViewModelClass
 * @param {string} labelName
 * @param {string} template
 * @param {string} route
 */
rl.addSettingsViewModel = (SettingsViewModelClass, template, labelName, route) => {
	USER_VIEW_MODELS_HOOKS.push([SettingsViewModelClass, template, labelName, route]);
};

/**
 * @param {Function} SettingsViewModelClass
 * @param {string} labelName
 * @param {string} template
 * @param {string} route
 */
rl.addSettingsViewModelForAdmin = (SettingsViewModelClass, template, labelName, route) => {
	ADMIN_VIEW_MODELS_HOOKS.push([SettingsViewModelClass, template, labelName, route]);
};

/**
 * @param {boolean} admin
 */
export function runSettingsViewModelHooks(admin) {
	(admin ? ADMIN_VIEW_MODELS_HOOKS : USER_VIEW_MODELS_HOOKS).forEach(view =>
		settingsAddViewModel(...view)
	);
}

/**
 * @param {string} pluginSection
 * @param {string} name
 * @returns {?}
 */
rl.pluginSettingsGet = (pluginSection, name) =>
	SettingsGet('Plugins')?.[pluginSection]?.[name];

rl.pluginPopupView = AbstractViewPopup;
