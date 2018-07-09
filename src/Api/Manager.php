<?php

namespace TopotRu\TextRu\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use TopotRu\TextRu\Api\Exception\ApiException;
use TopotRu\TextRu\Api\Model\CheckResult;

/**
 * Class Manager
 * @package TopotRu\TextRu\Api
 */
class Manager
{
    const API_METHOD = 'POST';
    
    const API_URI         = 'https://api.text.ru/';
    const API_URI_POST    = self::API_URI.'post';
    const API_URI_ACCOUNT = self::API_URI.'account';
    
    /**
     * @var string
     */
    private $apiKey;
    
    /**
     * @var ClientInterface
     */
    private $client;
    
    /**
     * @param string $apiKey User API key.
     * @param ClientInterface $client HTTP client
     */
    public function __construct($apiKey, ClientInterface $client)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }
    
    /**
     * @param string $text
     * @param string $resultCallback URI of result callback
     * @param bool $isResultPublic
     * @param bool $hasResultVisualReport
     * @param array $excludedDomains
     * @return string Unique text identifier
     * @throws ApiException
     */
    public function check(
        $text,
        $resultCallback = null,
        $isResultPublic = false,
        $hasResultVisualReport = false,
        array $excludedDomains = []
    )
    {
        try {
            
            $data = $this->prepareCheckData(
                $text,
                $resultCallback,
                $isResultPublic,
                $hasResultVisualReport,
                $excludedDomains
            );
    
            $requestContent = $this->client->request(self::API_METHOD, self::API_URI_POST, ['form_params' => $data])
                ->getBody()
                ->getContents();
    
            $result = json_decode($requestContent, true);
    
            if (isset($result['error_code'])) {
                throw new ApiException($result['error_desc'] ?? '_unknown_', $result['error_code']);
            }
    
            return $result['text_uid'];
            
        } catch (GuzzleException | \Exception $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Prepares data ready for request.
     * @param string $text
     * @param string $resultCallback URI of result callback
     * @param bool $isResultPublic
     * @param bool $hasResultVisualReport
     * @param array $excludedDomains
     * @return array Returns data ready for request
     */
    private function prepareCheckData(
        $text,
        $resultCallback = null,
        $isResultPublic = false,
        $hasResultVisualReport = true,
        array $excludedDomains = []
    )
    {
        $data = [
            'text'    => $text,
            'userkey' => $this->apiKey,
        ];
        
        if (! empty($resultCallback)) {
            $data['callback'] = $resultCallback;
        }
        
        if ($isResultPublic) {
            $data['visible'] = 'vis_on';
        }
        
        if (! $hasResultVisualReport) {
            $data['copying'] = 'noadd';
        }
        
        if (0 !== count($excludedDomains)) {
            $data['exceptdomain'] = implode(' ', $excludedDomains);
        }
        
        return $data;
    }
    
    /**
     * Tries to get text check result.
     * @param string $textId Text unique identifier
     * @return CheckResult
     * @throws ApiException
     */
    public function tryGetResult($textId)
    {
        try {
            $jsonResponse = (string)$this->client->request(self::API_METHOD, self::API_URI_POST, [
                    'form_params' => [
                        'uid'         => $textId,
                        'userkey'     => $this->apiKey,
                        'jsonvisible' => 'detail',
                    ],
                ]
            )->getBody();
    
            return $this->parseResult($jsonResponse, $textId);
            
        } catch (GuzzleException | \Exception $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Tries to parse result and returns object model.
     * @param string $requestContent
     * @param null|string $textId
     * @return CheckResult
     * @throws ApiException
     */
    public function parseResult($requestContent, $textId = null)
    {
        $result = json_decode($requestContent, true);
    
        if (isset($result['error_code'])) {
            throw new ApiException($result['error_desc'] ?? '_unknown_', $result['error_code']);
        }
        
        $seoResult    = isset($result['seo_check']) ? json_decode($result['seo_check'], true) : [];
        $waterPercent = isset($seoResult['water_percent']) ? $seoResult['water_percent'] : 0;
        
        if (null === $textId) {
            $textId = $result['uid'];
        }
        
        return new CheckResult($textId, (float)$result['text_unique'], $waterPercent);
    }
    
    /**
     * Returns available symbols.
     * @return int
     * @throws ApiException
     */
    public function getAvailableSymbols()
    {
        try {
    
            $jsonResponse = $this->client->request(self::API_METHOD, self::API_URI_ACCOUNT, [
                    'form_params' => [
                        'method'  => 'get_packages_info',
                        'userkey' => $this->apiKey,
                    ],
                ]
            )->getBody()->getContents();
    
            $result = json_decode($jsonResponse, true);
    
            if (isset($result['error_code'])) {
                throw new ApiException($result['error_desc'] ?? '_unknown_', $result['error_code']);
            }
    
            return (int)$result['size'];
            
        } catch (GuzzleException | \Exception $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
