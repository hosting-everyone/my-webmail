import { AbstractViewRight } from 'Knoin/AbstractViews';
import { addObservablesTo } from 'External/ko';
import { decorateKoCommands } from 'Knoin/Knoin';
import Remote from 'Remote/User/Fetch';
import { getNotification } from 'Common/Translator';

export class ContactView extends AbstractViewRight {
	constructor() {
		super();

		addObservablesTo(this, {
			contact: null
		});

		this.saveCommand = this.saveCommand.bind(this);

		decorateKoCommands(this, {
			saveCommand: self => !self.isBusy()
		});
	}

	saveCommand() {
		this.saveContact(this.contact());
	}

	saveContact(contact) {
		const data = contact.toJSON();
		if (data.jCard != JSON.stringify(contact.jCard)) {
			this.isSaving(true);
			Remote.request('ContactSave',
				(iError, oData) => {
					if (iError) {
						alert(oData?.ErrorMessage || getNotification(iError));
					} else if (oData.Result.ResultID) {
						if (contact.id()) {
							contact.id(oData.Result.ResultID);
							contact.jCard = JSON.parse(data.jCard);
						} else {
							this.reloadContactList(); // TODO: remove when e-contact-foreach is dynamic
						}
					}
					this.isSaving(false);
				}, data
			);
		}
	}
}
