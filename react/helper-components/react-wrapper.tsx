import {ComponentType} from 'react';
import { createRoot } from 'react-dom/client';

// @ts-ignore
const ucwpReactData = window.ucwpReactData || {};
console.log(ucwpReactData);
export default function ReactRender(Children : ComponentType<any>) {
	const domNode = document.getElementById(ucwpReactData.react_id);
	if (domNode) {
		createRoot(domNode).render(<Children {...ucwpReactData} />);
	}
}
