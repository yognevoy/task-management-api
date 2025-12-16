<?php

namespace App\EventListener;

use App\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionEventListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse(
                ['error' => $exception->getMessage()],
                $exception->getStatusCode()
            );
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
