<?php

namespace App\Form\Support;

use App\Entity\SupportDepartment;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SupportRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('department', EntityType::class, [
                'class' => SupportDepartment::class,
                'choice_label' => 'name',
                'placeholder' => 'Bir birim seçin...',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('d')
                    ->andWhere('d.isActive = 1')
                    ->orderBy('d.name', 'ASC'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('subject', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10)],
                'attr' => ['rows' => 6],
            ])
            ->add('customerName', TextType::class, [
                'required' => false,
                'constraints' => [new Assert\Length(max: 180)],
            ])
            ->add('customerEmail', EmailType::class, [
                'required' => false,
                'constraints' => [new Assert\Length(max: 180)],
            ]);
    }
}
