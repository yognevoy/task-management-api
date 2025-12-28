<?php

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
            $response = new JsonResponse(
                ['error' => $exception->getPrevious()->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
