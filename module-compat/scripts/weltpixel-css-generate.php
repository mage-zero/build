<?php
/**
 * WeltPixel CSS generation — standalone bootstrap.
 *
 * Equivalent to `bin/magento weltpixel:css:generate --store=<code>` but
 * bypasses the CLI command registration that fails in CI.
 *
 * Usage: php -d memory_limit=-1 .mz-build/module-compat/scripts/weltpixel-css-generate.php <store_code>
 */

$storeCode = $argv[1] ?? null;
if (!$storeCode) {
    echo "Usage: php weltpixel-css-generate.php <store_code>\n";
    exit(1);
}

require __DIR__ . '/../../../app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Set area code — WeltPixel needs adminhtml context.
$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
} catch (\Magento\Framework\Exception\LocalizedException $e) {
    // Already set.
}

$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
$scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$themeProvider = $objectManager->get(\Magento\Framework\View\Design\Theme\ThemeProviderInterface::class);
$utilityHelper = $objectManager->get(\WeltPixel\Backend\Helper\Utility::class);
$generateCss = $objectManager->get(\WeltPixel\Command\Model\GenerateCss::class);

try {
    $store = $storeManager->getStore($storeCode);
} catch (\Exception $e) {
    echo "WeltPixel CSS: store '$storeCode' not found — " . $e->getMessage() . "\n";
    exit(1);
}

$locale = $scopeConfig->getValue(
    \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE,
    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
    $store->getId()
);

$themeId = $scopeConfig->getValue(
    \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
    $store->getId()
);

$theme = $themeProvider->getThemeById($themeId);
$themePath = $theme->getThemePath();

echo "WeltPixel CSS for $storeCode (theme: $themePath, locale: $locale):\n";

if (!$utilityHelper->isPearlThemeUsed($storeCode)) {
    echo "  Skipped (not a Pearl theme).\n";
    exit(0);
}

try {
    $generateCss->processContent($themePath, $locale, $storeCode);
    echo "  OK\n";
} catch (\Exception $e) {
    echo "  ERROR — " . $e->getMessage() . "\n";
    exit(1);
}
