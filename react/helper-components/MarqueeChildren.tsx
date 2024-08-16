import {ReactNode, HTMLProps, useState, useEffect} from 'react';
import Animate  from "react-smooth"

const MarqueeChildren = ({children, speed = 2000, childrenCount = 5 , ...props} : {children: ReactNode, speed ?: number, childrenCount ?: number } & HTMLProps<HTMLDivElement>) => {
	// a simple marquee animation that runs infinitely with the given speed to 100% * childrenCount
	// const steps =

	const [reset, setReset] = useState(false);

	useEffect(() => {
		const interval = setInterval(() => {
			setReset((prev) => !prev); // Trigger reset to restart animation
			console.log('reset');
		}, speed * childrenCount);

	return () => clearInterval(interval);
	}, []);
	return (
			<Animate
				className={`${props.className}`}
				from={{ transform: 'translateX(0)' }}
				to={{ transform: `translateX(-${100 * 10}%)` }}
				duration={speed * childrenCount}
				easing="linear"
				key={reset}
			>
				{children}
			</Animate >
	);
};

export default MarqueeChildren;
