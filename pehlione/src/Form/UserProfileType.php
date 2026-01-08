<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\User;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank(), new Length(['max' => 50])],
                'label' => 'First name',
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank(), new Length(['max' => 50])],
                'label' => 'Last name',
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [new NotBlank(), new Email(), new Length(['max' => 180])],
                'label' => 'Email address',
            ])
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Profile Photo',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Changes',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
