<?php

namespace App\Service\Support;

use App\Entity\SupportMessage;

final class TicketSlaService
{
    public function onTicketCreated(SupportMessage $ticket): void
    {
        $dept = $ticket->getDepartment();
        if (!$dept) {
            return;
        }

        $now = new \DateTimeImmutable();
        $mult = $ticket->getPriorityEnum()->multiplier();

        $first = (int) round($dept->getSlaFirstResponseMinutes() * $mult);
        $resolution = (int) round($dept->getSlaResolutionMinutes() * $mult);

        $ticket->setFirstResponseDueAt($now->modify("+{$first} minutes"));
        $ticket->setResolutionDueAt($now->modify("+{$resolution} minutes"));

        if ($ticket->getType() === SupportMessage::TYPE_INTERNAL) {
            $ticket->setLastStaffMessageAt($now);
        } else {
            $ticket->setLastCustomerMessageAt($now);
        }
    }

    public function onCustomerReply(SupportMessage $ticket): void
    {
        $dept = $ticket->getDepartment();
        if (!$dept) {
            return;
        }

        $now = new \DateTimeImmutable();
        $ticket->setLastCustomerMessageAt($now);

        $mult = $ticket->getPriorityEnum()->multiplier();
        $first = (int) round($dept->getSlaFirstResponseMinutes() * $mult);
        $ticket->setFirstResponseDueAt($now->modify("+{$first} minutes"));
    }

    public function onStaffReply(SupportMessage $ticket): void
    {
        $now = new \DateTimeImmutable();
        $ticket->setLastStaffMessageAt($now);

        if ($ticket->getFirstResponseAt() === null) {
            $ticket->setFirstResponseAt($now);
        }
    }
}
