<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        // Get filter parameters
        $categoryId = $request->query->get('category');
        $brand = $request->query->get('brand');
        $sortBy = $request->query->get('sort', 'newest'); // newest, price-asc, price-desc
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        // Build query
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->where('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->join('p.category', 'c');

        // Filter by category
        if ($categoryId && $categoryId !== 'all') {
            $queryBuilder->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Filter by brand
        if ($brand && $brand !== 'all') {
            $queryBuilder->andWhere('p.brand = :brand')
                ->setParameter('brand', $brand);
        }

        // Filter by price range
        if ($minPrice !== null && $minPrice !== '') {
            $queryBuilder->andWhere('p.priceAmount >= :minPrice')
                ->setParameter('minPrice', (int)$minPrice * 100); // Convert to cents
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $queryBuilder->andWhere('p.priceAmount <= :maxPrice')
                ->setParameter('maxPrice', (int)$maxPrice * 100); // Convert to cents
        }

        // Sorting
        match ($sortBy) {
            'price-asc' => $queryBuilder->orderBy('p.priceAmount', 'ASC'),
            'price-desc' => $queryBuilder->orderBy('p.priceAmount', 'DESC'),
            'name' => $queryBuilder->orderBy('p.name', 'ASC'),
            default => $queryBuilder->orderBy('p.createdAt', 'DESC'), // newest
        };

        $products = $queryBuilder->getQuery()->getResult();
        $categories = $categoryRepository->findAll();

        // Get unique brands for filter
        $brandsQuery = $productRepository->createQueryBuilder('p')
            ->select('DISTINCT p.brand')
            ->where('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.brand', 'ASC')
            ->getQuery();

        $brandsData = $brandsQuery->getResult();
        $brands = array_map(function($item) { return $item['brand']; }, $brandsData);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'selectedCategory' => $categoryId,
            'selectedBrand' => $brand,
            'sortBy' => $sortBy,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}


