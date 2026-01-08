<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone', TelType::class, [
                'label' => 'Phone',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('strasse', TextType::class, [
                'label' => 'Street',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('hausnummer', TextType::class, [
                'label' => 'House Number',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Postal Code',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('region', TextType::class, [
                'label' => 'Region',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('countryCode', TextType::class, [
                'label' => 'Country Code',
                'attr' => ['class' => 'form-control', 'maxlength' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}

