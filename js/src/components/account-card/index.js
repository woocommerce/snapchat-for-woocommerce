/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import snapchatLogoURL from '~/images/logo/snapchat.svg';
import wpLogoURL from '~/images/logo/wp.svg';
import './index.scss';

/**
 * Enum of account card appearances.
 *
 * @enum {string}
 */
export const APPEARANCE = {
	EMPTY: 'empty',
	WPCOM: 'wpcom',
	SNAPCHAT: 'snapchat',
};

const snapchatLogo = (
	<img
		src={ snapchatLogoURL }
		alt={ __( 'Snapchat Logo', 'snapchat-for-woocommerce' ) }
		width="40"
		height="40"
	/>
);

const wpLogo = (
	<img
		src={ wpLogoURL }
		alt={ __( 'WordPress.com Logo', 'snapchat-for-woocommerce' ) }
		width="40"
		height="40"
	/>
);

const appearanceDict = {
	[ APPEARANCE.EMPTY ]: {},
	[ APPEARANCE.WPCOM ]: {
		icon: wpLogo,
		title: 'WordPress.com',
	},
	[ APPEARANCE.SNAPCHAT ]: {
		icon: snapchatLogo,
		title: __( 'Snapchat', 'snapchat-for-woocommerce' ),
	},
};

// The `center` is the default alignment, and no need to append any additional class name.
const alignStyleName = {
	center: false,
	top: 'sfw-account-card__styled--align-top',
};

const indicatorAlignStyleName = {
	...alignStyleName,
	toDetail: 'sfw-account-card__indicator--align-to-detail',
};

/**
 * Renders a Card component with account info and status.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 * @param {APPEARANCE} [props.appearance=APPEARANCE.EMPTY]
 *   Kind of account to indicate the default card appearance, which could include icon, title, and description.
 *   If didn't specify this prop, all properties of appearance will be `undefined` (nothing shown),
 *   and it's usually used for full customization.
 * @param {JSX.Element} [props.icon] Card icon. It will fall back to the icon of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.title] Card title. It will fall back to the title of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.description] Description content below the card title. It will fall back to the description of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.helper] Helper content below the card description.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {'center'|'top'} [props.alignIcon='center'] Specify the vertical alignment of leading icon.
 * @param {'center'|'top'} [props.alignIndicator='center'] Specify the vertical alignment of `indicator`.
 * @param {JSX.Element} [props.detail] Detail content below the card description.
 * @param {boolean} [props.expandedDetail=false] Whether to expand the detail content.
 * @param {JSX.Element} [props.actions] Actions content below the card detail.
 * @param {Array<JSX.Element>} [props.children] Children to be rendered if needs more content within the card.
 * @param {Object} [props.restProps] Props to be forwarded to Section.Card.
 */
export default function AccountCard( {
	className,
	disabled = false,
	appearance = APPEARANCE.EMPTY,
	icon = appearanceDict[ appearance ].icon,
	title = appearanceDict[ appearance ].title,
	description = appearanceDict[ appearance ].description,
	helper,
	alignIcon = 'center',
	indicator,
	alignIndicator = 'center',
	detail,
	expandedDetail = false,
	actions,
	children,
	...restProps
} ) {
	const cardClassName = classnames(
		'sfw-account-card',
		disabled ? 'sfw-account-card--is-disabled' : false,
		expandedDetail ? 'sfw-account-card--is-expanded-detail' : false,
		className
	);

	const iconClassName = classnames(
		'sfw-account-card__icon',
		alignStyleName[ alignIcon ]
	);

	const indicatorClassName = classnames(
		'sfw-account-card__indicator',
		indicatorAlignStyleName[ alignIndicator ]
	);

	return (
		<Section.Card className={ cardClassName } { ...restProps }>
			<Section.Card.Body>
				<div className="sfw-account-card__body-layout">
					{ icon && <div className={ iconClassName }>{ icon }</div> }
					<div className="sfw-account-card__subject">
						{ title && (
							<Subsection.Title className="sfw-account-card__title">
								{ title }
							</Subsection.Title>
						) }
						{ description && (
							<div className="sfw-account-card__description">
								{ description }
							</div>
						) }
						{ helper && (
							<div className="sfw-account-card__helper">
								{ helper }
							</div>
						) }
					</div>
					{ detail && (
						<div className="sfw-account-card__detail">
							{ detail }
						</div>
					) }
					{ indicator && (
						<div className={ indicatorClassName }>
							{ indicator }
						</div>
					) }
					{ actions && (
						<div className="sfw-account-card__actions">
							{ actions }
						</div>
					) }
				</div>
			</Section.Card.Body>
			{ children }
		</Section.Card>
	);
}
