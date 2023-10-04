<?php
use RainLoop\Providers\AddressBook\Classes\Contact;

class NextcloudAddressBook implements \RainLoop\Providers\AddressBook\AddressBookInterface
{
	use RainLoop\Providers\AddressBook\CardDAV;
	private const URI = 'webmail';
	private $contactsManager;
	private $key;

	function __construct()
	{
		$this->contactsManager = \OC::$server->getContactsManager();

		foreach($this->contactsManager->getUserAddressBooks() as $addressBook) {
			if($addressBook->isSystemAddressBook()) {
				 $this->contactsManager->unregisterAddressBook($addressBook);
			}
		}
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$cardDavBackend = \OC::$server->get(\OCA\DAV\CardDAV\CardDavBackend::class);
		$principalUri = 'principals/users/'. $uid;
		$addressBookId = $cardDavBackend->getAddressBooksByUri($principalUri, self::URI);
		if ($addressBookId === null) {
			$addressBookId = $cardDavBackend->createAddressBook($principalUri, self::URI, array_filter([
                '{DAV:}displayname' => 'Webmail',
                '{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'Recipients from snappymail',
            ]));
		} else {
			$addressBookId = $addressBookId['id'];
		}
		$this->key = $addressBookId;
	}

	public function IsSupported() : bool {
		// Maybe just return true, contacts app is just a frontend
		//return \OC::$server->getAppManager()->isEnabledForUser('contacts');
		return true;
	}

	public function IsExternal(): bool
	{
		return true;
	}

	public function SetEmail(string $sEmail) : bool {
		return true;
	}

	public function Sync() : bool {
		return false;
	}

	public function Export(string $sType = 'vcf') : bool {
		return false;
	}

	public function ContactSave(Contact $oContact) : bool {
		return false;
	}

	public function DeleteContacts(array $aContactIds) : bool {
		return false;
	}

	public function DeleteAllContacts(string $sEmail) : bool {
		return false;
	}

	public function GetContacts(int $iOffset = 0, int $iLimit = 20, string $sSearch = '', int &$iResultCount = 0) : array {
		return [];
	}

	public function GetContactByEmail(string $sEmail) : ?Contact {
		return null;
	}

	public function GetContactByID($mID, bool $bIsStrID = false) : ?Contact {
		return null;
	}

	public function GetSuggestions(string $sSearch, int $iLimit = 20) : array {
		return [];
	}

	private function GetEmailObjects(array $aEmails) : array {
		$aEmailsObjects = \array_map(function ($mItem) {
			$oResult = null;
			try {
				$oResult = \MailSo\Mime\Email::Parse(\trim($mItem));
			}
			catch (\Throwable $oException) {
				unset($oException);
			}
			return $oResult;
		}, $aEmails);

		$aEmailsObjects = \array_filter($aEmailsObjects, function ($oItem) {
			return !!$oItem;
		});
		return $aEmailsObjects;
	}

	/**
	 * Add/increment email address usage
	 * Handy for "most used" sorting suggestions in PdoAddressBook
	 */
	public function IncFrec(array $aEmails, bool $bCreateAuto = true) : bool {
		if ($bCreateAuto) {
			$aEmailsObjects = $this->GetEmailObjects($aEmails);

			if (!count($aEmailsObjects)) {
				return false;
			}
			foreach ($aEmailsObjects as $oEmail) {		
				$properties = [];
				if ('' === \trim($oEmail->GetEmail())) {
					continue;
				}
				$sEmail = \trim($oEmail->GetEmail(true));
				$existingResults = $this->contactsManager->search($sEmail, ['EMAIL'], ['strict_search' => true]);
				if (!empty($existingResults)) {
					$properties['URI'] = $existingResults[0]['UID'] . '.vcf';
				}
				$properties['EMAIL'] = \trim($sEmail);
				if ('' !== \trim($oEmail->GetDisplayName())) {
					$properties['FN'] = $oEmail->GetDisplayName();
				} else {
					$properties['FN'] = \trim($sEmail);
				}
				$this->contactsManager->createOrUpdate($properties, $this->key);
			}
			return true;
		}
		return false;
	}

	public function Test() : string {
		return '';
	}


}
