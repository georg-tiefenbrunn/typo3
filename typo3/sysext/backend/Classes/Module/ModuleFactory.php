<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Module;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal only to be used within TYPO3 Core
 */
class ModuleFactory
{
    public function __construct(
        protected readonly IconRegistry $iconRegistry,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createModule(string $identifier, array $configuration): ModuleInterface
    {
        $configuration = $this->eventDispatcher->dispatch(
            new BeforeModuleCreationEvent($identifier, $configuration)
        )->getConfiguration();

        $configuration = $this->sanitizeConfiguration($identifier, $configuration);

        if (is_array($configuration['controllerActions'] ?? false)) {
            return ExtbaseModule::createFromConfiguration($identifier, $configuration);
        }
        return Module::createFromConfiguration($identifier, $configuration);
    }

    private function sanitizeConfiguration(string $identifier, array $configuration): array
    {
        if (empty($configuration['path'])) {
            $configuration['path'] = '/module/' . trim(str_replace('_', '/', $identifier), '/');
        }

        if ($configuration['icon'] ?? false) {
            $iconPath = $configuration['icon'];
            if (!PathUtility::isExtensionPath($iconPath)) {
                $iconPath = GeneralUtility::getFileAbsFileName($iconPath);
            }
            if ($iconPath !== '') {
                $iconIdentifier = 'module-' . $identifier;
                $iconProvider = $this->iconRegistry->detectIconProvider($iconPath);
                $this->iconRegistry->registerIcon($iconIdentifier, $iconProvider, ['source' => $iconPath]);
                $configuration['iconIdentifier'] = $iconIdentifier;
            }
            unset($configuration['icon']);
        }
        return $configuration;
    }
}
