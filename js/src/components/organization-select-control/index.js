/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import { SNAPCHAT_ORGANIZATION_STATUS } from '~/constants';
import useExistingSnapchatOrganizations from '~/hooks/useExistingSnapchatOrganizations';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const OrganizationSelectControl = ( props ) => {
	const { existingSnapchatOrganizations } =
		useExistingSnapchatOrganizations();
	const {
		id: connectedOrganizationId,
		name: connectedOrganizationName,
		status,
	} = useSnapchatOrganization();

	const accountIdExists = existingSnapchatOrganizations?.some(
		( existingAccount ) => existingAccount.id === connectedOrganizationId
	);

	// If the account ID is not in the list of existing accounts, fake the select options by displaying the connected account ID only.
	if (
		! accountIdExists &&
		status === SNAPCHAT_ORGANIZATION_STATUS.CONNECTED
	) {
		return (
			<AppSelectControl
				autoSelectFirstOption
				nonInteractive
				value={ connectedOrganizationId }
				options={ [
					{
						value: connectedOrganizationId,
						label: sprintf(
							// translators: 1: account domain, 2: account ID.
							__( '(%1$s) (%2$s)', 'snapchat-for-woo' ),
							connectedOrganizationName,
							connectedOrganizationId
						),
					},
				] }
			/>
		);
	}

	const options = existingSnapchatOrganizations?.map( ( acc ) => ( {
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

export default OrganizationSelectControl;
