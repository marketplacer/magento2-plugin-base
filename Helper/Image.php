<?php

namespace Marketplacer\Base\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\ImageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\ConfigInterface;
use Marketplacer\Base\Model\Media\Config;

/**
 * Class Image
 * @package Marketplacer\Base\Helper
 */
class Image extends \Magento\Catalog\Helper\Image
{
    const OPTION_IMAGE_ID = 'image';
    const OPTION_IMAGE_INFO = 'image_info';

    const OPTION_WIDGET_IMAGE_ID = 'widget_logo';
    const OPTION_WIDGET_LOGO_INFO = 'widget_logo_info';

    const IMAGES_INFO = [
        self::OPTION_IMAGE_ID        => self::OPTION_IMAGE_INFO,
        self::OPTION_WIDGET_IMAGE_ID => self::OPTION_WIDGET_LOGO_INFO
    ];

    /**
     * @var Config
     */
    protected $mediaConfig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var Mime
     */
    protected $mime;

    /**
     * Image constructor.
     * @param Context $context
     * @param ImageFactory $productImageFactory
     * @param Repository $assetRepo
     * @param ConfigInterface $viewConfig
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param Mime $mime
     */
    public function __construct(
        Context $context,
        ImageFactory $productImageFactory,
        Repository $assetRepo,
        ConfigInterface $viewConfig,
        Config $mediaConfig,
        Filesystem $filesystem,
        Mime $mime
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mime = $mime;

        parent::__construct($context, $productImageFactory, $assetRepo, $viewConfig);
    }

    /**
     * Initialize Helper to work with Image
     * @param DataObject|Product $option
     * @param string $imageId
     * @param array $attributes
     * @return $this
     */
    public function init($option, $imageId, $attributes = [])
    {
        parent::init($option, 'marketplacer_record_listing', $attributes);
        $this->setImageFile($this->getOptionImage($option, $imageId));
        return $this;
    }

    /**
     * Get image from option object
     * @param DataObject|null $option
     * @param string $key
     * @return string
     */
    public function getOptionImage(DataObject $option = null, $key = self::OPTION_IMAGE_ID)
    {
        if (null === $option) {
            $option = $this->getProduct();
        }
        return $option->getData($key);
    }

    /**
     * Get image information from option
     * @param DataObject $option
     * @return DataObject
     */
    public function addOptionImagesInfo(DataObject $option)
    {
        foreach (array_keys(self::IMAGES_INFO) as $key) {
            $this->addImageInfo($option, $key);
        }

        return $option;
    }

    /**
     * Get image information from option
     * @param DataObject $option
     * @param string $key
     * @return DataObject
     */
    protected function addImageInfo(DataObject $option, $key = self::OPTION_IMAGE_ID)
    {
        if ($image = $option->getData($key)) {
            $fileName = $this->mediaConfig->getBaseMediaPath() . $image;
        }

        $infoKey = self::IMAGES_INFO[$key];
        if (isset($fileName) && $this->mediaDirectory->isExist($fileName)) {
            $stat = $this->mediaDirectory->stat($fileName);
            $imageInfo = [
                [
                    'url'    => $this->getOriginalImageUrl($option, $key),
                    'file'   => $image,
                    'size'   => is_array($stat) ? $stat['size'] : 0,
                    'exists' => true,
                    'type'   => $this->mime->getMimeType($this->mediaDirectory->getAbsolutePath($fileName)),
                ]
            ];
            $option->setData($infoKey, $imageInfo);
        } else {
            $option->unsetData($infoKey);
        }

        return $option;
    }

    /**
     * Get image url from option object
     * @param DataObject $option
     * @param string $key
     * @return string
     */
    public function getOriginalImageUrl($option = null, $key = self::OPTION_IMAGE_ID)
    {
        return $this->mediaConfig->getBaseMediaUrl() . $this->getOptionImage($option, $key);
    }

    /**
     * Get page logo image information from option
     * @param DataObject|array $option
     * @param bool $delete
     * @return string[]
     */
    public function getOptionImageInfo($option, $delete = true)
    {
        return $this->getImageInfo($option, self::OPTION_IMAGE_INFO, $delete);
    }

    /**
     * Get image information from option
     * @param DataObject|array $option
     * @param string $key
     * @param bool $delete
     * @return string[]
     */
    public function getImageInfo($option, $key = self::OPTION_IMAGE_INFO, $delete = true)
    {
        $imageInfo = [];
        if ($option instanceof DataObject) {
            $imageInfo = $option->getData($key);
            if ($delete) {
                $option->unsetData($key);
            }
        } elseif (is_array($option) && isset($option[$key])) {
            $imageInfo = $option[$key];
            if ($delete) {
                unset($option[$key]);
            }
        }

        if (is_array($imageInfo) && !empty($imageInfo)) {
            return current($imageInfo);
        }

        return [];
    }

    /**
     * Get widget logo image information from option
     * @param DataObject|array $option
     * @param bool $delete
     * @return string[]
     */
    public function getOptionWidgetImageInfo($option, $delete = true)
    {
        return $this->getImageInfo($option, self::OPTION_WIDGET_LOGO_INFO, $delete);
    }

    /**
     * Get image information from option
     * @param DataObject|array|string $option
     * @param string $key
     * @return bool
     * @throws FileSystemException
     */
    public function deleteOldImage($option, $key = self::OPTION_IMAGE_ID)
    {
        if ($option instanceof DataObject) {
            $images = $option->getData($key);
        } elseif (is_array($option)) {
            $images = !empty($option[$key]) ? $option[$key] : $option;
        } else {
            $images = (string)$option;
        }

        if (!empty($images)) {
            if (!is_array($images)) {
                $images = [$images];
            }

            foreach ($images as $image) {
                if (!$image) {
                    continue;
                }
                $fileName = $this->mediaConfig->getBaseMediaPath() . $image;
                $this->mediaDirectory->delete($fileName);
            }

            return true;
        }

        return false;
    }
}
