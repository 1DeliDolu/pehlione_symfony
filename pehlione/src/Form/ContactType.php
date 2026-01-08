<?php

namespace App\Form;

use App\Entity\SupportDepartment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityRepository;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('department', EntityType::class, [
                'class' => SupportDepartment::class,
                'choice_label' => 'name',
                'label' => 'Birim / Konu Alanı',
                'placeholder' => 'Bir birim seçin...',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('d')
                    ->andWhere('d.isActive = 1')
                    ->orderBy('d.name', 'ASC'),
                'attr' => [
                    'class' => 'block w-full rounded-md border border-gray-300 px-3.5 py-2 text-gray-900 focus:outline-2 focus:outline-indigo-600'
                ]
            ])
            ->add('subject', TextType::class, [
                'label' => 'Konu',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 3, max: 120),
                ],
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 120,
                    'class' => 'block w-full rounded-md border border-gray-300 px-3.5 py-2 text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600',
                    'placeholder' => 'Mesajınızın konusunu yazınız...'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Mesaj',
                'attr' => [
                    'rows' => 6,
                    'minlength' => 10,
                    'maxlength' => 5000,
                    'class' => 'block w-full rounded-md border border-gray-300 px-3.5 py-2 text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600',
                    'placeholder' => 'Ayrıntılı mesajınızı yazınız... (en az 10 karakter)'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10, max: 5000),
                ],
            ]);
    }
}
