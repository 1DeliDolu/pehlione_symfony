<?php

namespace App\Form\Support;

use App\Entity\SupportDepartment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class InternalThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('department', EntityType::class, [
                'class' => SupportDepartment::class,
                'choice_label' => 'name',
                'placeholder' => 'Hedef birim seçin...',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('subject', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
                'attr' => ['rows' => 5],
            ]);
    }
}
