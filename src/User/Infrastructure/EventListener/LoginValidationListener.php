<?php

namespace App\User\Infrastructure\EventListener;

use App\User\Application\DTO\LoginRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoginValidationListener
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getPathInfo() === '/api/login_check' && $request->isMethod('POST')) {
            $content = json_decode($request->getContent(), true);

            $loginRequest = new LoginRequest();
            $loginRequest->email = $content['email'] ?? '';
            $loginRequest->password = $content['password'] ?? '';

            $errors = $this->validator->validate($loginRequest);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                $response = new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], 400);

                $event->setResponse($response);
                $event->stopPropagation();
            }
        }
    }
}
