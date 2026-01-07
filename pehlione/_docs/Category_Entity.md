# Category Entity Dokümantasyonu

## Genel Bakış

`Category` entity'si, sistem içindeki kategori verilerini yöneten temel Doctrine ORM entity'sidir.

## Dosya Konumu

```
src/Entity/Category.php
```

## Entity Özellikleri

### Alanlar

| Alan | Tip | Uzunluk | Nullable | Açıklama |
|------|-----|---------|----------|----------|
| `id` | integer | - | No | Primary key, otomatik artan |
| `name` | string | 150 | No | Kategori adı |
| `slug` | string | 160 | No | URL-friendly slug (unique) |
| `description` | text | - | Yes | Kategori açıklaması |
| `createdAt` | datetime_immutable | - | No | Oluşturulma zamanı |
| `updatedAt` | datetime_immutable | - | No | Güncellenme zamanı |

### Constraint'ler

- **Unique Constraint**: `slug` alanı için unique index
  ```php
  #[ORM\UniqueConstraint(name: 'uniq_category_slug', columns: ['slug'])]
  ```

### Lifecycle Callbacks

Entity, Doctrine lifecycle callback'lerini kullanır:

#### PrePersist
İlk kayıt oluşturulurken çalışır:
- `createdAt` ve `updatedAt` alanlarını mevcut zamana ayarlar

#### PreUpdate
Kayıt güncellenirken çalışır:
- `updatedAt` alanını mevcut zamana ayarlar

```php
#[ORM\HasLifecycleCallbacks]
```

## CLI ile Entity Oluşturma

### 1. MakerBundle Kurulumu

```bash
composer require --dev symfony/maker-bundle
```

### 2. Entity Oluşturma

```bash
php bin/console make:entity Category
```

#### Alan Tanımlamaları

Komut çalıştırıldığında aşağıdaki alanları girin:

1. **name**
   - Type: `string`
   - Length: `150`
   - Nullable: `no`

2. **slug**
   - Type: `string`
   - Length: `160`
   - Nullable: `no`

3. **description**
   - Type: `text`
   - Nullable: `yes`

4. **createdAt**
   - Type: `datetime_immutable`
   - Nullable: `no`

5. **updatedAt**
   - Type: `datetime_immutable`
   - Nullable: `no`

Alan eklemeyi bitirmek için `Enter` ile boş geçin.

### 3. Manuel Eklemeler

MakerBundle bazı özellikleri otomatik eklemez. Aşağıdakileri manuel olarak ekleyin:

#### Unique Constraint

```php
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_category_slug', columns: ['slug'])]
class Category
```

#### Lifecycle Callbacks

Class başına:
```php
#[ORM\HasLifecycleCallbacks]
```

Class sonuna metodlar:
```php
#[ORM\PrePersist]
public function onPrePersist(): void
{
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
}

#[ORM\PreUpdate]
public function onPreUpdate(): void
{
    $this->updatedAt = new \DateTimeImmutable();
}
```

### 4. Migration Oluşturma ve Uygulama

```bash
# Migration dosyası oluştur
php bin/console doctrine:migrations:diff

# Migration'ı veritabanına uygula
php bin/console doctrine:migrations:migrate
```

## Kullanım Örnekleri

### Yeni Kategori Oluşturma

```php
use App\Entity\Category;

$category = new Category();
$category->setName('Teknoloji');
$category->setSlug('teknoloji');
$category->setDescription('Teknoloji ile ilgili içerikler');

$entityManager->persist($category);
$entityManager->flush();

// createdAt ve updatedAt otomatik olarak doldurulur
```

### Kategori Güncelleme

```php
$category = $categoryRepository->find($id);
$category->setName('Yeni Teknoloji');

$entityManager->flush();

// updatedAt otomatik olarak güncellenir
```

### Kategorileri Listeleme

```php
// Tüm kategoriler
$categories = $categoryRepository->findAll();

// Slug'a göre arama
$category = $categoryRepository->findOneBy(['slug' => 'teknoloji']);

// İsme göre arama
$categories = $categoryRepository->findBy(['name' => 'Teknoloji']);
```

## Slug Otomasyonu (Önerilen İyileştirme)

Slug'ı otomatik oluşturmak için birkaç yöntem:

### Yöntem 1: Controller/Service Katmanında

```php
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryService
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}
    
    public function createCategory(string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setSlug($this->slugger->slug($name)->lower());
        
        return $category;
    }
}
```

### Yöntem 2: Entity Event Subscriber

```php
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: Category::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Category::class)]
class CategorySlugSubscriber
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}
    
    public function prePersist(Category $category): void
    {
        $this->updateSlug($category);
    }
    
    public function preUpdate(Category $category): void
    {
        $this->updateSlug($category);
    }
    
    private function updateSlug(Category $category): void
    {
        if (!$category->getSlug() || $category->getSlug() === '') {
            $category->setSlug(
                $this->slugger->slug($category->getName())->lower()
            );
        }
    }
}
```

### Yöntem 3: Entity İçinde Gedmo/Sluggable (Bundle Gerektirir)

```bash
composer require stof/doctrine-extensions-bundle
```

```php
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Slug(fields: ['name'])]
#[ORM\Column(length: 160)]
private ?string $slug = null;
```

## Repository

Entity ile birlikte `CategoryRepository` otomatik oluşturulur:

```
src/Repository/CategoryRepository.php
```

Repository içine özel sorgular ekleyebilirsiniz:

```php
public function findActiveCategories(): array
{
    return $this->createQueryBuilder('c')
        ->orderBy('c.name', 'ASC')
        ->getQuery()
        ->getResult();
}

public function findBySlugWithDetails(string $slug): ?Category
{
    return $this->createQueryBuilder('c')
        ->andWhere('c.slug = :slug')
        ->setParameter('slug', $slug)
        ->getQuery()
        ->getOneOrNullResult();
}
```

## Veritabanı Tablosu

Migration uygulandıktan sonra oluşacak tablo yapısı:

```sql
CREATE TABLE category (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    UNIQUE INDEX uniq_category_slug (slug)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
```

## Best Practices

1. **Slug Validasyonu**: Form validation'da slug format kontrolü ekleyin
2. **Cascade Operations**: İlişkili entity'ler eklendiğinde cascade ayarlarını düşünün
3. **Soft Delete**: İsterseniz soft delete için `deletedAt` alanı ekleyin
4. **Ordering**: Kategori sıralaması için `position` alanı ekleyebilirsiniz
5. **Hiyerarşi**: Alt kategoriler için self-referencing relation ekleyebilirsiniz

## İlgili Dosyalar

- Entity: `src/Entity/Category.php`
- Repository: `src/Repository/CategoryRepository.php`
- Migrations: `migrations/VersionXXXXXXXXXXXXXX.php`

## Güncelleme Geçmişi

- İlk oluşturulma: Lifecycle callbacks ve unique constraint ile
- Timestamp alanları: PrePersist/PreUpdate ile otomatik dolduruluyor
