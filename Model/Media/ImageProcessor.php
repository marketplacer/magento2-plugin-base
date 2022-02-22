<?php

namespace Marketplacer\Base\Model\Media;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Design\BackendModelFactory;
use Magento\Theme\Model\Design\Config\MetadataProvider;
use Marketplacer\Base\Helper\Image as ImageHelper;
use Marketplacer\Base\Model\Media\Config as MediaConfig;

/**
 * Class ImageProcessor
 * @package Marketplacer\Base\Model\Media
 */
class ImageProcessor
{
    /**
     * @var string
     */
    const FILE_DIR = 'design/file';

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var BackendModelFactory
     */
    protected $backendModelFactory;

    /**
     * @var MetadataProvider
     */
    protected $metadataProvider;

    /**
     * Media Directory object (writable).
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MediaConfig
     */
    protected $mediaConfig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string[]
     */
    protected $allowedImageTypes;

    /**
     * @var int
     */
    protected $maxFileSize;

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * ImageProcessor constructor.
     * @param UploaderFactory $uploaderFactory
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param AdapterFactory $adapterFactory
     * @param ImageHelper $imageHelper
     * @param array $allowedImageTypes
     * @param int $maxFileSize
     * @throws FileSystemException
     */
    public function __construct(
        UploaderFactory $uploaderFactory,
        MediaConfig $mediaConfig,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        AdapterFactory $adapterFactory,
        ImageHelper $imageHelper,
        $allowedImageTypes = MediaConfig::VALID_TYPES,
        $maxFileSize = 0
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->mediaConfig = $mediaConfig;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->allowedImageTypes = $allowedImageTypes;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Save file to eaa media directory
     * @param string[]|string|DataObject $fileId
     * @return string[]
     */
    public function save($fileId)
    {
        if (!$fileId) {
            return ['error' => __('Empty file object'), 'errorcode' => null];
        }

        try {
            if (is_array($fileId)) {
                // Skip upload if it is not new image
                if (empty($fileId['new'])) {
                    return $fileId;
                }

                if (empty($fileId['file'])) {
                    return ['error' => __('Empty file name'), 'errorcode' => null];
                }

                $fileId = [
                    'name'     => basename($fileId['file']),
                    'tmp_name' => $this->getAbsoluteTmpMediaPath() . $fileId['file'],
                    'type'     => $fileId['type'],
                    'size'     => $fileId['size'],
                ];
            }

            $result = $this->upload($this->getAbsoluteMediaPath(), $fileId);
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $result;
    }

    /**
     * Retrieve absolute temp media path
     * @return string
     */
    protected function getAbsoluteTmpMediaPath()
    {
        return $this->mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseTmpMediaPath());
    }

    /**
     * Save image
     * @param string $destination
     * @param string|null $fileId
     * @return array
     */
    protected function upload($destination, $fileId = null)
    {
        $result = ['type' => '', 'size' => '', 'file' => ''];

        $uploader = $this->uploaderFactory->create(['fileId' => $fileId ?: ImageHelper::OPTION_IMAGE_ID]);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $uploader->setAllowedExtensions(MediaConfig::VALID_TYPES);
        $uploader->addValidateCallback('image', $this->adapterFactory->create(), 'validateUploadFile');
        $uploader->addValidateCallback('size', $this, 'validateMaxSize');
        return array_intersect_key($uploader->save($destination), $result);
    }

    /**
     * Retrieve absolute temp media path
     * @return string
     */
    protected function getAbsoluteMediaPath()
    {
        return $this->mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseMediaPath());
    }

    /**
     * Save file to temp eaa media directory
     * @param string|null $fileId
     * @return array
     */
    public function saveToTmp($fileId = null)
    {
        try {
            $result = $this->upload($this->getAbsoluteTmpMediaPath(), $fileId);
            $result['url'] = $this->getTmpMediaUrl($result['file']);
            $result['new'] = 1;
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $result;
    }

    /**
     * Retrieve temp media url
     * @param string $file
     * @return string
     */
    protected function getTmpMediaUrl($file)
    {
        return $this->mediaConfig->getTmpMediaUrl($file);
    }

    /**
     * Validation callback for checking max file size
     * @param string $filePath Path to temporary uploaded file
     * @return void
     * @throws LocalizedException
     */
    public function validateMaxSize($filePath)
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::SYS_TMP);
        if ($this->maxFileSize > 0
            && $directory->stat($directory->getRelativePath($filePath))['size'] > $this->maxFileSize * 1024
        ) {
            throw new LocalizedException(
                __('The file you\'re uploading exceeds the server size limit of %1 kilobytes.', $this->maxFileSize)
            );
        }
    }

    /**
     * Prepare file
     * @param string $file
     * @return string
     */
    protected function prepareFile($file)
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }
}
