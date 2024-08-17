import { ArrowDown, ArrowUp } from "iconsax-react";
import React, { CSSProperties, HTMLAttributes } from "react";

/**
 * Props for the PricePercentage component.
 *
 * @interface PricePercentageProps
 * @extends {HTMLAttributes<HTMLDivElement>}
 * @property {number} percentage - The percentage value to display.
 * @property {number | false} [arrowSize=15] - The size of the arrow icon. If set to false, the arrow will be hidden.
 * @property {CSSProperties} [styles] - Additional styles to apply to the component.
 */
interface PricePercentageProps extends HTMLAttributes<HTMLDivElement> {
  percentage: number;
  arrowSize?: number | false;
  styles?: CSSProperties;
}

/**
 * A component that displays a percentage value with an optional up or down arrow icon.
 * The arrow icon indicates whether the percentage is positive or negative.
 * 
 * common properties
 * @param {number} percentage - The percentage value to display.
 * @param {number | false} [arrowSize=15] - The size of the arrow icon. If set to false, the arrow will be hidden.
 *
 * @param {PricePercentageProps} props - The props for the component.
 * @returns {JSX.Element} The rendered component.
 */
const PricePercentage: React.FC<PricePercentageProps> = ({
  percentage,
  arrowSize = 15,
  styles,
  ...props
}) => {
  const isPositive = percentage > 0;
  return (
    <div {...props} style={{ color: isPositive ? "green" : "red", ...styles }}>
      {arrowSize !== false &&
        (isPositive ? (
          <ArrowUp size={arrowSize} color="green" />
        ) : (
          <ArrowDown size={arrowSize} color="red" />
        ))}
      {percentage}
    </div>
  );
};

export default PricePercentage;
