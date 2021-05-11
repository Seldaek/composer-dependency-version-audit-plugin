<?php
/**
 * Copyright © 2021 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ComposerDependencyVersionAuditPlugin;

use Composer\Factory;
use Composer\IO\NullIO;
use Magento\ComposerDependencyVersionAuditPlugin\Utils\Version;
use PHPUnit\Framework\TestCase;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer\PackageEvent;
use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\ComposerRepository;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Composer\Config;
use PHPUnit\Framework\MockObject\MockObject;
use Composer\DependencyResolver\Pool;

/**
 * Test for Class Magento\ComposerDependencyVersionAuditPlugin\Plugin
 */
class PluginTest extends TestCase
{

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var MockObject
     */
    private $eventMock;

    /**
     * @var MockObject
     */
    private $composerMock;
    /**
     * @var MockObject
     */
    private $packageMock;

    /**
     * @var MockObject
     */
    private $installOperationMock;

    /**
     * @var MockObject
     */
    private $poolMock;

    /**
     * @var MockObject
     */
    private $versionSelectorMock;

    /**
     * @var MockObject
     */
    private $repositoryMock1;

    /**
     * @var MockObject
     */
    private $repositoryMock2;


    /**#@+
     * Package name constant for test
     */
    const PACKAGE_NAME = 'foo';

    /**
     * Initialize Dependencies
     */
    protected function setUp(): void
    {
        $this->io = new NullIO();
        $this->config = Factory::createConfig($this->io);
        $this->repositoryManager = new RepositoryManager($this->io, $this->config);

        $this->eventMock = $this->getMockBuilder(PackageEvent::class)
            ->onlyMethods(['getOperation', 'getComposer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->packageMock = $this->getMockBuilder(PackageInterface::class)
            ->onlyMethods(['getName', 'getFullPrettyVersion'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->composerMock = $this->getMockBuilder(Composer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPackage', 'getRepositoryManager'])
            ->getMock();

        $this->installOperationMock = $this->getMockBuilder(InstallOperation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPackage'])
            ->getMock();

        $this->poolMock = $this->getMockBuilder(Pool::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addRepository'])
            ->getMock();

        $this->versionSelectorMock = $this->getMockBuilder(Version::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBestCandidate'])
            ->getMock();

        $this->repositoryMock1 = $this->getMockBuilder(ComposerRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findPackage', 'addPackage', 'getRepoConfig'])
            ->getMock();

        $this->repositoryMock2 = $this->getMockBuilder(ComposerRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findPackage', 'addPackage', 'getRepoConfig'])
            ->getMock();

        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($this->repositoryManager);

        $this->eventMock->expects($this->once())
            ->method('getComposer')
            ->willReturn($this->composerMock);

        $this->eventMock->expects($this->once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->installOperationMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects($this->once())
            ->method('getName')
            ->willReturn(self::PACKAGE_NAME);

        $this->plugin = new Plugin($this->versionSelectorMock);
        $this->repositoryManager->addRepository($this->repositoryMock1);
        $this->repositoryManager->addRepository($this->repositoryMock2);
        parent::setUp();
    }

    /**
     * Test valid package install/update
     */
    public function testValidPackageUpdate(): void
    {
        $this->repositoryMock1->expects($this->once())
            ->method('getRepoConfig')
            ->willReturn(['url' => 'https://repo.packagist.org']);

        $this->repositoryMock2->expects($this->once())
            ->method('getRepoConfig')
            ->willReturn(['url' => 'https://someprivaterepo.org']);

        $this->versionSelectorMock->expects($this->exactly(2))
            ->method('findBestCandidate')
            ->willReturn($this->packageMock);

        $this->packageMock->expects($this->exactly(2))
            ->method('getFullPrettyVersion')
            ->willReturnOnConsecutiveCalls('1.0.1', '1.0.10');

        $this->assertNull($this->plugin->packageUpdate($this->eventMock));
    }

    /**
     * Test invalid package install/update
     */
    public function testInvalidPackageUpdate(): void
    {
        $exceptionMessage = 'A higher version for %s was found in public packagist.org, which might need further investigation.';

        $this->repositoryMock1->expects($this->once())
            ->method('getRepoConfig')
            ->willReturn(['url' => 'https://repo.packagist.org']);

        $this->repositoryMock2->expects($this->once())
            ->method('getRepoConfig')
            ->willReturn(['url' => 'https://example.org']);

        $this->versionSelectorMock->expects($this->exactly(2))
            ->method('findBestCandidate')
            ->willReturn($this->packageMock);

        $this->packageMock->expects($this->exactly(2))
            ->method('getFullPrettyVersion')
            ->willReturnOnConsecutiveCalls('1.0.5', '1.0.1');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf($exceptionMessage, self::PACKAGE_NAME));
        $this->plugin->packageUpdate($this->eventMock);
    }

}