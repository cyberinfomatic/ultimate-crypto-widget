// import { __ } from '@wordpress/i18n';
import ReactRender from "../helper-components/react-wrapper";
import '@/styles/sass/admin.scss';
import React from 'react';


ReactRender(({...props}) => {
	const helpfulLinks = [
		{
			name: "Documentation",
			link: "/ultimate-crypto-widget",
		},
		{
			name: "Support",
			link: "https://wordpress.org/support/plugin/ultimate-crypto-widget/",
		},
		{
			name: "Rate Us",
			link: "https://wordpress.org/support/plugin/ultimate-crypto-widget/reviews/",
		},
	]
	// add go pro url to the first of the array if props.pro is false
	if(!props.pro){
		helpfulLinks.unshift({
			name: "Go Pro",
			link: props.pro_url
		})
	}

	return (
		<div className="wrap">
			{/* <h1> { __( 'Ultimate Crypto Widget', 'ultimate-crypto-widget' ) } </h1> */}
			<div className={"ucwp-admin-display-main"}>
				<div className={`ucwp-admin-welcome`}>
					<div className={`ucwp-admin-logo`}>
						<img src={props.png_logo} alt="logo" width="200" height="200"/>
					</div>
					<div className={`ucwp-admin-welcome-message`} >
						{/* <p> { __( 'Welcome to the Ultimate Crypto Widget plugin.', 'ultimate-crypto-widget' ) } </p> */}
						{/*short description about the plugin*/}
						{/* <p> { __( 'This plugin allows you to display the current price of any cryptocurrency in your website.', 'ultimate-crypto-widget' ) } </p> */}
						{/* helpful links */}
						<div className={`ucwp-admin-helpful-links`}>
							{helpfulLinks.map((link, index) => (
								<a key={index} href={link.link} target="_blank" rel="noreferrer">{link.name}</a>
							))}
						</div>
					</div>
				</div>
			</div>
		</div>
	);
})
