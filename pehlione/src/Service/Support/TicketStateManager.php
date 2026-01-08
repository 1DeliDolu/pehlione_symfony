<?php

namespace App\Service\Support;

use App\Entity\SupportMessage;
use Symfony\Component\Workflow\WorkflowInterface;

final class TicketStateManager
{
    public function __construct(private WorkflowInterface $supportTicketWorkflow) {}

    public function markPending(SupportMessage $ticket): void
    {
        if ($this->supportTicketWorkflow->can($ticket, 'mark_pending')) {
            $this->supportTicketWorkflow->apply($ticket, 'mark_pending');
        }
    }

    public function reopen(SupportMessage $ticket): void
    {
        if ($this->supportTicketWorkflow->can($ticket, 'reopen')) {
            $this->supportTicketWorkflow->apply($ticket, 'reopen');
        } elseif ($this->supportTicketWorkflow->can($ticket, 'reopen_closed')) {
            $this->supportTicketWorkflow->apply($ticket, 'reopen_closed');
        }
    }

    public function close(SupportMessage $ticket): void
    {
        if ($this->supportTicketWorkflow->can($ticket, 'close')) {
            $this->supportTicketWorkflow->apply($ticket, 'close');
            $ticket->setClosedAt(new \DateTimeImmutable());
        }
    }
}
