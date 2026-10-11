/** Joins class names, skipping the ones that are switched off. */
export const cx = (...names: Array<string | false | null | undefined>): string => names.filter(Boolean).join(" ");
