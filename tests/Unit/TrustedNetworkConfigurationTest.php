<?php

namespace Tests\Unit;

use App\Support\TrustedNetworkConfiguration;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class TrustedNetworkConfigurationTest extends TestCase
{
    public function test_blank_proxy_configuration_disables_proxy_trust(): void
    {
        $this->assertNull(TrustedNetworkConfiguration::trustedProxies(null));
        $this->assertNull(TrustedNetworkConfiguration::trustedProxies(''));
        $this->assertNull(TrustedNetworkConfiguration::trustedProxies('   '));
    }

    public function test_wildcard_proxy_configuration_is_reduced_to_remote_addr(): void
    {
        $this->assertSame('REMOTE_ADDR', TrustedNetworkConfiguration::trustedProxies('*'));
        $this->assertSame('REMOTE_ADDR', TrustedNetworkConfiguration::trustedProxies('**'));
    }

    public function test_proxy_configuration_is_normalized_into_trimmed_list(): void
    {
        $this->assertSame(
            ['10.0.0.1', '10.0.0.2', 'REMOTE_ADDR'],
            TrustedNetworkConfiguration::trustedProxies(' 10.0.0.1 , 10.0.0.2 , REMOTE_ADDR ')
        );
    }

    public function test_forwarded_host_header_is_not_trusted(): void
    {
        $headers = TrustedNetworkConfiguration::trustedProxyHeaders();

        $this->assertSame(0, $headers & Request::HEADER_X_FORWARDED_HOST);
        $this->assertSame(0, $headers & Request::HEADER_X_FORWARDED_PREFIX);
        $this->assertNotSame(0, $headers & Request::HEADER_X_FORWARDED_FOR);
        $this->assertNotSame(0, $headers & Request::HEADER_X_FORWARDED_PROTO);
    }

    public function test_trusted_hosts_include_app_host_and_optional_ngrok_patterns(): void
    {
        $hosts = TrustedNetworkConfiguration::trustedHosts(
            'example.com, *.example.com',
            'https://medcapsule.example.com',
            true
        );

        $this->assertContains('example.com', $hosts);
        $this->assertContains('*.example.com', $hosts);
        $this->assertContains('medcapsule.example.com', $hosts);
        $this->assertContains('*.ngrok-free.app', $hosts);
    }

    public function test_host_patterns_escape_and_expand_wildcards(): void
    {
        $patterns = TrustedNetworkConfiguration::hostPatterns([
            'example.com',
            '*.example.com',
            '^api\.example\.com$',
        ]);

        $this->assertSame('^example\.com$', $patterns[0]);
        $this->assertSame('^.*\.example\.com$', $patterns[1]);
        $this->assertSame('^api\.example\.com$', $patterns[2]);
    }
}
