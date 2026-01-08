<?php

namespace App\Form\Support\Admin;

use App\Entity\SupportTag;
use App\Entity\User;
use App\Enum\TicketPriority;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class SupportAdminUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => TicketPriority::LOW->value,
                    'Normal' => TicketPriority::NORMAL->value,
                    'High' => TicketPriority::HIGH->value,
                    'Urgent' => TicketPriority::URGENT->value,
                ],
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'choice_label' => fn (User $user) => trim(($user->getEmail() ?? '').' '.$user->getUserIdentifier()),
            ])
            ->add('tags', EntityType::class, [
                'class' => SupportTag::class,
                'multiple' => true,
                'required' => false,
                'choice_label' => 'name',
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'support_admin_update';
    }
}
