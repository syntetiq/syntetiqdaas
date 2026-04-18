<?php

namespace SyntetiQ\Bundle\ModelBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;

class ImageSizeExtension extends AbstractExtension
{
    const WIDTH = 'width';
    const HEIGHT = 'height';

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('imageSize', [$this, 'getImageSize'])
        ];
    }

    /**
     * Get age as number of years.
     *
     * @param string $sizeCode
     *
     * @return array
     */
    public function getImageSize($sizeCode)
    {
        $result = [
            self::WIDTH => 0,
            self::HEIGHT => 0
        ];

        if ($sizeCode === ImageSize::SIZE_320_320) {
            $result = [
                self::WIDTH => 320,
                self::HEIGHT => 320
            ];
        }
        if ($sizeCode === ImageSize::SIZE_640_640) {
            $result = [
                self::WIDTH => 640,
                self::HEIGHT => 640
            ];
        }
        if ($sizeCode === ImageSize::SIZE_1280_1280) {
            $result = [
                self::WIDTH => 1280,
                self::HEIGHT => 1280
            ];
        }

        return $result;
    }
}
