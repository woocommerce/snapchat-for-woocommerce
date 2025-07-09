/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppSpinner from '~/components/app-spinner';
import useEnableEnhancedConversions from '~/hooks/useEnableEnhancedConversions';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';

const TrackConversions = () => {
	const { isEnabled, hasFinishedResolution } = useEnableEnhancedConversions();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateEnhancedConversionsStatus } = useAppDispatch();

	const toggleEnhancedConversions = useCallback( async () => {
		await updateEnhancedConversionsStatus( ! isEnabled );
	}, [ updateEnhancedConversionsStatus, isEnabled ] );

	const handleOnChange = async () => {
		try {
			setIsSaving( true );
			await toggleEnhancedConversions();

			createNotice(
				'success',
				__(
					'Enhanced Conversions status updated successfully.',
					'snapchat-for-woo'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateEnhancedConversionsStatus` action.
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<AccountCard
			title={ __( 'Conversions API', 'snapchat-for-woo' ) }
			description={ __(
				'Send server-side conversion events to improve attribution.',
				'snapchat-for-woo'
			) }
			actions={
				<CheckboxControl
					label={ __(
						'Enable Conversions API tracking',
						'snapchat-for-woo'
					) }
					checked={ isEnabled }
					disabled={ isSaving }
					onChange={ handleOnChange }
				/>
			}
		/>
	);
};

export default TrackConversions;
