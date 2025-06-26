/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import './confirm-create-modal.scss';

/**
 * Google Ads account creation confirmation modal.
 * This modal is shown when the user tries to create a new Google Ads account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onContinue Callback to continue with account creation.
 * @param {Function} props.onRequestClose Callback to close the modal.
 * @return {JSX.Element} Confirmation modal.
 */
const ConfirmCreateModal = ( { onContinue, onRequestClose } ) => {
	return (
		<AppModal
			className="sfw-ads-warning-modal"
			title={ __( 'Create Google Ads Account', 'snapchat-for-woo' ) }
			buttons={ [
				<AppButton key="confirm" isSecondary onClick={ onContinue }>
					{ __( 'Yes, I want a new account', 'snapchat-for-woo' ) }
				</AppButton>,
				<AppButton key="cancel" isPrimary onClick={ onRequestClose }>
					{ __( 'Cancel', 'snapchat-for-woo' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="sfw-ads-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Google Ads account?',
						'snapchat-for-woo'
					) }
				</span>
			</p>
			<p>
				{ __(
					'You already have another Ads account associated with this Google account.',
					'snapchat-for-woo'
				) }
			</p>
			<p>
				{ __(
					'If you create a new Google Ads account, you will need to accept an invite to the account before it can be used.',
					'snapchat-for-woo'
				) }
			</p>
		</AppModal>
	);
};

export default ConfirmCreateModal;
