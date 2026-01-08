<?php

namespace App\Service\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class AvatarUploader
{
    public function __construct(
        private SluggerInterface $slugger,
        private string $avatarDirectory
    ) {
    }

    public function upload(int $userId, UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(4)) . '.' . $file->guessExtension();

        $file->move($this->avatarDirectory, $newFilename);

        return 'avatars/' . $newFilename;
    }
}
