import { AbstractViewRight } from 'Knoin/AbstractViews';

import { addObservablesTo, addComputablesTo } from 'External/ko';
import { computedPaginatorHelper } from 'Common/UtilsUser';

import { Selector } from 'Common/Selector';

import { SettingsUserStore } from 'Stores/User/Settings';
import { ContactUserStore } from 'Stores/User/Contact';

import { decorateKoCommands } from 'Knoin/Knoin';

const
	CONTACTS_PER_PAGE = 50;

export class ContactsList extends AbstractViewRight {
	constructor() {
		super();

		addObservablesTo(this, {
			search: '',
			contactsCount: 0,

			selectorContact: null,

			importButton: null,

			contactsPage: 1,

			isSaving: false,

			contact: null
		});

		addComputablesTo(this, {
			checkAll: {
				read: () => ContactUserStore.hasChecked(),
				write: (value) => {
					value = !!value;
					ContactUserStore.forEach(contact => contact.checked(value));
				}
			}
		});

		this.contacts = ContactUserStore;

		this.useCheckboxesInList = SettingsUserStore.useCheckboxesInList;

		this.selector = new Selector(
			ContactUserStore,
			this.selectorContact,
			null,
			'.e-contact-item',
			'.e-contact-item .checkboxItem'
		);

		this.selector.on('ItemSelect', contact => this.populateViewContact(contact));

		this.selector.on('ItemGetUid', contact => contact ? contact.id() : '');

		addComputablesTo(this, {
			contactsPaginator: computedPaginatorHelper(
				this.contactsPage,
				() => Math.max(1, Math.ceil(this.contactsCount() / CONTACTS_PER_PAGE))
			),

			contactsCheckedOrSelected: () => {
				const checked = ContactUserStore.filter(item => item.checked()),
					selected = this.selectorContact();
				return checked.length ? checked : (selected ? [selected] : []);
			},

			contactsSyncEnabled: () => ContactUserStore.allowSync() && ContactUserStore.syncMode(),

			isIncompleteChecked: () => {
				const c = ContactUserStore.listChecked().length;
				return c && ContactUserStore().length > c;
			},

			isBusy: () => ContactUserStore.syncing() | ContactUserStore.importing() | ContactUserStore.loading()
				| this.isSaving()
		});

		this.search.subscribe(() => this.reloadContactList());

		decorateKoCommands(this, {
			deleteCommand: self => !self.isBusy() && 0 < self.contactsCheckedOrSelected().length,
			newMessageCommand: self => !self.isBusy() && 0 < self.contactsCheckedOrSelected().length,
			syncCommand: self => !self.isBusy()
		});
	}

	clearSearch() {
		this.search('');
	}
}
