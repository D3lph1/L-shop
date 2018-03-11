<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Items\Add;

use App\DataTransferObjects\Admin\Items\Add\Add;
use App\Entity\EnchantmentItem;
use App\Entity\Item;
use App\Exceptions\Enchantment\DoesNotExistException;
use App\Exceptions\InvalidArgumentTypeException;
use App\Exceptions\UnexpectedValueException;
use App\Repository\Enchantment\EnchantmentRepository;
use App\Repository\Item\ItemRepository;
use App\Services\Item\Image\Hashing\Hasher;
use App\Services\Item\Image\Image;
use App\Services\Item\Type;
use Illuminate\Http\UploadedFile;

class AddHandler
{
    private const IMAGE_DEFAULT = 'default';

    private const IMAGE_UPLOAD = 'upload';

    private const IMAGE_BROWSE = 'browse';

    /**
     * @var ItemRepository
     */
    private $repository;

    /**
     * @var EnchantmentRepository
     */
    private $enchantmentRepository;

    /**
     * @var Hasher
     */
    private $imageHasher;

    public function __construct(ItemRepository $repository, EnchantmentRepository $enchantmentRepository, Hasher $imageHasher)
    {
        $this->repository = $repository;
        $this->enchantmentRepository = $enchantmentRepository;
        $this->imageHasher = $imageHasher;
    }

    public function handle(Add $dto): void
    {
        $image = $this->imageName($dto->getImageType(), $dto->getFile() ?: $dto->getImageName());

        $item = (new Item($dto->getName(), $dto->getItemType(), $dto->getGameId()))
            ->setDescription($dto->getDescription())
            ->setType($dto->getItemType())
            ->setImage($image)
            ->setExtra($dto->getExtra());

        if ($dto->getItemType() === Type::ITEM) {
            foreach ($dto->getEnchantments() as $each) {
                $enchantment = $this->enchantmentRepository->find($each->getId());
                if ($enchantment === null) {
                    throw new DoesNotExistException($each->getId());
                }

                $ei = new EnchantmentItem($enchantment, $each->getLevel());
                $ei->setItem($item);
                $item->addEnchantmentItem($ei);
            }
        }

        $this->repository->create($item);
    }

    /**
     * @param string              $type
     * @param string|UploadedFile $fileOrName
     *
     * @return null|string Image name or null if image default.
     */
    private function imageName(string $type, $fileOrName): ?string
    {
        if ($type === self::IMAGE_DEFAULT) {
            return null;
        }

        if ($type === self::IMAGE_BROWSE) {
            return $fileOrName;
        }

        if ($type === self::IMAGE_UPLOAD) {
            if ($fileOrName instanceof UploadedFile) {
                return $this->moveAndGetName($fileOrName);
            }

            throw new InvalidArgumentTypeException('$fileOrName', UploadedFile::class, $fileOrName);
        }

        throw new UnexpectedValueException('Unexpected value (' . $type . ') of argument $type');
    }

    private function moveAndGetName(UploadedFile $file): string
    {
        $hash = $this->imageHasher->make($file->path());
        $filename = "{$hash}.{$file->getClientOriginalExtension()}";
        $file->move(Image::absolutePath(), $filename);

        return $filename;
    }
}