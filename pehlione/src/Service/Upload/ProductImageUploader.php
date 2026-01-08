<?php

namespace App\Service\Upload;

use App\Entity\Product;
use App\Entity\ProductImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProductImageUploader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * @param UploadedFile[] $files
     * @return ProductImage[]
     */
    public function upload(Product $product, array $files): array
    {
        $images = [];

        $productId = $product->getId();
        if (!$productId) {
            throw new \RuntimeException('Product must be persisted before uploading images');
        }

        $folder = sprintf('images/products/%d', $productId);
        $targetDir = $this->projectDir . '/public/' . $folder;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $ext = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
            $hash = bin2hex(random_bytes(16));
            $finalName = sprintf('%s.%s', $hash, $ext);

            $file->move($targetDir, $finalName);

            $img = new ProductImage();
            $img->setProduct($product);
            $img->setOriginalName($file->getClientOriginalName());
            $img->setPath($folder . '/' . $finalName);
            $img->setAltText($product->getName());

            $images[] = $img;
        }

        return $images;
    }
}
