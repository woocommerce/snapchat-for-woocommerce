/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import ConnectExistingAccount from './connect-existing-account';
import ConnectingAccount from './connecting-account';

/**
 * ConnectOrganization component renders an account card to connect to an existing Snapchat Organization account.
 *
 * @param {Object} props Component props.
 * @param {string|null} props.upsertingAction The action the user is performing. Possible values are 'update', or null.
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectOrganization = ( { upsertingAction } ) => {
	if ( upsertingAction ) {
		return <ConnectingAccount />;
	}

	return <ConnectExistingAccount />;
};

export default ConnectOrganization;
