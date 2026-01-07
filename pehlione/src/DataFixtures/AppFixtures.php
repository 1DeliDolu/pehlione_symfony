<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private const CATEGORIES = [
        'Electronics',
        'Fashion',
        'Home & Garden',
        'Sports & Outdoors',
        'Books & Media',
        'Toys & Games',
        'Beauty & Personal Care',
        'Food & Beverages',
        'Automotive',
        'Office Supplies',
    ];

    private const PRODUCT_NAMES = [
        'Premium',
        'Deluxe',
        'Standard',
        'Professional',
        'Essential',
        'Classic',
        'Modern',
        'Vintage',
        'Compact',
        'Ultimate',
        'Smart',
        'Eco-Friendly',
        'Luxury',
        'Basic',
        'Advanced',
    ];

    private const BRANDS = [
        'Samsung',
        'LG',
        'Sony',
        'Apple',
        'Nike',
        'Adidas',
        'Puma',
        'Tommy Hilfiger',
        'Calvin Klein',
        'Gucci',
        'IKEA',
        'Philips',
        'Bosch',
        'Canon',
        'Nikon',
    ];

    public function load(ObjectManager $manager): void
    {
        $categories = [];

        // Create 10 categories
        foreach (self::CATEGORIES as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $category->setSlug(strtolower(str_replace(' ', '-', $categoryName)));

            $manager->persist($category);
            $categories[] = $category;
        }

        $manager->flush();

        // Create 30 products for each category
        foreach ($categories as $category) {
            for ($i = 1; $i <= 30; $i++) {
                $product = new Product();

                // Generate product name
                $productBaseName = self::PRODUCT_NAMES[array_rand(self::PRODUCT_NAMES)];
                $product->setName("{$productBaseName} {$category->getName()} #{$i}");

                // Generate slug
                $product->setSlug(strtolower(str_replace(' ', '-', $product->getName())));

                // Set brand
                $product->setBrand(self::BRANDS[array_rand(self::BRANDS)]);

                // Set category
                $product->setCategory($category);

                // Generate description
                $descriptions = [
                    "High-quality {$category->getName()} product with excellent durability and performance.",
                    "Premium {$category->getName()} designed for maximum comfort and style.",
                    "Professional-grade {$category->getName()} perfect for everyday use.",
                    "Innovative {$category->getName()} with modern features and sleek design.",
                    "Affordable {$category->getName()} without compromising on quality.",
                ];
                $product->setDescription($descriptions[array_rand($descriptions)]);

                // Set price (in cents, EUR)
                $product->setPriceAmount(rand(999, 99999)); // €9.99 to €999.99
                $product->setCurrency('EUR');

                // Set stock quantity
                $product->setStockQuantity(rand(5, 100));

                // Set active status
                $product->setIsActive(rand(0, 1) === 1);

                $manager->persist($product);
            }
        }

        $manager->flush();
    }
}
