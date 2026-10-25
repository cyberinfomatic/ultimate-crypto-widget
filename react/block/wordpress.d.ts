/**
 * The parts of the block editor's packages this block uses.
 *
 * WordPress provides these packages on the page (the build leaves them out), so
 * they are not installed here; these declarations cover only what we call.
 */

declare module "@wordpress/blocks" {
	import { ComponentType } from "react";

	export function registerBlockType(
		name: string,
		settings: {
			// The attributes' shape is the block's own; the editor passes them through.
			edit: ComponentType<any>; // eslint-disable-line @typescript-eslint/no-explicit-any -- see above
			save: () => null;
			variations?: Array<{
				name: string;
				title: string;
				icon?: string;
				keywords?: string[];
				attributes?: Record<string, unknown>;
				scope?: Array<"inserter" | "block" | "transform">;
				isActive?: (blockAttributes: any, variationAttributes: any) => boolean; // eslint-disable-line @typescript-eslint/no-explicit-any -- the block's own attributes
			}>;
		}
	): unknown;
}

declare module "@wordpress/block-editor" {
	import { ComponentType, ReactNode } from "react";

	export const InspectorControls: ComponentType<{ children?: ReactNode }>;
	export function useBlockProps(props?: Record<string, unknown>): Record<string, unknown>;
}

declare module "@wordpress/components" {
	import { ComponentType, ReactNode } from "react";

	type Option = { value: string; label: string; disabled?: boolean };

	type Common = {
		label?: string;
		help?: string;
		__nextHasNoMarginBottom?: boolean;
	};

	export const PanelBody: ComponentType<{ title?: string; initialOpen?: boolean; children?: ReactNode }>;
	export const Placeholder: ComponentType<{ icon?: string; label?: string; instructions?: string; children?: ReactNode }>;

	type SelectProps =
		| (Common & { multiple?: false; value: string; options: Option[]; onChange: (value: string) => void })
		| (Common & { multiple: true; value: string[]; options: Option[]; onChange: (value: string[]) => void });
	export const SelectControl: ComponentType<SelectProps>;

	export const FormTokenField: ComponentType<{
		label?: string;
		value: string[];
		suggestions?: string[];
		maxLength?: number;
		onChange: (tokens: Array<string | { value: string }>) => void;
		__nextHasNoMarginBottom?: boolean;
		__next40pxDefaultSize?: boolean;
		__experimentalExpandOnFocus?: boolean;
		__experimentalShowHowTo?: boolean;
		__experimentalValidateInput?: (token: string) => boolean;
	}>;

	export const TextControl: ComponentType<
		Common & {
			type?: "text" | "number";
			value: string;
			min?: number;
			max?: number;
			step?: number;
			onChange: (value: string) => void;
		}
	>;
}
