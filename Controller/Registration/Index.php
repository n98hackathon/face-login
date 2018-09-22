<?php
/**
 * @copyright Copyright (c) 1999-2018 netz98 GmbH (http://www.netz98.de)
 *
 * @see PROJECT_LICENSE.txt
 */

namespace N98\FaceLogin\Controller\Registration;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends Action
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $file;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonFactory;

    /**
     * Selection constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $mbLimit = 4 * 1096 * 1024; // 4MB

        $file = $this->request->getParam('file', false);
        if (!$file || strlen($file) > $mbLimit) {
            $response = $this->jsonFactory->create();
            $response->setStatusHeader(400);

            return $response->setData(
                [
                    'error' => "Bad Request",
                    'reason' => "Invalid file or file is too big",
                ]
            );
        }

        if (!($file = base64_decode($file, true))) {
//            $file = file_get_contents($this->request->getParam('file'));

            $response = $this->jsonFactory->create();
            $response->setStatusHeader(400);

            return $response->setData(
                [
                    'error' => "Bad Request",
                    'reason' => "Invalid encoding",
                ]
            );
        }

        $filePath = "/facelogin/registration/";
        $path = $this->directoryList->getPath('media') . $filePath;

        $ioAdapter = $this->file;
        if (!is_dir($path)) {
            $ioAdapter->mkdir($path, 0775);
        }

        $hash = sha1(microtime(true) . ':' . $file);
        $fileName = $hash . '.png';

        $ioAdapter->open(array('path' => $path));
        if (!$ioAdapter->write($fileName, $file, 0666)) {
            $response = $this->jsonFactory->create();
            $response->setStatusHeader(500);

            return $response->setData(
                [
                    'error' => "Internal Server Error",
                    'reason' => "Can't save file",
                ]
            );
        }

        return $this->jsonFactory->create()->setData(
            [
                'hash' => $hash,
            ]
        );
    }
}
