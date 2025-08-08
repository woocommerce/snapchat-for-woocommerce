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
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';
import './index.scss';

/**
 * ConversionsAPI component for managing the tracking setting.
 *
 * Renders a card UI allowing users to enable or disable server-side conversion event tracking.
 * Handles asynchronous state updates and displays success notifications upon status change.
 * Shows a loading spinner while the current tracking status is being resolved.
 *
 * @return {JSX.Element} The rendered ConversionsAPI settings card.
 */
const ConversionsAPI = () => {
	const { isCapiEnabled, hasFinishedResolution } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateSettings } = useAppDispatch();

	const toggleTrackConversions = useCallback( async () => {
		await updateSettings( { trackConversions: ! isCapiEnabled } );
	}, [ updateSettings, isCapiEnabled ] );

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
			// Silently fail because the error is handled within `updateSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<AccountCard
			className="sfw-settings-track-conversions"
			title={ __( 'Conversions API', 'snapchat-for-woo' ) }
			description={ __(
				'Send server-side conversion events to improve attribution.',
				'snapchat-for-woo'
			) }
			actions={
				<div className="sfw-settings-track-conversions__actions">
					<CheckboxControl
						label={ __(
							'Enable Conversions API tracking',
							'snapchat-for-woo'
						) }
						checked={ isCapiEnabled }
						disabled={ isSaving }
						onChange={ handleOnChange }
					/>
				</div>
			}
		/>
	);
};

export default ConversionsAPI;
