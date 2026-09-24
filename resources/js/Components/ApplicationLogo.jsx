import { branding } from '@/branding';

export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            {...props}
            src={branding.logoUrl}
            alt={`${branding.schoolName} logo`}
            className={'object-contain ' + className}
        />
    );
}
