// The link students scan to get a ticket, built from the address this page was opened with.
// On this PC the site is also reachable as queue-system.test or localhost, but phones can't
// open those, so a QR code is only useful when the page was opened via the network address.
export function queueUrl() {
    const { origin, hostname } = window.location;
    const phonesCanReach = !(
        hostname === 'localhost' ||
        hostname === '127.0.0.1' ||
        hostname.endsWith('.test')
    );

    return { url: `${origin}/queue`, phonesCanReach };
}
