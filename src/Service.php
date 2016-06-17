<?php
/**
 * OData client library
 *
 * @author  Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license MIT
 */
namespace Mekras\OData\Client;

use Http\Client\Exception as HttpClientException;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Mekras\OData\Client\Exception\ClientErrorException;
use Mekras\OData\Client\Exception\ErrorException;
use Mekras\OData\Client\Exception\LogicException;
use Mekras\OData\Client\Exception\RuntimeException;
use Mekras\OData\Client\Exception\ServerErrorException;
use Mekras\OData\Client\Parser\ParserFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * OData Service.
 */
class Service
{
    /**
     * Service root URI.
     *
     * @var string
     */
    private $serviceRootUri;

    /**
     * Клиент HTTP.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * The HTTP request factory.
     *
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * The response parser factory.
     *
     * @var ParserFactory
     */
    private $parserFactory;

    /**
     * Creates new OData service proxy.
     *
     * @param string         $serviceRootUri OData service root URI.
     * @param HttpClient     $httpClient     HTTP client to use.
     * @param RequestFactory $requestFactory The HTTP request factory.
     *
     * @since 1.0
     *
     * @link  http://www.odata.org/documentation/odata-version-2-0/uri-conventions#ServiceRootUri
     */
    public function __construct(
        $serviceRootUri,
        HttpClient $httpClient,
        RequestFactory $requestFactory
    ) {
        $this->serviceRootUri = rtrim($serviceRootUri, '/');
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->parserFactory = new ParserFactory();
    }

    /**
     * Perform actual HTTP request to service
     *
     * @param string $method  HTTP method.
     * @param string $uri     URI.
     * @param string $content Request body contents.
     *
     * @return array Generalized data.
     *
     * @throws \Mekras\OData\Client\Exception\ErrorException
     * @throws \Mekras\OData\Client\Exception\InvalidDataException
     * @throws \Mekras\OData\Client\Exception\InvalidFormatException
     * @throws \Mekras\OData\Client\Exception\LogicException
     * @throws \Mekras\OData\Client\Exception\RuntimeException
     * @throws \Mekras\OData\Client\Exception\ServerErrorException
     * @throws \Mekras\OData\Client\Exception\UnsupportedException
     */
    public function sendRequest($method, $uri, $content = null)
    {
        $headers = [
            'DataServiceVersion' => '1.0',
            'MaxDataServiceVersion' => '1.0',
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $uri = '/' . ltrim($uri, '/');
        $request = $this->requestFactory
            ->createRequest($method, $this->serviceRootUri . $uri, $headers, $content);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (HttpClientException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new LogicException($e->getMessage(), 0, $e);
        }

        $version = $response->getHeaderLine('DataServiceVersion');
        if ('' === $version) {
            throw new ServerErrorException('DataServiceVersion header not missed');
        }

        $contentType = $response->getHeaderLine('Content-type');
        $contentType = explode(';', $contentType)[0];

        $parser = $this->parserFactory->getByContentType($contentType);

        $rawData = $parser->parse((string) $response->getBody());

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400 && $statusCode < 600) {
            throw $this->createErrorException($response, $rawData);
        }

        return $rawData;
    }

    /**
     * Creates error exception from response and parsed raw data
     *
     * @param ResponseInterface $response
     * @param array             $rawData
     *
     * @return ErrorException
     */
    private function createErrorException(ResponseInterface $response, array $rawData)
    {
        if ($response->getStatusCode() < 500) {
            $exception = ClientErrorException::createFromArray(
                $rawData,
                $response->getStatusCode()
            );
        } else {
            $exception = ServerErrorException::createFromArray(
                $rawData,
                $response->getStatusCode()
            );
        }

        return $exception;
    }
}