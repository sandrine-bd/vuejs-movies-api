<?php

namespace App\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public function __construct(private NormalizerInterface $decorated, private EntityManagerInterface $entityManager)
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function normalize($object, $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        $data = $this->decorated->normalize($object, $format, $context);
        if (is_array($data)) {
            $dateFormat = 'Y-m-d H:i:s';
            /*
             * These timestamp fields are added via traits so we add them manually, without being able to set their serialization groups
             */
            if (is_callable([$object, 'getCreatedAt']) && is_callable([$object, 'getUpdatedAt'])) {
                $data['createdAt'] = $object->getCreatedAt()->format($dateFormat);
                $data['updatedAt'] = $object->getUpdatedAt()->format($dateFormat);
            }
            if (is_callable([$object, 'getDeletedAt'])) {
                $data['deletedAt'] = $object->getDeletedAt()?->format($dateFormat);
            }
        }

        return $data;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return true;
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $obj = $this->decorated->denormalize($data, $type, $format, $context);
        /*
         * Handling fields that are added to entities via traits, with which we can't add groups manually.
         * The updatedAt field is already updated automatically, here we handle createdAt and deletedAt that act like open/activated periods
         */
        if (array_key_exists('createdAt', $data) && is_callable([$obj, 'setCreatedAt'])) {
            $obj->setCreatedAt($data['createdAt'] ? new \DateTime($data['createdAt']) : null);
        }
        if (array_key_exists('deletedAt', $data) && is_callable([$obj, 'setDeletedAt'])) {
            $obj->setDeletedAt($data['deletedAt'] ? new \DateTime($data['deletedAt']) : null);
        }

        return $obj;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        $supported = [];

        // We support all entities handled by Doctrine (all our ApiPlatform resources)
        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $supported[$metadata->getName()] = true;
        }

        return $supported;
    }
}
