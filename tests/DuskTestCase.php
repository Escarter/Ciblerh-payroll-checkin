<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\Helpers\BrowserTestHelpers;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, BrowserTestHelpers;
    
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed essential data for Dusk tests
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            // Check if ChromeDriver is already running
            $ch = curl_init('http://localhost:9515/status');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            @curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // If ChromeDriver is not running, try to start it
            if ($httpCode !== 200) {
                // Try to use system ChromeDriver
                $chromedriverPath = trim((string) shell_exec('which chromedriver 2>/dev/null'));
                if ($chromedriverPath && file_exists($chromedriverPath)) {
                    // Set environment variable for Dusk to use system ChromeDriver
                    putenv('CHROMEDRIVER_PATH=' . $chromedriverPath);
                }
                
                // Try to start ChromeDriver, but don't fail if it's already running
                try {
                    static::startChromeDriver(['--port=9515']);
                } catch (\Exception $e) {
                    // If ChromeDriver is already running or can't start, continue
                    // ChromeDriver might be started manually
                    if (!str_contains($e->getMessage(), 'already running') && 
                        !str_contains($e->getMessage(), 'Invalid path')) {
                        // Only throw if it's a real error
                        // For "Invalid path" errors, assume ChromeDriver is running manually
                    }
                }
            }
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
