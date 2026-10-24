<?php
namespace App\Helpers;

use App\Config\App;

class Network
{
    private static ?string $cachedIp = null;

    /**
     * Get the active LAN/School network IPv4 address of this machine
     */
    public static function getServerLanIp(): ?string
    {
        if (self::$cachedIp !== null) {
            return self::$cachedIp;
        }

        $candidates = [];

        // 1. Resolve host by machine hostname
        $hostname = gethostname();
        if ($hostname) {
            $ips = @gethostbynamel($hostname);
            if (is_array($ips)) {
                $candidates = array_merge($candidates, $ips);
            }
            $single = @gethostbyname($hostname);
            if ($single) {
                $candidates[] = $single;
            }
        }

        // 2. Check server environment variable
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $candidates[] = $_SERVER['SERVER_ADDR'];
        }

        // 3. Filter candidates for valid private IPv4 LAN addresses (10.x.x.x, 192.168.x.x, 172.16-31.x.x)
        $candidates = array_unique(array_filter($candidates));
        foreach ($candidates as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            // Skip loopback (127.0.0.1) and APIPA auto-assigned (169.254.x.x)
            if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) {
                continue;
            }
            // Check private subnet ranges commonly used in school networks
            if (
                str_starts_with($ip, '10.') || 
                str_starts_with($ip, '192.168.') || 
                preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)
            ) {
                self::$cachedIp = $ip;
                return $ip;
            }
        }

        // 4. Any other non-loopback IPv4 address
        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_starts_with($ip, '127.') && !str_starts_with($ip, '169.254.')) {
                self::$cachedIp = $ip;
                return $ip;
            }
        }

        return null;
    }

    /**
     * Get the full school network URL for the current application
     */
    public static function getSchoolNetworkUrl(): ?string
    {
        $lanIp = self::getServerLanIp();
        if (!$lanIp) {
            return null;
        }

        $cleanScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($cleanScript));
        $base = rtrim($dir, '/');
        if ($base === '' || $base === '.' || $base === '/') {
            $base = '/' . rawurlencode(basename(dirname(__DIR__, 2)));
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $port = (int)($_SERVER['SERVER_PORT'] ?? 80);
        $portStr = ($port === 80 || $port === 443) ? '' : ":{$port}";

        return "{$scheme}://{$lanIp}{$portStr}{$base}";
    }

    /**
     * Enforce opening via School LAN IP address if connected to network
     */
    public static function enforceSchoolIpRedirect(): void
    {
        // Don't redirect during CLI runs, unit tests, or if noredirect query is specified
        if (PHP_SAPI === 'cli' || isset($_GET['noredirect'])) {
            return;
        }

        // Never redirect in production environment or if disabled in .env
        $isProd = (App::get('APP_ENV') === 'production');
        $enabled = filter_var(App::get('FORCE_SCHOOL_IP_REDIRECT', false), FILTER_VALIDATE_BOOLEAN);
        if ($isProd || !$enabled) {
            return;
        }

        $lanIp = self::getServerLanIp();
        if (!$lanIp) {
            return; // No active school LAN network found
        }

        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $hostOnly = explode(':', $httpHost)[0];
        $hostname = strtolower((string)gethostname());

        // Target hosts that should be redirected to the school network IP
        $redirectHosts = ['localhost', '127.0.0.1', '::1'];
        if ($hostname !== '') {
            $redirectHosts[] = $hostname;
        }

        if (in_array(strtolower($hostOnly), $redirectHosts, true)) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $port = (int)($_SERVER['SERVER_PORT'] ?? 80);
            $portStr = ($port === 80 || $port === 443) ? '' : ":{$port}";

            $targetUrl = "{$scheme}://{$lanIp}{$portStr}{$requestUri}";
            header("Location: {$targetUrl}", true, 302);
            exit;
        }
    }
}
