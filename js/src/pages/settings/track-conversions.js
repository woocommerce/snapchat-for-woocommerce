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
import useTrackConversions from '~/hooks/useTrackConversions';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';

const TrackConversions = () => {
	const { isEnabled, hasFinishedResolution } = useTrackConversions();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateTrackConversionsStatus } = useAppDispatch();

	const toggleTrackConversions = useCallback( async () => {
		await updateTrackConversionsStatus( ! isEnabled );
	}, [ updateTrackConversionsStatus, isEnabled ] );

	const handleOnChange = async () => {
		try {
			setIsSaving( true );
			await toggleTrackConversions();

			createNotice(
				'success',
				__(
					'Conversions API Tracking status updated successfully.',
					'snapchat-for-woo'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateTrackConversionsStatus` action.
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
