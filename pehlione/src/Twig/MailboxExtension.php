<?php

namespace App\Twig;

use App\Repository\Support\SupportMessageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MailboxExtension extends AbstractExtension
{
    public function __construct(private SupportMessageRepository $repo) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('support_unread_count', fn() => $this->repo->countUnread()),
        ];
    }
}
