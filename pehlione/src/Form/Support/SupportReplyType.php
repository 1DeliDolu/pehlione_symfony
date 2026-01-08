<?php

namespace App\Form\Support;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SupportReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
                'attr' => ['rows' => 4],
            ])
            ->add('internal', CheckboxType::class, [
                'required' => false,
            ]);
    }
}
