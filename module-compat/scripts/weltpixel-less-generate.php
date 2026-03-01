<?php
/**
 * WeltPixel LESS generation — standalone bootstrap.
 *
 * Equivalent to `bin/magento weltpixel:less:generate` but bypasses the CLI
 * command registration that fails in CI (the compiled DI has the command
 * wiring, but Magento's console Application doesn't load it).
 *
 * Usage: php -d memory_limit=-1 .mz-build/module-compat/scripts/weltpixel-less-generate.php
 */

require __DIR__ . '/../../../app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Set area code — WeltPixel observers need adminhtml context.
$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
} catch (\Magento\Framework\Exception\LocalizedException $e) {
    // Already set.
}

// Load the command to get its DI-injected generationContainer.
$cmd = $objectManager->get(\WeltPixel\Command\Console\Command\GenerateLessCommand::class);
$container = $cmd->getGenerationContainer();

if (empty($container)) {
    echo "WeltPixel LESS: no generation observers registered.\n";
    exit(0);
}

echo "WeltPixel LESS generation:\n";
$observer = $objectManager->get(\Magento\Framework\Event\Observer::class);
$failed = 0;

foreach ($container as $key => $item) {
    try {
        $item->execute($observer);
        echo "  $key: OK\n";
    } catch (\Exception $e) {
        echo "  $key: ERROR — " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "Done.\n";
exit($failed > 0 ? 1 : 0);
