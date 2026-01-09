<?php

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ExceptionEventListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse(
                ['error' => $exception->getMessage()],
                $exception->getStatusCode()
            );
            $event->setResponse($response);
            $event->stopPropagation();
        }

        if ($exception instanceof HandlerFailedException) {
            $previous = $exception->getPrevious();
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;

            if ($previous instanceof HttpExceptionInterface) {
                $code = $previous->getStatusCode();
            }

            $response = new JsonResponse(
                ['error' => $previous->getMessage()],
                $code
            );
            $event->setResponse($response);
            $event->stopPropagation();
        }

        if ($exception instanceof UnprocessableEntityHttpException) {
            $message = $exception->getMessage();

            if (str_contains($message, "\n")) {
                $errorLines = explode("\n", $message);
                $errors = [];

                foreach ($errorLines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $errors[] = $line;
                    }
                }

                $response = new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => $errors
                ], $exception->getStatusCode());

                $event->setResponse($response);
                $event->stopPropagation();
            }
        }
    }
}
