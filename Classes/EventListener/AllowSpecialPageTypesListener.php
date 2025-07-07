<?php

declare(strict_types=1);

namespace Rovitch\HeadlessPagePassword\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use Rovitch\PagePassword\Event\BeforeAccessIsGrantedEvent;

final class AllowSpecialPageTypesListener
{
    /**
     * Initial data type
     * @var array|int[]
     */
    private array $allowedTypes = [834];

    public function __invoke(BeforeAccessIsGrantedEvent $event): void
    {
        /** @var ServerRequestInterface $request */
        $request = $event->getRequest();
        $type = $request->getAttribute('routing')->getPageType();
        if (in_array($type, $this->allowedTypes)) {
            $event->setAccessGranted(true);
        }
    }
}
