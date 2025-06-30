/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';

const GetStarted = () => {
	return (
		<Card className="sfw-get-started-card" isBorderless>
			<CardBody>
				<AppButton
					isPrimary
					href={ getOnboardingUrl() }
					eventName="sfw_setup_snapchat"
					eventProps={ {
						triggered_by: 'start-onboarding-button',
						action: 'go-to-onboarding',
						context: 'get-started',
					} }
				>
					{ __( 'Get Started', 'snapchat-for-woo' ) }
				</AppButton>
			</CardBody>
		</Card>
	);
};

export default GetStarted;
