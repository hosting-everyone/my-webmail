import { ScopeContacts } from 'Common/Enums';
import { i18n } from 'Common/Translator';

import { AppUserStore } from 'Stores/User/App';

import { SystemDropDownUserView } from 'View/User/SystemDropDown';
import { AddressBooks } from 'View/User/Contacts/AddressBooks';
import { ContactsList } from 'View/User/Contacts/ContactsList';
import { ContactView } from 'View/User/Contacts/ContactView';

import { AbstractScreen } from 'Knoin/AbstractScreen';

export class ContactsUserScreen extends AbstractScreen {

	constructor() {
		super('contacts', [
			SystemDropDownUserView,
			AddressBooks,
			ContactsList,
			ContactView
		]);
	}

	onShow() {
		this.setTitle();
		AppUserStore.focusedState('none');
		AppUserStore.focusedState(ScopeContacts);
	}

	setTitle() {
		rl.setTitle(i18n('GLOBAL/CONTACTS'));
	}
}
