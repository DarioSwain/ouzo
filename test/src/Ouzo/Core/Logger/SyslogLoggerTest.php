<?php

namespace Ouzo\Logger;


use Ouzo\Config;
use Ouzo\Tests\Assert;
use Ouzo\Tests\Mock\Mock;
use Ouzo\Utilities\Clock;
use Psr\Log\LoggerInterface;

class SyslogLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SyslogLogProvider
     */
    private $syslogLogProvider;

    protected function setUp()
    {
        parent::setUp();
        $this->syslogLogProvider = Mock::create(SyslogLogProvider::class);
        $this->logger = new SyslogLogger('TEST', 'default', $this->syslogLogProvider);
    }

    protected function tearDown()
    {
        Config::clearProperty('logger', 'default', 'minimal_levels');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function shouldWriteErrorMessage()
    {
        //when
        $this->logger->error('My error log line with param %s and %s.', [42, 'Zaphod']);

        //then
        Mock::verify($this->syslogLogProvider)->log(LOG_ERR, 'TEST error: [ID: ] My error log line with param 42 and Zaphod.');
    }

}
