<?php

namespace YandexCheckout;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/autoload.php';

class AutoloadTest extends TestCase
{
    public function testAutoload()
    {
        $functions = spl_autoload_functions();
        self::assertArrayHasKey(1, $functions);
        self::assertEquals('yandexCheckoutLoadClass', $functions[1]);

        self::assertTrue(defined('YANDEX_CHECKOUT_SDK_ROOT_PATH'));
        self::assertTrue(defined('YANDEX_CHECKOUT_PSR_LOG_PATH'));

        $this->walkDirectoriesAndTest(YANDEX_CHECKOUT_SDK_ROOT_PATH, 'YandexCheckout');
        if (version_compare('5.4', PHP_VERSION, '<')) {
            $this->walkDirectoriesAndTest(YANDEX_CHECKOUT_PSR_LOG_PATH, 'Psr\Log');
        }

        spl_autoload_unregister($functions[1]);
        spl_autoload_register($functions[0]);
    }

    private function walkDirectoriesAndTest($directoryName, $namespace)
    {
        $dir = opendir($directoryName);
        while (($entry = readdir($dir)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directoryName . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($entry)) {
                $this->walkDirectoriesAndTest($path, $namespace . '\\' . $entry);
            } else {
                $extension = pathinfo($entry, PATHINFO_EXTENSION);
                if ($extension === 'php' && strtoupper($entry[0]) === $entry[0]) {
                    $className = $namespace . '\\' . pathinfo($entry, PATHINFO_FILENAME);
                    if (!$this->classExists($className)) {
                        yandexCheckoutLoadClass($className);
                        self::assertTrue($this->classExists($className), 'Class "' . $className . '" not exists');
                    }
                }
            }
        }
        closedir($dir);
    }

    private function classExists($className)
    {
        return class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false);
    }
}