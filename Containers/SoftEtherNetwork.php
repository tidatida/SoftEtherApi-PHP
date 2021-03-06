<?php

namespace SoftEtherApi\SoftEtherNetwork
{
    require('Model/SoftEtherHttpResult.php');
    use SoftEtherApi\Model\SoftEtherHttpResult;

    function GetDefaultHeaders()
    {
        return [
            'Keep-Alive' => 'timeout=15; max=19',
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/octet-stream'
        ];
    }

    function SendHttpRequest($socket, $method, $target, $body, $headers)
    {
        $header = strtoupper($method) . " {$target} HTTP/1.1\r\n";

        foreach ($headers as $key => $val)
            $header .= "${key}: ${val}\r\n";

        if (!array_key_exists('Content-Length', $headers))
            $header .= 'Content-Length: '. strlen($body). "\r\n";

        $header .= "\r\n";
        $bytesWritten = fwrite($socket, $header);
        $bytesWritten = fwrite($socket, $body);
        $flushDone = fflush($socket);
    }

    function GetHttpResponse($socket)
    {
        $firstLine = fgets($socket);
        $responseCode = (int)substr($firstLine, 9, 3);
        $responseHeaders = [];
        $responseLength = 0;

        while (true)
        {
            $headerLine = fgets($socket);

            if ($headerLine == "\r\n")
                break;

            $headerArray = explode(': ', $headerLine);
            $headerName = strtolower(trim($headerArray[0]));
            $headerValue = trim($headerArray[1]);

            $responseHeaders[$headerName] = $headerValue;

            if ($headerName == "content-length")
                $responseLength = (int)$headerValue;
        }

        $responseBody = '';
        while (strlen($responseBody) < $responseLength)
            $responseBody .= fread($socket, $responseLength - strlen($responseBody));

        $result = new SoftEtherHttpResult();
        $result->code = $responseCode;
        $result->headers = $responseHeaders;
        $result->length = $responseLength;
        $result->body = $responseBody;

        return $result;
    }
}