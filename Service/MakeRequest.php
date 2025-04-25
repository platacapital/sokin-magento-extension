<?php

namespace SokinPay\PaymentGateway\Service;

use Exception;
use InvalidArgumentException;
use Magento\Framework\HTTP\Client\Curl;
use SokinPay\PaymentGateway\Helper\ConfigHelper;

class MakeRequest
{
    /**
     * @var Curl
     */
    protected $curl;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param Curl $curl
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Curl $curl,
        ConfigHelper $configHelper
    ) {
        $this->curl = $curl;
        $this->configHelper = $configHelper;
    }

    /**
     * Send HTTP request
     *
     * @param string $endPoint
     * @param string $method
     * @param array|string $params
     * @param array $headers
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function sendRequest($endPoint, $method, $params, $headers = [])
    {
        try {
            $this->configHelper->logger->info('----- API Request Start -----');

            $headers = $this->prepareHeaders($headers);
            $url = $this->prepareUrl($endPoint);

            $this->configHelper->logger->info('Url : ' . $url);
            $this->configHelper->logger->info('Headers : ' . var_export($headers, true));
            $this->configHelper->logger->info('Method : ' . $method);
            $this->configHelper->logger->info('Params : ' . var_export($params, true));

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setHeaders($headers);
            // Set SSL version
            $this->curl->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            // Set Curl HTTP version
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            // Handle different request methods
            switch (strtoupper($method)) {
                case RequestMethods::REQUEST_METHOD_GET:
                    $this->curl->get($url);
                    break;
                case RequestMethods::REQUEST_METHOD_POST:
                    $this->curl->post($url, $params);
                    break;
                case RequestMethods::REQUEST_METHOD_DELETE:
                    $this->curl->delete($url);
                    break;
                default:
                    throw new InvalidArgumentException('Invalid HTTP Method : ' . $method);
            }
            // Get response
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus(); // Get HTTP response code

            $this->configHelper->logger->info('Response Code : ' . $httpCode);
            $this->configHelper->logger->info('Response : ' . var_export($response, true));

            $returnData = [
                'code' => $httpCode,
                'response' => $this->isJson($response) ? json_decode($response, true) : $response
            ];
        } catch (Exception $e) {
            $returnData = ['code' => 500, 'response' => 'Exception : ' . $e->getMessage()];
        }

        $this->configHelper->logger->info('----- API Request End -----');

        return $returnData;
    }

    /**
     * Prepare Headers Including the Mandatory Headers
     *
     * @param array $headers
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareHeaders($headers = [])
    {

        $apiKey = $this->configHelper->getSecretKey();
        $mandatoryHeaders = [
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json'
        ];
        return array_merge($mandatoryHeaders, $headers);
    }

    /**
     * Prepare Url Including the EndPoint
     *
     * @param string $endPoint
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareUrl($endPoint = '')
    {
        $apiUlr = $this->configHelper->getApiUrl();
        return !empty($endPoint) ? $apiUlr . $endPoint : $apiUlr;
    }

    /**
     * Function To Check the String is Json or not
     *
     * @param string $string
     *
     * @return bool
     */
    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}
