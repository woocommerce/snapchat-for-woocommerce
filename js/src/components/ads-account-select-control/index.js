/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import useExistingSnapchatAdsAccounts from '~/hooks/useExistingSnapchatAdsAccounts';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( props ) => {
	const { data: existingAccounts } = useExistingSnapchatAdsAccounts();
	const { snapchatAdsAccount, isReady } = useSnapchatAdsAccount();

	const accountIdExists = existingAccounts?.some(
		( existingAccount ) => existingAccount.id === snapchatAdsAccount?.id
	);

	// If the account ID is not in the list of existing accounts, fake the select options by displaying the connected account ID only.
	if ( ! accountIdExists && isReady ) {
		return (
			<AppSelectControl
				autoSelectFirstOption
				nonInteractive
				value={ snapchatAdsAccount.id }
				options={ [
					{
						value: snapchatAdsAccount.id,
						label: sprintf(
							// translators: 1: account domain, 2: account ID.
							__( '(%1$s) (%2$s)', 'snapchat-for-woo' ),
							snapchatAdsAccount.name,
							snapchatAdsAccount.id
						),
					},
				] }
			/>
		);
	}

	const options = existingAccounts?.map( ( acc ) => ( {
		value: acc.id,
		label: `(${ acc.name }) ${ acc.id }`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
