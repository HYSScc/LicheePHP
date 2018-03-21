<?php

namespace Lichee\Kernel;

/**
 * Interface KernelInterface
 * @package Lichee\Kernel
 */
interface KernelInterface
{
    /**
     * register bundles;
     * @return array
     */
    public function getBundles(): array;

    /**
     * Gets the name of the kernel.
     *
     * @return string The kernel name
     */
    public function getName(): string;

    public function getContainer(): Container;

    /**
     * Gets the environment.
     *
     * @return string The current environment
     */
    public function getEnvironment(): string;

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool true if debug mode is enabled, false otherwise
     */
    public function isDebug(): bool;

    /**
     * Gets the application root dir (path of the project's Kernel class).
     *
     * @return string The Kernel root dir
     */
    public function getRootDir(): string;

    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     */
    public function getCacheDir(): string;

    /**
     * Gets the log directory.
     *
     * @return string The log directory
     */
    public function getLogDir(): string;

    /**
     * Gets the config directory.
     *
     * @return string The log directory
     */
    public function getConfigDir(): string;

    /**
     * @return string
     */
    public function getCharset(): string;

    /**
     * @return string
     */
    public function getTimezone(): string;
}
