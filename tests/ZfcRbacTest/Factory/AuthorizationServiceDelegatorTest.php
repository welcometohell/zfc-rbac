<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */
namespace ZfcRbacTest\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Factory\AuthorizationServiceDelegatorFactory;
use ZfcRbacTest\Initializer\AuthorizationAwareFake;
use ZfcRbacTest\Util\ServiceManagerFactory;

/**
 * @covers  \ZfcRbac\Factory\AuthorizationServiceDelegatorFactory
 * @author  Jean-Marie Leroux <jmleroux.pro@gmail.com>
 * @license MIT License
 */
class AuthorizationServiceDelegatorTest extends \PHPUnit_Framework_TestCase
{
    public function testDelegatorFactory()
    {
        $authServiceClassName = 'ZfcRbac\Service\AuthorizationService';
        $delegator            = new AuthorizationServiceDelegatorFactory();
        $serviceLocator       = $this->prophesize(ServiceLocatorInterface::class);
        $serviceLocator->willImplement(ContainerInterface::class);

        $authorizationService = $this->getMock('ZfcRbac\Service\AuthorizationService', [], [], '', false);

        $callback = function () {
            return new AuthorizationAwareFake();
        };

        $serviceLocator->get($authServiceClassName)->willReturn($authorizationService)->shouldBeCalled();

        $decoratedInstance = $delegator->createDelegatorWithName($serviceLocator->reveal(), 'name', 'requestedName', $callback);

        $this->assertEquals($authorizationService, $decoratedInstance->getAuthorizationService());
    }

    public function testAuthorizationServiceIsNotInjectedWithoutDelegator()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $serviceManager->setAllowOverride(true);
        $authorizationService = $this->getMock('ZfcRbac\Service\AuthorizationService', [], [], '', false);
        $serviceManager->setService(
            'ZfcRbac\Service\AuthorizationService',
            $authorizationService
        );
        $serviceManager->setAllowOverride(false);

        $serviceManager->setInvokableClass(
            'ZfcRbacTest\AuthorizationAware',
            'ZfcRbacTest\Initializer\AuthorizationAwareFake'
        );
        $decoratedInstance = $serviceManager->get('ZfcRbacTest\AuthorizationAware');
        $this->assertNull($decoratedInstance->getAuthorizationService());
    }

    public function testAuthorizationServiceIsInjectedWithDelegator()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        if (method_exists($serviceManager, 'build')) {
            $this->markTestSkipped('this test is only vor zend-servicemanager v2');
        }

        $serviceManager->setAllowOverride(true);
        $authorizationService = $this->getMock('ZfcRbac\Service\AuthorizationService', [], [], '', false);
        $serviceManager->setService(
            'ZfcRbac\Service\AuthorizationService',
            $authorizationService
        );
        $serviceManager->setAllowOverride(false);

        $serviceManager->setInvokableClass(
            'ZfcRbacTest\AuthorizationAware',
            'ZfcRbacTest\Initializer\AuthorizationAwareFake'
        );

        $serviceManager->addDelegator(
            'ZfcRbacTest\AuthorizationAware',
            'ZfcRbac\Factory\AuthorizationServiceDelegatorFactory'
        );

        $decoratedInstance = $serviceManager->get('ZfcRbacTest\AuthorizationAware');
        $this->assertEquals($authorizationService, $decoratedInstance->getAuthorizationService());
    }

    public function testAuthorizationServiceIsInjectedWithDelegatorV3()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        if (! method_exists($serviceManager, 'build')) {
            $this->markTestSkipped('this test is only vor zend-servicemanager v3');
        }

        $serviceManager->setAllowOverride(true);
        $authorizationService = $this->getMock('ZfcRbac\Service\AuthorizationService', [], [], '', false);
        $serviceManager->setService(
            'ZfcRbac\Service\AuthorizationService',
            $authorizationService
        );
        $serviceManager->setAllowOverride(false);

        $serviceManager->setInvokableClass(
            'ZfcRbacTest\AuthorizationAware',
            'ZfcRbacTest\Initializer\AuthorizationAwareFake'
        );

        $serviceManager->addDelegator(
            'ZfcRbacTest\Initializer\AuthorizationAwareFake',
            'ZfcRbac\Factory\AuthorizationServiceDelegatorFactory'
        );

        $decoratedInstance = $serviceManager->get('ZfcRbacTest\AuthorizationAware');
        $this->assertEquals($authorizationService, $decoratedInstance->getAuthorizationService());
    }

    public function testDelegatorThrowExceptionWhenBadInterface()
    {
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $serviceManager->setAllowOverride(true);
        $authorizationService = $this->getMock('ZfcRbac\Service\AuthorizationService', [], [], '', false);
        $serviceManager->setService(
            'ZfcRbac\Service\AuthorizationService',
            $authorizationService
        );
        $serviceManager->setAllowOverride(false);

        $serviceManager->setFactory(
            'ZfcRbacTest\AuthorizationAware',
            function () {
                return new \StdClass();
            }
        );

        $serviceManager->addDelegator(
            'ZfcRbacTest\AuthorizationAware',
            'ZfcRbac\Factory\AuthorizationServiceDelegatorFactory'
        );

        $thrown = false;
        try {
            $serviceManager->get('ZfcRbacTest\AuthorizationAware');
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertStringEndsWith('The service ZfcRbacTest\AuthorizationAware must implement AuthorizationServiceAwareInterface.', $e->getMessage());
            if ($e->getPrevious()) {
                $this->assertInstanceOf('ZfcRbac\Exception\RuntimeException', $e->getPrevious());
            }
        }

        $this->assertTrue($thrown);
    }
}
