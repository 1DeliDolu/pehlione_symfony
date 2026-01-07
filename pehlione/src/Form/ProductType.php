<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ad',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marka',
            ])
            ->add('category', EntityType::class, [
                'label' => 'Kategori',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Kategori seçin',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Açıklama',
                'required' => false,
                'attr' => ['rows' => 6],
            ])
            ->add('priceAmount', IntegerType::class, [
                'label' => 'Fiyat (cent)',
                'help' => 'EUR cent olarak girin. Örn: 49,90 EUR için 4990.',
            ])
            ->add('currency', TextType::class, [
                'label' => 'Para Birimi',
                'attr' => ['maxlength' => 3],
            ])
            ->add('stockQuantity', IntegerType::class, [
                'label' => 'Stok',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Aktif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
