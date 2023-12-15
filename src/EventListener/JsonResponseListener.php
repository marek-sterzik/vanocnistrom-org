<?php

namespace App\EventListener;

use Exception;

use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonResponseListener
{
    /**
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function onKernelView(ViewEvent $event): void
    {
        try {
            $response = new JsonResponse($event->getControllerResult());
            $event->setResponse($response);
        } catch (Exception $e) {
        }
    }
}
