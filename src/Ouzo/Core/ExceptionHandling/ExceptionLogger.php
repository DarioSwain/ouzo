<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */

namespace Ouzo\ExceptionHandling;

use ErrorException;
use Exception;
use Ouzo\ContentType;
use Ouzo\Logger\Logger;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Objects;
use Ouzo\Utilities\Strings;

class ExceptionLogger
{
    const PASSWORD_PLACEHOLDER = '***';
    const UNAUTHORIZED = '401';
    const NOT_FOUND = '404';

    /** @var OuzoExceptionData */
    private $exceptionData;

    public function __construct(OuzoExceptionData $exceptionData)
    {
        $this->exceptionData = $exceptionData;
    }

    public static function newInstance(OuzoExceptionData $exceptionData)
    {
        return new self($exceptionData);
    }

    public static function forException(Exception $exception, $httpCode = 500)
    {
        return new self(OuzoExceptionData::forException($httpCode, $exception));
    }

    public function log()
    {
        $message = $this->getMessage();
        Logger::getLogger(__CLASS__)->error($message);
    }

    public function getMessage()
    {
        $className = $this->exceptionData->getClassName();
        $originalMessage = $this->exceptionData->getOriginalMessageWithCodes();
        $httpCode = $this->exceptionData->getHttpCode();
        $trace = $this->exceptionData->getStackTrace();
        $traceString = $trace->getTraceAsString();
        $source = $trace->getFile() . ":" . $trace->getLine();
        $isErrorException = is_subclass_of($className, ErrorException::class);

        if (in_array($httpCode, [self::UNAUTHORIZED, self::NOT_FOUND])) {
            $method = Arrays::getValue($_SERVER, 'REQUEST_METHOD');
            $uri = Arrays::getValue($_SERVER, 'SCRIPT_URI');
            return "$httpCode Exception [$method $uri]: $originalMessage";
        } else {
            $message = "Exception: $originalMessage";
            $message .= "\n------------------------------------------------------------------------------------------------------------------------------------";
            $message .= "\nHTTP status: $httpCode";

            if ($className && !$isErrorException) {
                $message .= "\nException: $className\nMessage: $originalMessage";
            } else {
                $message .= "\nError: $originalMessage";
            }

            if ($this->exceptionData->getSeverity()) {
                $message .= "\nSeverity: {$this->exceptionData->getSeverityAsString()}";
            }

            $message .= "\nLine: $source";

            if ($traceString) {
                $message .= "\nStack trace:\n$traceString";
            }
            $message .= "\nREQUEST_METHOD = " . Arrays::getValue($_SERVER, 'REQUEST_METHOD');
            $message .= "\nSCRIPT_URI = " . Arrays::getValue($_SERVER, 'SCRIPT_URI');
            $message .= "\nREQUEST_URI = " . Arrays::getValue($_SERVER, 'REQUEST_URI');
            $message .= "\nREDIRECT_URL = " . Arrays::getValue($_SERVER, 'REDIRECT_URL');
            if (!empty($_GET)) {
                $message .= "\nGET = " . self::sanitize($_GET);
            }
            if (!empty($_POST)) {
                $message .= "\nPOST = " . self::sanitize($_POST);
            }
            if (Strings::equalsIgnoreCase(ContentType::value(), 'application/json')) {
                $jsonBody = stream_get_contents(fopen('php://input', 'r'));
                $jsonBodyAbbrev = Strings::abbreviate($jsonBody, 1024 * 20);
                $message .= "\nJSON BODY = '$jsonBodyAbbrev'";
            }
            $message .= "\n------------------------------------------------------------------------------------------------------------------------------------";
            return $message;
        }
    }

    public static function sanitize(array $array)
    {
        if (isset($array['password'])) {
            $array['password'] = self::PASSWORD_PLACEHOLDER;
        }
        return Objects::toString($array);
    }
}