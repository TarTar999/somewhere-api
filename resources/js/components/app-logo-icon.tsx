import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    const { className, ...rest } = props;
    return (
        <img
            src="/images/icon.png"
            alt="SW"
            className={className}
            {...rest}
        />
    );
}
