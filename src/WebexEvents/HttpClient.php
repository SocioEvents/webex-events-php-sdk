<?php

namespace WebexEvents;

use WebexEvents\Exceptions\HttpClientError;

class HttpClient implements HttpClientInterface
{
    public LoggerInterface $logger;
    public RequestOptions $requestOptions;

    /**
     * @param RequestOptions $requestOptions
     */
    public function __construct(RequestOptions $requestOptions)
    {
        $this->logger = Configuration::getLogger();
        $this->requestOptions = $requestOptions;
    }

    function post(string $url, array $data, array $requestHeaders): array
    {
        $ch = curl_init($url);
        $jsonPostData = json_encode($data);

        // Set cURL options
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPostData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestOptions->getTimeoutSeconds());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->requestOptions->getConnectTimeoutSeconds());
        // ssl verify
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$responseHeaders)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $responseHeaders[strtoupper(trim($header[0]))] = trim($header[1]);
                return $len;
            }
        );

        //Post request
        $postResponse = curl_exec($ch);

        if ($postResponse === false) {
            $this->logger->error('cURL error: ' . curl_error($ch));
            throw new HttpClientError('cURL error: ' . curl_error($ch));
        }


        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTimeMs = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;

        return [
            'url' => $url,
            'httpStatusCode' => $httpCode,
            'responseHeaders' => $responseHeaders,
            'responseBodyString' => $postResponse,
            'totalTimeMs' => $totalTimeMs,
            'requestHeaders' => $requestHeaders,
            'requestBody' => $jsonPostData
        ];
    }
}