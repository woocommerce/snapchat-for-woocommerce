/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import wooLogoURL from '~/images/logo/woocommerce.svg';
import snapchatLogoURL from '~/images/logo/snapchat-wide.svg';
import { getSettingsUrl } from '~/utils/urls';
import './index.scss';

/**
 * OnboardingSuccessModal component displays a modal dialog indicating successful setup of Snapchat for WooCommerce.
 *
 * The modal includes:
 * - Logos for WooCommerce and Snapchat.
 * - A success message and description.
 * - A close button that redirects the user to the settings page.
 *
 * @return {JSX.Element} The onboarding success modal UI.
 */
const OnboardingSuccessModal = () => {
	const handleCloseModal = () => {
		getHistory().replace( getSettingsUrl() );
	};

	return (
		<AppModal
			className="sfw-onboarding-success-modal"
			onRequestClose={ handleCloseModal }
			buttons={ [
				<AppButton
					key="close"
					variant="secondary"
					onClick={ handleCloseModal }
				>
					{ __( 'Close', 'snapchat-for-woocommerce' ) }
				</AppButton>,
			] }
		>
			<div className="sfw-onboarding-success-modal__logos">
				<Flex gap={ 6 } align="center" justify="center">
					<FlexItem>
						<img
							src={ wooLogoURL }
							alt={ __(
								'WooCommerce Logo',
								'snapchat-for-woocommerce'
							) }
							width="187.5"
						/>
					</FlexItem>
					<FlexItem className="sfw-onboarding-success-modal__logo-separator-line" />
					<FlexItem>
						<img
							src={ snapchatLogoURL }
							alt={ __(
								'Snapchat Logo',
								'snapchat-for-woocommerce'
							) }
							width="123"
						/>
					</FlexItem>
				</Flex>
			</div>

			<div className="sfw-onboarding-success-modal__content">
				<h2 className="sfw-onboarding-success-modal__title">
					{ __(
						'You’ve successfully set up Snapchat for WooCommerce! 🎉',
						'snapchat-for-woocommerce'
					) }
				</h2>
				<div className="sfw-onboarding-success-modal__description">
					{ __(
						'Your store is now connected to Snapchat. You can start running ads, track performance, and reach Snapchat users with your WooCommerce products.',
						'snapchat-for-woocommerce'
					) }
				</div>
			</div>
		</AppModal>
	);
};

export default OnboardingSuccessModal;
