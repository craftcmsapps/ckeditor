<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\ckeditor\web\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * Base asset bundle class for DLL-compatible CKEditor packages.
 *
 * Child classes can define a list of CKEditor plugin names and toolbar buttons provided by the package.
 *
 * If translation files are located in `translations/` relative to the source path, the appropriate translation file
 * will automatically be published alongside the JavaScript files defined by [[js]].
 *
 * @since 3.4.0
 */
abstract class BaseCkeditorPackageAsset extends AssetBundle
{
    /**
     * @var string[] List of CKEditor plugins’ names that should be loaded by default.
     *
     * Plugins should be defined in the global `window.CKEditor5` object.
     *
     * ```js
     * window.CKEditor5.placeholder = {
     *   Placeholder: MyPlaceholderPlugin,
     * };
     * ```
     *
     * The plugin names listed here should match the plugins’ `pluginName` getter values.
     */
    public array $pluginNames = [];

    /**
     * @var array<string|string[]> List of toolbar items that should be available to CKEditor toolbars.
     *
     * Each item can be represented in one of these ways:
     *
     * - The button name, as registered via `editor.ui.componentFactory.add()` (e.g. `'bold'`).
     * - An array of button names, if they should always be included together as a group
     *       (e.g. `['outdent', 'indent']`).
     *
     * If this list isn’t empty, the plugins referenced by [[pluginNames]] will only be included for editors where at
     * least one of the associated toolbar items is selected.
     */
    public array $toolbarItems = [];

    public function registerAssetFiles($view): void
    {
        $this->includeTranslation();
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->registerPackage($view);
        }
    }

    private function includeTranslation(): void
    {
        $language = match (Craft::$app->language) {
            'en', 'en-US' => false,
            'nn' => 'no',
            default => strtolower(Craft::$app->language),
        };

        if ($language === false) {
            return;
        }

        if ($this->includeTranslationForLanguage($language)) {
            return;
        }

        // maybe without the territory?
        $dashPos = strpos($language, '-');
        if ($dashPos !== false) {
            $this->includeTranslationForLanguage(substr($language, 0, $dashPos));
        }
    }

    private function includeTranslationForLanguage($language): bool
    {
        $subpath = "translations/$language.js";
        $path = "$this->sourcePath/$subpath";
        if (!file_exists($path)) {
            return false;
        }
        $this->js[] = $subpath;
        return true;
    }

    private function registerPackage(View $view): void
    {
        if (!empty($this->pluginNames) || !empty($this->toolbarItems)) {
            $view->registerJsWithVars(fn($package) => "CKEditor5.craftcms.registerPackage($package);", [
                [
                    'pluginNames' => $this->pluginNames,
                    'toolbarItems' => $this->toolbarItems,
                ],
            ], View::POS_END);
        }
    }
}
