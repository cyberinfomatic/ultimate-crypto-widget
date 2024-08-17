import React from 'react';
import { ComponentType } from 'react';
import { createRoot, Root } from 'react-dom/client';
import { UCWPWidgetSetting } from "../types";

// Retrieve or initialize global data
declare global {
  interface Window {
    ucwpReactData: Record<string, any>;
    ucwpRenderedRoots: Record<string, Root>;
  }
}
const ucwpReactData = window.ucwpReactData || {};
console.log(ucwpReactData);

// Store references to rendered roots
window.ucwpRenderedRoots = {};

export default function ReactRender<PropType = any>(
  Children: ComponentType<PropType & { settings: UCWPWidgetSetting } >
) {
  // Get the DOM node based on the react_id from ucwpReactData
  const domNode = document.getElementById(ucwpReactData.react_id);
  // Check if a root already exists for this react_id, or create a new one
  let root = window.ucwpRenderedRoots[ucwpReactData.react_id] || null;
  if (domNode && !root) {
    root = createRoot(domNode);
    window.ucwpRenderedRoots[ucwpReactData.react_id] = root;
  }

  // Render the React component
  if (root) {
    const prop = { ...ucwpReactData as PropType &  React.JSX.IntrinsicAttributes & { settings: UCWPWidgetSetting; } };
    root.render(<Children {...prop} />);
    console.log('React component rendered');
  }
}
