<?php
/**
 * Created by PhpStorm.
 * User: topot
 * Date: 04.07.2018
 * Time: 2:15
 */

namespace TopotRu\TextRu\Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use TopotRu\TextRu\Api\Manager;
use TopotRu\TextRu\Api\Model\CheckResult;

/**
 * Class ManagerTest
 * @package TopotRu\TextRu\Tests\Unit
 */
class ManagerTest extends \PHPUnit\Framework\TestCase
{
    
    const VAL_APIKEY = 'apikey';
    
    /**
     * @var MockObject
     */
    protected $clientMock;
    
    /**
     * @var Manager
     */
    protected $manager;
    
    /**
     * @throws \TopotRu\TextRu\Api\Exception\ApiException
     */
    public function testCheck()
    {
        $data = [
            'text'         => 'Some text',
            'userkey'      => self::VAL_APIKEY,
            'callback'     => 'http://test.com/process-result',
            'copying'      => 'noadd',
            'visible'      => 'vis_on',
            'exceptdomain' => 'test.com mail.ru',
        ];
        
        $this->clientMock->method('request')
            ->with(Manager::API_METHOD, Manager::API_URI_POST, ['body' => $data])
            ->willReturn(new Response(200, [], '{"text_uid":"12345"}'));
        
        $result = $this->manager->check($data['text'], $data['callback'], true, false, ['test.com', 'mail.ru']);
        
        $this->assertEquals('12345', $result);
        
    }
    
    /**
     * @throws \TopotRu\TextRu\Api\Exception\ApiException
     */
    public function testTryGetResult()
    {
        $this->clientMock->method('request')
            ->with(Manager::API_METHOD, Manager::API_URI_POST, [
                    'body' => [
                        'uid'         => '12345',
                        'userkey'     => self::VAL_APIKEY,
                        'jsonvisible' => 'detail',
                    ],
                ]
            )->willReturn(new Response(200, [], '{"text_unique":12.5, "seo_check": "{\"water_percent\": 7}"}'));
        
        $actual = $this->manager->tryGetResult(12345);
        
        $this->assertEquals(new CheckResult(12345, 12.5, 7), $actual);
    }
    
    /**
     *
     */
    public function testParseResult()
    {
        $actual = $this->manager
            ->parseResult('{"uid": 12345,"text_unique":12.5, "seo_check": "{\"water_percent\": 7}"}');
        
        $this->assertEquals(new CheckResult(12345, 12.5, 7), $actual);
    }
    
    /**
     * @throws \TopotRu\TextRu\Api\Exception\ApiException
     */
    public function testAvailableSymbols()
    {
        $this->clientMock->method('request')
            ->with(Manager::API_METHOD, Manager::API_URI_ACCOUNT, ['body' => ['method' => 'get_packages_info', 'userkey' => self::VAL_APIKEY]])
            ->willReturn(new Response(200, [], '{"size":115}'));
        
        $actual = $this->manager->getAvailableSymbols();
        
        $this->assertEquals(115, $actual);
        
    }
    
    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->clientMock = $this->getMockBuilder(ClientInterface::class)
            //->setMethods(['request'])
            ->getMock();
        
        $this->manager = new Manager(self::VAL_APIKEY, $this->clientMock);
    }
}
