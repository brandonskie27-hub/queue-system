// Addresses that only work on the PC running the app (Herd's .test site, localhost).
// Phones can't open these, so they get special handling.
export function isLocalOnlyHost(hostname = window.location.hostname) {
    return (
        hostname === 'localhost' ||
        hostname === '127.0.0.1' ||
        hostname.endsWith('.test')
    );
}

// The link students scan to get a ticket, built from the address this page was opened with.
// A QR code is only useful when the page was opened via the network address.
export function queueUrl() {
    return {
        url: `${window.location.origin}/queue`,
        phonesCanReach: !isLocalOnlyHost(),
    };
}
