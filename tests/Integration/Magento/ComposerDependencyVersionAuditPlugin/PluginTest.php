<?php
/**
 * Copyright © 2021 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ComposerDependencyVersionAuditPlugin;

use Composer\Factory;
use Composer\IO\NullIO;
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
use Exception;

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
     * Initialize Dependencies
     */
    protected function setUp(): void
    {
        $this->eventMock = $this->getMockBuilder(PackageEvent::class)
            ->onlyMethods(['getOperation','getComposer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->packageMock = $this->getMockBuilder(PackageInterface::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->composerMock = $this->getMockBuilder(Composer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->installOperationMock = $this->getMockBuilder(InstallOperation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPackage'])
            ->getMockForAbstractClass();

        $this->io = new NullIO();
        $this->config = Factory::createConfig($this->io);
        $this->plugin = new Plugin();
        $this->repositoryManager =  new RepositoryManager($this->io, $this->config);
        parent::setUp();
    }

    /**
     * Test valid package install/update
     */
    public function testValidPackageUpdate(): void
    {
        var_dump(get_headers('https://repo.magento.com'));

        $repoUrl1 = [
            'url' => 'https://repo.magento.com/'
        ];

        $repoUrl2 = [
            'url' => 'https://repo.packagist.org/'
        ];

        $repository1 = new ComposerRepository($repoUrl1, $this->io, $this->config);
        $repository2 = new ComposerRepository($repoUrl2, $this->io, $this->config);

        $this->repositoryManager->addRepository($repository1);
        $this->repositoryManager->addRepository($repository2);

        $testPackage='magento/composer';

        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($this->repositoryManager);

        $this->eventMock->expects($this->once())
            ->method('getComposer')
            ->willReturn($this->composerMock);

        $this->eventMock->expects($this->once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->packageMock->expects($this->once())
            ->method('getName')
            ->willReturn($testPackage);

        $this->installOperationMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->plugin->packageUpdate($this->eventMock);
    }

    /**
     * Test Invalid Package install/update
     */
    public function testInvalidPackageDownload(): void
    {
        $testPackage = 'temando/packagist-test';

        $repoUrl1 = [
            'url' => 'https://repo.magento.com'
        ];

        $repoUrl2 = [
            'url' => 'https://repo.packagist.org/'
        ];

        $repository1 = new ComposerRepository($repoUrl1, $this->io, $this->config);
        $repository2 = new ComposerRepository($repoUrl2, $this->io, $this->config);

        $this->repositoryManager->addRepository($repository1);
        $this->repositoryManager->addRepository($repository2);

        $exceptionMessage = 'A higher version for this package was found in public packagist.org, which might need further investigation.';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($this->repositoryManager);

        $this->eventMock->expects($this->once())
            ->method('getComposer')
            ->willReturn($this->composerMock);

        $this->eventMock->expects($this->once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->packageMock->expects($this->once())
            ->method('getName')
            ->willReturn($testPackage);

        $this->installOperationMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->plugin->packageUpdate($this->eventMock);
    }
}