import SetupTopBar from './setup-top-bar';
import { useLayout } from '~/hooks/useLayout';

export function Onboarding() {
	useLayout( 'full-page' );

	return (
		<>
			<SetupTopBar />
			<h2>This will be a Stepper component</h2>
		</>
	);
}
